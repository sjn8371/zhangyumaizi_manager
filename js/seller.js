document.addEventListener('DOMContentLoaded', function() {
  // 全局变量
  let orderToDelete = null;
  let lastRefreshTime = 0;
  let refreshInterval = null;
  let refreshClickCount = 0;
  let lastRefreshClickTime = 0;
  let currentQrUrl = '';
  let currentSecretKey = '';
  let currentExpiry = '';
  
  // 初始化
  function initialize() {
      // 加载当日订单
      loadOrders('today');
      
      // Tab切换
      document.querySelectorAll('.tab').forEach(tab => {
          tab.addEventListener('click', function() {
              const tabId = this.dataset.tab;
              
              // 更新活跃Tab
              document.querySelectorAll('.tab').forEach(t => {
                  t.classList.remove('active');
              });
              this.classList.add('active');
              
              // 显示对应内容
              document.querySelectorAll('.tab-content').forEach(content => {
                  content.classList.remove('active');
              });
              document.getElementById(tabId).classList.add('active');
              
              // 加载对应数据
              switch(tabId) {
                  case 'today-orders':
                      loadOrders('today');
                      break;
                  case 'history-orders':
                      // 不自动加载，等待用户筛选
                      break;
                  case 'analytics':
                      loadAnalytics();
                      break;
              }
          });
      });
      
      // 刷新时间点击事件
      const refreshTimeElement = document.getElementById('refresh-time');
      refreshTimeElement.addEventListener('click', handleRefreshClick);
      
      // 开店生成二维码按钮
      document.getElementById('generate-qr').addEventListener('click', generateQRCode);
      
      // 闭店按钮
      document.getElementById('close-shop').addEventListener('click', showCloseShopModal);
      
      // 历史订单筛选
      document.getElementById('apply-history-filter').addEventListener('click', loadHistoryOrders);
      
      // 导出历史订单
      document.getElementById('export-history').addEventListener('click', exportHistoryData);
      
      // 刷新分析按钮
      document.getElementById('refresh-analytics').addEventListener('click', loadAnalytics);
      
      // 弹窗关闭按钮
      document.getElementById('close-qr-modal').addEventListener('click', closeQRModal);
      document.getElementById('close-qr').addEventListener('click', closeQRModal);
      document.getElementById('close-delete-modal').addEventListener('click', closeDeleteModal);
      document.getElementById('close-shop-modal-btn').addEventListener('click', closeCloseShopModal);
      
      // 删除确认弹窗按钮
      document.getElementById('cancel-delete').addEventListener('click', closeDeleteModal);
      document.getElementById('confirm-delete').addEventListener('click', confirmDelete);
      
      // 闭店确认弹窗按钮
      document.getElementById('cancel-close-shop').addEventListener('click', closeCloseShopModal);
      document.getElementById('confirm-close-shop').addEventListener('click', confirmCloseShop);
      
      // 下载二维码按钮
      document.getElementById('download-qr').addEventListener('click', downloadQRCode);
      
      // 启动3秒自动刷新
      startAutoRefresh();
  }
  
  // 处理刷新时间点击
  function handleRefreshClick() {
      const now = Date.now();
      
      // 如果距离上次点击超过5秒，重置计数
      if (now - lastRefreshClickTime > 5000) {
          refreshClickCount = 0;
      }
      
      refreshClickCount++;
      lastRefreshClickTime = now;
      
      // 如果连续点击10次，退出登录
      if (refreshClickCount >= 10) {
          logout();
          return;
      }
      
      // 手动刷新订单
      loadOrders('today');
  }
  
  // 退出登录
  function logout() {
      fetch('api/logout.php')
          .then(response => response.json())
          .then(data => {
              if (data.success) {
                  window.location.href = 'index.php';
              }
          })
          .catch(error => {
              console.error('退出登录失败:', error);
              window.location.href = 'index.php';
          });
  }
  
  // 启动自动刷新
  function startAutoRefresh() {
      // 清除现有定时器
      if (refreshInterval) {
          clearInterval(refreshInterval);
      }
      
      // 设置3秒刷新一次
      refreshInterval = setInterval(() => {
          const now = Math.floor(Date.now() / 1000);
          const secondsAgo = now - lastRefreshTime;
          
          // 更新刷新时间显示
          const refreshTimeElement = document.getElementById('refresh-time');
          refreshTimeElement.textContent = secondsAgo + 's前';
          
          // 每3秒刷新一次
          if (secondsAgo >= 3) {
              if (document.getElementById('today-orders').classList.contains('active')) {
                  loadOrders('today', true); // true表示静默刷新
              }
          }
      }, 1000);
  }
  
  // 加载订单
  function loadOrders(type = 'today', silent = false) {
      const activeTab = document.querySelector('.tab.active').dataset.tab;
      
      if (type === 'today' && activeTab !== 'today-orders') {
          return; // 如果不是当前显示的Tab，不加载
      }
      
      const ordersList = document.getElementById('orders-list');
      if (!silent) {
          ordersList.innerHTML = '<div class="loading">正在加载订单...</div>';
      }
      
      lastRefreshTime = Math.floor(Date.now() / 1000);
      
      fetch('api/get_orders.php?type=' + type)
          .then(response => response.json())
          .then(data => {
              if (data.success) {
                  displayOrders(data.orders, type);
                  updateStats(data.orders, data.stats);
              } else {
                  if (!silent) {
                      ordersList.innerHTML = '<div class="error">加载订单失败: ' + data.message + '</div>';
                  }
              }
          })
          .catch(error => {
              if (!silent) {
                  ordersList.innerHTML = '<div class="error">网络错误，请稍后重试</div>';
              }
              console.error('加载订单失败:', error);
          });
  }
  
  // 显示订单
  function displayOrders(orders, type = 'today') {
      const ordersList = type === 'today' ? 
          document.getElementById('orders-list') : 
          document.getElementById('history-orders-list');
      const emptyState = type === 'today' ? 
          document.getElementById('empty-state') : 
          document.getElementById('history-empty-state');
      
      if (orders.length === 0) {
          ordersList.style.display = 'none';
          emptyState.style.display = 'block';
          return;
      }
      
      ordersList.style.display = 'block';
      emptyState.style.display = 'none';
      
      // 按时间倒序排序
      orders.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));
      
      // 生成订单HTML
      const ordersHTML = orders.map(order => createOrderItem(order)).join('');
      ordersList.innerHTML = ordersHTML;
      
      // 绑定操作按钮事件
      bindOrderActions();
  }
  
  // 创建扁平化订单项HTML
  function createOrderItem(order) {
      const orderTime = new Date(order.timestamp).toLocaleTimeString('zh-CN', {
          hour: '2-digit',
          minute: '2-digit'
      });
      const statusClass = order.status === 'completed' ? 'completed' : 'pending';
      const statusText = order.status === 'completed' ? '已完成' : '待处理';
      
      // 选项名称映射
      const optionNames = {
          portion: {
              small: "小份",
              large: "大份"
          },
          flavor: {
              salad: "沙",
              honey_mustard: "蜜",
              teriyaki: "照",
              tomato: "番"
          },
          topping: {
              bonito: "木",
              pork_floss: "肉",
              seaweed: "海"
          }
      };
      
      // 格式化口味
      const flavorsText = order.flavors && order.flavors.length > 0 
          ? order.flavors.map(f => optionNames.flavor[f]).join('+')
          : '原味';
      
      // 格式化小料
      const toppingsText = order.toppings && order.toppings.length > 0 
          ? order.toppings.map(t => optionNames.topping[t]).join('+')
          : '无';
      
      return `
          <div class="order-item ${statusClass}" data-order-id="${order.id}">
              <div class="order-id">#${order.id}</div>
              <div class="order-info">
                  <div class="order-line">
                      <span class="order-label">份:</span>
                      <span class="order-value">${optionNames.portion[order.portion]}</span>
                      <span class="order-label">味:</span>
                      <span class="order-value">${flavorsText}</span>
                      <span class="order-label">料:</span>
                      <span class="order-value">${toppingsText}</span>
                  </div>
                  <div class="order-line">
                      <span class="order-label">时:</span>
                      <span class="order-value">${orderTime}</span>
                      <span class="order-label">态:</span>
                      <span class="order-value">${statusText}</span>
                  </div>
              </div>
              <div class="order-price">${order.price}元</div>
              ${order.status === 'pending' ? `
                  <div class="order-actions">
                      <button class="action-btn complete-btn" data-action="complete" data-id="${order.id}">完成</button>
                      <button class="action-btn delete-btn" data-action="delete" data-id="${order.id}">删除</button>
                  </div>
              ` : ''}
          </div>
      `;
  }
  
  // 绑定订单操作事件
  function bindOrderActions() {
      // 标记出餐按钮
      document.querySelectorAll('.complete-btn').forEach(btn => {
          btn.addEventListener('click', function() {
              const orderId = this.dataset.id;
              updateOrderStatus(orderId, 'completed');
          });
      });
      
      // 删除订单按钮
      document.querySelectorAll('.delete-btn').forEach(btn => {
          btn.addEventListener('click', function() {
              const orderId = this.dataset.id;
              showDeleteModal(orderId);
          });
      });
  }
  
  // 更新统计信息
  function updateStats(orders, stats = null) {
      if (stats) {
          // 使用服务器计算的统计数据
          const total = stats.total || orders.length;
          const pending = stats.pending || orders.filter(o => o.status === 'pending').length;
          const revenue = stats.revenue || orders.reduce((sum, o) => sum + (parseInt(o.price) || 0), 0);
          
          // 更新顶部统计栏
          document.getElementById('total-revenue').textContent = revenue + '元';
          document.getElementById('pending-total-orders').textContent = pending + '/' + total;
          
      } else {
          // 客户端计算
          const total = orders.length;
          const pending = orders.filter(o => o.status === 'pending').length;
          const revenue = orders.reduce((sum, o) => sum + (parseInt(o.price) || 0), 0);
          
          document.getElementById('total-revenue').textContent = revenue + '元';
          document.getElementById('pending-total-orders').textContent = pending + '/' + total;
      }
  }
  
  // 更新订单状态
  function updateOrderStatus(orderId, status) {
      fetch('api/update_order.php', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json',
          },
          body: JSON.stringify({
              id: orderId,
              status: status
          })
      })
      .then(response => response.json())
      .then(data => {
          if (data.success) {
              loadOrders('today'); // 重新加载订单
          } else {
              alert('更新失败: ' + data.message);
          }
      })
      .catch(error => {
          console.error('更新订单状态失败:', error);
          alert('网络错误，请稍后重试');
      });
  }
  
  // 显示删除确认弹窗
  function showDeleteModal(orderId) {
      orderToDelete = orderId;
      const modal = document.getElementById('delete-modal');
      document.getElementById('delete-order-id').textContent = orderId;
      modal.style.display = 'flex';
  }
  
  // 关闭删除确认弹窗
  function closeDeleteModal() {
      const modal = document.getElementById('delete-modal');
      modal.style.display = 'none';
      orderToDelete = null;
  }
  
  // 确认删除订单
  function confirmDelete() {
      if (!orderToDelete) return;
      
      fetch('api/update_order.php', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json',
          },
          body: JSON.stringify({
              id: orderToDelete,
              action: 'delete'
          })
      })
      .then(response => response.json())
      .then(data => {
          if (data.success) {
              closeDeleteModal();
              loadOrders('today'); // 重新加载订单
          } else {
              alert('删除失败: ' + data.message);
          }
      })
      .catch(error => {
          console.error('删除订单失败:', error);
          alert('网络错误，请稍后重试');
      });
  }
  
  // 生成二维码
  function generateQRCode() {
      const qrBtn = document.getElementById('generate-qr');
      const originalText = qrBtn.textContent;
      
      // 防止重复点击
      qrBtn.disabled = true;
      qrBtn.textContent = '生成中...';
      
      fetch('api/generate_qr.php')
          .then(response => response.json())
          .then(data => {
              if (data.success) {
                  currentQrUrl = data.qr_url;
                  currentSecretKey = data.secret_key;
                  currentExpiry = data.expires_at;
                  showQRCodeModal();
              } else {
                  alert('生成失败: ' + data.message);
                  if (data.message.includes('登录')) {
                      window.location.href = 'index.php';
                  }
              }
              qrBtn.disabled = false;
              qrBtn.textContent = originalText;
          })
          .catch(error => {
              console.error('生成二维码失败:', error);
              alert('网络错误，请重试');
              qrBtn.disabled = false;
              qrBtn.textContent = originalText;
          });
  }
  
  // 显示二维码弹窗
  function showQRCodeModal() {
      const modal = document.getElementById('qr-modal');
      const qrContainer = document.getElementById('qrcode-container');
      const expiryElement = document.getElementById('qr-expiry');
      
      // 清空之前的二维码
      qrContainer.innerHTML = '';
      
      // 设置有效期信息
      expiryElement.textContent = `有效期至: ${currentExpiry}`;
      
      // 生成二维码
      if (typeof QRCode !== 'undefined') {
          new QRCode(qrContainer, {
              text: currentQrUrl,
              width: 200,
              height: 200,
              colorDark: "#000000",
              colorLight: "#ffffff",
              correctLevel: QRCode.CorrectLevel.H
          });
      } else {
          qrContainer.innerHTML = `
              <div style="padding: 20px; background: #f5f5f5; border-radius: 8px;">
                  <p>二维码链接:</p>
                  <p style="word-break: break-all; font-size: 0.9rem;">${currentQrUrl}</p>
              </div>
          `;
      }
      
      // 显示弹窗
      modal.style.display = 'flex';
  }
  
  // 关闭二维码弹窗
  function closeQRModal() {
      const modal = document.getElementById('qr-modal');
      modal.style.display = 'none';
  }
  
  // 下载二维码
  function downloadQRCode() {
      const qrContainer = document.getElementById('qrcode-container');
      const canvas = qrContainer.querySelector('canvas');
      
      if (canvas) {
          const link = document.createElement('a');
          link.download = `章鱼小丸子店铺二维码_${new Date().toLocaleDateString('zh-CN')}.png`;
          link.href = canvas.toDataURL('image/png');
          link.click();
      } else {
          alert('请先生成二维码');
      }
  }
  
  // 显示闭店确认弹窗
  function showCloseShopModal() {
      const modal = document.getElementById('close-shop-modal');
      modal.style.display = 'flex';
  }
  
  // 关闭闭店确认弹窗
  function closeCloseShopModal() {
      const modal = document.getElementById('close-shop-modal');
      modal.style.display = 'none';
  }
  
  // 确认闭店
  function confirmCloseShop() {
      fetch('api/close_shop.php')
          .then(response => response.json())
          .then(data => {
              if (data.success) {
                  alert('店铺已关闭，所有二维码已失效');
                  closeCloseShopModal();
              } else {
                  alert('闭店失败: ' + data.message);
              }
          })
          .catch(error => {
              console.error('闭店失败:', error);
              alert('网络错误，请重试');
          });
  }
  
  // 加载历史订单
  function loadHistoryOrders() {
      const year = document.getElementById('filter-year').value;
      const month = document.getElementById('filter-month').value;
      const date = document.getElementById('filter-date').value;
      
      const ordersList = document.getElementById('history-orders-list');
      ordersList.innerHTML = '<div class="loading">正在查询历史订单...</div>';
      
      let url = 'api/get_orders.php?type=history';
      if (year) url += `&year=${year}`;
      if (month) url += `&month=${month}`;
      if (date) url += `&date=${date}`;
      
      fetch(url)
          .then(response => response.json())
          .then(data => {
              if (data.success) {
                  displayOrders(data.orders, 'history');
              } else {
                  ordersList.innerHTML = '<div class="error">查询失败: ' + data.message + '</div>';
              }
          })
          .catch(error => {
              ordersList.innerHTML = '<div class="error">网络错误，请稍后重试</div>';
              console.error('查询历史订单失败:', error);
          });
  }
  
  // 导出历史数据
  function exportHistoryData() {
      const year = document.getElementById('filter-year').value;
      const month = document.getElementById('filter-month').value;
      const date = document.getElementById('filter-date').value;
      
      let url = 'api/get_orders.php?type=history&export=true';
      if (year) url += `&year=${year}`;
      if (month) url += `&month=${month}`;
      if (date) url += `&date=${date}`;
      
      // 新窗口打开导出
      window.open(url, '_blank');
  }
  
  // 加载AI分析数据
  function loadAnalytics() {
      const analyticsGrid = document.getElementById('analytics-grid');
      const suggestionsDiv = document.getElementById('analytics-suggestions');
      const timeElement = document.getElementById('analytics-time');
      
      analyticsGrid.innerHTML = '<div class="loading">正在生成分析报告...</div>';
      suggestionsDiv.innerHTML = '<div class="loading">正在分析数据...</div>';
      
      fetch('api/analytics.php')
          .then(response => response.json())
          .then(data => {
              if (data.success) {
                  // 显示分析数据
                  displayAnalytics(data.analytics);
                  displaySuggestions(data.suggestions);
                  
                  // 更新时间
                  timeElement.textContent = '上次更新: ' + new Date().toLocaleTimeString('zh-CN');
              } else {
                  analyticsGrid.innerHTML = '<div class="error">分析失败: ' + data.message + '</div>';
              }
          })
          .catch(error => {
              analyticsGrid.innerHTML = '<div class="error">网络错误，请稍后重试</div>';
              console.error('加载分析数据失败:', error);
          });
  }
  
  // 显示分析数据
  function displayAnalytics(analytics) {
      const grid = document.getElementById('analytics-grid');
      
      const html = `
          <div class="analytics-card">
              <h4>最受欢迎口味</h4>
              <div class="value">${analytics.topFlavor || '--'}</div>
          </div>
          <div class="analytics-card">
              <h4>最受欢迎小料</h4>
              <div class="value">${analytics.topTopping || '--'}</div>
          </div>
          <div class="analytics-card">
              <h4>大份/小份比例</h4>
              <div class="value">${analytics.portionRatio || '--'}</div>
          </div>
          <div class="analytics-card">
              <h4>平均出餐时间</h4>
              <div class="value">${analytics.avgServeTime || '--'}</div>
          </div>
      `;
      
      grid.innerHTML = html;
  }
  
  // 显示智能建议
  function displaySuggestions(suggestions) {
      const div = document.getElementById('analytics-suggestions');
      
      let html = '<h4>💡 智能建议</h4>';
      if (suggestions && suggestions.length > 0) {
          html += '<ul>';
          suggestions.forEach(suggestion => {
              html += `<li>${suggestion}</li>`;
          });
          html += '</ul>';
      } else {
          html += '<p>暂无建议</p>';
      }
      
      div.innerHTML = html;
  }
  
  // 初始化
  initialize();
});