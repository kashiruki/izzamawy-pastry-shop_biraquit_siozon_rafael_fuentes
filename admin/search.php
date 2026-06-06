<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Simple JSON search endpoint for admin quick-search
if (!is_admin_logged_in()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'unauthenticated']);
    exit;
}

$q = trim($_GET['q'] ?? '');
header('Content-Type: application/json; charset=utf-8');
if ($q === '' || strlen($q) < 2) {
    echo json_encode(['products' => [], 'orders' => []]);
    exit;
}

$db = Database::getInstance()->getConnection();
$like = '%' . str_replace('%', '\\%', $q) . '%';
// include suppliers and customers
$out = ['products' => [], 'orders' => [], 'suppliers' => [], 'customers' => []];
try {
    $stmt = $db->prepare("SELECT id, name, sku, stock_quantity FROM products WHERE is_active = 1 AND (name LIKE :q OR sku LIKE :q) ORDER BY name LIMIT 6");
    $stmt->execute([':q' => $like]);
    $out['products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { /* ignore */ }

try {
    $stmt = $db->prepare("SELECT id, order_number, customer_name, total FROM orders WHERE (order_number LIKE :q OR customer_name LIKE :q) ORDER BY created_at DESC LIMIT 6");
    $stmt->execute([':q' => $like]);
    $out['orders'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { /* ignore */ }

try {
    $stmt = $db->prepare("SELECT id, company_name, contact_person, phone FROM suppliers WHERE (company_name LIKE :q OR contact_person LIKE :q) ORDER BY company_name LIMIT 6");
    $stmt->execute([':q' => $like]);
    $out['suppliers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { /* ignore */ }

try {
    $stmt = $db->prepare("SELECT id, CONCAT(first_name, ' ', last_name) as name, email, phone FROM customers WHERE (first_name LIKE :q OR last_name LIKE :q OR email LIKE :q) ORDER BY first_name LIMIT 6");
    $stmt->execute([':q' => $like]);
    $out['customers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { /* ignore */ }

echo json_encode($out);

?>
