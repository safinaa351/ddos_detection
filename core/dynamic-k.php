<?php
require_once "../config.php";

// =======================
// AMBIL DATA DARI WINDOW
// =======================

$data = include "sliding-window.php";

// hitung total request
$ptotal = 0;
foreach ($data as $row) {
    $ptotal += $row['request_count'];
}

// validasi
if ($ptotal == 0) {
    echo "No data in window\n";
    exit;
}

// =======================
// AMBIL ENTROPY VECTOR (EV)
// =======================

$N = 20;

$result = $conn->query("
    SELECT entropy 
    FROM entropy_log 
    ORDER BY timestamp DESC 
    LIMIT $N
");

$ev = [];

while ($row = $result->fetch_assoc()) {
    $ev[] = $row['entropy'];
}

// validasi
if (count($ev) == 0) {
    echo "EV empty\n";
    exit;
}

// =======================
// EMAx = entropy maksimum
// =======================

$emax = max($ev);

// =======================
// HITUNG k_dynamic 
// =======================

if ($emax > 0 && $ptotal > 0) {

    $numerator = pow(log(1 + $emax), 2);
    $denominator = ($emax + $ptotal) * log(1 + $ptotal);

    if ($denominator != 0) {
        $k_dynamic = $numerator / $denominator;
    } else {
        $k_dynamic = 0;
    }

} else {
    $k_dynamic = 0;
}

// =======================
// SIMPAN KE DATABASE
// =======================

$timestamp = date("Y-m-d H:i:s");

$stmt = $conn->prepare("
    INSERT INTO dynamic_k
    (timestamp, emax, ptotal, k_dynamic)
    VALUES (?, ?, ?, ?)
");

$stmt->bind_param("sddd",
    $timestamp,
    $emax,
    $ptotal,
    $k_dynamic
);

$stmt->execute();

$conn->close();

echo "Emax: $emax | Ptotal: $ptotal | k_dynamic: $k_dynamic\n";
?>