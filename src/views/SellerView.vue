<template>
  <div class="min-h-screen bg-gray-50">
    <!-- 顶部导航 -->
    <div class="bg-gradient-to-r from-purple-600 to-blue-600 text-white shadow-lg">
      <div class="container mx-auto px-4 py-4">
        <div class="flex flex-col md:flex-row justify-between items-center">
          <div class="text-center md:text-left mb-4 md:mb-0">
            <h1 class="text-2xl font-bold flex items-center justify-center md:justify-start gap-2">
              <span>🐙</span> 章鱼小丸子商家端
            </h1>
            <p class="text-purple-100 text-sm">{{ currentDate }}</p>
          </div>
          <div class="flex gap-3">
            <div class="text-center bg-white/20 backdrop-blur-sm rounded-xl px-4 py-3 min-w-[90px]">
              <p class="text-xs opacity-90">今日订单</p>
              <p class="text-xl font-bold">{{ safeTodayOrders.length }}</p>
            </div>
            <div class="text-center bg-white/20 backdrop-blur-sm rounded-xl px-4 py-3 min-w-[90px]">
              <p class="text-xs opacity-90">待制作</p>
              <p class="text-xl font-bold">{{ pendingCount }}</p>
            </div>
            <div class="text-center bg-white/20 backdrop-blur-sm rounded-xl px-4 py-3 min-w-[90px]">
              <p class="text-xs opacity-90">今日营收</p>
              <p class="text-xl font-bold">¥{{ todayRevenue }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab切换 -->
    <div class="container mx-auto px-4">
      <div class="flex overflow-x-auto border-b border-gray-200 bg-white shadow-sm mt-4 rounded-t-lg">
        <button 
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'px-6 py-3 text-sm font-medium whitespace-nowrap transition-all',
            activeTab === tab.id 
              ? 'text-purple-600 border-b-2 border-purple-600 bg-purple-50'
              : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'
          ]"
        >
          {{ tab.icon }} {{ tab.label }}
        </button>
      </div>
    </div>

    <!-- 主内容区域 -->
    <div class="container mx-auto px-4 pb-20">
      <!-- Tab 1: 今日订单 -->
      <div v-if="activeTab === 'today'" class="bg-white rounded-b-lg shadow border border-gray-200">
        <!-- 订单状态过滤 -->
        <div class="p-4 border-b border-gray-200">
          <div class="flex gap-2">
            <button 
              v-for="tab in orderTabs"
              :key="tab.id"
              @click="orderFilter = tab.id"
              :class="[
                'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                orderFilter === tab.id
                  ? 'bg-purple-100 text-purple-600 border border-purple-200'
                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
              ]"
            >
              {{ tab.label }} ({{ getOrderCount(tab.id) }})
            </button>
          </div>
        </div>

        <!-- 订单列表 -->
        <div class="p-4">
          <div v-if="filteredOrders.length === 0" class="text-center py-12">
            <div class="text-4xl mb-4">📝</div>
            <p class="text-gray-500">暂无订单</p>
          </div>

          <div class="space-y-4 max-h-[500px] overflow-y-auto pr-2">
            <div v-for="order in filteredOrders" :key="order.id" 
                 class="border border-gray-200 rounded-xl p-4 hover:shadow-md transition-shadow">
              <div class="flex justify-between items-start mb-3">
                <div class="flex items-center gap-3">
                  <div class="bg-purple-100 text-purple-600 font-bold text-xl px-4 py-2 rounded-lg">
                    {{ order.pickupCode }}
                  </div>
                  <span v-if="order.isManual" class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded">
                    手动补单
                  </span>
                </div>
                <div class="flex items-center gap-3">
                  <span class="text-sm text-gray-500">{{ formatTime(order.createdAt) }}</span>
                  <span :class="[
                    'px-3 py-1 rounded-full text-xs font-bold',
                    order.status === 'pending' 
                      ? 'bg-yellow-100 text-yellow-800'
                      : 'bg-green-100 text-green-800'
                  ]">
                    {{ order.status === 'pending' ? '制作中' : '已完成' }}
                  </span>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <div class="bg-gray-50 p-3 rounded-lg">
                  <p class="text-sm text-gray-500 mb-1">规格</p>
                  <p class="font-bold text-gray-700">{{ order.sizeName }}</p>
                  <p class="text-xs text-gray-400">{{ order.count }}个 ¥{{ order.totalPrice }}</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                  <p class="text-sm text-gray-500 mb-1">口味</p>
                  <div class="flex flex-wrap gap-1">
                    <span v-for="flavorName in getFlavorNames(order.flavors)" 
                          :key="flavorName"
                          class="inline-flex items-center gap-1 bg-purple-50 text-purple-600 px-2 py-1 rounded text-xs">
                      {{ flavorName }}
                    </span>
                  </div>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                  <p class="text-sm text-gray-500 mb-1">小料</p>
                  <div class="flex flex-wrap gap-1">
                    <span v-for="toppingName in getToppingNames(order.toppings)" 
                          :key="toppingName"
                          class="bg-blue-50 text-blue-600 px-2 py-1 rounded text-xs">
                      {{ toppingName }}
                    </span>
                    <span v-if="!order.toppings || order.toppings.length === 0" class="text-gray-400 text-xs">
                      无小料
                    </span>
                  </div>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                  <p class="text-sm text-gray-500 mb-1">操作</p>
                  <div class="flex gap-2">
                    <button 
                      v-if="order.status === 'pending'"
                      @click="completeOrder(order.id)"
                      class="flex-1 bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                    >
                      完成制作
                    </button>
                    <button 
                      @click="deleteOrder(order.id)"
                      class="flex-1 bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                    >
                      删除
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab 2: 历史统计 -->
      <div v-if="activeTab === 'history'" class="bg-white rounded-b-lg shadow border border-gray-200 p-6">
        <div class="mb-6">
          <h3 class="text-lg font-bold text-gray-700 mb-4">📅 历史数据查询</h3>
          <div class="flex flex-col md:flex-row gap-4 items-end">
            <div class="flex-1">
              <label class="block text-sm font-medium text-gray-700 mb-2">开始日期</label>
              <input 
                type="date" 
                v-model="startDate"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              >
            </div>
            <div class="flex-1">
              <label class="block text-sm font-medium text-gray-700 mb-2">结束日期</label>
              <input 
                type="date" 
                v-model="endDate"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              >
            </div>
            <button 
              @click="loadHistoryStats"
              class="bg-purple-600 text-white px-6 py-2.5 rounded-lg font-medium hover:bg-purple-700 transition-colors"
            >
              查询统计
            </button>
          </div>
        </div>

        <!-- 统计结果 -->
        <div v-if="historyStats" class="space-y-6">
          <!-- 总览统计 -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
              <p class="text-sm text-gray-600 mb-1">总订单数</p>
              <p class="text-2xl font-bold text-gray-800">{{ historyStats.totalOrders }}</p>
            </div>
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
              <p class="text-sm text-gray-600 mb-1">总收入</p>
              <p class="text-2xl font-bold text-gray-800">¥{{ historyStats.totalRevenue }}</p>
            </div>
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
              <p class="text-sm text-gray-600 mb-1">小份订单</p>
              <p class="text-2xl font-bold text-gray-800">{{ historyStats.sizeDistribution?.small || 0 }}</p>
            </div>
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
              <p class="text-sm text-gray-600 mb-1">大份订单</p>
              <p class="text-2xl font-bold text-gray-800">{{ historyStats.sizeDistribution?.large || 0 }}</p>
            </div>
          </div>

          <!-- 口味统计 -->
          <div v-if="historyStats.flavorStats && Object.keys(historyStats.flavorStats).length > 0" class="bg-gray-50 rounded-xl p-5">
            <h4 class="text-lg font-bold text-gray-700 mb-4">口味统计</h4>
            <div class="space-y-3">
              <div v-for="(count, flavor) in historyStats.flavorStats" 
                   :key="flavor"
                   class="flex items-center gap-4">
                <div class="w-24">
                  <span class="font-medium text-gray-700">{{ flavor }}</span>
                </div>
                <div class="flex-1">
                  <div class="h-6 bg-gray-200 rounded-full overflow-hidden">
                    <div 
                      class="h-full bg-purple-500 rounded-full transition-all duration-700"
                      :style="{ width: getFlavorPercentage(flavor) + '%' }"
                    ></div>
                  </div>
                </div>
                <div class="w-12 text-right">
                  <span class="font-bold text-gray-800">{{ count }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- 小料统计 -->
          <div v-if="historyStats.toppingStats && Object.keys(historyStats.toppingStats).length > 0" class="bg-gray-50 rounded-xl p-5">
            <h4 class="text-lg font-bold text-gray-700 mb-4">小料统计</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div v-for="(count, topping) in historyStats.toppingStats" 
                   :key="topping"
                   class="bg-white p-4 rounded-lg border border-gray-200">
                <div class="flex justify-between items-center mb-2">
                  <span class="font-medium text-gray-700">{{ topping }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <div class="w-full bg-gray-200 rounded-full h-2.5 mr-3">
                    <div 
                      class="h-2.5 bg-blue-500 rounded-full"
                      :style="{ width: getToppingPercentage(topping) + '%' }"
                    ></div>
                  </div>
                  <span class="font-bold text-gray-800">{{ count }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="text-center py-12">
          <div class="text-4xl mb-4">📊</div>
          <p class="text-gray-500">选择日期范围查看历史统计</p>
        </div>
      </div>

      <!-- Tab 3: 数据分析 -->
      <div v-if="activeTab === 'analytics'" class="bg-white rounded-b-lg shadow border border-gray-200 p-6">
        <div class="mb-6">
          <h3 class="text-lg font-bold text-gray-700 mb-2">📈 多维度数据分析</h3>
          <p class="text-gray-600 text-sm">基于最近30天的订单数据进行多维度分析</p>
        </div>

        <!-- 数据分析结果 -->
        <div v-if="analyticsData" class="space-y-8">
          <!-- 热门口味 -->
          <div class="bg-gradient-to-r from-orange-50 to-red-50 border border-orange-200 rounded-xl p-5">
            <h4 class="text-lg font-bold text-gray-700 mb-4 flex items-center gap-2">
              <span>🔥</span> 热门口味排行
            </h4>
            <div class="space-y-3">
              <div v-for="(flavor, index) in analyticsData.popularFlavors.slice(0, 5)" 
                   :key="flavor.name"
                   class="flex items-center gap-4 p-3 bg-white rounded-lg border border-orange-100">
                <div class="w-8 h-8 bg-gradient-to-br from-orange-500 to-red-500 text-white rounded-full flex items-center justify-center font-bold">
                  {{ index + 1 }}
                </div>
                <div class="flex-1">
                  <div class="flex justify-between items-center mb-1">
                    <span class="font-bold text-gray-800">{{ flavor.name }}</span>
                    <span class="text-gray-600">{{ flavor.count }}次</span>
                  </div>
                  <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div 
                      class="h-full bg-gradient-to-r from-orange-400 to-red-400 rounded-full"
                      :style="{ width: (flavor.count / analyticsData.totalOrders * 100) + '%' }"
                    ></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 份量分析 -->
          <div class="bg-gradient-to-r from-purple-50 to-blue-50 border border-purple-200 rounded-xl p-5">
            <h4 class="text-lg font-bold text-gray-700 mb-4">📊 份量分析</h4>
            <div class="grid grid-cols-2 gap-4">
              <div class="bg-white p-4 rounded-lg border border-purple-100">
                <div class="text-center mb-3">
                  <div class="text-2xl font-bold text-purple-600 mb-1">{{ analyticsData.sizeStats.small.count }}</div>
                  <p class="text-sm text-gray-600">小份订单</p>
                </div>
                <div class="text-center">
                  <div class="text-2xl font-bold text-green-600 mb-1">¥{{ analyticsData.sizeStats.small.revenue }}</div>
                  <p class="text-sm text-gray-600">小份收入</p>
                </div>
              </div>
              <div class="bg-white p-4 rounded-lg border border-blue-100">
                <div class="text-center mb-3">
                  <div class="text-2xl font-bold text-blue-600 mb-1">{{ analyticsData.sizeStats.large.count }}</div>
                  <p class="text-sm text-gray-600">大份订单</p>
                </div>
                <div class="text-center">
                  <div class="text-2xl font-bold text-green-600 mb-1">¥{{ analyticsData.sizeStats.large.revenue }}</div>
                  <p class="text-sm text-gray-600">大份收入</p>
                </div>
              </div>
            </div>
          </div>

          <!-- 时段分析 -->
          <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-5">
            <h4 class="text-lg font-bold text-gray-700 mb-4">⏰ 订单时段分布</h4>
            <div class="grid grid-cols-4 md:grid-cols-6 lg:grid-cols-12 gap-2">
              <div v-for="hour in 24" :key="hour" class="text-center">
                <div class="text-xs text-gray-500 mb-1">{{ hour }}时</div>
                <div class="h-20 relative bg-gray-100 rounded overflow-hidden">
                  <div 
                    v-if="analyticsData.hourlyStats[hour]"
                    class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-green-400 to-emerald-400 rounded"
                    :style="{ height: (analyticsData.hourlyStats[hour] / maxHourlyCount * 100) + '%' }"
                  ></div>
                </div>
                <div class="text-xs font-medium text-gray-700 mt-1">
                  {{ analyticsData.hourlyStats[hour] || 0 }}
                </div>
              </div>
            </div>
          </div>

          <!-- 口味组合分析 -->
          <div v-if="analyticsData.flavorCombinations.length > 0" class="bg-gradient-to-r from-pink-50 to-rose-50 border border-pink-200 rounded-xl p-5">
            <h4 class="text-lg font-bold text-gray-700 mb-4">🎯 热门口味组合</h4>
            <div class="space-y-3">
              <div v-for="combo in analyticsData.flavorCombinations.slice(0, 5)" 
                   :key="combo.combo"
                   class="flex items-center justify-between p-3 bg-white rounded-lg border border-pink-100">
                <div>
                  <span class="font-medium text-gray-800">
                    {{ getComboNames(combo.combo) }}
                  </span>
                  <span class="text-xs text-gray-500 ml-2">{{ combo.count }}次</span>
                </div>
                <div class="text-sm text-pink-600 font-medium">
                  {{ (combo.count / analyticsData.totalOrders * 100).toFixed(1) }}%
                </div>
              </div>
            </div>
          </div>

          <!-- 小料分析 -->
          <div v-if="analyticsData.toppingStats && Object.keys(analyticsData.toppingStats).length > 0" class="bg-gradient-to-r from-cyan-50 to-blue-50 border border-cyan-200 rounded-xl p-5">
            <h4 class="text-lg font-bold text-gray-700 mb-4">✨ 小料使用情况</h4>
            <div class="space-y-3">
              <div v-for="(count, topping) in analyticsData.toppingStats" 
                   :key="topping"
                   class="flex items-center gap-4">
                <div class="w-24">
                  <span class="font-medium text-gray-700">{{ topping }}</span>
                </div>
                <div class="flex-1">
                  <div class="h-6 bg-gray-200 rounded-full overflow-hidden">
                    <div 
                      class="h-full bg-gradient-to-r from-cyan-400 to-blue-400 rounded-full"
                      :style="{ width: (count / analyticsData.totalOrders * 100) + '%' }"
                    ></div>
                  </div>
                </div>
                <div class="w-12 text-right">
                  <span class="font-bold text-gray-800">{{ count }}</span>
                  <span class="text-xs text-gray-500">({{ (count / analyticsData.totalOrders * 100).toFixed(1) }}%)</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="text-center py-12">
          <div class="text-4xl mb-4">📈</div>
          <p class="text-gray-500">暂无足够数据进行分析</p>
        </div>
      </div>
    </div>

    <!-- 手动补单悬浮按钮 -->
    <button 
      @click="showManualOrder = true"
      class="fixed bottom-6 right-6 w-14 h-14 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-full shadow-lg hover:shadow-xl hover:scale-110 transition-all duration-200 flex items-center justify-center text-2xl z-50"
      title="手动补单"
    >
      ✏️
    </button>

    <!-- 手动补单弹窗 -->
    <div v-if="showManualOrder" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200 sticky top-0 bg-white">
          <div class="flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
              <span>✏️</span> 手动补单
            </h3>
            <button @click="showManualOrder = false" class="text-gray-400 hover:text-gray-600 text-2xl">
              &times;
            </button>
          </div>
        </div>

        <div class="p-6 space-y-6">
          <!-- 份量选择 -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-3">选择份量</label>
            <div class="grid grid-cols-2 gap-3">
              <div 
                v-for="size in store.settings?.sizes || []" 
                :key="size.id"
                @click="manualOrder.size = size.id"
                :class="[
                  'border-2 rounded-xl p-4 cursor-pointer transition-all',
                  manualOrder.size === size.id
                    ? 'border-purple-500 bg-purple-50'
                    : 'border-gray-200 hover:border-gray-300'
                ]"
              >
                <div class="flex justify-between items-start">
                  <div>
                    <p class="font-bold text-gray-800">{{ size.name }}</p>
                    <p class="text-sm text-gray-500 mt-1">{{ size.count }}个</p>
                  </div>
                  <span class="text-lg font-bold text-purple-600">¥{{ size.basePrice }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- 口味选择 -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-3">选择口味（可多选）</label>
            <div class="grid grid-cols-2 gap-3">
              <div 
                v-for="flavor in store.settings?.flavors || []" 
                :key="flavor.id"
                @click="toggleManualFlavor(flavor.id)"
                :class="[
                  'border-2 rounded-xl p-4 cursor-pointer transition-all',
                  manualOrder.flavors.includes(flavor.id)
                    ? 'border-orange-500 bg-orange-50'
                    : 'border-gray-200 hover:border-gray-300'
                ]"
              >
                <div class="flex justify-between items-center">
                  <span class="font-medium text-gray-800">{{ flavor.name }}</span>
                  <span class="text-green-600 text-sm">免费</span>
                </div>
              </div>
            </div>
          </div>

          <!-- 小料选择 -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-3">选择小料（可多选）</label>
            <div class="grid grid-cols-2 gap-3">
              <div 
                v-for="topping in store.settings?.toppings || []" 
                :key="topping.id"
                @click="toggleManualTopping(topping.id)"
                :class="[
                  'border-2 rounded-xl p-4 cursor-pointer transition-all',
                  manualOrder.toppings.includes(topping.id)
                    ? 'border-blue-500 bg-blue-50'
                    : 'border-gray-200 hover:border-gray-300'
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
          <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <div class="flex justify-between items-center">
              <span class="text-gray-600">总计金额</span>
              <span class="text-2xl font-bold text-purple-600">¥{{ manualOrderTotal }}</span>
            </div>
            <div class="mt-2 text-sm text-gray-500">
              <p>包含：{{ getSizeName(manualOrder.size) }} × 1</p>
            </div>
          </div>
        </div>

        <div class="p-6 border-t border-gray-200 flex gap-3">
          <button 
            @click="showManualOrder = false"
            class="flex-1 px-4 py-3 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors"
          >
            取消
          </button>
          <button 
            @click="submitManualOrder"
            :disabled="manualOrder.flavors.length === 0"
            :class="[
              'flex-1 px-4 py-3 rounded-lg font-medium transition-colors',
              manualOrder.flavors.length === 0
                ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                : 'bg-purple-600 text-white hover:bg-purple-700'
            ]"
          >
            确认补单
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { useOrderStore } from '../stores/orderStore';

export default {
  name: 'SellerView',
  setup() {
    const store = useOrderStore();
    const activeTab = ref('today');
    const orderFilter = ref('all');
    const showManualOrder = ref(false);
    const startDate = ref('');
    const endDate = ref('');
    const historyStats = ref(null);
    
    const manualOrder = ref({
      size: 'small',
      flavors: [],
      toppings: []
    });

    const tabs = [
      { id: 'today', label: '当日订单', icon: '📋' },
      { id: 'history', label: '历史统计', icon: '📊' },
      { id: 'analytics', label: '数据分析', icon: '📈' }
    ];

    const orderTabs = [
      { id: 'all', label: '全部订单' },
      { id: 'pending', label: '待制作' },
      { id: 'completed', label: '已完成' }
    ];

    const currentDate = computed(() => {
      return new Date().toLocaleDateString('zh-CN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        weekday: 'long'
      });
    });

    const safeTodayOrders = computed(() => {
      return store.todayOrders || [];
    });

    const pendingCount = computed(() => {
      return safeTodayOrders.value.filter(o => o.status === 'pending').length;
    });

    const todayRevenue = computed(() => {
      return safeTodayOrders.value.reduce((sum, o) => sum + (o.totalPrice || 0), 0);
    });

    const filteredOrders = computed(() => {
      if (!safeTodayOrders.value.length) return [];
      if (orderFilter.value === 'all') return safeTodayOrders.value;
      return safeTodayOrders.value.filter(o => o.status === orderFilter.value);
    });

    const getOrderCount = (type) => {
      if (type === 'all') return safeTodayOrders.value.length;
      return safeTodayOrders.value.filter(o => o.status === type).length;
    };

    const getSizeName = (sizeId) => {
      const size = store.settings?.sizes?.find(s => s.id === sizeId);
      return size ? size.name : '';
    };

    const manualOrderTotal = computed(() => {
      const size = store.settings?.sizes?.find(s => s.id === manualOrder.value.size);
      return size ? size.basePrice : 0;
    });

    const getFlavorNames = (flavorIds) => {
      if (!flavorIds || !Array.isArray(flavorIds)) return [];
      return flavorIds.map(id => {
        const flavor = store.settings?.flavors?.find(f => f.id === id);
        return flavor ? flavor.name : '';
      }).filter(name => name);
    };

    const getToppingNames = (toppingIds) => {
      if (!toppingIds || !Array.isArray(toppingIds)) return [];
      return toppingIds.map(id => {
        const topping = store.settings?.toppings?.find(t => t.id === id);
        return topping ? topping.name : '';
      }).filter(name => name);
    };

    const getComboNames = (comboString) => {
      if (!comboString || comboString === '无小料') return comboString;
      const ids = comboString.split('-').map(Number);
      return ids.map(id => {
        const flavor = store.settings?.flavors?.find(f => f.id === id);
        return flavor ? flavor.name : '';
      }).join(' + ');
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

    const completeOrder = (orderId) => {
      if (confirm('确认订单已完成？')) {
        store.completeOrder(orderId);
      }
    };

    const deleteOrder = (orderId) => {
      if (confirm('确定删除此订单？')) {
        store.deleteOrder(orderId);
      }
    };

    const toggleManualFlavor = (flavorId) => {
      const index = manualOrder.value.flavors.indexOf(flavorId);
      if (index > -1) {
        manualOrder.value.flavors.splice(index, 1);
      } else {
        manualOrder.value.flavors.push(flavorId);
      }
    };

    const toggleManualTopping = (toppingId) => {
      const index = manualOrder.value.toppings.indexOf(toppingId);
      if (index > -1) {
        manualOrder.value.toppings.splice(index, 1);
      } else {
        manualOrder.value.toppings.push(toppingId);
      }
    };

    const submitManualOrder = () => {
      if (manualOrder.value.flavors.length === 0) {
        alert('请至少选择一个口味');
        return;
      }
      
      const code = store.addManualOrder(manualOrder.value);
      alert(`手动补单成功！取件码：${code}`);
      showManualOrder.value = false;
      
      manualOrder.value = {
        size: 'small',
        flavors: [],
        toppings: []
      };
    };

    const loadHistoryStats = () => {
      if (!startDate.value || !endDate.value) {
        alert('请选择日期范围');
        return;
      }
      
      historyStats.value = store.getStatsByDate(startDate.value, endDate.value);
    };

    const getFlavorPercentage = (flavor) => {
      if (!historyStats.value || historyStats.value.totalOrders === 0) return 0;
      const count = historyStats.value.flavorStats?.[flavor] || 0;
      return Math.round((count / historyStats.value.totalOrders) * 100);
    };

    const getToppingPercentage = (topping) => {
      if (!historyStats.value || historyStats.value.totalOrders === 0) return 0;
      const count = historyStats.value.toppingStats?.[topping] || 0;
      return Math.round((count / historyStats.value.totalOrders) * 100);
    };

    // 多维度数据分析
    const analyticsData = computed(() => {
      return store.getMultiDimensionStats();
    });

    const maxHourlyCount = computed(() => {
      if (!analyticsData.value?.hourlyStats) return 1;
      return Math.max(...Object.values(analyticsData.value.hourlyStats), 1);
    });

    onMounted(() => {
      // 设置默认查询日期为最近30天
      const end = new Date();
      const start = new Date();
      start.setDate(start.getDate() - 30);
      
      endDate.value = end.toISOString().split('T')[0];
      startDate.value = start.toISOString().split('T')[0];
    });

    return {
      store,
      activeTab,
      tabs,
      orderFilter,
      orderTabs,
      showManualOrder,
      startDate,
      endDate,
      historyStats,
      manualOrder,
      analyticsData,
      maxHourlyCount,
      currentDate,
      safeTodayOrders,
      pendingCount,
      todayRevenue,
      filteredOrders,
      getOrderCount,
      getSizeName,
      manualOrderTotal,
      getFlavorNames,
      getToppingNames,
      getComboNames,
      formatTime,
      completeOrder,
      deleteOrder,
      toggleManualFlavor,
      toggleManualTopping,
      submitManualOrder,
      loadHistoryStats,
      getFlavorPercentage,
      getToppingPercentage
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