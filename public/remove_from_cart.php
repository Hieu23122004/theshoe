<?php
session_start();
include '../includes/database.php';
header('Content-Type: application/json');

$product_id = $_POST['product_id'] ?? null;
$color = $_POST['color'] ?? '';
$size = $_POST['size'] ?? '';

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['product_id'] == $product_id && $item['color'] == $color && $item['size'] == $size) {
            unset($_SESSION['cart'][$key]);
            break;
        }
    }
}

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ? AND color = ? AND size = ?");
    $stmt->bind_param('iiss', $user_id, $product_id, $color, $size);
    $stmt->execute();
    $stmt->close();
}

echo json_encode(['success' => true]);
