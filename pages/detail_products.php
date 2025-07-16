<?php
session_start();
include '../includes/database.php';
include '../includes/header.php';

// Get product id
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($product_id <= 0) {
    echo '<div class="container py-5 text-center">Sản phẩm không tồn tại.</div>';
    include '../includes/footer.php';
    exit;
}

// Get user info
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Get product info
$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
if (!$result || $result->num_rows == 0) {
    echo '<div class="container py-5 text-center">Sản phẩm không tồn tại.</div>';
    include '../includes/footer.php';
    exit;
}
$product = $result->fetch_assoc();

// Parse images, colors, sizes
$gallery = array();
if (!empty($product['image_urls'])) {
    $gallery = json_decode($product['image_urls'], true);
}
if (!empty($product['image_url'])) {
    array_unshift($gallery, $product['image_url']);
}
$gallery = array_values(array_filter($gallery)); // loại bỏ ảnh rỗng/null
$gallery = array_slice($gallery, 0, 4); // lấy tối đa 4 ảnh

$color_options = json_decode($product['color_options'], true);
$size_stock = json_decode($product['size_stock'], true);

// Get user's favorites
$is_favorite = false;
if ($user_id) {
    $fav_stmt = $conn->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND product_id = ?");
    $fav_stmt->bind_param("ii", $user_id, $product_id);
    $fav_stmt->execute();
    $fav_stmt->store_result();
    $is_favorite = $fav_stmt->num_rows > 0;
}

// Get all sizes (flattened)
$all_sizes = array();
foreach ($size_stock as $color => $sizes) {
    foreach ($sizes as $size => $qty) {
        if (!in_array($size, $all_sizes)) $all_sizes[] = $size;
    }
}
sort($all_sizes);

