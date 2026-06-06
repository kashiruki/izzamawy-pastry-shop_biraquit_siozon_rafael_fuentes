<?php
/**
 * Cart API Handler
 * Izzamawy Pastry and Delicacies
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

class CartAPI {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Add item to cart
    public function addToCart($product_id, $quantity = 1) {
        try {
            // Get product details
            $sql = "SELECT * FROM products WHERE id = :id AND is_active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $product_id, PDO::PARAM_INT);
            $stmt->execute();
            $product = $stmt->fetch();
            
            if (!$product) {
                return ['success' => false, 'message' => 'Product not found'];
            }
            
            if ($product['stock_quantity'] < $quantity) {
                return ['success' => false, 'message' => 'Insufficient stock'];
            }
            
            // Initialize cart if not exists
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            
            // Check if product already in cart
            $found = false;
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['id'] == $product_id) {
                    $item['quantity'] += $quantity;
                    $found = true;
                    break;
                }
            }
            
            // Add new item if not found
            if (!$found) {
                $_SESSION['cart'][] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'image_url' => $product['image_url'],
                    'quantity' => $quantity
                ];
            }
            
            return [
                'success' => true, 
                'message' => 'Product added to cart',
                'cart_count' => get_cart_count()
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // Update cart item quantity
    public function updateCart($product_id, $quantity) {
        if (!isset($_SESSION['cart'])) {
            return ['success' => false, 'message' => 'Cart is empty'];
        }
        
        if ($quantity <= 0) {
            return $this->removeFromCart($product_id);
        }
        
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] == $product_id) {
                $item['quantity'] = $quantity;
                return [
                    'success' => true,
                    'message' => 'Cart updated',
                    'cart_count' => get_cart_count(),
                    'cart_total' => calculate_cart_total()
                ];
            }
        }
        
        return ['success' => false, 'message' => 'Product not found in cart'];
    }
    
    // Remove item from cart
    public function removeFromCart($product_id) {
        if (!isset($_SESSION['cart'])) {
            return ['success' => false, 'message' => 'Cart is empty'];
        }
        
        $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($product_id) {
            return $item['id'] != $product_id;
        });
        
        // Reindex array
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        
        return [
            'success' => true,
            'message' => 'Product removed from cart',
            'cart_count' => get_cart_count(),
            'cart_total' => calculate_cart_total()
        ];
    }
    
    // Get cart contents
    public function getCart() {
        return [
            'items' => $_SESSION['cart'] ?? [],
            'count' => get_cart_count(),
            'total' => calculate_cart_total()
        ];
    }
    
    // Clear cart
    public function clearCart() {
        $_SESSION['cart'] = [];
        return ['success' => true, 'message' => 'Cart cleared'];
    }
}

// Handle API requests
$api = new CartAPI();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $product_id = $data['product_id'] ?? 0;
            $quantity = $data['quantity'] ?? 1;
            echo json_encode($api->addToCart($product_id, $quantity));
            break;
            
        case 'update':
            $product_id = $data['product_id'] ?? 0;
            $quantity = $data['quantity'] ?? 1;
            echo json_encode($api->updateCart($product_id, $quantity));
            break;
            
        case 'remove':
            $product_id = $data['product_id'] ?? 0;
            echo json_encode($api->removeFromCart($product_id));
            break;
            
        case 'clear':
            echo json_encode($api->clearCart());
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'get') {
        echo json_encode($api->getCart());
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}
