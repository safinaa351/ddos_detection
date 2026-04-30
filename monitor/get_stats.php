<?php
require_once "/home/main/ddos_skripsi/ddos_detection/config.php";

// Ambil data klasifikasi terbaru join dengan reevaluation
$query = "SELECT c.*, r.normalized_esip, r.threshold_esip 
          FROM classification c 
          LEFT JOIN reevaluation_log r ON c.timestamp = r.timestamp 
          ORDER BY c.id DESC LIMIT 1";

$result = $conn->query($query);
$data = $result->fetch_assoc();

if ($data) {
    // Kirim raw number (penting untuk chart)
    $data['normalized_entropy'] = (float)$data['normalized_entropy'];
    $data['threshold'] = (float)$data['threshold'];
    $data['normalized_esip'] = isset($data['normalized_esip']) ? (float)$data['normalized_esip'] : 0;
    $data['threshold_esip'] = isset($data['threshold_esip']) ? (float)$data['threshold_esip'] : 0;

    // Tambahkan versi formatted untuk UI (opsional)
    $data['normalized_entropy_fmt'] = number_format($data['normalized_entropy'], 3);
    $data['threshold_fmt'] = number_format($data['threshold'], 3);
    $data['normalized_esip_fmt'] = number_format($data['normalized_esip'], 3);
    $data['threshold_esip_fmt'] = number_format($data['threshold_esip'], 3);
}

header('Content-Type: application/json');
echo json_encode($data);