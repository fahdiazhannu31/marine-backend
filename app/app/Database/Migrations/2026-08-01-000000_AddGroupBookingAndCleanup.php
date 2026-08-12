<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds support for "group booking" (1 pemesan/group, banyak nama penumpang
 * per kursi) and removes tables that are not used anywhere in the codebase:
 *
 *  - `seats`       -> dead table, no model/controller references it at all.
 *                     The app actually uses `seat` (singular) + `booked_seats`.
 *  - `yacht_seats` -> only referenced by ApiController::adminYachtSeats(),
 *                     adminAssignSeats(), adminGenerateTicket() and
 *                     generateDefaultSeats(), none of which are called by the
 *                     React frontend (it uses adminBoatSeats/adminBookedSeats
 *                     + /insert-bookedseats against `seat`/`booked_seats`
 *                     instead). Also used to have 2 migration files declaring
 *                     the exact same PHP class name, which is a fatal error
 *                     waiting to happen — both files were removed.
 *  - `photo_boat`  -> empty table, zero rows, zero code references.
 */
class AddGroupBookingAndCleanup extends Migration
{
    public function up()
    {
        // ── 1. payments.group_name ───────────────────────────────────────
        // Name of the "group" for this booking. Defaults to the booker's
        // fullname if left blank, but can be overridden (e.g. company /
        // family / trip name) when the booking is created.
        if (!$this->db->fieldExists('group_name', 'payments')) {
            $this->forge->addColumn('payments', [
                'group_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                    'after'      => 'user_id',
                ],
            ]);
        }

        // ── 2. booking_passengers ────────────────────────────────────────
        // One row per pax in a booking. `seat_id` starts NULL (seats are
        // only chosen by the admin after payment is settled) and gets
        // filled in when the admin assigns seats via /insert-bookedseats.
        if (!$this->db->tableExists('booking_passengers')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'payment_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                ],
                'seat_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey('payment_id');
            $this->forge->addKey('seat_id');
            $this->forge->createTable('booking_passengers');
        }

        // ── 3. Drop unused tables ─────────────────────────────────────────
        foreach (['seats', 'yacht_seats', 'photo_boat'] as $table) {
            if ($this->db->tableExists($table)) {
                $this->forge->dropTable($table, true);
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('booking_passengers', true);

        if ($this->db->fieldExists('group_name', 'payments')) {
            $this->forge->dropColumn('payments', 'group_name');
        }

        // Note: `seats`, `yacht_seats`, `photo_boat` are intentionally NOT
        // recreated on rollback since they were unused/empty/dead tables.
    }
}
