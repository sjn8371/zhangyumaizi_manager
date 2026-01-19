<template>
  <div class="buyer-view">
    <div class="container">
      <header class="header">
        <div class="logo">
          <span class="logo-icon">🐙</span>
          <h1>章鱼小丸子</h1>
          <div class="tagline">热乎乎 香喷喷</div>
        </div>
      </header>

      <!-- 点餐区域 -->
      <div class="card order-form">
        <!-- 份量选择 -->
        <div class="section">
          <h2><span class="section-icon">🍢</span> 选择份量</h2>
          <div class="size-options">
            <div
              v-for="size in store.settings.sizes"
              :key="size.id"
              :class="['size-option', { selected: selectedSize === size.id }]"
              @click="selectedSize = size.id"
            >
              <div class="size-icon">{{ size.id === 'large' ? '🍢' : '🍡' }}</div>
              <div class="size-info">
                <div class="size-name">{{ size.name }}</div>
                <div class="size-count">{{ size.count }}个</div>
                <div class="size-price">¥{{ size.basePrice }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- 口味选择 -->
        <div class="section">
          <h2>
            <span class="section-icon">🎨</span> 选择口味
            <span class="hot-tag" v-if="hotFlavor">🔥 {{ hotFlavor }}</span>
          </h2>
          <div class="flavor-options">
            <div
              v-for="flavor in store.settings.flavors"
              :key="flavor.id"
              :class="[
                'flavor-option',
                {
                  selected: selectedFlavors.includes(flavor.id),
                  hot: isHotFlavor(flavor.name),
                },
              ]"
              @click="toggleFlavor(flavor.id)"
            >
              <div class="flavor-icon">{{ getFlavorIcon(flavor.name) }}</div>
              <div class="flavor-info">
                <div class="flavor-name">
                  {{ flavor.name }}
                  <span v-if="isHotFlavor(flavor.name)" class="hot-indicator">🔥</span>
                </div>
                <div class="flavor-price">免费</div>
              </div>
            </div>
          </div>
        </div>

        <!-- 小料选择 -->
        <div class="mb-6">
          <h2 class="text-lg font-bold text-gray-700 mb-3 flex items-center gap-2">
            <span>✨</span> 添加小料（免费）
          </h2>
          <div class="grid grid-cols-2 gap-3">
            <div
              v-for="topping in store.settings.toppings"
              :key="topping.id"
              @click="toggleTopping(topping.id)"
              :class="[
                'border-2 rounded-xl p-4 cursor-pointer transition-all',
                selectedToppings.includes(topping.id)
                  ? 'border-blue-500 bg-blue-50'
                  : 'border-gray-200 hover:border-gray-300',
              ]"
            >
              <div class="flex justify-between items-center">
                <span class="font-medium text-gray-800">{{ topping.name }}</span>
                <span class="text-green-600 text-sm">免费</span>
              </div>
            </div>
          </div>
        </div>

        <!-- 订单汇总 -->
        <div class="order-summary">
          <h3>📝 订单详情</h3>
          <div class="summary-details">
            <div class="summary-item">
              <span>份量：</span>
              <span>{{ getSizeName(selectedSize) }} ({{ getSizeCount() }}个)</span>
            </div>
            <div class="summary-item" v-if="selectedFlavors.length > 0">
              <span>口味：</span>
              <span>{{ getFlavorNames().join('、') }}</span>
            </div>
            <div class="summary-item" v-if="selectedToppings.length > 0">
              <span>小料：</span>
              <span>{{ getToppingNames().join('、') }}</span>
            </div>
            <div class="summary-total">
              <span>总计：</span>
              <span class="total-price">¥{{ totalPrice }}</span>
            </div>
          </div>
        </div>

        <!-- 提交按钮 -->
        <button
          @click="submitOrder"
          :disabled="selectedFlavors.length === 0"
          :class="['btn submit-btn', { disabled: selectedFlavors.length === 0 }]"
        >
          {{ selectedFlavors.length > 0 ? '确认下单' : '请选择口味' }}
        </button>
      </div>

      <!-- 下单成功 -->
      <div v-if="showSuccess" class="card success-card">
        <div class="success-icon">🎉</div>
        <h2>下单成功！</h2>
        <div class="pickup-info">
          <p>您的取件码是</p>
          <div class="pickup-code">{{ pickupCode }}</div>
          <p class="pickup-tip">请凭取件码领取您的章鱼小丸子</p>
          <p class="order-desc">{{ getOrderDescription() }}</p>
        </div>
        <div class="success-actions">
          <button @click="newOrder" class="btn">再下一单</button>
        </div>
      </div>

      <!-- 最近订单（只显示自己下的单） -->
      <div v-if="myRecentOrders.length > 0 && !showSuccess" class="card">
        <h2>📋 我的最近订单</h2>
        <div class="recent-orders">
          <div v-for="order in myRecentOrders" :key="order.id" class="recent-order">
            <div class="recent-code">{{ order.pickupCode }}</div>
            <div class="recent-info">
              <div>{{ order.sizeName }} | {{ getOrderFlavors(order) }}</div>
              <div class="recent-time">{{ formatTime(order.createdAt) }}</div>
            </div>
            <div :class="['recent-status', order.status]">
              {{ order.status === 'pending' ? '制作中' : '已可取' }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useOrderStore } from '../stores/orderStore'

