<?php
// search_suggest.php
include '../includes/database.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$suggestions = [];

if ($q !== '') {
    // Ưu tiên sản phẩm nổi bật, mới, giảm giá
    $stmt = $conn->prepare("
        SELECT product_id, name, image_url, price, discount_percent
        FROM products
        WHERE name LIKE CONCAT('%', ?, '%')
        ORDER BY is_featured DESC, created_at DESC, discount_percent DESC, name ASC
        LIMIT 5
    ");
    $stmt->bind_param("s", $q);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = $row;
    }
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'suggestions' => $suggestions]);
