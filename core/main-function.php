<?php
require_once "../config.php";

// 1. AMBIL DATA SLIDING WINDOW
$data = include "sliding-window.php";
$total_requests = 0;
foreach ($data as $row) {
    $total_requests += $row['request_count'];
}

if ($total_requests == 0) {
    die("No data in this window\n");
}

$current_timestamp = date("Y-m-d H:i:s");
$n = count($data);

// 2. HITUNG ENTROPY
$entropy = 0;
foreach ($data as $row) {
    $p = $row['request_count'] / $total_requests;
    if ($p > 0) {
        $entropy -= $p * log($p, 2);
    }
}
$normalized_entropy = ($n > 1) ? ($entropy / log($n, 2)) : 0;

// Simpan Entropy
$stmt = $conn->prepare("INSERT INTO entropy_log (unique_ip, entropy, normalized_entropy, total_request, timestamp) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("iddis", $n, $entropy, $normalized_entropy, $total_requests, $current_timestamp);
$stmt->execute();

// 3. HITUNG DYNAMIC K
$N_window = 20;
$result_ev = $conn->query("SELECT entropy FROM entropy_log ORDER BY timestamp DESC LIMIT $N_window");
$ev = [];
while ($row = $result_ev->fetch_assoc()) { $ev[] = $row['entropy']; }

$emax = (!empty($ev)) ? max($ev) : 0;
$k_dynamic = 0;
if ($emax > 0 && $total_requests > 0) {
    $numerator = pow(log(1 + $emax), 2);
    $denominator = ($emax + $total_requests) * log(1 + $total_requests);
    $k_dynamic = ($denominator != 0) ? ($numerator / $denominator) : 0;
}

// Simpan Dynamic K
$stmt = $conn->prepare("INSERT INTO dynamic_k (timestamp, emax, ptotal, k_dynamic) VALUES (?, ?, ?, ?)");
$stmt->bind_param("sddd", $current_timestamp, $emax, $total_requests, $k_dynamic);
$stmt->execute();

// 4. HITUNG THRESHOLD
$result_nev = $conn->query("SELECT normalized_entropy FROM entropy_log ORDER BY timestamp DESC LIMIT $N_window");
$nev = [];
while ($row = $result_nev->fetch_assoc()) { $nev[] = $row['normalized_entropy']; }

if (count($nev) >= 2) {
    $mean = array_sum($nev) / count($nev);
    $variance = 0;
    foreach ($nev as $val) { $variance += pow($val - $mean, 2); }
    $stddev = sqrt($variance / count($nev));
    
    $threshold = $mean - ($k_dynamic * $stddev);
} else {
    $threshold = 0; // Default jika data kurang
    $mean = 0; $stddev = 0;
}

// Simpan Threshold
$stmt = $conn->prepare("INSERT INTO threshold (timestamp, mean, stddev, k_dynamic, threshold) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sdddd", $current_timestamp, $mean, $stddev, $k_dynamic, $threshold);
$stmt->execute();

// 5. KLASIFIKASI
$result_status = ($normalized_entropy < $threshold) ? "ATTACK" : "NORMAL";

// Simpan Hasil Klasifikasi
$stmt = $conn->prepare("INSERT INTO classification (timestamp, normalized_entropy, threshold, result) VALUES (?, ?, ?, ?)");
$stmt->bind_param("sdds", $current_timestamp, $normalized_entropy, $threshold, $result_status);
$stmt->execute();

$conn->close();

echo "Siklus Selesai: $current_timestamp | Status: $result_status | NE: $normalized_entropy | Thres: $threshold\n";
?>