export default {
  name: 'BuyerView',
  setup() {
    const store = useOrderStore()
    const selectedSize = ref('small')
    const selectedFlavors = ref([])
    const selectedToppings = ref([])
    const showSuccess = ref(false)
    const pickupCode = ref('')
    const myOrders = ref([])

    // 获取最热门口味
    const hotFlavor = computed(() => {
      if (store.popularFlavors.length > 0) {
        return store.popularFlavors[0].name
      }
      return null
    })

    // 判断是否是热门口味
    const isHotFlavor = (flavorName) => {
      return store.popularFlavors.some((flavor) => flavor.name === flavorName)
    }

    // 我的最近订单（基于本地存储）
    const myRecentOrders = computed(() => {
      const myCodes = myOrders.value.map((order) => order.pickupCode)
      return store.orders.filter((order) => myCodes.includes(order.pickupCode)).slice(0, 5)
    })

    const totalPrice = computed(() => {
      const size = store.settings.sizes.find((s) => s.id === selectedSize.value)
      return size ? size.basePrice : 0
    })

    const getSizeName = (sizeId) => {
      const size = store.settings.sizes.find((s) => s.id === sizeId)
      return size ? size.name : ''
    }

    const getSizeCount = () => {
      const size = store.settings.sizes.find((s) => s.id === selectedSize.value)
      return size ? size.count : 0
    }

    const getFlavorIcon = (flavorName) => {
      const icons = {
        照烧: '🍯',
        番茄: '🍅',
        蜂蜜芥末: '🍯',
      }
      return icons[flavorName] || '🎨'
    }

    const getToppingIcon = (toppingName) => {
      const icons = {
        海苔: '🍘',
        肉松: '🥩',
      }
      return icons[toppingName] || '✨'
    }

    const getFlavorNames = () => {
      return selectedFlavors.value
        .map((id) => {
          const flavor = store.settings.flavors.find((f) => f.id === id)
          return flavor ? flavor.name : ''
        })
        .filter((name) => name)
    }

    const getToppingNames = () => {
      return selectedToppings.value
        .map((id) => {
          const topping = store.settings.toppings.find((t) => t.id === id)
          return topping ? topping.name : ''
        })
        .filter((name) => name)
    }

    const getOrderFlavors = (order) => {
      return order.flavors
        .map((id) => {
          const flavor = store.settings.flavors.find((f) => f.id === id)
          return flavor ? flavor.name : ''
        })
        .filter((name) => name)
        .join('、')
    }

    const toggleFlavor = (flavorId) => {
      const index = selectedFlavors.value.indexOf(flavorId)
      if (index > -1) {
        selectedFlavors.value.splice(index, 1)
      } else {
        selectedFlavors.value.push(flavorId)
      }
    }

    const toggleTopping = (toppingId) => {
      const index = selectedToppings.value.indexOf(toppingId)
      if (index > -1) {
        selectedToppings.value.splice(index, 1)
      } else {
        selectedToppings.value.push(toppingId)
      }
    }

    const submitOrder = () => {
      if (selectedFlavors.value.length === 0) return

      const orderData = {
        size: selectedSize.value,
        flavors: [...selectedFlavors.value],
        toppings: [...selectedToppings.value],
      }

      pickupCode.value = store.addOrder(orderData)
      showSuccess.value = true

      // 保存到我的订单
      const myOrder = {
        pickupCode: pickupCode.value,
        time: new Date().toISOString(),
      }
      myOrders.value.unshift(myOrder)
      saveMyOrders()

      // 清空选择
      selectedFlavors.value = []
      selectedToppings.value = []
      selectedSize.value = 'small'
    }

    const getOrderDescription = () => {
      const size = store.settings.sizes.find((s) => s.id === selectedSize.value)
      const flavorNames = getFlavorNames()
      const toppingNames = getToppingNames()

      let desc = `${size ? size.name : ''} ${size ? size.count : 0}个`
      if (flavorNames.length > 0) {
        desc += `，口味：${flavorNames.join('、')}`
      }
      if (toppingNames.length > 0) {
        desc += `，小料：${toppingNames.join('、')}`
      }
      return desc
    }

    const newOrder = () => {
      showSuccess.value = false
      pickupCode.value = ''
    }

    const formatTime = (isoString) => {
      const date = new Date(isoString)
      return date.toLocaleTimeString('zh-CN', {
        hour: '2-digit',
        minute: '2-digit',
      })
    }

    // 保存我的订单到本地存储
    const saveMyOrders = () => {
      try {
        localStorage.setItem('octopus_my_orders', JSON.stringify(myOrders.value))
      } catch (error) {
        console.error('保存我的订单失败:', error)
      }
    }

    // 加载我的订单
    const loadMyOrders = () => {
      try {
        const saved = localStorage.getItem('octopus_my_orders')
        if (saved) {
          myOrders.value = JSON.parse(saved)
        }
      } catch (error) {
        console.error('加载我的订单失败:', error)
      }
    }

    onMounted(() => {
      loadMyOrders()
    })

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
      getFlavorIcon,
      getToppingIcon,
      getFlavorNames,
      getToppingNames,
      getOrderFlavors,
      toggleFlavor,
      toggleTopping,
      submitOrder,
      getOrderDescription,
      newOrder,
      formatTime,
    }
  },
}
</script>

