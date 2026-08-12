<?php

namespace App\Models;

use CodeIgniter\Model;

class PackageScheduleModel extends Model
{
    protected $table            = 'package_schedule';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['departure_date', 'departure_date', 'available_slots'];


    public function getDepartureDate($idpackage)
    {
        return $this->db->table('departure_schedule')
            ->select('*')
            ->where('package_id', $idpackage)
            ->get()
            ->getResult();
    }

    public function getReturnDate($idpackage)
    {
        return $this->db->table('return_schedule')
            ->select('*')
            ->where('package_id', $idpackage)
            ->get()
            ->getResult();
    }
}
