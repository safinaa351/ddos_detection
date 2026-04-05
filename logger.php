<?php
require_once "config.php";

// ambil data request
$timestamp = date("Y-m-d H:i:s");

// ambil IP client
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
    $ip = $_SERVER['REMOTE_ADDR'];
}

// endpoint (halaman yang diakses)
$endpoint = $_SERVER['REQUEST_URI'];

// method (GET / POST)
$method = $_SERVER['REQUEST_METHOD'];

// user agent
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

// payload size (ukuran request)
$payload_size = $_SERVER['CONTENT_LENGTH'] ?? 0;

// insert ke database
$stmt = $conn->prepare("
    INSERT INTO raw_traffic 
    (timestamp, ip_address, endpoint, method, user_agent, payload_size) 
    VALUES (?, ?, ?, ?, ?, ?)
");

$stmt->bind_param("sssssi", 
    $timestamp, 
    $ip, 
    $endpoint, 
    $method, 
    $user_agent, 
    $payload_size
);

$stmt->execute();
$stmt->close();
$conn->close();
?>