<?php
require_once "../config.php";

$window_size = 30;

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

return [
    'window_size' => $window_size,
    'data' => $data
];
?>