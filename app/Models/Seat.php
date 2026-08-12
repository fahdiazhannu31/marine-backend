<?php

namespace App\Models;

use CodeIgniter\Model;

class Seat extends Model
{
    protected $table            = 'seat';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['boat_id', 'seat_number'];

    // In your SeatsModel
    public function getSeatsByBoat($boatId)
    {
        // Example query to fetch seats for a given boat ID
        return $this->db->table('seat')
            ->where('boat_id', $boatId)
            ->get()
            ->getResultArray();
    }
}
