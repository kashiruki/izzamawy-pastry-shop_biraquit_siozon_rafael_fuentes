<?php
require_once __DIR__ . '/config/config.php';

// Redirect if cart is empty
if (empty($_SESSION['cart'])) {
    redirect('cart.php');
}

$cart_items = $_SESSION['cart'];
$subtotal = calculate_cart_total();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="top-bar">
            <div class="container">
                <p>Secure Checkout</p>
            </div>
        </div>
        
        <nav class="navbar">
            <div class="container">
                <a href="index.php" class="logo">
                    <h1><?php echo SITE_NAME; ?></h1>
                </a>
            </div>
        </nav>
    </header>

    <!-- Checkout Section -->
    <section class="checkout-section">
        <div class="container">
            <h1>Checkout</h1>
            
            <div class="checkout-layout">
                <div class="checkout-form">
                    <form id="checkoutForm">
                        <div class="form-section">
                            <h2><i class="fas fa-user"></i> Contact Information</h2>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="customer_name">Full Name *</label>
                                    <input type="text" id="customer_name" name="customer_name" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="customer_email">Email Address *</label>
                                    <input type="email" id="customer_email" name="customer_email" required>
                                </div>
                                <div class="form-group">
                                    <label for="customer_phone">Phone Number *</label>
                                    <input type="tel" id="customer_phone" name="customer_phone" required
                                           inputmode="numeric" pattern="\d{10,11}" maxlength="11" minlength="10"
                                           placeholder="e.g. 09171234567" aria-describedby="phoneHelp">
                                    <small id="phoneHelp" class="form-help">Digits only, 10–11 characters.</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h2><i class="fas fa-shipping-fast"></i> Shipping Address</h2>
                            <div class="form-group">
                                <label for="shipping_address">Street Address *</label>
                                <textarea id="shipping_address" name="shipping_address" rows="3" required></textarea>
                            </div>
                            <div class="form-row single">
                                <div class="form-group">
                                    <label for="province">Municipality *</label>
                                                                        <select id="province" name="province" required>
                                                                            <option value="">Select Municipality</option>
                                                                            <?php foreach (SHIPPING_RATES as $mun => $price): ?>
                                                                                <?php $days = (defined('SHIPPING_ESTIMATES') && isset(SHIPPING_ESTIMATES[$mun])) ? SHIPPING_ESTIMATES[$mun] : null; ?>
                                                                                <option value="<?php echo htmlspecialchars($mun); ?>" data-shipping="<?php echo (int)$price; ?>" data-days="<?php echo $days ?? ''; ?>">
                                                                                    <?php echo htmlspecialchars($mun . ' — ₱' . number_format($price) . ($days ? ' (Est. ' . $days . 'd)' : '')); ?>
                                                                                </option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="postal_code">Postal Code</label>
                                <input type="text" id="postal_code" name="postal_code" inputmode="numeric" pattern="\d{4}" maxlength="4" minlength="4" placeholder="e.g. 2000" aria-describedby="postalHelp">
                                <small id="postalHelp" class="form-help">4 digits.</small>
                            </div>
                        </div>

                        <div class="form-section">
                            <h2><i class="fas fa-credit-card"></i> Payment Method</h2>
                            <div class="payment-methods">
                                <?php foreach (PAYMENT_METHODS as $key => $label): ?>
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="<?php echo $key; ?>" 
                                           <?php echo $key === 'cod' ? 'checked' : ''; ?>>
                                    <span><?php echo $label; ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="form-section">
                            <h2><i class="fas fa-sticky-note"></i> Order Notes (Optional)</h2>
                            <div class="form-group">
                                <textarea id="notes" name="notes" rows="4" 
                                          placeholder="Special instructions for your order..."></textarea>
                            </div>
                        </div>

                        <div id="orderMessage" class="order-message"></div>

                        <button type="submit" class="btn btn-primary btn-block btn-large">
                            <i class="fas fa-lock"></i> Place Order
                        </button>
                    </form>
                </div>

                <div class="checkout-summary">
                    <h2>Order Summary</h2>
                    <div class="summary-items">
                        <?php foreach ($cart_items as $item): ?>
                        <div class="summary-item">
                            <img src="<?php echo get_product_image($item['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <div class="summary-item-info">
                                <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                <p>Qty: <?php echo $item['quantity']; ?></p>
                            </div>
                            <span class="summary-item-price">
                                <?php echo format_price($item['price'] * $item['quantity']); ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="summary-totals">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span id="summarySubtotal"><?php echo format_price($subtotal); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping:</span>
                            <span id="summaryShipping">Calculated at next step</span>
                        </div>
                        <div class="summary-row">
                            <span>Estimated Delivery:</span>
                            <span id="summaryDelivery">Select municipality</span>
                        </div>
                        <div class="summary-total">
                            <span>Estimated Total:</span>
                            <span id="summaryTotal"><?php echo format_price($subtotal); ?></span>
                        </div>
                    </div>

                    <div class="secure-checkout">
                        <i class="fas fa-shield-alt"></i>
                        <p>Secure Checkout</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    <?php include __DIR__ . '/inc/footer.php'; ?>
    <?php include __DIR__ . '/inc/footer.php'; ?>

    <script src="js/main.js"></script>
    <script src="js/checkout.js"></script>

