<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

// Facebook configuration
$config = [
    'callback' => 'http://localhost/app_test/public/facebook_login.php',
    'providers' => [
        'Facebook' => [
            'enabled' => true,
            'keys'    => [
                'id'     => $_ENV['FACEBOOK_CLIENT_ID'],
                'secret' => $_ENV['FACEBOOK_CLIENT_SECRET'],
            ],
            'scope' => 'email,public_profile',
            'trustForwarded' => false,
            'version' => 'v18.0',
        ],
    ],
];

try {
    // Debug information
    error_log("Starting Facebook authentication...");
    
    $hybridauth = new Hybridauth\Hybridauth($config);
    $adapter = $hybridauth->authenticate('Facebook');
    $userProfile = $adapter->getUserProfile();

    if (empty($userProfile->email)) {
        throw new Exception("Cannot retrieve email from Facebook. Please ensure you've granted email permission.");
    }

    $email = $userProfile->email;
    $full_name = $userProfile->displayName;

    require_once __DIR__ . '/../includes/database.php';

    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection failed. Please try again later.");
    }

    // Check if user already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        // Create new user with random password (they can reset it later if needed)
        $random_password = bin2hex(random_bytes(8));
        $password_hash = password_hash($random_password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (fullname, email, password_hash, role) VALUES (?, ?, ?, 'customer')");
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("sss", $full_name, $email, $password_hash);
        if (!$stmt->execute()) {
            throw new Exception("Failed to create user account: " . $stmt->error);
        }

        $user_id = $stmt->insert_id;
        $stmt->close();
    } else {
        $user_id = $user["user_id"];
        $full_name = $user["fullname"]; // Using fullname from database to be consistent
    }

    // Set session variables
    $_SESSION["user_id"] = $user_id;
    $_SESSION["username"] = $email;
    $_SESSION["full_name"] = $full_name;

    header('Location: /app_test/pages/home.php');
    exit();
} catch (Exception $e) {
    error_log("Facebook Login Error: " . $e->getMessage());
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Login Error</title>
        <style>
            .error-container {
                text-align: center;
                margin-top: 50px;
                font-family: Arial, sans-serif;
            }
            .error-message {
                color: #dc3545;
                margin-bottom: 20px;
            }
            .back-button {
                background-color: #007bff;
                color: white;
                padding: 10px 20px;
                text-decoration: none;
                border-radius: 5px;
            }
            .back-button:hover {
                background-color: #0056b3;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <h2 class="error-message">Login failed: <?php echo htmlspecialchars($e->getMessage()); ?></h2>
            <a href="/app_test/pages/login.php" class="back-button">Back to Login</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}
