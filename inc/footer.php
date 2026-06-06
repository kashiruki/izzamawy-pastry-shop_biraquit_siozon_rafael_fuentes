<?php
// Shared footer include
?>
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h3><?php echo SITE_NAME; ?></h3>
                <p><?php echo SITE_TAGLINE; ?></p>
            </div>

            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="<?php echo SITE_URL; ?>/products.php">Products</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/about.php">About Us</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/contact.php">Contact</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/login_simple.php">Admin Log-In</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </div>
</footer>
<?php
// Include React bundle if built
$reactBundle = __DIR__ . '/../js/react/app.js';
if (file_exists($reactBundle)) {
    echo '<script src="js/react/app.js" defer></script>';
}
?>
