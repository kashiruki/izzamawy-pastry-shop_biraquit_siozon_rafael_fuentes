<?php
/**
 * Orders API Handler
 * Izzamawy Pastry and Delicacies
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

// When this file is requested directly (API endpoint), emit JSON headers
// and handle the request. When included by other pages (e.g. order-confirmation.php)
// we should only provide the OrdersAPI class without sending headers or output.

class OrdersAPI {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Create new order
    public function createOrder($data) {
        try {
            // Validate required fields (city is optional because frontend no longer sends it)
            $required = ['customer_name', 'customer_email', 'customer_phone', 'shipping_address', 'province', 'payment_method'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "Field {$field} is required"];
                }
            }
            
            // Check if cart is not empty
            if (empty($_SESSION['cart'])) {
                return ['success' => false, 'message' => 'Cart is empty'];
            }
            
            // Calculate totals
            $subtotal = calculate_cart_total();
            $shipping_fee = $this->calculateShipping($data['province'], $subtotal);
            $total = $subtotal + $shipping_fee;
            
            // Generate order number
            $order_number = generate_order_number();
            // Compute estimated arrival date server-side from config if available
            $estimated_arrival = null;
            $provinceKey = $data['province'] ?? '';
            if (!empty($provinceKey) && defined('SHIPPING_ESTIMATES') && is_array(SHIPPING_ESTIMATES) && isset(SHIPPING_ESTIMATES[$provinceKey])) {
                $days = (int) SHIPPING_ESTIMATES[$provinceKey];
                if ($days > 0) {
                    $estimated_arrival = date('Y-m-d', strtotime("+{$days} days"));
                }
            }
            
            // Begin transaction
            $this->db->beginTransaction();
            
            // Insert order - include `estimated_arrival` only if the column exists (defensive)
            $hasEstimated = false;
            try {
                $colStmt = $this->db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'estimated_arrival'");
                $colStmt->execute();
                $hasEstimated = (bool)$colStmt->fetchColumn();
            } catch (Throwable $e) { $hasEstimated = false; }

            if ($hasEstimated) {
                $sql = "INSERT INTO orders (
                            customer_id, order_number, customer_name, customer_email, 
                            customer_phone, shipping_address, city, province, postal_code,
                            subtotal, shipping_fee, total, payment_method, notes, estimated_arrival
                        ) VALUES (
                            :customer_id, :order_number, :customer_name, :customer_email,
                            :customer_phone, :shipping_address, :city, :province, :postal_code,
                            :subtotal, :shipping_fee, :total, :payment_method, :notes, :estimated_arrival
                        )";

                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':customer_id' => $_SESSION['customer_id'] ?? null,
                    ':order_number' => $order_number,
                    ':customer_name' => sanitize_input($data['customer_name']),
                    ':customer_email' => sanitize_input($data['customer_email']),
                    ':customer_phone' => sanitize_input($data['customer_phone']),
                    ':shipping_address' => sanitize_input($data['shipping_address']),
                    ':city' => sanitize_input($data['city'] ?? ''),
                    ':province' => sanitize_input($data['province']),
                    ':postal_code' => sanitize_input($data['postal_code'] ?? ''),
                    ':subtotal' => $subtotal,
                    ':shipping_fee' => $shipping_fee,
                    ':total' => $total,
                    ':payment_method' => sanitize_input($data['payment_method']),
                    ':notes' => sanitize_input($data['notes'] ?? ''),
                    ':estimated_arrival' => $estimated_arrival
                ]);
            } else {
                $sql = "INSERT INTO orders (
                            customer_id, order_number, customer_name, customer_email, 
                            customer_phone, shipping_address, city, province, postal_code,
                            subtotal, shipping_fee, total, payment_method, notes
                        ) VALUES (
                            :customer_id, :order_number, :customer_name, :customer_email,
                            :customer_phone, :shipping_address, :city, :province, :postal_code,
                            :subtotal, :shipping_fee, :total, :payment_method, :notes
                        )";

                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':customer_id' => $_SESSION['customer_id'] ?? null,
                    ':order_number' => $order_number,
                    ':customer_name' => sanitize_input($data['customer_name']),
                    ':customer_email' => sanitize_input($data['customer_email']),
                    ':customer_phone' => sanitize_input($data['customer_phone']),
                    ':shipping_address' => sanitize_input($data['shipping_address']),
                    ':city' => sanitize_input($data['city'] ?? ''),
                    ':province' => sanitize_input($data['province']),
                    ':postal_code' => sanitize_input($data['postal_code'] ?? ''),
                    ':subtotal' => $subtotal,
                    ':shipping_fee' => $shipping_fee,
                    ':total' => $total,
                    ':payment_method' => sanitize_input($data['payment_method']),
                    ':notes' => sanitize_input($data['notes'] ?? '')
                ]);
            }
            
            $order_id = $this->db->lastInsertId();
            
            // Insert order items
            $sql = "INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, subtotal)
                    VALUES (:order_id, :product_id, :product_name, :quantity, :unit_price, :subtotal)";
            $stmt = $this->db->prepare($sql);
            
            foreach ($_SESSION['cart'] as $item) {
                // Insert items as recorded in cart. Stock deduction is deferred until admin confirmation.
                $item_subtotal = $item['price'] * $item['quantity'];
                $stmt->execute([
                    ':order_id' => $order_id,
                    ':product_id' => $item['id'],
                    ':product_name' => $item['name'],
                    ':quantity' => $item['quantity'],
                    ':unit_price' => $item['price'],
                    ':subtotal' => $item_subtotal
                ]);
            }

            // Ensure `stock_deducted` flag exists and is set to 0 for this order (defensive, non-fatal)
            try {
                $colStmt = $this->db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'stock_deducted'");
                $colStmt->execute();
                $hasFlag = (bool)$colStmt->fetchColumn();
                if ($hasFlag) {
                    $u = $this->db->prepare('UPDATE orders SET stock_deducted = 0 WHERE id = :id');
                    $u->execute([':id' => $order_id]);
                }
            } catch (Throwable $e) { /* ignore */ }
            
            // Commit transaction
            $this->db->commit();
            
            // Clear cart
            $_SESSION['cart'] = [];

            // Store last order number in session for immediate confirmation page
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['last_order_number'] = $order_number;
            
            return [
                'success' => true,
                'message' => 'Order placed successfully',
                'order_number' => $order_number,
                'order_id' => $order_id
            ];
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // Calculate shipping fee
    private function calculateShipping($province, $subtotal) {
        // Free shipping for orders above threshold
        if ($subtotal >= FREE_SHIPPING_THRESHOLD) {
            return 0;
        }
        // Prefer configured per-municipality rates when available
        if (defined('SHIPPING_RATES') && is_array(SHIPPING_RATES) && !empty($province)) {
            if (isset(SHIPPING_RATES[$province])) {
                return (int) SHIPPING_RATES[$province];
            }
        }

        // Fallback: check if Metro Manila
        $metro_manila = ['Metro Manila', 'NCR', 'Manila', 'Quezon City', 'Makati', 'Taguig', 'Pasig'];
        if (in_array($province, $metro_manila)) {
            return SHIPPING_FEE_METRO_MANILA;
        }

        return SHIPPING_FEE_PROVINCIAL;
    }
    
    // Get order by order number
    public function getOrder($order_number) {
        try {
            $sql = "SELECT * FROM orders WHERE order_number = :order_number";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':order_number' => $order_number]);
            $order = $stmt->fetch();
            
            if ($order) {
                // Get order items
                $sql = "SELECT * FROM order_items WHERE order_id = :order_id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':order_id' => $order['id']]);
                $order['items'] = $stmt->fetchAll();
            }
            
            return $order;
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
}

// Handle API requests only when this file is requested directly.
if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json');
    $api = new OrdersAPI();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';

        switch ($action) {
            case 'create':
                echo json_encode($api->createOrder($data));
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';

        if ($action === 'get') {
            $order_number = $_GET['order_number'] ?? '';
            echo json_encode($api->getOrder($order_number));
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }
}
