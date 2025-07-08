<?php
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['hub_mode']) && $_GET['hub_mode'] === 'subscribe') {
    $verify_token = $_GET['hub_verify_token'];
    $challenge = $_GET['hub_challenge'];

    if ($verify_token === 'theshoe') {
        echo $challenge;
        exit;
    } else {
        echo 'Token không hợp lệ';
        http_response_code(403);
        exit;
    }
}

require 'config.php';

$input = json_decode(file_get_contents('php://input'), true);

// === 1. Facebook gửi tin nhắn đến === //
if (isset($input['entry'][0]['messaging'][0])) {
    $message = $input['entry'][0]['messaging'][0];
    $senderId = $message['sender']['id'];
    $pageId = $message['recipient']['id'];

    if (isset($message['message']['is_echo']) && $message['message']['is_echo'] === true) {
        return;
    }

    if ($senderId === $pageId) {
        return;
    }

    $userMessage = $message['message']['text'] ?? '';

    // Gửi lời chào nếu là người dùng mới
    if (isNewUser($senderId)) {
        sendMessage($senderId, "Chào bạn! Tôi là trợ lý ảo của Fanpage.");
        sendMessage($senderId, "Bạn cần hỗ trợ gì hôm nay? Gõ bất kỳ để bắt đầu nhé!");
        saveNewUser($senderId);
    }

    // ✅ Gửi message sang n8n webhook để xử lý AI, NLP, DB, v.v.
    forwardToN8N($senderId, $userMessage);

    // ✅ Gửi phản hồi mặc định (nếu muốn)
    $botReply = "Cảm ơn bạn đã nhắn tin! Bạn vui lòng để lại câu hỏi, chúng tôi sẽ phản hồi sớm.";
    sendMessage($senderId, $botReply);

    http_response_code(200);
    exit;
}

// === 2. Dữ liệu gửi từ N8N trả về để gửi lại Facebook === //
elseif (
    isset($input['message']) &&
    isset($input['senderId']) &&
    is_string($input['senderId']) &&
    !empty($input['senderId'])
) {
    $userMessage = $input['message'];
    $senderId = $input['senderId'];

    $botReply = $userMessage;
    sendMessage($senderId, $botReply);

    header('Content-Type: application/json');
    echo json_encode(['reply' => $botReply]);
    exit;
}

// === Mặc định === //
http_response_code(200);


// ====== Các hàm ====== //

function sendMessage($recipientId, $messageText) {
    $url = "https://graph.facebook.com/v23.0/me/messages?access_token=" . PAGE_ACCESS_TOKEN;

    $data = [
        'recipient' => ['id' => $recipientId],
        'message' => ['text' => $messageText]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($data)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    file_put_contents('fb_send_log.txt', "Send to $recipientId: $messageText\nHTTP Code: $httpCode\nResponse: $response\n\n", FILE_APPEND);
}

function forwardToN8N($senderId, $messageText) {
    $n8nWebhookUrl = N8N_WEBHOOK_URL;

    $data = [
        'senderId' => $senderId,
        'message' => $messageText
    ];

    $ch = curl_init($n8nWebhookUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($data)
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    file_put_contents('n8n_log.txt', "Forward to N8N: $senderId: $messageText\nResponse: $response\n\n", FILE_APPEND);
}

function isNewUser($senderId) {
    $file = 'users.json';
    if (!file_exists($file)) {
        file_put_contents($file, json_encode([]));
    }

    $data = json_decode(file_get_contents($file), true);
    return !in_array($senderId, $data);
}

function saveNewUser($senderId) {
    $file = 'users.json';
    $data = json_decode(file_get_contents($file), true);
    $data[] = $senderId;
    file_put_contents($file, json_encode($data));
}
