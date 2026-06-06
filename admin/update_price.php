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
    $price = isset($data['price']) ? $data['price'] : null;
    if ($product_id <= 0) {
        throw new Exception('Invalid product id');
    }
    if ($price === null || !is_numeric($price)) {
        throw new Exception('Invalid price');
    }

    $priceVal = round((float)$price, 2);

    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare('UPDATE products SET price = :price WHERE id = :id');
    $stmt->execute([':price' => $priceVal, ':id' => $product_id]);

    $formatted = function_exists('format_price') ? format_price($priceVal) : number_format($priceVal, 2);

    echo json_encode(['success' => true, 'product_id' => $product_id, 'price' => $priceVal, 'formatted_price' => $formatted]);
    exit;

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
