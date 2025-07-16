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

// Function để xác định class CSS cho từng bước trong timeline
function getStepClass($currentStatus, $stepStatus)
{
    $statusOrder = ['Pending', 'Processing', 'Shipped', 'Delivered'];
    $currentIndex = array_search($currentStatus, $statusOrder);
    $stepIndex = array_search($stepStatus, $statusOrder);

    if ($currentStatus === 'Cancelled') {
        return $stepStatus === 'Pending' ? 'cancelled' : '';
    }

    if ($currentIndex === false || $stepIndex === false) {
        return '';
    }

    if ($stepIndex < $currentIndex) {
        return 'completed';
    } elseif ($stepIndex === $currentIndex) {
        return 'active';
    }

    return '';
}

// Function để lấy icon cho từng trạng thái
function getStatusIcon($status)
{
    switch ($status) {
        case 'Pending':
            return '⏳';
        case 'Processing':
            return '🔄';
        case 'Shipped':
            return '🚚';
        case 'Delivered':
            return '✅';
        default:
            return '○';
    }
}

$user_id = $_SESSION['user_id'];
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
<style>
    .order-status-timeline {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 20px 0;
        padding: 0 10px;
        position: relative;
    }

    .order-status-timeline::before {
        content: '';
        position: absolute;
        top: 20px;
        left: 50px;
        right: 50px;
        height: 2px;
        background-color: #e0e0e0;
        z-index: 1;
    }

    .status-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 2;
        background: white;
        padding: 5px;
    }

    .status-step.active {
        color: #28a745;
    }

    .status-step.completed {
        color: #28a745;
    }

    .status-step.cancelled {
        color: #dc3545;
    }

    .status-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: 2px solid #e0e0e0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
        margin-bottom: 8px;
        font-size: 18px;
    }

    .status-step.active .status-circle,
    .status-step.completed .status-circle {
        border-color: #28a745;
        background: #28a745;
        color: white;
    }

    .status-step.cancelled .status-circle {
        border-color: #dc3545;
        background: #dc3545;
        color: white;
    }

    .status-label {
        font-size: 12px;
        font-weight: 500;
        text-align: center;
        white-space: nowrap;
    }

    @media (max-width: 768px) {
        .order-status-timeline {
            padding: 0 5px;
        }

        .order-status-timeline::before {
            left: 25px;
            right: 25px;
        }

        .status-circle {
            width: 30px;
            height: 30px;
            font-size: 14px;
        }

        .status-label {
            font-size: 10px;
        }
    }
</style>
<div class="order-details-container">
    <?php if (empty($orders)): ?>
        <div style="text-align:center;color:#888;font-size:1.2rem;">You have no orders yet.</div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <div class="order-details-block">
                <div class="order-details-header">
                    <span class="order-details-shop">Order ID: #<?php echo $order['order_id']; ?></span>
                </div>

                <!-- Order Status Timeline -->
                <div class="order-status-timeline">
                    <?php
                    $statuses = ['Pending', 'Processing', 'Shipped', 'Delivered'];
                    foreach ($statuses as $status):
                        $stepClass = getStepClass($order['status'], $status);
                        $icon = getStatusIcon($status);
                    ?>
                        <div class="status-step <?php echo $stepClass; ?>">
                            <div class="status-circle">
                                <?php echo $icon; ?>
                            </div>
                            <div class="status-label"><?php echo $status; ?></div>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($order['status'] === 'Cancelled'): ?>
                        <div class="status-step cancelled">
                            <div class="status-circle">
                                ❌
                            </div>
                            <div class="status-label">Cancelled</div>
                        </div>
                    <?php endif; ?>
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
                    <?php if ($order['status'] === 'Pending'): ?>
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