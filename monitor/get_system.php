<?php
header('Content-Type: application/json');

// ===== CPU USAGE =====
// ambil dari top
$cpu = shell_exec("top -bn1 | grep 'Cpu(s)'");

// parsing CPU idle → lalu convert ke usage
preg_match('/(\d+\.\d+)\s*id/', $cpu, $matches);
$idle = isset($matches[1]) ? (float)$matches[1] : 0;
$cpu_usage = 100 - $idle;

// ===== RAM USAGE =====
$ram = shell_exec("free -m");

// parsing RAM
$lines = explode("\n", $ram);
$mem = preg_split('/\s+/', trim($lines[1]));

$total = (int)$mem[1];
$used  = (int)$mem[2];

$ram_usage = ($total > 0) ? ($used / $total) * 100 : 0;

// ===== OUTPUT =====
echo json_encode([
    "cpu" => round($cpu_usage, 2),
    "ram" => round($ram_usage, 2),
    "total_ram" => $total,
    "used_ram" => $used
]);