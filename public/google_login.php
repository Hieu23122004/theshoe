<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

// Load env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri('http://localhost/app/public/google_login.php');
$client->addScope('email');
$client->addScope('profile');

if (!isset($_GET['code'])) {
    $authUrl = $client->createAuthUrl();
    header('Location: ' . $authUrl);
    exit();
} else {
    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        $client->setAccessToken($token);

        $payload = $client->verifyIdToken();
        if ($payload) {
            $email = $payload['email'];
            $full_name = $payload['name'];

            require_once __DIR__ . '/../config/database.php';

            // Kiểm tra email đã tồn tại chưa
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if (!$user) {
                // Nếu chưa có thì tạo tài khoản mới
                $password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);

                $stmt = $conn->prepare("INSERT INTO users (fullname, email, password_hash, phone, address, role) 
                                        VALUES (?, ?, ?, NULL, NULL, 'customer')");
                $stmt->bind_param("sss", $full_name, $email, $password);
                $stmt->execute();

                $user_id = $stmt->insert_id;
            } else {
                $user_id = $user["user_id"];
                $full_name = $user["fullname"];
            }

            // Lưu session
            $_SESSION["user_id"] = $user_id;
            $_SESSION["username"] = $email;
            $_SESSION["full_name"] = $full_name;
            $_SESSION["user_role"] = $user["role"] ?? 'customer';

           header('Location: ../app/pages/new_products.php');
            exit();
        } else {
            echo 'Xác thực Google thất bại!';
            exit();
        }
    } catch (Exception $e) {
        echo 'Google Login thất bại: ' . $e->getMessage();
        exit();
    }
}
?>
