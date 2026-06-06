<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
if (!is_admin_logged_in()) redirect('login.php');
$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_transfer') {
    $ids = $_POST['product_ids'] ?? [];
    $qtys = $_POST['qtys'] ?? [];
    foreach ($ids as $i => $id) {
        $pid = (int)$id;
        $q = (int)($qtys[$i] ?? 0);
        if ($pid && $q > 0) {
            $upd = $db->prepare('UPDATE products SET stock_quantity = GREATEST(stock_quantity - :q, 0) WHERE id = :id');
            $upd->execute([':q'=>$q, ':id'=>$pid]);
            $ins = $db->prepare('INSERT INTO stock_transfers (product_id, qty, note) VALUES (:pid, :qty, :note)');
            $ins->execute([':pid'=>$pid, ':qty'=>$q, ':note'=>'Bulk transfer via admin']);
                // sync product_stock
                try {
                    $sync = $db->prepare('UPDATE product_stock ps JOIN products p ON p.id = ps.product_id SET ps.stock_quantity = p.stock_quantity, ps.restock_required = (p.stock_quantity < ps.restock_threshold), ps.last_checked = NOW() WHERE p.id = :id');
                    $sync->execute([':id' => $pid]);
                    if ($sync->rowCount() === 0) {
                        $ins2 = $db->prepare('INSERT INTO product_stock (product_id, stock_quantity, restock_threshold, restock_required, created_at) SELECT p.id, p.stock_quantity, 10, (p.stock_quantity < 10), NOW() FROM products p WHERE p.id = :id AND NOT EXISTS (SELECT 1 FROM product_stock ps WHERE ps.product_id = p.id)');
                        $ins2->execute([':id' => $pid]);
                    }
                } catch (Throwable $e) { /* ignore sync errors */ }
        }
    }
    redirect('bulk_transfer.php');
}

$products = [];
try { $pstmt = $db->query("SELECT id, name, stock_quantity FROM products WHERE is_active = 1 ORDER BY name ASC LIMIT 500"); $products = $pstmt->fetchAll(); } catch (Throwable $e) { $products = []; }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Bulk Transfer - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--gold-900:#b8860b;--gold-700:#d4af37;--bg:#f8f7f5;--card-bg:#fff;--muted:#6b6b6b;--accent:#6a28b8}
        body{background:var(--bg);font-family:Segoe UI, Tahoma, sans-serif}
        .admin-layout{display:grid;grid-template-columns:260px 1fr;min-height:100vh}
        .admin-sidebar{background:linear-gradient(180deg,var(--accent) 0%,#8338ec 100%);color:#fff;padding:22px;display:flex;flex-direction:column;gap:18px}
        .brand h2{color:var(--gold-700)}
        .admin-menu a{color:var(--gold-700);display:block;padding:10px 12px;border-radius:8px;text-decoration:none;font-weight:600}
        .admin-menu a.active,.admin-menu a:hover{background:linear-gradient(90deg,var(--gold-900),var(--gold-700));color:#fff}
        .admin-main{padding:24px 30px}
        .content-card{background:var(--card-bg);border-radius:12px;padding:18px;box-shadow:0 6px 20px rgba(16,24,40,0.04)}
    </style>
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="brand"><h2><?php echo SITE_NAME; ?></h2></div>
        <div class="profile"><div class="avatar"><?php echo strtoupper(substr($_SESSION['admin_username'] ?? 'A',0,1)); ?></div>
            <div class="meta"><div style="font-weight:700"><?php echo $_SESSION['admin_name']; ?></div><div style="font-size:12px;opacity:0.9">System Administrator</div></div></div>
        <ul class="admin-menu">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="real_time_inventory.php"><i class="fas fa-bolt"></i> Real-Time Inventory</a></li>
            <li><a href="stock_monitoring.php"><i class="fas fa-eye"></i> Stock Monitoring</a></li>
            <li><a href="bulk_transfer.php" class="active"><i class="fas fa-exchange-alt"></i> Bulk Transfer</a></li>
            <li><a href="sales_history.php"><i class="fas fa-history"></i> Sales History</a></li>
            <li><a href="order_checker.php"><i class="fas fa-check-circle"></i> Order Checker</a></li>
            <li><a href="sales_statistics.php"><i class="fas fa-chart-line"></i> Sales Statistics</a></li>
        </ul>
        <div class="sidebar-bottom"><a href="logout.php" class="btn ghost" style="width:100%;justify-content:center"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
    </aside>

    <main class="admin-main">
        <div class="content-card">
            <h2>Bulk Transfer</h2>
            <form method="POST">
                <input type="hidden" name="action" value="bulk_transfer">
                <p>Select multiple products and quantities to transfer (this will deduct stock):</p>
                <div style="max-height:320px;overflow:auto;border:1px solid #f0eeeb;padding:8px;border-radius:8px">
                    <?php foreach ($products as $prod): ?>
                        <div style="display:flex;gap:8px;align-items:center;margin-bottom:6px">
                            <label style="width:280px"><?php echo htmlspecialchars($prod['name']); ?> (<?php echo (int)$prod['stock_quantity']; ?>)</label>
                            <input type="hidden" name="product_ids[]" value="<?php echo (int)$prod['id']; ?>">
                            <input type="number" name="qtys[]" min="0" value="0" style="width:100px;padding:6px;border-radius:6px">
                        </div>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top:8px"><button class="btn" type="submit">Perform Bulk Transfer</button></div>
            </form>
        </div>
    </main>
</div>
</body>
</html>
