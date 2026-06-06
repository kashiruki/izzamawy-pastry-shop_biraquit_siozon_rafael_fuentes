<?php
require_once __DIR__ . '/config/config.php';

$cart_items = $_SESSION['cart'] ?? [];
$subtotal = calculate_cart_total();
$shipping_estimate = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_FEE_METRO_MANILA;
$total = $subtotal + $shipping_estimate;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/inc/header.php'; ?>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1>Shopping Cart</h1>
            <p>Review your items before checkout</p>
        </div>
    </section>

    <!-- Cart Section -->
    <section class="cart-section">
        <div class="container">
            <!-- React cart mount point (hidden for progressive enhancement) -->
            <div id="react-cart-root" data-cart-count="<?php echo count($cart_items); ?>" style="display:none"></div>
            <?php if (!empty($cart_items)): ?>
            <div class="cart-layout">
                <div class="cart-items">
                    <h2>Cart Items</h2>
                    <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item" data-product-id="<?php echo $item['id']; ?>">
                        <img src="<?php echo get_product_image($item['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <div class="cart-item-details">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="cart-item-price"><?php echo format_price($item['price']); ?></p>
                        </div>
                        <div class="cart-item-quantity">
                            <button class="qty-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, -1)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" value="<?php echo $item['quantity']; ?>" 
                                   min="1" class="qty-input" readonly>
                            <button class="qty-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, 1)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div class="cart-item-total">
                            <?php echo format_price($item['price'] * $item['quantity']); ?>
                        </div>
                        <button class="remove-item" onclick="removeFromCart(<?php echo $item['id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                
                <!-- Continue Shopping button placed at the bottom of the left column -->
                <div class="continue-shopping-left">
                    <a href="products.php" class="btn btn-outline btn-continue-left">
                        Continue Shopping
                    </a>
                </div>
                </div>
                <div class="cart-summary">
                    <h2>Order Summary</h2>
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span id="subtotal"><?php echo format_price($subtotal); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping (estimate):</span>
                        <span id="shipping"><?php echo format_price($shipping_estimate); ?></span>
                    </div>
                    <?php if ($subtotal >= FREE_SHIPPING_THRESHOLD): ?>
                    <div class="free-shipping-notice">
                        <i class="fas fa-check-circle"></i>
                        You qualify for free shipping!
                    </div>
                    <?php else: ?>
                    <div class="shipping-notice">
                        <i class="fas fa-info-circle"></i>
                        Add <?php echo format_price(FREE_SHIPPING_THRESHOLD - $subtotal); ?> more for free shipping!
                    </div>
                    <?php endif; ?>
                    <div class="summary-total">
                        <span>Total:</span>
                        <span id="total"><?php echo format_price($total); ?></span>
                    </div>
                    <a href="checkout.php" class="btn btn-primary btn-block">
                        Proceed to Checkout
                    </a>
                </div>
            </div>
            <?php else: ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h2>Your cart is empty</h2>
                <p>Start adding some delicious treats!</p>
                <a href="products.php" class="btn btn-primary">Shop Now</a>
            </div>
            <?php endif; ?>
        </div>
    </section>

        <?php include __DIR__ . '/inc/footer.php'; ?>

    <script src="js/main.js"></script>
    <script src="js/cart.js"></script>
</body>
</html>
