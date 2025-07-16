<?php
include '../../includes/database.php';
include '../../includes/auth.php';
$filter = "";
if (isset($_GET['since']) && $_GET['since']) {
    $since_date = $_GET['since'] . ' 00:00:00';
    $filter = "WHERE created_at >= '$since_date'";
}
$sql = "SELECT * FROM login_attempts $filter ORDER BY attempts DESC, created_at DESC";
$result = $conn->query($sql);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ip'])) {
    $ip = $conn->real_escape_string($_POST['ip']);
    if (isset($_POST['reset'])) {
        $conn->query("UPDATE login_attempts SET attempts = 0, blocked_until = NULL WHERE ip = '$ip'");
        $_SESSION['admin_message'] = "IP $ip has been unblocked successfully.";
    } elseif (isset($_POST['block'])) {
        $blockedUntil = time() + 3600;
        $conn->query("UPDATE login_attempts SET blocked_until = $blockedUntil WHERE ip = '$ip'");
        $_SESSION['admin_message'] = "IP $ip has been blocked for 1 hour. User will be automatically logged out.";
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
include '../../includes/header_ad.php';
?>
<div class="container-fluid px-2" style="margin-top: 110px;">
    <?php if (isset($_SESSION['admin_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['admin_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['admin_message']); ?>
    <?php endif; ?>
    <div class="card mb-4">
        <div class="card-header bg-primary"><strong>Login Attempts Management</strong></div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="since" class="form-label">Filter from date:</label>
                    <input type="date" id="since" name="since" class="form-control" value="<?= $_GET['since'] ?? '' ?>">
                </div>
                <div class="col-md-2 align-self-end">
                    <button class="btn btn-primary w-100"><i class="bi bi-funnel-fill me-1"></i>Filter</button>
                </div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><strong>Failed Login Attempts List</strong></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>IP Address</th>
                        <th>Failed Attempts</th>
                        <th>First Attempt</th>
                        <th>Block Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars(str_replace('::', '', $row['ip'])) ?></td>
                            <td><span class="badge bg-danger"><?= $row['attempts'] ?></span></td>
                            <td><?= $row['created_at'] ?></td>
                            <td>
                                <?php if ($row['blocked_until'] && $row['blocked_until'] > time()): ?>
                                    <?php
                                    $remaining_seconds = $row['blocked_until'] - time();
                                    $remaining_minutes = floor($remaining_seconds / 60);
                                    $remaining_secs = $remaining_seconds % 60;
                                    $current_time = time();
                                    $block_end_time = date('H:i:s', $row['blocked_until']);
                                    ?>
                                    <span class="badge bg-warning text-dark">
                                        Blocked: <?= $remaining_minutes ?>m <?= $remaining_secs ?>s left
                                    </span>
                                <?php elseif ($row['blocked_until']): ?>
                                    <span class="badge bg-success">Block expired</span>
                                <?php else: ?>
                                    <span class="text-muted">Not blocked</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="ip" value="<?= htmlspecialchars($row['ip']) ?>">
                                    <button name="reset" class="btn btn-sm btn-outline-warning me-1">
                                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                                    </button>
                                   
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if ($result->num_rows === 0): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No data available</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    setTimeout(function() {
        const alertMsg = document.querySelector('.alert-success');
        if (alertMsg) {
            alertMsg.style.transition = 'opacity 0.5s';
            alertMsg.style.opacity = '0';
            setTimeout(() => alertMsg.remove(), 500);
        }
    }, 1000);
</script>
<?php include '../../includes/footer.php'; ?>