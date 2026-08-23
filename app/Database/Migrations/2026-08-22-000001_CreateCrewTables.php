<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Creates:
 *   crew             — master crew data (captain, ABK, GRO, staff) with permanent QR
 *   crew_assignments — assigns a crew member to a specific schedule (date + boat)
 *   crew_checkins    — records each check-in event per assignment
 */
class CreateCrewTables extends Migration
{
    public function up()
    {
        // ── crew ─────────────────────────────────────────────────────────
        if (!$this->db->tableExists('crew')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'auto_increment' => true,
                ],
                'name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                ],
                'role' => [
                    'type'       => 'ENUM',
                    'constraint' => ['captain', 'abk', 'gro', 'staff', 'other'],
                    'default'    => 'abk',
                ],
                'phone' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'null'       => true,
                ],
                'id_number' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'comment'    => 'NIK / KTP / Seaman Book',
                ],
                'notes' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                // Permanent QR code — generated once, never changes
                'qr_code' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 80,
                    'null'       => true,
                    'unique'     => true,
                    'comment'    => 'CREW_<ULID> — permanent identifier embedded in QR',
                ],
                'active' => [
                    'type'    => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                ],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('qr_code');
            $this->forge->createTable('crew');
        }

        // ── crew_assignments ──────────────────────────────────────────────
        if (!$this->db->tableExists('crew_assignments')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'auto_increment' => true,
                ],
                'crew_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                ],
                'schedule_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                    'comment'    => 'References schedule.id',
                ],
                'boat_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'trip_date' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'direction' => [
                    'type'       => 'ENUM',
                    'constraint' => ['DEPARTURE', 'RETURN', 'BOTH'],
                    'default'    => 'DEPARTURE',
                ],
                'notes' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('crew_id');
            $this->forge->addKey('schedule_id');
            $this->forge->addKey('trip_date');
            $this->forge->createTable('crew_assignments');
        }

        // ── crew_checkins ─────────────────────────────────────────────────
        if (!$this->db->tableExists('crew_checkins')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'auto_increment' => true,
                ],
                'crew_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                ],
                'assignment_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                    'comment'    => 'NULL = walk-in scan without pre-assignment',
                ],
                'schedule_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'checked_in_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'note' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('crew_id');
            $this->forge->addKey('assignment_id');
            $this->forge->createTable('crew_checkins');
        }
    }

    public function down()
    {
        $this->forge->dropTable('crew_checkins', true);
        $this->forge->dropTable('crew_assignments', true);
        $this->forge->dropTable('crew', true);
    }
}
