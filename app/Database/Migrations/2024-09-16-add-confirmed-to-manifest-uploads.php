<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddConfirmedToManifestUploads extends Migration
{
    public function up()
    {
        $this->forge->addColumn('manifest_uploads', [
            'confirmed' => [
                'type'       => 'BOOLEAN',
                'default'    => false,
                'null'       => false,
                'after'      => 'vendor_count',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('manifest_uploads', 'confirmed');
    }
}
