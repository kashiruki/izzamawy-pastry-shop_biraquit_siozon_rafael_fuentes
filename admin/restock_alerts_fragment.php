<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');
$db = Database::getInstance()->getConnection();
$need_restock = [];
try {
    $r = $db->query("SELECT p.id AS product_id, p.name, SUM(ps.stock_quantity) AS total_stock FROM product_stock ps JOIN products p ON p.id = ps.product_id GROUP BY p.id HAVING total_stock <= 30 ORDER BY total_stock ASC");
    if ($r) $need_restock = $r->fetchAll();
} catch (Throwable $e) { $need_restock = []; }

$count = count($need_restock);
// Build alerts HTML (same markup used in dashboard)
if ($count === 0) {
    $alerts_html = '';
    $list_html = '<div style="color:var(--muted)">No low-stock items.</div>';
} else {
    ob_start();
    ?>
    <div style="margin-bottom:18px">
        <div class="content-card" style="border-left:4px solid var(--gold-900);">
            <h3 style="margin:0 0 8px 0;color:var(--gold-900)">Restock Alerts</h3>
            <div style="font-size:14px;color:var(--muted)">The following products have low stock (≤ 30) and should be restocked:</div>
            <ul style="margin:8px 0 0 0;padding:0 0 0 16px">
                <?php foreach($need_restock as $nr): ?>
                    <li style="margin:6px 0">
                        <strong><?php echo htmlspecialchars($nr['name']); ?></strong> — <?php echo (int)$nr['total_stock']; ?> units
                        <a href="http://localhost/izzamawy-pastry-shop/admin/stock_monitoring.php" style="margin-left:12px;color:var(--gold-900);text-decoration:underline">Open Inventory</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php
    $alerts_html = ob_get_clean();

    // build side list
    ob_start();
    ?>
    <ul style="margin:0;padding:0 0 0 16px">
        <?php foreach($need_restock as $p): ?>
            <li><?php echo htmlspecialchars($p['name'] ?? 'Unnamed'); ?> — <?php echo (int)$p['total_stock']; ?> left</li>
        <?php endforeach; ?>
    </ul>
    <?php
    $list_html = ob_get_clean();
}

echo json_encode(['count' => $count, 'alerts_html' => $alerts_html, 'list_html' => $list_html]);
