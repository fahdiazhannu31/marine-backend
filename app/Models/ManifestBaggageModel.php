<?php

namespace App\Models;

use CodeIgniter\Model;

class ManifestBaggageModel extends Model
{
    protected $table            = 'manifest_baggage';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'upload_id', 'schedule_id', 'trip_date', 'group_name',
        'bag_label', 'weight_kg', 'bag_count', 'description',
        'direction', 'tag_printed',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /** All baggage rows for a given upload, ordered by group. */
    public function getByUpload(int $uploadId): array
    {
        return $this->where('upload_id', $uploadId)
            ->orderBy('group_name', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /** Mark a tag as printed. */
    public function markPrinted(int $id): bool
    {
        return $this->update($id, ['tag_printed' => 1]);
    }
}
