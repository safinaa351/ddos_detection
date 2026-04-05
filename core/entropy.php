<?php
require_once "../config.php";

// ambil data window
$data = include "sliding-window.php";

// total request
$total_requests = 0;
foreach ($data as $row) {
    $total_requests += $row['request_count'];
}

// validasi
if ($total_requests == 0) {
    echo "No data in this window\n";
    exit;
}

// hitung entropy
$entropy = 0;

foreach ($data as $row) {
    $p = $row['request_count'] / $total_requests;
    
    if ($p > 0) {
        $entropy -= $p * log($p, 2);
    }
}

// =======================
// NORMALISASI ENTROPY
// =======================

// jumlah IP unik
$n = count($data);

if ($n > 1) {
    $normalized_entropy = $entropy / log($n, 2);
} else {
    $normalized_entropy = 0;
}

// timestamp
$timestamp = date("Y-m-d H:i:s");

// simpan ke DB
$stmt = $conn->prepare("
    INSERT INTO entropy_log
    (unique_ip, entropy, normalized_entropy, total_request, timestamp)
    VALUES (?, ?, ?, ?, ?)
");

$unique_ip = $n;

$stmt->bind_param("iddis", 
    $unique_ip,
    $entropy,
    $normalized_entropy,
    $total_requests,
    $timestamp
);

$stmt->execute();

$conn->close();
?>