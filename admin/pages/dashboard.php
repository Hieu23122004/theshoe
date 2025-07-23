<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
include '../../includes/auth.php';
include '../../includes/database.php';
include '../../includes/header_ad.php';
?>

<?php
function formatCurrency($amount)
{
    return number_format($amount, 0, ',', '.') . ' VND';
}
function formatNumber($number)
{
    return number_format($number, 0, ',', '.');
}
function getStatusColor($status)
{
    switch (strtolower($status)) {
        case 'pending':
            return 'warning';
        case 'confirmed':
            return 'info';
        case 'shipped':
            return 'primary';
        case 'delivered':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}
$message = '';
$stats = [];
try {
    // Chỉ tính revenue từ đơn hàng Delivered
    $revenue_query = "SELECT COALESCE(SUM(total_amount), 0) as total_revenue FROM orders WHERE status = 'Delivered'";
    $revenue_result = $conn->query($revenue_query);
    $stats['total_revenue'] = $revenue_result->fetch_assoc()['total_revenue'];

    // Tổng số đơn hàng (giữ nguyên)
    $orders_query = "SELECT COUNT(*) as total_orders FROM orders";
    $orders_result = $conn->query($orders_query);
    $stats['total_orders'] = $orders_result->fetch_assoc()['total_orders'];

    // Số đơn hàng gần đây (30 ngày)
    $recent_orders_query = "SELECT COUNT(*) as recent_orders FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $recent_orders_result = $conn->query($recent_orders_query);
    $stats['recent_orders'] = $recent_orders_result->fetch_assoc()['recent_orders'];

    // Chỉ tính revenue tháng này từ đơn hàng Delivered
    $recent_revenue_query = "SELECT COALESCE(SUM(total_amount), 0) as recent_revenue FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND status = 'Delivered'";
    $recent_revenue_result = $conn->query($recent_revenue_query);
    $stats['recent_revenue'] = $recent_revenue_result->fetch_assoc()['recent_revenue'];

    // Tính tổng số lượng sản phẩm trong giỏ hàng của tất cả user
    $cart_items_query = "SELECT COALESCE(SUM(quantity), 0) as total_cart_items FROM cart_items";
    $cart_items_result = $conn->query($cart_items_query);
    $stats['total_cart_items'] = $cart_items_result->fetch_assoc()['total_cart_items'];

    // Tính tổng số lượng sản phẩm đã mua từ đơn hàng Delivered (tất cả thời gian)
    $delivered_items_query = "SELECT 
                                o.order_id, 
                                o.order_details 
                              FROM orders o 
                              WHERE o.status = 'Delivered' AND o.order_details IS NOT NULL";
    $delivered_items_result = $conn->query($delivered_items_query);
    $total_delivered_items = 0;

    while ($row = $delivered_items_result->fetch_assoc()) {
        $order_details = json_decode($row['order_details'], true);
        if (is_array($order_details)) {
            foreach ($order_details as $item) {
                if (isset($item['quantity'])) {
                    $total_delivered_items += (int)$item['quantity'];
                }
            }
        }
    }
    $stats['total_delivered_items'] = $total_delivered_items;

    // Tính số lượng sản phẩm đã giao trong 1 tháng gần đây
    $recent_delivered_query = "SELECT 
                                o.order_id, 
                                o.order_details 
                              FROM orders o 
                              WHERE o.status = 'Delivered' 
                                AND o.order_details IS NOT NULL 
                                AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $recent_delivered_result = $conn->query($recent_delivered_query);
    $recent_delivered_items = 0;

    while ($row = $recent_delivered_result->fetch_assoc()) {
        $order_details = json_decode($row['order_details'], true);
        if (is_array($order_details)) {
            foreach ($order_details as $item) {
                if (isset($item['quantity'])) {
                    $recent_delivered_items += (int)$item['quantity'];
                }
            }
        }
    }
    $stats['recent_delivered_items'] = $recent_delivered_items;

    // Tính số lượng sản phẩm đã đặt hàng trong 1 tháng gần đây (tất cả trạng thái)
    $recent_ordered_query = "SELECT 
                                o.order_id, 
                                o.order_details 
                              FROM orders o 
                              WHERE o.order_details IS NOT NULL 
                                AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $recent_ordered_result = $conn->query($recent_ordered_query);
    $recent_ordered_items = 0;

    while ($row = $recent_ordered_result->fetch_assoc()) {
        $order_details = json_decode($row['order_details'], true);
        if (is_array($order_details)) {
            foreach ($order_details as $item) {
                if (isset($item['quantity'])) {
                    $recent_ordered_items += (int)$item['quantity'];
                }
            }
        }
    }
    $stats['recent_ordered_items'] = $recent_ordered_items;

    // Tổng số lượng sản phẩm (giỏ hàng + đã mua delivered)
    $stats['total_product_quantity'] = $stats['total_cart_items'] + $stats['total_delivered_items'];
    $bestseller_query = "SELECT p.product_id, p.name, p.sold_quantity, p.image_url, c.name as category_name 
                        FROM products p 
                        LEFT JOIN categories c ON p.category_id = c.category_id 
                        ORDER BY p.sold_quantity DESC 
                        LIMIT 5";
    $bestseller_result = $conn->query($bestseller_query);
    $stats['bestsellers'] = $bestseller_result->fetch_all(MYSQLI_ASSOC);
    $new_users_query = "SELECT COUNT(*) as new_users FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $new_users_result = $conn->query($new_users_query);
    $stats['new_users'] = $new_users_result->fetch_assoc()['new_users'];
    $total_users_query = "SELECT COUNT(*) as total_users FROM users";
    $total_users_result = $conn->query($total_users_query);
    $stats['total_users'] = $total_users_result->fetch_assoc()['total_users'];
    $total_products_query = "SELECT COUNT(*) as total_products FROM products";
    $total_products_result = $conn->query($total_products_query);
    $stats['total_products'] = $total_products_result->fetch_assoc()['total_products'];
    $low_stock_query = "SELECT COUNT(*) as low_stock FROM products WHERE stock_quantity < 50";
    $low_stock_result = $conn->query($low_stock_query);
    $stats['low_stock'] = $low_stock_result->fetch_assoc()['low_stock'];
    $most_favorited_query = "SELECT p.product_id, p.name, p.image_url, c.name as category_name, COUNT(f.user_id) as favorite_count
                            FROM products p 
                            LEFT JOIN categories c ON p.category_id = c.category_id 
                            LEFT JOIN favorites f ON p.product_id = f.product_id
                            GROUP BY p.product_id, p.name, p.image_url, c.name
                            HAVING favorite_count > 0
                            ORDER BY favorite_count DESC 
                            LIMIT 5";
    $most_favorited_result = $conn->query($most_favorited_query);
    $stats['most_favorited'] = $most_favorited_result->fetch_all(MYSQLI_ASSOC);
    $status_query = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
    $status_result = $conn->query($status_query);
    $stats['order_status'] = $status_result->fetch_all(MYSQLI_ASSOC);
    $top_reviewed_query = "SELECT p.product_id, p.name, COUNT(pr.review_id) as review_count, AVG(pr.rating) as avg_rating
                          FROM products p 
                          LEFT JOIN product_reviews pr ON p.product_id = pr.product_id 
                          GROUP BY p.product_id, p.name 
                          HAVING review_count > 0
                          ORDER BY review_count DESC, avg_rating DESC 
                          LIMIT 5";
    $top_reviewed_result = $conn->query($top_reviewed_query);
    $stats['top_reviewed'] = $top_reviewed_result->fetch_all(MYSQLI_ASSOC);
    $monthly_revenue_query = "SELECT 
                                DATE_FORMAT(created_at, '%Y-%m') as month,
                                SUM(total_amount) as revenue,
                                COUNT(*) as orders_count
                              FROM orders 
                              WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) 
                                AND status = 'Delivered'
                              GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
                              ORDER BY month DESC";
    $monthly_revenue_result = $conn->query($monthly_revenue_query);
    $stats['monthly_revenue'] = $monthly_revenue_result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $message = "<div class='alert alert-danger'>Error loading dashboard data: " . $e->getMessage() . "</div>";
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="../../assets/css/dashboard.css">
<div class="container-fluid px-3 pt-5 dashboard-bg" style="margin-top: 80px;">
    <?= $message ?>
    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 dashboard-header animate__animated animate__fadeInDown">
        <h2 class="mb-2 mb-md-0" style="color:#8c7e71"><i class="bi bi-speedometer2"></i> Dashboard Overview</h2>
        <small class="text-muted"><i class="bi bi-clock-history"></i> Last updated: <?= date('M d, Y H:i', time()) ?></small>
    </div>
    <!-- Quick Stats Cards -->
    <div class="row g-4 mb-4 align-items-stretch">
        <div class="col-xl-3 col-md-6 d-flex">
            <div class="card border-left-primary shadow w-100 h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Revenue</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= formatCurrency($stats['total_revenue']) ?> <span class="desc">VND</span></div>
                        </div>
                        <div class="col-auto">
            <i class="bi bi-currency-dollar fa-2x" style="color:#8c7e71"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 d-flex">
            <div class="card border-left-success shadow w-100 h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Monthly Revenue</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= formatCurrency($stats['recent_revenue']) ?> <span class="desc">VND</span></div>
                        </div>
                        <div class="col-auto">
            <i class="bi bi-graph-up fa-2x" style="color:#8c7e71"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 d-flex">
            <div class="card border-left-info shadow w-100 h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Product Quantities</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= formatNumber($stats['total_cart_items'] + $stats['recent_ordered_items']) ?> <span class="desc">items</span></div>
                            <small class="text-muted">
                                In Cart: <?= formatNumber($stats['total_cart_items']) ?> |
                                Ordered Month: <?= formatNumber($stats['recent_ordered_items']) ?>
                            </small>
                        </div>
                        <div class="col-auto">
            <i class="bi bi-box-seam fa-2x" style="color:#8c7e71"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 d-flex">
            <div class="card border-left-warning shadow w-100 h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Users</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= formatNumber($stats['total_users']) ?> <span class="desc">users</span></div>
                            <small class="text-muted"><?= formatNumber($stats['new_users']) ?> new this month</small>
                        </div>
                        <div class="col-auto">
            <i class="bi bi-people fa-2x" style="color:#8c7e71"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Secondary Stats -->
    <div class="row g-4 mb-4 align-items-stretch">
        <div class="col-xl-3 col-md-6 d-flex">
            <div class="card border-left-secondary shadow w-100 h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Total Orders</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= formatNumber($stats['total_orders']) ?> <span class="desc">orders</span></div>
                            <small class="text-muted"><?= formatNumber($stats['recent_orders']) ?> new this month</small>
                        </div>
                        <div class="col-auto">
            <i class="bi bi-receipt fa-2x" style="color:#8c7e71"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 d-flex">
            <div class="card border-left-secondary shadow w-100 h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Total Products</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= formatNumber($stats['total_products']) ?> <span class="desc">products</span></div>
                        </div>
                        <div class="col-auto">
            <i class="bi bi-box fa-2x" style="color:#8c7e71"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 d-flex">
            <div class="card border-left-danger shadow w-100 h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Low Stock Items</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= formatNumber($stats['low_stock']) ?> <span class="desc">items</span></div>
                            <small class="text-muted">Less than 50 items</small>
                        </div>
                        <div class="col-auto">
            <i class="bi bi-exclamation-triangle fa-2x" style="color:#8c7e71"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 d-flex">
            <div class="card border-left-info shadow w-100 h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Order Status Distribution</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $total_status_count = array_sum(array_column($stats['order_status'], 'count'));
                                echo formatNumber($total_status_count);
                                ?> <span class="desc">statuses</span>
                            </div>
                            <!-- Đã bỏ chi tiết trạng thái, chỉ giữ tổng số -->
                        </div>
                        <div class="col-auto">
            <i class="bi bi-bar-chart fa-2x" style="color:#8c7e71"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Status Distribution Row -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-dark">Order Status Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="row justify-content-center">
                        <?php foreach ($stats['order_status'] as $index => $status): ?>
                            <div class="col-12 col-sm-6 col-md-3 text-center mb-3 d-flex">
                                <div class="border rounded p-3 w-100 h-100 mx-auto">
                                    <div class="text-xs font-weight-bold text-uppercase mb-2 text-<?= getStatusColor($status['status']) ?>">
                                        <?= ucfirst($status['status']) ?>
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= formatNumber($status['count']) ?></div>
                                    <div class="progress mt-2" style="height: 4px;">
                                        <div class="progress-bar bg-<?= getStatusColor($status['status']) ?>" role="progressbar"
                                            style="width: <?= ($status['count'] / max(array_column($stats['order_status'], 'count'))) * 100 ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Tables Row -->
    <div class="row">
        <!-- Best Selling Products -->
        <div class="col-xl-4 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-dark">Best Selling Products</h6>
                    <a href="products.php" class="btn btn-sm btn-outline-dark">View All</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($stats['bestsellers'])): ?>
                        <?php foreach ($stats['bestsellers'] as $product): ?>
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?= htmlspecialchars($product['image_url']) ?>"
                                    alt="<?= htmlspecialchars($product['name']) ?>"
                                    class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                <div class="ms-3 flex-grow-1">
                                    <div class="fw-bold"><?= htmlspecialchars($product['name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($product['category_name']) ?></small>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-success"><?= formatNumber($product['sold_quantity']) ?> sold</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center">No sales data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- Most Favorited Products -->
        <div class="col-xl-4 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-dark">Most Favorited Products</h6>
                    <a href="products.php" class="btn btn-sm btn-outline-dark">View All</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($stats['most_favorited'])): ?>
                        <?php foreach ($stats['most_favorited'] as $product): ?>
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?= htmlspecialchars($product['image_url']) ?>"
                                    alt="<?= htmlspecialchars($product['name']) ?>"
                                    class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                <div class="ms-3 flex-grow-1">
                                    <div class="fw-bold"><?= htmlspecialchars($product['name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($product['category_name']) ?></small>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-danger">
                                        <i class="bi bi-heart-fill"></i> <?= formatNumber($product['favorite_count']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center">No favorites data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- Top Reviewed Products -->
        <div class="col-xl-4 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-dark">Most Reviewed Products</h6>
                    <a href="reviews.php" class="btn btn-sm btn-outline-dark">View All</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($stats['top_reviewed'])): ?>
                        <?php foreach ($stats['top_reviewed'] as $product): ?>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($product['name']) ?></div>
                                    <div class="text-warning">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star<?= $i <= round($product['avg_rating']) ? '-fill' : '' ?>"></i>
                                        <?php endfor; ?>
                                        <span class="ms-1"><?= number_format($product['avg_rating'], 1) ?></span>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold"><?= formatNumber($product['review_count']) ?> reviews</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center">No reviews data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Monthly Revenue Chart -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-dark">Monthly Revenue Trend (Last 6 Months)</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($stats['monthly_revenue'])): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Month</th>
                                        <th>Revenue</th>
                                        <th>Orders</th>
                                        <th>Avg Order Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['monthly_revenue'] as $month_data): ?>
                                        <tr>
                                            <td><?= date('F Y', strtotime($month_data['month'] . '-01')) ?></td>
                                            <td class="text-success fw-bold"><?= formatCurrency($month_data['revenue']) ?></td>
                                            <td><?= formatNumber($month_data['orders_count']) ?></td>
                                            <td><?= formatCurrency($month_data['revenue'] / $month_data['orders_count']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center">No revenue data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Hiệu ứng và style đã chuyển sang dashboard.css, không cần style nội tuyến ở đây -->
<?php include '../../includes/footer.php'; ?>