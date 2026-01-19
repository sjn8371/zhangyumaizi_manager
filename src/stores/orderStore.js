import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useOrderStore = defineStore('orders', () => {
  // 订单数据
  const orders = ref([]);
  const mode = ref('buyer');
  
  // 商家设置
  const settings = ref({
    sizes: [
      { id: 'small', name: '小份', basePrice: 10, count: 6 },
      { id: 'large', name: '大份', basePrice: 15, count: 10 }
    ],
    flavors: [
      { id: 1, name: '照烧', price: 0 },
      { id: 2, name: '番茄', price: 0 },
      { id: 3, name: '蜂蜜芥末', price: 0 }
    ],
    toppings: [
      { id: 1, name: '海苔', price: 0 },
      { id: 2, name: '肉松', price: 0 }
    ]
  });

  // 获取今日订单
  const todayOrders = computed(() => {
    try {
      const today = new Date().toISOString().split('T')[0];
      return orders.value.filter(order => 
        order.createdAt && order.createdAt.startsWith(today)
      );
    } catch (error) {
      return [];
    }
  });

  // 生成取件码
  const generatePickupCode = () => {
    const code = Math.floor(1000 + Math.random() * 9000).toString();
    const existingCodes = orders.value.map(order => order.pickupCode);
    return existingCodes.includes(code) ? generatePickupCode() : code;
  };

  // 添加新订单
  const addOrder = (orderData) => {
    const sizeObj = settings.value.sizes.find(s => s.id === orderData.size);
    
    const newOrder = {
      id: Date.now().toString(),
      pickupCode: generatePickupCode(),
      size: orderData.size,
      sizeName: sizeObj ? sizeObj.name : '',
      flavors: orderData.flavors || [],
      toppings: orderData.toppings || [],
      count: sizeObj ? sizeObj.count : 0,
      totalPrice: calculatePrice(orderData),
      status: 'pending',
      createdAt: new Date().toISOString(),
      completedAt: null,
      isManual: false
    };
    
    orders.value.unshift(newOrder);
    saveToLocalStorage();
    
    if (window.syncManager) {
      window.syncManager.sendNewOrder(newOrder);
    }
    
    return newOrder.pickupCode;
  };

  // 手动补单
  const addManualOrder = (orderData) => {
    const orderDataWithManual = {
      ...orderData,
      isManual: true
    };
    return addOrder(orderDataWithManual);
  };

  // 计算价格
  const calculatePrice = (orderData) => {
    const size = settings.value.sizes.find(s => s.id === orderData.size);
    return size ? size.basePrice : 0;
  };

  // 完成订单
  const completeOrder = (orderId) => {
    const order = orders.value.find(o => o.id === orderId);
    if (order) {
      order.status = 'completed';
      order.completedAt = new Date().toISOString();
      saveToLocalStorage();
      
      if (window.syncManager) {
        window.syncManager.sendCompleteOrder(orderId);
      }
    }
  };

  // 删除订单
  const deleteOrder = (orderId) => {
    const index = orders.value.findIndex(o => o.id === orderId);
    if (index > -1) {
      orders.value.splice(index, 1);
      saveToLocalStorage();
      
      if (window.syncManager) {
        window.syncManager.sendDeleteOrder(orderId);
      }
    }
  };

  // 获取多维度统计数据
  const getMultiDimensionStats = () => {
    const today = new Date();
    const oneWeekAgo = new Date(today);
    oneWeekAgo.setDate(today.getDate() - 7);
    const oneMonthAgo = new Date(today);
    oneMonthAgo.setMonth(today.getMonth() - 1);
    
    const recentOrders = orders.value.filter(order => 
      new Date(order.createdAt) >= oneMonthAgo
    );
    
    const weeklyOrders = orders.value.filter(order => 
      new Date(order.createdAt) >= oneWeekAgo
    );
    
    // 口味分析
    const flavorStats = {};
    const flavorHourly = {};
    const flavorCombinations = {};
    
    // 份量分析
    const sizeStats = {
      small: { count: 0, revenue: 0 },
      large: { count: 0, revenue: 0 }
    };
    
    // 时段分析
    const hourlyStats = {};
    
    // 小料分析
    const toppingStats = {};
    const toppingCombinations = {};
    
    recentOrders.forEach(order => {
      // 时段统计
      const hour = new Date(order.createdAt).getHours();
      hourlyStats[hour] = (hourlyStats[hour] || 0) + 1;
      
      // 份量统计
      if (order.size === 'small') {
        sizeStats.small.count++;
        sizeStats.small.revenue += order.totalPrice;
      } else {
        sizeStats.large.count++;
        sizeStats.large.revenue += order.totalPrice;
      }
      
      // 口味统计
      order.flavors.forEach(flavorId => {
        const flavor = settings.value.flavors.find(f => f.id === flavorId);
        if (flavor) {
          flavorStats[flavor.name] = (flavorStats[flavor.name] || 0) + 1;
          
          // 口味时段偏好
          if (!flavorHourly[flavor.name]) {
            flavorHourly[flavor.name] = {};
          }
          flavorHourly[flavor.name][hour] = (flavorHourly[flavor.name][hour] || 0) + 1;
        }
      });
      
      // 口味组合统计
      const flavorCombo = order.flavors.sort().join('-');
      flavorCombinations[flavorCombo] = (flavorCombinations[flavorCombo] || 0) + 1;
      
      // 小料统计
      order.toppings.forEach(toppingId => {
        const topping = settings.value.toppings.find(t => t.id === toppingId);
        if (topping) {
          toppingStats[topping.name] = (toppingStats[topping.name] || 0) + 1;
        }
      });
      
      // 小料组合统计
      const toppingCombo = order.toppings.sort().join('-') || '无小料';
      toppingCombinations[toppingCombo] = (toppingCombinations[toppingCombo] || 0) + 1;
    });
    
    // 热门口味（近一周）
    const weeklyFlavorStats = {};
    weeklyOrders.forEach(order => {
      order.flavors.forEach(flavorId => {
        const flavor = settings.value.flavors.find(f => f.id === flavorId);
        if (flavor) {
          weeklyFlavorStats[flavor.name] = (weeklyFlavorStats[flavor.name] || 0) + 1;
        }
      });
    });
    
    const popularFlavors = Object.entries(weeklyFlavorStats)
      .map(([name, count]) => ({ name, count }))
      .sort((a, b) => b.count - a.count);
    
    return {
      // 基础统计
      totalOrders: recentOrders.length,
      totalRevenue: recentOrders.reduce((sum, o) => sum + o.totalPrice, 0),
      
      // 时段分析
      hourlyStats,
      
      // 份量分析
      sizeStats,
      sizePercentage: {
        small: sizeStats.small.count / recentOrders.length * 100 || 0,
        large: sizeStats.large.count / recentOrders.length * 100 || 0
      },
      sizeRevenuePercentage: {
        small: sizeStats.small.revenue / (sizeStats.small.revenue + sizeStats.large.revenue) * 100 || 0,
        large: sizeStats.large.revenue / (sizeStats.small.revenue + sizeStats.large.revenue) * 100 || 0
      },
      
      // 口味分析
      flavorStats,
      popularFlavors,
      flavorHourly,
      flavorCombinations: Object.entries(flavorCombinations)
        .map(([combo, count]) => ({ combo, count }))
        .sort((a, b) => b.count - a.count)
        .slice(0, 10),
      
      // 小料分析
      toppingStats,
      toppingCombinations: Object.entries(toppingCombinations)
        .map(([combo, count]) => ({ combo, count }))
        .sort((a, b) => b.count - a.count),
      
      // 时间范围
      startDate: oneMonthAgo.toISOString().split('T')[0],
      endDate: today.toISOString().split('T')[0]
    };
  };

  // 根据日期筛选订单
  const getOrdersByDate = (startDate, endDate) => {
    const start = new Date(startDate);
    const end = new Date(endDate);
    end.setHours(23, 59, 59, 999);
    
    return orders.value.filter(order => {
      const orderDate = new Date(order.createdAt);
      return orderDate >= start && orderDate <= end;
    });
  };

  // 获取统计数据
  const getStatsByDate = (startDate, endDate) => {
    const filteredOrders = getOrdersByDate(startDate, endDate);
    
    const stats = {
      totalOrders: filteredOrders.length,
      totalRevenue: filteredOrders.reduce((sum, o) => sum + o.totalPrice, 0),
      sizeDistribution: {
        small: 0,
        large: 0
      },
      flavorStats: {},
      toppingStats: {}
    };

    filteredOrders.forEach(order => {
      // 统计大小
      stats.sizeDistribution[order.size]++;
      
      // 统计口味
      order.flavors.forEach(flavorId => {
        const flavor = settings.value.flavors.find(f => f.id === flavorId);
        if (flavor) {
          stats.flavorStats[flavor.name] = (stats.flavorStats[flavor.name] || 0) + 1;
        }
      });
      
      // 统计小料
      order.toppings.forEach(toppingId => {
        const topping = settings.value.toppings.find(t => t.id === toppingId);
        if (topping) {
          stats.toppingStats[topping.name] = (stats.toppingStats[topping.name] || 0) + 1;
        }
      });
    });

    return stats;
  };

  // 本地存储
  const saveToLocalStorage = () => {
    try {
      localStorage.setItem('octopus_orders', JSON.stringify(orders.value));
      localStorage.setItem('octopus_settings', JSON.stringify(settings.value));
    } catch (error) {
      console.error('保存到本地存储失败:', error);
    }
  };

  const loadFromLocalStorage = () => {
    try {
      const savedOrders = localStorage.getItem('octopus_orders');
      const savedSettings = localStorage.getItem('octopus_settings');
      
      if (savedOrders) orders.value = JSON.parse(savedOrders);
      if (savedSettings) settings.value = JSON.parse(savedSettings);
    } catch (error) {
      console.error('从本地存储加载失败:', error);
    }
  };

  // 设置模式
  const setMode = (newMode) => {
    mode.value = newMode;
  };

  // 导出数据
  const exportData = () => {
    const data = {
      orders: orders.value,
      settings: settings.value,
      exportDate: new Date().toISOString()
    };
    return JSON.stringify(data, null, 2);
  };

  // 导入数据
  const importData = (data) => {
    try {
      const parsed = JSON.parse(data);
      if (parsed.orders) orders.value = parsed.orders;
      if (parsed.settings) settings.value = parsed.settings;
      saveToLocalStorage();
      return true;
    } catch (error) {
      console.error('导入数据失败:', error);
      return false;
    }
  };

  // 初始化
  loadFromLocalStorage();

  return {
    orders,
    todayOrders,
    mode,
    settings,
    getMultiDimensionStats,
    addOrder,
    addManualOrder,
    completeOrder,
    deleteOrder,
    getOrdersByDate,
    getStatsByDate,
    setMode,
    exportData,
    importData,
    loadFromLocalStorage,
    saveToLocalStorage
  };
});