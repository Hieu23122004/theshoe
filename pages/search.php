<?php
session_start();
include '../includes/database.php';
include '../includes/header.php';

$search_results = [];
$search_keyword = '';
if (isset($_GET['q']) && trim($_GET['q']) !== '') {
    $search_keyword = trim($_GET['q']);
    $like = '%' . $search_keyword . '%';
    $stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE ? ORDER BY name");
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $search_results[] = $row;
    }
    $stmt->close();
}
// Lấy danh sách loại sản phẩm (categories)
$category_map = [];
$cat_result = $conn->query("SELECT category_id, name FROM categories");
if ($cat_result) {
    while ($cat = $cat_result->fetch_assoc()) {
        $category_map[$cat['category_id']] = $cat['name'];
    }
}
// Lấy danh sách sản phẩm yêu thích nếu đã đăng nhập
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$favorites = array();
if ($user_id) {
    $fav_query = "SELECT product_id FROM favorites WHERE user_id = ?";
    $stmt = $conn->prepare($fav_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $fav_result = $stmt->get_result();
    while ($fav = $fav_result->fetch_assoc()) {
        $favorites[] = $fav['product_id'];
    }
}
?>
<!-- Bootstrap 5 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<style>
    h5.text-primary span {
    background-color: #f0f0f0;
    padding: 2px 8px;
    border-radius: 6px;
}

</style>
<link rel="stylesheet" href="/assets/css/products.css">
<div class="container products-container py-4" style="margin-top:110px !important;">
    <form method="GET" action="" class="mb-3 position-relative d-flex" autocomplete="off" style="gap:8px;">
        <input type="text" class="form-control" id="searchInput" name="q" placeholder="Search products by name..." value="<?= htmlspecialchars($search_keyword) ?>" autocomplete="off">
        <button type="submit" class="btn btn-dark" style="min-width:48px;">
            <i class="fa fa-search"></i>
        </button>
        <div id="suggestions" class="list-group position-absolute w-100" style="z-index:1000;top:100%;"></div>
    </form>
    <?php if ($search_keyword !== ''): ?>
      <h5 class="text-dark mt-3">
    Search results for: 
    <span class=" text-dark">"<?= htmlspecialchars($search_keyword) ?>"</span>
</h5>

        <div class="row gy-3 gx-3">
        <?php if (count($search_results) > 0): ?>
            <?php foreach ($search_results as $row): ?>
                <?php $colors = json_decode($row['color_options'], true); ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="product-card">
                        <button class="favorite-btn" onclick="handleFavorite(event, <?= $row['product_id']; ?>)">
                            <i class="fa<?= in_array($row['product_id'], $favorites) ? 's' : 'r'; ?> fa-heart"></i>
                        </button>
                        <div class="color-options">
                            <?php if (is_array($colors)) foreach ($colors as $color): ?>
                                <div class="color-dot" style="background-color: <?= strtolower($color); ?>" title="<?= $color; ?>"></div>
                            <?php endforeach; ?>
                        </div>
                        <a href="detail_products.php?id=<?= $row['product_id']; ?>" class="product-link">
                            <div class="product-image-container" style="position:relative;">
                                <img src="<?= $row['image_url']; ?>" class="product-image" alt="<?= $row['name']; ?>">
                                <?php if ($row['discount_percent'] >= 20): ?>
                                    <span class="discount-badge-animated">
                                        -<?= $row['discount_percent']; ?>%
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <div class="text-secondary small mb-1">
                                    <?php if (isset($category_map[$row['category_id']])): ?>
                                        <span class="badge" style="color: #8c7e71; background-color: #FAF9F6; margin-right: 20px; font-size: 0.8rem;">
                                            <?= htmlspecialchars($category_map[$row['category_id']]); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="product-title mb-1"><?= $row['name']; ?></h3>
                                <div class="price-container">
                                    <span class="current-price"><?= number_format($row['price']); ?>₫</span>
                                    <?php if ($row['original_price'] > $row['price']): ?>
                                        <span class="original-price"><?= number_format($row['original_price']); ?>₫</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center"><p>No products found.</p></div>
        <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
<?php include '../includes/floating_contact.php'; ?>
<script src="/assets/js/auto_logout.js"></script>
<script src="/assets/js/sale_products.js"></script>
<!-- Bootstrap 5 JS Bundle (with Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Đảm bảo biến isLoggedIn có giá trị đúng
var isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

const input = document.getElementById('searchInput');
const suggestions = document.getElementById('suggestions');
let timer = null;
input.addEventListener('input', function() {
    const val = this.value.trim();
    if (val.length < 1) {
        suggestions.innerHTML = '';
        suggestions.style.display = 'none';
        return;
    }
    clearTimeout(timer);
    timer = setTimeout(() => {
        fetch('/public/search_suggest.php?q=' + encodeURIComponent(val))
            .then(res => res.json())
            .then(data => {
                if (data.success && Array.isArray(data.suggestions) && data.suggestions.length > 0) {
                    suggestions.innerHTML = data.suggestions.map(item => `
                        <button type="button" class="list-group-item list-group-item-action d-flex align-items-center" data-name="${item.name.replace(/"/g, '&quot;')}">
                            <img src="${item.image_url}" style="width:60px;height:60px;object-fit:cover;margin-right:10px;border-radius:6px;">
                            <span style="flex:1;text-align:left;">${item.name}</span>
                            ${item.discount_percent > 0 ? `<span class="badge bg-danger ms-2">-${item.discount_percent}%</span>` : ''}
                        </button>
                    `).join('');
                    suggestions.style.display = 'block';
                } else {
                    suggestions.innerHTML = '';
                    suggestions.style.display = 'none';
                }
            });
    }, 200);
});
suggestions.addEventListener('click', function(e) {
    if (e.target.closest('button[data-name]')) {
        input.value = e.target.closest('button[data-name]').getAttribute('data-name');
        suggestions.innerHTML = '';
        suggestions.style.display = 'none';
        input.form.submit();
    }
});
document.addEventListener('click', function(e) {
    if (!suggestions.contains(e.target) && e.target !== input) {
        suggestions.innerHTML = '';
        suggestions.style.display = 'none';
    }
});
</script>