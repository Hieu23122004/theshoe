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
<style>
    .favorite-mini-list {
        background: #fff;
        padding: 1rem;
        border-radius: 10px;
        border: 1px solid #dee2e6;
        max-height: 400px;
        overflow-y: auto;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .favorite-item {
        display: flex;
        gap: 0.75rem;
        padding: 0.75rem;
        margin-bottom: 0.75rem;
        background: #f9f9f9;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        transition: background 0.2s ease;
    }

    .favorite-item:hover {
        background: #f1f1f1;
    }

    .favorite-item img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #ccc;
    }

    .favorite-info {
        flex-grow: 1;
    }

    .favorite-remove-btn {
        width: 28px;
        height: 30px;
        padding: 0;
        border: none;
        background: #f8f9fa;
        border: 1px solid #ced4da;
        border-radius: 4px;
        transition: background 0.2s ease;
    }

    .favorite-remove-btn:hover {
        background: #e9ecef;
    }

    .favorite-remove-btn i {
        font-size: 14px;
        color: #dc3545;
    }

    .favorite-empty {
        margin-top: 4rem;
        margin-bottom: 4rem;
        color: #adb5bd;
        font-size: 1.1rem;
    }
</style>
<div class="favorite-mini-list bg-white p-3 rounded-2 border border-secondary-subtle" style="max-height: 450px; overflow-y: auto;">
    <?php if (count($favorites) === 0): ?>
        <div class="text-center favorite-empty">No favorite products yet.</div>
    <?php else: ?>
        <?php foreach ($favorites as $fav): ?>
            <div class="d-flex gap-3 mb-3 p-2 border rounded shadow-sm" style="margin-top: 8px; max-width: 100%; width: 100%; margin-bottom: 8px; background: #f8f9fa;">
                <img src="<?php echo htmlspecialchars($fav['image_url']); ?>" class="rounded-2 border" style="width: 115px; height: 100px; object-fit: cover;">
                
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

