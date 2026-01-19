<template>
  <div id="app">
    <SellerView v-if="store.mode === 'seller'" />
    <BuyerView v-else />
  </div>
</template>

<script>
import { onMounted } from 'vue';
import { useOrderStore } from './stores/orderStore';
import SellerView from './views/SellerView.vue';
import BuyerView from './views/BuyerView.vue';
import { initSync } from './utils/syncManager';

export default {
  name: 'App',
  components: {
    SellerView,
    BuyerView
  },
  setup() {
    const store = useOrderStore();
    
    onMounted(() => {
      // 初始化同步
      initSync(store);
      
      // 检查URL参数
      const urlParams = new URLSearchParams(window.location.search);
      const role = urlParams.get('role');
      if (role === 'seller') {
        store.setMode('seller');
      }
      
      // 请求通知权限
      if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
      }
    });
    
    return {
      store
    };
  }
};
</script>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background: #f5f5f5;
}

#app {
  min-height: 100vh;
}

.container {
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
}

.card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.1);
  padding: 24px;
  margin-bottom: 20px;
}

.btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 8px;
  font-size: 16px;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}

.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.btn-success {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}

.btn-danger {
  background: linear-gradient(135deg, #dc3545 0%, #f5576c 100%);
}

.btn-outline {
  background: white;
  color: #667eea;
  border: 2px solid #667eea;
}
</style>