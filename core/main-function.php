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

echo "Siklus Selesai: $current_timestamp | Status: $result_status | NE: $normalized_entropy | Thres: $threshold\n";

// ==============================
// 6. IDENTIFIKASI SUSIP (HANYA JIKA ATTACK)
// ==============================
$susip = [];

if ($result_status == "ATTACK") {

    // Urutkan IP berdasarkan request_count (desc)
    usort($data, function($a, $b) {
        return $b['request_count'] - $a['request_count'];
    });

    // Ambil semua IP dalam window
    foreach ($data as $row) {
        $p = $row['request_count'] / $total_requests;

        $susip[] = [
            'ip' => $row['ip_address'], // Pastikan key sesuai dengan output sliding-window.php (ip atau ip_address)
            'request_count' => $row['request_count'],
            'p' => $p
        ];

        // Simpan ke DB
        $stmt = $conn->prepare("INSERT INTO suspicious_ip (timestamp, ip, request_count, probability) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssid", $current_timestamp, $row['ip_address'], $row['request_count'], $p);
        $stmt->execute();
    }
}

// ==============================
// 7. HITUNG ENTROPY ESIP
// ==============================
$entropy_esip = 0;
$total_susip_requests = 0;

foreach ($susip as $s) {
    $total_susip_requests += $s['request_count'];
}

if ($total_susip_requests > 0) {
    foreach ($susip as $s) {
        $p = $s['request_count'] / $total_susip_requests;
        if ($p > 0) {
            $entropy_esip -= $p * log($p, 2);
        }
    }
}

// ==============================
// 8. HITUNG THRESHOLD ESIP (DINAMIS)
// ==============================
$esip_values = [];

$result_esip = $conn->query("SELECT entropy_esip FROM reevaluation_log ORDER BY timestamp DESC LIMIT $N_window");

while ($row = $result_esip->fetch_assoc()) {
    $esip_values[] = $row['entropy_esip'];
}

$mean_esip = 0;
$stddev_esip = 0;
$threshold_esip = 0;

if (count($esip_values) >= 2) {
    $mean_esip = array_sum($esip_values) / count($esip_values);

    $variance = 0;
    foreach ($esip_values as $val) {
        $variance += pow($val - $mean_esip, 2);
    }

    $stddev_esip = sqrt($variance / count($esip_values));

    // pakai k_dynamic yang sama dari tahap awal
    $threshold_esip = $mean_esip - ($k_dynamic * $stddev_esip);
} else {
    $threshold_esip = -1; //jika data kurang, set threshold negatif agar tidak mempengaruhi klasifikasi
}

// ==============================
// 9. FINAL DECISION
// ==============================
$final_result = $result_status;

// hanya reevaluate jika ada SUSIP
if (!empty($susip) && $entropy_esip > 0) {

    if ($entropy_esip < $threshold_esip) {
        $final_result = "ATTACK";
    } else {
        $final_result = "NORMAL";
    }
}

// ==============================
// 10. SIMPAN REEVALUATION
// ==============================
$stmt = $conn->prepare("
    INSERT INTO reevaluation_log 
    (timestamp, entropy_esip, mean_esip, stddev_esip, threshold_esip, final_result) 
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("sdddds", $current_timestamp, $entropy_esip, $mean_esip, $stddev_esip, $threshold_esip, $final_result);
$stmt->execute();

// update hasil klasifikasi final
$stmt = $conn->prepare("UPDATE classification SET result = ? WHERE timestamp = ?");
$stmt->bind_param("ss", $final_result, $current_timestamp);
$stmt->execute();

$conn->close();
?>