<?php

namespace App\Models;

use CodeIgniter\Model;

class AboutusModel extends Model
{
    protected $table = 'aboutus';
    protected $primaryKey = 'id';
    protected $allowedFields = ['jb_photo', 'jb_title', 'jb_description', 'as_title', 'as_description', 'as_photo', 'as_name', 'as_position'];
}