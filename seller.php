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
            max-width: 800px;
            margin: 0 auto;
            padding: 15px;
        }
        
        /* 顶部统计栏 */
        .header-stats {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 15px;
            align-items: center;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-label {
            display: block;
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 4px;
        }
        
        .stat-value {
            display: block;
            font-size: 1.1rem;
            font-weight: bold;
        }
        
        #refresh-time {
            color: #2196F3;
            cursor: pointer;
            transition: all 0.3s;
            padding: 5px 10px;
            border-radius: 6px;
        }
        
        #refresh-time:hover {
            background: #e3f2fd;
        }
        
        #total-revenue {
            color: #4CAF50;
        }
        
        #pending-total-orders {
            color: #ff9800;
        }
        
        .shop-controls {
            display: flex;
            gap: 10px;
        }
        
        .shop-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            font-size: 0.9rem;
            white-space: nowrap;
        }
        
        .qr-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .qr-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .close-btn {
            background: linear-gradient(135deg, #ff5e62, #ff9966);
            color: white;
        }
        
        .close-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 94, 98, 0.3);
        }
        
        /* Tab切换样式 */
        .tabs-container {
            background: white;
            border-radius: 12px;
            margin-bottom: 15px;
            padding: 5px;
            display: flex;
            gap: 2px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .tab {
            flex: 1;
            padding: 12px;
            background: #f8f9fa;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            text-align: center;
            transition: all 0.3s;
        }
        
        .tab.active {
            background: white;
            color: #ff5e62;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        /* 订单内容区域 */
        .content-area {
            height: calc(100vh - 220px);
            overflow-y: auto;
            padding-right: 5px;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* 扁平化订单列表 */
        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .order-item {
            background: white;
            border-radius: 10px;
            padding: 12px 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 12px;
            align-items: center;
            border-left: 4px solid #4CAF50;
            transition: all 0.2s;
        }
        
        .order-item.pending {
            border-left-color: #ff9800;
        }
        
        .order-item.completed {
            border-left-color: #2196F3;
        }
        
        .order-item:hover {
            box-shadow: 0 3px 6px rgba(0,0,0,0.12);
            transform: translateY(-1px);
        }
        
        .order-id {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
            width: 65px;
            text-align: center;
        }
        
        .order-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .order-line {
            display: flex;
            gap: 8px;
            align-items: center;
            font-size: 0.9rem;
        }
        
        .order-label {
            color: #666;
            min-width: 25px;
        }
        
        .order-value {
            color: #333;
        }
        
        .order-actions {
            display: flex;
            gap: 6px;
        }
        
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s;
        }
        
        .complete-btn {
            background: #4CAF50;
            color: white;
        }
        
        .complete-btn:hover {
            background: #45a049;
        }
        
        .delete-btn {
            background: #ff5e62;
            color: white;
        }
        
        .delete-btn:hover {
            background: #e53935;
        }
        
        .order-price {
            font-weight: bold;
            color: #ff5e62;
            font-size: 1rem;
            text-align: right;
            min-width: 50px;
        }
        
        .order-time {
            font-size: 0.8rem;
            color: #999;
            margin-top: 2px;
        }
        
        /* 模态框样式 */
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
            max-width: 400px;
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
            font-size: 1.3rem;
        }
        
        .close-modal {
            font-size: 1.8rem;
            cursor: pointer;
            color: #999;
            transition: color 0.3s;
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
            padding: 12px 30px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
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
        
        .cancel-btn:hover {
            background-color: #777;
        }
        
        .delete-btn-modal {
            background-color: #f44336;
        }
        
        .delete-btn-modal:hover {
            background-color: #d32f2f;
        }
        
        .download-btn {
            background-color: #2196F3;
        }
        
        .download-btn:hover {
            background-color: #1976d2;
        }
        
        /* 二维码样式 */
        #qrcode-container {
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            display: inline-block;
        }
        
        .qr-info {
            margin-top: 15px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .qr-expiry {
            color: #ff9800;
            font-weight: 500;
            margin-top: 5px;
        }
        
        /* 加载状态 */
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.3;
        }
        
        .empty-state h3 {
            color: #666;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #999;
        }
        
        /* 历史订单筛选 */
        .history-filters {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-group label {
            font-size: 0.9rem;
            color: #666;
            white-space: nowrap;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 8px 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 0.9rem;
            min-width: 120px;
        }
        
        .filter-btn {
            padding: 8px 16px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .export-btn {
            background: #2196F3;
            margin-left: auto;
        }
        
        /* AI分析面板 */
        .analytics-panel {
            background: white;
            border-radius: 12px;
            padding: 20px;
        }
        
        .analytics-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .analytics-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        
        .analytics-card h4 {
            color: #333;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }
        
        .analytics-card .value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #ff5e62;
        }
        
        .analytics-suggestions {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #2196F3;
        }
        
        .analytics-suggestions h4 {
            color: #1976d2;
            margin-bottom: 10px;
        }
        
        .analytics-suggestions ul {
            padding-left: 20px;
            color: #333;
        }
        
        .analytics-suggestions li {
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        .refresh-time {
            font-size: 0.8rem;
            color: #888;
            text-align: right;
            margin-top: 10px;
        }
        
        /* 响应式调整 */
        @media (max-width: 768px) {
            .header-stats {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .shop-controls {
                grid-column: 1;
                justify-content: center;
            }
            
            .order-item {
                grid-template-columns: 1fr;
                gap: 8px;
            }
            
            .order-id {
                width: auto;
                text-align: left;
            }
            
            .order-actions {
                justify-content: flex-start;
            }
            
            .order-price {
                text-align: left;
            }
            
            .history-filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .export-btn {
                margin-left: 0;
                width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            .shop-controls {
                flex-direction: column;
                width: 100%;
            }
            
            .shop-btn {
                width: 100%;
            }
            
            .tabs-container {
                flex-direction: column;
            }
            
            .analytics-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 顶部统计栏 -->
        <div class="header-stats">
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
            
            <div class="shop-controls">
                <button class="shop-btn qr-btn" id="generate-qr">开店生成二维码</button>
                <button class="shop-btn close-btn" id="close-shop">闭店</button>
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
                <h2>店铺二维码</h2>
                <span class="close-modal" id="close-qr-modal">&times;</span>
            </div>
            <div class="modal-body">
                <div id="qrcode-container"></div>
                <div class="qr-info">
                    <p>请让顾客扫描此二维码进入点餐页面</p>
                    <p class="qr-expiry" id="qr-expiry"></p>
                </div>
                <div class="modal-footer">
                    <button class="modal-btn download-btn" id="download-qr">下载二维码</button>
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