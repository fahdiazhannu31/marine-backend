<?php
$db = new mysqli('127.0.0.1', 'root', '', 'nama_mrn');
if ($db->connect_error) die('DB Error: ' . $db->connect_error);

$rawToken = "930803f2ccc47e3955738c8049f6cf1ab97e7ca719e605f9891b2a26baf7aae3";
$tokenHash = hash('sha256', $rawToken);

echo "Raw Token (first 10): " . substr($rawToken, 0, 10) . "\n";
echo "Token Hash (first 10): " . substr($tokenHash, 0, 10) . "\n";
echo "Current time: " . date('Y-m-d H:i:s') . "\n\n";

// Query 1: Check all tokens in DB
echo "=== All tokens in DB ===\n";
$result = $db->query('SELECT id, user_id, token, expires_at FROM api_tokens');
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']}, User: {$row['user_id']}, Token (first 10): " . substr($row['token'], 0, 10) . ", Expires: {$row['expires_at']}\n";
}

echo "\n=== Looking for our token ===\n";
// Query 2: Find matching token
$result = $db->query("SELECT * FROM api_tokens WHERE token = '" . $db->real_escape_string($tokenHash) . "'");
if ($result->num_rows > 0) {
    $found = $result->fetch_assoc();
    echo "Found token! User ID: {$found['user_id']}, Expires: {$found['expires_at']}\n";
    
    // Check if expired
    $isExpired = strtotime($found['expires_at']) < time();
    echo "Expired? " . ($isExpired ? "YES" : "NO") . "\n";
} else {
    echo "Token NOT found in DB\n";
}

echo "\n=== Check admin status for user 36 ===\n";
$result = $db->query('SELECT * FROM auth_groups_users WHERE user_id = 36 AND group_id = 1');
echo "Admin check result: " . ($result->num_rows > 0 ? "YES - User 36 is admin" : "NO") . "\n";

$db->close();
