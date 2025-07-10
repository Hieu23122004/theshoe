<?php
// get_qr_code.php
header('Content-Type: application/json');
$bank = isset($_POST['bank']) ? $_POST['bank'] : '';
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;

// Thông tin tài khoản mẫu (bạn thay bằng tài khoản thật)
$accounts = [
    'vcb' => [
        'account' => '0123456789',
        'name' => 'NGUYEN VAN A',
        'bank_code' => 'VCB',
    ],
    'tcb' => [
        'account' => '1234567890',
        'name' => 'TRAN VAN B',
        'bank_code' => 'TCB',
    ],
    'mb' => [
        'account' => '0373327816', // Số tài khoản MB thật của bạn
        'name' => 'MB BANK', // Bạn có thể thay bằng tên thật nếu muốn
        'bank_code' => 'MB',
    ],
    'bidv' => [
        'account' => '3456789012',
        'name' => 'PHAM VAN D',
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
