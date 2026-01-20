<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 检查店铺状态
$shopStatusFile = '../data/shop_status.json';
if (file_exists($shopStatusFile)) {
    $shopStatus = json_decode(file_get_contents($shopStatusFile), true);
    if (isset($shopStatus['is_open']) && $shopStatus['is_open'] === false) {
        echo json_encode(['valid' => false, 'message' => '店铺已关闭，暂时无法下单']);
        exit;
    }
}

// 二维码数据文件
$qrFile = '../data/qr_data.json';

// 获取密钥参数
$key = $_GET['key'] ?? '';

if (empty($key)) {
    echo json_encode(['valid' => false, 'message' => '无效的二维码']);
    exit;
}

// 检查二维码数据文件是否存在
if (!file_exists($qrFile)) {
    echo json_encode(['valid' => false, 'message' => '二维码已失效，请联系商家获取新的二维码']);
    exit;
}

// 读取二维码数据
$qrData = json_decode(file_get_contents($qrFile), true);

// 验证密钥和有效期
if (!isset($qrData['secret_key']) || $qrData['secret_key'] !== $key) {
    echo json_encode(['valid' => false, 'message' => '二维码已过期，请联系商家获取新的二维码']);
    exit;
}

if (time() > $qrData['expires_at']) {
    echo json_encode(['valid' => false, 'message' => '二维码已过期，请联系商家获取新的二维码']);
    exit;
}

// 验证通过
echo json_encode([
    'valid' => true,
    'message' => '验证通过'
]);
?>