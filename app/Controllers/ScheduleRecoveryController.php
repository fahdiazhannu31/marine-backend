<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

/**
 * ScheduleRecoveryController
 * 
 * Utility controller to recover accidentally deleted schedules
 * that still have manifest data
 */
class ScheduleRecoveryController extends ResourceController
{
    protected $format = 'json';

    /**
     * Check if user is admin
     */
    private function isAdminUser(): bool
    {
        $auth = service('auth');
        $user = $auth->user();
        return $user && in_array($user->role, ['admin', 'superadmin']);
    }

    /**
     * Find orphaned manifests (schedule deleted but manifest exists)
     * 
     * GET /api/admin/schedule-recovery/orphaned
     */
    public function findOrphanedManifests()
    {
        if (!$this->isAdminUser()) {
            return $this->respond(['error' => 'Forbidden'], 403);
        }

        $db = \Config\Database::connect();

        // Find manifest uploads where schedule doesn't exist
        $orphaned = $db->query("
            SELECT 
                mu.id as upload_id,
                mu.schedule_id,
                mu.boat_id,
                mu.boat_name,
                mu.trip_date,
                mu.direction,
                mu.total_pax,
                mu.status,
                COUNT(mt.id) as total_tickets,
                SUM(CASE WHEN mt.checked_in = 1 THEN 1 ELSE 0 END) as checked_in_count,
                MAX(mt.checked_in_at) as last_checkin_at
            FROM manifest_uploads mu
            LEFT JOIN schedule s ON s.id = mu.schedule_id
            LEFT JOIN manifest_tickets mt ON mt.upload_id = mu.id
            WHERE s.id IS NULL
            GROUP BY mu.id
            ORDER BY mu.trip_date DESC, mu.id DESC
        ")->getResultArray();

        return $this->respond([
            'success' => true,
            'orphaned_manifests' => $orphaned,
            'count' => count($orphaned),
        ]);
    }

    /**
     * Restore a deleted schedule
     * 
     * POST /api/admin/schedule-recovery/restore
     * Body: { "upload_id": 123 }
     */
    public function restoreSchedule()
    {
        if (!$this->isAdminUser()) {
            return $this->respond(['error' => 'Forbidden'], 403);
        }

        $uploadId = $this->request->getJSON()->upload_id ?? null;

        if (!$uploadId) {
            return $this->respond(['error' => 'upload_id is required'], 400);
        }

        $db = \Config\Database::connect();

        // Get manifest upload details
        $upload = $db->table('manifest_uploads')
            ->where('id', $uploadId)
            ->get()
            ->getFirstRow('array');

        if (!$upload) {
            return $this->respond(['error' => 'Manifest upload not found'], 404);
        }

        $scheduleId = $upload['schedule_id'];
        $boatId = $upload['boat_id'];
        $tripDate = $upload['trip_date'];
        $direction = $upload['direction'] ?? 'DEPARTURE';

        // Check if schedule already exists
        $existingSchedule = $db->table('schedule')
            ->where('id', $scheduleId)
            ->get()
            ->getFirstRow('array');

        if ($existingSchedule) {
            return $this->respond([
                'success' => true,
                'message' => 'Schedule already exists (no restoration needed)',
                'schedule_id' => $scheduleId,
            ]);
        }

        // Re-create the schedule
        try {
            $db->query("
                INSERT INTO schedule (id, boat_id, type, date, created_at, updated_at)
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ", [$scheduleId, $boatId, $direction, $tripDate]);

            log_message('info', "Schedule restored: ID={$scheduleId}, boat_id={$boatId}, date={$tripDate}");

            return $this->respond([
                'success' => true,
                'message' => 'Schedule restored successfully',
                'schedule_id' => $scheduleId,
                'boat_id' => $boatId,
                'trip_date' => $tripDate,
                'direction' => $direction,
            ]);

        } catch (\Exception $e) {
            log_message('error', "Failed to restore schedule: " . $e->getMessage());
            return $this->respond([
                'error' => 'Failed to restore schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Batch restore all orphaned schedules
     * 
     * POST /api/admin/schedule-recovery/restore-all
     */
    public function restoreAllOrphaned()
    {
        if (!$this->isAdminUser()) {
            return $this->respond(['error' => 'Forbidden'], 403);
        }

        $db = \Config\Database::connect();

        // Get all orphaned manifests
        $orphaned = $db->query("
            SELECT 
                mu.id as upload_id,
                mu.schedule_id,
                mu.boat_id,
                mu.trip_date,
                mu.direction
            FROM manifest_uploads mu
            LEFT JOIN schedule s ON s.id = mu.schedule_id
            WHERE s.id IS NULL
        ")->getResultArray();

        if (empty($orphaned)) {
            return $this->respond([
                'success' => true,
                'message' => 'No orphaned manifests found',
                'restored_count' => 0,
            ]);
        }

        $restored = [];
        $failed = [];

        foreach ($orphaned as $item) {
            try {
                $db->query("
                    INSERT INTO schedule (id, boat_id, type, date, created_at, updated_at)
                    VALUES (?, ?, ?, ?, NOW(), NOW())
                ", [
                    $item['schedule_id'],
                    $item['boat_id'],
                    $item['direction'] ?? 'DEPARTURE',
                    $item['trip_date'],
                ]);

                $restored[] = $item['schedule_id'];
                
                log_message('info', "Batch restore: schedule_id={$item['schedule_id']}");

            } catch (\Exception $e) {
                $failed[] = [
                    'schedule_id' => $item['schedule_id'],
                    'error' => $e->getMessage(),
                ];
                log_message('error', "Batch restore failed for schedule_id={$item['schedule_id']}: " . $e->getMessage());
            }
        }

        return $this->respond([
            'success' => true,
            'message' => "Restored {count($restored)} schedules",
            'restored_count' => count($restored),
            'restored_ids' => $restored,
            'failed_count' => count($failed),
            'failed' => $failed,
        ]);
    }
}
