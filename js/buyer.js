// 全局变量
let selectedPortion = null;
let selectedFlavors = [];
let selectedToppings = [];
let currentOrder = null;

// 中文映射配置
const chineseMapping = {
    // 分量
    small: '小份',
    large: '大份',
    
    // 口味
    salad: '沙拉酱',
    honey_mustard: '蜂蜜芥末',
    teriyaki: '照烧酱',
    tomato: '番茄酱',
    
    // 小料
    bonito: '木鱼花',
    pork_floss: '肉松',
    seaweed: '海苔'
};

// DOM加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    initEventListeners();
    updateOrderSummary();
});

function initEventListeners() {
    // 分量选择 - 修复选择器
    document.querySelectorAll('.section:nth-child(1) .option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('.section:nth-child(1) .option').forEach(o => {
                o.classList.remove('selected');
            });
            this.classList.add('selected');
            selectedPortion = this.dataset.value;
            updateOrderSummary();
        });
    });

    // 口味选择 - 修复选择器
    document.querySelectorAll('.section:nth-child(2) .option').forEach(option => {
        option.addEventListener('click', function() {
            if (this.classList.contains('selected')) {
                this.classList.remove('selected');
                selectedFlavors = selectedFlavors.filter(f => f !== this.dataset.value);
            } else {
                this.classList.add('selected');
                selectedFlavors.push(this.dataset.value);
            }
            updateOrderSummary();
        });
    });

    // 小料选择 - 修复选择器
    document.querySelectorAll('.section:nth-child(3) .option').forEach(option => {
        option.addEventListener('click', function() {
            if (this.classList.contains('selected')) {
                this.classList.remove('selected');
                selectedToppings = selectedToppings.filter(t => t !== this.dataset.value);
            } else {
                this.classList.add('selected');
                selectedToppings.push(this.dataset.value);
            }
            updateOrderSummary();
        });
    });

    // 下单按钮事件绑定
    document.getElementById('place-order').addEventListener('click', placeOrder);

    // 查询订单按钮事件绑定
    document.getElementById('check-order').addEventListener('click', checkOrderStatus);

    // 关闭模态框事件绑定
    document.querySelector('.close-modal').addEventListener('click', closeModal);
    document.getElementById('close-order-modal').addEventListener('click', closeModal);

    // 保存截图按钮事件绑定
    document.getElementById('save-image').addEventListener('click', saveOrderScreenshot);
}

// 将英文值转换为中文显示
function translateToChinese(value) {
    return chineseMapping[value] || value;
}

function updateOrderSummary() {
    const config = {
        prices: { small: 10, large: 15 }
    };
    
    // 更新分量显示 - 修复ID
    const portionDisplay = document.getElementById('summary-portion');
    if (selectedPortion) {
        portionDisplay.textContent = selectedPortion === 'small' ? '小份(6个)' : '大份(10个)';
    } else {
        portionDisplay.textContent = '未选择';
    }

    // 更新口味显示 - 修复ID，使用中文显示
    const flavorsDisplay = document.getElementById('summary-flavors');
    if (selectedFlavors.length > 0) {
        const chineseFlavors = selectedFlavors.map(f => translateToChinese(f));
        flavorsDisplay.textContent = chineseFlavors.join(', ');
    } else {
        flavorsDisplay.textContent = '未选择';
    }

    // 更新小料显示 - 修复ID，使用中文显示
    const toppingsDisplay = document.getElementById('summary-toppings');
    if (selectedToppings.length > 0) {
        const chineseToppings = selectedToppings.map(t => translateToChinese(t));
        toppingsDisplay.textContent = chineseToppings.join(', ');
    } else {
        toppingsDisplay.textContent = '未选择';
    }

    // 计算总价 - 修复ID
    const totalPrice = selectedPortion ? config.prices[selectedPortion] : 0;
    document.getElementById('summary-price').textContent = totalPrice + '元';
}

