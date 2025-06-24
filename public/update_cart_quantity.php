<?php
session_start();
include '../includes/database.php';
header('Content-Type: application/json');
if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !isset($_POST['product_id']) ||
    !isset($_POST['quantity'])
) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}
$product_id = $_POST['product_id'];
$color = $_POST['color'] ?? '';
$size = $_POST['size'] ?? '';
$quantity = max(1, intval($_POST['quantity']));

// Kiểm tra tồn kho thực tế theo từng màu/size
$stmt = $conn->prepare("SELECT size_stock FROM products WHERE product_id = ?");
$stmt->bind_param('i', $product_id);
$stmt->execute();
$stmt->bind_result($size_stock_json);
$stmt->fetch();
$stmt->close();

$max_qty = 99;
if ($size_stock_json) {
    $size_stock = json_decode($size_stock_json, true);
    if (
        isset($size_stock[$color]) &&
        isset($size_stock[$color][$size])
    ) {
        $max_qty = (int)$size_stock[$color][$size];
    }
}
if ($quantity > $max_qty) {
    $quantity = $max_qty;
}

// Kiểm tra tồn kho tổng thể
include_once '../includes/database.php';
$stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE product_id = ?");
$stmt->bind_param('i', $product_id);
$stmt->execute();
$stmt->bind_result($stock_quantity);
$stmt->fetch();
$stmt->close();
if (isset($stock_quantity) && $quantity > $stock_quantity) {
    $quantity = $stock_quantity;
}

if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $key => &$item) {
        if ($item['product_id'] == $product_id && $item['color'] == $color && $item['size'] == $size) {
            $item['quantity'] = $quantity;
            break;
        }
    }
}

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ? AND color = ? AND size = ?");
    $stmt->bind_param('iiiss', $quantity, $user_id, $product_id, $color, $size);
    $stmt->execute();
    $stmt->close();
}
echo json_encode(['success' => true, 'quantity' => $quantity]);
