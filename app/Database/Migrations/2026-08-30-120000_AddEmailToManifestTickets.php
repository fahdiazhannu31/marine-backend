<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEmailToManifestTickets extends Migration
{
    public function up()
    {
        // Add email column to manifest_tickets
        $this->forge->addColumn('manifest_tickets', [
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'comment'    => 'Email address for group lead/contact',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('manifest_tickets', ['email']);
    }
}
