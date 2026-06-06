<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        throw new Exception('Invalid JSON payload');
    }

    $product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;
    $total_stock = isset($data['total_stock']) ? (int)$data['total_stock'] : null;
    if ($product_id <= 0) {
        throw new Exception('Invalid product id');
    }
    if ($total_stock === null || $total_stock < 0) {
        throw new Exception('Invalid total_stock');
    }

    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();

    // Update master product stock
    $upd = $db->prepare('UPDATE products SET stock_quantity = :qty WHERE id = :id');
    $upd->execute([':qty' => $total_stock, ':id' => $product_id]);

    // Try to sync existing product_stock rows; if none exist, insert baseline
    try {
        $sync = $db->prepare('UPDATE product_stock ps JOIN products p ON p.id = ps.product_id SET ps.stock_quantity = p.stock_quantity, ps.restock_required = (p.stock_quantity < ps.restock_threshold), ps.last_checked = NOW() WHERE p.id = :id');
        $sync->execute([':id' => $product_id]);
        if ($sync->rowCount() === 0) {
            $ins = $db->prepare('INSERT INTO product_stock (product_id, stock_quantity, restock_threshold, restock_required, created_at) SELECT p.id, p.stock_quantity, 10, (p.stock_quantity < 10), NOW() FROM products p WHERE p.id = :id AND NOT EXISTS (SELECT 1 FROM product_stock ps WHERE ps.product_id = p.id)');
            $ins->execute([':id' => $product_id]);
        }
    } catch (Throwable $e) {
        // ignore sync issues but continue
    }

    $db->commit();

    // Return aggregated total stock (sum of product_stock if present else products.stock_quantity)
    $stmt = $db->prepare('SELECT COALESCE(SUM(ps.stock_quantity), p.stock_quantity) AS total_stock FROM products p LEFT JOIN product_stock ps ON ps.product_id = p.id WHERE p.id = :id');
    $stmt->execute([':id' => $product_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $agg = (int)($row['total_stock'] ?? $total_stock);
    $is_low = $agg <= 30;

    echo json_encode(['success' => true, 'product_id' => $product_id, 'total_stock' => $agg, 'is_low' => $is_low]);
    exit;

} catch (Throwable $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
