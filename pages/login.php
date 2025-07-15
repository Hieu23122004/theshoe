<?php
session_start();
include '../includes/database.php';

// Get redirect parameter from GET or POST
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : (isset($_POST['redirect']) ? $_POST['redirect'] : '/pages/new_products.php');

$message = ""; // tránh lỗi nếu chưa nhấn login

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $max_attempts = 5;
    $block_time = 15 * 60;
    $now = time();

    // Check login_attempts table for this IP
    $stmt = $conn->prepare("SELECT attempts, blocked_until FROM login_attempts WHERE ip = ?");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $stmt->bind_result($db_attempts, $db_blocked_until);
    $has_row = $stmt->fetch();
    $stmt->close();

    if ($has_row && $db_blocked_until !== null && $now < $db_blocked_until) {
        $minutes = ceil(($db_blocked_until - $now) / 60);
        $_SESSION['login_error'] = 'You have entered the wrong password too many times. Please try again in ' . $minutes . ' minutes.';
    } else {
        if ($has_row && $db_blocked_until !== null && $now >= $db_blocked_until) {
            // Unblock IP if block expired
            $stmt = $conn->prepare("UPDATE login_attempts SET attempts = 0, blocked_until = NULL WHERE ip = ?");
            $stmt->bind_param("s", $ip);
            $stmt->execute();
            $stmt->close();
            $db_attempts = 0;
            $db_blocked_until = null;
        }
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_assoc();

                if (!password_verify($password, $user['password_hash'])) {
                    // Increase failed attempts in DB
                    $new_attempts = ($has_row ? $db_attempts : 0) + 1;
                    if ($new_attempts >= $max_attempts) {
                        $blocked_until = $now + $block_time;
                        if ($has_row) {
                            $stmt2 = $conn->prepare("UPDATE login_attempts SET attempts = ?, blocked_until = ? WHERE ip = ?");
                            $stmt2->bind_param("iis", $new_attempts, $blocked_until, $ip);
                        } else {
                            $stmt2 = $conn->prepare("INSERT INTO login_attempts (ip, attempts, blocked_until) VALUES (?, ?, ?)");
                            $stmt2->bind_param("sii", $ip, $new_attempts, $blocked_until);
                        }
                        $stmt2->execute();
                        $stmt2->close();
                        $minutes_left = ceil(($blocked_until - $now) / 60);
                        $_SESSION['login_error'] = 'You have entered the wrong password too many times. Please try again in ' . $minutes_left . ' minutes.';
                    } else {
                        if ($has_row) {
                            $stmt2 = $conn->prepare("UPDATE login_attempts SET attempts = ?, blocked_until = NULL WHERE ip = ?");
                            $stmt2->bind_param("is", $new_attempts, $ip);
                        } else {
                            $stmt2 = $conn->prepare("INSERT INTO login_attempts (ip, attempts) VALUES (?, ?)");
                            $stmt2->bind_param("si", $ip, $new_attempts);
                        }
                        $stmt2->execute();
                        $stmt2->close();
                        $_SESSION['login_error'] = "Wrong password. You have " . ($max_attempts - $new_attempts) . " attempts left.";
                    }
                } else {
                    // Đăng nhập thành công, reset đếm trong DB
                    if ($has_row) {
                        $stmt2 = $conn->prepare("UPDATE login_attempts SET attempts = 0, blocked_until = NULL WHERE ip = ?");
                        $stmt2->bind_param("s", $ip);
                        $stmt2->execute();
                        $stmt2->close();
                    }
                    // Lưu thông tin người dùng vào session
                    $_SESSION['user'] = [
                        'user_id' => $user['user_id'],
                        'fullname' => $user['fullname'],
                        'email' => $user['email'],
                        'role' => $user['role']
                    ];
                    $_SESSION['user_id'] = $user['user_id']; // for cart logic
                    $_SESSION['is_logged_in'] = true; // đánh dấu đã đăng nhập
                    $_SESSION['last_activity'] = time(); // lưu thời gian hoạt động cuối
                    $message = "Login successful!";

                    // --- Đồng bộ cart session và favorites vào database sau khi đăng nhập ---
                    try {
                        // Đồng bộ cart session vào database
                        if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                            $user_id = $user['user_id'];
                            foreach ($_SESSION['cart'] as $product_id => $item) {
                                // Kiểm tra product_id có tồn tại trong database không
                                $stmt_product_check = $conn->prepare("SELECT product_id FROM products WHERE product_id = ?");
                                $stmt_product_check->bind_param("i", $product_id);
                                $stmt_product_check->execute();
                                $product_exists = $stmt_product_check->get_result();
                                $stmt_product_check->close();
                                
                                // Chỉ xử lý nếu product_id tồn tại
                                if ($product_exists && $product_exists->num_rows > 0) {
                                    $color = $item['color'] ?? '';
                                    $size = $item['size'] ?? '';
                                    $quantity = (int)($item['quantity'] ?? 1);
                                    
                                    // Kiểm tra đã có sản phẩm này trong cart_items chưa
                                    $stmt_check = $conn->prepare("SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ? AND color = ? AND size = ?");
                                    $stmt_check->bind_param("iiss", $user_id, $product_id, $color, $size);
                                    $stmt_check->execute();
                                    $result_check = $stmt_check->get_result();
                                    
                                    if ($result_check && $result_check->num_rows > 0) {
                                        // Đã có, cộng dồn số lượng
                                        $row = $result_check->fetch_assoc();
                                        $new_qty = $row['quantity'] + $quantity;
                                        $stmt_update = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ? AND color = ? AND size = ?");
                                        $stmt_update->bind_param("iiiss", $new_qty, $user_id, $product_id, $color, $size);
                                        $stmt_update->execute();
                                        $stmt_update->close();
                                    } else {
                                        // Chưa có, insert mới
                                        $stmt_insert = $conn->prepare("INSERT INTO cart_items (user_id, product_id, color, size, quantity) VALUES (?, ?, ?, ?, ?)");
                                        $stmt_insert->bind_param("iissi", $user_id, $product_id, $color, $size, $quantity);
                                        $stmt_insert->execute();
                                        $stmt_insert->close();
                                    }
                                    $stmt_check->close();
                                }
                                // Nếu product không tồn tại, bỏ qua item này (không báo lỗi)
                            }
                            // Xóa cart session sau khi đồng bộ
                            unset($_SESSION['cart']);
                        }
                        
                        // Đồng bộ sản phẩm yêu thích
                        $user_id = $user['user_id'];
                        $favorites_to_add = [];
                        if (isset($_SESSION['pending_favorite']) && $_SESSION['pending_favorite']) {
                            $favorites_to_add[] = (int)$_SESSION['pending_favorite'];
                            unset($_SESSION['pending_favorite']);
                        }
                        if (isset($_SESSION['favorites']) && is_array($_SESSION['favorites'])) {
                            foreach ($_SESSION['favorites'] as $pid) {
                                $favorites_to_add[] = (int)$pid;
                            }
                            unset($_SESSION['favorites']);
                        }
                        // Loại bỏ trùng lặp
                        $favorites_to_add = array_unique($favorites_to_add);
                        foreach ($favorites_to_add as $fav_pid) {
                            // Kiểm tra product_id có tồn tại trong database không
                            $stmt_product_check = $conn->prepare("SELECT product_id FROM products WHERE product_id = ?");
                            $stmt_product_check->bind_param("i", $fav_pid);
                            $stmt_product_check->execute();
                            $product_exists = $stmt_product_check->get_result();
                            $stmt_product_check->close();
                            
                            // Chỉ xử lý nếu product_id tồn tại
                            if ($product_exists && $product_exists->num_rows > 0) {
                                // Kiểm tra đã có chưa
                                $stmt = $conn->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND product_id = ?");
                                $stmt->bind_param("ii", $user_id, $fav_pid);
                                $stmt->execute();
                                $stmt->store_result();
                                if ($stmt->num_rows == 0) {
                                    $stmt_insert = $conn->prepare("INSERT INTO favorites (user_id, product_id) VALUES (?, ?)");
                                    $stmt_insert->bind_param("ii", $user_id, $fav_pid);
                                    $stmt_insert->execute();
                                    $stmt_insert->close();
                                }
                                $stmt->close();
                            }
                            // Nếu product không tồn tại, bỏ qua item này
                        }
                    } catch (Exception $sync_error) {
                        // Ghi log lỗi nhưng không ngăn đăng nhập thành công
                        error_log("Cart/Favorites sync error for user " . $user['user_id'] . ": " . $sync_error->getMessage());
                    }

                    // Role-based redirection
                    if ($user['role'] === 'admin') {
                        $redirect = '../admin/pages/dashboard.php'; // Fixed relative path for admin
                    } else {
                        // Customer goes to home or original redirect
                        if ($redirect === '/pages/home.php' || strpos($redirect, 'login.php') !== false) {
                            $redirect = '/pages/home.php';
                        }
                    }

                    // Nếu redirect về checkout thì reload lại để JS lấy localStorage (tránh autofill từ trình duyệt)
                    if (strpos($redirect, 'checkout.php') !== false) {
                        echo "<script>window.location.replace('" . htmlspecialchars($redirect, ENT_QUOTES) . "');</script>";
                        exit;
                    } else {
                        header('Location: ' . $redirect);
                        exit;
                    }
                }
            } else {
                // Increase failed attempts in DB
                $new_attempts = ($has_row ? $db_attempts : 0) + 1;
                if ($new_attempts >= $max_attempts) {
                    $blocked_until = $now + $block_time;
                    if ($has_row) {
                        $stmt2 = $conn->prepare("UPDATE login_attempts SET attempts = ?, blocked_until = ? WHERE ip = ?");
                        $stmt2->bind_param("iis", $new_attempts, $blocked_until, $ip);
                    } else {
                        $stmt2 = $conn->prepare("INSERT INTO login_attempts (ip, attempts, blocked_until) VALUES (?, ?, ?)");
                        $stmt2->bind_param("sii", $ip, $new_attempts, $blocked_until);
                    }
                    $stmt2->execute();
                    $stmt2->close();
                    $_SESSION['login_error'] = 'You have entered the wrong password too many times. Please try again in 15 minutes.';
                } else {
                    if ($has_row) {
                        $stmt2 = $conn->prepare("UPDATE login_attempts SET attempts = ?, blocked_until = NULL WHERE ip = ?");
                        $stmt2->bind_param("is", $new_attempts, $ip);
                    } else {
                        $stmt2 = $conn->prepare("INSERT INTO login_attempts (ip, attempts) VALUES (?, ?)");
                        $stmt2->bind_param("si", $ip, $new_attempts);
                    }
                    $stmt2->execute();
                    $stmt2->close();
                    $_SESSION['login_error'] = "Account does not exist. You have " . ($max_attempts - $new_attempts) . " attempts left.";
                }
            }
        } else {
            $_SESSION['login_error'] = "System error. Please try again.";
        }
    }
} else {
    if (isset($_SESSION['login_error'])) unset($_SESSION['login_error']);
}

