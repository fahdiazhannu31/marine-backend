<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class HomeGallerySeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $data = [
            // Kolom kiri — 3 foto
            [
                'photo'           => '1.jpg',
                'alt_text'        => 'Group Photo in Water',
                'column_position' => 'left',
                'media_type'      => 'image',
                'sort_order'      => 1,
                'is_active'       => 1,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'photo'           => '4.jpg',
                'alt_text'        => 'People Walking on Beach Path',
                'column_position' => 'left',
                'media_type'      => 'image',
                'sort_order'      => 2,
                'is_active'       => 1,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'photo'           => '6.jpg',
                'alt_text'        => 'Floating House on Water',
                'column_position' => 'left',
                'media_type'      => 'image',
                'sort_order'      => 3,
                'is_active'       => 1,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],

            // Kolom tengah — 1 video
            [
                'photo'           => 'video.mp4',
                'alt_text'        => 'Island Experience Video',
                'column_position' => 'center',
                'media_type'      => 'video',
                'sort_order'      => 1,
                'is_active'       => 1,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],

            // Kolom kanan — 3 foto
            [
                'photo'           => 'foto3.webp',
                'alt_text'        => 'Group Photo on Beach',
                'column_position' => 'right',
                'media_type'      => 'image',
                'sort_order'      => 1,
                'is_active'       => 1,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'photo'           => 'bridge.jpg',
                'alt_text'        => 'Wooden Bridge over Turquoise Water',
                'column_position' => 'right',
                'media_type'      => 'image',
                'sort_order'      => 2,
                'is_active'       => 1,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'photo'           => 'foto1.webp',
                'alt_text'        => 'Island View',
                'column_position' => 'right',
                'media_type'      => 'image',
                'sort_order'      => 3,
                'is_active'       => 1,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
        ];

        $this->db->table('home_gallery')->insertBatch($data);
    }
}
