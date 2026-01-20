<?php
session_start();
require_once '../config.php'; // 引入配置文件
header('Content-Type: application/json; charset=utf-8');

// 检查登录状态和有效期
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
  echo json_encode(['success' => false, 'message' => '请先登录']);
  exit;
}

// 检查登录有效期（12小时）
if (isset($_SESSION['login_time'])) {
  $loginTime = $_SESSION['login_time'];
  $currentTime = time();
  $expiryTime = 12 * 3600; // 12小时

  if ($currentTime - $loginTime > $expiryTime) {
    session_destroy();
    echo json_encode(['success' => false, 'message' => '登录已过期，请重新登录']);
    exit;
  }
}

// 确保data目录存在
if (!file_exists('../data')) {
  mkdir('../data', 0777, true);
}

// 二维码数据文件
$qrFile = '../data/qr_data.json';

// 生成随机密钥（用于验证二维码有效性）
$secretKey = bin2hex(random_bytes(16));
$timestamp = time();

// 生成二维码数据
$qrData = [
  'secret_key' => $secretKey,
  'generated_at' => $timestamp,
  'expires_at' => $timestamp + (24 * 3600), // 24小时后过期
  'generated_by' => $_SESSION['username'] ?? 'admin'
];

// 调试信息（生产环境请注释掉）
$debugInfo = [
  'host' => $_SERVER['HTTP_HOST'],
  'script_name' => $_SERVER['PHP_SELF'],
  'request_uri' => $_SERVER['REQUEST_URI'],
  'document_root' => $_SERVER['DOCUMENT_ROOT']
];


// 保存到文件
if (file_put_contents($qrFile, json_encode($qrData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))) {
  // 同时确保店铺状态为开启
  $shopStatusFile = '../data/shop_status.json';
  $shopStatus = ['is_open' => true, 'last_opened' => $timestamp];
  file_put_contents($shopStatusFile, json_encode($shopStatus, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

  // 生成二维码链接
  $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
  $host = $_SERVER['HTTP_HOST'];

  // 计算正确的买家端路径
  // 方法1：直接使用相对路径
  $basePath = dirname($_SERVER['PHP_SELF']); // 获取当前目录
  $basePath = dirname($basePath); // 向上一级
  $basePath = rtrim($basePath, '/');

  // 方法2：如果方法1不行，尝试直接指定
  // $basePath = ''; // 如果是根目录

  $qrUrl = $protocol . $host . $basePath . "/buyer.php?key=" . urlencode($secretKey);

  echo json_encode([
    'success' => true,
    'qr_url' => $qrUrl,
    'secret_key' => $secretKey,
    'expires_at' => date('Y-m-d H:i:s', $qrData['expires_at']),
    'message' => '二维码生成成功，请在24小时内使用',
    'debug' => $debugInfo, // 调试信息
    'base_path' => $basePath // 调试信息
  ], JSON_UNESCAPED_UNICODE);
} else {
  echo json_encode(['success' => false, 'message' => '生成二维码失败']);
}
?>