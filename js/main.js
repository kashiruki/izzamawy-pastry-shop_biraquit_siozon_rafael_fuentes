/**
 * Main JavaScript - Izzamawy Pastry and Delicacies
 */

// Mobile Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
        });
    }
    
    // Search Modal
    const searchBtn = document.getElementById('searchBtn');
    const searchModal = document.getElementById('searchModal');
    const closeSearch = document.querySelector('.close-search');
    const searchInput = document.getElementById('searchInput');
    
    if (searchBtn) {
        searchBtn.addEventListener('click', function() {
            searchModal.classList.add('active');
            searchInput.focus();
        });
    }
    
    if (closeSearch) {
        closeSearch.addEventListener('click', function() {
            searchModal.classList.remove('active');
        });
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === searchModal) {
            searchModal.classList.remove('active');
        }
    });

    // Login Modal
    const loginBtn = document.getElementById('loginBtn');
    const loginModal = document.getElementById('loginModal');
    const closeLogin = document.querySelector('.close-login');
    
    if (loginBtn) {
        loginBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (loginModal) {
                loginModal.classList.add('active');
                loginModal.setAttribute('aria-hidden', 'false');
                const u = document.getElementById('modalUsername');
                if (u) u.focus();
            }
        });
    }
    if (closeLogin) {
        closeLogin.addEventListener('click', function() {
            if (loginModal) {
                loginModal.classList.remove('active');
                loginModal.setAttribute('aria-hidden', 'true');
            }
        });
    }
    window.addEventListener('click', function(e) {
        if (e.target === loginModal) {
            if (loginModal) {
                loginModal.classList.remove('active');
                loginModal.setAttribute('aria-hidden', 'true');
            }
        }
    });

    // Close with ESC and trap focus inside modal
    document.addEventListener('keydown', function(e) {
        if (!loginModal || !loginModal.classList.contains('active')) return;
        if (e.key === 'Escape') {
            loginModal.classList.remove('active');
            loginModal.setAttribute('aria-hidden', 'true');
        }
        if (e.key === 'Tab') {
            // Basic focus trap
            const focusable = loginModal.querySelectorAll('a, button, input, textarea, select');
            if (focusable.length === 0) return;
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        }
    });

    // Handle auth form submissions via AJAX with inline errors
    const authForms = document.querySelectorAll('.auth-form');
    authForms.forEach(f => {
        f.addEventListener('submit', async function(e) {
            e.preventDefault();

            const btn = f.querySelector('.submit-btn');
            const text = btn ? btn.querySelector('.btn-text') : null;
            const spinner = btn ? btn.querySelector('.btn-spinner') : null;
            const errorsBox = f.querySelector('.auth-errors');
            if (errorsBox) { errorsBox.style.display = 'none'; errorsBox.textContent = ''; }

            // Basic client-side validation
            const username = f.querySelector('input[name="username"]')?.value.trim() || '';
            const password = f.querySelector('input[name="password"]')?.value || '';
            if (!username || !password) {
                if (errorsBox) { errorsBox.textContent = 'Please enter both username (or email) and password.'; errorsBox.style.display = 'block'; }
                return;
            }

            if (btn) { if (text) text.style.display = 'none'; if (spinner) spinner.style.display = 'inline-block'; btn.disabled = true; }

            try {
                const formData = new FormData(f);
                const resp = await fetch(f.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await resp.json().catch(() => null);
                if (!data) {
                    if (errorsBox) { errorsBox.textContent = 'Server error. Please try again.'; errorsBox.style.display = 'block'; }
                    if (btn) { if (text) text.style.display = ''; if (spinner) spinner.style.display = 'none'; btn.disabled = false; }
                    return;
                }

                if (!data.success) {
                    if (errorsBox) { errorsBox.textContent = data.message || 'Login failed'; errorsBox.style.display = 'block'; }
                    if (btn) { if (text) text.style.display = ''; if (spinner) spinner.style.display = 'none'; btn.disabled = false; }
                    return;
                }

                // Success: update header (no full reload) and close modal
                    if (data.customer_name) {
                    // Update account icon to point to account page
                    const accountIcon = document.querySelector('.account-icon');
                    if (accountIcon) {
                        accountIcon.href = 'account.php';
                        accountIcon.title = 'My Account';
                        accountIcon.setAttribute('aria-label', 'My Account');
                        // keep existing svg/icon; add tooltip with name
                        accountIcon.dataset.customerName = data.customer_name;
                    }

                    // Add logout link if not present
                    if (!document.querySelector('.logout-link')) {
                        const navIcons = document.querySelector('.nav-icons');
                        if (navIcons) {
                            const logout = document.createElement('a');
                            logout.href = 'logout.php';
                            logout.className = 'logout-link';
                            logout.textContent = 'Logout';
                            logout.style.marginLeft = '8px';
                            navIcons.insertBefore(logout, navIcons.querySelector('.menu-toggle'));
                        }
                    }

                    // Fetch server-rendered header fragment and replace nav-icons
                    try {
                        const r = await fetch('api/auth_status.php', { credentials: 'same-origin', cache: 'no-store' });
                        if (r.ok) {
                            const html = await r.text();
                            const navIcons = document.querySelector('.nav-icons');
                            if (navIcons) navIcons.innerHTML = html;
                        }
                    } catch (e) {
                        // ignore
                    }

                    if (loginModal && loginModal.classList.contains('active')) loginModal.classList.remove('active');
                    if (typeof showNotification === 'function') showNotification(data.message || 'Logged in', 'success');
                    return;
                }

                // fallback: redirect if no header update info
                const redirectTo = data.redirect || 'index.php';
                if (loginModal && loginModal.classList.contains('active')) loginModal.classList.remove('active');
                window.location.href = redirectTo;
            } catch (err) {
                console.error('Login error', err);
                if (errorsBox) { errorsBox.textContent = 'Network error. Please try again.'; errorsBox.style.display = 'block'; }
                if (btn) { if (text) text.style.display = ''; if (spinner) spinner.style.display = 'none'; btn.disabled = false; }
            }
        });
    });
    
    // Search functionality
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const keyword = this.value.trim();
            
            if (keyword.length < 2) {
                document.getElementById('searchResults').innerHTML = '';
                return;
            }
            
            searchTimeout = setTimeout(() => {
                searchProducts(keyword);
            }, 300);
        });
    }
    
    // Update cart count on page load
    updateCartCount();
});

