<?php
session_start();
include '../includes/database.php';

function jsonResponse($data) {
    echo json_encode($data);
    exit;
}

if (!isset($_POST['add_to_cart'], $_POST['product_id'], $_POST['color'], $_POST['size'], $_POST['quantity'])) {
    jsonResponse(['success' => false, 'message' => 'Invalid request']);
}

$pid   = intval($_POST['product_id']);
$color = trim($_POST['color']);
$size  = trim($_POST['size']);
$qty   = max(1, intval($_POST['quantity']));

// --- Lấy tồn kho từ DB theo màu + size ---
$stmt = $conn->prepare("SELECT size_stock FROM products WHERE product_id = ?");
$stmt->bind_param('i', $pid);
$stmt->execute();
$stmt->bind_result($size_stock_json);
$stmt->fetch();
$stmt->close();

$max_qty = 99; // Mặc định
if ($size_stock_json) {
    $size_stock = json_decode($size_stock_json, true);
    if (isset($size_stock[$color][$size])) {
        $max_qty = (int)$size_stock[$color][$size];
    }
}

// --- Kiểm tra giỏ hàng hiện tại của user ---
$key = "{$pid}_{$color}_{$size}";
$current_qty = isset($_SESSION['cart'][$key]) ? (int)$_SESSION['cart'][$key]['quantity'] : 0;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ? AND color = ? AND size = ?");
    $stmt->bind_param('iiss', $user_id, $pid, $color, $size);
    $stmt->execute();
    $stmt->bind_result($db_qty);
    if ($stmt->fetch()) {
        $current_qty = (int)$db_qty;
    }
    $stmt->close();
}

// --- Nếu đã vượt tồn kho ---
if ($current_qty >= $max_qty) {
    jsonResponse([
        'success' => false,
        'message' => 'This is the maximum quantity available in stock.',
        'maxed'   => true
    ]);
}

// --- Tính số lượng được phép thêm ---
$add_qty = min($qty, $max_qty - $current_qty);
$msg = ($add_qty < $qty)
    ? 'You can only add up to ' . $add_qty . ' items to the cart (limited stock).'
    : '';

// --- Cập nhật giỏ hàng trong session ---
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

if (isset($_SESSION['cart'][$key])) {
    $_SESSION['cart'][$key]['quantity'] += $add_qty;
} else {
    $_SESSION['cart'][$key] = [
        'product_id' => $pid,
        'color'      => $color,
        'size'       => $size,
        'quantity'   => $add_qty
    ];
}

// --- Cập nhật vào database nếu đăng nhập ---
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT cart_item_id, quantity FROM cart_items WHERE user_id = ? AND product_id = ? AND color = ? AND size = ?");
    $stmt->bind_param('iiss', $user_id, $pid, $color, $size);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($cart_item_id, $db_qty);
        $stmt->fetch();
        $new_qty = $db_qty + $add_qty;
        $update = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
        $update->bind_param('ii', $new_qty, $cart_item_id);
        $update->execute();
        $update->close();
    } else {
        $insert = $conn->prepare("INSERT INTO cart_items (user_id, product_id, color, size, quantity) VALUES (?, ?, ?, ?, ?)");
        $insert->bind_param('iissi', $user_id, $pid, $color, $size, $add_qty);
        $insert->execute();
        $insert->close();
    }

    $stmt->close();
}

// --- Trả về kết quả ---
jsonResponse([
    'success' => true,
    'added'   => $add_qty,
    'message' => $msg,
    'maxed'   => ($add_qty + $current_qty) >= $max_qty
]);
