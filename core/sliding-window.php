<?php
// sliding-window.php dummy
if (php_sapi_name() == "cli") {
    $mode = $argv[1] ?? 'normal'; // Ambil argumen pertama setelah nama file
} else {
    $mode = $_GET['mode'] ?? 'normal';
}

$data = [];

if ($mode == 'normal') {
    // Skenario Normal: Banyak IP unik, jumlah request merata (Entropy Tinggi)
    for ($i = 1; $i <= 20; $i++) {
        $data[] = ['ip_address' => "192.168.1.$i", 'request_count' => rand(5, 10)];
    }
} elseif ($mode == 'ddos') {
    // Skenario DDoS: Satu/sedikit IP mendominasi trafik (Entropy Rendah)
    $data[] = ['ip_address' => "10.0.0.1", 'request_count' => 1000]; // Penyerang
    for ($i = 1; $i <= 10; $i++) {
        $data[] = ['ip_address' => "192.168.1.$i", 'request_count' => rand(1, 2)];
    }
} elseif ($mode == 'flash-crowd') {
    // Skenario Flash Crowd: Banyak IP unik, tapi ada lonjakan volume (Tengah-tengah)
    for ($i = 1; $i <= 50; $i++) {
        $data[] = ['ip_address' => "172.16.1.$i", 'request_count' => rand(20, 30)];
    }
}

return $data;