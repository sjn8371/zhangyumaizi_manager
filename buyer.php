document.addEventListener('DOMContentLoaded', function() {
    // 当前选择的选项
    let selectedPortion = null;
    let selectedFlavors = [];
    let selectedToppings = [];
    
    // 价格映射
    const prices = {
        small: 10,
        large: 15
    };
    
    // 选项名称映射
    const optionNames = {
        portion: {
            small: "小份 (10元/6个)",
            large: "大份 (15元/10个)"
        },
        flavor: {
            salad: "沙拉酱",
            honey_mustard: "蜂蜜芥末",
            teriyaki: "照烧酱",
            tomato: "番茄酱"
        },
        topping: {
            bonito: "木鱼花",
            pork_floss: "肉松",
            seaweed: "海苔"
        }
    };
    
    // 初始化选项选择
    function initializeOptions() {
        // 分量选择
        document.querySelectorAll('.option').forEach(option => {
            option.addEventListener('click', function() {
                const parentSection = this.closest('.section');
                const value = this.dataset.value;
                
                // 判断是哪个部分的选项
                if (parentSection.querySelector('h2').textContent.includes('分量')) {
                    // 分量单选
                    parentSection.querySelectorAll('.option').forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    this.classList.add('selected');
                    selectedPortion = value;
                } 
                else if (parentSection.querySelector('h2').textContent.includes('口味')) {
                    // 口味多选
                    const index = selectedFlavors.indexOf(value);
                    if (index === -1) {
                        this.classList.add('selected');
                        selectedFlavors.push(value);
                    } else {
                        this.classList.remove('selected');
                        selectedFlavors.splice(index, 1);
                    }
                }
                else if (parentSection.querySelector('h2').textContent.includes('小料')) {
                    // 小料多选
                    const index = selectedToppings.indexOf(value);
                    if (index === -1) {
                        this.classList.add('selected');
                        selectedToppings.push(value);
                    } else {
                        this.classList.remove('selected');
                        selectedToppings.splice(index, 1);
                    }
                }
                
                updateSummary();
            });
        });
        
        // 下单按钮
        document.getElementById('place-order').addEventListener('click', placeOrder);
        
        // 查询订单按钮
        document.getElementById('check-order').addEventListener('click', checkOrderStatus);
        
        // 监听订单号输入框的Enter键
        document.getElementById('order-number-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                checkOrderStatus();
            }
        });
        
        // 弹窗关闭按钮
        document.querySelector('.close-modal').addEventListener('click', closeModal);
        document.getElementById('close-order-modal').addEventListener('click', closeModal);
        
        // 保存图片按钮
        document.getElementById('save-image').addEventListener('click', saveOrderImage);
    }
    
    // 更新订单摘要
    function updateSummary() {
        // 更新分量
        const portionElement = document.getElementById('summary-portion');
        if (selectedPortion) {
            portionElement.textContent = optionNames.portion[selectedPortion];
        } else {
            portionElement.textContent = "未选择";
        }
        
        // 更新口味
        const flavorsElement = document.getElementById('summary-flavors');
        if (selectedFlavors.length > 0) {
            flavorsElement.textContent = selectedFlavors.map(f => optionNames.flavor[f]).join(', ');
        } else {
            flavorsElement.textContent = "原味";
        }
        
        // 更新小料
        const toppingsElement = document.getElementById('summary-toppings');
        if (selectedToppings.length > 0) {
            toppingsElement.textContent = selectedToppings.map(t => optionNames.topping[t]).join(', ');
        } else {
            toppingsElement.textContent = "无";
        }
        
        // 更新总价
        const priceElement = document.getElementById('summary-price');
        if (selectedPortion) {
            priceElement.textContent = prices[selectedPortion] + "元";
        } else {
            priceElement.textContent = "0元";
        }
    }
    
    // 提交订单
    function placeOrder() {
        // 验证是否选择了分量
        if (!selectedPortion) {
            alert('请选择分量');
            return;
        }
        
        // 生成随机订单号
        const orderId = Math.floor(1000 + Math.random() * 9000);
        
        // 准备订单数据
        const orderData = {
            id: orderId,
            portion: selectedPortion,
            flavors: selectedFlavors,
            toppings: selectedToppings,
            price: prices[selectedPortion],
            status: 'pending',
            timestamp: new Date().toISOString()
        };
        
        // 显示订单确认弹窗
        showOrderConfirmation(orderData);
        
        // 发送订单到服务器
        saveOrder(orderData);
    }
    
    // 显示订单确认弹窗
    function showOrderConfirmation(orderData) {
        const modal = document.getElementById('order-modal');
        const orderIdElement = document.getElementById('order-id');
        const modalDetailsElement = document.getElementById('modal-details');
        
        // 设置订单号
        orderIdElement.textContent = orderData.id;
        
        // 设置订单详情
        const detailsHTML = `
            <div class="detail-item">
                <span>分量:</span>
                <span>${optionNames.portion[orderData.portion]}</span>
            </div>
            <div class="detail-item">
                <span>口味:</span>
                <span>${orderData.flavors.length > 0 ? orderData.flavors.map(f => optionNames.flavor[f]).join(', ') : '原味'}</span>
            </div>
            <div class="detail-item">
                <span>小料:</span>
                <span>${orderData.toppings.length > 0 ? orderData.toppings.map(t => optionNames.topping[t]).join(', ') : '无'}</span>
            </div>
            <div class="detail-item total">
                <span>总价:</span>
                <span>${orderData.price}元</span>
            </div>
        `;
        
        modalDetailsElement.innerHTML = detailsHTML;
        
        // 显示弹窗
        modal.style.display = 'flex';
    }
    
    // 保存订单截图
    function saveOrderImage() {
        const modalContent = document.getElementById('modal-content-screenshot');
        
        // 使用html2canvas截图
        if (typeof html2canvas !== 'undefined') {
            html2canvas(modalContent, {
                scale: 2,
                useCORS: true,
                backgroundColor: '#ffffff'
            }).then(canvas => {
                // 转换为图片URL
                const imgData = canvas.toDataURL('image/png');
                
                // 创建下载链接
                const link = document.createElement('a');
                const orderId = document.getElementById('order-id').textContent;
                link.download = `章鱼小丸子订单_${orderId}.png`;
                link.href = imgData;
                
                // 触发下载
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                // 提示用户
                alert('订单截图已保存到您的设备！');
            }).catch(error => {
                console.error('截图失败:', error);
                alert('截图保存失败，请重试');
            });
        } else {
            alert('截图功能暂时不可用');
        }
    }
    
    // 查询订单状态
    function checkOrderStatus() {
        const orderNumber = document.getElementById('order-number-input').value.trim();
        const statusDisplay = document.getElementById('order-status-display');
        const statusContent = document.getElementById('order-status-content');
        
        if (!orderNumber || orderNumber.length !== 4) {
            alert('请输入4位订单号');
            return;
        }
        
        // 显示加载状态
        statusContent.innerHTML = '<div class="loading">正在查询...</div>';
        statusDisplay.style.display = 'block';
        
        // 查询订单
        fetch(`api/get_order_by_id.php?id=${orderNumber}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.order) {
                    const order = data.order;
                    const statusText = order.status === 'completed' ? '已完成' : '制作中';
                    const statusClass = order.status === 'completed' ? 'status-completed' : 'status-pending';
                    
                    const orderTime = new Date(order.timestamp).toLocaleString('zh-CN');
                    
                    statusContent.innerHTML = `
                        <div class="${statusClass}">状态: ${statusText}</div>
                        <div style="margin-top: 10px;">
                            <div><strong>订单号:</strong> ${order.id}</div>
                            <div><strong>下单时间:</strong> ${orderTime}</div>
                            <div><strong>分量:</strong> ${optionNames.portion[order.portion]}</div>
                            <div><strong>口味:</strong> ${order.flavors.length > 0 ? order.flavors.map(f => optionNames.flavor[f]).join(', ') : '原味'}</div>
                            <div><strong>小料:</strong> ${order.toppings.length > 0 ? order.toppings.map(t => optionNames.topping[t]).join(', ') : '无'}</div>
                            <div><strong>总价:</strong> ${order.price}元</div>
                        </div>
                    `;
                } else {
                    statusContent.innerHTML = '<div class="error">未找到该订单号，请确认后重试</div>';
                }
            })
            .catch(error => {
                console.error('查询失败:', error);
                statusContent.innerHTML = '<div class="error">查询失败，请检查网络连接</div>';
            });
    }
    
    // 关闭弹窗
    function closeModal() {
        const modal = document.getElementById('order-modal');
        modal.style.display = 'none';
        
        // 重置选择
        resetSelections();
    }
    
    // 重置选择
    function resetSelections() {
        selectedPortion = null;
        selectedFlavors = [];
        selectedToppings = [];
        
        // 重置UI
        document.querySelectorAll('.option').forEach(option => {
            option.classList.remove('selected');
        });
        
        // 重置摘要
        updateSummary();
    }
    
    // 保存订单到服务器
    function saveOrder(orderData) {
        fetch('api/place_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(orderData)
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('订单保存失败:', data.message);
            }
        })
        .catch(error => {
            console.error('网络错误:', error);
        });
    }
    
    // 初始化
    initializeOptions();
});