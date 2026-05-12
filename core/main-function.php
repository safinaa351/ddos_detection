<?php
require_once "../config.php";
date_default_timezone_set('Asia/Jakarta');
$current_timestamp = date("Y-m-d H:i:s");

// 0. AMBIL DATA SLIDING WINDOW
$data = include "sliding-window.php";
$total_requests = 0;
foreach ($data as $row) {
    $total_requests += $row['request_count'];
}

if ($total_requests == 0) {
    die("No data in this window\n");
}

$n = count($data);

// 1. BUAT WINDOW RECORD (anchor untuk semua tabel di siklus ini)
$stmt = $conn->prepare("INSERT INTO window_log (timestamp, window_size) VALUES (?, ?)");
$window_size = 30;
$stmt->bind_param("si", $current_timestamp, $window_size);
$stmt->execute();
$window_id = $conn->insert_id;

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
$stmt = $conn->prepare("INSERT INTO entropy_log (window_id, unique_ip, entropy, normalized_entropy, total_request, timestamp) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iiddis", $window_id, $n, $entropy, $normalized_entropy, $total_requests, $current_timestamp);
$stmt->execute() or die($stmt->error);

// 3. HITUNG DYNAMIC K
$N_window = 20;

$emax = ($n > 0) ? log($n, 2) : 0;
$k_dynamic = 0;
if ($emax > 0 && $total_requests > 0) {
    $numerator = pow(log(1 + $emax), 2); //log() = log natural (ln), memang php-nya begini.
    $denominator = ($emax + $total_requests) * log(1 + $total_requests);
    $k_dynamic = ($numerator / $denominator);
}

