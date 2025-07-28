<?php
// Lấy biến môi trường từ file .env
function env($key, $default = null) {
    static $env = null;
    if ($env === null) {
        if (file_exists(__DIR__ . '/../.env')) {
            $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                [$k, $v] = array_pad(explode('=', $line, 2), 2, null);
                if ($k !== null) $env[trim($k)] = trim($v, " \t\n\r\0\x0B\"");
            }
        } else {
            $env = [];
        }
    }
    return $env[$key] ?? $default;
}

// Token lấy từ .env
$verify_token = env('VERIFY_TOKEN');
$n8n_webhook = env('N8N_WEBHOOK_URL');

// 1. Xác minh token khi Facebook gửi GET yêu cầu webhook
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['hub_mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';

    if ($mode === 'subscribe' && $token === $verify_token) {
        echo $challenge;
    } else {
        http_response_code(403);
        echo "Forbidden";
    }
    exit;
}

// 2. Xử lý POST khi có tin nhắn hoặc sự kiện gửi về từ Facebook
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    // Gửi payload đến webhook n8n
    $ch = curl_init($n8n_webhook);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    echo "EVENT_RECEIVED";
    exit;
}

// Nếu không phải GET/POST thì trả lỗi
http_response_code(404);
echo "Not Found";
?>
