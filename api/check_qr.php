<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// 检查登录状态
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

// 二维码数据文件
$qrFile = '../data/qr_data.json';

// 检查二维码数据文件是否存在
if (!file_exists($qrFile)) {
    echo json_encode(['success' => false, 'message' => '暂无二维码']);
    exit;
}

// 读取二维码数据
$qrData = json_decode(file_get_contents($qrFile), true);

// 检查有效期
if (time() > $qrData['expires_at']) {
    echo json_encode(['success' => false, 'message' => '二维码已过期']);
    exit;
}

// 生成二维码链接
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$basePath = dirname(dirname($_SERVER['PHP_SELF']));
$basePath = rtrim($basePath, '/');
$qrUrl = $protocol . $host . $basePath . "/buyer.php?key=" . urlencode($qrData['secret_key']);

echo json_encode([
    'success' => true,
    'qr_url' => $qrUrl,
    'expires_at' => date('Y-m-d H:i:s', $qrData['expires_at']),
    'generated_at' => date('Y-m-d H:i:s', $qrData['generated_at'])
], JSON_UNESCAPED_UNICODE);
?>