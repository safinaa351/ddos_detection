<?php
require_once "/home/main/ddos_skripsi/ddos_detection/config.php";

$query = "SELECT ip, request_count, probability, timestamp 
          FROM suspicious_ip 
          ORDER BY id DESC LIMIT 10";

$result = $conn->query($query);
$logs = [];

while($row = $result->fetch_assoc()) {
    $row['probability'] = (float)$row['probability'];
    $row['probability_fmt'] = number_format($row['probability'], 3);
    $logs[] = $row;
}

header('Content-Type: application/json');
echo json_encode($logs);