// Search Products
async function searchProducts(keyword) {
    try {
        const response = await fetch(`api/products.php?action=search&keyword=${encodeURIComponent(keyword)}`);
        const products = await response.json();
        
        const resultsDiv = document.getElementById('searchResults');
        
        if (products.error) {
            resultsDiv.innerHTML = '<p>Error searching products.</p>';
            return;
        }
        
        if (products.length === 0) {
            resultsDiv.innerHTML = '<p>No products found.</p>';
            return;
        }
        
        let html = '<div class="search-results-list">';
        products.forEach(product => {
            html += `
                <a href="product-details.php?id=${product.id}" class="search-result-item">
                    <img src="${product.image_url || 'product_pictures/Adobo_Garlic_Cashew.jpg'}" alt="${product.name}">
                    <div>
                        <h4>${product.name}</h4>
                        <p>₱${parseFloat(product.price).toFixed(2)}</p>
                    </div>
                </a>
            `;
        });
        html += '</div>';
        
        resultsDiv.innerHTML = html;
    } catch (error) {
        console.error('Search error:', error);
    }
}

// Add to Cart
async function addToCart(productId, quantity = 1) {
    try {
        const response = await fetch('api/cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'add',
                product_id: productId,
                quantity: quantity
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Product added to cart!', 'success');
            updateCartCount();
        } else {
            showNotification(result.message || 'Error adding to cart', 'error');
        }
    } catch (error) {
        console.error('Add to cart error:', error);
        showNotification('Error adding to cart', 'error');
    }
}

// Update Cart Count
async function updateCartCount() {
    try {
        const response = await fetch('api/cart.php?action=get');
        const cart = await response.json();
        
        const cartCountElements = document.querySelectorAll('.cart-count');
        cartCountElements.forEach(element => {
            element.textContent = cart.count || 0;
        });
    } catch (error) {
        console.error('Update cart count error:', error);
    }
}

// Show Notification
function showNotification(message, type = 'info') {
    // Remove existing notification
    const existing = document.querySelector('.notification');
    if (existing) {
        existing.remove();
    }
    
    // Create notification
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    // Add styles
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
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
    
    .search-result-item {
        display: flex;
        gap: 15px;
        padding: 15px;
        border-bottom: 1px solid #e5e5e5;
        transition: background-color 0.3s;
    }
    
    .search-result-item:hover {
        background-color: #f9f7f4;
    }
    
    .search-result-item img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 5px;
    }
    
    .search-result-item h4 {
        font-size: 16px;
        margin-bottom: 5px;
    }
    
    .search-result-item p {
        color: #d4a574;
        font-weight: 600;
    }
`;
document.head.appendChild(style);

// Auth helpers: password toggle and simple UX improvements
document.addEventListener('DOMContentLoaded', function() {
    // Password toggle buttons
    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.password-field');
            if (!input) return;
            if (input.type === 'password') {
                input.type = 'text';
                this.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                input.type = 'password';
                this.innerHTML = '<i class="fas fa-eye"></i>';
            }
            input.focus();
        });
    });

    // Small nicety: focus first input on auth pages
    const firstAuthInput = document.querySelector('.auth-card input:first-of-type');
    if (firstAuthInput) firstAuthInput.focus();
});
