<?php
require_once "../config.php";

// window (detik)
$window_size = 30;

// ambil waktu sekarang
$now = date("Y-m-d H:i:s");

// DUMMY DATA
/*
$data = [
    ['ip_address' => '192.168.1.10', 'request_count' => 20],   // Normal User
    ['ip_address' => '192.168.1.11', 'request_count' => 5000], // Attacker 1
    ['ip_address' => '192.168.1.12', 'request_count' => 5000], // Attacker 2
    ['ip_address' => '192.168.1.13', 'request_count' => 5000], // Attacker 3
];
*/

// ambil data dalam window terakhir
$query = "
SELECT ip_address, COUNT(*) as request_count
FROM raw_traffic
WHERE timestamp >= NOW() - INTERVAL $window_size SECOND
GROUP BY ip_address
";

$result = $conn->query($query);

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// return data ke entropy.php
return $data;
?>