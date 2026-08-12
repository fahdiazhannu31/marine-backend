<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMetadataToManifestUploads extends Migration
{
    public function up()
    {
        // Add metadata columns parsed from Excel header rows
        $fields = [
            // Trip info from Excel header
            'origin' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'boat_name',
            ],
            'destination' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'origin',
            ],
            // Crew from Excel header (separate from upload-time captain/abk)
            'gro_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => true,
                'after'      => 'abk_names',
            ],
            // Pax category counts from Excel summary rows
            'overnight_count' => [
                'type'       => 'INT',
                'constraint' => 6,
                'null'       => true,
                'after'      => 'total_pax',
            ],
            'daytrip_count' => [
                'type'       => 'INT',
                'constraint' => 6,
                'null'       => true,
                'after'      => 'overnight_count',
            ],
            'staff_count' => [
                'type'       => 'INT',
                'constraint' => 6,
                'default'    => 0,
                'null'       => true,
                'after'      => 'daytrip_count',
            ],
            'foc_count' => [
                'type'       => 'INT',
                'constraint' => 6,
                'default'    => 0,
                'null'       => true,
                'after'      => 'staff_count',
            ],
            'vendor_count' => [
                'type'       => 'INT',
                'constraint' => 6,
                'default'    => 0,
                'null'       => true,
                'after'      => 'foc_count',
            ],
        ];

        $this->forge->addColumn('manifest_uploads', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('manifest_uploads', [
            'origin', 'destination', 'gro_name',
            'overnight_count', 'daytrip_count',
            'staff_count', 'foc_count', 'vendor_count',
        ]);
    }
}
