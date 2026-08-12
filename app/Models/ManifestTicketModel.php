<?php

namespace App\Models;

use CodeIgniter\Model;

class ManifestTicketModel extends Model
{
    protected $table            = 'manifest_tickets';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'upload_id', 'schedule_id', 'boat_id', 'direction', 'trip_date',
        'seq_no', 'ket', 'passenger_name', 'group_name', 'agent',
        'package', 'pax_count', 'notes', 'age', 'gender', 'domicile',
        'id_passport', 'seat_id', 'seat_number', 'ticket_code',
        'checked_in', 'checked_in_at', 'cancelled',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /** Get all tickets for an upload, ordered by group then seq_no. */
    public function getByUpload(int $uploadId): array
    {
        return $this->where('upload_id', $uploadId)
            ->orderBy('group_name', 'ASC')
            ->orderBy('seq_no', 'ASC')
            ->findAll();
    }

    /** Bulk-insert ticket rows (array of associative arrays). */
    public function bulkInsert(array $rows): bool
    {
        if (empty($rows)) {
            return true;
        }
        $db = \Config\Database::connect();
        return $db->table($this->table)->insertBatch($rows) !== false;
    }

    /**
     * Assign seat_id + seat_number to a ticket, and also mark
     * the seat as booked in the seat table.
     */
    public function assignSeat(int $ticketId, int $seatId, string $seatNumber): bool
    {
        $db = \Config\Database::connect();
        // Update ticket
        $ok = $db->table($this->table)->update(
            ['seat_id' => $seatId, 'seat_number' => $seatNumber],
            ['id' => $ticketId]
        );
        if ($ok) {
            // Mark seat as booked
            $db->table('seat')->update(['status' => 'booked'], ['id' => $seatId]);
        }
        return $ok;
    }

    /** Get distinct group names for an upload (for baggage management). */
    public function getGroups(int $uploadId): array
    {
        $db = \Config\Database::connect();
        return $db->table($this->table)
            ->select('group_name, COUNT(id) AS pax_count, MAX(pax_count) AS group_size')
            ->where('upload_id', $uploadId)
            ->where('group_name IS NOT NULL', null, false)
            ->where('group_name !=', '')
            ->groupBy('group_name')
            ->orderBy('group_name', 'ASC')
            ->get()
            ->getResultArray();
    }
}
