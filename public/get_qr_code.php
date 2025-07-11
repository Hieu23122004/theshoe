<?php
// get_qr_code.php
header('Content-Type: application/json');
$bank = isset($_POST['bank']) ? $_POST['bank'] : '';
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;

// Thông tin tài khoản mẫu (bạn thay bằng tài khoản thật)
$accounts = [
    'vcb' => [
        'account' => '9999999999',
        'name' => 'Diep Van Huy',
        'bank_code' => 'VCB',
    ],
    'tcb' => [
        'account' => '9999999999',
        'name' => 'Nguyen Huu Hieu',
        'bank_code' => 'TCB',
    ],
    'mb' => [
        'account' => '9999999999', // Số tài khoản MB thật của bạn
        'name' => 'Nguyen Tien Dung', // Bạn có thể thay bằng tên thật nếu muốn
        'bank_code' => 'MB',
    ],
    'bidv' => [
        'account' => '9999999999',
        'name' => 'Tran Vu Duc Luong',
        'bank_code' => 'BIDV',
    ],
];
if (!isset($accounts[$bank]) || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid bank or amount']);
    exit;
}
$acc = $accounts[$bank];
// Tạo link QR động qua vnpay/zalopay hoặc dịch vụ bên thứ 3, ở đây demo bằng API vban.vn
$qr_url = "https://img.vietqr.io/image/{$acc['bank_code']}-{$acc['account']}-compact2.png?amount={$amount}&addInfo=THANH+TOAN+DON+HANG&accountName=".urlencode($acc['name']);
echo json_encode(['success' => true, 'qr_url' => $qr_url]);