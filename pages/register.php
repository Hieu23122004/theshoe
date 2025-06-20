<?php
include '../includes/database.php';
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
                $message = "Registration successful! Please login.";
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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/assets/css/login_register.css">
</head>

<body class="d-flex align-items-center justify-content-center min-vh-100 bg-light">
    <a href="home.php" class="back-to-home">
        <i class="fas fa-home me-2"></i>
        Home
    </a>

    <div class="container-fluid  bg-gradient vh-100 d-flex align-items-center justify-content-center">
    <div class="row w-100">
        <!-- Logo và slogan bên trái -->
        <div class="col-md-6 d-none d-md-flex flex-column align-items-center justify-content-center text-white">
            <a href="" target="_blank">
                <img src="/assets/images/logo.png" alt="" style="width: 250px; height: auto;">
            </a>
            <h4 class="text-center fw-bold text-dark"> Alf TheShoe – Fashion Shoe World<br>
        For Your Style</h4>
        </div>


    <div class="col-md-6 d-flex align-items-center justify-content-center">
      <div class="bg-white rounded-3 shadow p-4 w-100" style="max-width: 400px;">
        <h4 class="text-center text-dark fw-bold mb-4">REGISTER</h4>

        <?php if ($message): ?>
          <div class="alert alert-info text-center"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST">
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
          <small>Already have an account? <a href="/pages/login.php" class="text-danger">Log in</a></small>
        </div>
      </div>
    </div>
  </div>
</div>
</body>

</html>