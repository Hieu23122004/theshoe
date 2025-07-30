<?php
require 'config.php';

$input = json_decode(file_get_contents('php://input'), true);

// =============================
// Log toàn bộ input từ Facebook để debug
// =============================
logDebug('Facebook Input', $input);

// =============================
// 1. Xử lý tin nhắn gửi đến từ Facebook
// =============================

if (
    isset($input['entry']) &&
    is_array($input['entry']) &&
    isset($input['entry'][0]) &&
    isset($input['entry'][0]['messaging']) &&
    is_array($input['entry'][0]['messaging']) &&
    isset($input['entry'][0]['messaging'][0])
) {
    $message = $input['entry'][0]['messaging'][0];
    $senderId = $message['sender']['id'] ?? null;
    $pageId = $message['recipient']['id'] ?? null;

    logDebug('Message Init', [
        'senderId' => $senderId,
        'pageId' => $pageId,
        'rawMessage' => $message
    ]);

    // Nếu thiếu ID thì bỏ qua
    if (!$senderId || !$pageId) {
        logDebug('Thiếu senderId hoặc pageId. Bỏ qua.', []);
        http_response_code(200);
        exit;
    }

    // Bỏ qua tin nhắn echo hoặc gửi từ chính page
    if (isset($message['message']['is_echo']) && $message['message']['is_echo'] === true) {
        logDebug('Bỏ qua is_echo message', []);
        http_response_code(200);
        return;
    }
    if ($senderId === $pageId) {
        logDebug('Bỏ qua tin nhắn từ chính page gửi', []);
        http_response_code(200);
        return;
    }

    $userMessage = $message['message']['text'] ?? '';
    $attachments = $message['message']['attachments'] ?? [];

    logDebug('Message Details', [
        'senderId' => $senderId,
        'userMessage' => $userMessage,
        'attachments' => $attachments,
        'hasAttachments' => !empty($attachments)
    ]);

    if (isNewUser($senderId)) {
        sendMessage($senderId, "Chào bạn! Tôi là trợ lý ảo của Fanpage.");
        sendMessage($senderId, "Bạn cần hỗ trợ gì hôm nay? Gõ bất kỳ để bắt đầu nhé!");
        saveNewUser($senderId);
    }

    forwardToN8N($senderId, $userMessage, $attachments);

    if (!empty($attachments)) {
        logFileReceived($senderId, $attachments);
    }

    $botReply = !empty($attachments)
        ? "Cảm ơn bạn đã gửi file! Chúng tôi sẽ xem xét và phản hồi sớm."
        : "Cảm ơn bạn đã nhắn tin! Bạn vui lòng để lại câu hỏi, chúng tôi sẽ phản hồi sớm.";

    sendMessage($senderId, $botReply);

    http_response_code(200);
    exit;
}

// =============================
// 2. Xử lý dữ liệu trả về từ N8N để gửi lại Facebook
// =============================
elseif (
    isset($input['message']) &&
    isset($input['senderId']) &&
    is_string($input['senderId']) &&
    !empty($input['senderId'])
) {
    $userMessage = $input['message'];
    $senderId = $input['senderId'];
    $imageUrl = $input['imageUrl'] ?? null;

    logDebug('N8N Response Data', $input);

    if (!empty($userMessage)) {
        sendMessage($senderId, $userMessage);
    }

    if (!empty($imageUrl)) {
        sendImageMessage($senderId, $imageUrl);
    }

    header('Content-Type: application/json');
    echo json_encode([
        'reply' => $userMessage,
        'imageUrl' => $imageUrl,
        'status' => 'sent'
    ]);
    exit;
}

// =============================
// 3. Mặc định: trả về 200 OK nếu không khớp trường hợp nào
// =============================
logDebug('Không xử lý được payload này', $input);
http_response_code(200);

// =============================
// Các hàm chức năng
// =============================

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
    
    logDebug('Send Message Response', [
        'recipientId' => $recipientId,
        'messageText' => $messageText,
        'httpCode' => $httpCode,
        'response' => $response
    ]);
}

function sendImageMessage($recipientId, $imageUrl) {
    $url = "https://graph.facebook.com/v23.0/me/messages?access_token=" . PAGE_ACCESS_TOKEN;

    $data = [
        'recipient' => ['id' => $recipientId],
        'message' => [
            'attachment' => [
                'type' => 'image',
                'payload' => [
                    'url' => $imageUrl,
                    'is_reusable' => true
                ]
            ]
        ]
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
    
    logDebug('Send Image Response', [
        'recipientId' => $recipientId,
        'imageUrl' => $imageUrl,
        'httpCode' => $httpCode,
        'response' => $response
    ]);
}

function forwardToN8N($senderId, $messageText, $attachments = []) {
    $n8nWebhookUrl = N8N_WEBHOOK_URL;

    $data = [
        'senderId' => $senderId,
        'message' => $messageText,
        'hasAttachments' => !empty($attachments)
    ];

    if (!empty($attachments)) {
        $data['attachments'] = $attachments;
        $fileData = [];

        foreach ($attachments as $attachment) {
            if (isset($attachment['type']) && isset($attachment['payload']['url'])) {
                $fileInfo = [
                    'type' => $attachment['type'],
                    'url' => $attachment['payload']['url'],
                    'name' => 'data'
                ];

                if ($attachment['type'] === 'image') {
                    $fileInfo['mime_type'] = 'image/jpeg';
                    $fileInfo['extension'] = 'jpg';
                }

                $fileData[] = $fileInfo;
            }
        }

        $data['data'] = $fileData;
    }

    logDebug('N8N Request Data', $data);

    $ch = curl_init($n8nWebhookUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    logDebug('N8N Response', [
        'httpCode' => $httpCode,
        'response' => $response,
        'error' => curl_error($ch)
    ]);

    curl_close($ch);
    return $response;
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

function logFileReceived($senderId, $attachments) {
    $logFile = 'file_log.json';
    if (!file_exists($logFile)) {
        file_put_contents($logFile, json_encode([]));
    }

    $logData = json_decode(file_get_contents($logFile), true);

    foreach ($attachments as $attachment) {
        $logEntry = [
            'senderId' => $senderId,
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => $attachment['type'] ?? 'unknown',
            'url' => $attachment['payload']['url'] ?? '',
            'filename' => 'data'
        ];
        $logData[] = $logEntry;
    }

    file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT));
}

function logDebug($title, $data) {
    $debugFile = 'debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] {$title}: " . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
    file_put_contents($debugFile, $logEntry, FILE_APPEND | LOCK_EX);
}
