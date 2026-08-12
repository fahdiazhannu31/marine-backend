<?php

namespace App\Models;

use CodeIgniter\Model;

class Payment extends Model
{
    protected $table            = 'payments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['user_id', 'group_name', 'checkout_link', 'external_id', 'jml_pax', 'package_id', 'package_name', 'schedule_return_id', 'schedule_departure_id', 'status', 'amount', 'trip_type', 'qr_code', 'attendance', 'transfer_slip', 'created_at', 'updated_at'];
    protected $useTimestamps = true;

    public function getQRCode($userId)
    {
        // Retrieve the latest payment with status 'PAID' or 'SETTLED' for the given user
        $payment = $this->where('user_id', $userId)
            ->whereIn('status', ['PAID', 'SETTLED'])
            ->orderBy('id', 'DESC')
            ->first();

        // Check if payment exists and if it has a QR code generated
        return $payment && !empty($payment['qr_code']) ? $payment['qr_code'] : null;
    }

    public function getPaymentByQRCode($qrCode)
    {
        return $this->where('qr_code', $qrCode)->first();  // Mengambil data berdasarkan QR Code
    }

    public function getByUserId($user_id)
    {
        return $this->where('user_id', $user_id)->first();
    }

    public function getPaymentsWithDetails($user_id)
    {
        return $this->select('payments.*, 
                              users.fullname, 
                              boat_departure.boat_name AS boat_departure_name, 
                              boat_return.boat_name AS boat_return_name,
                              schedule_departure.date AS date_departure, 
                              schedule_return.date AS date_return')
            ->join('schedule AS schedule_departure', 'schedule_departure.id = payments.schedule_departure_id AND schedule_departure.type = "DEPARTURE"', 'left')
            ->join('schedule AS schedule_return', 'schedule_return.id = payments.schedule_return_id AND schedule_return.type = "RETURN"', 'left')
            ->join('users', 'users.id = payments.user_id', 'left')
            ->join('boat AS boat_departure', 'boat_departure.id = schedule_departure.boat_id', 'left')
            ->join('boat AS boat_return', 'boat_return.id = schedule_return.boat_id', 'left')
            ->where('payments.user_id', $user_id)
            ->findAll(); // Using findAll to return results
    }
}
