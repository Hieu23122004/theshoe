<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    $loginUrl = '/pages/login.php';
    if (!empty($_SERVER['HTTP_HOST'])) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $loginUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/pages/login.php';
    }
    header('Location: ' . $loginUrl);
    exit;
}
include '../includes/database.php';
$user_id = $_SESSION['user_id'];

// Get all orders of the user
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$orders = [];
while ($row = $result->fetch_assoc()) {
    $row['order_details'] = json_decode($row['order_details'], true);
    $orders[] = $row;
}
$stmt->close();
include '../includes/header.php';
?>
<link rel="stylesheet" href="/assets/css/detail_orders.css">
<!-- Bootstrap 5 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<div class="order-details-container">
    <?php if (empty($orders)): ?>
        <div style="text-align:center;color:#888;font-size:1.2rem;">You have no orders yet.</div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <div class="order-details-block">
                <div class="order-details-header">
                    <span class="order-details-shop">Order ID: #<?php echo $order['order_id']; ?></span>
                    <span class="order-details-status"><?php echo htmlspecialchars($order['status']); ?></span>
                </div>

                <div class="order-details-products">
                    <?php foreach ($order['order_details'] as $item): ?>
                        <?php
                        $item_price = isset($item['price']) ? $item['price'] : 0;
                        $item_name = $item['name'] ?? '';
                        $item_color = $item['color'] ?? '';
                        $item_size = $item['size'] ?? '';
                        $item_qty = (int)($item['quantity'] ?? 1);
                        $item_img = '';
                        if ($item_price == 0 && !empty($item['product_id'])) {
                            $pid = (int)$item['product_id'];
                            $stmt_p = $conn->prepare("SELECT price, image_url, name FROM products WHERE product_id = ?");
                            $stmt_p->bind_param('i', $pid);
                            $stmt_p->execute();
                            $stmt_p->bind_result($db_price, $db_img, $db_name);
                            if ($stmt_p->fetch()) {
                                $item_price = $db_price;
                                if (!$item_img) $item_img = $db_img;
                                if (!$item_name) $item_name = $db_name;
                            }
                            $stmt_p->close();
                        }
                        if (!$item_img && !empty($item['product_id'])) {
                            $pid = (int)$item['product_id'];
                            $stmt_img = $conn->prepare("SELECT image_url FROM products WHERE product_id = ?");
                            $stmt_img->bind_param('i', $pid);
                            $stmt_img->execute();
                            $stmt_img->bind_result($db_img2);
                            if ($stmt_img->fetch()) $item_img = $db_img2;
                            $stmt_img->close();
                        }
                        $item_desc = '';
                        if (!empty($item['product_id'])) {
                            $pid = (int)$item['product_id'];
                            $stmt_desc = $conn->prepare("SELECT description FROM products WHERE product_id = ?");
                            $stmt_desc->bind_param('i', $pid);
                            $stmt_desc->execute();
                            $stmt_desc->bind_result($db_desc);
                            if ($stmt_desc->fetch()) $item_desc = $db_desc;
                            $stmt_desc->close();
                        }
                        ?>
                        <div class="order-details-product-card">
                            <img class="order-details-product-img" src="<?php echo htmlspecialchars($item_img); ?>" alt="Product image">
                            <div class="order-details-product-info">
                                <div class="order-details-product-title"><?php echo htmlspecialchars($item_name); ?></div>
                                <div class="order-details-product-meta">Color: <b><?php echo htmlspecialchars($item_color); ?></b> | Size: <b><?php echo htmlspecialchars($item_size); ?></b></div>
                                <div class="order-details-product-qty">Quantity: <b>x<?php echo $item_qty; ?></b></div>
                                <?php if ($item_desc): ?>
                                    <div class="order-details-product-desc">
                                        Description: <?php echo htmlspecialchars($item_desc); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="order-details-meta" style="margin-left:0;padding-left:0; margin-top:2px;">
                                    <b>Order Date:</b> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                    | <b>Payment:</b> <?php echo htmlspecialchars($order['payment_method']); ?>
                                    | <b>Total:</b> <span style="color:black;font-weight:800;font-size:1rem;"><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>₫</span>
                                </div>
                            </div>
                            <div class="order-details-product-price ms-auto">
                                <?php echo number_format($item_price * $item_qty, 0, ',', '.'); ?>₫
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="order-details-action">
                    <a href="/pages/home.php" class="btn">Back to Home</a>
                    <?php if ($order['status'] === 'Processing'): ?>
                        <button class="btn btn-dark btn-cancel-order" data-order-id="<?php echo $order['order_id']; ?>" style=" background: linear-gradient(90deg, #000000 0%, #444444 100%);">Cancel Order</button>
                    <?php endif; ?>
                </div>
            </div>
            <hr class="order-details-hr">
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php include '../includes/truck.php'; ?>
<?php include '../includes/footer.php'; ?>
<!-- Bootstrap 5 JS Bundle (with Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.btn-cancel-order').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!confirm('Are you sure you want to cancel this order?')) return;
                var orderId = this.getAttribute('data-order-id');
                var button = this;
                button.disabled = true;
                fetch('/public/cancel_order.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'order_id=' + encodeURIComponent(orderId)
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            button.closest('.order-details-block').remove();
                        } else {
                            alert(data.message || 'Cannot cancel the order!');
                            button.disabled = false;
                        }
                    })
                    .catch(() => {
                        alert('Server connection error!');
                        button.disabled = false;
                    });
            });
        });
    });
</script>