async function placeOrder() {
    if (!selectedPortion) {
        alert('请选择分量');
        return;
    }

    const orderData = {
        portion: selectedPortion,
        flavors: selectedFlavors,
        toppings: selectedToppings,
        timestamp: new Date().toISOString(),
        status: 'pending'
    };

    try {
        const response = await fetch('api/place_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(orderData)
        });

        const result = await response.json();
        
        if (result.success) {
            currentOrder = result.order;
            showOrderModal(result.order);
        } else {
            alert('下单失败: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('下单失败，请稍后重试');
    }
}

function showOrderModal(order) {
    document.getElementById('order-id').textContent = order.order_id || '0000';
    
    const detailsDiv = document.getElementById('modal-details');
    const config = {
        prices: { small: 10, large: 15 }
    };
    
    // 将口味和小料转换为中文显示
    const chineseFlavors = order.flavors.map(f => translateToChinese(f));
    const chineseToppings = order.toppings.map(t => translateToChinese(t));
    
    detailsDiv.innerHTML = `
        <div class="detail-item">
            <span>分量:</span>
            <span>${order.portion === 'small' ? '小份(6个)' : '大份(10个)'}</span>
        </div>
        <div class="detail-item">
            <span>口味:</span>
            <span>${chineseFlavors.length > 0 ? chineseFlavors.join(', ') : '无'}</span>
        </div>
        <div class="detail-item">
            <span>小料:</span>
            <span>${chineseToppings.length > 0 ? chineseToppings.join(', ') : '无'}</span>
        </div>
        <div class="detail-item">
            <span>总价:</span>
            <span>${config.prices[order.portion]}元</span>
        </div>
        <div class="detail-item">
            <span>下单时间:</span>
            <span>${new Date(order.timestamp).toLocaleString()}</span>
        </div>
        <div class="detail-item total">
            <span>状态:</span>
            <span>待制作</span>
        </div>
    `;
    
    document.getElementById('order-modal').style.display = 'flex';
}

function closeModal() {
    const modal = document.getElementById('order-modal');
    if (modal) {
        modal.style.display = 'none';
    }
    // 重置选择
    resetSelection();
}

// 在DOM加载完成后添加事件监听器
document.addEventListener('DOMContentLoaded', function() {
    // 绑定关闭按钮事件
    const closeBtn = document.querySelector('.close-modal');
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
    
    // 绑定确认按钮事件
    const confirmBtn = document.getElementById('confirm-order');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', closeModal);
    }
    
    // 绑定保存截图按钮事件
    const saveBtn = document.getElementById('save-screenshot');
    if (saveBtn) {
        saveBtn.addEventListener('click', saveOrderScreenshot);
    }
    
    // 绑定查询订单按钮事件
    const queryBtn = document.getElementById('query-order');
    if (queryBtn) {
        queryBtn.addEventListener('click', checkOrderStatus);
    }
});

function resetSelection() {
    selectedPortion = null;
    selectedFlavors = [];
    selectedToppings = [];
    
    document.querySelectorAll('.option').forEach(o => {
        o.classList.remove('selected');
    });
    
    updateOrderSummary();
}

async function saveOrderScreenshot() {
    const modalContent = document.querySelector('.modal-content');
    
    html2canvas(modalContent).then(canvas => {
        const link = document.createElement('a');
        link.download = `订单-${currentOrder.order_id}.png`;
        link.href = canvas.toDataURL();
        link.click();
    });
}

async function checkOrderStatus() {
    const orderNumber = document.getElementById('order-number-input').value.trim();
    
    if (!orderNumber || orderNumber.length !== 4) {
        alert('请输入有效的4位订单号');
        return;
    }

    try {
        const response = await fetch(`api/get_order_by_id.php?order_id=${orderNumber}`);
        const result = await response.json();
        
        const statusDisplay = document.getElementById('order-status-display');
        const statusContent = document.getElementById('order-status-content');
        
        if (result.success) {
            const order = result.order;
            const config = {
                prices: { small: 10, large: 15 }
            };
            
            let statusText = '';
            switch (order.status) {
                case 'pending':
                    statusText = '待制作';
                    break;
                case 'completed':
                    statusText = '已完成';
                    break;
                case 'cancelled':
                    statusText = '已取消';
                    break;
                default:
                    statusText = '未知';
            }
            
            // 将口味和小料转换为中文显示
            const chineseFlavors = order.flavors.map(f => translateToChinese(f));
            const chineseToppings = order.toppings.map(t => translateToChinese(t));
            
            statusContent.innerHTML = `
                <p><strong>订单号:</strong> ${order.order_id}</p>
                <p><strong>分量:</strong> ${order.portion === 'small' ? '小份(6个)' : '大份(10个)'}</p>
                <p><strong>口味:</strong> ${chineseFlavors.length > 0 ? chineseFlavors.join(', ') : '无'}</p>
                <p><strong>小料:</strong> ${chineseToppings.length > 0 ? chineseToppings.join(', ') : '无'}</p>
                <p><strong>总价:</strong> ${config.prices[order.portion]}元</p>
                <p><strong>下单时间:</strong> ${new Date(order.timestamp).toLocaleString()}</p>
                <p><strong>状态:</strong> <span class="status-${order.status}">${statusText}</span></p>
            `;
            statusDisplay.style.display = 'block';
        } else {
            statusContent.innerHTML = `<p class="error">${result.message}</p>`;
            statusDisplay.style.display = 'block';
        }
    } catch (error) {
        console.error('Error:', error);
        const statusContent = document.getElementById('order-status-content');
        statusContent.innerHTML = '<p class="error">查询失败，请稍后重试</p>';
        document.getElementById('order-status-display').style.display = 'block';
    }
}