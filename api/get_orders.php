<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// 确保data目录存在
if (!file_exists('../data')) {
    mkdir('../data', 0777, true);
}

// 订单数据文件路径
$ordersFile = '../data/orders.json';

// 加载订单
if (file_exists($ordersFile)) {
    $orders = json_decode(file_get_contents($ordersFile), true);
    if (!is_array($orders)) {
        $orders = [];
    }
} else {
    $orders = [];
}

// 获取请求参数
$type = $_GET['type'] ?? 'today';
$year = $_GET['year'] ?? null;
$month = $_GET['month'] ?? null;
$date = $_GET['date'] ?? null;
$export = isset($_GET['export']);

// 根据类型筛选订单
if ($type === 'today') {
    // 只返回当天的订单
    $today = date('Y-m-d');
    $filteredOrders = array_filter($orders, function($order) use ($today) {
        return date('Y-m-d', strtotime($order['timestamp'])) === $today;
    });
    
    // 计算统计数据
    $total = count($filteredOrders);
    $pending = count(array_filter($filteredOrders, function($order) {
        return isset($order['status']) && $order['status'] === 'pending';
    }));
    $revenue = array_reduce($filteredOrders, function($sum, $order) {
        return $sum + (isset($order['price']) ? intval($order['price']) : 0);
    }, 0);
    
    $response = [
        'success' => true,
        'orders' => array_values($filteredOrders),
        'stats' => [
            'total' => $total,
            'pending' => $pending,
            'revenue' => $revenue
        ]
    ];
} else if ($type === 'history') {
    // 历史订单筛选
    $filteredOrders = array_filter($orders, function($order) use ($year, $month, $date) {
        $orderDate = strtotime($order['timestamp']);
        $orderYear = date('Y', $orderDate);
        $orderMonth = date('m', $orderDate);
        $orderDay = date('Y-m-d', $orderDate);
        
        $match = true;
        if ($year && $orderYear != $year) $match = false;
        if ($month && $orderMonth != $month) $match = false;
        if ($date && $orderDay != $date) $match = false;
        
        return $match;
    });
    
    // 导出功能
    if ($export) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="orders_export.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['订单号', '下单时间', '分量', '口味', '小料', '价格', '状态']);
        
        foreach ($filteredOrders as $order) {
            $flavors = isset($order['flavors']) && is_array($order['flavors']) ? 
                implode(',', $order['flavors']) : '';
            $toppings = isset($order['toppings']) && is_array($order['toppings']) ? 
                implode(',', $order['toppings']) : '';
            
            fputcsv($output, [
                $order['id'],
                $order['timestamp'],
                $order['portion'] ?? '',
                $flavors,
                $toppings,
                $order['price'] ?? 0,
                $order['status'] ?? ''
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    $response = [
        'success' => true,
        'orders' => array_values($filteredOrders)
    ];
}

if (!$export) {
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>