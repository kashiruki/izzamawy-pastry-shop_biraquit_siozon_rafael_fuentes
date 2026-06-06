<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
if (!is_logged_in()) {
    redirect('login.php');
}

$db = Database::getInstance()->getConnection();
$customer = [];
try {
    $stmt = $db->prepare('SELECT * FROM customers WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $_SESSION['customer_id']]);
    $customer = $stmt->fetch();
} catch (Exception $e) {
    // ignore
}

// Fetch recent orders
$orders = [];
try {
    $stmt = $db->prepare('SELECT * FROM orders WHERE customer_id = :id ORDER BY created_at DESC LIMIT 20');
    $stmt->execute([':id' => $_SESSION['customer_id']]);
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    // ignore
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include __DIR__ . '/inc/header.php'; ?>

    <section class="page-header">
        <div class="container">
            <h1>My Account</h1>
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION['customer_name'] ?? ''); ?></p>
        </div>
    </section>

    <section style="padding:60px 0;">
        <div class="container">
            <div style="display:grid;grid-template-columns:1fr 380px;gap:30px;">
                <div>
                    <h2 style="margin-bottom:12px;">Order History</h2>
                    <?php if (!empty($orders)): ?>
                        <div style="background:var(--bg-light);padding:16px;border-radius:8px;">
                            <?php foreach ($orders as $o): ?>
                                <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border-color);">
                                    <div>
                                        <strong><?php echo htmlspecialchars($o['order_number']); ?></strong>
                                        <div style="color:var(--text-light);font-size:13px;"><?php echo date('F d, Y', strtotime($o['created_at'])); ?></div>
                                    </div>
                                    <div style="text-align:right;">
                                        <div style="font-weight:700;color:var(--primary-color);"><?php echo format_price($o['total']); ?></div>
                                        <div style="color:var(--text-light);font-size:13px;margin-top:6px;">Status: <?php echo htmlspecialchars($o['order_status']); ?></div>
                                        <div style="margin-top:8px;"><a href="order-confirmation.php?order=<?php echo urlencode($o['order_number']); ?>" class="btn btn-outline">View</a></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>You have no recent orders.</p>
                    <?php endif; ?>
                </div>

                <aside style="width:100%;max-width:380px;">
                    <div style="background:var(--bg-light);padding:16px;border-radius:8px;margin-bottom:20px;">
                        <h3 style="margin-bottom:8px;">Profile</h3>
                        <form action="account_action.php" method="post">
                            <input type="hidden" name="action" value="update_profile">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px;">
                                <input type="text" name="first_name" placeholder="First name" required value="<?php echo htmlspecialchars($customer['first_name'] ?? ''); ?>">
                                <input type="text" name="last_name" placeholder="Last name" required value="<?php echo htmlspecialchars($customer['last_name'] ?? ''); ?>">
                            </div>
                            <input type="email" name="email" placeholder="Email" required value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>" style="width:100%;margin-bottom:8px;">
                            <input type="text" name="phone" placeholder="Phone" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>" style="width:100%;margin-bottom:8px;">
                            <button class="btn btn-primary" type="submit">Save Profile</button>
                        </form>
                    </div>

                    <div style="background:var(--bg-light);padding:16px;border-radius:8px;">
                        <h3 style="margin-bottom:8px;">Address Book</h3>
                        <form action="account_action.php" method="post">
                            <input type="hidden" name="action" value="update_address">
                            <textarea name="address" placeholder="Address" style="width:100%;min-height:80px;margin-bottom:8px;"><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                            <input type="text" name="city" placeholder="City" value="<?php echo htmlspecialchars($customer['city'] ?? ''); ?>" style="width:100%;margin-bottom:8px;">
                            <input type="text" name="province" placeholder="Province" value="<?php echo htmlspecialchars($customer['province'] ?? ''); ?>" style="width:100%;margin-bottom:8px;">
                            <input type="text" name="postal_code" placeholder="Postal Code" value="<?php echo htmlspecialchars($customer['postal_code'] ?? ''); ?>" style="width:100%;margin-bottom:8px;">
                            <button class="btn btn-primary" type="submit">Save Address</button>
                        </form>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>
