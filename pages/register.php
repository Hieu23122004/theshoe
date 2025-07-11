<?php
include '../includes/database.php';
session_start();
// Get redirect parameter from GET or POST
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : (isset($_POST['redirect']) ? $_POST['redirect'] : '/pages/profile.php');
// Handle registration
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
  $full_name = trim($_POST['full_name']);
  $email = trim($_POST['email']);
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
  $phone = trim($_POST['phone']);
  $address = trim($_POST['address']);
  $role = 'customer'; // Default role is customer

  // Check for allowed email domains
  $allowed_domains = ['gmail.com', 'hotmail.com', 'outlook.com', 'microsoft.com', 'fpt.edu.vn'];
  $email_domain = explode('@', $email)[1] ?? '';

  if (!in_array($email_domain, $allowed_domains)) {
    $message = "Email domain is not allowed!";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $message = "Invalid email format.";
  } else {
    // Prepare SQL statement
    $stmt = $conn->prepare("INSERT INTO users (fullname, email, password_hash, phone, address, role) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
      $message = "Database error: " . $conn->error;
    } else {
      $stmt->bind_param("ssssss", $full_name, $email, $password, $phone, $address, $role);
      if ($stmt->execute()) {
        $message = "Registration successful! You can now log in.";
        // Không auto-login, không chuyển trang
      } else {
        if ($conn->errno == 1062) {
          $message = "Email already exists!";
        } else {
          $message = "Error inserting data: " . $conn->error;
        }
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>register</title>
  <!-- Bootstrap 5 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/assets/css/login_register.css">
  <script src="/assets/js/auto_logout.js"></script>
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
          <div class="d-flex flex-column align-items-center" style="line-height:1; margin-right: 16px;">
            <span style="font-family:'Montserrat',Arial,sans-serif;font-size:2.2rem;font-weight:700;letter-spacing:3px;">
              MULGATI
              <sup style="font-size:0.9em; position: relative; top: 4px; margin-left: 3px;">®</sup>
            </span>
            <span style="display:block;width:100%;height:2px;background:#8C7E71;margin:3px 0;"></span>
            <span style="font-size:1rem;letter-spacing:6px;">RUSSIA</span>
          </div>
        </div>
      </a>
      <h4 class="text-center text-dark m-1 mt-1">MULGATI® – Timeless Style, Russian Soul</h4>
    </div>
    <!-- Form đăng ký bên phải -->

    <div class="flex-grow-1 d-flex align-items-center justify-content-center">
      <div class="bg-white rounded-3 shadow p-4 w-100" style="max-width: 400px; min-height: 480px;">
        <h4 class="text-center text-dark fw-bold mb-4">REGISTER</h4>

        <?php if ($message): ?>
          <div class="alert alert-info text-center"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST">
          <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
          <div class="mb-3">
            <input type="text" name="full_name" class="form-control" placeholder="Full Name" required>
          </div>
          <div class="mb-3">
            <input type="email" name="email" class="form-control" placeholder="Email" required>
          </div>
          <div class="mb-3">
            <input type="password" name="password" class="form-control" placeholder="Password" required>
          </div>
          <div class="mb-3">
            <input type="text" name="phone" class="form-control" placeholder="Phone Number">
          </div>
          <div class="mb-3">
            <input type="text" name="address" class="form-control" placeholder="Address">
          </div>
          <button type="submit" name="register" class="btn btn-dark w-100">Register</button>
        </form>

        <div class="text-center mt-4">
          <small>Already have an account? <a href="/pages/login.php?redirect=<?= urlencode($redirect) ?>" class="text-danger">Log in</a></small>
        </div>
      </div>
    </div>
  </div>
  <!-- Bootstrap 5 JS Bundle (with Popper) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>