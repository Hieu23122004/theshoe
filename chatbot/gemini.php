<?php
require 'config.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['message'] ?? '';
$chatHistory = $input['history'] ?? []; // Mảng các lượt hội thoại cũ

function getGeminiReply($userMessage, $chatHistory = []) {
    $apiKey = GEMINI_API_KEY;
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=$apiKey";

    $trainingContext = <<<EOT
    Bạn là một Chatbot tư vấn bán hàng về giày Mulgati.
    - Luôn xưng là "tôi", gọi người dùng là "Bạn".
    - Khi người dùng hỏi một nhiệm vụ, hãy mô tả ngắn gọn.
    EOT;

    // Xây dựng mảng hội thoại theo định dạng Gemini API
    $messages = [
        ['role' => 'user', 'parts' => [['text' => $trainingContext]]]
    ];

    foreach ($chatHistory as $entry) {
        $messages[] = ['role' => 'user', 'parts' => [['text' => $entry['user']]]];
        $messages[] = ['role' => 'model', 'parts' => [['text' => $entry['assistant']]]];
    }

    // Thêm câu mới của người dùng
    $messages[] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];

    $data = ['contents' => $messages];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($data)
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    return $result['candidates'][0]['content']['parts'][0]['text'] ?? 'Xin lỗi, tôi không hiểu.';
}

echo json_encode([
    'reply' => getGeminiReply($userMessage, $chatHistory)
]);
?>
