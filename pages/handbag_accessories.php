<?php
session_start();
include '../includes/database.php';
// Handle favorite toggle via AJAX (đặt lên đầu, trước khi include header.php)
if (isset($_POST['toggle_favorite']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    // Lấy danh sách favorites hiện tại
    $favorites = array();
    $fav_query = "SELECT product_id FROM favorites WHERE user_id = ?";
    $stmt = $conn->prepare($fav_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $fav_result = $stmt->get_result();
    while ($fav = $fav_result->fetch_assoc()) {
        $favorites[] = $fav['product_id'];
    }
    $product_id = $_POST['product_id'];
    if (in_array($product_id, $favorites)) {
        $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ?");
    } else {
        $stmt = $conn->prepare("INSERT INTO favorites (user_id, product_id) VALUES (?, ?)");
    }
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    header('Content-Type: application/json');
    exit(json_encode(['success' => true]));
}

include '../includes/header.php';

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

// Khi bấm yêu thích mà chưa đăng nhập, lưu product_id vào session pending_favorite
if (isset($_GET['pending_favorite'])) {
    $_SESSION['pending_favorite'] = (int)$_GET['pending_favorite'];
}

// Get filter parameters
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$price_range = isset($_GET['price']) ? $_GET['price'] : 'all';
$color = isset($_GET['color']) ? $_GET['color'] : 'all';
$selected_category = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Lấy danh sách categories có parent_id = 2
$filter_categories = [];
$cat_filter_result = $conn->query("SELECT category_id, name FROM categories WHERE parent_id = 2");
if ($cat_filter_result) {
    while ($cat = $cat_filter_result->fetch_assoc()) {
        $filter_categories[] = $cat;
    }
}

// Lấy danh sách màu sắc có trong category đang chọn
$color_options = [];
if ($selected_category) {
    $color_query = "SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(color_options, CONCAT('$[0]'))) as color FROM products WHERE category_id = $selected_category";
    $color_result = $conn->query("SELECT DISTINCT color_options FROM products WHERE category_id = $selected_category");
    if ($color_result) {
        $color_set = [];
        while ($row = $color_result->fetch_assoc()) {
            $colors = json_decode($row['color_options'], true);
            if (is_array($colors)) {
                foreach ($colors as $c) {
                    $color_set[$c] = true;
                }
            }
        }
        $color_options = array_keys($color_set);
    }
}

// Pagination settings
$items_per_page = 8;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Base query for counting total products
if ($selected_category && in_array($selected_category, [9, 10, 11])) {
    // Nếu chọn 1 trong 3 loại con của Footwear thì chỉ lấy sản phẩm thuộc loại đó
    $count_query = "SELECT COUNT(*) as total FROM products WHERE category_id = $selected_category";
    $query = "SELECT * FROM products WHERE category_id = $selected_category";
} else {
    // Mặc định: lấy tất cả sản phẩm có danh mục cha là Footwear (id=1)
    $count_query = "SELECT COUNT(*) as total FROM products p JOIN categories c ON p.category_id = c.category_id WHERE c.parent_id = 2";
    $query = "SELECT p.* FROM products p JOIN categories c ON p.category_id = c.category_id WHERE c.parent_id = 2";
}

// Add price range filter to both queries
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

// Add color filter to both queries
if ($color != 'all') {
    $color_filter = " AND JSON_CONTAINS(color_options, '\"$color\"')";
    $query .= $color_filter;
    $count_query .= $color_filter;
}

// Add sorting and filtering
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

// Add pagination
$query .= " LIMIT $items_per_page OFFSET $offset";

// Get total number of products
$count_result = $conn->query($count_query);
$total_items = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);

$result = $conn->query($query);

// Lấy danh sách loại sản phẩm (categories)
$category_map = [];
$cat_result = $conn->query("SELECT category_id, name FROM categories");
if ($cat_result) {
    while ($cat = $cat_result->fetch_assoc()) {
        $category_map[$cat['category_id']] = $cat['name'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Products</title>
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-minimal@5/minimal.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="/assets/css/products.css">
    <script>
        var isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    </script>
</head>

<body>

    <!-- Banner Carousel -->
    <div id="bannerCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="https://scontent.fhan14-2.fna.fbcdn.net/v/t39.30808-6/481987176_625456420253877_7046595585156986004_n.jpg?_nc_cat=100&ccb=1-7&_nc_sid=cc71e4&_nc_ohc=ty-iVfHIFI4Q7kNvwG4GKH4&_nc_oc=AdlzUzu6eFi-15Cbbr8AXG8and4G0fAAHAul2lQz0cNTy6NqbPTfJ-029fguMfdBMwQ&_nc_zt=23&_nc_ht=scontent.fhan14-2.fna&_nc_gid=hOs3f4bnkotGGEXnPvNgYA&oh=00_AfRf8epjWcoQwoKwLbrGKfGZ3GPAut2jmSNTVcTMP0egHg&oe=68709A5B" class="d-block w-100" alt="Banner 1">
            </div>
            <div class="carousel-item">
                <img src="https://scontent.fhan14-4.fna.fbcdn.net/v/t39.30808-6/366725736_268091702670046_8048118267574591361_n.png?stp=dst-jpg_tt6&_nc_cat=103&ccb=1-7&_nc_sid=86c6b0&_nc_ohc=k_an77esuJIQ7kNvwGSw3na&_nc_oc=Adk1MffWvJG7fnEusIr9vAlrb0Ix64bBNTr_3bHHL8GRYFwqtHOQMMwHH8bzgETsGJg&_nc_zt=23&_nc_ht=scontent.fhan14-4.fna&_nc_gid=W68IJfHzlQXkm6LmJCxqHw&oh=00_AfSgH1pz9S363bgwZZktIKOvB50rtNOKXOwfRtCDiry5_w&oe=68708C60" class="d-block w-100" alt="Banner 2">
            </div>

        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <div class="container">
            <form id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <select name="category" class="form-select" onchange="filterProducts()">
                        <option value="0" <?php echo $selected_category == 0 ? 'selected' : ''; ?>>All Categories</option>
                        <?php foreach ($filter_categories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>" <?php echo $selected_category == $cat['category_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="sort" class="form-select" onchange="filterProducts()">
                        <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest</option>
                        <option value="featured" <?php echo $sort == 'featured' ? 'selected' : ''; ?>>Featured Products</option>
                        <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Low to High</option>
                        <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>High to Low</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="price" class="form-select" onchange="filterProducts()">
                        <option value="all" <?php echo $price_range == 'all' ? 'selected' : ''; ?>>All Prices</option>
                        <option value="under1m" <?php echo $price_range == 'under1m' ? 'selected' : ''; ?>>Under 1 Million</option>
                        <option value="1mto2.5m" <?php echo $price_range == '1mto2.5m' ? 'selected' : ''; ?>>1 Million - 2.5 Million</option>
                        <option value="2.5mto5m" <?php echo $price_range == '2.5mto5m' ? 'selected' : ''; ?>>2.5 Million - 5 Million</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="color" class="form-select" onchange="filterProducts()">
                        <option value="all" <?php echo $color == 'all' ? 'selected' : ''; ?>>All Color</option>
                        <?php foreach ($color_options as $c): ?>
                            <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $color == $c ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
            <div id="loading" class="text-center py-3" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Grid -->
    <div class="container products-container py-4">
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
                echo '<div class="col-12 text-center"><p>No results for your search.</p></div>';
            }
            ?>
        </div>

        <!-- Pagination with reduced spacing -->
        <?php if ($total_pages > 1): ?>
            <div class="row mt-3">
                <div class="col-12">
                    <nav aria-label="Product pagination" class="mb-0">
                        <ul class="pagination justify-content-center">
                            <?php if ($current_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $current_page - 1; ?>&sort=<?php echo $sort; ?>&price=<?php echo $price_range; ?>&color=<?php echo $color; ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            // Show max 5 pages with current page in the middle when possible
                            $start_page = max(1, min($current_page - 2, $total_pages - 4));
                            $end_page = min($total_pages, max(5, $current_page + 2));

                            if ($start_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1&sort=<?php echo $sort; ?>&price=<?php echo $price_range; ?>&color=<?php echo $color; ?>">1</a>
                                </li>
                                <?php if ($start_page > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif;
                            endif;

                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&sort=<?php echo $sort; ?>&price=<?php echo $price_range; ?>&color=<?php echo $color; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor;

                            if ($end_page < $total_pages): ?>
                                <?php if ($end_page < $total_pages - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $total_pages; ?>&sort=<?php echo $sort; ?>&price=<?php echo $price_range; ?>&color=<?php echo $color; ?>"><?php echo $total_pages; ?></a>
                                </li>
                            <?php endif; ?>

                            <?php if ($current_page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $current_page + 1; ?>&sort=<?php echo $sort; ?>&price=<?php echo $price_range; ?>&color=<?php echo $color; ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php include '../includes/truck.php'; ?>
    <?php include '../includes/footer.php'; ?>
    <?php include '../includes/floating_contact.php'; ?>
    <script src="/assets/js/auto_logout.js"></script>
    <script src="/assets/js/handbag_accessories.js"></script>
    <!-- Bootstrap 5 JS Bundle (with Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>