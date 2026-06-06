<?php
/**
 * Products API Handler
 * Izzamawy Pastry and Delicacies
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

class ProductsAPI {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Get all active products with optional filters
    public function getProducts($category_id = null, $featured = null, $limit = null) {
        try {
            $sql = "SELECT p.*, c.name as category_name 
                    FROM products p 
                    INNER JOIN categories c ON p.category_id = c.id 
                    WHERE p.is_active = 1";
            
            $params = [];
            
            if ($category_id !== null) {
                $sql .= " AND p.category_id = :category_id";
                $params[':category_id'] = $category_id;
            }
            
            if ($featured !== null) {
                $sql .= " AND p.is_featured = :featured";
                $params[':featured'] = $featured;
            }
            
            $sql .= " ORDER BY p.created_at DESC";
            
            if ($limit !== null) {
                $sql .= " LIMIT :limit";
            }
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            if ($limit !== null) {
                $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    // Get single product by ID
    public function getProduct($id) {
        try {
            $sql = "SELECT p.*, c.name as category_name 
                    FROM products p 
                    INNER JOIN categories c ON p.category_id = c.id 
                    WHERE p.id = :id AND p.is_active = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    // Search products
    public function searchProducts($keyword) {
        try {
            $sql = "SELECT p.*, c.name as category_name 
                    FROM products p 
                    INNER JOIN categories c ON p.category_id = c.id 
                    WHERE p.is_active = 1 
                    AND (p.name LIKE :keyword OR p.description LIKE :keyword)
                    ORDER BY p.name ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':keyword', '%' . $keyword . '%');
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    // Get all categories
    public function getCategories() {
        try {
            $sql = "SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order ASC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    // Get category by ID
    public function getCategory($id) {
        try {
            $sql = "SELECT * FROM categories WHERE id = :id AND is_active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // Get category by name
    public function getCategoryByName($name) {
        try {
            $sql = "SELECT * FROM categories WHERE name = :name AND is_active = 1 LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':name', $name);
            $stmt->execute();

            return $stmt->fetch();
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // Get products by category name (joins categories table)
    public function getProductsByCategoryName($category_name, $limit = null) {
        try {
            $sql = "SELECT p.*, c.name as category_name 
                    FROM products p 
                    INNER JOIN categories c ON p.category_id = c.id 
                    WHERE p.is_active = 1 AND c.name = :cname 
                    ORDER BY p.created_at DESC";

            if ($limit !== null) {
                $sql .= " LIMIT :limit";
            }

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':cname', $category_name);

            if ($limit !== null) {
                $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            }

            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // Get single product by name (partial match)
    public function getProductByName($name) {
        try {
            $sql = "SELECT p.*, c.name as category_name 
                    FROM products p 
                    INNER JOIN categories c ON p.category_id = c.id 
                    WHERE p.is_active = 1 AND (p.name = :exact OR p.name LIKE :like)
                    ORDER BY p.created_at DESC LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':exact', $name);
            $stmt->bindValue(':like', '%' . $name . '%');
            $stmt->execute();

            return $stmt->fetch();
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
}

// Handle API requests
// Only run API handler if this file is accessed directly with an action parameter
if (isset($_GET['action']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    
    $api = new ProductsAPI();
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_products':
            $category_id = $_GET['category_id'] ?? null;
            $featured = $_GET['featured'] ?? null;
            $limit = $_GET['limit'] ?? null;
            echo json_encode($api->getProducts($category_id, $featured, $limit));
            break;
            
        case 'get_product':
            $id = $_GET['id'] ?? 0;
            echo json_encode($api->getProduct($id));
            break;
            
        case 'search':
            $keyword = $_GET['keyword'] ?? '';
            echo json_encode($api->searchProducts($keyword));
            break;
            
        case 'get_categories':
            echo json_encode($api->getCategories());
            break;
            
        case 'get_category':
            $id = $_GET['id'] ?? 0;
            echo json_encode($api->getCategory($id));
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}
