<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Dotenv\Dotenv;

// Tắt error reporting để tránh xuất ra text không mong muốn
error_reporting(0);
ini_set('display_errors', 0);

// Bắt đầu output buffering
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (Exception $e) {
    ob_clean();
    echo json_encode(["status" => "error", "message" => "Environment file error: " . $e->getMessage()]);
    exit;
}

// Nhận dữ liệu từ JSON body
$input = file_get_contents('php://input');
if (empty($input)) {
    ob_clean();
    echo json_encode(["status" => "error", "message" => "No input data received"]);
    exit;
}

$data = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    ob_clean();
    echo json_encode(["status" => "error", "message" => "Invalid JSON input: " . json_last_error_msg()]);
    exit;
}



$category = array_key_exists('category', $data) ? $data['category'] : null;
$product_name = array_key_exists('product_name', $data) ? $data['product_name'] : null;
$color = array_key_exists('color', $data) ? $data['color'] : null;
$size = array_key_exists('size', $data) ? $data['size'] : null;
$original = array_key_exists('original', $data) ? $data['original'] : null;
$wants_discount_code = array_key_exists('wants_discount_code', $data) ? $data['wants_discount_code'] : false;
$asking_price = array_key_exists('asking_price', $data) ? $data['asking_price'] : null;
$min_price = array_key_exists('min_price', $data) ? $data['min_price'] : null;
$max_price = array_key_exists('max_price', $data) ? $data['max_price'] : null;
$discount_percent = array_key_exists('discount_percent', $data) ? $data['discount_percent'] : false;
$is_featured = array_key_exists('is_featured', $data) ? $data['is_featured'] : false;
// Chuẩn hóa các trường đầu vào: nếu là chuỗi rỗng hoặc không hợp lệ thì gán null
if ($color === '' || $color === [] || $color === "undefined") $color = null;
if ($size === '' || $size === [] || $size === "undefined") $size = null;
if ($min_price === '' || $min_price === "undefined") $min_price = null;
if ($max_price === '' || $max_price === "undefined") $max_price = null;
if ($product_name === '' || $product_name === "undefined") $product_name = null;
if ($category === '' || $category === "undefined") $category = null;
if ($original === '' || $original === "undefined") $original = null;

// Nếu color/size là mảng, lấy giá trị đầu tiên (hoặc xử lý nhiều giá trị nếu cần)
if (is_array($color)) {
    $color = count($color) > 0 ? $color[0] : null;
}
if (is_array($size)) {
    $size = count($size) > 0 ? $size[0] : null;
}

// Kết nối PDO
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    ob_clean();
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