<style scoped>
.buyer-view {
  min-height: 100vh;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  padding: 20px 0;
}

.header {
  text-align: center;
  background: white;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 20px;
}

.logo-icon {
  font-size: 40px;
  display: block;
  margin-bottom: 10px;
}

.logo h1 {
  color: #333;
  font-size: 24px;
  margin: 0;
}

.tagline {
  color: #666;
  font-size: 14px;
  margin-top: 5px;
}

.order-form {
  max-width: 500px;
  margin: 0 auto 20px;
}

.section {
  margin-bottom: 30px;
}

.section h2 {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 15px;
  color: #333;
}

.section-icon {
  font-size: 20px;
}

.hot-tag {
  background: #ff6b6b;
  color: white;
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 12px;
  margin-left: 10px;
}

.size-options {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 15px;
}

.size-option {
  border: 2px solid #e0e0e0;
  border-radius: 10px;
  padding: 15px;
  cursor: pointer;
  transition: all 0.3s;
  text-align: center;
}

.size-option:hover {
  border-color: #667eea;
  transform: translateY(-2px);
}

.size-option.selected {
  border-color: #667eea;
  background: #eef2ff;
}

.size-icon {
  font-size: 32px;
  margin-bottom: 10px;
}

.size-name {
  font-size: 16px;
  font-weight: bold;
  color: #333;
}

