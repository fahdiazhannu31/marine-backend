<?php

namespace App\Models;

use CodeIgniter\Model;

class ApiTokenModel extends Model
{
    protected $table         = 'api_tokens';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['user_id', 'token', 'expires_at'];
    protected $useTimestamps = true;
    protected $updatedField  = '';   // no updated_at column

    /**
     * Generate a cryptographically secure token for a user.
     * Old tokens for the same user are removed first.
     */
    public function generateFor(int $userId, int $ttlHours = 720): string
    {
        // Revoke previous tokens for this user
        $this->where('user_id', $userId)->delete();

        $raw   = bin2hex(random_bytes(32));          // 64-char hex
        $token = hash('sha256', $raw);               // store hash only

        $this->insert([
            'user_id'    => $userId,
            'token'      => $token,
            'expires_at' => date('Y-m-d H:i:s', time() + $ttlHours * 3600),
        ]);

        return $raw;  // return plain token to caller (shown once)
    }

    /**
     * Find a valid (non-expired) token row and return it, or null.
     * Handles both raw tokens (from old setup) and hashed tokens (new).
     */
    public function findValid(string $rawToken): ?array
    {
        // Try 1: Check if token is already stored as raw (legacy)
        $result = $this->where('token', $rawToken)
                    ->where('expires_at >', date('Y-m-d H:i:s'))
                    ->first();
        
        if ($result) {
            return $result;
        }

        // Try 2: Check if token needs to be hashed (new method)
        $hash = hash('sha256', $rawToken);
        return $this->where('token', $hash)
                    ->where('expires_at >', date('Y-m-d H:i:s'))
                    ->first();
    }

    /** Revoke all tokens for a user (logout). */
    public function revokeFor(int $userId): void
    {
        $this->where('user_id', $userId)->delete();
    }
}
