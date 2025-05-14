<?php
require_once '../config.php';
header('Content-Type: application/json');

switch($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get single product
        if (isset($_GET['id'])) {
            $stmt = $db->prepare('SELECT * FROM products WHERE id = ?');
            $stmt->execute([$_GET['id']]);
            $product = $stmt->fetch();
            
            if ($product) {
                echo json_encode($product);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Product not found']);
            }
            exit();
        }

        // Get all products with optional filtering
        $query = 'SELECT * FROM products WHERE 1=1';
        $params = [];

        // Category filter
        if (isset($_GET['category']) && $_GET['category'] !== 'All Categories') {
            $query .= ' AND category = ?';
            $params[] = $_GET['category'];
        }

        // Price range filter
        if (isset($_GET['price_range'])) {
            switch($_GET['price_range']) {
                case '0-5000':
                    $query .= ' AND price_kes <= 5000';
                    break;
                case '5000-10000':
                    $query .= ' AND price_kes > 5000 AND price_kes <= 10000';
                    break;
                case '10000+':
                    $query .= ' AND price_kes > 10000';
                    break;
            }
        }

        // Search filter
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $query .= ' AND (name LIKE ? OR description LIKE ?)';
            $searchTerm = '%' . $_GET['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Order by
        $query .= ' ORDER BY name ASC';

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $products = $stmt->fetchAll();

        echo json_encode([
            'products' => $products,
            'total' => count($products)
        ]);
        break;

    case 'POST':
        // Check authentication for adding products
        requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (isset($data['name']) && isset($data['price_kes'])) {
            try {
                $stmt = $db->prepare('INSERT INTO products (name, description, price_kes, category, stock, image) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([
                    $data['name'],
                    $data['description'] ?? '',
                    $data['price_kes'],
                    $data['category'] ?? null,
                    $data['stock'] ?? 0,
                    $data['image'] ?? null
                ]);
                
                $productId = $db->lastInsertId();
                
                $stmt = $db->prepare('SELECT * FROM products WHERE id = ?');
                $stmt->execute([$productId]);
                $product = $stmt->fetch();
                
                echo json_encode([
                    'success' => true,
                    'product' => $product
                ]);
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create product']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
        }
        break;

    case 'PUT':
        // Check authentication for updating products
        requireAuth();
        
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Product ID not specified']);
            exit();
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $fields = [];
            $params = [];
            
            if (isset($data['name'])) {
                $fields[] = 'name = ?';
                $params[] = $data['name'];
            }
            if (isset($data['description'])) {
                $fields[] = 'description = ?';
                $params[] = $data['description'];
            }
            if (isset($data['price_kes'])) {
                $fields[] = 'price_kes = ?';
                $params[] = $data['price_kes'];
            }
            if (isset($data['category'])) {
                $fields[] = 'category = ?';
                $params[] = $data['category'];
            }
            if (isset($data['stock'])) {
                $fields[] = 'stock = ?';
                $params[] = $data['stock'];
            }
            if (isset($data['image'])) {
                $fields[] = 'image = ?';
                $params[] = $data['image'];
            }

            if (empty($fields)) {
                http_response_code(400);
                echo json_encode(['error' => 'No fields to update']);
                exit();
            }

            $params[] = $_GET['id'];
            
            $stmt = $db->prepare('UPDATE products SET ' . implode(', ', $fields) . ' WHERE id = ?');
            $stmt->execute($params);
            
            if ($stmt->rowCount() > 0) {
                $stmt = $db->prepare('SELECT * FROM products WHERE id = ?');
                $stmt->execute([$_GET['id']]);
                $product = $stmt->fetch();
                
                echo json_encode([
                    'success' => true,
                    'product' => $product
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Product not found']);
            }
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update product']);
        }
        break;

    case 'DELETE':
        // Check authentication for deleting products
        requireAuth();
        
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Product ID not specified']);
            exit();
        }

        try {
            $stmt = $db->prepare('DELETE FROM products WHERE id = ?');
            $stmt->execute([$_GET['id']]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Product not found']);
            }
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete product']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
?>
