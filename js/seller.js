document.addEventListener("DOMContentLoaded", function () {
  // 全局变量
  let orderToDelete = null;
  let lastRefreshTime = 0;
  let refreshInterval = null;
  let refreshClickCount = 0;
  let lastRefreshClickTime = 0;
  let currentQrUrl = "";
  let currentExpiry = "";
  let currentSecretKey = "";

  // 初始化
  function initialize() {
    // 加载当日订单
    loadOrders("today");

    // Tab切换
    document.querySelectorAll(".tab").forEach((tab) => {
      tab.addEventListener("click", function () {
        const tabId = this.dataset.tab;

        // 更新活跃Tab
        document.querySelectorAll(".tab").forEach((t) => {
          t.classList.remove("active");
});
        this.classList.add("active");

        // 显示对应内容
        document.querySelectorAll(".tab-content").forEach((content) => {
          content.classList.remove("active");
        });
        document.getElementById(tabId).classList.add("active");

        // 加载对应数据
        switch (tabId) {
          case "today-orders":
            loadOrders("today");
            break;
          case "history-orders":
            // 不自动加载，等待用户筛选
            break;
          case "analytics":
            loadAnalytics();
            break;
        }
      });
    });

    // 刷新时间点击事件
    const refreshTimeElement = document.getElementById("refresh-time");
    refreshTimeElement.addEventListener("click", handleRefreshClick);

    // 查看二维码按钮
    document.getElementById("view-qr").addEventListener("click", viewQRCode);

    // 闭店按钮
    document
      .getElementById("close-shop")
      .addEventListener("click", showCloseShopModal);

    // 历史订单筛选
    document
      .getElementById("apply-history-filter")
      .addEventListener("click", loadHistoryOrders);

    // 移除导出历史订单按钮事件
    // document.getElementById("export-history").addEventListener("click", exportHistoryData);

    // 移除下载二维码按钮事件
    // document.getElementById("download-qr").addEventListener("click", downloadQRCode);

    // 刷新分析按钮
    document
      .getElementById("refresh-analytics")
      .addEventListener("click", loadAnalytics);

    // 弹窗关闭按钮
    document
      .getElementById("close-qr-modal")
      .addEventListener("click", closeQRModal);
    document.getElementById("close-qr").addEventListener("click", closeQRModal);
    document
      .getElementById("close-delete-modal")
      .addEventListener("click", closeDeleteModal);
    document
      .getElementById("close-shop-modal-btn")
      .addEventListener("click", closeCloseShopModal);

    // 二维码相关按钮
    document
      .getElementById("update-qr")
      .addEventListener("click", generateQRCode);
    document
      .getElementById("download-qr")
      .addEventListener("click", downloadQRCode);

    // 删除确认弹窗按钮
    document
      .getElementById("cancel-delete")
      .addEventListener("click", closeDeleteModal);
    document
      .getElementById("confirm-delete")
      .addEventListener("click", confirmDelete);

    // 闭店确认弹窗按钮
    document
      .getElementById("cancel-close-shop")
      .addEventListener("click", closeCloseShopModal);
    document
      .getElementById("confirm-close-shop")
      .addEventListener("click", confirmCloseShop);

    // 启动3秒自动刷新
    startAutoRefresh();

    // 添加动画样式
    addAnimationStyles();
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
    loadOrders("today");
  }

  // 退出登录
  function logout() {
    fetch("api/logout.php")
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          window.location.href = "index.php";
        }
      })
      .catch((error) => {
        console.error("退出登录失败:", error);
        window.location.href = "index.php";
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
      const refreshTimeElement = document.getElementById("refresh-time");
      if (refreshTimeElement) {
        refreshTimeElement.textContent = formatTimeAgo(secondsAgo);
      }

      // 每3秒刷新一次
      if (secondsAgo >= 3) {
        const todayOrdersTab = document.getElementById("today-orders");
        if (todayOrdersTab && todayOrdersTab.classList.contains("active")) {
          loadOrders("today", true); // true表示静默刷新
        }
      }
    }, 1000);
  }

  // 格式化时间显示
  function formatTimeAgo(seconds) {
    if (seconds < 60) {
      return seconds + "s前";
    } else if (seconds < 3600) {
      return Math.floor(seconds / 60) + "分钟前";
    } else {
      return Math.floor(seconds / 3600) + "小时前";
    }
  }

  // 加载订单
  function loadOrders(type = "today", silent = false) {
    const activeTab = document.querySelector(".tab.active").dataset.tab;

    if (type === "today" && activeTab !== "today-orders") {
      return; // 如果不是当前显示的Tab，不加载
    }

    const ordersList = document.getElementById("orders-list");
    if (!silent) {
      ordersList.innerHTML = '<div class="loading">正在加载订单...</div>';
    }

    lastRefreshTime = Math.floor(Date.now() / 1000);

    fetch("api/get_orders.php?type=" + type)
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          displayOrders(data.orders, type);
          updateStats(data.orders, data.stats);
        } else {
          if (!silent) {
            ordersList.innerHTML =
              '<div class="error">加载订单失败: ' + data.message + "</div>";
          }
        }
      })
      .catch((error) => {
        if (!silent) {
          ordersList.innerHTML =
            '<div class="error">网络错误，请稍后重试</div>';
        }
        console.error("加载订单失败:", error);
      });
  }

  // 显示订单
  function displayOrders(orders, type = "today") {
    const ordersList =
      type === "today"
        ? document.getElementById("orders-list")
        : document.getElementById("history-orders-list");
    const emptyState =
      type === "today"
        ? document.getElementById("empty-state")
        : document.getElementById("history-empty-state");

    if (orders.length === 0) {
      ordersList.style.display = "none";
      emptyState.style.display = "block";
      return;
    }

    ordersList.style.display = "block";
    emptyState.style.display = "none";

    // 按时间倒序排序
    orders.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));

    // 生成订单HTML
    const ordersHTML = orders.map((order) => createOrderItem(order)).join("");
    ordersList.innerHTML = ordersHTML;

    // 绑定操作按钮事件
    bindOrderActions();
  }

  // 创建扁平化订单项HTML
  function createOrderItem(order) {
    const orderTime = new Date(order.timestamp).toLocaleTimeString("zh-CN", {
      hour: "2-digit",
      minute: "2-digit",
    });
    const orderDate = new Date(order.timestamp).toLocaleDateString("zh-CN");
    const statusClass = order.status === "completed" ? "completed" : "pending";
    const statusText = order.status === "completed" ? "已出餐" : "待出餐";

    // 选项名称映射
    const optionNames = {
      portion: {
        small: "小份",
        large: "大份",
      },
      flavor: {
        salad: "沙拉酱",
        honey_mustard: "蜂蜜芥末",
        teriyaki: "照烧酱",
        tomato: "番茄酱",
      },
      topping: {
        bonito: "木鱼花",
        pork_floss: "肉松",
        seaweed: "海苔",
      },
    };

    // 格式化口味
    const flavorsHTML =
      order.flavors && order.flavors.length > 0
        ? order.flavors
            .map(
              (f) => `<span class="flavor-tag">${optionNames.flavor[f]}</span>`
            )
            .join("")
        : '<span class="flavor-tag" style="background:#f5f5f5;color:#999">原味</span>';

    // 格式化小料
    const toppingsHTML =
      order.toppings && order.toppings.length > 0
        ? order.toppings
            .map(
              (t) =>
                `<span class="topping-tag">${optionNames.topping[t]}</span>`
            )
            .join("")
        : '<span class="topping-tag" style="background:#f5f5f5;color:#999">无</span>';

    // 修复订单号显示问题 - 使用order.order_id而不是order.id
    const orderId = order.order_id || order.id || '0000';
    const orderPrice = order.price || (order.portion === 'small' ? 10 : 15);

    return `
          <div class="order-item ${statusClass}" data-order-id="${orderId}">
              <div class="order-header">
                  <div class="order-id-section">
                      <div class="order-id">#${orderId}</div>
                      <div class="order-time-status">
                          <div class="order-time">${orderDate} ${orderTime}</div>
                          <div class="order-status status-${
                            order.status
                          }">${statusText}</div>
                      </div>
                  </div>
              </div>
              
              <div class="order-details">
                  <div class="detail-row">
                      <div class="detail-item">
                          <span class="detail-label">分量:</span>
                          <span class="detail-value">
                              <span class="portion">${
                                optionNames.portion[order.portion]
                              }</span>
                          </span>
                      </div>
                      <div class="detail-item">
                          <span class="detail-label">口味:</span>
                          <span class="detail-value">
                              <div class="flavors">${flavorsHTML}</div>
                          </span>
                      </div>
                      <div class="detail-item">
                          <span class="detail-label">小料:</span>
                          <span class="detail-value">
                              <div class="toppings">${toppingsHTML}</div>
                          </span>
                      </div>
                  </div>
                  <div class="detail-price">总价: ${orderPrice}元</div>
              </div>
              
              ${
                order.status === "pending"
                  ? `
                  <div class="order-actions">
                      <button class="action-btn serve-btn" data-action="complete" data-id="${orderId}">出餐</button>
                      <button class="action-btn delete-btn" data-action="delete" data-id="${orderId}">删除</button>
                  </div>
              `
                  : ""
              }
          </div>
      `;
  }

  // 绑定订单操作事件
  function bindOrderActions() {
    // 出餐按钮
    document.querySelectorAll(".serve-btn").forEach((btn) => {
      btn.addEventListener("click", function () {
        const orderId = this.dataset.id;
        updateOrderStatus(orderId, "completed");
      });
    });

    // 删除订单按钮
    document.querySelectorAll(".delete-btn").forEach((btn) => {
      btn.addEventListener("click", function () {
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
      const pending =
        stats.pending || orders.filter((o) => o.status === "pending").length;
      const revenue =
        stats.revenue ||
        orders.reduce((sum, o) => sum + (parseInt(o.price) || 0), 0);

      // 更新顶部统计栏
      document.getElementById("total-revenue").textContent = revenue + "元";
      document.getElementById("pending-total-orders").textContent =
        pending + "/" + total;
    } else {
      // 客户端计算
      const total = orders.length;
      const pending = orders.filter((o) => o.status === "pending").length;
      const revenue = orders.reduce(
        (sum, o) => sum + (parseInt(o.price) || 0),
        0
      );

      document.getElementById("total-revenue").textContent = revenue + "元";
      document.getElementById("pending-total-orders").textContent =
        pending + "/" + total;
    }
  }

  // 更新订单状态
  function updateOrderStatus(orderId, status) {
    fetch("api/update_order.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        id: orderId,
        status: status,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          loadOrders("today"); // 重新加载订单
          showToast("订单状态已更新");
        } else {
          alert("更新失败: " + data.message);
        }
      })
      .catch((error) => {
        console.error("更新订单状态失败:", error);
        alert("网络错误，请稍后重试");
      });
  }

  // 查看二维码
  function viewQRCode() {
    // 显示二维码弹窗
    showQRCodeModal();

    // 尝试加载现有二维码
    loadQRCodeData();
  }

  // 加载二维码数据
  function loadQRCodeData() {
    const qrContainer = document.getElementById("qrcode-container");
    const expiryElement = document.getElementById("qr-expiry");

    qrContainer.innerHTML = '<div class="loading">正在加载二维码数据...</div>';

    fetch("api/check_qr.php")
      .then((response) => response.json())
      .then((data) => {
        if (data.success && data.qr_url) {
          currentQrUrl = data.qr_url;
          currentExpiry = data.expires_at;

          // 更新有效期显示
          expiryElement.textContent = `有效期至：${currentExpiry}`;

          // 生成二维码
          generateQRDisplay();
        } else {
          // 没有现有二维码
          showNoQRCodeMessage(qrContainer);
          expiryElement.textContent = "暂无有效二维码";
        }
      })
      .catch((error) => {
        console.error("加载二维码数据失败:", error);
        showQRCodeError(qrContainer);
        expiryElement.textContent = "数据加载失败";
      });
  }

  // 显示无二维码消息
  function showNoQRCodeMessage(container) {
    container.innerHTML = `
          <div style="text-align: center; padding: 30px;">
              <div style="font-size: 4rem; margin-bottom: 20px; opacity: 0.3;">🏪</div>
              <div style="font-size: 1.1rem; color: #666; margin-bottom: 15px;">
                  暂无有效二维码
              </div>
              <div style="color: #999; font-size: 0.95rem;">
                  点击"更新二维码"按钮生成新的二维码
              </div>
          </div>
      `;
  }

  // 显示二维码错误
  function showQRCodeError(container) {
    container.innerHTML = `
          <div style="text-align: center; padding: 30px;">
              <div style="color: #f44336; margin-bottom: 15px; font-size: 1.1rem;">
                  ⚠️ 加载失败
              </div>
              <div style="color: #666; font-size: 0.95rem;">
                  请点击"更新二维码"重新生成
              </div>
          </div>
      `;
  }

  // 生成二维码显示
  function generateQRDisplay() {
    const qrContainer = document.getElementById("qrcode-container");

    // 清空容器
    qrContainer.innerHTML = "";

    // 检查 QRCode 库是否可用
    if (typeof QRCode !== "undefined" && currentQrUrl) {
      try {
        new QRCode(qrContainer, {
          text: currentQrUrl,
          width: 220,
          height: 220,
          colorDark: "#000000",
          colorLight: "#ffffff",
          correctLevel: QRCode.CorrectLevel.H,
        });
      } catch (error) {
        console.error("生成二维码失败:", error);
        showQRCodeFallback(qrContainer, currentQrUrl);
      }
    } else {
      showQRCodeFallback(qrContainer, currentQrUrl);
    }
  }

  // 二维码生成失败时的备用显示
  function showQRCodeFallback(container, qrUrl) {
    container.innerHTML = `
          <div style="text-align: center; padding: 20px;">
              <div style="margin-bottom: 15px; color: #ff9800; font-size: 1.2rem;">
                  ⚠️
              </div>
              <div style="margin-bottom: 15px; font-size: 0.95rem; color: #666;">
                  请复制链接分享给顾客：
              </div>
              <div style="background: #f5f5f5; padding: 12px; border-radius: 6px; word-break: break-all; font-size: 0.85rem; border: 1px solid #ddd; margin-bottom: 15px;">
                  ${qrUrl}
              </div>
              <button onclick="copyQRUrl('${qrUrl}')" style="padding: 8px 20px; background: #2196F3; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 0.9rem;">
                  复制链接
              </button>
          </div>
      `;
  }

  // 生成新二维码
  function generateQRCode() {
    const qrContainer = document.getElementById("qrcode-container");
    const updateBtn = document.getElementById("update-qr");
    const expiryElement = document.getElementById("qr-expiry");

    // 保存按钮原始状态
    const originalText = updateBtn.textContent;

    // 显示加载状态
    qrContainer.innerHTML = '<div class="loading">正在生成二维码...</div>';
    expiryElement.textContent = "正在生成...";
    updateBtn.disabled = true;
    updateBtn.textContent = "生成中...";

    fetch("api/generate_qr.php")
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          currentQrUrl = data.qr_url;
          currentSecretKey = data.secret_key;
          currentExpiry = data.expires_at;

          // 更新显示
          expiryElement.textContent = `有效期至：${currentExpiry}`;

          // 生成二维码显示
          generateQRDisplay();

          // 显示成功消息
          showToast("二维码已更新成功！");
        } else {
          alert("生成失败: " + data.message);
          if (data.message.includes("登录")) {
            window.location.href = "index.php";
          }
        }

        updateBtn.disabled = false;
        updateBtn.textContent = originalText;
      })
      .catch((error) => {
        console.error("生成二维码失败:", error);
        alert("网络错误，请重试");
        updateBtn.disabled = false;
        updateBtn.textContent = originalText;
        expiryElement.textContent = "生成失败";
      });
  }

  // 显示二维码弹窗
  function showQRCodeModal() {
    const modal = document.getElementById("qr-modal");
    modal.style.display = "flex";
  }

  // 关闭二维码弹窗
  function closeQRModal() {
    const modal = document.getElementById("qr-modal");
    modal.style.display = "none";
  }

  // 下载二维码
  function downloadQRCode() {
    const qrContainer = document.getElementById("qrcode-container");
    const canvas = qrContainer.querySelector("canvas");

    if (canvas && currentQrUrl) {
      // 从Canvas下载
      const link = document.createElement("a");
      const timestamp = new Date()
        .toLocaleDateString("zh-CN")
        .replace(/\//g, "-");
      link.download = `章鱼小丸子店铺二维码_${timestamp}.png`;
      link.href = canvas.toDataURL("image/png");
      link.click();

      showToast("二维码下载成功！");
    } else {
      alert("请先生成二维码");
    }
  }

  // 复制链接到剪贴板
  function copyQRUrl(url) {
    const textarea = document.createElement("textarea");
    textarea.value = url;
    document.body.appendChild(textarea);
    textarea.select();

    try {
      const successful = document.execCommand("copy");
      if (successful) {
        showToast("链接已复制到剪贴板");
      } else {
        alert("复制失败，请手动选择并复制");
      }
    } catch (err) {
      alert("复制失败，请手动选择并复制");
    }

    document.body.removeChild(textarea);
  }

  // 显示删除确认弹窗
  function showDeleteModal(orderId) {
    orderToDelete = orderId;
    const modal = document.getElementById("delete-modal");
    document.getElementById("delete-order-id").textContent = orderId;
    modal.style.display = "flex";
  }

  // 关闭删除确认弹窗
  function closeDeleteModal() {
    const modal = document.getElementById("delete-modal");
    modal.style.display = "none";
    orderToDelete = null;
  }

  // 确认删除订单
  function confirmDelete() {
    if (!orderToDelete) return;

    fetch("api/update_order.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        id: orderToDelete,
        action: "delete",
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          closeDeleteModal();
          loadOrders("today"); // 重新加载订单
          showToast("订单已删除");
        } else {
          alert("删除失败: " + data.message);
        }
      })
      .catch((error) => {
        console.error("删除订单失败:", error);
        alert("网络错误，请稍后重试");
      });
  }

  // 显示闭店确认弹窗
  function showCloseShopModal() {
    const modal = document.getElementById("close-shop-modal");
    modal.style.display = "flex";
  }

  // 关闭闭店确认弹窗
  function closeCloseShopModal() {
    const modal = document.getElementById("close-shop-modal");
    modal.style.display = "none";
  }

  // 确认闭店
  function confirmCloseShop() {
    fetch("api/close_shop.php")
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          showToast("店铺已关闭，所有二维码已失效");
          closeCloseShopModal();
        } else {
          alert("闭店失败: " + data.message);
        }
      })
      .catch((error) => {
        console.error("闭店失败:", error);
        alert("网络错误，请重试");
      });
  }

  // 加载历史订单
  function loadHistoryOrders() {
    const year = document.getElementById("filter-year").value;
    const month = document.getElementById("filter-month").value;
    const date = document.getElementById("filter-date").value;

    const ordersList = document.getElementById("history-orders-list");
    ordersList.innerHTML = '<div class="loading">正在查询历史订单...</div>';

    let url = "api/get_orders.php?type=history";
    if (year) url += `&year=${year}`;
    if (month) url += `&month=${month}`;
    if (date) url += `&date=${date}`;

    fetch(url)
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          displayOrders(data.orders, "history");
        } else {
          ordersList.innerHTML =
            '<div class="error">查询失败: ' + data.message + "</div>";
        }
      })
      .catch((error) => {
        ordersList.innerHTML = '<div class="error">网络错误，请稍后重试</div>';
        console.error("查询历史订单失败:", error);
      });
  }

  // 导出历史数据
  function exportHistoryData() {
    const year = document.getElementById("filter-year").value;
    const month = document.getElementById("filter-month").value;
    const date = document.getElementById("filter-date").value;

    let url = "api/get_orders.php?type=history&export=true";
    if (year) url += `&year=${year}`;
    if (month) url += `&month=${month}`;
    if (date) url += `&date=${date}`;

    // 新窗口打开导出
    window.open(url, "_blank");
  }

  // 加载AI分析数据
  function loadAnalytics() {
    const analyticsGrid = document.getElementById("analytics-grid");
    const suggestionsDiv = document.getElementById("analytics-suggestions");
    const timeElement = document.getElementById("analytics-time");

    analyticsGrid.innerHTML = '<div class="loading">正在生成分析报告...</div>';
    suggestionsDiv.innerHTML = '<div class="loading">正在分析数据...</div>';

    fetch("api/analytics.php")
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // 显示分析数据
          displayAnalytics(data.analytics);
          displaySuggestions(data.suggestions);

          // 更新时间
          timeElement.textContent =
            "上次更新: " + new Date().toLocaleTimeString("zh-CN");
        } else {
          analyticsGrid.innerHTML =
            '<div class="error">分析失败: ' + data.message + "</div>";
        }
      })
      .catch((error) => {
        analyticsGrid.innerHTML =
          '<div class="error">网络错误，请稍后重试</div>';
        console.error("加载分析数据失败:", error);
      });
  }

  // 显示分析数据
  function displayAnalytics(analytics) {
    const grid = document.getElementById("analytics-grid");

    const html = `
          <div class="analytics-card">
              <h4>最受欢迎口味</h4>
              <div class="value">${analytics.topFlavor || "--"}</div>
          </div>
          <div class="analytics-card">
              <h4>最受欢迎小料</h4>
              <div class="value">${analytics.topTopping || "--"}</div>
          </div>
          <div class="analytics-card">
              <h4>大份/小份比例</h4>
              <div class="value">${analytics.portionRatio || "--"}</div>
          </div>
          <div class="analytics-card">
              <h4>平均出餐时间</h4>
              <div class="value">${analytics.avgServeTime || "--"}</div>
          </div>
      `;

    grid.innerHTML = html;
  }

  // 显示智能建议
  function displaySuggestions(suggestions) {
    const div = document.getElementById("analytics-suggestions");

    let html = "<h4>💡 智能建议</h4>";
    if (suggestions && suggestions.length > 0) {
      html += "<ul>";
      suggestions.forEach((suggestion) => {
        html += `<li>${suggestion}</li>`;
      });
      html += "</ul>";
    } else {
      html += "<p>暂无建议</p>";
    }

    div.innerHTML = html;
  }

  // 显示提示消息
  function showToast(message) {
    // 移除现有的toast
    const existingToast = document.querySelector(".toast-message");
    if (existingToast) {
      existingToast.remove();
    }

    const toast = document.createElement("div");
    toast.className = "toast-message";
    toast.style.cssText = `
          position: fixed;
          top: 20px;
          right: 20px;
          background: linear-gradient(135deg, #4CAF50, #45a049);
          color: white;
          padding: 15px 25px;
          border-radius: 8px;
          box-shadow: 0 5px 15px rgba(0,0,0,0.2);
          z-index: 2000;
          font-weight: 500;
          animation: toastSlideIn 0.3s ease;
          max-width: 300px;
      `;

    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
      toast.style.animation = "toastSlideOut 0.3s ease";
      setTimeout(() => {
        document.body.removeChild(toast);
      }, 300);
    }, 3000);
  }

  // 添加动画样式
  function addAnimationStyles() {
    const style = document.createElement("style");
    style.textContent = `
          @keyframes toastSlideIn {
              from {
                  transform: translateX(100%);
                  opacity: 0;
              }
              to {
                  transform: translateX(0);
                  opacity: 1;
              }
          }
          
          @keyframes toastSlideOut {
              from {
                  transform: translateX(0);
                  opacity: 1;
              }
              to {
                  transform: translateX(100%);
                  opacity: 0;
              }
          }
          
          @keyframes fadeIn {
              from { opacity: 0; }
              to { opacity: 1; }
          }
          
          .loading {
              animation: fadeIn 0.3s ease;
          }
      `;
    document.head.appendChild(style);
  }

  // 全局函数
  window.copyQRUrl = copyQRUrl;

  // 初始化
  initialize();
});