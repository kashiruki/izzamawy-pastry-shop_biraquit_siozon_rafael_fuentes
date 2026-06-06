<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
if (!is_admin_logged_in()) redirect(SITE_URL . '/admin/admin_login.php');
$db = Database::getInstance()->getConnection();
$id = (int)($_GET['id'] ?? 0);
$order = null;
$items = [];
if ($id) {
    try {
        $stmt = $db->prepare('SELECT * FROM orders WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        try {
            $it = $db->prepare('SELECT * FROM order_items WHERE order_id = :id');
            $it->execute([':id' => $id]);
            $items = $it->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) { $items = []; }
    } catch (Throwable $e) { $order = null; }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Order Details - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body style="padding:24px;font-family:Segoe UI, Tahoma, sans-serif;background:#f8f7f5">
    <?php
    $backUrl = 'dashboard.php';
    if (isset($_GET['return']) && $_GET['return'] === 'order_checker') {
        $backUrl = 'order_checker.php';
    } elseif (!empty($_SERVER['HTTP_REFERER'])) {
        // If referer is within admin, prefer it
        $ref = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH);
        if ($ref && strpos($ref, '/admin/') !== false) {
            $backUrl = basename($ref);
        }
    }
    ?>
    <a href="<?php echo htmlspecialchars($backUrl); ?>">← Back</a>
    <div style="max-width:980px;margin-top:14px;background:#fff;padding:18px;border-radius:10px;box-shadow:0 8px 26px rgba(0,0,0,0.06)">
        <?php if (!$order): ?>
            <h2>Order not found</h2>
            <p>No order matches that ID.</p>
        <?php else: ?>
            <h2>Order <?php echo htmlspecialchars($order['order_number'] ?? $order['id']); ?></h2>
            <p style="color:#666">Customer: <?php echo htmlspecialchars($order['customer_name'] ?? ''); ?> · Date: <?php echo htmlspecialchars($order['created_at'] ?? ''); ?></p>
            <h3>Items</h3>
            <table style="width:100%;border-collapse:collapse">
                <thead><tr style="text-align:left"><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
                <tbody>
                <?php
                    $calculated_total = 0.0;
                    foreach ($items as $it):
                        $productName = $it['product_name'] ?? $it['product_id'] ?? '';
                        $qty = (int)($it['quantity'] ?? 0);
                        // Support legacy column `price` but prefer `unit_price` and `subtotal`
                        $unit_price = isset($it['unit_price']) ? (float)$it['unit_price'] : (isset($it['price']) ? (float)$it['price'] : 0.0);
                        $item_subtotal = isset($it['subtotal']) ? (float)$it['subtotal'] : ($unit_price * $qty);
                        $calculated_total += $item_subtotal;
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($productName); ?></td>
                        <td><?php echo $qty; ?></td>
                        <td><?php echo function_exists('format_price') ? format_price($unit_price) : number_format($unit_price,2); ?></td>
                        <td><?php echo function_exists('format_price') ? format_price($item_subtotal) : number_format($item_subtotal,2); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div style="margin-top:12px;text-align:right;font-weight:800">Total: <?php echo function_exists('format_price') ? format_price($calculated_total) : number_format($calculated_total,2); ?></div>
            <?php if (isset($order['total']) && (float)$order['total'] !== (float)$calculated_total): ?>
                <div style="margin-top:6px;text-align:right;color:#777;font-weight:700">Recorded total: <?php echo function_exists('format_price') ? format_price($order['total']) : number_format($order['total'],2); ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
