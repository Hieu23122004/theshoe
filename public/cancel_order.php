<?php
session_start();
include '../includes/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập!']);
    exit;
}
$user_id = $_SESSION['user_id'];
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Thiếu mã đơn hàng!']);
    exit;
}
// Chỉ cho phép hủy đơn nếu đúng user và trạng thái chưa giao
$stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ? AND user_id = ? AND status = 'Processing'");
$stmt->bind_param('ii', $order_id, $user_id);
$stmt->execute();
if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Không thể hủy đơn hàng này!']);
}
$stmt->close();
