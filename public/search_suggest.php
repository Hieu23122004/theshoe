<?php
// search_suggest.php
header('Content-Type: application/json');
include '../includes/database.php';

$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$suggestions = [];

if ($keyword !== '' && strlen($keyword) >= 1) {
    $like = '%' . $keyword . '%';
    $stmt = $conn->prepare("SELECT product_id, name, image_url FROM products WHERE name LIKE ? ORDER BY name LIMIT 5");
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = $row;
    }
    $stmt->close();
}

echo json_encode($suggestions);
