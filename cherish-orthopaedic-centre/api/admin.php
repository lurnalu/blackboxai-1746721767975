<?php
require_once '../config.php';
header('Content-Type: application/json');

// Check if user is admin
function requireAdmin() {
    requireAuth();
    
    $stmt = $db->prepare('SELECT is_admin FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || !$user['is_admin']) {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        exit();
    }
}

switch($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        requireAdmin();
        
        // Get dashboard statistics
        if (isset($_GET['stats'])) {
            try {
                // Get total users
                $stmt = $db->query('SELECT COUNT(*) as total_users FROM users');
                $users = $stmt->fetch();

                // Get total orders
                $stmt = $db->query('SELECT COUNT(*) as total_orders, SUM(total_amount) as total_revenue FROM orders');
                $orders = $stmt->fetch();

                // Get total appointments
                $stmt = $db->query('SELECT COUNT(*) as total_appointments FROM appointments');
                $appointments = $stmt->fetch();

                // Get low stock products
                $stmt = $db->query('SELECT * FROM products WHERE stock < 10');
                $low_stock = $stmt->fetchAll();

                // Get recent orders
                $stmt = $db->query('
                    SELECT o.*, u.name as user_name 
                    FROM orders o 
                    JOIN users u ON o.user_id = u.id 
                    ORDER BY o.created_at DESC 
                    LIMIT 5
                ');
                $recent_orders = $stmt->fetchAll();

                echo json_encode([
                    'success' => true,
                    'stats' => [
                        'total_users' => $users['total_users'],
                        'total_orders' => $orders['total_orders'],
                        'total_revenue' => $orders['total_revenue'],
                        'total_appointments' => $appointments['total_appointments'],
                        'low_stock_products' => $low_stock,
                        'recent_orders' => $recent_orders
                    ]
                ]);
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to fetch statistics']);
            }
            exit();
        }

        // Get all users
        if (isset($_GET['users'])) {
            try {
                $stmt = $db->query('SELECT id, name, email, created_at FROM users');
                $users = $stmt->fetchAll();
                echo json_encode([
                    'success' => true,
                    'users' => $users
                ]);
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to fetch users']);
            }
            exit();
        }

        break;

    case 'POST':
        requireAdmin();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Add new doctor
        if (isset($data['action']) && $data['action'] === 'add_doctor') {
            if (!isset($data['name']) || !isset($data['specialization'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                exit();
            }

            try {
                $stmt = $db->prepare('
                    INSERT INTO doctors (name, specialization, email) 
                    VALUES (?, ?, ?)
                ');
                $stmt->execute([
                    $data['name'],
                    $data['specialization'],
                    $data['email'] ?? null
                ]);
                
                $doctorId = $db->lastInsertId();
                
                $stmt = $db->prepare('SELECT * FROM doctors WHERE id = ?');
                $stmt->execute([$doctorId]);
                $doctor = $stmt->fetch();
                
                echo json_encode([
                    'success' => true,
                    'doctor' => $doctor
                ]);
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to add doctor']);
            }
            exit();
        }

        break;

    case 'PUT':
        requireAdmin();
        
        $data = json_decode(file_get_contents('php://input'), true);

        // Update order status
        if (isset($data['action']) && $data['action'] === 'update_order') {
            if (!isset($data['order_id']) || !isset($data['status'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                exit();
            }

            try {
                $stmt = $db->prepare('UPDATE orders SET status = ? WHERE id = ?');
                $stmt->execute([$data['status'], $data['order_id']]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true]);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Order not found']);
                }
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update order']);
            }
            exit();
        }

        // Update product stock
        if (isset($data['action']) && $data['action'] === 'update_stock') {
            if (!isset($data['product_id']) || !isset($data['stock'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                exit();
            }

            try {
                $stmt = $db->prepare('UPDATE products SET stock = ? WHERE id = ?');
                $stmt->execute([$data['stock'], $data['product_id']]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true]);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Product not found']);
                }
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update stock']);
            }
            exit();
        }

        break;

    case 'DELETE':
        requireAdmin();
        
        // Delete user
        if (isset($_GET['user_id'])) {
            try {
                $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
                $stmt->execute([$_GET['user_id']]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true]);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'User not found']);
                }
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete user']);
            }
            exit();
        }

        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
?>
