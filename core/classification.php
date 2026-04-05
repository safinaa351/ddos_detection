<?php
require_once "../config.php";

// =======================
// AMBIL ENTROPY TERBARU
// =======================

$result = $conn->query("
    SELECT normalized_entropy, timestamp 
    FROM entropy_log 
    ORDER BY timestamp DESC 
    LIMIT 1
");

$row_entropy = $result->fetch_assoc();

if (!$row_entropy) {
    echo "No entropy data\n";
    exit;
}

$normalized_entropy = $row_entropy['normalized_entropy'];
$timestamp = $row_entropy['timestamp'];

// =======================
// AMBIL THRESHOLD TERBARU
// =======================

$result = $conn->query("
    SELECT threshold 
    FROM threshold 
    ORDER BY timestamp DESC 
    LIMIT 1
");

$row_threshold = $result->fetch_assoc();

if (!$row_threshold) {
    echo "No threshold data\n";
    exit;
}

$threshold = $row_threshold['threshold'];

// =======================
// KLASIFIKASI
// =======================

$result_status = ($normalized_entropy < $threshold) ? "ATTACK" : "NORMAL";

// =======================
// SIMPAN HASIL
// =======================

$stmt = $conn->prepare("
    INSERT INTO classification
    (timestamp, normalized_entropy, threshold, result)
    VALUES (?, ?, ?, ?)
");

$stmt->bind_param("sdds",
    $timestamp,
    $normalized_entropy,
    $threshold,
    $result_status
);

$stmt->execute();

$conn->close();

echo "NE: $normalized_entropy | Threshold: $threshold | Result: $result_status\n";
?>