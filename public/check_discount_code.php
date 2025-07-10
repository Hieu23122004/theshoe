<?php
session_start();
include_once __DIR__ . '/../includes/database.php';

$code = isset($_POST['code']) ? trim($_POST['code']) : '';
$now = date('Y-m-d H:i:s');
$response = [
    'valid' => false,
    'message' => 'Invalid or expired discount code.'
];

$selected_items = isset($_POST['selected_items']) ? json_decode($_POST['selected_items'], true) : null;

// Tính tổng tiền giỏ hàng hiện tại
$cart = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM cart_items WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $cart[] = $row;
    }
    $stmt->close();
} else if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart[] = $item;
    }
}

// Nếu có selected_items (từ checkout), chỉ tính tổng tiền các sản phẩm này
if ($selected_items && is_array($selected_items) && count($selected_items) > 0) {
    $cart = [];
    foreach ($selected_items as $item) {
        $pid = isset($item['product_id']) ? (int)$item['product_id'] : 0;
        $color = isset($item['color']) ? $item['color'] : '';
        $size = isset($item['size']) ? $item['size'] : '';
        $qty = isset($item['quantity']) ? (int)$item['quantity'] : 1;
        if ($pid) {
            $cart[] = [
                'product_id' => $pid,
                'color' => $color,
                'size' => $size,
                'quantity' => $qty
            ];
        }
    }
}

$product_ids = array_column($cart, 'product_id');
$products = [];
if (!empty($product_ids)) {
    $ids = implode(',', array_map('intval', array_unique($product_ids)));
    $result = $conn->query("SELECT * FROM products WHERE product_id IN ($ids)");
    while ($row = $result->fetch_assoc()) {
        $products[$row['product_id']] = $row;
    }
}
$subtotal = 0;
foreach ($cart as $item) {
    $pid = $item['product_id'];
    $qty = isset($item['quantity']) ? (int)$item['quantity'] : 1;
    $price = isset($products[$pid]) ? $products[$pid]['price'] : 0;
    $subtotal += $price * $qty;
}

if ($code) {
    $stmt = $conn->prepare("SELECT * FROM discount_codes WHERE code = ?");
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        // Kiểm tra thời gian hiệu lực
        if (
            (!$row['valid_from'] || $row['valid_from'] <= $now) &&
            (!$row['valid_until'] || $row['valid_until'] >= $now) &&
            ($row['max_uses'] === null || $row['used_count'] < $row['max_uses'])
        ) {
            // Kiểm tra min_order_amount
            $min_order = (float)($row['min_order_amount'] ?? 0);
            if ($subtotal < $min_order) {
                $response['message'] = 'This code requires a higher order value (' . number_format($min_order, 0, ',', '.') . '₫).';
            } else {
                $response['valid'] = true;
                $response['discount_type'] = $row['discount_type'];
                $response['discount_value'] = $row['discount_value'];
                // Nếu là mã freeship, trả về shipping_discount
                if ($row['discount_type'] === 'fixed' && stripos($row['code'], 'freeship') !== false) {
                    $response['shipping_discount'] = $row['discount_value'];
                }
                $response['message'] = 'Code applied successfully!';
            }
        } else {
            $response['message'] = 'This promo code is no longer valid.';
        }
    }
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode($response);
