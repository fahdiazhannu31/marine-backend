<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddKkmGuardToManifestUploads extends Migration
{
    public function up()
    {
        $fields = [
            'kkm_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => true,
                'after'      => 'gro_name',
            ],
            'guard_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => true,
                'after'      => 'kkm_name',
            ],
        ];

        $this->forge->addColumn('manifest_uploads', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('manifest_uploads', ['kkm_name', 'guard_name']);
    }
}
