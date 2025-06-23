<?php
session_start();
header('Content-Type: application/json');
$count = 0;
if (isset($_SESSION['user_id'])) {
    include_once __DIR__ . '/../includes/database.php';
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM cart_items WHERE user_id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $count = (int)$row['total'];
    }
    $stmt->close();
} else if (isset($_SESSION['cart'])) {
    $count = count($_SESSION['cart']);
}
echo json_encode(['success' => true, 'count' => $count]);
