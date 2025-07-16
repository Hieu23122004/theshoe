<?php

include '../../includes/database.php';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$message = isset($_GET['message']) && !empty($_GET['message']) ? $_GET['message'] : null;
$query = "SELECT o.order_id, u.fullname, u.email, u.phone, o.total_amount, o.status, 
          o.created_at, o.order_details, o.shipping_address, o.payment_method
          FROM orders o
          LEFT JOIN users u ON o.user_id = u.user_id";
if (!empty($status_filter)) {
    $query .= " WHERE o.status = '" . $conn->real_escape_string($status_filter) . "'";
}
$query .= " ORDER BY o.created_at DESC";
$result = $conn->query($query);
include '../../includes/header_ad.php';
?>
<!-- Begin Page Content -->
<div class="container-fluid px-2" style="margin-top: 110px;">
    <?php if (isset($message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert" id="statusAlert">
            <strong>Thông báo:</strong> <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <script>
            setTimeout(function() {
                const alert = document.getElementById('statusAlert');
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 1000);
        </script>
    <?php endif; ?>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-cente bg-primary">
            <strong>Orders management</strong>
        </div>
        <select name="status" class="form-select w-25 m-3" onchange="window.location.href='?status='+this.value">
            <option value="">All statuses</option>
            <option value="Pending" <?= $status_filter == 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="Processing" <?= $status_filter == 'Processing' ? 'selected' : '' ?>>Processing</option>
            <option value="Shipped" <?= $status_filter == 'Shipped' ? 'selected' : '' ?>>Shipped</option>
            <option value="Delivered" <?= $status_filter == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
            <option value="Cancelled" <?= $status_filter == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
        <div class="card-body table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="3%">ID</th>
                        <th width="10%">Customer</th>
                        <th width="17%">Contact</th>
                        <th width="25%">Shipping Address</th>
                        <th width="10%">Total Amount</th>
                        <th width="10%">Payment Method</th>
                        <th width="15%">Status</th>
                        <th width="10%">Ngày đặt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $result->fetch_assoc()):
                        $orderDetails = json_decode($order['order_details'], true);
                    ?>
                        <tr>
                            <td>#<?= $order['order_id'] ?></td>
                            <td><?= htmlspecialchars($order['fullname']) ?></td>
                            <td>
                                <small>Email:</small> <?= htmlspecialchars($order['email']) ?><br>
                                <small>ĐT:</small> <?= htmlspecialchars($order['phone']) ?>
                            </td>
                            <td><?= htmlspecialchars($order['shipping_address']) ?></td>
                            <td><strong><?= number_format($order['total_amount']) ?> đ</strong></td>
                            <td><?= htmlspecialchars($order['payment_method']) ?></td>
                            <td>
                                <select class="form-select form-select-sm status-select"
                                    data-order-id="<?= $order['order_id'] ?>"
                                    data-current-status="<?= $order['status'] ?>"
                                    onclick="event.stopPropagation();">
                                    <option value="Pending" <?= $order['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="Processing" <?= $order['status'] == 'Processing' ? 'selected' : '' ?>>Processing</option>
                                    <option value="Shipped" <?= $order['status'] == 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                                    <option value="Delivered" <?= $order['status'] == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                    <option value="Cancelled" <?= $order['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Custom JavaScript -->
<script src="../../assets/js/ad_order.js"></script>

<?php include '../../includes/footer.php'; ?>