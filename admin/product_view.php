<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
if (!is_admin_logged_in()) redirect(SITE_URL . '/admin/admin_login.php');
$db = Database::getInstance()->getConnection();
$id = (int)($_GET['id'] ?? 0);
$product = null;
if ($id) {
    try {
        $stmt = $db->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) { $product = null; }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Product Details - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body style="padding:24px;font-family:Segoe UI, Tahoma, sans-serif;background:#f8f7f5">
    <a href="dashboard.php">← Back to dashboard</a>
    <div style="max-width:900px;margin-top:14px;background:#fff;padding:18px;border-radius:10px;box-shadow:0 8px 26px rgba(0,0,0,0.06)">
        <?php if (!$product): ?>
            <h2>Product not found</h2>
            <p>No product matches that ID.</p>
        <?php else: ?>
            <h2><?php echo htmlspecialchars($product['name'] ?? ''); ?></h2>
            <p style="color:#666">SKU: <?php echo htmlspecialchars($product['sku'] ?? ''); ?> · Price: <?php echo format_price($product['price'] ?? 0); ?></p>
            <p><?php echo nl2br(htmlspecialchars($product['description'] ?? '')); ?></p>
            <p><strong>Stock:</strong> <?php echo (int)($product['stock_quantity'] ?? 0); ?></p>
            <div style="margin-top:12px">
                <a class="btn" href="real_time_inventory.php">Manage Stock</a>
                <a class="btn ghost" href="product_view.php?id=<?php echo (int)$id; ?>&edit=1">Edit</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
