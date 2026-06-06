<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// NOTE: Authentication check removed to allow direct access for configuration.
// If you later want to protect the dashboard, restore this block and require login.

$db = Database::getInstance()->getConnection();

// Gather statistics safely
$stats = ['products'=>0,'orders'=>0,'pending_orders'=>0,'revenue'=>0];
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM products WHERE is_active = 1");
    $row = $stmt->fetch(); $stats['products'] = $row['total'] ?? 0;
} catch (Throwable $e) { $stats['products'] = 0; }

try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM orders");
    $row = $stmt->fetch(); $stats['orders'] = $row['total'] ?? 0;
} catch (Throwable $e) { $stats['orders'] = 0; }

try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM orders WHERE order_status = 'pending'");
    $row = $stmt->fetch(); $stats['pending_orders'] = $row['total'] ?? 0;
} catch (Throwable $e) { $stats['pending_orders'] = 0; }

try {
    // Revenue for the current month
    $stmt = $db->query("SELECT SUM(total) as revenue FROM orders WHERE payment_status != 'failed' AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())");
    $row = $stmt->fetch(); $stats['revenue'] = $row['revenue'] ?? 0;
} catch (Throwable $e) { $stats['revenue'] = 0; }

$recent_orders = [];
try {
    $stmt = $db->query("SELECT id, customer_name, total, order_status, created_at FROM orders ORDER BY created_at DESC LIMIT 10");
    $recent_orders = $stmt->fetchAll();
} catch (Throwable $e) { $recent_orders = []; }

$low_stock = [];
try {
    $stmt = $db->query("SELECT id, name, stock_quantity FROM products WHERE stock_quantity < 10 AND is_active = 1 ORDER BY stock_quantity ASC LIMIT 10");
    $low_stock = $stmt->fetchAll();
} catch (Throwable $e) { $low_stock = []; }

// Restock alerts: aggregate total stock per product from product_stock and show those <= 30
$need_restock = [];
try {
    $r = $db->query("SELECT p.id AS product_id, p.name, SUM(ps.stock_quantity) AS total_stock FROM product_stock ps JOIN products p ON p.id = ps.product_id GROUP BY p.id HAVING total_stock <= 30 ORDER BY total_stock ASC");
    if ($r) $need_restock = $r->fetchAll();
} catch (Throwable $e) { $need_restock = []; }

