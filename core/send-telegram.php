<?php

$botToken = "8669689676:AAGiLIK7fbVEyZzU3smeXZpoLaGZDh8XlOw";
$chatId = "1946296399";

// ambil daftar IP mencurigakan
$susip_text = "";

foreach ($filtered_susip as $s) {
    $susip_text .= "- {$s['ip_address']} ({$s['request_count']} req)\n";
}

$message = "
🚨 *DDoS ATTACK DETECTED* 🚨

*Time:* $current_timestamp
*Total Request:* $total_requests

*NE:* $normalized_entropy
*Threshold:* $threshold

*NESIP:* $normalized_esip
*Threshold NESIP:* $threshold_nesip

*Suspicious IPs:*
$susip_text
";

$url = "https://api.telegram.org/bot$botToken/sendMessage";

$dataTelegram = [
    'chat_id' => $chatId,
    'text' => $message,
    'parse_mode' => 'Markdown'
];

$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($dataTelegram),
    ],
];

$context = stream_context_create($options);

$response = file_get_contents($url, false, $context);

if ($response === FALSE) {
    echo "Failed to send Telegram alert!\n";
} else {
    echo "Telegram alert sent!\n";
}

?>