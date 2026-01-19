<template>
  <div class="min-h-screen bg-gradient-to-br from-purple-50 to-pink-50">
    <!-- 头部 -->
    <div class="bg-gradient-to-r from-purple-600 to-blue-600 text-white shadow-lg">
      <div class="container mx-auto px-4 py-6">
        <div class="text-center">
          <h1 class="text-3xl font-bold flex items-center justify-center gap-3 mb-2">
            <span class="text-4xl">🐙</span> 
            <span>章鱼小丸子点餐</span>
          </h1>
          <p class="text-purple-100">热乎乎 香喷喷 现点现做</p>
        </div>
      </div>
    </div>

    <div class="container mx-auto px-4 py-6 max-w-lg">
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
                'border-2 rounded-xl p-5 cursor-pointer transition-all duration-200 hover:shadow-lg',
                selectedSize === size.id
                  ? 'border-purple-500 bg-purple-50 shadow-md'
                  : 'border-gray-200 hover:border-gray-300'
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
            <span v-if="hotFlavor" class="bg-gradient-to-r from-orange-500 to-red-500 text-white text-sm px-3 py-1 rounded-full ml-2">
              🔥 {{ hotFlavor }}
            </span>
          </h2>
          <div class="grid grid-cols-2 gap-4">
            <div 
              v-for="flavor in store.settings?.flavors || []" 
              :key="flavor.id"
              @click="toggleFlavor(flavor.id)"
              :class="[
                'border-2 rounded-xl p-4 cursor-pointer transition-all duration-200',
                selectedFlavors.includes(flavor.id)
                  ? 'border-orange-500 bg-orange-50'
                  : 'border-gray-200 hover:border-gray-300'
              ]"
            >
              <div class="flex justify-between items-center">
                <div class="flex items-center gap-2">
                  <span class="font-medium text-gray-800">{{ flavor.name }}</span>
                  <span v-if="isHotFlavor(flavor.name)" class="text-orange-500">🔥</span>
                </div>
                <span class="text-green-600 font-medium">免费</span>
              </div>
            </div>
          </div>
        </div>

        <!-- 小料选择 -->
        <div class="mb-8">
          <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
            <span class="text-2xl">✨</span> 添加小料（免费）
          </h2>
          <div class="grid grid-cols-2 gap-4">
            <div 
              v-for="topping in store.settings?.toppings || []" 
              :key="topping.id"
              @click="toggleTopping(topping.id)"
              :class="[
                'border-2 rounded-xl p-4 cursor-pointer transition-all duration-200',
                selectedToppings.includes(topping.id)
                  ? 'border-blue-500 bg-blue-50'
                  : 'border-gray-200 hover:border-gray-300'
              ]"
            >
              <div class="flex justify-between items-center">
                <span class="font-medium text-gray-800">{{ topping.name }}</span>
                <span class="text-green-600 font-medium">免费</span>
              </div>
            </div>
          </div>
        </div>

        <!-- 订单汇总 -->
        <div class="mb-8">
          <div class="bg-gradient-to-r from-gray-50 to-white border border-gray-200 rounded-xl p-5">
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
                <span class="font-medium text-gray-800">{{ getFlavorNames().join('、') }}</span>
              </div>
              <div class="flex justify-between items-center" v-if="selectedToppings.length > 0">
                <span class="text-gray-600">小料：</span>
                <span class="font-medium text-gray-800">{{ getToppingNames().join('、') }}</span>
              </div>
              <div class="border-t border-gray-200 pt-3 mt-3">
                <div class="flex justify-between items-center">
                  <span class="text-lg font-bold text-gray-800">总计：</span>
                  <span class="text-3xl font-bold text-purple-600">¥{{ totalPrice }}</span>
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
            'w-full py-4 rounded-xl font-bold text-lg transition-all duration-200',
            selectedFlavors.length === 0
              ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
              : 'bg-gradient-to-r from-green-500 to-emerald-600 text-white hover:shadow-lg hover:scale-[1.02]'
          ]"
        >
          {{ selectedFlavors.length > 0 ? '确认下单' : '请至少选择一个口味' }}
        </button>
      </div>

      <!-- 下单成功 -->
      <div v-if="showSuccess" class="bg-white rounded-2xl shadow-xl p-6 mb-6 text-center">
        <div class="text-6xl mb-4">🎉</div>
        <h2 class="text-2xl font-bold text-gray-800 mb-2">下单成功！</h2>
        
        <div class="my-8">
          <p class="text-gray-600 mb-2">您的取件码是</p>
          <div class="bg-gradient-to-r from-purple-100 to-blue-100 border-2 border-dashed border-purple-300 rounded-2xl p-6 mb-4">
            <div class="text-5xl font-bold text-purple-600 tracking-wider">{{ pickupCode }}</div>
          </div>
          <p class="text-gray-500 text-sm">请凭此取件码领取您的章鱼小丸子</p>
        </div>

        <div class="bg-gray-50 rounded-xl p-4 mb-6 text-left">
          <p class="font-bold text-gray-700 mb-2">订单详情：</p>
          <p class="text-gray-600">份量：{{ getSizeName(selectedSize) }} ({{ getSizeCount() }}个)</p>
          <p class="text-gray-600" v-if="selectedFlavors.length > 0">口味：{{ getFlavorNames().join('、') }}</p>
          <p class="text-gray-600" v-if="selectedToppings.length > 0">小料：{{ getToppingNames().join('、') }}</p>
          <p class="text-gray-600 mt-2">总价：<span class="font-bold text-purple-600">¥{{ totalPrice }}</span></p>
        </div>

        <button 
          @click="newOrder"
          class="w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white py-4 rounded-xl font-bold text-lg hover:shadow-lg transition-all duration-200"
        >
          再下一单
        </button>
      </div>

      <!-- 最近订单 -->
      <div v-if="myRecentOrders.length > 0 && !showSuccess" class="bg-white rounded-2xl shadow-xl p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
          <span>📋</span> 我的最近订单
        </h2>
        <div class="space-y-3">
          <div 
            v-for="order in myRecentOrders" 
            :key="order.id" 
            class="border border-gray-200 rounded-xl p-4 hover:bg-gray-50 transition-colors"
          >
            <div class="flex justify-between items-center mb-2">
              <div class="bg-purple-100 text-purple-600 font-bold text-lg px-3 py-1 rounded-lg">
                {{ order.pickupCode }}
              </div>
              <span :class="[
                'px-3 py-1 rounded-full text-xs font-bold',
                order.status === 'pending' 
                  ? 'bg-yellow-100 text-yellow-800'
                  : 'bg-green-100 text-green-800'
              ]">
                {{ order.status === 'pending' ? '制作中' : '已可取' }}
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
    const selectedFlavors = ref([]);
    const selectedToppings = ref([]);
    const showSuccess = ref(false);
    const pickupCode = ref('');
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

    // 我的最近订单（基于本地存储）
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

    const toggleTopping = (toppingId) => {
      const index = selectedToppings.value.indexOf(toppingId);
      if (index > -1) {
        selectedToppings.value.splice(index, 1);
      } else {
        selectedToppings.value.push(toppingId);
      }
    };

    const submitOrder = () => {
      if (selectedFlavors.value.length === 0) return;
      
      const orderData = {
        size: selectedSize.value,
        flavors: [...selectedFlavors.value],
        toppings: [...selectedToppings.value]
      };
      
      pickupCode.value = store.addOrder(orderData);
      showSuccess.value = true;
      
      // 保存到我的订单
      const myOrder = {
        pickupCode: pickupCode.value,
        time: new Date().toISOString()
      };
      myOrders.value.unshift(myOrder);
      saveMyOrders();
      
      // 清空选择
      selectedFlavors.value = [];
      selectedToppings.value = [];
      selectedSize.value = 'small';
    };

    const newOrder = () => {
      showSuccess.value = false;
      pickupCode.value = '';
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
    });

    return {
      store,
      selectedSize,
      selectedFlavors,
      selectedToppings,
      showSuccess,
      pickupCode,
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
      toggleTopping,
      submitOrder,
      newOrder,
      formatTime
    };
  }
};
</script>

<style>
/* 自定义滚动条 */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}
</style>