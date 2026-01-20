<?php
session_start();

// 检查是否已登录
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

// 检查登录有效期（12小时）
if (isset($_SESSION['login_time'])) {
    $loginTime = $_SESSION['login_time'];
    $currentTime = time();
    $expiryTime = 12 * 3600; // 12小时
    
    if ($currentTime - $loginTime > $expiryTime) {
        session_destroy();
        header('Location: index.php?expired=1');
        exit;
    }
}

// 更新最后活动时间
$_SESSION['last_activity'] = time();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>订单管理 - 章鱼小丸子</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/qrcode.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 15px;
        }
        
        /* 顶部统计栏 - 单行布局 */
        .header-stats {
            background: white;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 15px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: nowrap;
            overflow-x: auto;
        }
        
        /* 统计数据项 - 水平排列 */
        .stats-left {
            display: flex;
            gap: 20px;
            flex: 1;
            min-width: 0;
            align-items: center;
        }
        
        .stat-item {
            text-align: center;
            padding: 10px 15px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            transition: all 0.3s;
            min-width: 120px;
            white-space: nowrap;
        }
        
        .stat-item:hover {
            background: linear-gradient(135deg, #e9ecef, #dee2e6);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .stat-label {
            display: block;
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .stat-value {
            display: block;
            font-size: 1.1rem;
            font-weight: bold;
            line-height: 1.2;
        }
        
        #refresh-time {
            color: #2196F3;
            cursor: pointer;
            transition: all 0.3s;
            padding: 4px 8px;
            border-radius: 6px;
            background: #e3f2fd;
            display: inline-block;
            min-width: 60px;
            font-size: 1rem;
        }
        
        #refresh-time:hover {
            background: #bbdefb;
            transform: scale(1.05);
        }
        
        #total-revenue {
            color: #4CAF50;
        }
        
        #pending-total-orders {
            color: #ff9800;
        }
        
        /* 右侧按钮区域 - 水平排列 */
        .shop-controls {
            display: flex;
            gap: 12px;
            flex-shrink: 0;
            align-items: center;
        }
        
        .shop-btn {
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 0.9rem;
            white-space: nowrap;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            letter-spacing: 0.5px;
            min-width: 100px;
        }
        
        .qr-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .qr-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .close-btn {
            background: linear-gradient(135deg, #ff5e62 0%, #ff9966 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 94, 98, 0.3);
        }
        
        .close-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 94, 98, 0.4);
        }
        
        /* Tab切换 - 横向flex平均分布 */
        .tabs-container {
            background: white;
            border-radius: 12px;
            margin-bottom: 15px;
            display: flex;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .tab {
            flex: 1;
            padding: 16px 10px;
            background: #f8f9fa;
            border: none;
            border-right: 1px solid #e0e0e0;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            text-align: center;
            transition: all 0.3s;
            font-size: 0.95rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .tab:last-child {
            border-right: none;
        }
        
        .tab.active {
            background: white;
            color: #ff5e62;
            position: relative;
        }
        
        .tab.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #ff5e62, #ff9966);
        }
        
        /* 订单内容区域 */
        .content-area {
            height: calc(100vh - 230px);
            overflow-y: auto;
            padding-right: 5px;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* 优化后的订单列表 */
        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .order-item {
            background: white;
            border-radius: 12px;
            padding: 15px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.2s;
        }
        
        .order-item.pending {
            border-left: 4px solid #ff9800;
        }
        
        .order-item.completed {
            border-left: 4px solid #4CAF50;
        }
        
        .order-item:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        /* 订单头部：取餐码 + 时间 + 状态 */
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .order-id-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .order-id {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            background: #f8f9fa;
            padding: 8px 15px;
            border-radius: 8px;
            min-width: 100px;
            text-align: center;
        }
        
        .order-time-status {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .order-time {
            font-size: 0.9rem;
            color: #666;
        }
        
        .order-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            text-align: center;
            min-width: 80px;
        }
        
        .status-pending {
            background: #fff3e0;
            color: #ff9800;
        }
        
        .status-completed {
            background: #e8f5e9;
            color: #4CAF50;
        }
        
        /* 订单详细信息 */
        .order-details {
            margin-bottom: 15px;
        }
        
        .detail-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 0;
        }
        
        .detail-label {
            color: #666;
            font-size: 0.9rem;
            min-width: 60px;
            font-weight: 500;
        }
        
        .detail-value {
            color: #333;
            font-size: 1rem;
            font-weight: 500;
            flex: 1;
        }
        
        .detail-value .portion {
            color: #ff5e62;
            font-weight: bold;
        }
        
        .detail-value .flavors {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        
        .flavor-tag {
            background: #e3f2fd;
            color: #1976d2;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.85rem;
        }
        
        .detail-value .toppings {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        
        .topping-tag {
            background: #f3e5f5;
            color: #7b1fa2;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.85rem;
        }
        
        .detail-price {
            font-size: 1.2rem;
            font-weight: bold;
            color: #ff5e62;
            text-align: right;
            margin-top: 5px;
        }
        
        /* 订单操作按钮 */
        .order-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            padding-top: 15px;
            border-top: 1px dashed #f0f0f0;
        }
        
        .action-btn {
            padding: 8px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.3s;
            min-width: 100px;
        }
        
        .serve-btn {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
        }
        
        .serve-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
        }
        
        .delete-btn {
            background: linear-gradient(135deg, #ff5e62, #ff4757);
            color: white;
        }
        
        .delete-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 94, 98, 0.3);
        }
        
        /* 二维码弹窗 */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .modal-content {
            background-color: white;
border-radius: 15px;
            width: 100%;
            max-width: 450px;
            animation: modalSlideIn 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
        }
        
        .modal-header h2 {
            color: #333;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .close-modal {
            font-size: 1.8rem;
            cursor: pointer;
            color: #999;
            transition: color 0.3s;
            line-height: 1;
        }
        
        .close-modal:hover {
            color: #333;
        }
        
        .modal-body {
            padding: 25px;
            text-align: center;
        }
        
        .modal-footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .modal-btn {
            padding: 12px 25px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 10px;
        }
        
        .modal-btn:hover {
            background-color: #45a049;
            transform: translateY(-2px);
        }
        
        .cancel-btn {
            background-color: #999;
        }
        /* 移除导出按钮样式 */
        .export-btn {
            display: none;
        }
        
        .download-btn {
            display: none;
        }
        
        .cancel-btn:hover {
            background-color: #777;
        }
        
        .delete-btn-modal {
            background-color: #f44336;
        }
        
        .delete-btn-modal:hover {
            background-color: #d32f2f;
        }
        
        .update-btn {
            background: linear-gradient(135deg, #2196F3, #1976d2);
        }
        
        .update-btn:hover {
            background: linear-gradient(135deg, #1976d2, #1565c0);
        }
        
        .download-btn {
            background: linear-gradient(135deg, #9c27b0, #7b1fa2);
        }
        
        .download-btn:hover {
            background: linear-gradient(135deg, #7b1fa2, #6a1b9a);
        }
        
        /* 二维码容器 */
        #qrcode-container {
            margin: 20px auto;
            padding: 25px;
            background: white;
            border-radius: 12px;
            display: inline-block;
            border: 1px solid #eee;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .qr-info {
            margin-top: 20px;
            color: #666;
            font-size: 0.95rem;
        }
        
        .qr-expiry {
            color: #ff9800;
            font-weight: 600;
            margin-top: 8px;
            font-size: 1rem;
            padding: 8px 15px;
            background: #fff3e0;
            border-radius: 8px;
            display: inline-block;
        }
        
        .qr-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 20px;
        }
        
        /* 其他样式 */
        .loading {
            text-align: center;
            padding: 60px;
            color: #666;
            font-size: 1.1rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 12px;
            margin-top: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        
        .empty-icon {
            font-size: 3.5rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .empty-state h3 {
            color: #666;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .empty-state p {
            color: #999;
            font-size: 1rem;
        }
        
        .history-filters {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-group label {
            font-size: 0.95rem;
            color: #666;
            white-space: nowrap;
            font-weight: 500;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
min-width: 140px;
            background: white;
        }
        
        .filter-btn {
            padding: 10px 25px;
            background: linear-gradient(135deg, #2196F3, #1976d2);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
        }
        
        .export-btn {
            background: linear-gradient(135deg, #9c27b0, #7b1fa2);
            margin-left: auto;
        }
        
        .analytics-panel {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        
        /* 响应式调整 */
        @media (max-width: 992px) {
            .header-stats {
                flex-direction: row;
                gap: 15px;
                overflow-x: auto;
                padding: 12px 15px;
            }
            
            .stats-left {
                flex-wrap: nowrap;
                gap: 15px;
            }
            
            .stat-item {
                min-width: 100px;
                padding: 8px 12px;
            }
            
            .shop-controls {
                flex-shrink: 0;
            }
            
            .shop-btn {
                min-width: 90px;
                padding: 8px 12px;
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 768px) {
            .header-stats {
                flex-direction: column;
                gap: 15px;
            }
            
            .stats-left {
                width: 100%;
                justify-content: space-between;
                flex-wrap: wrap;
            }
            
            .stat-item {
                flex: 1;
                min-width: 100px;
            }
            
            .shop-controls {
                width: 100%;
                justify-content: center;
            }
        }
        
        @media (max-width: 576px) {
            .stats-left {
                flex-direction: column;
                gap: 10px;
            }
            
            .stat-item {
                width: 100%;
            }
            
            .shop-controls {
                flex-direction: column;
                width: 100%;
            }
            
            .shop-btn {
                width: 100%;
            }
        }
        
            .tabs-container {
                flex-direction: column;
            }
            
            .tab {
                border-right: none;
                border-bottom: 1px solid #e0e0e0;
            }
            
            .tab.active::after {
                top: 0;
                bottom: auto;
                height: 3px;
            }
            
            .detail-value .flavors,
            .detail-value .toppings {
                justify-content: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 顶部统计栏 -->
        <div class="header-stats">
            <div class="stats-left">
                <div class="stat-item">
                    <span class="stat-label">刷新时间</span>
                    <span class="stat-value" id="refresh-time">0s前</span>
                </div>
                
                <div class="stat-item">
                    <span class="stat-label">今日营业额</span>
                    <span class="stat-value" id="total-revenue">0元</span>
                </div>
                
                <div class="stat-item">
                    <span class="stat-label">待处理/总单量</span>
                    <span class="stat-value" id="pending-total-orders">0/0</span>
                </div>
            </div>
            
            <div class="shop-controls">
                <button class="shop-btn qr-btn" id="view-qr">
                    <span class="btn-icon">👁️</span>
                    <span>查看二维码</span>
                </button>
                <button class="shop-btn close-btn" id="close-shop">
                    <span class="btn-icon">🔒</span>
                    <span>闭店</span>
                </button>
            </div>
        </div>
        
        <!-- Tab切换 -->
        <div class="tabs-container">
            <button class="tab active" data-tab="today-orders">📋 当日订单</button>
            <button class="tab" data-tab="history-orders">📊 历史订单</button>
            <button class="tab" data-tab="analytics">🤖 AI分析</button>
        </div>
        
        <!-- 内容区域 -->
        <div class="content-area">
            <!-- 当日订单Tab -->
            <div class="tab-content active" id="today-orders">
                <div class="orders-list" id="orders-list">
                    <div class="loading">正在加载订单...</div>
                </div>
                
                <div class="empty-state" id="empty-state" style="display: none;">
                    <div class="empty-icon">📋</div>
                    <h3>暂无订单</h3>
                    <p>当有顾客下单后，订单会显示在这里</p>
                </div>
            </div>
            
            <!-- 历史订单Tab -->
            <div class="tab-content" id="history-orders">
                <div class="history-filters">
                    <div class="filter-group">
                        <label>年份：</label>
                        <select id="filter-year">
                            <option value="">全部</option>
                            <option value="2023">2023</option>
                            <option value="2024" selected>2024</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>月份：</label>
                        <select id="filter-month">
                            <option value="">全部</option>
                            <option value="01">1月</option>
                            <option value="02">2月</option>
                            <option value="03">3月</option>
                            <option value="04">4月</option>
                            <option value="05">5月</option>
                            <option value="06">6月</option>
                            <option value="07">7月</option>
                            <option value="08">8月</option>
                            <option value="09">9月</option>
                            <option value="10">10月</option>
                            <option value="11">11月</option>
                            <option value="12">12月</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>日期：</label>
                        <input type="date" id="filter-date">
                    </div>
                    
                    <button class="filter-btn" id="apply-history-filter">查询</button>
                    <button class="filter-btn export-btn" id="export-history">导出数据</button>
                </div>
                
                <div class="orders-list" id="history-orders-list">
                    <div class="loading">请选择筛选条件查询历史订单</div>
                </div>
                
                <div class="empty-state" id="history-empty-state" style="display: none;">
                    <div class="empty-icon">📊</div>
                    <h3>无历史订单数据</h3>
                    <p>请调整筛选条件重新查询</p>
                </div>
            </div>
            
            <!-- AI数据分析Tab -->
            <div class="tab-content" id="analytics">
                <div class="analytics-panel">
                    <div class="analytics-header">
                        <h2>📈 智能数据分析</h2>
                        <button class="filter-btn" id="refresh-analytics">刷新分析</button>
                    </div>
                    
                    <div class="analytics-grid" id="analytics-grid">
                        <div class="loading">正在生成分析报告...</div>
                    </div>
                    
                    <div class="analytics-suggestions" id="analytics-suggestions">
                        <h4>💡 智能建议</h4>
                        <div class="loading">正在分析数据...</div>
                    </div>
                    
                    <div class="refresh-time" id="analytics-time">上次更新: --</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 二维码弹窗 -->
    <div class="modal" id="qr-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>
                    <span>🏪</span>
                    店铺二维码
                </h2>
                <span class="close-modal" id="close-qr-modal">&times;</span>
            </div>
            <div class="modal-body">
                <div id="qrcode-container">
                    <div class="loading">正在生成二维码...</div>
                </div>
                <div class="qr-info">
                    <p>请让顾客扫描此二维码进入点餐页面</p>
                    <p class="qr-expiry" id="qr-expiry">有效期至：--</p>
                </div>
                <div class="qr-actions">
                    <button class="modal-btn update-btn" id="update-qr">更新二维码</button>
                    <!-- 移除下载二维码按钮 -->
                    <button class="modal-btn cancel-btn" id="close-qr">关闭</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 删除确认弹窗 -->
    <div class="modal" id="delete-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>确认删除订单</h2>
                <span class="close-modal" id="close-delete-modal">&times;</span>
            </div>
            <div class="modal-body">
                <p>您确定要删除订单 <span id="delete-order-id"></span> 吗？</p>
                <p style="color: #f44336; font-weight: bold; margin: 10px 0;">此操作不可撤销！</p>
                <div class="modal-footer">
                    <button class="modal-btn cancel-btn" id="cancel-delete">取消</button>
                    <button class="modal-btn delete-btn-modal" id="confirm-delete">确认删除</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 闭店确认弹窗 -->
    <div class="modal" id="close-shop-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>确认闭店</h2>
                <span class="close-modal" id="close-shop-modal-btn">&times;</span>
            </div>
            <div class="modal-body">
                <p>确认要闭店吗？</p>
                <p style="color: #f44336; font-weight: bold; margin: 10px 0;">闭店后所有二维码将失效，顾客无法下单！</p>
                <div class="modal-footer">
                    <button class="modal-btn cancel-btn" id="cancel-close-shop">取消</button>
                    <button class="modal-btn delete-btn-modal" id="confirm-close-shop">确认闭店</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/seller.js"></script>
</body>
</html>