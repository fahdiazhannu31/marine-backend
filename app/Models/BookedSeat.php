<?php

namespace App\Models;

use CodeIgniter\Model;

class BookedSeat extends Model
{
    protected $table            = 'booked_seats';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['payment_id', 'seat_id'];
}
