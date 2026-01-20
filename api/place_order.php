<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 检查店铺状态
$shopStatus = json_decode(file_get_contents('../data/shop_status.json'), true);
if (!$shopStatus['is_open']) {
    echo json_encode(['success' => false, 'message' => '店铺已关闭，无法下单']);
    exit;
}

// 获取POST数据
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => '无效的请求数据']);
    exit;
}

// 验证必填字段
if (!isset($data['portion']) || !in_array($data['portion'], ['small', 'large'])) {
    echo json_encode(['success' => false, 'message' => '请选择正确的分量']);
    exit;
}

// 生成订单号
$order_id = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);

// 读取现有订单
$orders = json_decode(file_get_contents('../data/orders.json'), true);

// 创建新订单
$newOrder = [
    'order_id' => $order_id,
    'portion' => $data['portion'],
    'flavors' => $data['flavors'] ?? [],
    'toppings' => $data['toppings'] ?? [],
    'timestamp' => $data['timestamp'],
    'status' => 'pending',
    'date' => date('Y-m-d')
];

// 添加到订单列表
$orders[] = $newOrder;

// 更新订单数据文件
if (file_put_contents('../data/orders.json', json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    // 更新店铺统计数据
    updateShopStats($newOrder);
    
    echo json_encode([
        'success' => true,
        'order' => $newOrder,
        'message' => '下单成功'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => '保存订单失败']);
}

function updateShopStats($order) {
    $shopStatus = json_decode(file_get_contents('../data/shop_status.json'), true);
    $config = json_decode(file_get_contents('../data/config.json'), true);
    
    // 检查是否是当天的订单
    if ($order['date'] === date('Y-m-d')) {
        $price = $config['prices'][$order['portion']];
        
        $shopStatus['daily_sales'] += $price;
        $shopStatus['daily_orders']++;
        
        file_put_contents('../data/shop_status.json', json_encode($shopStatus, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
?>