// Simpan Dynamic K
$stmt = $conn->prepare("INSERT INTO dynamic_k (window_id, timestamp, emax, ptotal, k_dynamic) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("isddd", $window_id, $current_timestamp, $emax, $total_requests, $k_dynamic);
$stmt->execute() or die($stmt->error);

// 4. HITUNG THRESHOLD
$result_nev = $conn->query("SELECT normalized_entropy FROM entropy_log ORDER BY timestamp DESC LIMIT $N_window");
$nev = [];
while ($row = $result_nev->fetch_assoc()) { $nev[] = $row['normalized_entropy']; }

if (count($nev) >= $N_window) {
    $mean = array_sum($nev) / count($nev);
    $variance = 0;
    foreach ($nev as $val) { $variance += pow($val - $mean, 2); }
    $stddev = sqrt($variance / count($nev));
    
    $threshold = $mean - ($k_dynamic * $stddev);
} else {
    echo "Collecting baseline... (" . count($nev) . "/$N_window)\n";
    exit;
}

// Simpan Threshold
$stmt = $conn->prepare("INSERT INTO threshold (window_id, timestamp, mean, stddev, k_dynamic, threshold) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isdddd", $window_id, $current_timestamp, $mean, $stddev, $k_dynamic, $threshold);
$stmt->execute() or die($stmt->error);

// 5. KLASIFIKASI
$result_status = ($normalized_entropy < $threshold) ? "SUS" : "NORMAL";
$delta = $normalized_entropy - $threshold;

// Simpan Hasil Klasifikasi tahap 1
$stmt = $conn->prepare("
INSERT INTO classification 
(window_id, timestamp, normalized_entropy, threshold, delta, result, final_result) 
VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param("isddsss", 
    $window_id,
    $current_timestamp, 
    $normalized_entropy, 
    $threshold, 
    $delta, 
    $result_status,
    $result_status // default dulu
);

$stmt->execute() or die($stmt->error);
$inserted_id = $conn->insert_id;

echo "WINDOW: $current_timestamp | Total Request per Window: $total_requests\n";
echo "TAHAP 1: $current_timestamp | NE: $normalized_entropy | Thres: $threshold | Status: $result_status\n";

// ==============================
// 6. IDENTIFIKASI SUSIP & HITUNG ESIP
// ==============================
$normalized_esip = 0;
$entropy_esip = 0;
$filtered_susip = [];

if ($result_status == "SUS") {
    // 1. Hitung rata-rata request per IP dalam window ini
    $avg_req = $total_requests / $n;
    
    $filtered_susip = [];
    $total_req_filtered = 0;

    foreach ($data as $row) {
        // Hanya ambil IP yang requestnya sama atau di atas rata-rata
        if ($row['request_count'] >= $avg_req) {
            $filtered_susip[] = $row;
            $total_req_filtered += $row['request_count'];
        }
    }
    $n_susip = count($filtered_susip);

    if ($n_susip > 0) {
        foreach ($filtered_susip as $s) {
            $p_prime = $s['request_count'] / $total_req_filtered;
            if ($p_prime > 0) {
                $entropy_esip -= $p_prime * log($p_prime, 2);
            }
            
            // Simpan ke DB untuk audit (Opsional: hanya untuk tracing IP mana saja)
            $stmt = $conn->prepare("INSERT INTO suspicious_ip (window_id, timestamp, ip, request_count, probability) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isssd", $window_id, $current_timestamp, $s['ip_address'], $s['request_count'], $p_prime);
            $stmt->execute() or die($stmt->error);
        }
        // Gunakan Normalized Entropy untuk Re-evaluasi (NESIP)
        $normalized_esip = ($n_susip > 1) ? ($entropy_esip / log($n_susip, 2)) : 0;
    }
}

// ==============================
// 7. HITUNG THRESHOLD ESIP (dari histori NESIP sebelumnya)
// ==============================
$esip_values = [];
// Ambil nilai normalized_esip (yang berisi nilai ternormalisasi dari siklus lalu)
$result_esip = $conn->query("SELECT normalized_esip FROM reevaluation_log ORDER BY timestamp DESC LIMIT $N_window");

while ($row = $result_esip->fetch_assoc()) {
    $esip_values[] = $row['normalized_esip']; // Pastikan key sesuai hasil query
}

$mean_esip = 0;
$stddev_esip = 0;
$threshold_esip = 0;

if (count($esip_values) >= 2) {
    $mean_esip = array_sum($esip_values) / count($esip_values);
    $sum_sq = 0;
    foreach ($esip_values as $val) { $sum_sq += pow($val - $mean_esip, 2); }
    $stddev_esip = sqrt($sum_sq / count($esip_values));
    
    $threshold_esip = $mean_esip - ($k_dynamic * $stddev_esip);
} else {
    $threshold_esip = 0.5; //default threshold jika history belum cukup (how do i decide this)
}

// ==============================
// 8. FINAL DECISION
// ==============================
$final_result = $result_status;

if ($result_status == "SUS") {
    if ($normalized_esip < $threshold_esip) {
        $final_result = "ATTACK";
    } else {
        $final_result = "NORMAL"; 
    }
}

// Tampilkan hasil tahap 2 jika masuk ke re-evaluasi
if ($result_status == "SUS") {
    echo "TAHAP 2: NESIP: $normalized_esip | ThresESIP: $threshold_esip | FINAL: $final_result\n";
}
echo "--------------------------------------------------------------------------\n";

// ==============================
// 9. SIMPAN REEVALUATION
// ==============================
$stmt = $conn->prepare("
    INSERT INTO reevaluation_log 
    (classification_id, entropy_esip, normalized_esip, mean_esip, stddev_esip, threshold_esip, final_result) 
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("iddddds", $inserted_id, $entropy_esip, $normalized_esip, $mean_esip, $stddev_esip, $threshold_esip, $final_result);
$stmt->execute() or die($stmt->error);

// update hasil klasifikasi final
if ($result_status == "SUS") {
    $stmt = $conn->prepare("
        UPDATE classification 
        SET final_result = ? 
        WHERE id = ?
    ");
    $stmt->bind_param("si", $final_result, $inserted_id);
    $stmt->execute() or die($stmt->error);
}

$conn->close();
?>