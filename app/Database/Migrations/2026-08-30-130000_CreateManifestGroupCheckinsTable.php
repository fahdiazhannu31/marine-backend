<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateManifestGroupCheckinsTable extends Migration
{
    public function up()
    {
        $this->forge->createTable('manifest_group_checkins', false, [
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'auto_increment' => true,
                'unsigned'       => true,
            ],
            'upload_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'group_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'checked_in_at' => [
                'type' => 'DATETIME',
            ],
            'direction' => [
                'type'       => 'ENUM',
                'constraint' => ['DEPARTURE', 'RETURN'],
                'default'    => 'DEPARTURE',
            ],
            'notes' => [
                'type'       => 'TEXT',
                'null'       => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id', 'manifest_group_checkins');
        $this->forge->addKey(['upload_id', 'group_name'], 'manifest_group_checkins');
        $this->forge->addKey('checked_in_at', 'manifest_group_checkins');
    }
    }

    public function down()
    {
        $this->forge->dropTable('manifest_group_checkins');
    }
}
