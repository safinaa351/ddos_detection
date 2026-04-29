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

$emax = ($n > 0) ? log($n, 2) : 0;
$k_dynamic = 0;
if ($emax > 0 && $total_requests > 0) {
    $numerator = pow(log(1 + $emax), 2); //log() = log natural (ln), memang php-nya begini.
    $denominator = ($emax + $total_requests) * log(1 + $total_requests);
    $k_dynamic = ($numerator / $denominator);
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
$result_status = ($normalized_entropy < $threshold) ? "SUS" : "NORMAL";
$delta = $normalized_entropy - $threshold;

// Simpan Hasil Klasifikasi tahap 1
$stmt = $conn->prepare("
INSERT INTO classification 
(timestamp, normalized_entropy, threshold, delta, result, final_result) 
VALUES (?, ?, ?, ?, ?, ?)
");

$stmt->bind_param("sddsss", 
    $current_timestamp, 
    $normalized_entropy, 
    $threshold, 
    $delta, 
    $result_status,
    $result_status // default dulu
);

$stmt->execute();
$inserted_id = $conn->insert_id;

echo "TAHAP 1: $current_timestamp | NE: $normalized_entropy | Thres: $threshold | Status: $result_status\n";

// ==============================
// 6. IDENTIFIKASI SUSIP & HITUNG ESIP
// ==============================
$normalized_esip = 0;
$filtered_susip = [];

if ($result_status == "SUS") {
    // 1. Hitung rata-rata request per IP dalam window ini
    $avg_req = $total_requests / $n;
    
    $filtered_susip = [];
    $total_req_filtered = 0;

    foreach ($data as $row) {
        // Hanya ambil IP yang requestnya di atas rata-rata (Potensi Attacker)
        if ($row['request_count'] > $avg_req) {
            $filtered_susip[] = $row;
            $total_req_filtered += $row['request_count'];
        }
    }
    $n_susip = count($filtered_susip);

    if ($n_susip > 0) {
        $temp_entropy = 0;
        foreach ($filtered_susip as $s) {
            $p_prime = $s['request_count'] / $total_req_filtered;
            if ($p_prime > 0) {
                $temp_entropy -= $p_prime * log($p_prime, 2);
            }
            
            // Simpan ke DB untuk audit (Opsional: hanya untuk tracing IP mana saja)
            $stmt = $conn->prepare("INSERT INTO suspicious_ip (timestamp, ip, request_count, probability) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssid", $current_timestamp, $s['ip_address'], $s['request_count'], $p_prime);
            $stmt->execute();
        }
        // Gunakan Normalized Entropy untuk Re-evaluasi (NESIP)
        $normalized_esip = ($n_susip > 1) ? ($temp_entropy / log($n_susip, 2)) : 0;
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
    // Bandingkan NE saat ini dengan Threshold Re-evaluasi
    if ($normalized_esip < $threshold_esip) {
        $final_result = "ATTACK";
    } else {
        // Jika NE tinggi (mendekati 1), berarti distribusi IP merata (Normal/Flash Crowd)
        $final_result = "NORMAL"; 
    }
}

// Tampilkan hasil tahap 2 jika masuk ke re-evaluasi
if ($result_status == "SUS") {
    echo "TAHAP 2: ESIP: $normalized_esip | ThresESIP: $threshold_esip | FINAL: $final_result\n";
}
echo "--------------------------------------------------------------------------\n";

// ==============================
// 9. SIMPAN REEVALUATION
// ==============================
$stmt = $conn->prepare("
    INSERT INTO reevaluation_log 
    (timestamp, normalized_esip, mean_esip, stddev_esip, threshold_esip, final_result) 
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("sdddds", $current_timestamp, $normalized_esip, $mean_esip, $stddev_esip, $threshold_esip, $final_result);
$stmt->execute();

// update hasil klasifikasi final
if ($result_status == "SUS") {
    $stmt = $conn->prepare("
        UPDATE classification 
        SET final_result = ? 
        WHERE id = ?
    ");
    $stmt->bind_param("si", $final_result, $inserted_id);
    $stmt->execute();
}

$conn->close();
?>