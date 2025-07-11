<?php
include '../includes/header.php';
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
$cart = &$_SESSION['cart'];

include '../includes/database.php';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $cart = [];
    $stmt = $conn->prepare("SELECT product_id, color, size, quantity FROM cart_items WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $key = $row['product_id'] . '_' . $row['color'] . '_' . $row['size'];
        $cart[$key] = [
            'product_id' => $row['product_id'],
            'color' => $row['color'],
            'size' => $row['size'],
            'quantity' => $row['quantity']
        ];
    }
    $stmt->close();
}

$product_ids = array_map(function ($item) {
    return $item['product_id'];
}, $cart);
$products = [];
if (!empty($product_ids)) {
    $ids = implode(',', array_map('intval', array_unique($product_ids)));
    $result = $conn->query("SELECT * FROM products WHERE product_id IN ($ids)");
    while ($row = $result->fetch_assoc()) {
        $products[$row['product_id']] = $row;
    }
}

$total_quantity = 0;
foreach ($cart as $item) $total_quantity += $item['quantity'];
?>

<div class="container" style="margin-top:32px;">
    <div class="cart-main">
        <div class="cart-list cart-list-scrollable">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
                <h3 style="font-size:1.3rem;font-weight:700;">Giỏ hàng:</h3>
                <span style="font-size:15px;color:#222;"><?php echo $total_quantity; ?> sản phẩm</span>
            </div>
            <?php if (empty($cart)): ?>
                <div class="alert alert-dark" style="text-align: center;">
                    Your cart is empty. <a href="/pages/type_products.php" class="">Start shopping now!</a>
                </div>

            <?php else: ?>
                <?php $grand_total = 0; ?>
                <?php foreach ($cart as $key => $item):
                    $pid = $item['product_id'];
                    if (!isset($products[$pid])) continue;
                    $p = $products[$pid];
                    $total = $p['price'] * $item['quantity'];
                    $grand_total += $total;
                ?>
                    <div class="cart-item" data-pid="<?php echo $pid; ?>" data-color="<?php echo htmlspecialchars($item['color']); ?>" data-size="<?php echo htmlspecialchars($item['size']); ?>" style="display:flex;align-items:center;position:relative;">
                        <!-- Checkbox sát mép trái -->
                        <input type="checkbox" class="cart-item-select" style="width:18px;height:18px;margin-right:0;" />
                        <a href="/pages/detail_products.php?id=<?php echo $pid; ?>">
                            <img src="<?php echo htmlspecialchars($p['image_url']); ?>" class="cart-item-img" alt="">
                        </a>
                        <div class="cart-item-info">
                            <div class="cart-item-title">
                                <a href="/pages/detail_products.php?id=<?php echo $pid; ?>" style="color:inherit;text-decoration:none;">
                                    <?php echo htmlspecialchars($p['name']); ?>
                                </a>
                            </div>
                            <div class="cart-item-variant"><?php echo htmlspecialchars($item['color']); ?> / <?php echo htmlspecialchars($item['size']); ?></div>
                            <div class="cart-item-sku">Product ID: <?php echo htmlspecialchars($p['sku'] ?? $p['product_id']); ?></div>
                            <div class="cart-item-price"><?php echo number_format($p['price']); ?>₫</div>
                        </div>
                        <div class="cart-item-actions">
                            <div class="qty-box">
                                <button type="button" class="qty-btn qty-decrease" data-pid="<?php echo $pid; ?>" data-color="<?php echo htmlspecialchars($item['color']); ?>" data-size="<?php echo htmlspecialchars($item['size']); ?>">-</button>
                                <input type="text" readonly class="qty-input" value="<?php echo (int)$item['quantity']; ?>">
                                <button type="button" class="qty-btn qty-increase" data-pid="<?php echo $pid; ?>" data-color="<?php echo htmlspecialchars($item['color']); ?>" data-size="<?php echo htmlspecialchars($item['size']); ?>">+</button>
                            </div>
                            <button type="button" class="cart-item-remove"
                                data-pid="<?php echo $pid; ?>"
                                data-color="<?php echo htmlspecialchars($item['color']); ?>"
                                data-size="<?php echo htmlspecialchars($item['size']); ?>"
                                data-image="<?php echo htmlspecialchars($p['image_url']); ?>"
                                data-name="<?php echo htmlspecialchars($p['name']); ?>"
                                data-price="<?php echo (int)$p['price']; ?>">Delete</button>
                        </div>
                        <div class="cart-item-total"><?php echo number_format($total); ?>₫</div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="cart-summary">
            <div class="cart-summary-title">Order Information</div>
            <div class="cart-summary-label">Total Payment Due:</div>
            <div class="cart-summary-total">0₫</div>
            <div style="font-size:14px;color:#444;margin-bottom:10px;">Shipping fees will be calculated the checkout page.<br>You can also enter a discount code below</div>
            <form class="cart-summary-form">
                <button type="button" class="cart-summary-btn">CHECKOUT NOW</button>
            </form>
            <a href="/pages/home.php" class="cart-summary-link">&larr; Continue Shopping</a>
        </div>
    </div>
</div>
<?php include '../includes/truck.php'; ?>
<?php include '../includes/footer.php'; ?>
<?php include '../includes/floating_contact.php'; ?>
<script src="/assets/js/auto_logout.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/cart.js"></script>

<!-- Bootstrap 5 JS Bundle (with Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<link rel="stylesheet" href="/assets/css/cart.css">
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>