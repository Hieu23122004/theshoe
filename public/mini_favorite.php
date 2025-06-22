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
<div style="font-size: 1.5rem; font-weight: 700; text-align: center; margin-bottom: 12px;">Favorite Products</div>

<div class="favorite-mini-list" style="background:#fff; padding:16px; border-radius:12px; border:1px solid #ddd; max-height:400px; overflow-y:auto;">
    <?php if (count($favorites) === 0): ?>
        <div style="text-align:center; color:#888; margin: 30px 0;">No favorite products yet.</div>
    <?php else: ?>
        <?php foreach ($favorites as $fav): ?>
            <div class="d-flex align-items-center mb-3" style="gap:16px;">
                <img src="<?php echo htmlspecialchars($fav['image_url']); ?>" style="width:100px;height:100px;object-fit:cover;border-radius:8px;border:1px solid #eee;">
                <div style="flex:1;">
                    <div style="font-weight:700;"><?php echo htmlspecialchars($fav['name']); ?></div>
                    <div style="color:#e74c3c;font-weight:700;"><?php echo number_format($fav['price'], 0, ',', '.'); ?>₫</div>
                </div>
                <button class="favorite-mini-remove btn btn-link text-dark" data-pid="<?php echo $fav['product_id']; ?>" style="font-size:18px;"><i class="bi bi-x-lg"></i></button>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
