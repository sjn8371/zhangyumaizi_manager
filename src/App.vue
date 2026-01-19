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
    });
    
    return {
      store
    };
  }
};
</script>

<style>
/* 基础样式 */
body {
  margin: 0;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
</style>