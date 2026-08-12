<?php

namespace App\Models;

use CodeIgniter\Model;

class HomeModel extends Model
{
    protected $table = 'home';
    protected $primaryKey = 'id';
    protected $allowedFields = ['jb_photo', 'jb_title', 'jb_desc'];
}

