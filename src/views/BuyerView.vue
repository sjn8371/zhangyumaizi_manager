<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-purple-50">
    <!-- 头部 -->
    <div class="bg-gradient-to-r from-purple-600 to-blue-600 text-white shadow-lg">
      <div class="container mx-auto px-4 py-6">
        <div class="text-center">
          <h1 class="text-3xl font-bold flex items-center justify-center gap-3 mb-2">
            <span class="text-4xl">🐙</span>
            <span>章鱼小丸子点餐</span>
          </h1>
          <p class="text-purple-100 text-sm">现点现做 • 美味可口</p>
        </div>
      </div>
    </div>

    <div class="container mx-auto px-4 py-6 max-w-2xl">
      <!-- 点餐表单 -->
      <div class="bg-white rounded-2xl shadow-xl p-6 mb-6">
        <!-- 份量选择 -->
        <div class="mb-8">
          <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
            <span class="text-2xl">🍢</span> 选择份量
          </h2>
          <div class="grid grid-cols-2 gap-4">
            <div
              v-for="size in store.settings?.sizes || []"
              :key="size.id"
              @click="selectedSize = size.id"
              :class="[
                'border-2 rounded-xl p-5 cursor-pointer transition-all duration-200',
                selectedSize === size.id
                  ? 'border-purple-500 bg-purple-50 shadow-lg transform scale-[1.02]'
                  : 'border-gray-200 hover:border-gray-300 hover:shadow-md'
              ]"
            >
              <div class="flex justify-between items-start mb-2">
                <div>
                  <p class="font-bold text-lg text-gray-800">{{ size.name }}</p>
                  <p class="text-sm text-gray-600 mt-1">{{ size.count }}个</p>
                </div>
                <div class="text-2xl">{{ size.id === 'large' ? '🍢' : '🍡' }}</div>
              </div>
              <div class="text-right">
                <p class="text-2xl font-bold text-purple-600">¥{{ size.basePrice }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- 口味选择 -->
        <div class="mb-8">
          <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
            <span class="text-2xl">🎨</span> 选择口味（可多选）
            <span v-if="hotFlavor" class="bg-gradient-to-r from-orange-500 to-red-500 text-white text-xs px-2 py-1 rounded-full ml-2 animate-pulse">
              🔥 {{ hotFlavor }}
            </span>
          </h2>
          <div class="grid grid-cols-2 gap-3">
            <div
              v-for="flavor in store.settings?.flavors || []"
              :key="flavor.id"
              @click="toggleFlavor(flavor.id)"
              :class="[
                'border-2 rounded-xl p-4 cursor-pointer transition-all duration-200',
                selectedFlavors.includes(flavor.id)
                  ? isHotFlavor(flavor.name)
                    ? 'border-red-500 bg-gradient-to-r from-red-50 to-orange-50 shadow-lg'
                    : 'border-purple-500 bg-purple-50 shadow-lg'
                  : 'border-gray-200 hover:border-gray-300 hover:shadow-sm',
                isHotFlavor(flavor.name) ? 'animate-pulse-glow' : ''
              ]"
            >
              <div class="flex justify-between items-center">
                <div class="flex items-center gap-2">
                  <span class="font-medium text-gray-800">{{ flavor.name }}</span>
                  <span v-if="isHotFlavor(flavor.name)" class="text-red-500 animate-bounce">🔥</span>
                </div>
                <span class="text-green-600 font-medium text-sm">免费</span>
              </div>
            </div>
          </div>
          <div class="mt-3 flex gap-2">
            <button
              @click="selectAllFlavors"
              class="text-sm text-purple-600 hover:text-purple-700 font-medium"
            >
              全选
            </button>
            <button
              @click="clearAllFlavors"
              class="text-sm text-gray-500 hover:text-gray-700 font-medium"
            >
              清空
            </button>
          </div>
        </div>

        <!-- 小料选择 -->
        <div class="mb-8">
          <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
            <span class="text-2xl">✨</span> 添加小料（免费）
            <span class="text-xs text-gray-500">默认全选</span>
          </h2>
          <div class="grid grid-cols-3 gap-3">
            <div
              v-for="topping in store.settings?.toppings || []"
              :key="topping.id"
              @click="toggleTopping(topping.id)"
              :class="[
                'border-2 rounded-xl p-4 cursor-pointer transition-all duration-200',
                selectedToppings.includes(topping.id)
                  ? 'border-blue-500 bg-blue-50 shadow-lg'
                  : 'border-gray-200 hover:border-gray-300 hover:shadow-sm'
              ]"
            >
              <div class="text-center">
                <div class="text-2xl mb-2">✨</div>
                <p class="font-medium text-gray-800 text-sm">{{ topping.name }}</p>
                <p class="text-green-600 text-xs mt-1">免费</p>
              </div>
            </div>
          </div>
        </div>

        <!-- 订单汇总 -->
        <div class="mb-8">
          <div class="bg-gradient-to-r from-gray-50 to-white border border-gray-200 rounded-xl p-5 shadow-inner">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
              <span>📝</span> 订单详情
            </h3>
            <div class="space-y-3">
              <div class="flex justify-between items-center">
                <span class="text-gray-600">份量：</span>
                <span class="font-medium text-gray-800">{{ getSizeName(selectedSize) }} ({{ getSizeCount() }}个)</span>
              </div>
              <div class="flex justify-between items-center" v-if="selectedFlavors.length > 0">
                <span class="text-gray-600">口味：</span>
                <span class="font-medium text-gray-800 text-right">{{ getFlavorNames().join('、') }}</span>
              </div>
              <div class="flex justify-between items-center" v-if="selectedToppings.length > 0">
                <span class="text-gray-600">小料：</span>
                <span class="font-medium text-gray-800 text-right">{{ getToppingNames().join('、') }}</span>
              </div>
              <div class="border-t border-gray-200 pt-4 mt-4">
                <div class="flex justify-between items-center">
                  <span class="text-lg font-bold text-gray-800">总计：</span>
                  <div class="text-right">
                    <p class="text-3xl font-bold text-purple-600">¥{{ totalPrice }}</p>
                    <p class="text-xs text-gray-500">点击确认下单</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 提交按钮 -->
        <button
          @click="submitOrder"
          :disabled="selectedFlavors.length === 0"
          :class="[
            'w-full py-5 rounded-xl font-bold text-lg transition-all duration-200 relative overflow-hidden',
            selectedFlavors.length === 0
              ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
              : 'bg-gradient-to-r from-green-500 to-emerald-600 text-white hover:shadow-2xl hover:scale-[1.02] active:scale-95'
          ]"
        >
          <div v-if="selectedFlavors.length > 0" class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent animate-shimmer"></div>
          <span class="relative z-10">
            {{ selectedFlavors.length > 0 ? '🎯 确认下单' : '请选择口味' }}
          </span>
        </button>
      </div>

      <!-- 最近订单 -->
      <div v-if="myRecentOrders.length > 0" class="bg-white rounded-2xl shadow-xl p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
          <span>📋</span> 我的最近订单
        </h2>
        <div class="space-y-3">
          <div
            v-for="order in myRecentOrders"
            :key="order.id"
            class="border border-gray-200 rounded-xl p-4 hover:bg-gray-50 transition-colors group"
            @click="viewOrderDetails(order)"
          >
            <div class="flex justify-between items-center mb-2">
              <div class="bg-gradient-to-r from-purple-100 to-blue-100 text-purple-600 font-bold text-lg px-3 py-1 rounded-lg group-hover:scale-105 transition-transform">
                {{ order.pickupCode }}
              </div>
              <span :class="[
                'px-3 py-1 rounded-full text-xs font-bold transition-colors',
                order.status === 'pending'
                  ? 'bg-yellow-100 text-yellow-800 group-hover:bg-yellow-200'
                  : 'bg-green-100 text-green-800 group-hover:bg-green-200'
              ]">
                {{ order.status === 'pending' ? '制作中' : '已完成' }}
              </span>
            </div>
            <div class="text-gray-600 text-sm">
              <div>{{ order.sizeName }} | {{ getOrderFlavors(order) }}</div>
              <div class="text-gray-400 mt-1">{{ formatTime(order.createdAt) }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 全屏成功弹窗 -->
    <div v-if="showSuccess" class="fixed inset-0 bg-black/90 z-50 flex items-center justify-center p-4 animate-fade-in">
      <div class="bg-gradient-to-br from-white to-gray-50 w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden animate-slide-up">
        <!-- 头部 -->
        <div class="bg-gradient-to-r from-purple-600 to-blue-600 p-8 text-center text-white">
          <div class="text-6xl mb-4">🎉</div>
          <h2 class="text-3xl font-bold mb-2">下单成功！</h2>
          <p class="text-purple-100">您的订单已提交，请稍候制作</p>
        </div>

        <!-- 取件码区域 -->
        <div class="p-8 text-center">
          <p class="text-gray-600 mb-4 text-lg">取件码</p>
          <div class="relative inline-block">
            <div class="bg-gradient-to-r from-purple-100 to-blue-100 border-4 border-dashed border-purple-300 rounded-2xl p-8 mb-6 animate-pulse-glow">
              <div class="text-7xl font-bold text-purple-600 tracking-wider font-mono">
                {{ orderDetails.pickupCode }}
              </div>
            </div>
            <div class="absolute -top-3 -right-3 bg-red-500 text-white text-sm px-3 py-1 rounded-full animate-bounce">
              请记住！
            </div>
          </div>
          <p class="text-gray-500 mb-8">请凭此取件码领取您的章鱼小丸子</p>

          <!-- 订单详情 -->
          <div class="bg-gray-50 rounded-2xl p-6 mb-8 text-left">
            <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
              <span>📋</span> 订单详情
            </h3>
            <div class="space-y-3">
              <div class="flex justify-between items-center">
                <span class="text-gray-600">取件码：</span>
                <span class="font-bold text-purple-600 text-xl">{{ orderDetails.pickupCode }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-gray-600">份量：</span>
                <span class="font-medium text-gray-800">{{ getSizeName(orderDetails.size) }} ({{ getSizeCount() }}个)</span>
              </div>
              <div v-if="orderDetails.flavorNames.length > 0" class="flex justify-between items-start">
                <span class="text-gray-600">口味：</span>
                <span class="font-medium text-gray-800 text-right">{{ orderDetails.flavorNames.join('、') }}</span>
              </div>
              <div v-if="orderDetails.toppingNames.length > 0" class="flex justify-between items-start">
                <span class="text-gray-600">小料：</span>
                <span class="font-medium text-gray-800 text-right">{{ orderDetails.toppingNames.join('、') }}</span>
              </div>
              <div class="pt-4 mt-4 border-t border-gray-200">
                <div class="flex justify-between items-center">
                  <span class="text-lg font-bold text-gray-800">总计金额：</span>
                  <span class="text-3xl font-bold text-purple-600">¥{{ orderDetails.totalPrice }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- 操作按钮 -->
          <div class="grid grid-cols-2 gap-4">
            <button
              @click="copyOrderCode"
              class="bg-gradient-to-r from-gray-600 to-gray-700 text-white py-4 rounded-xl font-bold text-lg hover:shadow-lg transition-all duration-200 hover:scale-[1.02] active:scale-95"
            >
              复制取件码
            </button>
            <button
              @click="newOrder"
              class="bg-gradient-to-r from-purple-600 to-blue-600 text-white py-4 rounded-xl font-bold text-lg hover:shadow-lg transition-all duration-200 hover:scale-[1.02] active:scale-95"
            >
              继续下单
            </button>
          </div>

          <div class="mt-6">
            <button
              @click="closeSuccessPopup"
              class="text-gray-500 hover:text-gray-700 text-sm font-medium"
            >
              关闭窗口
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { useOrderStore } from '../stores/orderStore';

export default {
  name: 'BuyerView',
  setup() {
    const store = useOrderStore();
    const selectedSize = ref('small');
    const selectedFlavors = ref([...store.settings?.defaultFlavors || [1, 2, 3, 4]]);
    const selectedToppings = ref([...store.settings?.defaultToppings || [1, 2, 3]]);
    const showSuccess = ref(false);
    const orderDetails = ref({
      pickupCode: '',
      size: '',
      flavorNames: [],
      toppingNames: [],
      totalPrice: 0
    });
    const myOrders = ref([]);

    // 获取最热门口味
    const hotFlavor = computed(() => {
      const stats = store.getMultiDimensionStats();
      if (stats?.popularFlavors?.length > 0) {
        return stats.popularFlavors[0].name;
      }
      return null;
    });

    // 判断是否是热门口味
    const isHotFlavor = (flavorName) => {
      const stats = store.getMultiDimensionStats();
      return stats?.popularFlavors?.some(flavor => flavor.name === flavorName) || false;
    };

    // 我的最近订单
    const myRecentOrders = computed(() => {
      const myCodes = myOrders.value.map(order => order.pickupCode);
      return store.orders
        .filter(order => myCodes.includes(order.pickupCode))
        .slice(0, 5);
    });

    const totalPrice = computed(() => {
      const size = store.settings?.sizes?.find(s => s.id === selectedSize.value);
      return size ? size.basePrice : 0;
    });

    const getSizeName = (sizeId) => {
      const size = store.settings?.sizes?.find(s => s.id === sizeId);
      return size ? size.name : '';
    };

    const getSizeCount = () => {
      const size = store.settings?.sizes?.find(s => s.id === selectedSize.value);
      return size ? size.count : 0;
    };

    const getFlavorNames = () => {
      return selectedFlavors.value.map(id => {
        const flavor = store.settings?.flavors?.find(f => f.id === id);
        return flavor ? flavor.name : '';
      }).filter(name => name);
    };

    const getToppingNames = () => {
      return selectedToppings.value.map(id => {
        const topping = store.settings?.toppings?.find(t => t.id === id);
        return topping ? topping.name : '';
      }).filter(name => name);
    };

    const getOrderFlavors = (order) => {
      return order.flavors.map(id => {
        const flavor = store.settings?.flavors?.find(f => f.id === id);
        return flavor ? flavor.name : '';
      }).filter(name => name).join('、');
    };

    const toggleFlavor = (flavorId) => {
      const index = selectedFlavors.value.indexOf(flavorId);
      if (index > -1) {
        selectedFlavors.value.splice(index, 1);
      } else {
        selectedFlavors.value.push(flavorId);
      }
    };

    const selectAllFlavors = () => {
      selectedFlavors.value = [...store.settings?.defaultFlavors || [1, 2, 3, 4]];
    };

    const clearAllFlavors = () => {
      selectedFlavors.value = [];
    };

    const toggleTopping = (toppingId) => {
      const index = selectedToppings.value.indexOf(toppingId);
      if (index > -1) {
        selectedToppings.value.splice(index, 1);
      } else {
        selectedToppings.value.push(toppingId);
      }
    };

    const submitOrder = () => {
      if (selectedFlavors.value.length === 0) {
        alert('请至少选择一个口味');
        return;
      }

      const orderData = {
        size: selectedSize.value,
        flavors: selectedFlavors.value.length > 0 ? selectedFlavors.value : store.settings?.defaultFlavors,
        toppings: selectedToppings.value.length > 0 ? selectedToppings.value : store.settings?.defaultToppings
      };

      const newOrder = store.addOrder(orderData);
      orderDetails.value = {
        pickupCode: newOrder.pickupCode,
        size: selectedSize.value,
        flavorNames: getFlavorNames(),
        toppingNames: getToppingNames(),
        totalPrice: totalPrice.value
      };

      showSuccess.value = true;

      // 保存到我的订单
      const myOrder = {
        pickupCode: newOrder.pickupCode,
        time: new Date().toISOString()
      };
      myOrders.value.unshift(myOrder);
      saveMyOrders();
    };

    const copyOrderCode = () => {
      navigator.clipboard.writeText(orderDetails.value.pickupCode)
        .then(() => {
          alert('取件码已复制到剪贴板！');
        })
        .catch(err => {
          console.error('复制失败:', err);
        });
    };

    const newOrder = () => {
      showSuccess.value = false;
      // 重置表单
      selectedSize.value = 'small';
      selectedFlavors.value = [...store.settings?.defaultFlavors || [1, 2, 3, 4]];
      selectedToppings.value = [...store.settings?.defaultToppings || [1, 2, 3]];
    };

    const closeSuccessPopup = () => {
      showSuccess.value = false;
    };

    const viewOrderDetails = (order) => {
      orderDetails.value = {
        pickupCode: order.pickupCode,
        size: order.size,
        flavorNames: getOrderFlavors(order).split('、'),
        toppingNames: getToppingNames(),
        totalPrice: order.totalPrice
      };
      showSuccess.value = true;
    };

    const formatTime = (isoString) => {
      try {
        const date = new Date(isoString);
        return date.toLocaleTimeString('zh-CN', {
          hour: '2-digit',
          minute: '2-digit'
        });
      } catch {
        return '';
      }
    };

    // 保存我的订单到本地存储
    const saveMyOrders = () => {
      try {
        localStorage.setItem('octopus_my_orders', JSON.stringify(myOrders.value));
      } catch (error) {
        console.error('保存我的订单失败:', error);
      }
    };

    // 加载我的订单
    const loadMyOrders = () => {
      try {
        const saved = localStorage.getItem('octopus_my_orders');
        if (saved) {
          myOrders.value = JSON.parse(saved);
        }
      } catch (error) {
        console.error('加载我的订单失败:', error);
      }
    };

    onMounted(() => {
      loadMyOrders();
      // 设置默认选择
      selectedFlavors.value = [...store.settings?.defaultFlavors || [1, 2, 3, 4]];
      selectedToppings.value = [...store.settings?.defaultToppings || [1, 2, 3]];
    });

    return {
      store,
      selectedSize,
      selectedFlavors,
      selectedToppings,
      showSuccess,
      orderDetails,
      myRecentOrders,
      hotFlavor,
      totalPrice,
      isHotFlavor,
      getSizeName,
      getSizeCount,
      getFlavorNames,
      getToppingNames,
      getOrderFlavors,
      toggleFlavor,
      selectAllFlavors,
      clearAllFlavors,
      toggleTopping,
      submitOrder,
      copyOrderCode,
      newOrder,
      closeSuccessPopup,
      viewOrderDetails,
      formatTime
    };
  }
};
</script>

<style>
/* 自定义动画 */
@keyframes shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

.animate-shimmer {
  animation: shimmer 2s infinite;
}

/* 自定义滚动条 */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb {
  background: linear-gradient(to bottom, #667eea, #764ba2);
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(to bottom, #5a67d8, #6b46c1);
}
</style>
