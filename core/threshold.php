<?php
require_once "../config.php";

// =======================
// AMBIL EV
// =======================

$N = 20;

$result = $conn->query("
    SELECT normalized_entropy 
    FROM entropy_log 
    ORDER BY timestamp DESC 
    LIMIT $N
");

$ev = [];

while ($row = $result->fetch_assoc()) {
    $ev[] = $row['normalized_entropy'];
}

if (count($ev) < 2) {
    echo "Not enough EV data\n";
    exit;
}

// =======================
// HITUNG MEAN
// =======================

$mean = array_sum($ev) / count($ev);

// =======================
// HITUNG STDDEV
// =======================

$variance = 0;
foreach ($ev as $val) {
    $variance += pow($val - $mean, 2);
}
$stddev = sqrt($variance / count($ev));

// =======================
// AMBIL k_dynamic TERBARU
// =======================

$result = $conn->query("
    SELECT k_dynamic 
    FROM dynamic_k 
    ORDER BY timestamp DESC 
    LIMIT 1
");

$row = $result->fetch_assoc();

if (!$row) {
    echo "No k_dynamic data\n";
    exit;
}

$k_dynamic = $row['k_dynamic'];

// =======================
// HITUNG THRESHOLD
// =======================

$threshold = $mean - ($k_dynamic * $stddev);

// =======================
// SIMPAN
// =======================

$timestamp = date("Y-m-d H:i:s");

$stmt = $conn->prepare("
    INSERT INTO threshold
    (timestamp, mean, stddev, k_dynamic, threshold)
    VALUES (?, ?, ?, ?, ?)
");

$stmt->bind_param("sdddd",
    $timestamp,
    $mean,
    $stddev,
    $k_dynamic,
    $threshold
);

$stmt->execute();

$conn->close();

echo "Threshold: $threshold | Mean: $mean | StdDev: $stddev\n";
?>