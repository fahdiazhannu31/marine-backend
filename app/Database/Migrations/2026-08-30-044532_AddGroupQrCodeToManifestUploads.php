<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGroupQrCodeToManifestUploads extends Migration
{
    public function up()
    {
        // Add columns to store group QR codes (JSON structure)
        $this->forge->addColumn('manifest_uploads', [
            'group_qr_codes' => [
                'type'       => 'LONGTEXT',
                'null'       => true,
                'comment'    => 'JSON: {group_name: qr_code_url, ...}',
            ],
            'qr_generation_status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'generated', 'failed'],
                'default'    => 'pending',
                'comment'    => 'Status of group QR generation',
            ],
            'qr_emails_sent_at' => [
                'type'       => 'DATETIME',
                'null'       => true,
                'comment'    => 'Timestamp when group QR emails were sent',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('manifest_uploads', ['group_qr_codes', 'qr_generation_status', 'qr_emails_sent_at']);
    }
}
