<?php
session_start();
require_once __DIR__ . '/../includes/database.php';

header('Content-Type: application/json');
$response = ['success' => false];

if (!isset($_POST['product_id'])) {
    echo json_encode($response);
    exit;
}

$product_id = (int)$_POST['product_id'];

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param('ii', $_SESSION['user_id'], $product_id);
    $stmt->execute();
    $stmt->close();
    $response['success'] = true;
} else if (isset($_SESSION['favorites']) && is_array($_SESSION['favorites'])) {
    $_SESSION['favorites'] = array_filter($_SESSION['favorites'], function($pid) use ($product_id) {
        return $pid != $product_id;
    });
    $response['success'] = true;
}

echo json_encode($response);
