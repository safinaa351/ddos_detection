<?php
require_once "config.php";

// data yang dicatat: timestamp, ip_address, endpoint, method, user_agent, payload_size
$timestamp = date("Y-m-d H:i:s");

if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
    $ip = $_SERVER['REMOTE_ADDR'];
}

$endpoint = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$payload_size = $_SERVER['CONTENT_LENGTH'] ?? 0;

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