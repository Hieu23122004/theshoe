<?php
include '../includes/header.php';
include '../includes/database.php';
?>
<div class="container-fluid" style="margin-top:100px;padding:0;">
    <div class="row g-0 bg-white" style="min-height:100vh;">
        <div class="col-md-7 p-5" style="min-height:100vh;">
            
            <nav style="font-size:14px;margin-bottom:18px;">
                <a href="/pages/cart.php" class="text-decoration-none text-primary">My Cart</a>
                <span class="mx-1 text-muted">&gt;</span>
                <span class="text-dark">Shipping Information</span>
                <span class="mx-1 text-muted">&gt;</span>
                <span class="text-muted">Payment Method</span>
            </nav>
            <h4 class="mb-3 fw-bold" style="font-size:1.3rem;">Shipping Information</h4>
            <div class="mb-3" style="font-size:15px;">
                Do you have an account? <a href="/pages/login.php" class="text-primary">Login Now</a>
            </div>
            <form id="checkoutForm" autocomplete="off">
                <div class="row g-2 mb-2">
                    <div class="col-12">
                        <input type="text" class="form-control" name="fullname" placeholder="Họ và tên" required>
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <input type="email" class="form-control" name="email" placeholder="Email" required>
                    </div>
                    <div class="col-6">
                        <input type="tel" class="form-control" name="phone" placeholder="Số điện thoại" required>
                    </div>
                </div>
                <div class="mb-2">
                    <input type="text" class="form-control" name="address" placeholder="Địa chỉ" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <select class="form-select" name="province" required>
                            <option value="">Tỉnh / thành</option>
                            <option>Bắc Kạn</option>
                            <!-- ...other provinces... -->
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="district" required>
                            <option value="">Quận / huyện</option>
                            <option>Huyện Bạch Thông</option>
                            <!-- ...other districts... -->
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="ward" required>
                            <option value="">Phường / xã</option>
                            <option>Xã Mỹ Thanh</option>
                            <!-- ...other wards... -->
                        </select>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="/pages/cart.php" class="text-primary text-decoration-none">My Cart</a>
                    <button type="submit" class="btn btn-primary px-4" style="font-size:17px;">Continue to Payment Method</button>
                </div>
            </form>
        </div>
        <!-- Right: Order Summary -->
        <div class="col-md-5 p-5 border-start" style="background:#fafbfc;min-height:100vh;position:relative;">
            <div style="position:sticky;top:0;">
                <div id="checkoutCartList" style="max-height:270px;overflow-y:auto;scrollbar-width:thin;">
                <?php if ($cart): ?>
                    <?php foreach ($cart as $item):
                        $pid = $item['product_id'];
                        $product = $product_map[$pid];
                    ?>
                    <div class="d-flex align-items-center mb-3 checkout-cart-item" 
                        data-pid="<?php echo $pid; ?>" 
                        data-color="<?php echo htmlspecialchars($item['color']); ?>" 
                        data-size="<?php echo htmlspecialchars($item['size']); ?>"
                        style="cursor:pointer; padding: 10px 0 10px 0; min-height: 70px;"
                        onclick="if(event.target.closest('.btn-remove-checkout-item')) return; window.open('/pages/detail_products.php?id=<?php echo $pid; ?>', '_blank');">
                        <div class="position-relative me-3" style="margin-top: 6px;">
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" style="width:54px;height:54px;object-fit:cover;border-radius:8px;border:1px solid #eee;">
                            <?php if ($item['quantity'] > 1): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary" style="font-size:13px;"><?php echo $item['quantity']; ?></span>
                            <?php endif; ?>
                        </div>
                        <div style="flex:1; margin-top: 6px;">
                            <div style="font-weight:600;"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="text-muted small"><?php echo htmlspecialchars($item['color']); ?> / <?php echo htmlspecialchars($item['size']); ?></div>
                        </div>
                        <div style="font-weight:600; margin-top: 6px;"><?php echo number_format($product['price'] * $item['quantity'], 0, ',', '.'); ?>₫</div>
                        <button type="button" class="btn btn-link text-danger ms-2 btn-remove-checkout-item" 
                            data-pid="<?php echo $pid; ?>" 
                            data-color="<?php echo htmlspecialchars($item['color']); ?>" 
                            data-size="<?php echo htmlspecialchars($item['size']); ?>"
                            style="font-size:18px;"
                            onclick="event.stopPropagation();"><i class="fa fa-trash"></i></button>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                </div>
                <hr>
                <div class="mb-3">
                    <div class="input-group">
                        <input type="text" class="form-control" id="discountCodeInput" placeholder="Discount Code" style="font-size:15px;">
                        <button class="btn btn-dark border" type="button" id="applyDiscountBtn">Apply</button>
                    </div>
                    <div id="discountCodeMsg" class="mt-1" style="font-size:14px;"></div>
                </div>
                <div class="d-flex justify-content-between mb-2" style="font-size:15px;">
                    <span>Subtotal</span>
                    <span id="checkoutSubtotal"><?php echo number_format($subtotal, 0, ',', '.'); ?>₫</span>
                </div>
                <div id="discountRow" class="d-flex justify-content-between mb-2" style="font-size:15px;display:none;">
                    <span id="discountLabel"></span>
                    <span id="discountValue" style="color:#e74c3c;"></span>
                </div>
                <div class="d-flex justify-content-between mb-2" style="font-size:15px;">
                    <span>Shipping Free</span>
                    <span id="checkoutShipping">35,000₫</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center" style="font-size:18px;font-weight:700;">
                    <span>Total Amount</span>
                    <span><span style="font-size:13px;font-weight:400;color:#888;">VND</span> <span id="checkoutTotal"><?php echo number_format($total, 0, ',', '.'); ?></span>₫</span>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
