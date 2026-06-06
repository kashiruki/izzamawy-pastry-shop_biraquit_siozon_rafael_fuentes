<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// NOTE: Authentication check removed to allow direct access for configuration.
// If you want to re-enable protection, restore the is_admin_logged_in() redirect.
$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'subscribe_restock') {
    $pid = (int)($_POST['product_id'] ?? 0);
    $email = trim($_POST['email'] ?? '');
    if ($pid && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $ins = $db->prepare('INSERT INTO restock_notifications (product_id, email) VALUES (:pid, :email)');
        $ins->execute([':pid'=>$pid, ':email'=>$email]);
    }
    redirect(SITE_URL . '/admin/stock_monitoring.php');
}

$product_stocks = [];
try {
    $stmt = $db->query("SELECT p.id, p.name, p.price, SUM(ps.stock_quantity) AS total_stock FROM product_stock ps JOIN products p ON ps.product_id = p.id GROUP BY p.id, p.name, p.price ORDER BY p.name ASC");
    $product_stocks = $stmt->fetchAll();
} catch (Throwable $e) { $product_stocks = []; }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Stock Monitoring - <?php echo SITE_NAME; ?></title>
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
        .stock-label{display:inline-block;margin-left:8px;padding:4px 8px;border-radius:12px;font-size:12px;font-weight:700}
        .stock-label.low{background:#fff4e5;color:#b86b00;border:1px solid #f0d7b0}
        .stock-label.ok{background:#eefaf3;color:#1f7a3a;border:1px solid #cfead7}
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
            <li><a href="stock_monitoring.php" class="active"><i class="fas fa-eye"></i> Stock Monitoring</a></li>
            <li><a href="sales_history.php"><i class="fas fa-history"></i> Sales History</a></li>
            <li><a href="order_checker.php"><i class="fas fa-check-circle"></i> Order Checker</a></li>
            <li><a href="sales_statistics.php"><i class="fas fa-chart-line"></i> Sales Statistics</a></li>
        </ul>
        <div class="sidebar-bottom"><a href="logout.php" class="btn ghost" style="width:100%;justify-content:center"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
    </aside>

    <main class="admin-main">
        <div class="content-card">
            <h2>Stock Monitoring (By Product)</h2>
            <?php if (empty($product_stocks)): ?>
                <p>No product stock records found.</p>
            <?php else: ?>
                <table class="table">
                    <thead><tr><th>Product</th><th>Total Stock</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($product_stocks as $item): ?>
                        <?php $total = (int)($item['total_stock'] ?? 0); $isLow = $total <= 30; ?>
                        <tr data-id="<?php echo (int)$item['id']; ?>">
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td class="stock-cell">
                                <?php echo $total; ?> units
                                        <?php if ($isLow): ?>
                                            <span class="stock-label low">Low Stock</span>
                                        <?php else: ?>
                                            <span class="stock-label ok">OK</span>
                                        <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($isLow): ?>
                                    <button class="btn-edit-restock" data-id="<?php echo (int)$item['id']; ?>">Edit</button>
                                <?php else: ?>
                                    <button class="btn-edit-restock" data-id="<?php echo (int)$item['id']; ?>">Adjust</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <script>
            // notify other tabs/windows when stock is updated via BroadcastChannel or localStorage fallback
            function notifyRestockRefresh(){
                try {
                    if (window.BroadcastChannel) {
                        var bc = new BroadcastChannel('izzamawy_restock');
                        bc.postMessage('refresh');
                        bc.close();
                        return;
                    }
                } catch(e){}
                // fallback
                try { localStorage.setItem('izzamawy_restock_refresh', Date.now()); } catch(e){}
            }

            // extend existing save handler by listening to successful updates via DOM changes
            document.addEventListener('click', function(e){
                if (e.target && e.target.classList.contains('btn-save-restock')) {
                    // after a short delay allow the AJAX in-page handler to complete, then notify
                    setTimeout(function(){ notifyRestockRefresh(); }, 400);
                }
            });
        </script>
        <script>
            // Edit restock inline: input new total stock, save via AJAX to update_stock.php
            document.addEventListener('click', function(e){
                if (e.target && e.target.classList.contains('btn-edit-restock')) {
                    var id = e.target.getAttribute('data-id');
                    var row = document.querySelector('tr[data-id="'+id+'"]'); if (!row) return;
                    var cell = row.querySelector('.stock-cell'); if (!cell) return;
                    if (cell.querySelector('.restock-input')) return; // already editing
                    var orig = cell.textContent.trim(); row.dataset._orig = orig;
                    var current = orig.replace(/[^0-9]/g,'');
                    cell.innerHTML = '<input class="restock-input" type="number" min="0" value="'+current+'" style="width:100px;padding:6px;border-radius:6px;border:1px solid #ddd" /> <button class="btn-save-restock">Save</button> <button class="btn-cancel-restock">Cancel</button>';
                    var input = cell.querySelector('.restock-input'); if (input) input.focus();
                    return;
                }

                if (e.target && e.target.classList.contains('btn-cancel-restock')) {
                    var row = e.target.closest('tr'); if (!row) return; var cell = row.querySelector('.stock-cell'); if (!cell) return;
                    cell.textContent = row.dataset._orig || '';
                    delete row.dataset._orig;
                    return;
                }

                if (e.target && e.target.classList.contains('btn-save-restock')) {
                    var row = e.target.closest('tr'); if (!row) return; var id = row.getAttribute('data-id'); var cell = row.querySelector('.stock-cell'); if (!cell) return;
                    var input = cell.querySelector('.restock-input'); if (!input) return; var val = input.value.trim();
                    if (val === '' || isNaN(val) || parseInt(val) < 0) { alert('Invalid quantity'); input.focus(); return; }
                    var saveBtn = cell.querySelector('.btn-save-restock'); saveBtn.disabled = true;
                    fetch('update_stock.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ product_id: id, total_stock: parseInt(val) }) })
                    .then(function(r){ return r.json(); }).then(function(resp){
                        if (resp && resp.success) {
                            // update UI: show new total and label
                            cell.textContent = resp.total_stock + ' units';
                            var span = document.createElement('span');
                            if (resp.is_low) { span.className='stock-label low'; span.textContent='Low Stock'; }
                            else { span.className='stock-label ok'; span.textContent='OK'; }
                            cell.appendChild(span);
                        } else {
                            alert('Update failed: '+(resp && resp.error?resp.error:'unknown'));
                            cell.textContent = row.dataset._orig || '';
                        }
                        delete row.dataset._orig;
                    }).catch(function(){ alert('Request failed'); cell.textContent = row.dataset._orig || ''; delete row.dataset._orig; });
                }
            });
        </script>
    </main>
</div>
</body>
</html>