// Xác định loại sản phẩm dựa vào parent_id
$product_type = 'shoes'; // mặc định
$category_id = $product['category_id'] ?? 0;
$parent_id = 0;
if ($category_id) {
    $stmt = $conn->prepare("SELECT parent_id FROM categories WHERE category_id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $stmt->bind_result($parent_id);
    $stmt->fetch();
    $stmt->close();
    if ($parent_id == 2) $product_type = 'bag';
    else if ($parent_id == 3) $product_type = 'belt';
    else $product_type = 'shoes';
}

// Lấy các mã giảm giá Fundiin còn hạn sử dụng
$fundiin_codes = [];
$now = date('Y-m-d H:i:s');
$sql = "SELECT code, discount_type, discount_value, min_order_amount, valid_from, valid_until FROM discount_codes WHERE valid_from <= ? AND valid_until >= ? ORDER BY discount_value DESC, code ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $now, $now);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $fundiin_codes[] = $row;
}

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?></title>
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/detail_product.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- CSS cho modal phóng to ảnh -->
    <style>
        .image-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
        }

        .image-modal-content {
            position: relative;
            margin: auto;
            display: block;
            max-width: 90%;
            max-height: 90%;
            top: 50%;
            transform: translateY(-50%);
            border-radius: 10px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            animation: zoomIn 0.3s ease-out;
        }

        @keyframes zoomIn {
            from {
                transform: translateY(-50%) scale(0.8);
                opacity: 0;
            }

            to {
                transform: translateY(-50%) scale(1);
                opacity: 1;
            }
        }

        .image-modal-close {
            display: none;
        }

        .gallery-image {
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .gallery-image:hover {
            transform: scale(1.02);
        }
    </style>
</head>

<body style="margin-top: 120px;">
    <!-- Modal phóng to ảnh -->
    <div id="imageModal" class="image-modal">
        <span class="image-modal-close">&times;</span>
        <img class="image-modal-content" id="modalImage" alt="Enlarged product image">
    </div>

    <div class="container detail-product-container">
        <div class="row">
            <!-- Gallery -->
            <div class="col-md-7 gallery-col">
                <div class="gallery-main product-gallery-grid" style="padding:0; border-radius:18px; overflow:hidden; box-shadow:0 2px 16px rgba(0,0,0,0.06);">
                    <div class="gallery-grid gallery-grid-large">
                        <?php for ($i = 0; $i < 4; $i++): ?>
                            <div class="gallery-grid-item gallery-grid-item-large">
                                <?php if (!empty($gallery[$i])): ?>
                                    <img src="<?php echo htmlspecialchars($gallery[$i]); ?>" alt="Product image <?php echo $i + 1; ?>" class="gallery-main-img gallery-main-img-large gallery-image">
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
                <!-- Size guide link and popup -->
                <div style="margin-top:12px;">
                    <a href="#" id="sizeGuideToggle" style="color:#8c7e71; font-weight:600; text-decoration:underline; cursor:pointer;">
                        <?php if ($product_type === 'shoes'): ?>Not sure about your size? Click here<?php elseif ($product_type === 'bag'): ?>Not sure about bag size? Click here<?php elseif ($product_type === 'belt'): ?>Not sure about belt size? Click here<?php endif; ?>
                    </a>
                    <div id="sizeGuideBox" style="display:none; background:#fff; border-radius:10px; box-shadow:0 2px 12px rgba(0,0,0,0.08); padding:18px; margin-top:10px;">
                        <?php if ($product_type === 'shoes'): ?>
                            <h5 style="font-weight:700; font-size:18px; margin-bottom:12px;">Shoe Size Guide</h5>
                            <table class="table table-bordered table-sm" style="background:#fafbfc;">
                                <thead>
                                    <tr>
                                        <th>Foot Length (cm)</th>
                                        <th>US Size</th>
                                        <th>EU Size</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>23.5</td>
                                        <td>5.5</td>
                                        <td>37</td>
                                    </tr>
                                    <tr>
                                        <td>24.1</td>
                                        <td>6.5</td>
                                        <td>38</td>
                                    </tr>
                                    <tr>
                                        <td>24.8</td>
                                        <td>7.5</td>
                                        <td>39</td>
                                    </tr>
                                    <tr>
                                        <td>25.4</td>
                                        <td>8.5</td>
                                        <td>40</td>
                                    </tr>
                                    <tr>
                                        <td>26.0</td>
                                        <td>9.5</td>
                                        <td>41</td>
                                    </tr>
                                    <tr>
                                        <td>26.7</td>
                                        <td>10.5</td>
                                        <td>42</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div style="font-size:15px; color:#555;">* Measure your foot length and compare with the table above to choose the right size.</div>
                        <?php elseif ($product_type === 'bag'): ?>
                            <h5 style="font-weight:700; font-size:18px; margin-bottom:12px;">Bag Size Guide</h5>
                            <table class="table table-bordered table-sm" style="background:#fafbfc;">
                                <thead>
                                    <tr>
                                        <th>Size Label</th>
                                        <th>Approx. Width (cm)</th>
                                        <th>Typical Use</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Small</td>
                                        <td>~25</td>
                                        <td>Phone, cards, keys</td>
                                    </tr>
                                    <tr>
                                        <td>Medium</td>
                                        <td>~27</td>
                                        <td>Wallet, phone, makeup</td>
                                    </tr>
                                    <tr>
                                        <td>Large</td>
                                        <td>~29</td>
                                        <td>Tablet, notebook, daily essentials</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div style="font-size:15px; color:#555;">
                                * Check product dimensions and model photos for scale reference.
                            </div>
                        <?php elseif ($product_type === 'belt'): ?>
                            <h5 style="font-weight:700; font-size:18px; margin-bottom:12px;">Belt Size Guide</h5>
                            <table class="table table-bordered table-sm" style="background:#fafbfc;">
                                <thead>
                                    <tr>
                                        <th>Size</th>
                                        <th>Waist (cm)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>M</td>
                                        <td>~75–85 cm</td>
                                    </tr>
                                    <tr>
                                        <td>L</td>
                                        <td>~85–95 cm</td>
                                    </tr>
                                    <tr>
                                        <td>XL</td>
                                        <td>~95–105 cm</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div style="font-size:15px; color:#555;">
                                * Choose a belt size 10–15 cm longer than your waist measurement.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- Product Info -->
            <div class="col-md-5 info-col">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h2 class="product-title mb-0" style="font-size:2rem;line-height:1.2;"><?php echo htmlspecialchars($product['name']); ?></h2>
                    <button type="button" class="favorite-btn ms-2" id="favoriteBtn" style="margin-bottom:0;">
                        <i class="fa<?php echo $is_favorite ? 's' : 'r'; ?> fa-heart"></i>
                    </button>
                </div>
                <div class="product-rating mb-2" style="margin-top:2px;">
                    <?php
                    // Tính trung bình rating và tổng số đánh giá
                    $stmt = $conn->prepare("SELECT COUNT(*), AVG(rating) FROM product_reviews WHERE product_id = ?");
                    $stmt->bind_param("i", $product_id);
                    $stmt->execute();
                    $stmt->bind_result($review_count, $avg_rating);
                    $stmt->fetch();
                    $stmt->close();
                    $avg_rating = $avg_rating ? round($avg_rating, 1) : 0;
                    ?>
                    <span style="color:#FFD600;font-size:20px;">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?php if ($i <= floor($avg_rating)): ?>
                                <i class="fas fa-star"></i>
                            <?php elseif ($i - $avg_rating < 1): ?>
                                <i class="fas fa-star-half-alt"></i>
                            <?php else: ?>
                                <i class="far fa-star"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </span>
                    <span style="font-size:15px;color:#222;vertical-align:middle;">(<?php echo $review_count; ?> Review )</span>
                </div>
                <div class="product-sku" style="margin-top:2px;">Product ID: <?php echo htmlspecialchars($product['product_id']); ?></div>
                <div class="product-sold" style="margin-bottom:8px;">Sold: <?php echo (int)$product['sold_quantity']; ?></div>
                <div class="product-price" style="margin: 10px 0 16px 0; font-size:1.7rem;">
                    <span class="price"><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</span>
                    <?php if ($product['discount_percent'] > 0): ?>
                        <span class="original-price"><?php echo number_format($product['original_price'], 0, ',', '.'); ?>đ</span>
                        <span class="discount">-<?php echo (int)$product['discount_percent']; ?>%</span>
                    <?php endif; ?>
                </div>

                <div style="background: linear-gradient(to right, #4B2EFF , #00D1C1); padding: 6px 14px; border-radius: 8px; display: flex; align-items: center; color: white; font-family: Arial, sans-serif; font-size: 17px;">
                    <img src="/assets/images/discount.png" alt="Discount" style="height:65px; margin-right: 10px;">
                    <span>
                        Get <strong>10% off</strong> instantly when you pay with Fundiin. <a href="#" id="fundiinLearnMore" style="color: #d0faff; text-decoration: underline;">Learn more</a>
                    </span>
                </div>


                <form id="addToCartForm" method="post" action="" style="margin-bottom:18px;">
                    <!-- Color row with stock info at right -->
                    <div class="color-size-row" style="display: flex; align-items: flex-end; gap: 14px; justify-content: space-between;">
                        <div style="display: flex; align-items: flex-end; gap: 14px;">
                            <label class="color-size-label" style="margin-bottom:2px;">Color:</label>
                            <div class="color-options">
                                <?php foreach ($color_options as $i => $color): ?>
                                    <span class="color-dot" data-color="<?php echo htmlspecialchars($color); ?>" style="background-color:<?php echo strtolower($color); ?>;" title="<?php echo htmlspecialchars($color); ?>"></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <span id="stockLeft" class="stock-info" style="margin-bottom:0; margin-left:auto; color:#8c7e71;"></span>
                    </div>
                    <div style="border-bottom:1px solid #eee; margin-bottom:10px;"></div>
                    <!-- Size row -->
                    <div class="color-size-row" style="display: flex; align-items: center; gap: 14px; margin-top: 0px;">
                        <label class="color-size-label">Size:</label>
                        <div class="size-options" id="sizeOptions">
                            <!-- Render size buttons with stock info for selected color -->
                        </div>
                    </div>
                    <div style="border-bottom:1px solid #eee; margin-bottom:10px;"></div>
                    <!-- Quantity row -->
                    <div class="quantity-row">
                        <label class="quantity-label">Quantity:</label>
                        <input type="number" id="quantityInput" name="quantity" value="1" min="1" max="1" class="quantity-input">
                    </div>
                    <!-- Buy button on top -->
                    <button type="submit" class="buy-btn" style="margin-bottom:10px;padding:10px 0;font-size:15px;">BUY NOW</button>
                    <!-- Add to cart & Share on one row below -->
                    <div class="action-row" style="margin-top:0;margin-bottom:16px;gap:10px;">
                        <button type="button" class="add-cart-btn" style="font-size:14px;padding:10px 0;">ADD TO CART</button>
                        <button type="button" class="share-btn" style="font-size:14px;padding:10px 0;"><i class="fas fa-share-alt"></i> SHARE</button>
                    </div>
                </form>
                <ul class="product-benefits" style="font-size:16px;margin:14px 0 8px 0;">
                    <li>Free nationwide shipping on orders over 1,000,000₫</li>
                    <li>Check and pay upon delivery (COD available)</li>
                    <li>Exchange within 15 days</li>
                </ul>
                <div class="product-tabs" style="margin-top:18px;">
                    <button class="tab-btn active" data-tab="info">Product Information</button>
                    <button class="tab-btn" data-tab="warranty">Warranty & Return Policy</button>
                </div>
                <div class="tab-content" id="tab-info">
                    <ul>
                        <li><b>Product ID:</b> <?php echo htmlspecialchars($product['product_id']); ?></li>
                        <li><b>Style:</b> <?php echo htmlspecialchars($product['name']); ?></li>
                        <li><b>Material:</b> <?php echo htmlspecialchars($product['material']); ?></li>
                        <li><b>Color:</b> <?php echo implode(', ', $color_options); ?></li>
                        <li><b>Size:</b> <?php echo implode('-', $all_sizes); ?></li>
                    </ul>
                </div>
                <div class="tab-content" id="tab-warranty" style="display:none;">
                    <ul>
                        <li>12-month warranty for manufacturing defects</li>
                        <li>Returns accepted within 15 days if the product is unused</li>
                        <li>No returns or exchanges for used items</li>
                        <li>Free repair service during the warranty period</li>
                        <li>Proof of purchase is required for returns</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Truyền danh sách mã Fundiin sang JS
        window.fundiinCodes = <?php echo json_encode($fundiin_codes); ?>;
    </script>
    <script src="/assets/js/detail_product.js"></script>
    <script>
        // --- Image Modal Logic ---
        const imageModal = document.getElementById('imageModal');
        const modalImage = document.getElementById('modalImage');
        const closeModal = document.querySelector('.image-modal-close');

        // Thêm sự kiện click cho tất cả ảnh trong gallery
        function addImageClickEvents() {
            document.querySelectorAll('.gallery-image, .gallery-thumb, .product-image').forEach(function(img) {
                img.style.cursor = 'pointer';
                img.classList.add('gallery-image');
                img.addEventListener('click', function(e) {
                    e.preventDefault();
                    imageModal.style.display = 'block';
                    modalImage.src = this.src;
                    document.body.style.overflow = 'hidden'; // Ngăn scroll trang
                });
            });
        }

        // Đóng modal khi click vào dấu X
        closeModal.addEventListener('click', function() {
            imageModal.style.display = 'none';
            document.body.style.overflow = 'auto'; // Cho phép scroll lại
        });

        // Đóng modal khi click bên ngoài ảnh
        imageModal.addEventListener('click', function(e) {
            if (e.target === imageModal) {
                imageModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });

        // Đóng modal bằng phím ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && imageModal.style.display === 'block') {
                imageModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });

        // --- Gallery logic ---
        document.querySelectorAll('.gallery-thumb').forEach(function(thumb, idx) {
            thumb.addEventListener('click', function() {
                document.getElementById('mainImage').src = this.src;
                document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Khởi tạo sự kiện click cho ảnh khi trang load
        document.addEventListener('DOMContentLoaded', function() {
            addImageClickEvents();
        });

        // --- Color/Size/Stock logic ---
        const sizeStock = <?php echo json_encode($size_stock); ?>;
        const colorOptions = <?php echo json_encode($color_options); ?>;
        const allSizes = <?php echo json_encode($all_sizes); ?>;
        let selectedColor = colorOptions[0];
        let selectedSize = null;

        function renderSizes() {
            const sizeOptionsDiv = document.getElementById('sizeOptions');
            sizeOptionsDiv.innerHTML = '';
            // Lấy danh sách size thực tế theo màu đang chọn
            let sizes = [];
            if (sizeStock[selectedColor]) {
                sizes = Object.keys(sizeStock[selectedColor]);
            }
            sizes.sort((a, b) => a - b); // Sắp xếp tăng dần
            sizes.forEach(size => {
                let qty = 0;
                if (sizeStock[selectedColor] && sizeStock[selectedColor][size] !== undefined) {
                    qty = sizeStock[selectedColor][size];
                }
                if (qty > 0) {
                    sizeOptionsDiv.innerHTML += `<span class="size-btn" data-size="${size}">${size}</span>`;
                } else {
                    sizeOptionsDiv.innerHTML += `<span class="size-btn disabled" data-size="${size}">${size}</span>`;
                }
            });
            // Add event listeners
            sizeOptionsDiv.querySelectorAll('.size-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (this.classList.contains('disabled')) {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'info',
                            title: undefined,
                            html: `<div style="display:flex;align-items:center;;min-width:180px;">
                                     
                                      <span style='font-size:18px;font-weight:600;color:#fff;'>Out of stock with this size!</span>
                                   </div>`,
                            showConfirmButton: false,
                            timer: 1800,
                            background: '#222',
                            color: '#fff',
                            customClass: {
                                popup: 'swal2-toast-custom'
                            },
                            width: 320,
                            padding: '10px 0 10px 0'
                        });
                        return;
                    }
                    selectSize(this.getAttribute('data-size'));
                });
            });
        }

        function updateSizes() {
            renderSizes();
            // Auto-select first available size
            let firstAvailable = null;
            let sizes = [];
            if (sizeStock[selectedColor]) {
                sizes = Object.keys(sizeStock[selectedColor]);
                sizes.sort((a, b) => a - b);
                for (let size of sizes) {
                    if (sizeStock[selectedColor][size] && sizeStock[selectedColor][size] > 0) {
                        firstAvailable = size;
                        break;
                    }
                }
            }
            if (firstAvailable) {
                selectSize(firstAvailable);
            } else {
                selectedSize = null;
                document.getElementById('stockLeft').textContent = 'Hết hàng';
                document.getElementById('quantityInput').max = 1;
                document.getElementById('quantityInput').value = 1;
            }
        }

        function selectSize(size) {
            selectedSize = size;
            document.querySelectorAll('.size-btn').forEach(btn => {
                btn.classList.toggle('active', btn.getAttribute('data-size') == size);
            });
            // Update stock info
            const stock = sizeStock[selectedColor][size] || 0;
            document.getElementById('stockLeft').textContent = 'Only ' + stock + ' items left in stock!';
            document.getElementById('stockLeft').style.color = '#8c7e71';
            document.getElementById('quantityInput').max = stock;
            if (parseInt(document.getElementById('quantityInput').value) > stock) {
                document.getElementById('quantityInput').value = stock;
            }
        }
        // Color select
        function selectColor(color) {
            selectedColor = color;
            document.querySelectorAll('.color-dot').forEach(dot => {
                dot.classList.toggle('active', dot.getAttribute('data-color') == color);
            });
            updateSizes();
        }
        document.querySelectorAll('.color-dot').forEach(dot => {
            dot.addEventListener('click', function() {
                selectColor(this.getAttribute('data-color'));
            });
        });
        // Init
        selectColor(selectedColor);

        // Quantity input
        const qtyInput = document.getElementById('quantityInput');
        qtyInput.addEventListener('input', function() {
            let max = parseInt(qtyInput.max);
            let val = parseInt(qtyInput.value);
            if (val > max) qtyInput.value = max;
            if (val < 1 || isNaN(val)) qtyInput.value = 1;
        });

        // --- Favorite button ---
        document.getElementById('favoriteBtn').addEventListener('click', function(e) {
            if (!<?php echo json_encode((bool)$user_id); ?>) {
                const img = '<?php echo htmlspecialchars($product["image_url"] ?? "/assets/img/default.jpg"); ?>';
                const name = '<?php echo htmlspecialchars($product["name"] ?? "Sản phẩm"); ?>';
                const price = '<?php echo htmlspecialchars($product["price"] ?? "0₫"); ?>';

                Swal.fire({
                    html: `
        <div style='background:#fff;border-radius:18px;box-shadow:0 4px 32px rgba(44,62,80,0.10);padding:24px 18px 18px 18px;max-width:350px;margin:0 auto;'>
            <div style='font-size:20px;font-weight:800;color:#222;text-align:center;margin-bottom:16px;letter-spacing:0.5px;'>Please log in to continue</div>
            <div style='display:flex;align-items:center;gap:18px;margin-bottom:18px;'>
                <img src='${img}' style='width:90px;height:90px;object-fit:cover;border-radius:16px;border:2px solid #eee;box-shadow:0 2px 12px rgba(44,62,80,0.10);background:#fafafa;'>
                <div style='text-align:left;max-width:200px;'>
                    <div style='font-size:19px;font-weight:800;margin-bottom:4px;line-height:1.2;word-break:break-word;color:#222;'>${name}</div>
                    <div style='color:#e74c3c;font-weight:800;font-size:18px;margin-bottom:2px;'>${price}</div>
                </div>
            </div>
            <div style='font-size:15px;color:#444;margin-bottom:18px;text-align:left;'>You need to log in to add products to your favorites list.</div>
            <div style='display:flex;gap:12px;'>
                <button id='loginFavBtn' style='flex:1;background:#6e5f51;color:#fff;font-weight:700;font-size:16px;padding:10px 0;border:none;border-radius:8px;box-shadow:0 2px 8px rgba(44,62,80,0.08);cursor:pointer;'>Log In</button>
                <button id='cancelFavBtn' style='flex:1;background:#f3f3f3;color:#444;font-weight:600;font-size:16px;padding:10px 0;border:none;border-radius:8px;cursor:pointer;'>Later</button>
            </div>
        </div>
        `,
                    showConfirmButton: false,
                    showCloseButton: false,
                    allowOutsideClick: false,
                    background: 'transparent',
                    didOpen: () => {
                        document.getElementById('loginFavBtn').onclick = function() {
                            window.location.href = '/pages/login.php?pending_favorite=<?php echo $product_id; ?>';
                        };
                        document.getElementById('cancelFavBtn').onclick = function() {
                            Swal.close();
                        };
                    }
                });
                return;
            }


            // AJAX toggle favorite
            fetch('/pages/new_products.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'toggle_favorite=1&product_id=<?php echo $product_id; ?>'
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    const icon = document.querySelector('#favoriteBtn i');
                    icon.classList.toggle('fas');
                    icon.classList.toggle('far');
                    // Cập nhật badge số lượng yêu thích trên header
                    fetch('/public/get_favorite_count.php')
                        .then(r => r.json())
                        .then(data => {
                            if (data.success && typeof updateFavoriteBadge === 'function') updateFavoriteBadge(data.count);
                        });
                }
            });
        });

        function showCartToast(type, product) {
            if (type === 'add') {
                Swal.fire({
                    html: `
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 5px;">
                        <div style="font-size: 20px; font-weight: 700;">Successfully added to cart!</div>
                        <ion-icon name="checkmark-circle-outline" style="font-size: 30px; color: black;margin-left: 160px;"></ion-icon>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <img src="${product.image_url}" style="width:100px;height:100px;object-fit:cover;border-radius:6px;border:1px solid #eee;">
                        <div style="text-align:left;">
                            <div style="font-size:16px;font-weight:600;margin-bottom:2px;">${product.name}</div>
                            <div style="font-size:16px;font-weight:600;margin-bottom:2px;">Quantity: ${product.quantity}</div>
                            <div style="color:#e74c3c;font-weight:700;font-size:18px;">${product.price.toLocaleString('vi-VN')}₫</div>
                        </div>
                    </div>
        
                `,
                    showConfirmButton: false,
                    timer: 1800,
                    width: 500,
                    customClass: {
                        popup: 'swal2-toast-custom'
                    }
                });
            } else {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'info',
                    title: 'Đã xóa khỏi giỏ hàng!',
                    showConfirmButton: false,
                    timer: 1500,
                    timerProgressBar: true,
                    width: 320,
                    customClass: {
                        popup: 'swal2-toast-custom'
                    }
                });
            }
        }

        // --- Add to cart / Buy now ---
        document.querySelector('.add-cart-btn').addEventListener('click', function(e) {
            e.preventDefault();
            addToCart('cart');
        });

        // Thêm xử lý cho nút BUY NOW
        document.querySelector('.buy-btn').addEventListener('click', function(e) {
            e.preventDefault();
            buyNow();
        });

        function buyNow() {
            if (!selectedColor || !selectedSize) {
                Swal.fire('Please select color and size!');
                return;
            }
            const qty = parseInt(qtyInput.value);
            const max = parseInt(qtyInput.max);
            if (qty < 1 || qty > max) {
                Swal.fire('Invalid quantity!');
                return;
            }
            // AJAX add to cart
            fetch('/public/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `add_to_cart=1&product_id=<?php echo $product_id; ?>&color=${encodeURIComponent(selectedColor)}&size=${encodeURIComponent(selectedSize)}&quantity=${qty}`
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    // Thêm sản phẩm vào checkout_selected (không ghi đè, giữ lại các sản phẩm cũ)
                    let checkoutSelected = [];
                    try {
                        checkoutSelected = JSON.parse(localStorage.getItem('checkout_selected')) || [];
                    } catch (e) {
                        checkoutSelected = [];
                    }
                    // Kiểm tra trùng sản phẩm (pid, color, size)
                    const idx = checkoutSelected.findIndex(item =>
                        item.pid == <?php echo $product_id; ?> &&
                        item.color == selectedColor &&
                        item.size == selectedSize
                    );
                    if (idx > -1) {
                        // Nếu đã có, cộng dồn số lượng
                        checkoutSelected[idx].quantity += qty;
                    } else {
                        checkoutSelected.push({
                            pid: <?php echo $product_id; ?>,
                            color: selectedColor,
                            size: selectedSize,
                            quantity: qty
                        });
                    }
                    localStorage.setItem('checkout_selected', JSON.stringify(checkoutSelected));
                    window.location.href = '/pages/checkout.php';
                } else {
                    Swal.fire('Lỗi', data.message || 'Không thể thêm sản phẩm vào giỏ', 'error');
                }
            }).catch(() => {
                Swal.fire('Lỗi', 'Không thể kết nối máy chủ', 'error');
            });
        }

        function addToCart(action) {
            if (!selectedColor || !selectedSize) {
                Swal.fire('Please select color and size!');
                return;
            }
            const qty = parseInt(qtyInput.value);
            const max = parseInt(qtyInput.max);
            if (qty < 1 || qty > max) {
                Swal.fire('Invalid quantity!');
                return;
            }
            // AJAX add to cart (new endpoint)
            fetch('/public/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `add_to_cart=1&product_id=<?php echo $product_id; ?>&color=${encodeURIComponent(selectedColor)}&size=${encodeURIComponent(selectedSize)}&quantity=${qty}`
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    showCartToast('add', {
                        image_url: <?php echo json_encode($product['image_url']); ?>,
                        name: <?php echo json_encode($product['name']); ?>,
                        price: <?php echo (int)$product['price']; ?>,
                        quantity: qty
                    });
                    fetch('/public/get_cart_count.php')
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) {
                                document.dispatchEvent(new CustomEvent('addToCartSuccess', {
                                    detail: {
                                        count: res.count
                                    }
                                }));
                            }
                        });
                    // Nếu đã đạt tối đa, thông báo nhỏ màu đen
                    if (data.maxed) {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'info',
                            title: data.message || 'This is the maximum quantity available in stock.',
                            showConfirmButton: false,
                            timer: 2200,
                            background: '#222',
                            color: '#fff',
                            customClass: {
                                popup: 'swal2-toast-custom'
                            }
                        });
                    }
                } else if (data.maxed) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'info',
                        title: data.message || 'This is the maximum quantity available in stock.',
                        showConfirmButton: false,
                        timer: 2200,
                        background: '#222',
                        color: '#fff',
                        customClass: {
                            popup: 'swal2-toast-custom'
                        }
                    });
                } else {
                    Swal.fire('Lỗi', data.message || 'Không thể thêm vào giỏ hàng', 'error');
                }
            }).catch(() => {
                Swal.fire('Lỗi', 'Không thể kết nối máy chủ', 'error');
            });
        }
    </script>
    <!-- Bootstrap 5 JS Bundle (with Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include '../includes/truck.php'; ?>
    <?php include '../includes/footer.php'; ?>
    <?php include '../includes/floating_contact.php'; ?>
    <script src="/assets/js/auto_logout.js"></script>
</body>

</html>