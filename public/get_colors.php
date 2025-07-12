<?php
include '../includes/database.php';

$type = isset($_GET['type']) ? $_GET['type'] : 'product'; // 'product' hoặc 'accessory'
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$colors = [];

if ($type === 'accessory') {
    // Lấy tất cả category_id có parent_id = 2 hoặc 3
    $category_ids = [];
    $cat_result = $conn->query("SELECT category_id FROM categories WHERE parent_id IN (2,3)");
    if ($cat_result) {
        while ($row = $cat_result->fetch_assoc()) {
            $category_ids[] = (int)$row['category_id'];
        }
    }
    if ($category_id && in_array($category_id, $category_ids)) {
        $color_result = $conn->query("SELECT DISTINCT color_options FROM products WHERE category_id = $category_id");
    } else {
        $color_result = $conn->query("SELECT DISTINCT color_options FROM products p JOIN categories c ON p.category_id = c.category_id WHERE c.parent_id IN (2,3)");
    }
} else {
    // Mặc định: type=product, lấy theo parent_id = 1
    $category_ids = [];
    $cat_result = $conn->query("SELECT category_id FROM categories WHERE parent_id = 1");
    if ($cat_result) {
        while ($row = $cat_result->fetch_assoc()) {
            $category_ids[] = (int)$row['category_id'];
        }
    }
    if ($category_id && in_array($category_id, $category_ids)) {
        $color_result = $conn->query("SELECT DISTINCT color_options FROM products WHERE category_id = $category_id");
    } else {
        $color_result = $conn->query("SELECT DISTINCT color_options FROM products p JOIN categories c ON p.category_id = c.category_id WHERE c.parent_id = 1");
    }
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
