<?php

namespace App\Models;

use CodeIgniter\Model;

class DepartureModel extends Model
{
    protected $table            = 'departure_schedule';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['package_id', 'departure_date', 'departure_time', 'available_slots', 'created_at', 'updated_at'];
}
