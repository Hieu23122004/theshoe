<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$response = ["status" => "error", "message" => "Lỗi không xác định."];


if (!isset($conn) || !$conn) {
    $response["message"] = "Không thể kết nối cơ sở dữ liệu.";
    echo json_encode($response);
    exit;
}

function sendEmail($toEmail, $subject, $body)
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'theshoe6868@gmail.com';
        $mail->Password = 'zgcsvetmozzbyoek';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('theshoe6868@gmail.com', 'The Shoe Shop');
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';
    $email = $_POST['email'] ?? '';


    if (empty($email) || empty($action)) {
        $response["message"] = "Thiếu action hoặc email.";
        echo json_encode($response);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["message"] = "Email không hợp lệ.";
        echo json_encode($response);
        exit;
    }


    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    if (!$stmt) {
        $response["message"] = "Lỗi truy vấn CSDL: " . $conn->error;
        echo json_encode($response);
        exit;
    }
    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        $response["message"] = "Không thể thực hiện truy vấn.";
        echo json_encode($response);
        exit;
    }
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        $response["message"] = "Email chưa được đăng ký.";
        echo json_encode($response);
        exit;
    }

    if ($action === "send_code") {
        $code = rand(100000, 999999);
        $_SESSION["reset_code"] = $code;
        $_SESSION["reset_email"] = $email;
        $_SESSION["reset_code_expiry"] = time() + 120;

        $full_name = $user['fullname'] ?? 'Customer';

        $body = "
            <html>
            <head><style>body { font-family: Arial; }</style></head>
            <body>
                <h3>Xin chào $full_name,</h3>
                <p>Bạn đã yêu cầu đặt lại mật khẩu trên The Shoe Shop.</p>
                <p>Mã xác nhận của bạn là: <strong>$code</strong></p>
                <p>Mã này sẽ hết hạn sau 2 phút.</p>
            </body>
            </html>
        ";

        if (sendEmail($email, "Password recovery confirmation code", $body)) {
            $response["status"] = "success";
            $response["message"] = "Mã xác nhận đã gửi tới email của bạn.";
        } else {
            $response["message"] = "Không thể gửi email. Vui lòng thử lại sau.";
        }
    } elseif ($action === "verify_code") {
        $code_input = $_POST["code"] ?? '';
        $currentTime = time();

        if (
            isset($_SESSION["reset_code"]) &&
            $_SESSION["reset_code"] == $code_input &&
            $_SESSION["reset_email"] == $email &&
            isset($_SESSION["reset_code_expiry"]) &&
            $currentTime <= $_SESSION["reset_code_expiry"]
        ) {

            $newPassword = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 10);
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
            if (!$stmt) {
                $response["message"] = "Lỗi truy vấn CSDL: " . $conn->error;
                echo json_encode($response);
                exit;
            }
            $stmt->bind_param("ss", $hash, $email);
            if (!$stmt->execute()) {
                $response["message"] = "Không thể cập nhật mật khẩu.";
                echo json_encode($response);
                exit;
            }


            $body = "
                <html>
                <head><style>body { font-family: Arial; }</style></head>
                <body>
                    <p>Mật khẩu mới của bạn là: <strong>$newPassword</strong></p>
                    <p>Vui lòng đăng nhập và thay đổi mật khẩu sau khi đăng nhập.</p>
                </body>
                </html>
            ";

            if (sendEmail($email, "Your new password", $body)) {
                $response["status"] = "success";
                $response["message"] = "Mật khẩu mới đã được gửi tới email của bạn.";
                unset($_SESSION["reset_code"], $_SESSION["reset_email"], $_SESSION["reset_code_expiry"]);
            } else {
                $response["message"] = "Không thể gửi email mật khẩu mới.";
            }
        } else {
            $response["status"] = "expired";
            $response["message"] = "Mã xác nhận không đúng hoặc đã hết hạn.";
        }
    } else {
        $response["message"] = "Action không hợp lệ.";
    }
} else {
    $response["message"] = "Phương thức yêu cầu không hợp lệ.";
}

echo json_encode($response);