// Xây dựng SQL
try {

// Kiểm tra nếu chỉ muốn mã giảm giá mà không có thông tin sản phẩm khác
if ($wants_discount_code && empty($product_name) && empty($category) && $min_price === null && $max_price === null && empty($color) && empty($size) && $discount_percent === false && $is_featured === false) {
    // Lấy 1 mã giảm giá ngẫu nhiên (bất kể loại nào)
    $discount_codes = [];
    
    // Lấy 1 mã bất kỳ còn sử dụng được
    $sql_random = "SELECT * FROM discount_codes WHERE valid_until > NOW() AND (max_uses IS NULL OR used_count < max_uses) ORDER BY RAND() LIMIT 1";
    $stmt_random = $pdo->prepare($sql_random);
    $stmt_random->execute();
    $random_code = $stmt_random->fetch(PDO::FETCH_ASSOC);
    
    if ($random_code) $discount_codes[] = $random_code;
    
    // Format response cho discount codes
    $result = [];
    $formatted_response = [];
    
    foreach ($discount_codes as $code) {
        $discount_text = $code['discount_type'] === 'percent' 
            ? $code['discount_value'] . '%' 
            : number_format($code['discount_value'], 0, ',', '.') . ' VNĐ';
            
        $min_order_text = $code['min_order_amount'] > 0 
            ? ' (Đơn tối thiểu: ' . number_format($code['min_order_amount'], 0, ',', '.') . ' VNĐ)'
            : '';
            
        $result[] = [
            "code" => $code['code'],
            "discount_type" => $code['discount_type'],
            "discount_value" => $code['discount_value'],
            "discount_text" => $discount_text,
            "min_order_amount" => $code['min_order_amount'],
            "valid_until" => $code['valid_until']
        ];
        
        $formatted_response[] = 
            "Đây là thông tin Voucher dành cho bạn:\n" .
            "🎟️ Mã: " . $code['code'] . "\n" .
            "💰 Giảm: " . $discount_text . $min_order_text . "\n" .
            "📅 Hết hạn: " . date('d/m/Y H:i', strtotime($code['valid_until']));
    }
    
    // Trả về kết quả cho discount codes
    if (ob_get_length()) ob_clean();
    echo json_encode([
        "status" => "success",
        "type" => "discount_codes",
        "input" => compact('category', 'product_name', 'color', 'size', 'original', 'wants_discount_code', 'asking_price', 'min_price', 'max_price', 'discount_percent', 'is_featured'),
        "result_count" => count($result),
        "discount_codes" => $result,
        "formatted_display" => $formatted_response
    ]);
    exit;
}

// Universal Product Query Builder - Xử lý tất cả 127 tổ hợp có thể
$sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id WHERE 1=1";
$params = [];

// Debug: Log the search criteria
error_log("=== PRODUCT SEARCH DEBUG ===");
error_log("category: " . ($category ?? 'null'));
error_log("product_name: " . ($product_name ?? 'null'));
error_log("color: " . ($color ?? 'null'));
error_log("size: " . ($size ?? 'null'));
error_log("min_price: " . ($min_price ?? 'null'));
error_log("max_price: " . ($max_price ?? 'null'));
error_log("discount_percent: " . ($discount_percent ? 'true' : 'false'));
error_log("is_featured: " . ($is_featured ? 'true' : 'false'));

// 1. Product Name Filter
if (!empty($product_name)) {
    $sql .= " AND LOWER(p.name) LIKE ?";
    $params[] = '%' . strtolower($product_name) . '%';
    error_log("Added product_name filter: " . $product_name);
}

// 2. Category Filter (parent và child categories)
if (!empty($category)) {
    $sql .= " AND (c.name = ? OR c.category_id IN (
        SELECT category_id FROM categories WHERE parent_id IN (
            SELECT category_id FROM categories WHERE name = ?
        )
    ))";
    $params[] = $category;
    $params[] = $category;
    error_log("Added category filter: " . $category);
}

// 3. Color Filter
if (!empty($color)) {
    $sql .= " AND JSON_CONTAINS(LOWER(JSON_EXTRACT(p.color_options, '$[*]')), LOWER(?), '$')";
    $params[] = "\"$color\"";
    error_log("Added color filter: " . $color);
}

// 4. Size Filter
if (!empty($size)) {
    $sql .= " AND JSON_CONTAINS_PATH(p.size_stock, 'one', '$.\"$size\"')";
    error_log("Added size filter: " . $size);
}

// 5. Price Range Filters
if ($min_price !== null && $max_price !== null) {
    $sql .= " AND p.price >= ? AND p.price <= ?";
    $params[] = $min_price;
    $params[] = $max_price;
    error_log("Added price range filter: " . $min_price . " - " . $max_price);
} else if ($min_price !== null) {
    $sql .= " AND p.price >= ?";
    $params[] = $min_price;
    error_log("Added min_price filter: " . $min_price);
} else if ($max_price !== null) {
    $sql .= " AND p.price <= ?";
    $params[] = $max_price;
    error_log("Added max_price filter: " . $max_price);
}

// 6. Discount Percent Filter
if ($discount_percent === true) {
    $sql .= " AND p.discount_percent > 0";
    error_log("Added discount_percent filter: > 0");
}

// 7. Featured Product Filter
if ($is_featured === true) {
    $sql .= " AND p.is_featured = 1";
    error_log("Added is_featured filter: = 1");
}

// Ordering Strategy - Ưu tiên theo product_name nếu có, nếu không thì random
if (!empty($product_name)) {
    $sql .= " ORDER BY ABS(LENGTH(p.name) - LENGTH(?)) ASC, RAND() LIMIT 1";
    $params[] = $product_name;
} else {
    $sql .= " ORDER BY RAND() LIMIT 1";
}

error_log("Final SQL: " . $sql);
error_log("Parameters: " . json_encode($params));

// Execute query
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

error_log("Query result count: " . count($products));
if (count($products) > 0) {
    error_log("Found products: " . json_encode(array_column($products, 'name')));
} else {
    error_log("No products found matching criteria");
}

