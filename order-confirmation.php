<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/api/orders.php';

$order_number = $_GET['order'] ?? '';
$order = null;

// If no order number in GET, check session for last created order (from API)
if (empty($order_number) && isset($_SESSION['last_order_number'])) {
    $order_number = $_SESSION['last_order_number'];
}

if ($order_number) {
    $ordersAPI = new OrdersAPI();
    $order = $ordersAPI->getOrder($order_number);
    // clear session-stored order_number after fetching so page can be re-used
    if (isset($_SESSION['last_order_number']) && $_SESSION['last_order_number'] === $order_number) {
        unset($_SESSION['last_order_number']);
    }
}
        // Send a one-time confirmation email to the customer using send_email()
        if ($order && !empty($order['customer_email'])) {
            // avoid re-sending on page refreshes during the same session
            if (!isset($_SESSION['emailed_orders']) || !is_array($_SESSION['emailed_orders'])) {
                $_SESSION['emailed_orders'] = [];
            }

            if (empty($_SESSION['emailed_orders'][$order['order_number']])) {
                $subject = 'Order Confirmation - ' . SITE_NAME . ' - ' . $order['order_number'];

                // Build a nicer HTML email body with inline CSS and branding
                $logo = rtrim(SITE_URL, '/') . '/images/logo.png';
                $body = '<!doctype html><html><head><meta charset="utf-8"><title>' . htmlspecialchars($subject) . '</title>';
                $body .= '<style>body{font-family:Arial,Helvetica,sans-serif;background:#f4f6f8;color:#222;margin:0;padding:0} .email-wrap{max-width:600px;margin:24px auto;background:#ffffff;border:1px solid #e9eef2;border-radius:8px;overflow:hidden} .email-header{background:#ffffff;padding:20px;text-align:center} .email-header img{max-height:60px} .email-body{padding:20px} h2{color:#2b6cb0;margin:0 0 12px} .order-details{width:100%;border-collapse:collapse;margin-top:12px} .order-details td{padding:8px;border-bottom:1px solid #eef2f6} .order-total{font-weight:700;color:#1a202c;font-size:18px;padding-top:12px} .footer{padding:16px;text-align:center;font-size:13px;color:#667085;background:#f8fafc}</style>';
                $body .= '</head><body><div class="email-wrap">';
                $body .= '<div class="email-header"><img src="' . $logo . '" alt="' . htmlspecialchars(SITE_NAME) . '" /></div>';
                $body .= '<div class="email-body">';
                $body .= '<h2>Order Confirmed</h2>';
                $body .= '<p>Thanks for your purchase. Below are the details for <strong>' . htmlspecialchars($order['order_number']) . '</strong>.</p>';
                $body .= '<table class="order-details">';
                $body .= '<tr><td><strong>Order Number</strong></td><td>' . htmlspecialchars($order['order_number']) . '</td></tr>';
                $body .= '<tr><td><strong>Date</strong></td><td>' . date('F d, Y', strtotime($order['created_at'])) . '</td></tr>';
                $body .= '<tr><td><strong>Customer</strong></td><td>' . htmlspecialchars($order['customer_name']) . '</td></tr>';
                $body .= '<tr><td><strong>Email</strong></td><td>' . htmlspecialchars($order['customer_email']) . '</td></tr>';
                $body .= '</table>';

                $body .= '<h3 style="margin-top:18px">Items</h3>';
                $body .= '<table class="order-details">';
                if (!empty($order['items']) && is_array($order['items'])) {
                    foreach ($order['items'] as $it) {
                        $body .= '<tr><td>' . htmlspecialchars($it['product_name']) . ' x' . intval($it['quantity']) . '</td><td style="text-align:right">' . format_price($it['subtotal']) . '</td></tr>';
                    }
                }
                $body .= '<tr><td>Subtotal</td><td style="text-align:right">' . format_price($order['subtotal']) . '</td></tr>';
                $body .= '<tr><td>Shipping</td><td style="text-align:right">' . ($order['shipping_fee'] > 0 ? format_price($order['shipping_fee']) : 'FREE') . '</td></tr>';
                $body .= '<tr class="order-total"><td>Total</td><td style="text-align:right">' . format_price($order['total']) . '</td></tr>';
                $body .= '</table>';

                $body .= '<h4 style="margin-top:18px">Shipping Address</h4>';
                $body .= '<p>' . nl2br(htmlspecialchars($order['shipping_address'])) . '<br>' . htmlspecialchars($order['city']) . ', ' . htmlspecialchars($order['province']) . '</p>';

                $body .= '<p>If you have any questions, reply to this email or contact us at ' . ADMIN_EMAIL . '.</p>';
                $body .= '</div>'; // email-body
                $body .= '<div class="footer">' . htmlspecialchars(SITE_NAME) . ' &middot; ' . htmlspecialchars(SITE_TAGLINE) . '</div>';
                $body .= '</div></body></html>';

                $alt = 'Order ' . $order['order_number'] . ' confirmed. Total: ' . format_price($order['total']);

                try {
                    $sent = send_email($order['customer_email'], $subject, $body, $alt);
                    $_SESSION['emailed_orders'][$order['order_number']] = $sent ? true : false;
                    // optional logging
                    if (!$sent) {
                        @file_put_contents(__DIR__ . '/storage/logs/mail_debug.log', date('[Y-m-d H:i:s] ') . "Failed to send order confirmation to {$order['customer_email']} for {$order['order_number']}\n", FILE_APPEND);
                    }
                } catch (Throwable $e) {
                    $_SESSION['emailed_orders'][$order['order_number']] = false;
                    @file_put_contents(__DIR__ . '/storage/logs/mail_debug.log', date('[Y-m-d H:i:s] ') . "Exception sending mail: " . $e->getMessage() . "\n", FILE_APPEND);
                }
            }
        }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/inc/header.php'; ?>

    <!-- Confirmation Section -->
    <section style="padding: 60px 0; min-height: 70vh;">
        <div class="container">
            <?php if ($order && !isset($order['error'])): ?>
            <div style="max-width: 800px; margin: 0 auto; text-align: center;">
                <!-- Success Icon -->
                <div style="margin-bottom: 30px;">
                    <i class="fas fa-check-circle" style="font-size: 80px; color: var(--success);"></i>
                </div>
                
                <h1 style="font-size: 36px; margin-bottom: 15px; color: var(--text-dark);">Order Confirmed!</h1>
                <p style="font-size: 18px; color: var(--text-light); margin-bottom: 40px;">
                    Thank you for your order. We've received it and will process it shortly.
                </p>
                
                <!-- Order Details Card -->
                <div style="background-color: var(--bg-light); padding: 40px; border-radius: 10px; text-align: left; margin-bottom: 30px;">
                    <h2 style="font-size: 24px; margin-bottom: 20px; color: var(--primary-color);">Order Details</h2>
                    
                    <div style="display: grid; gap: 15px; margin-bottom: 30px;">
                        <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                            <strong>Order Number:</strong>
                            <span><?php echo htmlspecialchars($order['order_number']); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                            <strong>Date:</strong>
                            <span><?php echo date('F d, Y', strtotime($order['created_at'])); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                            <strong>Customer:</strong>
                            <span><?php echo htmlspecialchars($order['customer_name']); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                            <strong>Email:</strong>
                            <span><?php echo htmlspecialchars($order['customer_email']); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                            <strong>Phone:</strong>
                            <span><?php echo htmlspecialchars($order['customer_phone']); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                            <strong>Shipping Address:</strong>
                            <span style="text-align: right;">
                                <?php echo htmlspecialchars($order['shipping_address']); ?>,<br>
                                <?php echo htmlspecialchars($order['city']); ?>, <?php echo htmlspecialchars($order['province']); ?>
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                            <strong>Payment Method:</strong>
                            <span><?php echo ucfirst($order['payment_method']); ?></span>
                        </div>
                    </div>
                    
                    <h3 style="font-size: 20px; margin-bottom: 15px; color: var(--text-dark);">Order Items</h3>
                    <div style="margin-bottom: 20px;">
                        <?php foreach ($order['items'] as $item): ?>
                        <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border-color);">
                            <div>
                                <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                <span style="color: var(--text-light); margin-left: 10px;">x<?php echo $item['quantity']; ?></span>
                            </div>
                            <span><?php echo format_price($item['subtotal']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="border-top: 2px solid var(--border-color); padding-top: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Subtotal:</span>
                            <span><?php echo format_price($order['subtotal']); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                            <span>Shipping:</span>
                            <span><?php echo $order['shipping_fee'] > 0 ? format_price($order['shipping_fee']) : 'FREE'; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 24px; font-weight: 700; color: var(--primary-color);">
                            <span>Total:</span>
                            <span><?php echo format_price($order['total']); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Next Steps -->
                <div style="background-color: #e8f5e9; padding: 25px; border-radius: 10px; margin-bottom: 30px; text-align: left;">
                    <h3 style="font-size: 20px; margin-bottom: 15px; color: var(--success);">
                        <i class="fas fa-info-circle"></i> What's Next?
                    </h3>
                    <ul style="list-style: none; padding: 0; color: var(--text-dark);">
                        <li style="margin-bottom: 10px;">
                            <i class="fas fa-check" style="color: var(--success); margin-right: 10px;"></i>
                            You will receive a confirmation email at <?php echo htmlspecialchars($order['customer_email']); ?>
                        </li>
                        <li style="margin-bottom: 10px;">
                            <i class="fas fa-check" style="color: var(--success); margin-right: 10px;"></i>
                            We will contact you to confirm your order
                        </li>
                        <li style="margin-bottom: 10px;">
                            <i class="fas fa-check" style="color: var(--success); margin-right: 10px;"></i>
                            Your order will be prepared and shipped within 1-2 business days
                        </li>
                        <li>
                            <i class="fas fa-check" style="color: var(--success); margin-right: 10px;"></i>
                            Save your order number for reference: <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                        </li>
                    </ul>
                </div>
                
                <!-- Action Buttons -->
                <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> Back to Homepage
                    </a>
                    <a href="products.php" class="btn btn-secondary">
                        <i class="fas fa-shopping-bag"></i> Continue Shopping
                    </a>
                    <button onclick="window.print()" class="btn btn-outline">
                        <i class="fas fa-print"></i> Print Order
                    </button>
                </div>
            </div>
            <?php else: ?>
            <div style="text-align: center; padding: 60px 20px;">
                <i class="fas fa-exclamation-circle" style="font-size: 80px; color: var(--danger); margin-bottom: 20px;"></i>
                <h1 style="font-size: 32px; margin-bottom: 15px;">Order Not Found</h1>
                <p style="font-size: 18px; color: var(--text-light); margin-bottom: 30px;">
                    We couldn't find the order you're looking for.
                </p>
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Go to Homepage
                </a>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>
