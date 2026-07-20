<?php

function getSlidingWindow($conn, $window_size = 30)
{
    $query = "
    SELECT ip_address, COUNT(*) as request_count
    FROM raw_traffic
    WHERE timestamp >= NOW() - INTERVAL $window_size SECOND
    GROUP BY ip_address
    ";

    $result = $conn->query($query);

    $data = [];
    $total_requests = 0;

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        $total_requests += $row['request_count'];
    }

    $unique_ip = count($data);

    return [
        'window_size' => $window_size,
        'data' => $data,
        'total_requests' => $total_requests,
        'unique_ip' => $unique_ip
    ];
}
?>