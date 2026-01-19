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

export default {
  name: 'App',
  components: {
    SellerView,
    BuyerView
  },
  setup() {
    const store = useOrderStore();

    onMounted(() => {
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
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'PingFang SC', 'Hiragino Sans GB', 'Microsoft YaHei', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}
</style>
