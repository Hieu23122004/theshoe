<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
include '../../includes/database.php';
if (!isset($_GET['parent_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Parent ID is required']);
    exit;
}
$parent_id = intval($_GET['parent_id']);
try {
    $stmt = $conn->prepare("SELECT category_id, name FROM categories WHERE parent_id = ? ORDER BY name ASC");
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    echo json_encode($categories);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
