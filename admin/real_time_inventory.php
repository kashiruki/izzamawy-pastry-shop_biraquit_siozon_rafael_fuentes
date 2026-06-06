<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// NOTE: Authentication check removed to allow direct access for configuration.
// If you want to re-enable protection, restore the is_admin_logged_in() redirect.

$db = Database::getInstance()->getConnection();

// Handle simple actions (update stock, subscribe)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'update_stock') {
        $pid = (int)($_POST['product_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 0);
        if ($pid >= 0) {
            $upd = $db->prepare('UPDATE products SET stock_quantity = :qty WHERE id = :id');
            $upd->execute([':qty' => $qty, ':id' => $pid]);
            // sync product_stock
            try {
                $sync = $db->prepare('UPDATE product_stock ps JOIN products p ON p.id = ps.product_id SET ps.stock_quantity = p.stock_quantity, ps.restock_required = (p.stock_quantity < ps.restock_threshold), ps.last_checked = NOW() WHERE p.id = :id');
                $sync->execute([':id' => $pid]);
                if ($sync->rowCount() === 0) {
                    $ins = $db->prepare('INSERT INTO product_stock (product_id, stock_quantity, restock_threshold, restock_required, created_at) SELECT p.id, p.stock_quantity, 10, (p.stock_quantity < 10), NOW() FROM products p WHERE p.id = :id AND NOT EXISTS (SELECT 1 FROM product_stock ps WHERE ps.product_id = p.id)');
                    $ins->execute([':id' => $pid]);
                }
            } catch (Throwable $e) { /* ignore sync errors */ }
        }
    }
    if ($action === 'subscribe_restock') {
        $pid = (int)($_POST['product_id'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        if ($pid && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $ins = $db->prepare('INSERT INTO restock_notifications (product_id, email) VALUES (:pid, :email)');
            $ins->execute([':pid' => $pid, ':email' => $email]);
        }
    }
    redirect(SITE_URL . '/admin/real_time_inventory.php');
}

// Load products
$products = [];
try {
    $pstmt = $db->query("SELECT id, name, price FROM products ORDER BY name ASC");
    $products = $pstmt->fetchAll();
} catch (Throwable $e) { $products = []; }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Real-Time Inventory - <?php echo SITE_NAME; ?></title>
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
        .price-input{width:100px;padding:6px;border-radius:6px;border:1px solid #ddd}
        .btn-edit-price, .btn-save-price, .btn-cancel-price{padding:6px 8px;border-radius:6px;border:0;background:var(--gold-900);color:#fff;cursor:pointer}
        .btn-cancel-price{background:#888;margin-left:6px}
        .btn-save-price[disabled]{opacity:0.6;cursor:not-allowed}
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
            <li><a href="real_time_inventory.php" class="active"><i class="fas fa-bolt"></i> Real-Time Inventory</a></li>
            <li><a href="stock_monitoring.php"><i class="fas fa-eye"></i> Stock Monitoring</a></li>
            <li><a href="sales_history.php"><i class="fas fa-history"></i> Sales History</a></li>
            <li><a href="order_checker.php"><i class="fas fa-check-circle"></i> Order Checker</a></li>
            <li><a href="sales_statistics.php"><i class="fas fa-chart-line"></i> Sales Statistics</a></li>
        </ul>
        <div class="sidebar-bottom"><a href="logout.php" class="btn ghost" style="width:100%;justify-content:center"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
    </aside>

    <main class="admin-main">
        <div class="content-card">
            <h2>Real-Time Inventory</h2>
            <table class="table">
                <thead>
                    <tr><th>Product</th><th>Price</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php foreach ($products as $prod): ?>
                    <tr data-id="<?php echo (int)$prod['id']; ?>">
                        <td><?php echo htmlspecialchars($prod['name']); ?></td>
                        <td class="price-cell"><?php echo function_exists('format_price') ? format_price($prod['price']) : number_format($prod['price'],2); ?></td>
                        <td>
                            <button class="btn-edit-price" data-id="<?php echo (int)$prod['id']; ?>">Edit</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                    </table>
                </div>
        <script>
            // Inline price edit (input + Save/Cancel) with AJAX
            document.addEventListener('click', function(e){
                // Start edit
                if (e.target && e.target.classList.contains('btn-edit-price')) {
                    var id = e.target.getAttribute('data-id');
                    var row = document.querySelector('tr[data-id="' + id + '"]');
                    if (!row) return;
                    var priceCell = row.querySelector('.price-cell');
                    var currentText = priceCell ? priceCell.textContent.trim() : '';
                    var current = currentText.replace(/[^0-9.\-]/g,'');
                    // prevent duplicate editors
                    if (row.querySelector('.price-input')) return;
                    row.dataset._orig = currentText;
                    priceCell.innerHTML = '<input class="price-input" value="'+ current +'" /> <button class="btn-save-price">Save</button> <button class="btn-cancel-price">Cancel</button>';
                    var input = priceCell.querySelector('.price-input'); if (input) input.focus();
                    return;
                }

                // Save
                if (e.target && e.target.classList.contains('btn-save-price')) {
                    var row = e.target.closest('tr'); if (!row) return;
                    var id = row.getAttribute('data-id');
                    var priceCell = row.querySelector('.price-cell');
                    var input = priceCell.querySelector('.price-input');
                    var saveBtn = priceCell.querySelector('.btn-save-price');
                    var cancelBtn = priceCell.querySelector('.btn-cancel-price');
                    if (!input) return;
                    var val = input.value.trim();
                    if (val === '' || isNaN(val)) { alert('Invalid price'); input.focus(); return; }
                    saveBtn.disabled = true; cancelBtn.disabled = true;
                    fetch('update_price.php', {
                        method: 'POST', headers: {'Content-Type':'application/json'},
                        body: JSON.stringify({ product_id: id, price: val })
                    }).then(function(r){ return r.json(); }).then(function(resp){
                        if (resp && resp.success) {
                            priceCell.textContent = resp.formatted_price || resp.price;
                            // brief highlight
                            priceCell.style.transition = 'background .3s'; priceCell.style.background = '#e9f7ef';
                            setTimeout(function(){ priceCell.style.background = ''; }, 800);
                        } else {
                            alert('Update failed: ' + (resp && resp.error ? resp.error : 'unknown'));
                            priceCell.textContent = row.dataset._orig || (resp && resp.formatted_price) || val;
                        }
                    }).catch(function(){ alert('Request failed'); priceCell.textContent = row.dataset._orig || val; }).finally(function(){
                        delete row.dataset._orig;
                    });
                    return;
                }

                // Cancel
                if (e.target && e.target.classList.contains('btn-cancel-price')) {
                    var row = e.target.closest('tr'); if (!row) return;
                    var priceCell = row.querySelector('.price-cell');
                    priceCell.textContent = row.dataset._orig || '';
                    delete row.dataset._orig;
                    return;
                }
            });
        </script>
    </main>
</div>
</body>
</html>
