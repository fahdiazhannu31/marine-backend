<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateYachtSeatsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'schedule_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'seat_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'deck' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'comment'    => 'A, B, C, etc.',
            ],
            'row' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'available',
                'comment'    => 'available, occupied, pending, blocked',
            ],
            'booking_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', false, true);
        $this->forge->addKey(['schedule_id', 'status']);
        $this->forge->addKey('booking_id');
        $this->forge->createTable('yacht_seats');
    }

    public function down()
    {
        $this->forge->dropTable('yacht_seats');
    }
}
