<?php

namespace App\Models;

use CodeIgniter\Model;

class HomeGalleryModel extends Model
{
    protected $table         = 'home_gallery';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'photo',
        'alt_text',
        'column_position',
        'media_type',
        'sort_order',
        'is_active',
    ];
    protected $useTimestamps = true;

    /**
     * Ambil semua item gallery yang aktif, dikelompokkan per kolom
     * Return: ['left' => [...], 'center' => [...], 'right' => [...]]
     */
    public function getGrouped(): array
    {
        $items = $this->where('is_active', 1)
                      ->orderBy('column_position', 'ASC')
                      ->orderBy('sort_order', 'ASC')
                      ->findAll();

        $grouped = ['left' => [], 'center' => [], 'right' => []];
        foreach ($items as $item) {
            $grouped[$item['column_position']][] = $item;
        }

        return $grouped;
    }
}
