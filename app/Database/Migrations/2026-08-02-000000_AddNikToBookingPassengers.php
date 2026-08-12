<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds a NIK (Nomor Induk Kependudukan) field per passenger, captured at
 * booking time alongside their name. Needed for the passenger manifest
 * dashboard.
 */
class AddNikToBookingPassengers extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('booking_passengers') && !$this->db->fieldExists('nik', 'booking_passengers')) {
            $this->forge->addColumn('booking_passengers', [
                'nik' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                    'after'      => 'name',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('nik', 'booking_passengers')) {
            $this->forge->dropColumn('booking_passengers', 'nik');
        }
    }
}
