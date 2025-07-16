<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
include '../../includes/database.php';
try {
    $query = "
        SELECT 
            c.category_id,
            c.name,
            c.parent_id,
            p.name as parent_name
        FROM categories c 
        LEFT JOIN categories p ON c.parent_id = p.category_id 
        ORDER BY c.parent_id, c.category_id
    ";
    $result = $conn->query($query);
    if (!$result) {
        throw new Exception('Database query failed: ' . $conn->error);
    }
    $categories = [];
    $hierarchy = [
        'footwear' => ['parent_id' => 1, 'children' => []],
        'handbag' => ['parent_id' => 2, 'children' => []],
        'belt' => ['parent_id' => 3, 'children' => []]
    ];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
        if ($row['parent_id'] == 1) {
            $hierarchy['footwear']['children'][] = $row['category_id'];
        } elseif ($row['parent_id'] == 2) {
            $hierarchy['handbag']['children'][] = $row['category_id'];
        } elseif ($row['parent_id'] == 3) {
            $hierarchy['belt']['children'][] = $row['category_id'];
        }
    }
    echo json_encode([
        'success' => true,
        'categories' => $categories,
        'hierarchy' => $hierarchy
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
