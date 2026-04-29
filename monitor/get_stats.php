<?php
require_once "/home/main/ddos_skripsi/ddos_detection/config.php";

// Ambil data klasifikasi terbaru join dengan reevaluation
$query = "SELECT c.*, r.normalized_esip, r.threshold_esip 
          FROM classification c 
          LEFT JOIN reevaluation_log r ON c.timestamp = r.timestamp 
          ORDER BY c.id DESC LIMIT 1";

$result = $conn->query($query);
$data = $result->fetch_assoc();

// Pembulatan angka agar tidak kepanjangan
if ($data) {
    $data['normalized_entropy'] = number_format($data['normalized_entropy'], 2);
    $data['threshold'] = number_format($data['threshold'], 2);
    $data['normalized_esip'] = number_format($data['normalized_esip'] ?? 0, 2);
    $data['threshold_esip'] = number_format($data['threshold_esip'] ?? 0, 2);
}

header('Content-Type: application/json');
echo json_encode($data);