<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'nama_mrn');
if ($conn->connect_error) die('DB Error');

// Get token from database
$result = $conn->query("SELECT token FROM api_tokens WHERE user_id = 36 LIMIT 1");
$row = $result->fetch_assoc();
$token = $row['token'];
$conn->close();

echo "Token from DB: " . substr($token, 0, 30) . "...\n";

// Make curl request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8080/api/admin/bookings/settled");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $http_code\n";
echo "Response (first 200 chars):\n";
echo substr($response, 0, 200) . "\n";
