<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'nama_mrn');
if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);

$result = $conn->query('SELECT id, user_id, token, expires_at FROM api_tokens WHERE user_id = 36 LIMIT 1');
$row = $result->fetch_assoc();

echo json_encode($row ?: ['error' => 'No token found']);

// Also check if user 36 is in admin group
$admin_check = $conn->query('SELECT group_id FROM auth_groups_users WHERE user_id = 36 AND group_id = 1');
echo "\nAdmin group check: " . ($admin_check->num_rows > 0 ? "YES" : "NO");

$conn->close();
