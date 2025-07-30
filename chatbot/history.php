
<?php
// Kết nối database
include_once '../includes/database.php'; 

// Nhận dữ liệu JSON từ request (ví dụ: POST body)
$input = file_get_contents('php://input');
$json = json_decode($input, true);

// Lấy senderId và message
$senderId = isset($json['senderId']) ? $json['senderId'] : null;
$message = isset($json['message']) ? $json['message'] : "null";

if ($senderId !== null) {
    $sql = "INSERT INTO messages (sender_id, message) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $senderId, $message);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "Thêm tin nhắn thành công!";
    } else {
        echo "Lỗi khi thêm tin nhắn!";
    }
    $stmt->close();
} else {
    echo "Dữ liệu không hợp lệ!";
}
$conn->close();
?>
