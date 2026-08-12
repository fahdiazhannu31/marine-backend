<?php

namespace App\Models;

use CodeIgniter\Model;

class ManifestUploadModel extends Model
{
    protected $table            = 'manifest_uploads';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'schedule_id', 'boat_id', 'direction', 'trip_date', 'boat_name',
        'origin', 'destination',
        'captain_name', 'abk_names', 'gro_name', 'uploaded_by', 'original_file',
        'total_pax', 'overnight_count', 'daytrip_count',
        'staff_count', 'foc_count', 'vendor_count',
        'status', 'notes',
    ];
    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';

    /** Return one upload with its ticket count joined. */
    public function getWithDetail(int $id): ?array
    {
        $db = \Config\Database::connect();
        return $db->table('manifest_uploads mu')
            ->select('mu.*, COUNT(mt.id) AS ticket_count')
            ->join('manifest_tickets mt', 'mt.upload_id = mu.id', 'left')
            ->where('mu.id', $id)
            ->groupBy('mu.id')
            ->get()
            ->getFirstRow('array');
    }

    /** List uploads for a schedule (both directions). */
    public function listBySchedule(int $scheduleId): array
    {
        $db = \Config\Database::connect();
        return $db->table('manifest_uploads mu')
            ->select('mu.*, COUNT(mt.id) AS ticket_count')
            ->join('manifest_tickets mt', 'mt.upload_id = mu.id', 'left')
            ->where('mu.schedule_id', $scheduleId)
            ->groupBy('mu.id')
            ->orderBy('mu.id', 'DESC')
            ->get()
            ->getResultArray();
    }
}
