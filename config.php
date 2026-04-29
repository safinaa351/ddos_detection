<?php
$host = "localhost";
$user = "root";
$pass = "pass"; // sesuaikan
$db   = "ddos_detection"; // sesuaikan nama database kamu

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>