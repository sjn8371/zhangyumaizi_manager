<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// 订单数据文件路径
$ordersFile = '../data/orders.json';

// 获取订单号
$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($orderId < 1000 || $orderId > 9999) {
    echo json_encode(['success' => false, 'message' => '无效的订单号'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 加载订单
if (file_exists($ordersFile)) {
    $orders = json_decode(file_get_contents($ordersFile), true);
    if (!is_array($orders)) {
        $orders = [];
    }
} else {
    $orders = [];
}

// 查找订单
$foundOrder = null;
foreach ($orders as $order) {
    if (isset($order['id']) && $order['id'] == $orderId) {
        $foundOrder = $order;
        break;
    }
}

if ($foundOrder) {
    echo json_encode([
        'success' => true,
        'order' => $foundOrder
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'success' => false,
        'message' => '未找到该订单'
    ], JSON_UNESCAPED_UNICODE);
}
?>