// 全局变量
let selectedPortion = null;
let selectedFlavors = [];
let selectedToppings = [];
let currentOrder = null;

// DOM加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    initEventListeners();
    updateOrderSummary();
});

function initEventListeners() {
    // 分量选择
    document.querySelectorAll('.portion-option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('.portion-option').forEach(o => {
                o.classList.remove('selected');
            });
            this.classList.add('selected');
            selectedPortion = this.dataset.portion;
            updateOrderSummary();
        });
    });

    // 口味选择
    document.querySelectorAll('input[name="flavor"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                selectedFlavors.push(this.value);
            } else {
                selectedFlavors = selectedFlavors.filter(f => f !== this.value);
            }
            updateOrderSummary();
        });
    });

    // 小料选择
    document.querySelectorAll('input[name="topping"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                selectedToppings.push(this.value);
            } else {
                selectedToppings = selectedToppings.filter(t => t !== this.value);
            }
            updateOrderSummary();
        });
    });
}

function updateOrderSummary() {
    const config = {
        prices: { small: 10, large: 15 }
    };
    
    // 更新分量显示
    const portionDisplay = document.getElementById('selected-portion');
    if (selectedPortion) {
        portionDisplay.textContent = selectedPortion === 'small' ? '小份(6个)' : '大份(10个)';
    } else {
        portionDisplay.textContent = '未选择';
    }

    // 更新口味显示
    const flavorsDisplay = document.getElementById('selected-flavors');
    if (selectedFlavors.length > 0) {
        flavorsDisplay.textContent = selectedFlavors.join(', ');
    } else {
        flavorsDisplay.textContent = '未选择';
    }

    // 更新小料显示
    const toppingsDisplay = document.getElementById('selected-toppings');
    if (selectedToppings.length > 0) {
        toppingsDisplay.textContent = selectedToppings.join(', ');
    } else {
        toppingsDisplay.textContent = '未选择';
    }

    // 计算总价
    const totalPrice = selectedPortion ? config.prices[selectedPortion] : 0;
    document.getElementById('total-price').textContent = `¥${totalPrice}`;
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
    document.getElementById('order-number').textContent = order.order_id;
    
    const detailsDiv = document.querySelector('.order-details');
    const config = {
        prices: { small: 10, large: 15 }
    };
    
    detailsDiv.innerHTML = `
        <p><strong>分量:</strong> ${order.portion === 'small' ? '小份(6个)' : '大份(10个)'}</p>
        <p><strong>口味:</strong> ${order.flavors.length > 0 ? order.flavors.join(', ') : '无'}</p>
        <p><strong>小料:</strong> ${order.toppings.length > 0 ? order.toppings.join(', ') : '无'}</p>
        <p><strong>总价:</strong> ¥${config.prices[order.portion]}</p>
        <p><strong>下单时间:</strong> ${new Date(order.timestamp).toLocaleString()}</p>
        <p><strong>状态:</strong> 待制作</p>
    `;
    
    document.getElementById('order-modal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('order-modal').style.display = 'none';
    // 重置选择
    resetSelection();
}

function resetSelection() {
    selectedPortion = null;
    selectedFlavors = [];
    selectedToppings = [];
    
    document.querySelectorAll('.portion-option').forEach(o => {
        o.classList.remove('selected');
    });
    
    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = false;
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

function showOrderQuery() {
    document.getElementById('query-modal').style.display = 'flex';
}

function closeQueryModal() {
    document.getElementById('query-modal').style.display = 'none';
    document.getElementById('query-order-number').value = '';
    document.getElementById('query-result').innerHTML = '';
}

async function queryOrder() {
    const orderNumber = document.getElementById('query-order-number').value.trim();
    
    if (!orderNumber || orderNumber.length !== 4) {
        alert('请输入有效的4位订单号');
        return;
    }

    try {
        const response = await fetch(`api/get_order_by_id.php?order_id=${orderNumber}`);
        const result = await response.json();
        
        const resultDiv = document.getElementById('query-result');
        
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
            
            resultDiv.innerHTML = `
                <div class="order-details">
                    <p><strong>订单号:</strong> ${order.order_id}</p>
                    <p><strong>分量:</strong> ${order.portion === 'small' ? '小份(6个)' : '大份(10个)'}</p>
                    <p><strong>口味:</strong> ${order.flavors.length > 0 ? order.flavors.join(', ') : '无'}</p>
                    <p><strong>小料:</strong> ${order.toppings.length > 0 ? order.toppings.join(', ') : '无'}</p>
                    <p><strong>总价:</strong> ¥${config.prices[order.portion]}</p>
                    <p><strong>下单时间:</strong> ${new Date(order.timestamp).toLocaleString()}</p>
                    <p><strong>状态:</strong> ${statusText}</p>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `<p class="error">${result.message}</p>`;
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('query-result').innerHTML = '<p class="error">查询失败，请稍后重试</p>';
    }
}