.size-count {
  font-size: 14px;
  color: #666;
  margin: 5px 0;
}

.size-price {
  font-size: 18px;
  font-weight: bold;
  color: #f5576c;
}

.flavor-options,
.topping-options {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 15px;
}

.flavor-option,
.topping-option {
  border: 2px solid #e0e0e0;
  border-radius: 10px;
  padding: 15px;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  gap: 10px;
}

.flavor-option:hover,
.topping-option:hover {
  border-color: #f5576c;
  transform: scale(1.05);
}

.flavor-option.selected,
.topping-option.selected {
  border-color: #f5576c;
  background: #fff5f5;
}

.flavor-option.hot {
  border-color: #ff6b6b;
  position: relative;
}

.flavor-option.hot::before {
  content: '🔥';
  position: absolute;
  top: -8px;
  right: -8px;
  font-size: 16px;
}

.flavor-icon,
.topping-icon {
  font-size: 24px;
}

.flavor-info,
.topping-info {
  flex: 1;
}

.flavor-name,
.topping-name {
  font-size: 14px;
  font-weight: bold;
  color: #333;
  display: flex;
  align-items: center;
  gap: 5px;
}

.hot-indicator {
  color: #ff6b6b;
  font-size: 12px;
}

.flavor-price,
.topping-price {
  font-size: 12px;
  color: #28a745;
  font-weight: bold;
}

.order-summary {
  background: #f8f9fa;
  border-radius: 10px;
  padding: 20px;
  margin: 30px 0;
}

.summary-details {
  margin-top: 15px;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
  color: #666;
}

.summary-total {
  display: flex;
  justify-content: space-between;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 2px solid #e0e0e0;
  font-size: 18px;
  font-weight: bold;
}

.total-price {
  color: #f5576c;
  font-size: 24px;
}

.submit-btn {
  width: 100%;
  padding: 16px;
  font-size: 18px;
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}

.submit-btn.disabled {
  background: #6c757d;
  cursor: not-allowed;
}

.success-card {
  text-align: center;
  animation: slideUp 0.5s ease;
  max-width: 500px;
  margin: 0 auto;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.success-icon {
  font-size: 64px;
  margin-bottom: 20px;
}

.pickup-info {
  margin: 30px 0;
}

.pickup-code {
  font-size: 48px;
  font-weight: bold;
  color: #667eea;
  background: #eef2ff;
  padding: 10px 20px;
  border-radius: 10px;
  margin: 20px auto;
  display: inline-block;
  letter-spacing: 5px;
}

.pickup-tip {
  color: #666;
  margin: 10px 0;
}

.order-desc {
  background: #f8f9fa;
  padding: 10px;
  border-radius: 8px;
  margin: 20px 0;
  font-size: 14px;
}

.success-actions {
  margin-top: 30px;
}

.recent-orders {
  margin-top: 15px;
}

.recent-order {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  border-bottom: 1px solid #eee;
}

.recent-code {
  font-family: monospace;
  font-size: 18px;
  font-weight: bold;
  color: #667eea;
  min-width: 70px;
}

.recent-info {
  flex: 1;
  padding: 0 15px;
}

.recent-time {
  color: #666;
  font-size: 12px;
  margin-top: 5px;
}

.recent-status {
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  min-width: 60px;
  text-align: center;
}

.recent-status.pending {
  background: #fff3cd;
  color: #856404;
}

.recent-status.completed {
  background: #d4edda;
  color: #155724;
}
</style>