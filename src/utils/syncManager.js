class SyncManager {
  constructor() {
    this.ws = null;
    this.connected = false;
    this.reconnectAttempts = 0;
    this.maxReconnectAttempts = 10;
    this.reconnectDelay = 2000;
    this.store = null;
  }

  // 初始化连接
  init(store) {
    this.store = store;
    this.connect();
  }

  // 连接WebSocket
  connect() {
    try {
      const protocol = window.location.protocol === 'https:' ? 'wss' : 'ws';
      const host = window.location.hostname;
      const port = '3002'; // 使用3002端口

      const wsUrl = `${protocol}://${host}:${port}`;
      console.log('连接WebSocket:', wsUrl);

      this.ws = new WebSocket(wsUrl);

      this.ws.onopen = () => {
        console.log('WebSocket连接成功');
        this.connected = true;
        this.reconnectAttempts = 0;

        // 发送当前数据同步
        this.sendSyncData();
      };

      this.ws.onmessage = (event) => {
        this.handleMessage(event.data);
      };

      this.ws.onclose = () => {
        console.log('WebSocket连接关闭');
        this.connected = false;
        this.scheduleReconnect();
      };

      this.ws.onerror = (error) => {
        console.error('WebSocket错误:', error);
        this.connected = false;
        this.fallbackToLocalStorageSync();
      };

    } catch (error) {
      console.error('WebSocket初始化失败:', error);
      this.fallbackToLocalStorageSync();
    }
  }

  // 处理接收到的消息
  handleMessage(data) {
    try {
      const message = JSON.parse(data);

      switch (message.type) {
        case 'init':
          this.mergeData(message.data);
          break;

        case 'orders_updated':
          if (this.store) {
            this.store.orders = message.data.orders;
            this.store.saveToLocalStorage();
            window.dispatchEvent(new CustomEvent('ordersUpdated'));
          }
          break;

        case 'new_order_added':
          if (this.store) {
            const exists = this.store.orders.some(o => o.id === message.order.id);
            if (!exists) {
              this.store.orders.unshift(message.order);
              this.store.saveToLocalStorage();
              window.dispatchEvent(new CustomEvent('newOrder', { detail: message.order }));
            }
          }
          break;

        case 'sync_required':
          this.sendSyncData();
          break;
      }
    } catch (error) {
      console.error('消息处理错误:', error);
    }
  }

  // 发送同步数据
  sendSyncData() {
    if (this.connected && this.ws && this.store) {
      this.ws.send(JSON.stringify({
        type: 'sync_orders',
        orders: this.store.orders,
        timestamp: Date.now()
      }));
    }
  }

  // 发送新订单
  sendNewOrder(order) {
    if (this.connected && this.ws) {
      this.ws.send(JSON.stringify({
        type: 'new_order',
        order: order
      }));
    }
  }

  // 发送完成订单
  sendCompleteOrder(orderId) {
    if (this.connected && this.ws) {
      this.ws.send(JSON.stringify({
        type: 'complete_order',
        orderId: orderId
      }));
    }
  }

  // 发送删除订单
  sendDeleteOrder(orderId) {
    if (this.connected && this.ws) {
      this.ws.send(JSON.stringify({
        type: 'delete_order',
        orderId: orderId
      }));
    }
  }

  // 合并数据
  mergeData(serverData) {
    if (!serverData.orders || serverData.orders.length === 0 || !this.store) {
      return;
    }

    // 简单的合并策略：保留最新的数据
    if (serverData.lastSync > (this.store.lastSync || 0)) {
      this.store.orders = serverData.orders;
      this.store.saveToLocalStorage();
      window.dispatchEvent(new CustomEvent('ordersUpdated'));
    } else if (serverData.lastSync < (this.store.lastSync || 0)) {
      // 本地数据更新，发送到服务器
      this.sendSyncData();
    }
  }

  // 安排重连
  scheduleReconnect() {
    if (this.reconnectAttempts < this.maxReconnectAttempts) {
      this.reconnectAttempts++;
      console.log(`尝试重连 (${this.reconnectAttempts}/${this.maxReconnectAttempts})...`);

      setTimeout(() => {
        this.connect();
      }, this.reconnectDelay * this.reconnectAttempts);
    } else {
      console.log('达到最大重连次数，使用本地存储同步');
      this.fallbackToLocalStorageSync();
    }
  }

  // 降级到LocalStorage同步
  fallbackToLocalStorageSync() {
    console.log('使用LocalStorage同步');

    // 监听storage事件实现多标签页同步
    window.addEventListener('storage', (event) => {
      if (event.key === 'octopus_orders' && this.store) {
        try {
          const newOrders = JSON.parse(event.newValue || '[]');
          this.store.orders = newOrders;
          window.dispatchEvent(new CustomEvent('ordersUpdated'));
        } catch (error) {
          console.error('同步数据错误:', error);
        }
      }
    });
  }
}

// 创建单例
const syncManager = new SyncManager();

// 将syncManager挂载到window对象
if (typeof window !== 'undefined') {
  window.syncManager = syncManager;
}

// 初始化同步
export const initSync = (store) => {
  syncManager.init(store);

  // 监听网络状态变化
  window.addEventListener('online', () => {
    console.log('网络恢复，尝试重新连接WebSocket');
    syncManager.connect();
  });

  window.addEventListener('offline', () => {
    console.log('网络断开');
    syncManager.connected = false;
  });
};
