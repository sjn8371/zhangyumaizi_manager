<?php
// 验证二维码
session_start();

// 检查店铺状态
$shopStatusFile = 'data/shop_status.json';
$isShopOpen = true;

if (file_exists($shopStatusFile)) {
    $shopStatus = json_decode(file_get_contents($shopStatusFile), true);
    if (isset($shopStatus['is_open']) && $shopStatus['is_open'] === false) {
        $isShopOpen = false;
    }
}

// 验证二维码有效性
$key = $_GET['key'] ?? '';
$isValidQr = false;

if ($isShopOpen) {
    $qrFile = 'data/qr_data.json';
    
    if (!empty($key) && file_exists($qrFile)) {
        $qrData = json_decode(file_get_contents($qrFile), true);
        
        if (isset($qrData['secret_key']) && $qrData['secret_key'] === $key) {
            // 检查有效期
            if (time() <= $qrData['expires_at']) {
                $isValidQr = true;
                $_SESSION['qr_validated'] = true;
                $_SESSION['qr_key'] = $key;
            }
        }
    }
}

// 如果没有通过二维码验证或店铺已关闭
if ((!$isValidQr && !isset($_SESSION['qr_validated'])) || !$isShopOpen) {
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>二维码已失效</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                margin: 0;
                padding: 20px;
            }
            .error-container {
                background: white;
                padding: 40px;
                border-radius: 15px;
                text-align: center;
                max-width: 400px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
            .error-icon {
                font-size: 3rem;
                margin-bottom: 20px;
            }
            h1 {
                color: #333;
                margin-bottom: 15px;
            }
            p {
                color: #666;
                line-height: 1.6;
                margin-bottom: 20px;
            }
            .shop-closed {
                background: #ffebee;
                padding: 15px;
                border-radius: 8px;
                margin-top: 20px;
                color: #c62828;
                border-left: 4px solid #c62828;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">⚠️</div>
            <h1>无法访问</h1>
            <?php if (!$isShopOpen): ?>
                <div class="shop-closed">
                    <p>店铺已关闭，暂时无法下单</p>
                </div>
            <?php else: ?>
                <p>此二维码已过期或被撤销，请联系商家获取新的二维码。</p>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>点餐 - 章鱼小丸子</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #ff9966, #ff5e62);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            padding: 15px;
        }
        
        .container {
            max-width: 500px;
            margin: 0 auto;
        }
        
        .app-header {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .app-header h1 {
            font-size: 1.6rem;
            margin-bottom: 8px;
            color: #ff5e62;
        }
        
        .subtitle {
            color: #666;
            font-size: 0.9rem;
        }
        
        /* 订单表单 */
        .order-form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        
        .section {
            background: white;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .section.full-width {
            grid-column: 1 / -1;
        }
        
        .section h2 {
            font-size: 0.95rem;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
            color: #333;
        }
        
        .icon {
            font-size: 1.1em;
        }
        
        /* 选项样式 */
        .options-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }
        
        .option {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 10px;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
            min-height: 50px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .option.selected {
            border-color: #4CAF50;
            background: #e8f5e9;
        }
        
        .option h3 {
            font-size: 0.9rem;
            margin: 0 0 2px 0;
        }
        
        .option p {
            font-size: 0.8rem;
            color: #666;
            margin: 0;
        }
        
        /* 订单摘要 */
        .summary-section {
            background: #f0f7ff;
            border: 2px solid #2196F3;
        }
        
        .order-summary {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }
        
        .summary-item {
            font-size: 0.85rem;
            padding: 5px 0;
            display: flex;
            justify-content: space-between;
        }
        
        .summary-item.total {
            grid-column: 1 / -1;
            font-size: 1rem;
            color: #ff5e62;
            padding-top: 10px;
            margin-top: 5px;
            border-top: 1px solid #ddd;
        }
        
        /* 订单查询 */
        .order-check-section {
            margin-top: 10px;
        }
        
        .order-check-input {
            display: flex;
            gap: 8px;
            margin-top: 10px;
        }
        
        .order-check-input input {
            flex: 1;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .check-order-btn {
            padding: 10px 15px;
            background: #2196F3;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .order-status-display {
            margin-top: 10px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            display: none;
            font-size: 0.9rem;
        }
        
        .status-pending {
            color: #ff9800;
            font-weight: bold;
        }
        
        .status-completed {
            color: #4CAF50;
            font-weight: bold;
        }
        
        /* 下单按钮 */
        .submit-btn {
            grid-column: 1 / -1;
            padding: 16px;
            background: linear-gradient(135deg, #ff9966, #ff5e62);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 94, 98, 0.3);
        }
        
        .submit-btn:active {
            transform: translateY(-1px);
        }
        
        /* 模态框 */
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
        }
        
        .order-number {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .order-number h3 {
            font-size: 2.5rem;
            color: #ff5e62;
            margin: 10px 0;
            letter-spacing: 3px;
        }
        
        .order-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #ddd;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-item.total {
            font-weight: bold;
            color: #ff5e62;
        }
        
        .modal-footer {
            text-align: center;
            margin-top: 20px;
        }
        
        .save-image-btn {
            padding: 12px 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            margin-bottom: 10px;
            width: 100%;
        }
        
        .modal-btn {
            padding: 12px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            width: 100%;
        }
        
        /* 响应式调整 */
        @media (max-width: 480px) {
            .order-form {
                grid-template-columns: 1fr;
            }
            
            .options-grid {
                grid-template-columns: 1fr;
            }
            
            .order-summary {
                grid-template-columns: 1fr;
            }
            
            .summary-item.total {
                grid-column: 1;
            }
        }
        
        @media (max-width: 360px) {
            .order-check-input {
                flex-direction: column;
            }
            
            .check-order-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="app-header">
            <h1>🐙 章鱼小丸子点餐</h1>
            <p class="subtitle">新鲜现做，美味可口</p>
        </header>
        
        <main class="order-form">
            <!-- 分量选择 -->
            <section class="section">
                <h2><span class="icon">🍽️</span> 分量</h2>
                <div class="options-grid">
                    <div class="option" data-value="small">
                        <h3>小份</h3>
                        <p>10元 / 6个</p>
                    </div>
                    <div class="option" data-value="large">
                        <h3>大份</h3>
                        <p>15元 / 10个</p>
                    </div>
                </div>
            </section>
            
            <!-- 口味选择 -->
            <section class="section">
                <h2><span class="icon">🍯</span> 口味</h2>
                <div class="options-grid">
                    <div class="option" data-value="salad">沙拉酱</div>
                    <div class="option" data-value="honey_mustard">蜂蜜芥末</div>
                    <div class="option" data-value="teriyaki">照烧酱</div>
                    <div class="option" data-value="tomato">番茄酱</div>
                </div>
            </section>
            
            <!-- 小料选择 -->
            <section class="section">
                <h2><span class="icon">🧂</span> 小料</h2>
                <div class="options-grid">
                    <div class="option" data-value="bonito">木鱼花</div>
                    <div class="option" data-value="pork_floss">肉松</div>
                    <div class="option" data-value="seaweed">海苔</div>
                </div>
            </section>
            
            <!-- 订单汇总 -->
            <section class="section summary-section full-width">
                <h2><span class="icon">📝</span> 订单信息</h2>
                <div class="order-summary">
                    <div class="summary-item">
                        <span>分量:</span>
                        <span id="summary-portion">未选择</span>
                    </div>
                    <div class="summary-item">
                        <span>口味:</span>
                        <span id="summary-flavors">未选择</span>
                    </div>
                    <div class="summary-item">
                        <span>小料:</span>
                        <span id="summary-toppings">未选择</span>
                    </div>
                    <div class="summary-item total">
                        <span>总价:</span>
                        <span id="summary-price">0元</span>
                    </div>
                </div>
            </section>
            
            <!-- 订单查询 -->
            <section class="section full-width order-check-section">
                <h2><span class="icon">🔍</span> 查询订单进度</h2>
                <div class="order-check-input">
                    <input type="text" id="order-number-input" placeholder="请输入4位订单号" maxlength="4">
                    <button class="check-order-btn" id="check-order">查询</button>
                </div>
                <div class="order-status-display" id="order-status-display">
                    <div id="order-status-content"></div>
                </div>
            </section>
            
            <!-- 下单按钮 -->
            <button class="submit-btn" id="place-order">立即下单</button>
        </main>
    </div>
    
    <!-- 订单确认弹窗 -->
    <div class="modal" id="order-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>下单成功! 🎉</h2>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body" id="modal-content-screenshot">
                <div class="order-number">
                    <p>您的取餐号是</p>
                    <h3 id="order-id">0000</h3>
                </div>
                <div class="order-details">
                    <h4>订单详情:</h4>
                    <div id="modal-details"></div>
                </div>
                <div class="modal-footer">
                    <p>请稍等片刻，您的章鱼小丸子正在制作中...</p>
                    <button class="save-image-btn" id="save-image">保存订单截图</button>
                    <button class="modal-btn" id="close-order-modal">确定</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="lib/html2canvas.min.js"></script>
    <script src="js/buyer.js"></script>
</body>
</html>