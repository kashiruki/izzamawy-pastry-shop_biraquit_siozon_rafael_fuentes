/**
 * Cart JavaScript - Izzamawy Pastry and Delicacies
 */

// Update cart item quantity
async function updateQuantity(productId, change) {
    const cartItem = document.querySelector(`[data-product-id="${productId}"]`);
    const qtyInput = cartItem.querySelector('.qty-input');
    const currentQty = parseInt(qtyInput.value);
    const newQty = currentQty + change;
    
    if (newQty < 1) {
        if (confirm('Remove this item from cart?')) {
            removeFromCart(productId);
        }
        return;
    }
    
    try {
        const response = await fetch('api/cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'update',
                product_id: productId,
                quantity: newQty
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            qtyInput.value = newQty;
            updateCartDisplay();
        } else {
            showNotification(result.message || 'Error updating cart', 'error');
        }
    } catch (error) {
        console.error('Update quantity error:', error);
        showNotification('Error updating cart', 'error');
    }
}

// Remove item from cart
async function removeFromCart(productId) {
    try {
        const response = await fetch('api/cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'remove',
                product_id: productId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Remove item from DOM
            const cartItem = document.querySelector(`[data-product-id="${productId}"]`);
            cartItem.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                cartItem.remove();
                updateCartDisplay();
                
                // Check if cart is empty
                if (document.querySelectorAll('.cart-item').length === 0) {
                    location.reload();
                }
            }, 300);
            
            showNotification('Item removed from cart', 'success');
        } else {
            showNotification(result.message || 'Error removing item', 'error');
        }
    } catch (error) {
        console.error('Remove from cart error:', error);
        showNotification('Error removing item', 'error');
    }
}

// Update cart display (totals)
async function updateCartDisplay() {
    try {
        const response = await fetch('api/cart.php?action=get');
        const cart = await response.json();
        
        // Update item totals
        document.querySelectorAll('.cart-item').forEach(item => {
            const productId = item.dataset.productId;
            const cartItem = cart.items.find(i => i.id == productId);
            
            if (cartItem) {
                const totalElement = item.querySelector('.cart-item-total');
                totalElement.textContent = formatPrice(cartItem.price * cartItem.quantity);
            }
        });
        
        // Update summary
        const subtotal = cart.total;
        let shipping = 0;
        
        if (subtotal < 1000) {
            shipping = 100; // Default Metro Manila shipping
        }
        
        const total = subtotal + shipping;
        
        document.getElementById('subtotal').textContent = formatPrice(subtotal);
        document.getElementById('shipping').textContent = formatPrice(shipping);
        document.getElementById('total').textContent = formatPrice(total);
        
        // Update cart count in header
        document.querySelectorAll('.cart-count').forEach(element => {
            element.textContent = cart.count || 0;
        });
        
        // Update shipping notice
        const shippingNotice = document.querySelector('.shipping-notice');
        const freeShippingNotice = document.querySelector('.free-shipping-notice');
        
        if (subtotal >= 1000) {
            if (shippingNotice) shippingNotice.style.display = 'none';
            if (freeShippingNotice) freeShippingNotice.style.display = 'flex';
        } else {
            if (shippingNotice) {
                shippingNotice.style.display = 'flex';
                const remaining = 1000 - subtotal;
                shippingNotice.innerHTML = `
                    <i class="fas fa-info-circle"></i>
                    Add ${formatPrice(remaining)} more for free shipping!
                `;
            }
            if (freeShippingNotice) freeShippingNotice.style.display = 'none';
        }
        
    } catch (error) {
        console.error('Update cart display error:', error);
    }
}

// Format price
function formatPrice(price) {
    return '₱' + parseFloat(price).toFixed(2);
}

// Show notification
function showNotification(message, type = 'info') {
    const existing = document.querySelector('.notification');
    if (existing) {
        existing.remove();
    }
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: ${type === 'success' ? '#4caf50' : '#f44336'};
        color: white;
        padding: 15px 20px;
        border-radius: 5px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        z-index: 9999;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
