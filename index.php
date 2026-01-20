<?php
session_start();
// 如果已经登录，直接跳转到卖家端
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    // 检查登录有效期
    if (isset($_SESSION['login_time'])) {
        $expiryTime = 12 * 3600; // 12小时
        if (time() - $_SESSION['login_time'] > $expiryTime) {
            session_destroy();
        } else {
            header('Location: seller.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>商家登录 - 章鱼小丸子</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 15px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #333;
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-group label {
            color: #333;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .form-group input {
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .login-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 5px;
            font-size: 0.9rem;
            text-align: center;
            display: none;
            margin-bottom: 15px;
        }
        
        .demo-info {
            background-color: #f3f4f6;
            padding: 10px;
            border-radius: 5px;
            margin-top: 15px;
            font-size: 0.85rem;
        }
        
        .demo-info p {
            margin: 5px 0;
            color: #666;
        }
        
        .info-icon {
            color: #667eea;
            font-weight: bold;
        }
        
        .expired-notice {
            background-color: #fff3e0;
            color: #f57c00;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🐙 章鱼小丸子商家端</h1>
            <p>请输入账号密码登录管理系统</p>
        </div>
        
        <?php if (isset($_GET['expired'])): ?>
        <div class="expired-notice">
            登录已过期，请重新登录
        </div>
        <?php endif; ?>
        
        <div class="error-message" id="error-message"></div>
        
        <form class="login-form" id="login-form">
            <div class="form-group">
                <label for="username">账号</label>
                <input type="text" id="username" name="username" required value="admin">
            </div>
            
            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" required value="zyxwz">
            </div>
            
            <button type="submit" class="login-btn">登录</button>
        </form>
        
        <div class="demo-info">
            <p><span class="info-icon">ℹ️</span> 演示账号信息：</p>
            <p>账号：admin</p>
            <p>密码：zyxwz</p>
        </div>
    </div>
    
    <script>
        document.getElementById('login-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const errorMessage = document.getElementById('error-message');
            
            // 简单的客户端验证
            if (!username || !password) {
                errorMessage.textContent = '请输入账号和密码';
                errorMessage.style.display = 'block';
                return;
            }
            
            // 发送登录请求
            fetch('api/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'seller.php';
                } else {
                    errorMessage.textContent = data.message || '登录失败';
                    errorMessage.style.display = 'block';
                }
            })
            .catch(error => {
                errorMessage.textContent = '网络错误，请稍后重试';
                errorMessage.style.display = 'block';
            });
        });
    </script>
</body>
</html>