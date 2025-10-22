<?php
date_default_timezone_set('Asia/Jakarta');
// 1. Ganti dengan Token API bot Anda yang didapat dari @BotFather
define('BOT_TOKEN', '8421633102:AAGdz0N9MzMXSBUhP_8ugK6u-NTTh4wxj_g');
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');

// Fungsi untuk mengirim pesan balasan
function sendMessage($chat_id, $text) {
    $parameters = array(
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML' // Bisa menggunakan HTML/Markdown untuk format teks
    );

    // Mengirim permintaan ke API Telegram
    $ch = curl_init(API_URL . 'sendMessage');
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Opsional, tergantung hosting
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

// 2. Menerima data JSON dari Telegram
$update = file_get_contents('php://input');
$update = json_decode($update, true);

// Pastikan pesan diterima
if (isset($update['message'])) {
    $message = $update['message'];
    $chat_id = $message['chat']['id'];
    $text = $message['text'];

    // 3. Logika Bot
    $response_text = "Halo! Saya bot sederhana Anda. Anda mengirim pesan: " . $text;

    // Contoh penanganan perintah spesifik
    if (strpos($text, '/start') === 0) {
        $response_text = "Selamat datang di bot PHP Anda! Kirim pesan apa pun untuk saya balas.";
    } elseif (strpos($text, '/waktu') === 0) {
        $response_text = "Waktu saat ini: " . date("H:i:s d-m-Y");
    } elseif (strpos($text, '/botid') === 0) {
	    $response_text = "Bot ID: 8421633102:AAGdz0N9MzMXSBUhP_8ugK6u-NTTh4wxj_g";
    } elseif (strpos($text, '/asu') === 0) {
        $response_text = "kamu yang asu mas :)";
    } elseif (strpos($text, '/site') === 0) {
        $response_text = "<a href='mng.jualkode.com'>jualkode.com</a>";
    }

    // 4. Kirim Balasan
    sendMessage($chat_id, $response_text);
}

// Opsional: Untuk menghentikan proses eksekusi skrip agar tidak terus berjalan
http_response_code(200);
?>
