<?php
require_once "../config.php";

$window_size = 30;

$now = date("Y-m-d H:i:s");

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

return $data;
?>