<?php
require_once "/home/main/ddos_skripsi/ddos_detection/config.php";

$response = [
    "active" => [],
    "live" => []
];

// ==============================
// 1. ACTIVE ATTACKERS (UNIQUE IP)
// ==============================
$query_active = "
SELECT t1.ip, t1.request_count, t1.probability, t1.timestamp
FROM suspicious_ip t1
INNER JOIN (
    SELECT ip, MAX(id) as max_id
    FROM suspicious_ip
    GROUP BY ip
) t2 ON t1.ip = t2.ip AND t1.id = t2.max_id
ORDER BY t1.request_count DESC
LIMIT 10
";

$result_active = $conn->query($query_active);

if (!$result_active) {
    die("Query Active Error: " . $conn->error);
}

while($row = $result_active->fetch_assoc()) {
    $row['probability'] = (float)$row['probability'];
    $row['probability_fmt'] = number_format($row['probability'], 3);
    $response["active"][] = $row;
}

// ==============================
// 2. LIVE FEED (RAW LOG)
// ==============================
$query_live = "
SELECT ip, request_count, probability, timestamp
FROM suspicious_ip
ORDER BY id DESC
LIMIT 10
";

$result_live = $conn->query($query_live);

if (!$result_live) {
    die("Query Live Error: " . $conn->error);
}

while($row = $result_live->fetch_assoc()) {
    $row['probability'] = (float)$row['probability'];
    $row['probability_fmt'] = number_format($row['probability'], 3);
    $response["live"][] = $row;
}

// ==============================
header('Content-Type: application/json');
echo json_encode($response);