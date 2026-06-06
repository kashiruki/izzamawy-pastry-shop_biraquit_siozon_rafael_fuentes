<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Return a small HTML fragment for the header's nav-icons
header('Content-Type: text/html; charset=utf-8');

$logged = is_logged_in();
?>
<?php if ($logged): ?>
    <a href="<?php echo SITE_URL; ?>/cart.php" class="cart-icon">
        <i class="fas fa-shopping-cart"></i>
        <span class="cart-count" id="cartCount"><?php echo get_cart_count(); ?></span>
    </a>
    <a href="<?php echo SITE_URL; ?>/account.php" class="account-icon" title="My Account" aria-label="My Account">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M12 12c2.761 0 5-2.239 5-5s-2.239-5-5-5-5 2.239-5 5 2.239 5 5 5z" fill="currentColor" />
            <path d="M4 20c0-4.418 3.582-8 8-8s8 3.582 8 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </a>
    <a href="<?php echo SITE_URL; ?>/logout.php" class="logout-link">Logout</a>
<?php else: ?>
    <a href="<?php echo SITE_URL; ?>/cart.php" class="cart-icon">
        <i class="fas fa-shopping-cart"></i>
        <span class="cart-count" id="cartCount"><?php echo get_cart_count(); ?></span>
    </a>
    <a href="#" id="loginBtn" class="account-icon" title="Login" aria-label="Login">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M12 12c2.761 0 5-2.239 5-5s-2.239-5-5-5-5 2.239-5 5 2.239 5 5 5z" fill="currentColor" />
            <path d="M4 20c0-4.418 3.582-8 8-8s8 3.582 8 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </a>
<?php endif; ?>
