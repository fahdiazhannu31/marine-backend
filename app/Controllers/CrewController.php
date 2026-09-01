<?php

namespace App\Controllers;

/**
 * CrewController — manages crew (captain/ABK/GRO/staff) with permanent QR codes.
 *
 * Endpoints:
 *   GET    /api/admin/crew                      — list all crew
 *   POST   /api/admin/crew                      — create crew (auto-generates QR code)
 *   PUT    /api/admin/crew/{id}                 — update crew
 *   DELETE /api/admin/crew/{id}                 — delete crew
 *   GET    /api/admin/crew/{id}/qr-pdf          — stream A7 QR ID card PDF
 *
 *   GET    /api/admin/crew/assignments           — list assignments (optional ?date= or ?schedule_id=)
 *   POST   /api/admin/crew/assignments           — assign crew to schedule
 *   DELETE /api/admin/crew/assignments/{id}      — remove assignment
 *
 *   GET    /api/admin/crew/checkin-by-qr/{qr}   — look up crew + today assignments by QR
 *   POST   /api/admin/crew/checkin               — record check-in { qr_code, schedule_id? }
 */
class CrewController extends ApiController
{
    // ═══════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════

    /** Generate a permanent unique QR token for a crew member. */
    private function generateQrCode(): string
    {
        // CREW_ + 16 random hex chars — short enough to scan easily
        return 'CREW_' . strtoupper(bin2hex(random_bytes(8)));
    }

    /** Role display label. */
    private function roleLabel(string $role): string
    {
        return match ($role) {
            'captain' => 'Captain',
            'abk'     => 'ABK',
            'gro'     => 'GRO',
            'staff'   => 'Staff',
            default   => 'Other',
        };
    }

    // ═══════════════════════════════════════════════════════
    // CREW CRUD
    // ═══════════════════════════════════════════════════════

    // GET /api/admin/crew
    public function index()
    {
        if (!$this->isAdminUser()) return $this->jsonResponse(['error' => 'Forbidden.'], 403);

        $db   = \Config\Database::connect();
        $role = $this->request->getVar('role');

        $qb = $db->table('crew c')
            ->select('c.*')
            ->orderBy('c.role', 'ASC')
            ->orderBy('c.name', 'ASC');

        if ($role) {
            $qb->where('c.role', $role);
        }

        $rows = $qb->get()->getResultArray();

        // Attach today's assignment if any
        $today = date('Y-m-d');
        foreach ($rows as &$row) {
            $assignment = $db->table('crew_assignments ca')
                ->select('ca.*, b.boat_name, s.date as schedule_date')
                ->join('boat b', 'b.id = ca.boat_id', 'left')
                ->join('schedule s', 's.id = ca.schedule_id', 'left')
                ->where('ca.crew_id', $row['id'])
                ->where('ca.trip_date', $today)
                ->get()->getFirstRow('array');
            $row['today_assignment'] = $assignment;

            // Check-in status for today
            $checkin = $db->table('crew_checkins')
                ->where('crew_id', $row['id'])
                ->where('DATE(checked_in_at)', $today)
                ->orderBy('id', 'DESC')
                ->get()->getFirstRow('array');
            $row['checked_in_today'] = $checkin ? true : false;
            $row['checked_in_at']    = $checkin['checked_in_at'] ?? null;
        }
        unset($row);

        return $this->jsonResponse($rows);
    }

    // GET /api/admin/crew/{id}
    public function show(int $id)
    {
        if (!$this->isAdminUser()) return $this->jsonResponse(['error' => 'Forbidden.'], 403);

        $db   = \Config\Database::connect();
        $crew = $db->table('crew')->where('id', $id)->get()->getFirstRow('array');
        if (!$crew) return $this->jsonResponse(['error' => 'Crew not found.'], 404);

        // Attach upcoming assignments
        $crew['assignments'] = $db->table('crew_assignments ca')
            ->select('ca.*, b.boat_name, s.date as schedule_date')
            ->join('boat b', 'b.id = ca.boat_id', 'left')
            ->join('schedule s', 's.id = ca.schedule_id', 'left')
            ->where('ca.crew_id', $id)
            ->where('ca.trip_date >=', date('Y-m-d'))
            ->orderBy('ca.trip_date', 'ASC')
            ->get()->getResultArray();

        // Recent check-ins
        $crew['recent_checkins'] = $db->table('crew_checkins')
            ->where('crew_id', $id)
            ->orderBy('checked_in_at', 'DESC')
            ->limit(10)
            ->get()->getResultArray();

        return $this->jsonResponse($crew);
    }

