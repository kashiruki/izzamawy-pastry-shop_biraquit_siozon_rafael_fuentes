<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
if (!is_admin_logged_in()) redirect(SITE_URL . '/admin/admin_login.php');
$db = Database::getInstance()->getConnection();

$success = '';
$error = '';

// Handle confirmation POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_order_id'])) {
    $id = (int)$_POST['confirm_order_id'];
    if ($id <= 0) {
        $error = 'Invalid order id.';
    } else {
        try {
            // Begin transaction for status update + stock adjustments
            $db->beginTransaction();

            // Ensure orders table has `stock_deducted` flag for idempotence
            try {
                $colChk = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'stock_deducted'");
                $colChk->execute();
                $hasFlag = (bool)$colChk->fetchColumn();
                if (!$hasFlag) {
                    // add column default 0
                    $db->exec("ALTER TABLE orders ADD COLUMN stock_deducted TINYINT(1) DEFAULT 0 AFTER order_status");
                }
            } catch (Throwable $e) {
                // If altering fails, continue — we'll still attempt safe updates without flag
                $hasFlag = false;
            }

            // Load order and items
            $oStmt = $db->prepare('SELECT id, order_status, stock_deducted FROM orders WHERE id = :id FOR UPDATE');
            $oStmt->execute([':id' => $id]);
            $orderRow = $oStmt->fetch(PDO::FETCH_ASSOC);
            if (!$orderRow || $orderRow['order_status'] !== 'pending') {
                $db->rollBack();
                $error = 'Order not found or not pending.';
            } else {
                $alreadyDeducted = false;
                if ($hasFlag) {
                    $alreadyDeducted = (int)($orderRow['stock_deducted'] ?? 0) === 1;
                }

                if (!$alreadyDeducted) {
                    // Fetch items
                    $it = $db->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = :id');
                    $it->execute([':id' => $id]);
                    $items = $it->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($items as $itRow) {
                        $pid = (int)$itRow['product_id'];
                        $qty = (int)$itRow['quantity'];
                        if ($pid <= 0 || $qty <= 0) continue;

                        // Decrement products.stock_quantity safely
                        $upd = $db->prepare('UPDATE products SET stock_quantity = GREATEST(stock_quantity - :qty, 0) WHERE id = :id');
                        $upd->execute([':qty' => $qty, ':id' => $pid]);

                        // Update product_stock if exists, else insert baseline from products
                        try {
                            $ps_update = $db->prepare('UPDATE product_stock SET stock_quantity = GREATEST(stock_quantity - :quantity, 0), restock_required = (GREATEST(stock_quantity - :quantity, 0) < restock_threshold), last_checked = NOW() WHERE product_id = :id');
                            $ps_update->execute([':quantity' => $qty, ':id' => $pid]);

                            if ($ps_update->rowCount() === 0) {
                                $cur = $db->prepare('SELECT stock_quantity FROM products WHERE id = :id');
                                $cur->execute([':id' => $pid]);
                                $currentQty = (int)$cur->fetchColumn();

                                $ins = $db->prepare('INSERT INTO product_stock (product_id, stock_quantity, restock_threshold, restock_required, created_at) VALUES (:id, :qty, 10, (:qty < 10), NOW())');
                                $ins->execute([':id' => $pid, ':qty' => $currentQty]);
                            }
                        } catch (Throwable $e) {
                            // ignore sync errors but continue
                        }
                    }

                    // Mark stock deducted if flag present
                    if ($hasFlag) {
                        $mark = $db->prepare('UPDATE orders SET stock_deducted = 1 WHERE id = :id');
                        $mark->execute([':id' => $id]);
                    }
                }

                // Update order_status to confirmed
                $u = $db->prepare("UPDATE orders SET order_status = 'confirmed', updated_at = NOW() WHERE id = :id");
                $u->execute([':id' => $id]);

                $db->commit();
                $success = 'Order confirmed successfully.';
            }
        } catch (Throwable $e) {
            try { $db->rollBack(); } catch (Throwable $_) {}
            $error = 'Database error while confirming order.';
        }
    }
    // Redirect to avoid reposts and show message via query param
    $qs = $success ? 'success=1' : 'error=1';
    header('Location: order_checker.php?' . $qs);
    exit;
}

