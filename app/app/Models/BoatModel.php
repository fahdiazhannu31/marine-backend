<?php

namespace App\Models;

use CodeIgniter\Model;

class BoatModel extends Model
{
    protected $table            = 'boat';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['boat_name', 'capacity', 'photo1', 'photo2', 'photo3', 'photo4', 'photo5'];

    public function getDeparture()
    {
        $this->db->table('boat')
            ->select('*')
            ->where('type', 'DEPARTURE')
            ->get()
            ->getResult();
    }

    public function getReturn()
    {
        $this->db->table('boat')
            ->select('*')
            ->where('type', 'RETURN')
            ->get()
            ->getResult();
    }
}
