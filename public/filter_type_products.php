<?php
session_start();
include '../includes/database.php';

// Check if user is logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Get user's favorites
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

// Get filter parameters
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$price_range = isset($_GET['price']) ? $_GET['price'] : 'all';
$color = isset($_GET['color']) ? $_GET['color'] : 'all';
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$selected_category = isset($_GET['category']) ? (int)$_GET['category'] : 0;

$items_per_page = 12;
$offset = ($current_page - 1) * $items_per_page;

// Lấy danh sách category_id có parent_id = 1 từ database
$type_category_ids = [];
$type_cat_result = $conn->query("SELECT category_id FROM categories WHERE parent_id = 1");
if ($type_cat_result) {
    while ($cat = $type_cat_result->fetch_assoc()) {
        $type_category_ids[] = (int)$cat['category_id'];
    }
}

// Nếu chọn category con thuộc parent_id=1 thì chỉ lấy sản phẩm thuộc category đó, còn lại lấy tất cả sản phẩm có parent_id=1
if ($selected_category && in_array($selected_category, $type_category_ids)) {
    $count_query = "SELECT COUNT(*) as total FROM products WHERE category_id = $selected_category";
    $query = "SELECT * FROM products WHERE category_id = $selected_category";
} else {
    $count_query = "SELECT COUNT(*) as total FROM products p JOIN categories c ON p.category_id = c.category_id WHERE c.parent_id = 1";
    $query = "SELECT p.* FROM products p JOIN categories c ON p.category_id = c.category_id WHERE c.parent_id = 1";
}

$price_filter = "";
switch ($price_range) {
    case 'under1m':
        $price_filter = " AND price < 1000000";
        break;
    case '1mto2.5m':
        $price_filter = " AND price BETWEEN 1000000 AND 2500000";
        break;
    case '2.5mto5m':
        $price_filter = " AND price BETWEEN 2500000 AND 5000000";
        break;
}
$query .= $price_filter;
$count_query .= $price_filter;

if ($color != 'all') {
    $color_filter = " AND JSON_CONTAINS(color_options, '\"$color\"')";
    $query .= $color_filter;
    $count_query .= $color_filter;
}

if ($sort === 'featured') {
    $query .= " AND is_featured = 1";
    $count_query .= " AND is_featured = 1";
    $query .= " ORDER BY created_at DESC";
} elseif ($sort === 'newest') {
    $query .= " ORDER BY created_at DESC";
} elseif ($sort === 'price_asc') {
    $query .= " ORDER BY price ASC";
} elseif ($sort === 'price_desc') {
    $query .= " ORDER BY price DESC";
} else {
    $query .= " ORDER BY created_at DESC";
}

$query .= " LIMIT $items_per_page OFFSET $offset";

$count_result = $conn->query($count_query);
$total_items = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);

$result = $conn->query($query);

$category_map = [];
$cat_result = $conn->query("SELECT category_id, name FROM categories");
if ($cat_result) {
    while ($cat = $cat_result->fetch_assoc()) {
        $category_map[$cat['category_id']] = $cat['name'];
    }
}
?>


<div class="row gy-3 gx-3">
    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $colors = json_decode($row['color_options'], true);
    ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="product-card">
                    <button class="favorite-btn" onclick="handleFavorite(event, <?php echo $row['product_id']; ?>)">
                        <i class="fa<?php echo in_array($row['product_id'], $favorites) ? 's' : 'r'; ?> fa-heart"></i>
                    </button>

                    <div class="color-options">
                        <?php foreach ($colors as $color): ?>
                            <div class="color-dot" style="background-color: <?php echo strtolower($color); ?>" title="<?php echo $color; ?>"></div>
                        <?php endforeach; ?>
                    </div>

                    <a href="detail_products.php?id=<?php echo $row['product_id']; ?>" class="product-link">
                        <div class="product-image-container">
                            <img src="<?php echo $row['image_url']; ?>" class="product-image" alt="<?php echo $row['name']; ?>">
                        </div>
                        <div class="product-info">
                            <div class="text-secondary small mb-1">
                                <?php if (isset($category_map[$row['category_id']])): ?>
                                    <span class="badge" style="color: #8c7e71; background-color: #FAF9F6; margin-right: 20px; font-size: 0.8rem;"><?php echo htmlspecialchars($category_map[$row['category_id']]); ?></span>
                                <?php endif; ?>
                            </div>
                            <h3 class="product-title mb-1"><?php echo $row['name']; ?></h3>
                            <div class="price-container">
                                <span class="current-price"><?php echo number_format($row['price']); ?>₫</span>
                                <?php if ($row['original_price'] > $row['price']): ?>
                                    <span class="original-price"><?php echo number_format($row['original_price']); ?>₫</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
    <?php
        }
    } else {
        echo '<div class="col-12 text-center"><p>Không tìm thấy sản phẩm nào.</p></div>';
    }
    ?>
</div>


<?php if ($total_pages > 1): ?>
    <div class="row mt-3">
        <div class="col-12">
            <nav aria-label="Product pagination" class="mb-0">
                <ul class="pagination justify-content-center">
                    <?php if ($current_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $current_page - 1; ?>&category=<?php echo $selected_category; ?>&sort=<?php echo $sort; ?>&price=<?php echo $price_range; ?>&color=<?php echo $color; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, min($current_page - 2, $total_pages - 4));
                    $end_page = min($total_pages, max(5, $current_page + 2));

                    if ($start_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1&category=<?php echo $selected_category; ?>&sort=<?php echo $sort; ?>&price=<?php echo $price_range; ?>&color=<?php echo $color; ?>">1</a>
                        </li>
                        <?php if ($start_page > 2): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif;
                    endif;

                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&category=<?php echo $selected_category; ?>&sort=<?php echo $sort; ?>&price=<?php echo $price_range; ?>&color=<?php echo $color; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor;

                    if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $total_pages; ?>&category=<?php echo $selected_category; ?>&sort=<?php echo $sort; ?>&price=<?php echo $price_range; ?>&color=<?php echo $color; ?>"><?php echo $total_pages; ?></a>
                        </li>
                    <?php endif; ?>

                    <?php if ($current_page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $current_page + 1; ?>&category=<?php echo $selected_category; ?>&sort=<?php echo $sort; ?>&price=<?php echo $price_range; ?>&color=<?php echo $color; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </div>
<?php endif; ?>