// Lưu pending_favorite vào session nếu có trên URL (GET)
if (isset($_GET['pending_favorite']) && is_numeric($_GET['pending_favorite'])) {
    if (!isset($_SESSION['favorites']) || !is_array($_SESSION['favorites'])) {
        $_SESSION['favorites'] = [];
    }
    $pid = (int)$_GET['pending_favorite'];
    if (!in_array($pid, $_SESSION['favorites'])) {
        $_SESSION['favorites'][] = $pid;
    }
    // Để đảm bảo đồng bộ, cũng lưu vào session['pending_favorite']
    $_SESSION['pending_favorite'] = $pid;
}

if (isset($_SESSION['user']) && isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > 30) { // 900s = 15 phút
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['login_error'] = 'Session expired. Please log in again.';
        header('Location: login.php');
        exit;
    } else {
        $_SESSION['last_activity'] = time(); // cập nhật lại thời gian hoạt động
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập</title>
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Font Awesome with latest version -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/login_register.css">
</head>

<body class="d-flex align-items-stretch min-vh-100 bg-light">
    <a href="home.php" class="back-to-home">
        <i class="fas fa-home me-2"></i>
        Home
    </a>
    <div class="d-flex flex-row flex-grow-1 w-100" style="min-height:100vh;">
        <!-- Logo và slogan bên trái cố định -->
        <div class="d-none d-md-flex flex-column align-items-center justify-content-center bg-white" style="width:50vw;min-width:400px;">
            <a class="navbar-brand d-block mb-3" href="#">
                <div class="d-flex align-items-center gap-3" style="color:#8C7E71;">
                    <svg viewBox="0 0 100 100" fill="none" stroke="#8C7E71" stroke-width="5" stroke-linejoin="round" style="width:60px;height:60px;">
                        <path d="M10 50 L30 20 L70 20 L90 50 L50 90 Z" />
                        <path d="M30 55 L40 35 L50 55 L60 35 L70 55" />
                        <path d="M60 65 Q50 75 40 65 L40 60 L50 60 L50 68" />
                    </svg>
                    <div class="d-flex flex-column align-items-center" style="line-height:1;">
                        <span style="font-family:'Montserrat',Arial,sans-serif;font-size:2.2rem;font-weight:700;letter-spacing:3px;">
                            MULGATI
                            <sup style="font-size:0.9em; position: relative; top: 4px; margin-left: 3px;">®</sup>
                        </span>
                        <span style="display:block;width:100%;height:2px;background:#8C7E71;margin:3px 0;"></span>
                        <span style="font-size:1rem;letter-spacing:6px;">RUSSIA</span>
                    </div>
                </div>
            </a>
            <h4 class="text-center text-dark">MULGATI® – Timeless Style, Russian Soul</h4>
        </div>
        <!-- Form đăng nhập bên phải -->
        <div class="flex-grow-1 d-flex align-items-center justify-content-center">
            <div class="bg-white rounded-3 shadow p-4" style="width: 100%; max-width: 400px; min-height: 480px;">
                <h4 class="text-center text-dark fw-bold mb-4">LOGIN</h4>
                <?php if (!empty($message)): ?>
                    <div class="alert alert-info text-center"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
                    <div class="mb-3">
                        <input type="email" class="form-control" name="email" placeholder="Email" required>
                    </div>
                    <div class="mb-3">
                        <input type="password" class="form-control" name="password" placeholder="Mật khẩu" required>
                    </div>

                    <?php if (isset($_SESSION['login_error'])): ?>
                        <div class="alert alert-danger text-center small">
                            <?= $_SESSION['login_error']; ?>
                        </div>
                        <?php unset($_SESSION['login_error']); ?>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-dark w-100" name="login">Login</button>
                </form>

                <div class="text-center mt-3">
                    <a href="#" onclick="document.getElementById('forgotModal').style.display='block'; return false;" class="text-decoration-none text-primary small">Forgot Password</a> |
                    <a href="#" class="text-decoration-none text-primary small">Privacy Policy</a>
                </div>
                <!-- Modal Forgot Password -->
                <div id="forgotModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.2);">
                    <div style="background:white;padding:20px;max-width:400px;margin:100px auto;position:relative;border-radius:8px;">
                        <h4 class="mb-4 text-center">Forgot Password</h4>

                        <div class="mb-3">
                            <label for="forgotEmail" class="form-label">Email</label>
                            <input type="email" id="forgotEmail" class="form-control" required>
                        </div>

                        <div class="d-grid mb-3">
                            <button class="btn btn-dark" onclick="sendCode()">Send Verification Code</button>
                            <div id="loadingTextEMAIL" class="text-primary small mt-1" style="display:none;">🔄 Sending code...</div>
                        </div>

                        <div id="codeSection" style="display:none;">
                            <div class="mb-3">
                                <label for="verifyCode" class="form-label">Enter Verification Code (OTP)</label>
                                <input type="text" id="verifyCode" maxlength="6" class="form-control" placeholder="6-digit code">
                                <div id="loadingTextEmail" class="text-primary small mt-1" style="display:none;">🔄 Verifying code...</div>
                                <div id="emailCountdown" class="text-muted small mt-1"></div>
                            </div>
                            <div class="d-grid mb-3">
                                <button id="resendEmailBtn" class="btn btn-secondary" onclick="resendCode()" style="display:none;">Resend Code</button>
                            </div>
                        </div>

                        <p id="forgotMessage" class="mt-3 text-center text-primary small"></p>
                        <p id="forgotMessageloi" class="mt-3 text-center text-danger small"></p>

                        <button onclick="document.getElementById('forgotModal').style.display='none'" style="position:absolute;top:10px;right:10px;width:32px;height:32px;border:none;border-radius:4px;background:#ccc;color:#000;cursor:pointer;">X</button>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <hr>
                </div>


                <div class="text-center mt-3">
                    <small>Don't have an account? <a href="/pages/register.php?redirect=<?= urlencode($redirect) ?>" class="text-danger">Sign up</a></small>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap 5 JS Bundle (with Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/login.js"></script>
    <script>
        window.isLoggedIn = <?php echo isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] ? 'true' : 'false'; ?>;
    </script>
    <script src="/assets/js/auto_logout.js"></script>
</body>

</html>