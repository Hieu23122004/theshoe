<?php
require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

header('Content-Type: application/json');

// Kết nối DB
$conn = new mysqli(
    $_ENV['DB_HOST'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASS'],
    $_ENV['DB_NAME']
);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi kết nối database']);
    exit;
}

// Query dữ liệu
$sql = "SELECT * FROM discount_codes";
$result = $conn->query($sql);

$discount_codes = [];
while ($row = $result->fetch_assoc()) {
    $discount_codes[] = $row;
}

// Xuất ra JSON
echo json_encode($discount_codes);

$conn->close();
?>
