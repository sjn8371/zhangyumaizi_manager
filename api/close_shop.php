<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// 检查登录状态和有效期
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

// 检查登录有效期
if (isset($_SESSION['login_time'])) {
    $expiryTime = 12 * 3600;
    if (time() - $_SESSION['login_time'] > $expiryTime) {
        session_destroy();
        echo json_encode(['success' => false, 'message' => '登录已过期']);
        exit;
    }
}

// 确保data目录存在
if (!file_exists('../data')) {
    mkdir('../data', 0777, true);
}

// 更新店铺状态为关闭
$shopStatusFile = '../data/shop_status.json';
$shopStatus = [
    'is_open' => false,
    'closed_at' => time(),
    'closed_by' => $_SESSION['username'] ?? 'admin'
];

if (file_put_contents($shopStatusFile, json_encode($shopStatus, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))) {
    // 同时使所有二维码失效
    $qrFile = '../data/qr_data.json';
    if (file_exists($qrFile)) {
        $qrData = json_decode(file_get_contents($qrFile), true);
        $qrData['expires_at'] = time() - 1; // 设置为过去时间使其立即失效
        file_put_contents($qrFile, json_encode($qrData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
    
    echo json_encode([
        'success' => true,
        'message' => '店铺已关闭，所有二维码已失效'
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success' => false, 'message' => '闭店失败']);
}
?>