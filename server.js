const WebSocket = require('ws');
const http = require('http');
const express = require('express');
const cors = require('cors');
const path = require('path');

// 创建Express应用和HTTP服务器
const app = express();
const server = http.createServer(app);

// 启用CORS
app.use(cors());
app.use(express.json());

// 存储连接和订单数据
const connections = new Set();
const ordersDB = {
  orders: [],
  lastSync: Date.now()
};

// 创建WebSocket服务器
const wss = new WebSocket.Server({ server });

// WebSocket连接处理
wss.on('connection', (ws) => {
  console.log('新的客户端连接');
  connections.add(ws);
  
  // 发送当前数据给新连接的客户端
  ws.send(JSON.stringify({
    type: 'init',
    data: ordersDB
  }));
  
  ws.on('message', (message) => {
    try {
      const data = JSON.parse(message.toString());
      console.log('收到消息:', data.type);
      
      switch (data.type) {
        case 'sync_orders':
          handleSyncOrders(ws, data);
          break;
        case 'new_order':
          handleNewOrder(data);
          break;
        case 'complete_order':
          handleCompleteOrder(data);
          break;
        case 'delete_order':
          handleDeleteOrder(data);
          break;
        case 'ping':
          ws.send(JSON.stringify({ type: 'pong' }));
          break;
      }
    } catch (error) {
      console.error('消息处理错误:', error);
    }
  });
  
  ws.on('close', () => {
    console.log('客户端断开连接');
    connections.delete(ws);
  });
  
  ws.on('error', (error) => {
    console.error('WebSocket错误:', error);
  });
});

// 处理订单同步
function handleSyncOrders(ws, data) {
  const { orders, timestamp } = data;
  
  // 简单的冲突解决：保留最新的数据
  if (timestamp > ordersDB.lastSync) {
    ordersDB.orders = orders;
    ordersDB.lastSync = timestamp;
    
    // 广播给所有连接的客户端
    broadcast({
      type: 'orders_updated',
      data: ordersDB,
      timestamp: Date.now()
    });
  } else {
    // 如果客户端数据较旧，发送最新的数据
    ws.send(JSON.stringify({
      type: 'sync_required',
      data: ordersDB
    }));
  }
}

// 处理新订单
function handleNewOrder(data) {
  const { order } = data;
  ordersDB.orders.unshift(order);
  ordersDB.lastSync = Date.now();
  
  console.log(`新订单: ${order.pickupCode}`);
  
  // 广播新订单给所有商家端
  broadcast({
    type: 'new_order_added',
    order: order,
    timestamp: Date.now()
  });
}

// 处理完成订单
function handleCompleteOrder(data) {
  const { orderId } = data;
  const order = ordersDB.orders.find(o => o.id === orderId);
  
  if (order) {
    order.status = 'completed';
    order.completedAt = new Date().toISOString();
    ordersDB.lastSync = Date.now();
    
    // 广播订单更新
    broadcast({
      type: 'order_completed',
      orderId: orderId,
      timestamp: Date.now()
    });
  }
}

// 处理删除订单
function handleDeleteOrder(data) {
  const { orderId } = data;
  const index = ordersDB.orders.findIndex(o => o.id === orderId);
  
  if (index > -1) {
    ordersDB.orders.splice(index, 1);
    ordersDB.lastSync = Date.now();
    
    // 广播订单删除
    broadcast({
      type: 'order_deleted',
      orderId: orderId,
      timestamp: Date.now()
    });
  }
}

// 广播消息给所有连接的客户端
function broadcast(message) {
  const data = JSON.stringify(message);
  connections.forEach(client => {
    if (client.readyState === WebSocket.OPEN) {
      client.send(data);
    }
  });
}

// HTTP API端点
app.get('/api/orders', (req, res) => {
  res.json(ordersDB);
});

app.get('/api/stats', (req, res) => {
  const today = new Date().toISOString().split('T')[0];
  const todayOrders = ordersDB.orders.filter(order => 
    order.createdAt.startsWith(today)
  );
  
  const stats = {
    total: ordersDB.orders.length,
    today: todayOrders.length,
    pending: todayOrders.filter(o => o.status === 'pending').length,
    completed: todayOrders.filter(o => o.status === 'completed').length,
    revenue: todayOrders.reduce((sum, o) => sum + o.totalPrice, 0)
  };
  
  res.json(stats);
});

// 静态文件服务（用于生产环境）
app.use(express.static(path.join(__dirname, 'dist')));

// 处理所有路由，返回index.html（用于SPA）
app.get('*', (req, res) => {
  res.sendFile(path.join(__dirname, 'dist', 'index.html'));
});

// 启动服务器
const PORT = process.env.PORT || 3001;
server.listen(PORT, () => {
  console.log(`
    🚀 服务器已启动！
    📍 本地访问: http://localhost:${PORT}
    🌐 WebSocket: ws://localhost:${PORT}
    
    📱 商家端: http://localhost:${PORT}?role=seller
    🛍️  买家端: http://localhost:${PORT}
  `);
  
  // 获取本地IP地址
  const os = require('os');
  const interfaces = os.networkInterfaces();
  
  Object.keys(interfaces).forEach((iface) => {
    interfaces[iface].forEach((details) => {
      if (details.family === 'IPv4' && !details.internal) {
        console.log(`    📍 局域网地址: http://${details.address}:${PORT}`);
      }
    });
  });
});

// 定期清理断开连接的客户端
setInterval(() => {
  wss.clients.forEach((client) => {
    if (client.readyState === WebSocket.CLOSED) {
      connections.delete(client);
    }
  });
}, 30000);