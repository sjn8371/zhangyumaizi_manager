<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// 默认账号密码
$valid_username = 'admin';
$valid_password = 'zyxwz';

// 登出操作
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
  session_destroy();
  echo json_encode(['success' => true, 'message' => '已退出登录']);
  exit;
}

// 登录验证
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = $_POST['username'] ?? '';
  $password = $_POST['password'] ?? '';

  if ($username === $valid_username && $password === $valid_password) {
    $_SESSION['loggedin'] = true;
    $_SESSION['username'] = $username;
    $_SESSION['login_time'] = time(); // 记录登录时间

    echo json_encode([
      'success' => true,
      'message' => '登录成功'
    ], JSON_UNESCAPED_UNICODE);
  } else {
    echo json_encode([
      'success' => false,
      'message' => '账号或密码错误'
    ], JSON_UNESCAPED_UNICODE);
  }
} else {
  echo json_encode([
    'success' => false,
    'message' => '无效的请求'
  ], JSON_UNESCAPED_UNICODE);
}
?>