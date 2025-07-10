<?php
session_start();
include '../includes/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to continue!']);
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
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin giỏ hàng!']);
    exit;
}


$stmt = $conn->prepare("INSERT INTO orders (user_id, shipping_address, shipping_method, payment_method, total_amount, order_details, status) VALUES (?, ?, ?, ?, ?, ?, 'Processing')");
$stmt->bind_param('isssds', $user_id, $shipping_address, $shipping_method, $payment_method, $total_amount, $order_details);
$ok = $stmt->execute();
$order_id = $stmt->insert_id;
$stmt->close();

if ($ok) {
    // Xóa giỏ hàng sau khi đặt hàng thành công
    $conn->query("DELETE FROM cart_items WHERE user_id = $user_id");
    echo json_encode(['success' => true, 'message' => 'Order Placed Successfully!', 'order_id' => $order_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Order could not be completed!']);
}
