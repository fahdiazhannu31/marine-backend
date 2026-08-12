<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * One row per pax in a booking (group booking feature).
 *
 * `seat_id` is NULL right after checkout (seats are only assigned by the
 * admin once the payment is SETTLED). When the admin assigns seats via
 * Admin::insertBookedSeats(), each unassigned passenger row (ordered by id)
 * is paired with the seat ids submitted (in the order they were selected)
 * and its `seat_id` is filled in.
 */
class BookingPassengerModel extends Model
{
    protected $table            = 'booking_passengers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['payment_id', 'name', 'nik', 'seat_id'];
    protected $useTimestamps    = false; // we set created_at manually

    /**
     * Insert one row per passenger for a freshly created booking.
     *
     * @param int      $paymentId
     * @param string[] $names names, index-aligned with $niks
     * @param string[] $niks  NIK per passenger (optional, can be shorter
     *                        than $names or contain empty strings)
     */
    public function createForBooking(int $paymentId, array $names, array $niks = []): void
    {
        $now = date('Y-m-d H:i:s');
        $rows = [];

        foreach ($names as $i => $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $rows[] = [
                'payment_id' => $paymentId,
                'name'       => $name,
                'nik'        => isset($niks[$i]) ? trim((string) $niks[$i]) ?: null : null,
                'seat_id'    => null,
                'created_at' => $now,
            ];
        }

        if (!empty($rows)) {
            $this->insertBatch($rows);
        }
    }

    /**
     * All passengers for a booking (id, name, nik, seat_id), in the order
     * they were entered.
     */
    public function getNamesForBooking(int $paymentId): array
    {
        return $this->where('payment_id', $paymentId)
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Assign seat ids (in the given order) to the passengers of a booking
     * that don't have a seat yet (also in order). Extra seats beyond the
     * number of passenger names are simply left unmatched (can happen if
     * the booking was created before this feature existed, i.e. no
     * passenger rows at all).
     *
     * @param int   $paymentId
     * @param int[] $seatIds seat ids, in the order the admin selected them
     */
    public function assignSeatsInOrder(int $paymentId, array $seatIds): void
    {
        $passengers = $this->where('payment_id', $paymentId)
            ->where('seat_id', null)
            ->orderBy('id', 'ASC')
            ->findAll();

        foreach ($seatIds as $i => $seatId) {
            if (!isset($passengers[$i])) {
                break; // more seats than stored passenger names, nothing to map
            }
            $this->update($passengers[$i]['id'], ['seat_id' => (int) $seatId]);
        }
    }
}
