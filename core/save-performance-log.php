<?php

function savePerformanceLog($conn, $window_id, $timestamp, $start_time)
{
    $execution_time_ms = (microtime(true) - $start_time) * 1000;

    $stmt = $conn->prepare("
        INSERT INTO performance_log
        (window_id, timestamp, execution_time_ms)
        VALUES (?, ?, ?)
    ");

    $stmt->bind_param(
        "isd",
        $window_id,
        $timestamp,
        $execution_time_ms
    );

    $stmt->execute() or die($stmt->error);

    echo "Execution Time: " .
         round($execution_time_ms, 3) .
         " ms\n";
}
?>