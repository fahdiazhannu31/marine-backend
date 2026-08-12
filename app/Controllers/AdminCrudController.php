<?php

namespace App\Controllers;

use App\Models\BoatModel;
use App\Models\PackageModel;
use App\Models\ScheduleModel;

/**
 * Master-data CRUD for the React admin panel: boats, packages, schedules.
 * Reuses ApiController's CORS / jsonResponse / isAdminUser plumbing so the
 * same auth + response conventions apply here too.
 */
class AdminCrudController extends ApiController
{
    // ═══════════════════════════════════════════════════════════════
    // BOATS
    // ═══════════════════════════════════════════════════════════════

    // GET /api/admin/boats
    public function boats()
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }

        return $this->jsonResponse((new BoatModel())->orderBy('id', 'DESC')->findAll());
    }

    /**
     * Shared validation + save logic for create/update (both are POST so
     * the photo1 file upload works reliably via $_FILES).
     */
    private function saveBoatFromRequest(?int $id = null)
    {
        $boatName = trim((string) $this->request->getPost('boat_name'));
        $capacity = (int) $this->request->getPost('capacity');

        if ($boatName === '' || $capacity <= 0) {
            return ['error' => 'boat_name and a positive capacity are required.'];
        }

        $data = ['boat_name' => $boatName, 'capacity' => $capacity];

        $existing = $id ? (new BoatModel())->find($id) : null;

        $file = $this->request->getFile('photo1');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            if (!in_array($file->getMimeType(), ['image/jpeg', 'image/jpg', 'image/png'], true)) {
                return ['error' => 'photo1 must be a JPG or PNG image.'];
            }
            if ($file->getSizeByUnit('mb') > 2) {
                return ['error' => 'photo1 is too large (max 2MB).'];
            }
            $newName = $file->getRandomName();
            $file->move(FCPATH . 'assets_users/images/', $newName);
            $data['photo1'] = $newName;

            // Best-effort cleanup of the old photo file
            if ($existing && !empty($existing['photo1'])) {
                $oldPath = FCPATH . 'assets_users/images/' . $existing['photo1'];
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }
        } elseif (!$id) {
            // Schema declares photo1 NOT NULL with no default, so create
            // still needs *something* in there even without an upload.
            $data['photo1'] = '';
        }

        return ['data' => $data];
    }

    // POST /api/admin/boats  (multipart/form-data: boat_name, capacity, photo1?)
    public function createBoat()
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }

        $result = $this->saveBoatFromRequest(null);
        if (isset($result['error'])) {
            return $this->jsonResponse(['error' => $result['error']], 422);
        }

        $model = new BoatModel();
        $id    = $model->insert($result['data'], true);

        return $this->jsonResponse(['message' => 'Boat created.', 'id' => $id], 201);
    }

    // POST /api/admin/boats/{id}  (multipart/form-data; POST used instead of PUT
    // so the photo1 upload works reliably)
    public function updateBoat($id = null)
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }
        if (!$id) {
            return $this->jsonResponse(['error' => 'id is required.'], 422);
        }

        $model = new BoatModel();
        if (!$model->find($id)) {
            return $this->jsonResponse(['error' => 'Boat not found.'], 404);
        }

        $result = $this->saveBoatFromRequest((int) $id);
        if (isset($result['error'])) {
            return $this->jsonResponse(['error' => $result['error']], 422);
        }

        $model->update($id, $result['data']);

        return $this->jsonResponse(['message' => 'Boat updated.']);
    }

    // DELETE /api/admin/boats/{id}
    public function deleteBoat($id = null)
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }
        if (!$id) {
            return $this->jsonResponse(['error' => 'id is required.'], 422);
        }

        $model = new BoatModel();
        $boat  = $model->find($id);
        if (!$boat) {
            return $this->jsonResponse(['error' => 'Boat not found.'], 404);
        }

        $db = \Config\Database::connect();
        $scheduleCount = $db->table('schedule')->where('boat_id', $id)->countAllResults();
        if ($scheduleCount > 0) {
            return $this->jsonResponse([
                'error' => "Can't delete: this boat is used by {$scheduleCount} schedule(s). Delete those schedules first.",
            ], 422);
        }

        if (!empty($boat['photo1'])) {
            $path = FCPATH . 'assets_users/images/' . $boat['photo1'];
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $model->delete($id);

        return $this->jsonResponse(['message' => 'Boat deleted.']);
    }

    // ═══════════════════════════════════════════════════════════════
    // PACKAGES
    // ═══════════════════════════════════════════════════════════════

    // GET /api/admin/packages  (all statuses, unlike the public /api/packages)
    public function packagesAdmin()
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }

        return $this->jsonResponse((new PackageModel())->orderBy('id', 'DESC')->findAll());
    }

    /**
     * Shared validation + save logic for create/update (both are POST so
     * photo1/photo2/photo3 file uploads work reliably via $_FILES).
     */
    private function savePackageFromRequest(?int $id = null)
    {
        $title               = trim((string) $this->request->getPost('title'));
        $description         = trim((string) $this->request->getPost('description'));
        $pricePerPax         = $this->request->getPost('price_per_pax');
        $pricePerPaxWeekend  = $this->request->getPost('price_per_pax_weekend');
        $paxCount            = (int) $this->request->getPost('pax_count');
        $status              = trim((string) $this->request->getPost('status')) ?: 'active';

        if ($title === '' || $pricePerPax === null || $pricePerPax === '' || $paxCount <= 0) {
            return ['error' => 'title, price_per_pax and a positive pax_count are required.'];
        }

        $data = [
            'title'                 => $title,
            'description'           => $description,
            'price_per_pax'         => $pricePerPax,
            'price_per_pax_weekend' => $pricePerPaxWeekend !== '' ? $pricePerPaxWeekend : null,
            'pax_count'             => $paxCount,
            'status'                => $status,
        ];

        $existing = $id ? (new PackageModel())->find($id) : null;

        foreach (['photo1', 'photo2', 'photo3'] as $field) {
            $file = $this->request->getFile($field);
            if ($file && $file->isValid() && !$file->hasMoved()) {
                if (!in_array($file->getMimeType(), ['image/jpeg', 'image/jpg', 'image/png'], true)) {
                    return ['error' => "{$field} must be a JPG or PNG image."];
                }
                if ($file->getSizeByUnit('mb') > 2) {
                    return ['error' => "{$field} is too large (max 2MB)."];
                }
                $newName = $file->getRandomName();
                $file->move(FCPATH . 'assets_users/images/', $newName);
                $data[$field] = $newName;

                // Best-effort cleanup of the old photo file
                if ($existing && !empty($existing[$field])) {
                    $oldPath = FCPATH . 'assets_users/images/' . $existing[$field];
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }
            } elseif (!$id) {
                // On create, a missing photo just stays empty (schema allows it in practice
                // even though it's declared NOT NULL — matches the existing MVC behaviour).
                $data[$field] = $existing ? ($existing[$field] ?? '') : '';
            }
        }

        return ['data' => $data];
    }

    // POST /api/admin/packages  (multipart/form-data)
    public function createPackage()
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }

        $result = $this->savePackageFromRequest(null);
        if (isset($result['error'])) {
            return $this->jsonResponse(['error' => $result['error']], 422);
        }

        $model = new PackageModel();
        $id    = $model->insert($result['data'], true);

        return $this->jsonResponse(['message' => 'Package created.', 'id' => $id], 201);
    }

    // POST /api/admin/packages/{id}  (multipart/form-data; POST used instead of PUT
    // so file uploads work reliably)
    public function updatePackage($id = null)
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }
        if (!$id) {
            return $this->jsonResponse(['error' => 'id is required.'], 422);
        }

        $model = new PackageModel();
        if (!$model->find($id)) {
            return $this->jsonResponse(['error' => 'Package not found.'], 404);
        }

        $result = $this->savePackageFromRequest((int) $id);
        if (isset($result['error'])) {
            return $this->jsonResponse(['error' => $result['error']], 422);
        }

        $model->update($id, $result['data']);

        return $this->jsonResponse(['message' => 'Package updated.']);
    }

    // DELETE /api/admin/packages/{id}
    public function deletePackage($id = null)
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }
        if (!$id) {
            return $this->jsonResponse(['error' => 'id is required.'], 422);
        }

        $model   = new PackageModel();
        $package = $model->find($id);
        if (!$package) {
            return $this->jsonResponse(['error' => 'Package not found.'], 404);
        }

        $db = \Config\Database::connect();
        $bookingCount = $db->table('payments')->where('package_id', $id)->countAllResults();
        if ($bookingCount > 0) {
            return $this->jsonResponse([
                'error' => "Can't delete: this package has {$bookingCount} booking(s). Consider setting its status to inactive instead.",
            ], 422);
        }

        foreach (['photo1', 'photo2', 'photo3'] as $field) {
            if (!empty($package[$field])) {
                $path = FCPATH . 'assets_users/images/' . $package[$field];
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }

        $model->delete($id);

        return $this->jsonResponse(['message' => 'Package deleted.']);
    }

    // ═══════════════════════════════════════════════════════════════
    // SCHEDULES
    // ═══════════════════════════════════════════════════════════════

    // GET /api/admin/schedules
    public function schedules()
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }

        $rows = \Config\Database::connect()
            ->table('schedule s')
            ->select('s.id, s.boat_id, s.type, s.date, s.total_pax, b.boat_name, b.capacity')
            ->join('boat b', 'b.id = s.boat_id', 'left')
            ->orderBy('s.date', 'DESC')
            ->get()
            ->getResultArray();

        return $this->jsonResponse($rows);
    }

    // POST /api/admin/schedules  { boat_id, type, date, total_pax }
    public function createSchedule()
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }

        $body    = $this->request->getJSON(true) ?? [];
        $boatId  = (int) ($body['boat_id'] ?? 0);
        $type    = strtoupper(trim($body['type'] ?? ''));
        $date    = trim($body['date'] ?? '');
        $totalPax = $body['total_pax'] ?? null;

        if (!$boatId || !in_array($type, ['DEPARTURE', 'RETURN'], true) || $date === '') {
            return $this->jsonResponse(['error' => 'boat_id, a valid type (DEPARTURE/RETURN) and date are required.'], 422);
        }

        $boat = (new BoatModel())->find($boatId);
        if (!$boat) {
            return $this->jsonResponse(['error' => 'Boat not found.'], 404);
        }

        // Default to the boat's full capacity if total_pax wasn't given.
        $totalPax = $totalPax !== null && $totalPax !== '' ? (int) $totalPax : (int) $boat['capacity'];

        $model = new ScheduleModel();
        $id    = $model->insert([
            'boat_id'   => $boatId,
            'type'      => $type,
            'date'      => $date,
            'total_pax' => $totalPax,
        ], true);

        return $this->jsonResponse(['message' => 'Schedule created.', 'id' => $id], 201);
    }

    // PUT /api/admin/schedules/{id}  { boat_id, type, date, total_pax }
    public function updateSchedule($id = null)
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }
        if (!$id) {
            return $this->jsonResponse(['error' => 'id is required.'], 422);
        }

        $model    = new ScheduleModel();
        $schedule = $model->find($id);
        if (!$schedule) {
            return $this->jsonResponse(['error' => 'Schedule not found.'], 404);
        }

        $body     = $this->request->getJSON(true) ?? [];
        $boatId   = (int) ($body['boat_id'] ?? 0);
        $type     = strtoupper(trim($body['type'] ?? ''));
        $date     = trim($body['date'] ?? '');
        $totalPax = $body['total_pax'] ?? null;

        if (!$boatId || !in_array($type, ['DEPARTURE', 'RETURN'], true) || $date === '' || $totalPax === null || $totalPax === '') {
            return $this->jsonResponse(['error' => 'boat_id, a valid type (DEPARTURE/RETURN), date and total_pax are required.'], 422);
        }

        $model->update($id, [
            'boat_id'   => $boatId,
            'type'      => $type,
            'date'      => $date,
            'total_pax' => (int) $totalPax,
        ]);

        return $this->jsonResponse(['message' => 'Schedule updated.']);
    }

    // DELETE /api/admin/schedules/{id}
    public function deleteSchedule($id = null)
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }
        if (!$id) {
            return $this->jsonResponse(['error' => 'id is required.'], 422);
        }

        $model = new ScheduleModel();
        if (!$model->find($id)) {
            return $this->jsonResponse(['error' => 'Schedule not found.'], 404);
        }

        $db = \Config\Database::connect();
        $bookingCount = $db->table('payments')
            ->groupStart()
                ->where('schedule_departure_id', $id)
                ->orWhere('schedule_return_id', $id)
            ->groupEnd()
            ->countAllResults();

        if ($bookingCount > 0) {
            return $this->jsonResponse([
                'error' => "Can't delete: this schedule has {$bookingCount} booking(s) attached to it.",
            ], 422);
        }

        $model->delete($id);

        return $this->jsonResponse(['message' => 'Schedule deleted.']);
    }
}
