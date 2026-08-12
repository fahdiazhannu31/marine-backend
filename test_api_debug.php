<?php
// Test API endpoint directly without going through router
require 'vendor/autoload.php';

$config = new Config\Database();
$db = \Config\Database::connect();

$rawToken = "930803f2ccc47e3955738c8049f6cf1ab97e7ca719e605f9891b2a26baf7aae3";
$tokenHash = hash('sha256', $rawToken);

echo "Raw Token (first 10): " . substr($rawToken, 0, 10) . "\n";
echo "Token Hash (first 10): " . substr($tokenHash, 0, 10) . "\n";
echo "Current time: " . date('Y-m-d H:i:s') . "\n\n";

// Query 1: Check all tokens in DB
echo "=== All tokens in DB ===\n";
$allTokens = $db->table('api_tokens')->get()->getResultArray();
foreach ($allTokens as $t) {
    echo "ID: {$t['id']}, User: {$t['user_id']}, Token (first 10): " . substr($t['token'], 0, 10) . ", Expires: {$t['expires_at']}\n";
}

echo "\n=== Looking for our token ===\n";
// Query 2: Find matching token
$found = $db->table('api_tokens')
    ->where('token', $tokenHash)
    ->get()
    ->getFirstRow('array');

if ($found) {
    echo "Found token! User ID: {$found['user_id']}, Expires: {$found['expires_at']}\n";
    
    // Check if expired
    $isExpired = strtotime($found['expires_at']) < time();
    echo "Expired? " . ($isExpired ? "YES" : "NO") . "\n";
} else {
    echo "Token NOT found in DB\n";
}

echo "\n=== Check admin status for user 36 ===\n";
$adminCheck = $db->table('auth_groups_users')
    ->where('user_id', 36)
    ->where('group_id', 1)
    ->get()
    ->getFirstRow('array');

echo "Admin check result: " . ($adminCheck ? "YES - User 36 is admin" : "NO - User 36 is not admin") . "\n";
