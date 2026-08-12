<?php

namespace App\Models;

use CodeIgniter\Model;

class BoatPhotoModel extends Model
{
    protected $table            = 'boat_photo';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['boat_id', 'url'];
}
