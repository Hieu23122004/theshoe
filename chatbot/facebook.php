<?php
require 'config.php';

$input = json_decode(file_get_contents('php://input'), true);

// Log toàn bộ input từ Facebook để debug
logDebug('Facebook Input', $input);

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
    $attachments = $message['message']['attachments'] ?? [];

    // Log chi tiết message và attachments
    logDebug('Message Details', [
        'senderId' => $senderId,
        'userMessage' => $userMessage,
        'attachments' => $attachments,
        'hasAttachments' => !empty($attachments)
    ]);

    // Gửi lời chào nếu là người dùng mới
    if (isNewUser($senderId)) {
        sendMessage($senderId, "Chào bạn! Tôi là trợ lý ảo của Fanpage.");
        sendMessage($senderId, "Bạn cần hỗ trợ gì hôm nay? Gõ bất kỳ để bắt đầu nhé!");
        saveNewUser($senderId);
    }

    // ✅ Gửi message sang n8n webhook để xử lý AI, NLP, DB, v.v.
    forwardToN8N($senderId, $userMessage, $attachments);

    // ✅ Log file nếu có attachments
    if (!empty($attachments)) {
        logFileReceived($senderId, $attachments);
    }

    // ✅ Gửi phản hồi dựa trên loại message
    if (!empty($attachments)) {
        $botReply = "Cảm ơn bạn đã gửi file! Chúng tôi sẽ xem xét và phản hồi sớm.";
    } else {
        $botReply = "Cảm ơn bạn đã nhắn tin! Bạn vui lòng để lại câu hỏi, chúng tôi sẽ phản hồi sớm.";
    }
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
    $imageUrl = $input['imageUrl'] ?? null; // URL ảnh nếu có

    // Log dữ liệu từ N8N
    logDebug('N8N Response Data', $input);

    // Gửi text message
    if (!empty($userMessage)) {
        sendMessage($senderId, $userMessage);
    }

    // Gửi ảnh nếu có
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
    
    // Log response để debug
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
    
    // Log response để debug
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

    // Nếu có attachments, xử lý thông tin file
    if (!empty($attachments)) {
        $data['attachments'] = $attachments;
        $fileData = [];
        
        foreach ($attachments as $attachment) {
            if (isset($attachment['type']) && isset($attachment['payload']['url'])) {
                $fileInfo = [
                    'type' => $attachment['type'],
                    'url' => $attachment['payload']['url'],
                    'name' => 'data'  // Tên file mặc định là 'data'
                ];
                
                // Nếu là image, thêm thông tin chi tiết
                if ($attachment['type'] === 'image') {
                    $fileInfo['mime_type'] = 'image/jpeg';
                    $fileInfo['extension'] = 'jpg';
                }
                
                $fileData[] = $fileInfo;
            }
        }
        $data['data'] = $fileData; // Sử dụng key 'data' như trong ảnh
    }

    // Log data để debug
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
    
    // Log response để debug
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
