<?php

namespace App\Models;

use CodeIgniter\Model;

class FooterModel extends Model
{
    protected $table = 'footer';
    protected $primaryKey = 'id';
    protected $allowedFields = ['day_op', 'phone', 'copyright'];
}
