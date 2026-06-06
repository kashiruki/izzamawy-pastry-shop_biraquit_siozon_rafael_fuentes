<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/api/orders.php';

$order_number = trim($_GET['order_number'] ?? '');
$order = null;
if ($order_number !== '') {
    $api = new OrdersAPI();
    $order = $api->getOrder($order_number);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>View Order - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/inc/header.php'; ?>

    <section class="page-header">
        <div class="container">
            <h1>View Order</h1>
            <p>Enter your order number to see the details of your purchase.</p>
        </div>
    </section>

    <section style="padding:60px 0; min-height:60vh;">
        <div class="container" style="max-width:900px; margin:0 auto;">
            <div style="background-color:var(--bg-light); padding:32px; border-radius:10px;">
                <form method="get" action="view_order.php" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
                    <div style="flex:1;min-width:220px;">
                        <label for="order_number" style="display:block;margin-bottom:8px;font-weight:700;color:var(--text-dark);">Order Number</label>
                        <input id="order_number" name="order_number" type="text" placeholder="e.g. ORD-20260117-XXXX" value="<?php echo htmlspecialchars($order_number); ?>" style="width:100%;padding:12px;border:1px solid var(--border-color);border-radius:6px;font-size:15px;">
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">View Order</button>
                    </div>
                </form>

                <?php if ($order_number !== ''): ?>
                    <div style="margin-top:20px;">
                        <?php if ($order && !isset($order['error'])): ?>
                            <div style="background:#fff;padding:20px;border-radius:8px;border:1px solid var(--border-color);">
                                <h2 style="margin-top:0;color:var(--text-dark);">Order <?php echo htmlspecialchars($order['order_number'] ?? ''); ?></h2>
                                <div style="color:var(--muted);margin-bottom:12px;">Placed on <?php echo htmlspecialchars(isset($order['created_at']) ? date('F d, Y', strtotime($order['created_at'])) : '—'); ?></div>

                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
                                    <div>
                                        <strong>Customer</strong>
                                        <div><?php echo htmlspecialchars($order['customer_name'] ?? '—'); ?></div>
                                        <div style="color:var(--muted);font-size:13px"><?php echo htmlspecialchars($order['customer_email'] ?? ''); ?></div>
                                    </div>
                                    <div>
                                        <strong>Shipping Address</strong>
                                        <div style="text-align:right;"><?php echo nl2br(htmlspecialchars($order['shipping_address'] ?? '—')); ?><br><?php echo htmlspecialchars($order['city'] ?? ''); ?>, <?php echo htmlspecialchars($order['province'] ?? ''); ?></div>
                                    </div>
                                </div>

                                <h3 style="margin:8px 0 12px 0;color:var(--text-dark);">Items</h3>
                                <div style="border-top:1px solid var(--border-color);padding-top:12px">
                                    <?php if (!empty($order['items']) && is_array($order['items'])): ?>
                                        <?php foreach ($order['items'] as $it): ?>
                                            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #eee">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($it['product_name']); ?></strong>
                                                    <div style="color:var(--muted);font-size:13px">Quantity: <?php echo (int)$it['quantity']; ?> &middot; Unit: <?php echo format_price($it['unit_price']); ?></div>
                                                </div>
                                                <div style="font-weight:700;align-self:center"><?php echo format_price($it['subtotal']); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div style="color:var(--muted);">No items found for this order.</div>
                                    <?php endif; ?>
                                </div>

                                <div style="margin-top:14px;border-top:2px solid var(--border-color);padding-top:12px;display:flex;justify-content:space-between;align-items:center">
                                    <div style="color:var(--muted)">Subtotal</div>
                                    <div><?php echo isset($order['subtotal']) ? format_price($order['subtotal']) : '-'; ?></div>
                                </div>
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px">
                                    <div style="color:var(--muted)">Shipping</div>
                                    <div><?php echo (isset($order['shipping_fee']) && $order['shipping_fee']>0) ? format_price($order['shipping_fee']) : 'FREE'; ?></div>
                                </div>
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;font-weight:800;color:var(--primary-color);font-size:18px">
                                    <div>Total</div>
                                    <div><?php echo isset($order['total']) ? format_price($order['total']) : '-'; ?></div>
                                </div>

                                <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap">
                                    <a href="index.php" class="btn btn-outline">Back to Home</a>
                                    <button onclick="window.print()" class="btn btn-secondary">Print</button>
                                </div>
                            </div>
                        <?php else: ?>
                            <div style="background:#fff4f4;border:1px solid #f5c2c7;padding:18px;border-radius:8px;color:#842029">
                                <strong>Order not found.</strong>
                                <div style="margin-top:8px;color:var(--muted)">Please check your order number and try again.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>
