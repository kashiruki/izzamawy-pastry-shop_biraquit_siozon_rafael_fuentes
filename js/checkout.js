/**
 * Checkout JavaScript - Izzamawy Pastry and Delicacies
 */

document.addEventListener('DOMContentLoaded', function() {
    const checkoutForm = document.getElementById('checkoutForm');
    const provinceSelect = document.getElementById('province');
    
    // Update shipping cost when province changes
    if (provinceSelect) {
        provinceSelect.addEventListener('change', calculateShipping);
    }
    
    // Handle form submission
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', handleCheckout);
    }
    
    // Initial shipping calculation
    calculateShipping();

    // Enforce numeric-only inputs for phone and postal code
    const phoneInput = document.getElementById('customer_phone');
    const postalInput = document.getElementById('postal_code');

    function restrictToDigits(e) {
        // remove any non-digit characters
        const cleaned = this.value.replace(/\D+/g, '');
        if (this.value !== cleaned) this.value = cleaned;
    }

    if (phoneInput) {
        phoneInput.addEventListener('input', restrictToDigits);
        phoneInput.addEventListener('paste', function (e) {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text') || '';
            this.value = text.replace(/\D+/g, '').slice(0, this.maxLength || 11);
        });
    }

    if (postalInput) {
        postalInput.addEventListener('input', restrictToDigits);
        postalInput.addEventListener('paste', function (e) {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text') || '';
            this.value = text.replace(/\D+/g, '').slice(0, this.maxLength || 4);
        });
    }
});

// Calculate shipping based on province
async function calculateShipping() {
    try {
        const response = await fetch('api/cart.php?action=get');
        const cart = await response.json();
        
        const provinceSelect = document.getElementById('province');
        const subtotal = cart.total;

        let shipping = 0;

        // Free shipping threshold
        if (subtotal >= 1000) {
            shipping = 0;
        } else {
            // Use the selected option's data-shipping value (per-municipality price)
            if (provinceSelect) {
                const selected = provinceSelect.selectedOptions && provinceSelect.selectedOptions[0];
                if (selected && selected.dataset && selected.dataset.shipping) {
                    const parsed = parseInt(selected.dataset.shipping, 10);
                    shipping = Number.isFinite(parsed) ? parsed : 0;
                } else {
                    // fallback: default shipping if municipality not selected
                    shipping = 0;
                }
            }
        }

        const total = subtotal + shipping;
        
        // Update display
        document.getElementById('summarySubtotal').textContent = formatPrice(subtotal);

        const selectedValue = provinceSelect && provinceSelect.value ? provinceSelect.value : '';
        if (selectedValue !== '') {
            if (shipping === 0) {
                document.getElementById('summaryShipping').innerHTML = '<span style="color: #4caf50;">FREE</span>';
            } else {
                document.getElementById('summaryShipping').textContent = formatPrice(shipping);
            }
            document.getElementById('summaryTotal').textContent = formatPrice(total);
            // Show estimated delivery date if available
            const selectedOpt = provinceSelect.selectedOptions && provinceSelect.selectedOptions[0];
            let days = null;
            if (selectedOpt && selectedOpt.dataset && selectedOpt.dataset.days) {
                days = parseInt(selectedOpt.dataset.days, 10);
            }
            if (days && Number.isFinite(days)) {
                // compute arrival date (today + days)
                const now = new Date();
                const arrival = new Date(now.getFullYear(), now.getMonth(), now.getDate() + days);
                const opts = { year: 'numeric', month: 'short', day: 'numeric' };
                document.getElementById('summaryDelivery').textContent = `Est. ${days} day(s) — Arrives by ${arrival.toLocaleDateString(undefined, opts)}`;
            } else {
                document.getElementById('summaryDelivery').textContent = 'Estimated on selection';
            }
        } else {
            document.getElementById('summaryShipping').textContent = 'Select province';
            document.getElementById('summaryTotal').textContent = formatPrice(subtotal);
            document.getElementById('summaryDelivery').textContent = 'Select municipality';
        }
        
    } catch (error) {
        console.error('Calculate shipping error:', error);
    }
}

// Handle checkout form submission
async function handleCheckout(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const messageDiv = document.getElementById('orderMessage');
    
    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    // Get form data
        const formData = {
        action: 'create',
        customer_name: document.getElementById('customer_name').value,
        customer_email: document.getElementById('customer_email').value,
        customer_phone: document.getElementById('customer_phone').value,
        shipping_address: document.getElementById('shipping_address').value,
        province: document.getElementById('province').value,
        postal_code: document.getElementById('postal_code').value,
        payment_method: document.querySelector('input[name="payment_method"]:checked').value,
        notes: document.getElementById('notes').value
    };
    
    try {
        const response = await fetch('api/orders.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Show success message
            messageDiv.className = 'order-message success';
            messageDiv.innerHTML = `
                <i class="fas fa-check-circle"></i>
                Order placed successfully! Order Number: <strong>${result.order_number}</strong>
            `;
            
            // Redirect to order confirmation immediately
            window.location.href = `order-confirmation.php?order=${result.order_number}`;
            
        } else {
            // Show error message
            messageDiv.className = 'order-message error';
            messageDiv.innerHTML = `
                <i class="fas fa-exclamation-circle"></i>
                ${result.message || 'Error placing order. Please try again.'}
            `;
            
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-lock"></i> Place Order';
        }
        
    } catch (error) {
        console.error('Checkout error:', error);
        
        messageDiv.className = 'order-message error';
        messageDiv.innerHTML = `
            <i class="fas fa-exclamation-circle"></i>
            An error occurred. Please try again.
        `;
        
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-lock"></i> Place Order';
    }
}

// Format price
function formatPrice(price) {
    return '₱' + parseFloat(price).toFixed(2);
}

// Form validation
function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = '#f44336';
            isValid = false;
        } else {
            field.style.borderColor = '#e5e5e5';
        }
    });
    
    // Validate email
    const emailField = document.getElementById('customer_email');
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (emailField && !emailRegex.test(emailField.value)) {
        emailField.style.borderColor = '#f44336';
        isValid = false;
    }
    
    // Validate phone: digits only, 10-11 characters
    const phoneField = document.getElementById('customer_phone');
    const phoneRegex = /^\d{10,11}$/;
    if (phoneField) {
        const phoneVal = phoneField.value.replace(/\D+/g, '');
        if (!phoneRegex.test(phoneVal)) {
            phoneField.style.borderColor = '#f44336';
            isValid = false;
        }
    }

    // Validate postal code if provided: exactly 4 digits
    const postalField = document.getElementById('postal_code');
    if (postalField && postalField.value.trim()) {
        const postalVal = postalField.value.replace(/\D+/g, '');
        const postalRegex = /^\d{4}$/;
        if (!postalRegex.test(postalVal)) {
            postalField.style.borderColor = '#f44336';
            isValid = false;
        }
    }
    
    return isValid;
}

// Real-time validation
document.querySelectorAll('input[required], select[required], textarea[required]').forEach(field => {
    field.addEventListener('blur', function() {
        if (this.value.trim()) {
            this.style.borderColor = '#4caf50';
        } else {
            this.style.borderColor = '#f44336';
        }
    });
    
    field.addEventListener('input', function() {
        if (this.value.trim()) {
            this.style.borderColor = '#e5e5e5';
        }
    });
});
