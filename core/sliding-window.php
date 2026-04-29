<?php
require_once "../config.php";

// window (detik)
$window_size = 30;

// ambil waktu sekarang
$now = date("Y-m-d H:i:s");

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

//$conn->close();

// return data ke entropy.php
return $data;
?>