// Handle discount code for Priority 4 (Product + Discount)
$discount_result = null;
$discount_formatted = "";
if ($wants_discount_code && (!empty($product_name) || !empty($category) || $min_price !== null || $max_price !== null || !empty($color) || !empty($size) || $discount_percent === true || $is_featured === true)) {
    // Lấy 1 mã percent ngẫu nhiên
    $sql_percent = "SELECT * FROM discount_codes WHERE discount_type = 'percent' AND valid_until > NOW() AND (max_uses IS NULL OR used_count < max_uses) ORDER BY RAND() LIMIT 1";
    $stmt_percent = $pdo->prepare($sql_percent);
    $stmt_percent->execute();
    $discount_code = $stmt_percent->fetch(PDO::FETCH_ASSOC);
    
    if ($discount_code) {
        $discount_text = $discount_code['discount_value'] . '%';
        $min_order_text = $discount_code['min_order_amount'] > 0 
            ? ' (Đơn tối thiểu: ' . number_format($discount_code['min_order_amount'], 0, ',', '.') . ' VNĐ)'
            : '';
            
        $discount_result = [
            "code" => $discount_code['code'],
            "discount_type" => $discount_code['discount_type'],
            "discount_value" => $discount_code['discount_value'],
            "discount_text" => $discount_text,
            "min_order_amount" => $discount_code['min_order_amount'],
            "valid_until" => $discount_code['valid_until']
        ];
        
        $discount_formatted = 
            "- 🎟️ Mã: " . $discount_code['code'] . "\n" .
            "- 💰 Giảm: " . $discount_text . $min_order_text . "\n" .
            "- 📅 Hết hạn: " . date('d/m/Y H:i', strtotime($discount_code['valid_until']));
    }
}


// Trả về kết quả chuẩn hóa
$result = [];
$formatted_response = [];

foreach ($products as $p) {
    // Lấy size có sẵn từ size_stock
    $sizes = [];
    $size_stock = json_decode($p['size_stock'], true);
    if (is_array($size_stock)) {
        foreach ($size_stock as $colorKey => $sizeArr) {
            if (is_array($sizeArr)) {
                foreach ($sizeArr as $sizeKey => $qty) {
                    if ($qty > 0 && !in_array($sizeKey, $sizes)) {
                        $sizes[] = $sizeKey;
                    }
                }
            }
        }
    }
    
    // Format cho JSON response
    $result[] = [
        "name" => $p['name'],
        "image" => $p['image_url'], // Raw URL để tránh redirect
        "price" => number_format($p['price'], 0, ',', '.') . " VNĐ",
        "size" => $sizes,
        "link" => "Chi tiết:http://localhost:3000/pages/detail_products.php?id=" . $p['product_id']
    ];
    
    // Format cho chatbot display (để chatbot dễ parse và hiển thị)
    $formatted_response[] = 
        "Đây là thông tin sản phẩm dành cho bạn:\n" .
        "- 👟 Sản phẩm: " . $p['name'] . "\n" .
        "- 💸 Giá: " . number_format($p['price'], 0, ',', '.') . " VNĐ\n" .
        "- 📏 Size: " . implode(', ', $sizes) . "\n" .
        "- 🔗 Chi tiết: https://b2c41a722a50.ngrok-free.app/pages/detail_products.php?id=" . $p['product_id'];
}

// Nếu có cả sản phẩm và muốn mã giảm giá (Priority 4)
if ($wants_discount_code && (!empty($product_name) || !empty($category) || $min_price !== null || $max_price !== null || !empty($color) || !empty($size)) && isset($discount_result)) {
    // Trả về kết quả kết hợp sản phẩm + mã giảm giá
    if (ob_get_length()) ob_clean();
    echo json_encode([
        "status" => "success",
        "type" => "product_with_discount",
        "input" => compact('category', 'product_name', 'color', 'size', 'original', 'wants_discount_code', 'asking_price', 'min_price', 'max_price', 'discount_percent', 'is_featured'),
        "product_name_detected" => $product_name ?? '',
        "result_count" => count($result),
        "products" => $result,
        "discount_code" => $discount_result,
        "formatted_display" => array_merge($formatted_response, $discount_formatted ? [$discount_formatted] : [])
    ]);
    exit;
}



// Đảm bảo không có output thừa
if (ob_get_length()) ob_clean();
echo json_encode([
    "status" => "success",
    "input" => compact('category', 'product_name', 'color', 'size', 'original', 'wants_discount_code', 'asking_price', 'min_price', 'max_price', 'discount_percent', 'is_featured'),
    "product_name_detected" => $product_name ?? '',
    "result_count" => count($result),
    "products" => $result,
    "formatted_display" => $formatted_response // Thêm format đặc biệt cho chatbot
]);
exit;

} catch (Exception $e) {
    // Xử lý lỗi tổng thể
    if (ob_get_length()) ob_clean();
    echo json_encode([
        "status" => "error", 
        "message" => "An error occurred while processing your request"
    ]);
    exit;
}