// Load pending orders
$pending_orders = [];
try {
    $stmt = $db->query("SELECT id, order_number, customer_name, total, payment_status, created_at FROM orders WHERE order_status = 'pending' ORDER BY created_at ASC");
    $pending_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $pending_orders = [];
}

$showSuccess = isset($_GET['success']);
$showError = isset($_GET['error']);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Order Checker - <?php echo SITE_NAME; ?></title>
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
        .content-card{background:var(--card);border-radius:12px;padding:18px;box-shadow:0 6px 20px rgba(16,24,40,0.04)}
        table.table{width:100%;border-collapse:collapse}
        table.table th, table.table td{padding:10px;border-bottom:1px solid #f0eeeb;text-align:left}
        .btn-confirm{background:linear-gradient(90deg,var(--gold-700),var(--gold-900));color:#fff;padding:8px 12px;border-radius:8px;border:0;cursor:pointer;font-weight:700}
        .muted{color:var(--muted)}
        .toast{position:fixed;right:18px;bottom:18px;background:#222;color:#fff;padding:12px 16px;border-radius:8px;box-shadow:0 8px 30px rgba(0,0,0,0.2);opacity:0;transform:translateY(8px);transition:opacity .24s ease,transform .24s ease;z-index:9999}
        .toast.show{opacity:1;transform:translateY(0)}
        @media (max-width:900px){.admin-layout{grid-template-columns:1fr}}
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
                <li><a href="order_checker.php" class="active"><i class="fas fa-check-circle"></i> Order Checker</a></li>
                <li><a href="sales_statistics.php"><i class="fas fa-chart-line"></i> Sales Statistics</a></li>
            </ul>

            <div style="margin-top:auto">
                <a href="logout.php" style="display:block;padding:10px;border-radius:8px;background:transparent;color:#fff;text-align:center;text-decoration:none">Logout</a>
            </div>
        </aside>

        <main class="admin-main">
            <div class="topbar">
                <div>
                    <h1 style="margin:0;font-size:22px;color:#222">Order Checker</h1>
                    <div style="font-size:13px;color:var(--muted)">Confirm pending orders from the system</div>
                </div>
                <div style="font-size:13px;color:var(--muted)">Welcome back, <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></div>
            </div>

            <div class="content-card">
                <h2 style="margin-top:0">Pending Orders</h2>
                <?php if (empty($pending_orders)): ?>
                    <div style="color:var(--muted)">There are no pending orders at the moment.</div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr><th>Order #</th><th>Customer</th><th>Total</th><th>Payment</th><th>Date</th><th></th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($pending_orders as $o): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($o['order_number'] ?? $o['id']); ?></td>
                                <td><?php echo htmlspecialchars($o['customer_name'] ?? '—'); ?></td>
                                <td><?php echo function_exists('format_price') ? format_price($o['total']) : number_format($o['total'],2); ?></td>
                                <td><?php echo htmlspecialchars($o['payment_status'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($o['created_at'] ?? '—'); ?></td>
                                <td style="white-space:nowrap">
                                    <form method="post" style="display:inline" onsubmit="return confirm('Confirm this order?');">
                                        <input type="hidden" name="confirm_order_id" value="<?php echo (int)$o['id']; ?>">
                                        <button type="submit" class="btn-confirm">Confirm</button>
                                    </form>
                                    <a href="order_view.php?id=<?php echo (int)$o['id']; ?>&amp;return=order_checker" style="margin-left:10px;color:var(--muted);text-decoration:underline">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php if ($showSuccess): ?>
        <div class="toast show">Order confirmed successfully.</div>
    <?php endif; ?>
    <?php if ($showError): ?>
        <div class="toast show">There was a problem confirming the order.</div>
    <?php endif; ?>
</body>
</html>
