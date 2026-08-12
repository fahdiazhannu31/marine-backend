<?php

namespace App\Models;

use CodeIgniter\Model;

class Routes extends Model
{
    protected $table            = 'routes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['route_name', 'price'];

    public function getRoutes()
    {
        return $this->findAll();  // Mengambil semua data menggunakan findAll
    }
}