// Label for current month (e.g., "January 2026")
$current_month_label = date('F Y');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{
            --gold-700:#d4af37; --gold-900:#b8860b; --bg:#f8f7f5; --card:#fff; --muted:#6b6b6b;
        }
        body{background:var(--bg);font-family:Segoe UI, Tahoma, sans-serif;margin:0}
        .admin-layout{display:grid;grid-template-columns:260px 1fr;min-height:100vh}
        .admin-sidebar{background:linear-gradient(180deg,var(--gold-700),var(--gold-900));color:#fff;padding:22px;display:flex;flex-direction:column}
        .brand{display:flex;align-items:center;gap:10px}
        .brand img{width:34px;height:34px;border-radius:6px}
        .admin-menu{list-style:none;padding:0;margin-top:10px}
        .admin-menu a{display:block;color:#fff;padding:10px 12px;border-radius:8px;text-decoration:none;font-weight:600}
        .admin-main{padding:24px 30px}
        .topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
        .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:22px}
        .stat-card{background:var(--card);border-radius:12px;padding:18px;box-shadow:0 6px 20px rgba(16,24,40,0.06);display:flex;align-items:center;justify-content:space-between}
        .content-card{background:var(--card);border-radius:12px;padding:18px;box-shadow:0 6px 20px rgba(16,24,40,0.04)}
        table.table{width:100%;border-collapse:collapse}
        table.table th, table.table td{padding:10px;border-bottom:1px solid #f0eeeb;text-align:left}
        /* Quick Links metallic gold highlight */
        .quick-links a { display:block; padding:10px 12px; border-radius:8px; text-decoration:none; color:var(--muted); font-weight:700; transition: background .18s ease, color .18s ease; }
        .quick-links a:hover, .quick-links a:focus, .quick-links a.active { background: linear-gradient(90deg,var(--gold-900),var(--gold-700)); color:#fff; box-shadow: 0 6px 18px rgba(0,0,0,0.06); outline: none; }
        @media (max-width:900px){.admin-layout{grid-template-columns:1fr}.stats-grid{grid-template-columns:repeat(2,1fr)}}
        /* Toast */
        .toast{position:fixed;right:18px;bottom:18px;background:#222;color:#fff;padding:12px 16px;border-radius:8px;box-shadow:0 8px 30px rgba(0,0,0,0.2);opacity:0;transform:translateY(8px);transition:opacity .24s ease,transform .24s ease;z-index:9999}
        .toast.show{opacity:1;transform:translateY(0)}
        /* Logout modal styles */
        #logoutModal { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.45); opacity: 0; pointer-events: none; transition: opacity .18s ease; z-index: 10010; }
        #logoutModal.show { opacity: 1; pointer-events: auto; }
        .logout-modal-dialog { background: #fff; border-radius: 10px; padding: 18px; max-width: 420px; width: calc(100% - 40px); box-shadow: 0 12px 40px rgba(0,0,0,0.18); }
        .logout-modal-dialog h3 { margin: 0 0 8px 0; font-size: 18px; }
        .logout-modal-actions { display:flex;gap:10px;justify-content:flex-end;margin-top:14px }
    </style>
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="brand">
                <img src="../images/logo.png" alt="<?php echo SITE_NAME; ?> logo">
                <div style="font-weight:800;font-size:16px"><?php echo SITE_NAME; ?></div>
            </div>

            <div style="margin-top:18px">
                <div style="font-size:13px;font-weight:700"><?php echo $_SESSION['admin_name'] ?? 'Administrator'; ?></div>
                <div style="font-size:12px;opacity:0.9">System Administrator</div>
            </div>

            <ul class="admin-menu">
                <li><a href="real_time_inventory.php"><i class="fas fa-bolt"></i> Real-Time Inventory</a></li>
                <li><a href="stock_monitoring.php"><i class="fas fa-eye"></i> Stock Monitoring</a></li>
                <li><a href="sales_history.php"><i class="fas fa-history"></i> Sales History</a></li>
                <li><a href="order_checker.php"><i class="fas fa-check-circle"></i> Order Checker</a></li>
                <li><a href="sales_statistics.php"><i class="fas fa-chart-line"></i> Sales Statistics</a></li>
            </ul>

            <div style="margin-top:auto">
                <a href="logout.php" id="admin-logout" data-redirect="http://localhost/izzamawy-pastry-shop/login_simple.php" style="display:block;padding:10px;border-radius:8px;background:transparent;color:#fff;text-align:center;text-decoration:none">Logout</a>
            </div>
        </aside>

        <main class="admin-main">
            <div class="topbar">
                <div>
                    <h1 style="margin:0;font-size:22px;color:#222">Dashboard</h1>
                    <div style="font-size:13px;color:var(--muted)">Overview of recent activity</div>
                </div>
                <div style="font-size:13px;color:var(--muted)">Welcome back, <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></div>
            </div>

            <div id="restock-alerts-container">
            <?php if (!empty($need_restock)): ?>
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
            <?php endif; ?>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div>
                        <div style="font-size:13px;color:var(--muted)">Total Product Varieties</div>
                        <div style="font-size:20px;font-weight:800;color:var(--gold-900)"><?php echo (int)$stats['products']; ?></div>
                    </div>
                    <i class="fas fa-boxes" style="font-size:28px;color:rgba(0,0,0,0.08)"></i>
                </div>

                <div class="stat-card">
                    <div>
                        <div style="font-size:13px;color:var(--muted)">Pending Orders</div>
                        <div style="font-size:20px;font-weight:800;color:var(--gold-900)"><?php echo (int)$stats['pending_orders']; ?></div>
                    </div>
                    <i class="fas fa-hourglass-half" style="font-size:28px;color:rgba(0,0,0,0.08)"></i>
                </div>

                <div class="stat-card">
                    <div>
                            <div style="font-size:13px;color:var(--muted)">Revenue
                                <div style="font-size:11px;color:var(--muted);font-weight:700;margin-top:4px"><?php echo htmlspecialchars($current_month_label); ?></div>
                            </div>
                            <div style="font-size:20px;font-weight:800;color:var(--gold-900)"><?php echo function_exists('format_price') ? format_price($stats['revenue']) : number_format($stats['revenue'],2); ?></div>
                        </div>
                    <i class="fas fa-wallet" style="font-size:28px;color:rgba(0,0,0,0.08)"></i>
                </div>

                <div class="stat-card">
                    <div>
                        <div style="font-size:13px;color:var(--muted)">Low Stock Items</div>
                        <div id="low-stock-count" style="font-size:20px;font-weight:800;color:var(--gold-900)"><?php echo (int)count($need_restock); ?></div>
                    </div>
                    <i class="fas fa-exclamation-triangle" style="font-size:28px;color:rgba(0,0,0,0.08)"></i>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 420px;gap:16px">
                <div class="content-card">
                    <h2 style="margin-top:0">Recent Orders</h2>
                    <table class="table">
                        <thead>
                            <tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr>
                        </thead>
                        <tbody>
                        <?php if(empty($recent_orders)): ?>
                            <tr><td colspan="5" style="color:var(--muted)">No recent orders</td></tr>
                        <?php else: foreach($recent_orders as $o): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($o['id']); ?></td>
                                <td><?php echo htmlspecialchars($o['customer_name'] ?? '—'); ?></td>
                                <td><?php echo function_exists('format_price') ? format_price($o['total']) : number_format($o['total'],2); ?></td>
                                <td><?php echo htmlspecialchars($o['order_status'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($o['created_at'] ?? '—'); ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <div>
                    <div class="content-card" style="margin-bottom:16px">
                        <h3 style="margin-top:0">Low Stock</h3>
                        <?php if(empty($need_restock)): ?>
                            <div id="low-stock-list" style="color:var(--muted)">No low-stock items.</div>
                        <?php else: ?>
                            <div id="low-stock-list"><ul style="margin:0;padding:0 0 0 16px">
                                <?php foreach($need_restock as $p): ?>
                                    <li><?php echo htmlspecialchars($p['name'] ?? 'Unnamed'); ?> — <?php echo (int)$p['total_stock']; ?> left</li>
                                <?php endforeach; ?>
                            </ul></div>
                        <?php endif; ?>
                    </div>

                    <div class="content-card">
                        <h3 style="margin-top:0">Quick Links</h3>
                        <div class="quick-links" style="display:flex;flex-direction:column;gap:8px">
                            <a href="real_time_inventory.php">Real-Time Inventory</a>
                            <a href="stock_monitoring.php">Stock Monitoring</a>
                            <a href="sales_history.php">Sales History</a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <!-- Logout confirmation modal -->
    <div id="logoutModal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="logoutModalTitle">
        <div class="logout-modal-dialog" role="document">
            <h3 id="logoutModalTitle">Confirm logout</h3>
            <p>Are you sure you want to log out?</p>
            <div class="logout-modal-actions">
                <button type="button" id="logoutCancelBtn" class="btn">Cancel</button>
                <button type="button" id="logoutConfirmBtn" class="btn btn-primary" data-href="">Log out</button>
            </div>
        </div>
    </div>
    <script>
        // Listen for restock notifications and refresh alerts via AJAX
        (function(){
            var previousCount = parseInt((document.getElementById('low-stock-count')||{textContent:'0'}).textContent||'0',10);

            function showToast(msg, url){
                try{
                    var t = document.createElement('div'); t.className = 'toast';
                    var textNode = document.createTextNode(msg);
                    t.appendChild(textNode);
                    if (url) {
                        var a = document.createElement('a');
                        a.href = url;
                        a.textContent = 'View inventory';
                        a.style.cssText = 'color:#ffd966;margin-left:10px;text-decoration:underline';
                        a.target = '_blank';
                        t.appendChild(a);
                    }
                    document.body.appendChild(t);
                    // force reflow
                    void t.offsetWidth; t.classList.add('show');
                    setTimeout(function(){ t.classList.remove('show'); setTimeout(function(){ try{ document.body.removeChild(t); }catch(e){} },240); }, 3200);
                }catch(e){}
            }

            function refreshRestockFragments(){
                fetch('restock_alerts_fragment.php').then(function(r){ return r.json(); }).then(function(data){
                    if (!data) return;
                    var container = document.getElementById('restock-alerts-container');
                    if (container) container.innerHTML = data.alerts_html || '';
                    var countNode = document.getElementById('low-stock-count');
                    var newCount = parseInt(data.count || 0,10);
                    if (countNode && newCount !== previousCount) {
                        var inventoryUrl = 'http://localhost/izzamawy-pastry-shop/admin/stock_monitoring.php';
                        if (newCount === 0) showToast('All low-stock items have been restocked', inventoryUrl);
                        else showToast('Restock alerts updated — ' + newCount + ' low-stock item' + (newCount!==1?'s':''), inventoryUrl);
                        countNode.textContent = newCount;
                    } else if (countNode) {
                        countNode.textContent = newCount;
                    }
                    previousCount = newCount;
                    var listNode = document.getElementById('low-stock-list');
                    if (listNode) listNode.innerHTML = data.list_html || '<div style="color:var(--muted)">No low-stock items.</div>';
                }).catch(function(){ /* ignore errors */ });
            }

            // expose for interval/poll
            window.refreshRestockFragments = refreshRestockFragments;
            // initial previousCount already set
        })();

        // Listen via BroadcastChannel or storage events
        try {
            if (window.BroadcastChannel) {
                var bc = new BroadcastChannel('izzamawy_restock');
                bc.onmessage = function(m){ if (m && m.data === 'refresh') refreshRestockFragments(); };
            }
        } catch(e){}
        window.addEventListener('storage', function(e){ if (e.key === 'izzamawy_restock_refresh') refreshRestockFragments(); });

        // Optionally poll periodically (every 60s) as backup
        setInterval(refreshRestockFragments, 60000);

        // Logout confirmation modal handler
        (function(){
            var logoutBtn = document.getElementById('admin-logout');
            var modal = document.getElementById('logoutModal');
            var btnCancel = document.getElementById('logoutCancelBtn');
            var btnConfirm = document.getElementById('logoutConfirmBtn');
            if (!logoutBtn || !modal || !btnCancel || !btnConfirm) return;

            function openModal(href){
                btnConfirm.setAttribute('data-href', href || logoutBtn.getAttribute('href') || 'logout.php');
                modal.classList.add('show');
                modal.setAttribute('aria-hidden','false');
                // focus the confirm button for quick keyboard action
                btnConfirm.focus();
            }

            function closeModal(){
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden','true');
                logoutBtn.focus();
            }

            logoutBtn.addEventListener('click', function(e){
                e.preventDefault();
                openModal();
            }, false);

            btnCancel.addEventListener('click', function(){ closeModal(); }, false);
            btnConfirm.addEventListener('click', function(){
                var href = this.getAttribute('data-href') || logoutBtn.getAttribute('href') || 'logout.php';
                window.location.href = href;
            }, false);

            // Close when clicking backdrop
            modal.addEventListener('click', function(e){ if (e.target === modal) closeModal(); });

            // Close on ESC
            document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && modal.classList.contains('show')) closeModal(); });
        })();
    </script>
</body>
</html>
