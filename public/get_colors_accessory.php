<?php
include '../includes/database.php';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$colors = [];
if ($category_id && in_array($category_id, [9,10,11,12,13,14])) {
    $color_result = $conn->query("SELECT DISTINCT color_options FROM products WHERE category_id = $category_id");
} else {
    $color_result = $conn->query("SELECT DISTINCT color_options FROM products p JOIN categories c ON p.category_id = c.category_id WHERE c.parent_id IN (2,3)");
}
if ($color_result) {
    $color_set = [];
    while ($row = $color_result->fetch_assoc()) {
        $arr = json_decode($row['color_options'], true);
        if (is_array($arr)) {
            foreach ($arr as $c) {
                $color_set[$c] = true;
            }
        }
    }
    $colors = array_keys($color_set);
}
header('Content-Type: application/json');
echo json_encode(['success' => true, 'colors' => $colors]);
