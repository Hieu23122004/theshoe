<?php
session_start();
include '../includes/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to continue!']);
    exit;
}

$user_id = $_SESSION['user_id'];
$fullname = $_POST['fullname'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$address = $_POST['address'] ?? '';
$province = $_POST['province'] ?? '';
$district = $_POST['district'] ?? '';
$ward = $_POST['ward'] ?? '';
$discount_code = $_POST['discount_code'] ?? '';

// Lấy tên tỉnh, quận, xã nếu là mã code
function get_location_name($type, $code)
{
    if (!$code) return '';
    $url = "https://provinces.open-api.vn/api/";
    switch ($type) {
        case 'province':
            $url .= "p/$code";
            break;
        case 'district':
            $url .= "d/$code";
            break;
        case 'ward':
            $url .= "w/$code";
            break;
        default:
            return '';
    }
    $resp = @file_get_contents($url);
    if ($resp) {
        $data = json_decode($resp, true);
        // Nếu là xã/phường mà tên rỗng thì trả về chuỗi rỗng
        if ($type === 'ward' && empty($data['name'])) return '';
        return mb_strtolower($data['name'] ?? '', 'UTF-8');
    }
    return '';
}
$province_name = get_location_name('province', $province);
$district_name = get_location_name('district', $district);
$ward_name = get_location_name('ward', $ward);

// Nếu vẫn không lấy được tên xã/phường, thử lấy lại bằng cách duyệt danh sách wards của district
if ($ward && $ward_name === '') {
    $url = "https://provinces.open-api.vn/api/d/$district?depth=2";
    $resp = @file_get_contents($url);
    if ($resp) {
        $data = json_decode($resp, true);
        if (!empty($data['wards'])) {
            foreach ($data['wards'] as $w) {
                if ((string)$w['code'] === (string)$ward) {
                    $ward_name = mb_strtolower($w['name'], 'UTF-8');
                    break;
                }
            }
        }
    }
}
// Gộp shipping_address đúng chuẩn, KHÔNG gán shipping_method là địa chỉ!
$shipping_address = $address;
if ($ward_name !== '') $shipping_address .= ', ' . $ward_name;
if ($district_name !== '') $shipping_address .= ', ' . $district_name;
if ($province_name !== '') $shipping_address .= ', ' . $province_name;
$shipping_address = trim($shipping_address, ', ');

// shipping_method lấy đúng từ POST, không phải địa chỉ!
$shipping_method = $_POST['shipping_method'] ?? 'Standard Delivery';
$payment_method = $_POST['payment_method'] ?? 'COD';
$bank_name = $_POST['bank_name'] ?? '';
$total_amount = $_POST['total_amount'] ?? 0;
$order_details = $_POST['order_details'] ?? '';

// Nếu chọn chuyển khoản ngân hàng, lưu tên ngân hàng cụ thể vào payment_method
if (strtolower($payment_method) === 'bank' && $bank_name) {
    $payment_method = $bank_name;
}

if (!$order_details) {
    echo json_encode(['success' => false, 'message' => 'Missing cart information!']);
    exit;
}

// Kiểm tra và xử lý mã giảm giá nếu có
$discount_code_id = null;
if ($discount_code) {
    $now = date('Y-m-d H:i:s');
    
    // Bắt đầu transaction để đảm bảo tính nhất quán
    $conn->begin_transaction();
    
    try {
        // Kiểm tra mã giảm giá với FOR UPDATE để lock record
        $stmt = $conn->prepare("SELECT code_id, max_uses, used_count FROM discount_codes WHERE code = ? AND (valid_from IS NULL OR valid_from <= ?) AND (valid_until IS NULL OR valid_until >= ?) FOR UPDATE");
        $stmt->bind_param('sss', $discount_code, $now, $now);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Kiểm tra số lần sử dụng
            if ($row['max_uses'] === null || $row['used_count'] < $row['max_uses']) {
                $discount_code_id = $row['code_id'];
            } else {
                $stmt->close();
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Discount code has been used up!']);
                exit;
            }
        } else {
            $stmt->close();
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Invalid discount code!']);
            exit;
        }
        $stmt->close();
        
        // Thêm thông tin mã giảm giá vào order_details nếu có
        $order_data = json_decode($order_details, true);
        if ($discount_code) {
            $order_data['discount_code'] = $discount_code;
            $order_details = json_encode($order_data);
        }
        
        // Tạo đơn hàng
        $stmt = $conn->prepare("INSERT INTO orders (user_id, shipping_address, shipping_method, payment_method, total_amount, order_details, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
        $stmt->bind_param('isssds', $user_id, $shipping_address, $shipping_method, $payment_method, $total_amount, $order_details);
        $ok = $stmt->execute();
        $order_id = $stmt->insert_id;
        $stmt->close();
        
        if (!$ok) {
            $conn->rollback();
            // Log lỗi chi tiết để debug
            error_log("Order creation failed: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Order creation failed: ' . $conn->error]);
            exit;
        }
        
        // Cập nhật số lần sử dụng mã giảm giá
        if ($discount_code_id) {
            $stmt = $conn->prepare("UPDATE discount_codes SET used_count = used_count + 1 WHERE code_id = ?");
            $stmt->bind_param('i', $discount_code_id);
            if (!$stmt->execute()) {
                $stmt->close();
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Cannot update discount code! Error: ' . $conn->error]);
                exit;
            }
            $stmt->close();
        }
        
        // Xóa giỏ hàng
        $conn->query("DELETE FROM cart_items WHERE user_id = $user_id");
        
        // Commit transaction
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Order placed successfully!', 'order_id' => $order_id]);

    } catch (Exception $e) {
        $conn->rollback();
        // Log lỗi chi tiết để debug
        error_log("Order transaction failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Cannot complete order! Error: ' . $e->getMessage()]);
        exit;
    }
} else {
    // Không có mã giảm giá, xử lý bình thường
    $stmt = $conn->prepare("INSERT INTO orders (user_id, shipping_address, shipping_method, payment_method, total_amount, order_details, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
    $stmt->bind_param('isssds', $user_id, $shipping_address, $shipping_method, $payment_method, $total_amount, $order_details);
    $ok = $stmt->execute();
    $order_id = $stmt->insert_id;
    $stmt->close();

    if ($ok) {
        // Xóa giỏ hàng sau khi đặt hàng thành công
        $conn->query("DELETE FROM cart_items WHERE user_id = $user_id");
        echo json_encode(['success' => true, 'message' => 'Order placed successfully!', 'order_id' => $order_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Cannot complete order! Error: ' . $conn->error]);
    }
}