    // POST /api/admin/crew
    public function create()
    {
        if (!$this->isAdminUser()) return $this->jsonResponse(['error' => 'Forbidden.'], 403);

        $body = $this->request->getJSON(true) ?? [];
        $name = trim($body['name'] ?? '');
        $role = trim($body['role'] ?? 'abk');

        if ($name === '') return $this->jsonResponse(['error' => 'name is required.'], 422);

        $validRoles = ['captain', 'abk', 'gro', 'staff', 'other'];
        if (!in_array($role, $validRoles, true)) {
            return $this->jsonResponse(['error' => 'Invalid role.'], 422);
        }

        $db = \Config\Database::connect();

        // Generate unique QR code
        do {
            $qr = $this->generateQrCode();
        } while ($db->table('crew')->where('qr_code', $qr)->countAllResults() > 0);

        $now = date('Y-m-d H:i:s');
        $id  = $db->table('crew')->insert([
            'name'       => $name,
            'role'       => $role,
            'phone'      => trim($body['phone'] ?? ''),
            'id_number'  => trim($body['id_number'] ?? ''),
            'notes'      => trim($body['notes'] ?? ''),
            'qr_code'    => $qr,
            'active'     => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $crew = $db->table('crew')->where('id', $db->insertID())->get()->getFirstRow('array');

        return $this->jsonResponse(['message' => 'Crew created.', 'crew' => $crew], 201);
    }

    // PUT /api/admin/crew/{id}
    public function update(int $id)
    {
        if (!$this->isAdminUser()) return $this->jsonResponse(['error' => 'Forbidden.'], 403);

        $db   = \Config\Database::connect();
        $crew = $db->table('crew')->where('id', $id)->get()->getFirstRow('array');
        if (!$crew) return $this->jsonResponse(['error' => 'Crew not found.'], 404);

        $body   = $this->request->getJSON(true) ?? [];
        $update = ['updated_at' => date('Y-m-d H:i:s')];

        foreach (['name', 'role', 'phone', 'id_number', 'notes'] as $field) {
            if (isset($body[$field])) {
                $update[$field] = trim((string) $body[$field]);
            }
        }
        if (isset($body['active'])) {
            $update['active'] = (int) $body['active'];
        }

        $db->table('crew')->update($update, ['id' => $id]);
        $updated = $db->table('crew')->where('id', $id)->get()->getFirstRow('array');

        return $this->jsonResponse(['message' => 'Crew updated.', 'crew' => $updated]);
    }

    // DELETE /api/admin/crew/{id}
    public function delete(int $id)
    {
        if (!$this->isAdminUser()) return $this->jsonResponse(['error' => 'Forbidden.'], 403);

        $db   = \Config\Database::connect();
        $crew = $db->table('crew')->where('id', $id)->get()->getFirstRow('array');
        if (!$crew) return $this->jsonResponse(['error' => 'Crew not found.'], 404);

        $db->table('crew_checkins')->where('crew_id', $id)->delete();
        $db->table('crew_assignments')->where('crew_id', $id)->delete();
        $db->table('crew')->where('id', $id)->delete();

        return $this->jsonResponse(['message' => 'Crew deleted.']);
    }

    // ═══════════════════════════════════════════════════════
    // QR ID CARD PDF — Business Card style (85.6 x 54mm landscape)
    // GET /api/admin/crew/{id}/qr-pdf
    // No auth required — the numeric ID is the access token (same pattern as boarding pass)
    public function qrPdf(int $id)
    {
        $db   = \Config\Database::connect();
        $crew = $db->table('crew')->where('id', $id)->get()->getFirstRow('array');
        if (!$crew) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Crew not found.']);
        }

        // ── Role colors ───────────────────────────────────────────────────
        $roleColors = [
            'captain' => [24, 0, 173],
            'abk'     => [0, 100, 200],
            'gro'     => [0, 150, 110],
            'staff'   => [80, 80, 80],
            'other'   => [130, 90, 40],
        ];
        $brandColor  = $roleColors[$crew['role']] ?? [80, 80, 80];
        $luminance   = 0.299 * $brandColor[0] + 0.587 * $brandColor[1] + 0.114 * $brandColor[2];
        $headerText  = $luminance < 160 ? [255, 255, 255] : [20, 20, 20];
        $accentLight = [
            min(255, $brandColor[0] + 220),
            min(255, $brandColor[1] + 220),
            min(255, $brandColor[2] + 220),
        ];

        // ── Generate QR image ─────────────────────────────────────────────
        $qrDir = WRITEPATH . 'uploads/qr_codes/';
        if (!is_dir($qrDir)) mkdir($qrDir, 0775, true);

        $qrFilePath = $qrDir . 'crew_' . $id . '_' . time() . '.png';
        $qrOk = false;
        try {
            $writer = new \Endroid\QrCode\Writer\PngWriter();
            $qrCode = \Endroid\QrCode\QrCode::create($crew['qr_code'])
                ->setEncoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
                ->setSize(320)->setMargin(4)
                ->setForegroundColor(new \Endroid\QrCode\Color\Color(...$brandColor))
                ->setBackgroundColor(new \Endroid\QrCode\Color\Color(255, 255, 255));
            $writer->write($qrCode)->saveToFile($qrFilePath);
            $qrOk = true;
        } catch (\Exception $e) {
            $qrFilePath = null;
        }

        // ── PDF: 85.6 × 54mm landscape (standard business card) ──────────
        $W = 85.6; $H = 54;
        $pdf = new \App\Libraries\BoardingPassPDF('L', 'mm', [$W, $H]);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetTitle('Crew-ID-' . $id);
        $pdf->SetMargins(0, 0, 0);
        $pdf->AddPage();

        // ── Background ────────────────────────────────────────────────────
        // Full white card
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(0, 0, $W, $H, 'F');

        // Top header band (full width, brand color)
        $headerH = 15;
        $pdf->SetFillColor(...$brandColor);
        $pdf->Rect(0, 0, $W, $headerH, 'F');

        // Subtle accent strip at bottom
        $pdf->SetFillColor(...$accentLight);
        $pdf->Rect(0, $H - 7, $W, 7, 'F');

        // ── Header content ─────────────────────────────────────────────
        // Yacht icon top-left
        $pdf->YachtIconAuto(2.5, 2, 9, 9, '', $headerText[0], $headerText[1], $headerText[2]);

        // "NAMA Marine" top-left after icon
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor(...$headerText);
        $pdf->SetXY(13, 2.5);
        $pdf->Cell(40, 5, 'NAMA Marine', 0, 0, 'L');

        // Role badge top-right
        $roleLbl = strtoupper($this->roleLabel($crew['role']));
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->SetXY($W - 30, 3);
        $pdf->Cell(28, 4, $roleLbl, 0, 0, 'R');

        // "CREW ID" sub-label
        $pdf->SetFont('Arial', '', 6);
        $pdf->SetXY($W - 30, 7.5);
        $pdf->Cell(28, 3.5, 'CREW ID #' . str_pad($id, 4, '0', STR_PAD_LEFT), 0, 0, 'R');

        // ── Body: left info column + right QR ────────────────────────────
        $bodyTop = $headerH + 3;

        // QR (right side, vertically centered in body)
        $qrSize = $H - $headerH - 10;  // ~31mm
        $qrX    = $W - $qrSize - 2;
        $qrY    = $bodyTop;

        if ($qrOk && $qrFilePath && file_exists($qrFilePath)) {
            $pdf->Image($qrFilePath, $qrX, $qrY, $qrSize, $qrSize, 'PNG');
        }

        // QR label below
        $pdf->SetFont('Arial', '', 5);
        $pdf->SetTextColor(160, 160, 160);
        $pdf->SetXY($qrX, $qrY + $qrSize + 0.5);
        $pdf->Cell($qrSize, 3, 'SCAN TO VERIFY', 0, 0, 'C');

        // Vertical dotted separator
        $sepX = $qrX - 3;
        $pdf->SetFillColor(200, 200, 200);
        $pdf->DottedLine($sepX, $bodyTop, $sepX, $H - 9, 0.25, 1.2);

        // Info column
        $cx  = 4;
        $cw  = $sepX - $cx - 2;
        $y   = $bodyTop;

        // Name (large)
        $name = $crew['name'];
        if (mb_strlen($name) > 22) $name = mb_substr($name, 0, 19) . '…';
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(20, 20, 20);
        $pdf->SetXY($cx, $y);
        $pdf->Cell($cw, 7, $name, 0, 0, 'L');
        $y += 7.5;

        // Role subtitle (colored)
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(...$brandColor);
        $pdf->SetXY($cx, $y);
        $pdf->Cell($cw, 4.5, $this->roleLabel($crew['role']), 0, 0, 'L');
        $y += 5.5;

        // Thin rule
        $pdf->SetDrawColor(...$brandColor);
        $pdf->SetLineWidth(0.4);
        $pdf->Line($cx, $y, $cx + $cw * 0.6, $y);
        $y += 3;

        // Contact details
        $details = [];
        if (!empty($crew['phone']))     $details[] = ['Tel:', $crew['phone']];
        if (!empty($crew['id_number'])) $details[] = ['ID:', $crew['id_number']];
        if (!empty($crew['notes']))     $details[] = ['Ket:', mb_substr($crew['notes'], 0, 40)];

        foreach ($details as [$icon, $val]) {
            if ($y > $H - 12) break;
            $pdf->SetFont('Arial', 'B', 6);
            $pdf->SetTextColor(150, 150, 150);
            $pdf->SetXY($cx, $y);
            $pdf->Cell(5, 3.5, $icon, 0, 0, 'L');

            $pdf->SetFont('Arial', '', 6.5);
            $pdf->SetTextColor(40, 40, 40);
            $pdf->SetXY($cx + 5, $y);
            $pdf->Cell($cw - 5, 3.5, $val, 0, 0, 'L');
            $y += 4;
        }

        // ── Bottom accent strip content ───────────────────────────────────
        $pdf->SetFont('Arial', '', 5.5);
        $pdf->SetTextColor(...$brandColor);
        $pdf->SetXY($cx, $H - 5.5);
        $pdf->Cell($W - 8, 4, 'namamarine.cloud  |  ' . $crew['qr_code'], 0, 0, 'L');

        // ── Outer rounded border ──────────────────────────────────────────
        $pdf->SetDrawColor(220, 220, 220);
        $pdf->SetLineWidth(0.3);
        $pdf->Rect(0.3, 0.3, $W - 0.6, $H - 0.6);

        // ── Cleanup & output ──────────────────────────────────────────────
        if ($qrFilePath && file_exists($qrFilePath)) {
            @unlink($qrFilePath);
        }

        $pdfContent = $pdf->Output('S');
        while (ob_get_level() > 0) { ob_end_clean(); }

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="crew-id-' . $id . '.pdf"')
            ->setHeader('Cache-Control', 'max-age=0')
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Expose-Headers', 'Content-Disposition')
            ->setStatusCode(200)
            ->setBody($pdfContent);
    }

    // ═══════════════════════════════════════════════════════
    // ASSIGNMENTS
    // ═══════════════════════════════════════════════════════

    // GET /api/admin/crew/assignments?date=YYYY-MM-DD|schedule_id=X
    public function listAssignments()
    {
        if (!$this->isAdminUser()) return $this->jsonResponse(['error' => 'Forbidden.'], 403);

        $db         = \Config\Database::connect();
        $date       = $this->request->getVar('date') ?: date('Y-m-d');
        $scheduleId = (int) ($this->request->getVar('schedule_id') ?? 0);

        $qb = $db->table('crew_assignments ca')
            ->select('ca.*, c.name as crew_name, c.role, c.qr_code, b.boat_name, s.date as schedule_date')
            ->join('crew c', 'c.id = ca.crew_id', 'left')
            ->join('boat b', 'b.id = ca.boat_id', 'left')
            ->join('schedule s', 's.id = ca.schedule_id', 'left')
            ->orderBy('c.role', 'ASC')
            ->orderBy('c.name', 'ASC');

        if ($scheduleId) {
            $qb->where('ca.schedule_id', $scheduleId);
        } else {
            $qb->where('ca.trip_date', $date);
        }

        $rows = $qb->get()->getResultArray();

        // Attach check-in status
        foreach ($rows as &$row) {
            $checkin = $db->table('crew_checkins')
                ->where('crew_id', $row['crew_id'])
                ->where('assignment_id', $row['id'])
                ->orderBy('id', 'DESC')
                ->get()->getFirstRow('array');
            $row['checked_in']    = $checkin ? true : false;
            $row['checked_in_at'] = $checkin['checked_in_at'] ?? null;
        }
        unset($row);

        return $this->jsonResponse($rows);
    }

    // POST /api/admin/crew/assignments
    // Body: { crew_id, schedule_id, boat_id, trip_date, direction }
    public function createAssignment()
    {
        if (!$this->isAdminUser()) return $this->jsonResponse(['error' => 'Forbidden.'], 403);

        $db   = \Config\Database::connect();
        $body = $this->request->getJSON(true) ?? [];

        $crewId     = (int) ($body['crew_id'] ?? 0);
        $scheduleId = (int) ($body['schedule_id'] ?? 0);
        $boatId     = (int) ($body['boat_id'] ?? 0);
        $tripDate   = $body['trip_date'] ?? date('Y-m-d');
        $direction  = $body['direction'] ?? 'DEPARTURE';

        if (!$crewId) return $this->jsonResponse(['error' => 'crew_id is required.'], 422);

        if (!$db->table('crew')->where('id', $crewId)->countAllResults()) {
            return $this->jsonResponse(['error' => 'Crew not found.'], 404);
        }

        // Prevent duplicate assignment for same crew+schedule
        if ($scheduleId) {
            $exists = $db->table('crew_assignments')
                ->where('crew_id', $crewId)
                ->where('schedule_id', $scheduleId)
                ->where('direction', $direction)
                ->countAllResults();
            if ($exists) {
                return $this->jsonResponse(['error' => 'This crew member is already assigned to this schedule.'], 422);
            }
        }

        $now = date('Y-m-d H:i:s');
        $db->table('crew_assignments')->insert([
            'crew_id'     => $crewId,
            'schedule_id' => $scheduleId ?: null,
            'boat_id'     => $boatId ?: null,
            'trip_date'   => $tripDate,
            'direction'   => $direction,
            'notes'       => trim($body['notes'] ?? ''),
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);

        return $this->jsonResponse(['message' => 'Assignment created.', 'id' => $db->insertID()], 201);
    }

    // DELETE /api/admin/crew/assignments/{id}
    public function deleteAssignment(int $id)
    {
        if (!$this->isAdminUser()) return $this->jsonResponse(['error' => 'Forbidden.'], 403);

        $db = \Config\Database::connect();
        if (!$db->table('crew_assignments')->where('id', $id)->countAllResults()) {
            return $this->jsonResponse(['error' => 'Assignment not found.'], 404);
        }

        $db->table('crew_checkins')->where('assignment_id', $id)->delete();
        $db->table('crew_assignments')->where('id', $id)->delete();

        return $this->jsonResponse(['message' => 'Assignment removed.']);
    }

    // ═══════════════════════════════════════════════════════
    // CREW CHECK-IN (via permanent QR)
    // ═══════════════════════════════════════════════════════

    // GET /api/admin/crew/assignments/calendar?month=YYYY-MM
    // Returns assignments grouped by date for calendar view
    public function assignmentsCalendar()
    {
        if (!$this->isAdminUser()) return $this->jsonResponse(['error' => 'Forbidden.'], 403);

        $db    = \Config\Database::connect();
        $month = $this->request->getVar('month') ?: date('Y-m');

        // Validate format
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            return $this->jsonResponse(['error' => 'Invalid month format. Use YYYY-MM.'], 422);
        }

        $startDate = $month . '-01';
        $endDate   = date('Y-m-t', strtotime($startDate)); // last day of month

        $rows = $db->table('crew_assignments ca')
            ->select('ca.*, c.name as crew_name, c.role, c.phone, c.qr_code, b.boat_name, s.date as schedule_date')
            ->join('crew c', 'c.id = ca.crew_id', 'left')
            ->join('boat b', 'b.id = ca.boat_id', 'left')
            ->join('schedule s', 's.id = ca.schedule_id', 'left')
            ->where('ca.trip_date >=', $startDate)
            ->where('ca.trip_date <=', $endDate)
            ->orderBy('ca.trip_date', 'ASC')
            ->orderBy('c.role', 'ASC')
            ->orderBy('c.name', 'ASC')
            ->get()->getResultArray();

        // Attach check-in status per assignment
        foreach ($rows as &$row) {
            $checkin = $db->table('crew_checkins')
                ->where('crew_id', $row['crew_id'])
                ->where('DATE(checked_in_at)', $row['trip_date'])
                ->orderBy('id', 'DESC')
                ->get()->getFirstRow('array');
            $row['checked_in']    = $checkin ? true : false;
            $row['checked_in_at'] = $checkin['checked_in_at'] ?? null;
        }
        unset($row);

        // Group by date
        $byDate = [];
        foreach ($rows as $row) {
            $date = substr($row['trip_date'], 0, 10);
            if (!isset($byDate[$date])) {
                $byDate[$date] = [
                    'date'        => $date,
                    'assignments' => [],
                    'total'       => 0,
                    'checked_in'  => 0,
                    'roles'       => [],
                ];
            }
            $byDate[$date]['assignments'][] = $row;
            $byDate[$date]['total']++;
            if ($row['checked_in']) $byDate[$date]['checked_in']++;
            $role = $row['role'] ?? 'other';
            if (!in_array($role, $byDate[$date]['roles'], true)) {
                $byDate[$date]['roles'][] = $role;
            }
        }

        return $this->jsonResponse([
            'month'      => $month,
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'by_date'    => $byDate,
            'total_assignments' => count($rows),
        ]);
    }

    // GET /api/admin/crew/assignments?date=YYYY-MM-DD|schedule_id=X
    public function listAssignments()
    {
        if (!$this->isAdminUser()) return $this->jsonResponse(['error' => 'Forbidden.'], 403);

        $db   = \Config\Database::connect();
        $crew = $db->table('crew')->where('qr_code', $qr)->get()->getFirstRow('array');

        if (!$crew) {
            return $this->jsonResponse(['error' => 'Crew QR code not recognised.'], 404);
        }

        $today = date('Y-m-d');

        // Today's assignments
        $assignments = $db->table('crew_assignments ca')
            ->select('ca.*, b.boat_name, s.date as schedule_date')
            ->join('boat b', 'b.id = ca.boat_id', 'left')
            ->join('schedule s', 's.id = ca.schedule_id', 'left')
            ->where('ca.crew_id', $crew['id'])
            ->where('ca.trip_date', $today)
            ->get()->getResultArray();

        // Already checked-in today?
        $checkins = $db->table('crew_checkins')
            ->where('crew_id', $crew['id'])
            ->where('DATE(checked_in_at)', $today)
            ->orderBy('checked_in_at', 'DESC')
            ->get()->getResultArray();

        return $this->jsonResponse([
            'crew'             => $crew,
            'today_assignments' => $assignments,
            'checkins_today'   => $checkins,
            'already_checked_in' => count($checkins) > 0,
        ]);
    }

    // POST /api/admin/crew/checkin
    // Body: { qr_code, schedule_id? }
    public function checkin()
    {
        if (!$this->isAdminUser()) return $this->jsonResponse(['error' => 'Forbidden.'], 403);

        $db   = \Config\Database::connect();
        $body = $this->request->getJSON(true) ?? [];
        $qr   = trim($body['qr_code'] ?? '');

        if (!$qr) return $this->jsonResponse(['error' => 'qr_code is required.'], 422);

        $crew = $db->table('crew')->where('qr_code', $qr)->get()->getFirstRow('array');
        if (!$crew) return $this->jsonResponse(['error' => 'Crew QR code not recognised.'], 404);

        $scheduleId = (int) ($body['schedule_id'] ?? 0) ?: null;
        $today      = date('Y-m-d');

        // Find matching assignment
        $assignment = null;
        if ($scheduleId) {
            $assignment = $db->table('crew_assignments')
                ->where('crew_id', $crew['id'])
                ->where('schedule_id', $scheduleId)
                ->get()->getFirstRow('array');
        } else {
            $assignment = $db->table('crew_assignments')
                ->where('crew_id', $crew['id'])
                ->where('trip_date', $today)
                ->get()->getFirstRow('array');
        }

        $now = date('Y-m-d H:i:s');
        $db->table('crew_checkins')->insert([
            'crew_id'        => $crew['id'],
            'assignment_id'  => $assignment['id'] ?? null,
            'schedule_id'    => $scheduleId ?? ($assignment['schedule_id'] ?? null),
            'checked_in_at'  => $now,
            'note'           => trim($body['note'] ?? ''),
        ]);

        return $this->jsonResponse([
            'message'    => $crew['name'] . ' checked in at ' . $now,
            'crew'       => $crew,
            'assignment' => $assignment,
            'checked_in_at' => $now,
        ]);
    }
}
