<?php
session_start();
include '../includes/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You need to log in!']);
    exit;
}
$user_id = $_SESSION['user_id'];
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Missing order ID!']);
    exit;
}
// Kiểm tra trạng thái đơn hàng và lấy thông tin mã giảm giá trước khi hủy
$stmt = $conn->prepare("SELECT status, order_details FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->bind_param('ii', $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found!']);
    $stmt->close();
    exit;
}

$order = $result->fetch_assoc();
$status = $order['status'];
$order_details = $order['order_details'];
$stmt->close();

// Chỉ cho phép hủy đơn hàng có status là 'Pending'
if ($status !== 'Pending') {
    echo json_encode(['success' => false, 'message' => 'Order is being processed, cannot be canceled!']);
    exit;
}

// Bắt đầu transaction để đảm bảo tính nhất quán
$conn->begin_transaction();

try {
    // Kiểm tra xem có mã giảm giá trong đơn hàng không
    $discount_code = null;
    if ($order_details) {
        $order_data = json_decode($order_details, true);
        if (isset($order_data['discount_code']) && !empty($order_data['discount_code'])) {
            $discount_code = $order_data['discount_code'];
        }
    }
    
    // Thực hiện hủy đơn hàng
    $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ? AND user_id = ? AND status = 'Pending'");
    $stmt->bind_param('ii', $order_id, $user_id);
    $stmt->execute();
    
    if ($stmt->affected_rows == 0) {
        $stmt->close();
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Cannot cancel this order!']);
        exit;
    }
    $stmt->close();
    
    // Nếu có mã giảm giá, hoàn trả lại số lần sử dụng
    if ($discount_code) {
        $stmt = $conn->prepare("UPDATE discount_codes SET used_count = GREATEST(used_count - 1, 0) WHERE code = ?");
        $stmt->bind_param('s', $discount_code);
        $stmt->execute();
        $stmt->close();
    }
    
    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Order canceled successfully!']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Cannot cancel order!']);
}
