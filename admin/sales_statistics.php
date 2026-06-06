<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
// NOTE: Authentication check removed to allow direct access for configuration.
// If you want to re-enable protection, restore the is_admin_logged_in() redirect.
$db = Database::getInstance()->getConnection();

$stats_series = [];
try {
    $st = $db->query("SELECT DATE(created_at) as day, COUNT(*) as orders, SUM(total) as revenue FROM orders GROUP BY DATE(created_at) ORDER BY day DESC LIMIT 14");
    $stats_series = $st->fetchAll();
} catch (Throwable $e) { $stats_series = []; }

// Top products by revenue (sum of subtotals)
$top_products = [];
try {
    $q = "SELECT oi.product_id, COALESCE(oi.product_name, p.name) as product_name, SUM(oi.subtotal) as revenue
            FROM order_items oi
            LEFT JOIN products p ON p.id = oi.product_id
            GROUP BY oi.product_id
            ORDER BY revenue DESC
            LIMIT 10";
    $tp = $db->query($q);
    if ($tp) $top_products = $tp->fetchAll();
} catch (Throwable $e) { $top_products = []; }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Sales Statistics - <?php echo SITE_NAME; ?></title>
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
            <li><a href="sales_history.php"><i class="fas fa-history"></i> Sales History</a></li>
            <li><a href="order_checker.php"><i class="fas fa-check-circle"></i> Order Checker</a></li>
            <li><a href="sales_statistics.php" class="active"><i class="fas fa-chart-line"></i> Sales Statistics</a></li>
        </ul>
        <div class="sidebar-bottom"><a href="logout.php" class="btn ghost" style="width:100%;justify-content:center"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
    </aside>

    <main class="admin-main">
        <div class="content-card">
            <h2>Sales Statistics</h2>
            <div style="display:flex;gap:12px;flex-wrap:wrap">
                <?php foreach ($stats_series as $s): 
                    // format the stored DATE string into a human-friendly label
                    $label = isset($s['day']) ? date('M j, Y', strtotime($s['day'])) : '';
                ?>
                    <div style="background:#fff;padding:10px;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,0.04);min-width:140px">
                        <div style="font-size:13px;color:#64748b"><?php echo htmlspecialchars($label); ?></div>
                        <div style="font-weight:800;font-size:18px"><?php echo function_exists('format_price') ? format_price($s['revenue']) : number_format($s['revenue'],2); ?></div>
                        <div style="color:#94a3b8;font-size:12px"><?php echo (int)$s['orders']; ?> orders</div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Top-selling products bar chart -->
            <div style="margin-top:18px">
                <h3 style="margin:0 0 12px 0">Top Selling Products (by quantity)</h3>
                <canvas id="topProductsChart" width="800" height="360"></canvas>
            </div>
        </div>
    </main>
</div>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function(){
        const labels = <?php echo json_encode(array_map(function($r){ return $r['product_name']; }, $top_products)); ?>;
        const data = <?php echo json_encode(array_map(function($r){ return (float)$r['revenue']; }, $top_products)); ?>;

        const ctx = document.getElementById('topProductsChart');
        if (ctx && labels.length) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenue',
                        data: data,
                        backgroundColor: 'rgba(212,175,55,0.95)',
                        borderColor: 'rgba(184,134,11,0.95)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: function(value){ return '₱' + value.toLocaleString(); } } }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: function(ctx){ return 'Revenue: ₱' + Number(ctx.parsed.y || ctx.parsed).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}); } } }
                    }
                }
            });
        } else if (ctx) {
            ctx.parentNode.insertAdjacentHTML('beforeend', '<div style="color:var(--muted);margin-top:8px">No product sales data available.</div>');
        }
    })();
</script>
</body>
</html>
