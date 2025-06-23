<?php
session_start();
require_once __DIR__ . '/../includes/database.php';

// Kiểm tra đăng nhập trước khi xử lý
if (!isset($_SESSION['user_id'])) {
    header('Location: /pages/login.php');
    exit;
}

$favorites = [];
if (isset($_SESSION['user_id'])) {
    // Debug: kiểm tra user_id
    // echo "User ID: " . $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT f.product_id, p.name, p.image_url, p.price FROM favorites f JOIN products p ON f.product_id = p.product_id WHERE f.user_id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    // Debug: kiểm tra số dòng trả về
    // echo "Num rows: " . $result->num_rows;
    while ($row = $result->fetch_assoc()) {
        $favorites[] = $row;
    }
    $stmt->close();
} else if (isset($_SESSION['favorites']) && is_array($_SESSION['favorites'])) {
    foreach ($_SESSION['favorites'] as $pid) {
        $stmt = $conn->prepare("SELECT product_id, name, image_url, price FROM products WHERE product_id = ?");
        $stmt->bind_param('i', $pid);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $favorites[] = $row;
        }
        $stmt->close();
    }
}
?>

<div class="text-center fw-bold fs-4 mb-3">Favorite Products</div>
<div class="favorite-mini-list bg-white p-3 rounded-2 border border-secondary-subtle" style="max-height: 400px; overflow-y: auto;">
    <?php if (count($favorites) === 0): ?>
        <div class="text-center text-muted my-5">No favorite products yet.</div>
    <?php else: ?>
        <?php foreach ($favorites as $fav): ?>
            <div class="d-flex gap-3 mb-2 p-2 border rounded shadow-sm" style="margin: 0 auto; max-width: 100%; width: 95%;">
                <img src="<?php echo htmlspecialchars($fav['image_url']); ?>" class="rounded-2 border" style="width: 80px; height: 80px; object-fit: cover;">
                
                <div class="flex-grow-1">
                    <div class="fw-semibold"><?php echo htmlspecialchars($fav['name']); ?></div>
                    <div class="text-secondary small mb-1" style="font-weight: 500;">Product ID: <?php echo $fav['product_id']; ?></div>
                    <div class="text-danger fw-bold"><?php echo number_format($fav['price'], 0, ',', '.'); ?>₫</div>
                </div>
                
                <button class="favorite-mini-remove btn btn-light p-0 border" style="width: 28px; height: 30px;" data-pid="<?php echo $fav['product_id']; ?>">
                    <i class="bi bi-x-lg" style="font-size: 14px;"></i>
                </button>

            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

