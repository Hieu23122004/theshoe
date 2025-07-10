<?php
// Trả về tên tỉnh, quận, xã từ mã code (API provinces.open-api.vn)
header('Content-Type: application/json');
$type = $_GET['type'] ?? '';
$code = $_GET['code'] ?? '';
if (!$type || !$code) {
    echo json_encode(['name' => '']);
    exit;
}
$url = '';
switch($type) {
    case 'province':
        $url = "https://provinces.open-api.vn/api/p/$code";
        break;
    case 'district':
        $url = "https://provinces.open-api.vn/api/d/$code";
        break;
    case 'ward':
        $url = "https://provinces.open-api.vn/api/w/$code";
        break;
    default:
        echo json_encode(['name' => '']);
        exit;
}
$resp = @file_get_contents($url);
if ($resp) {
    $data = json_decode($resp, true);
    echo json_encode(['name' => $data['name'] ?? '']);
} else {
    echo json_encode(['name' => '']);
}
