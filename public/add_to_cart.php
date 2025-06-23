<?php
session_start();
include '../includes/database.php';

if (
    isset($_POST['add_to_cart']) &&
    isset($_POST['product_id']) &&
    isset($_POST['color']) &&
    isset($_POST['size']) &&
    isset($_POST['quantity'])
) {
    $pid = intval($_POST['product_id']);
    $color = trim($_POST['color']);
    $size = trim($_POST['size']);
    $qty = max(1, intval($_POST['quantity']));

    // Kiểm tra tồn kho thực tế theo từng màu/size
    $stmt = $conn->prepare("SELECT size_stock FROM products WHERE product_id = ?");
    $stmt->bind_param('i', $pid);
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

    // Tính tổng số lượng đã có trong giỏ (session hoặc db) với màu/size này
    $current_qty = 0;
    $key = $pid . '_' . $color . '_' . $size;
    if (isset($_SESSION['cart'][$key])) {
        $current_qty = (int)$_SESSION['cart'][$key]['quantity'];
    }

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

    // Nếu đã đủ tồn kho thì báo hết hàng
    if ($current_qty >= $max_qty) {
        echo json_encode([
            'success' => false,
            'message' => 'This is the maximum quantity available in stock.',
            'maxed' => true
        ]);
        exit;
    }

    // Nếu tổng vượt tồn kho thì chỉ cho thêm vừa đủ
    $add_qty = min($qty, $max_qty - $current_qty);
    if ($add_qty < $qty) {
        // Nếu chỉ thêm được một phần, báo cho user
        $msg = 'You can only add up to ' . $add_qty . ' items to the cart (limited stock).';
    } else {
        $msg = '';
    }

    // Thêm vào session cart
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['quantity'] += $add_qty;
    } else {
        $_SESSION['cart'][$key] = [
            'product_id' => $pid,
            'color' => $color,
            'size' => $size,
            'quantity' => $add_qty
        ];
    }

    // Thêm vào db nếu đã đăng nhập
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
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

    echo json_encode([
        'success' => true,
        'added' => $add_qty,
        'message' => $msg,
        'maxed' => ($add_qty + $current_qty) >= $max_qty
    ]);
    exit;
}
echo json_encode(['success' => false, 'message' => 'Invalid request']);
