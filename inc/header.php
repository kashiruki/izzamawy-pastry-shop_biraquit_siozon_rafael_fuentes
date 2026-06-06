<?php
// Shared header include
require_once __DIR__ . '/../config/config.php';
?>
<!---- Header start ---->
<header class="header">
    <div class="top-bar">
        <div class="container">
            <div class="announcement-bar" role="region" aria-label="Site announcement">
                <div class="marquee" aria-live="polite" role="status">Free shipping on orders over ₱1,000!</div>
            </div>
        </div>
    </div>

    <nav class="navbar">
        <div class="container">
            <a href="<?php echo SITE_URL; ?>" class="logo">
                <h1><?php echo SITE_NAME; ?></h1>
            </a>

            <ul class="nav-menu">
                <li><a href="<?php echo SITE_URL; ?>/index.php"<?php if (basename($_SERVER['PHP_SELF']) === 'index.php') echo ' class="active"'; ?>>Home</a></li>
                <li><a href="<?php echo SITE_URL; ?>/products.php"<?php if (basename($_SERVER['PHP_SELF']) === 'products.php') echo ' class="active"'; ?>>Products</a></li>
                <li><a href="<?php echo SITE_URL; ?>/about.php"<?php if (basename($_SERVER['PHP_SELF']) === 'about.php') echo ' class="active"'; ?>>About</a></li>
                <li><a href="<?php echo SITE_URL; ?>/view_order.php"<?php if (basename($_SERVER['PHP_SELF']) === 'view_order.php') echo ' class="active"'; ?>>View Order</a></li>
                <li><a href="<?php echo SITE_URL; ?>/contact.php"<?php if (basename($_SERVER['PHP_SELF']) === 'contact.php') echo ' class="active"'; ?>>Contact</a></li>
                <?php if (is_logged_in()): ?>
                    <li><a href="<?php echo SITE_URL; ?>/account.php"<?php if (basename($_SERVER['PHP_SELF']) === 'account.php') echo ' class="active"'; ?>>My Account</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/logout.php">Logout</a></li>
                <?php else: ?>
                    <!-- Register & Login removed from nav menu per request; use account button next to cart -->
                <?php endif; ?>
            </ul>

            <div class="nav-icons">
                <a href="<?php echo SITE_URL; ?>/cart.php" class="cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" id="cartCount"><?php echo get_cart_count(); ?></span>
                </a>
                <!-- React header mount point (keeps existing markup for safety) -->
                <div id="react-header-root" data-cart-count="<?php echo get_cart_count(); ?>" style="display:inline-block;vertical-align:middle"></div>
                <?php if (is_logged_in()): ?>
                    <a href="<?php echo SITE_URL; ?>/account.php" class="account-icon" title="My Account" aria-label="My Account">
                        <!-- simple user SVG -->
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M12 12c2.761 0 5-2.239 5-5s-2.239-5-5-5-5 2.239-5 5 2.239 5 5 5z" fill="currentColor" />
                            <path d="M4 20c0-4.418 3.582-8 8-8s8 3.582 8 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                <?php else: ?>
                    <a href="#" id="loginBtn" class="account-icon" title="Login" aria-label="Login">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M12 12c2.761 0 5-2.239 5-5s-2.239-5-5-5-5 2.239-5 5 2.239 5 5 5z" fill="currentColor" />
                            <path d="M4 20c0-4.418 3.582-8 8-8s8 3.582 8 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                <?php endif; ?>
                <div class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></div>
            </div>
        </div>
    </nav>

    <?php if (!empty($_SESSION['flash'])): ?>
        <?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
        <div class="container" style="padding:10px 20px;">
            <div style="border-radius:6px;padding:12px;<?php echo $flash['type']==='success' ? 'background:#e6ffed;color:#1b5e20;' : 'background:#fff4f4;color:#842029;'; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        </div>
    <?php endif; ?>
</header>
<!-- Login Modal -->
<?php if (!is_logged_in()): ?>
<div class="login-modal" id="loginModal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="login-modal-content" role="document" aria-labelledby="loginModalTitle">
        <button class="close-login" aria-label="Close">&times;</button>
        <div class="auth-card" role="form">
            <div class="auth-brand">
                <img src="images/logo.png" alt="<?php echo SITE_NAME; ?>" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='block'">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="72" height="72" style="display:none;" aria-hidden="true">
                    <defs>
                        <linearGradient id="g" x1="0" x2="1">
                            <stop offset="0" stop-color="#D4AF37"/>
                            <stop offset="1" stop-color="#B8860B"/>
                        </linearGradient>
                    </defs>
                    <rect width="64" height="64" rx="12" fill="#fff"/>
                    <path d="M32 12c6 0 10 4 10 10s-4 10-10 10-10-4-10-10 4-10 10-10z" fill="url(#g)"/>
                    <path d="M12 52c0-8 8-16 20-16s20 8 20 16" stroke="#B8860B" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                </svg>
                <h3 id="loginModalTitle">Welcome back</h3>
                <p class="auth-sub">Sign in to continue to <?php echo SITE_NAME; ?></p>
            </div>
            <form method="POST" action="<?php echo SITE_URL; ?>/login_simple_action.php" class="auth-form">
                <div class="auth-errors" aria-live="polite" style="display:none"></div>

                <label for="modalUsername">Email or Username</label>
                <input id="modalUsername" name="username" type="text" autocomplete="username" required>

                <label for="modalPassword">Password</label>
                <div class="input-with-icon">
                    <input id="modalPassword" name="password" type="password" class="password-field" autocomplete="current-password" required>
                    <button type="button" class="toggle-password" aria-label="Toggle password visibility"><i class="fas fa-eye"></i></button>
                </div>

                <div class="auth-row">
                    <label class="remember"><input type="checkbox" name="remember"> Remember me</label>
                    <a href="<?php echo SITE_URL; ?>/forgot_password.php" class="forgot-link">Forgot?</a>
                </div>

                <button type="submit" class="btn btn-primary submit-btn">
                    <span class="btn-text">Login</span>
                    <span class="btn-spinner" aria-hidden="true" style="display:none"><i class="fas fa-circle-notch fa-spin"></i></span>
                </button>
                <p class="auth-small">Don't have an account? <a href="<?php echo SITE_URL; ?>/register.php">Create one</a></p>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<!---- Header end ---->
