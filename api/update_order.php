<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 确保data目录存在
if (!file_exists('../data')) {
    mkdir('../data', 0777, true);
}

// 订单数据文件路径
$ordersFile = '../data/orders.json';

// 获取POST数据
$input = file_get_contents('php://input');
$updateData = json_decode($input, true);

if (!$updateData || !isset($updateData['id'])) {
    echo json_encode(['success' => false, 'message' => '无效的请求数据'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 加载现有订单
if (file_exists($ordersFile)) {
    $orders = json_decode(file_get_contents($ordersFile), true);
    if (!is_array($orders)) {
        $orders = [];
    }
} else {
    echo json_encode(['success' => false, 'message' => '订单文件不存在'], JSON_UNESCAPED_UNICODE);
    exit;
}

$orderId = $updateData['id'];
$updated = false;
$newOrders = [];

foreach ($orders as $order) {
    if ($order['id'] == $orderId) {
        // 如果是删除操作
        if (isset($updateData['action']) && $updateData['action'] == 'delete') {
            $updated = true;
            continue; // 跳过这个订单，相当于删除
        }
        
        // 如果是更新状态
        if (isset($updateData['status'])) {
            $order['status'] = $updateData['status'];
            $updated = true;
        }
    }
    $newOrders[] = $order;
}

if ($updated) {
    // 保存更新后的订单数据
    if (file_put_contents($ordersFile, json_encode($newOrders, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))) {
        echo json_encode(['success' => true, 'message' => '订单已更新'], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => '保存失败'], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode(['success' => false, 'message' => '订单未找到'], JSON_UNESCAPED_UNICODE);
}
?>