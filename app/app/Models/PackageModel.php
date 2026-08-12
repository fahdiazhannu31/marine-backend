<?php

namespace App\Models;

use CodeIgniter\Model;

class PackageModel extends Model
{
    protected $table = 'package'; // Specify the table name
    protected $primaryKey = 'id'; // Specify the primary key
    protected $allowedFields = [
        'id',
        'title',
        'description',
        'price_per_pax',
        'price_per_pax_weekend',
        'photo1',
        'photo2',
        'photo3',
        'pax_count',
        'status'
    ]; // Specify the fields that can be inserted/updated
}
