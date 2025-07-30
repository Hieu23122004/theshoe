<?php
header('Content-Type: application/json; charset=utf-8');
// Kết nối database
include_once '../includes/database.php'; 

// Nhận đầu vào JSON
$input = json_decode(file_get_contents('php://input'), true);
$senderId = isset($input['senderId']) ? $input['senderId'] : '';

if (!$senderId) {
    echo json_encode(['error' => 'Missing senderId']);
    exit;
}

// Truy vấn lấy message mới nhất theo sender_id
if (!isset($conn) || !($conn instanceof mysqli)) {
    echo json_encode([['data' => 'Database connection failed']]);
    exit;
}



$stmt = $conn->prepare("SELECT sender_id, message, created_at FROM messages WHERE sender_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param('s', $senderId);
$stmt->execute();
$result = $stmt->get_result();
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
if (count($rows) > 0) {
    echo json_encode($rows);
} else {
    echo json_encode([['data' => null]]);
}
$stmt->close();
?>
