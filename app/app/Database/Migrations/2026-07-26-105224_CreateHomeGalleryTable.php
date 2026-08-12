<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHomeGalleryTable extends Migration
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
            'photo' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'comment'    => 'Nama file gambar di assets_users/images/',
            ],
            'alt_text' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'column_position' => [
                'type'       => 'ENUM',
                'constraint' => ['left', 'center', 'right'],
                'default'    => 'left',
                'comment'    => 'Posisi kolom di gallery: left, center (video), right',
            ],
            'media_type' => [
                'type'       => 'ENUM',
                'constraint' => ['image', 'video'],
                'default'    => 'image',
            ],
            'sort_order' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'comment'    => 'Urutan tampil dalam kolom',
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
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

        $this->forge->addKey('id', true);
        $this->forge->addKey('column_position');
        $this->forge->addKey('is_active');
        $this->forge->createTable('home_gallery');
    }

    public function down()
    {
        $this->forge->dropTable('home_gallery', true);
    }
}
