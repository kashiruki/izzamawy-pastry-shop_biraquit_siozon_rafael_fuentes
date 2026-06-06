<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
// NOTE: Authentication check removed to allow direct access for configuration.
// If you want to re-enable protection, restore the is_admin_logged_in() redirect.
$db = Database::getInstance()->getConnection();

$sales = [];
try { $st = $db->query("SELECT id, order_number, customer_name, total, created_at FROM orders ORDER BY created_at DESC LIMIT 200"); $sales = $st->fetchAll(); } catch (Throwable $e) { $sales = []; }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Sales History - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--gold-900:#b8860b;--gold-700:#d4af37;--bg:#f8f7f5;--card-bg:#fff;--muted:#6b6b6b;--accent:#6a28b8}
        body{background:var(--bg);font-family:Segoe UI, Tahoma, sans-serif}
        .admin-layout{display:grid;grid-template-columns:260px 1fr;min-height:100vh}
        .admin-sidebar{background:linear-gradient(180deg,var(--gold-700) 0%,var(--gold-900) 100%);color:#fff;padding:22px;display:flex;flex-direction:column;gap:18px}
        .brand { display:flex; align-items:center; gap:10px; }
        .brand h2{color:#fff; font-size:14px; line-height:1.1; font-weight:800}
        .brand img{width:28px;height:28px;border-radius:6px;object-fit:cover;display:block}
        .profile .avatar { width:44px; height:44px; background:#fff3; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:18px; }
        .admin-menu a{color:#fff;display:block;padding:10px 12px;border-radius:8px;text-decoration:none;font-weight:600}
        .admin-menu a.active,.admin-menu a:hover{background:linear-gradient(90deg,var(--gold-900),var(--gold-700));color:#fff}
        .admin-main{padding:24px 30px}
        .profile .meta{display:flex;flex-direction:column;justify-content:center;font-size:14px;color:#fff}
        .profile .meta > div:first-child { font-weight:700; }
        .sidebar-bottom .btn.ghost{color:#fff}
        .content-card{background:var(--card-bg);border-radius:12px;padding:18px;box-shadow:0 6px 20px rgba(16,24,40,0.04)}
        table.table{width:100%;border-collapse:collapse}
        table.table th,table.table td{padding:12px 10px;border-bottom:1px solid #f0eeeb}
    </style>
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="brand"><img src="../images/logo.png" alt="<?php echo SITE_NAME; ?> logo" /><h2><?php echo SITE_NAME; ?></h2></div>
        <div class="profile">
            <div class="meta"><div style="font-weight:700"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator'); ?></div><div style="font-size:12px;opacity:0.9">System Administrator</div></div>
        </div>
        <ul class="admin-menu">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="real_time_inventory.php"><i class="fas fa-bolt"></i> Real-Time Inventory</a></li>
            <li><a href="stock_monitoring.php"><i class="fas fa-eye"></i> Stock Monitoring</a></li>
            <!-- Bulk Transfer removed -->
            <li><a href="sales_history.php" class="active"><i class="fas fa-history"></i> Sales History</a></li>
            <li><a href="order_checker.php"><i class="fas fa-check-circle"></i> Order Checker</a></li>
            <li><a href="sales_statistics.php"><i class="fas fa-chart-line"></i> Sales Statistics</a></li>
        </ul>
        <div class="sidebar-bottom"><a href="logout.php" class="btn ghost" style="width:100%;justify-content:center"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
    </aside>

    <main class="admin-main">
        <div class="content-card">
            <h2>Sales History</h2>
            <table class="table">
                <thead><tr><th>Order #</th><th>Customer</th><th>Date</th><th>Total</th></tr></thead>
                <tbody>
                <?php foreach ($sales as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['order_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                        <td><?php echo format_price($row['total']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>
