<?php
require_once '../config.php';
header('Content-Type: application/json');

switch($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        requireAuth();
        
        // Get single order
        if (isset($_GET['id'])) {
            $stmt = $db->prepare('
                SELECT o.*, oi.*, p.name as product_name, p.image as product_image 
                FROM orders o 
                JOIN order_items oi ON o.id = oi.order_id 
                JOIN products p ON oi.product_id = p.id 
                WHERE o.id = ? AND o.user_id = ?
            ');
            $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
            $items = $stmt->fetchAll();
            
            if ($items) {
                $order = [
                    'id' => $items[0]['id'],
                    'total_amount' => $items[0]['total_amount'],
                    'status' => $items[0]['status'],
                    'created_at' => $items[0]['created_at'],
                    'items' => array_map(function($item) {
                        return [
                            'product_id' => $item['product_id'],
                            'product_name' => $item['product_name'],
                            'product_image' => $item['product_image'],
                            'quantity' => $item['quantity'],
                            'price_kes' => $item['price_kes']
                        ];
                    }, $items)
                ];
                echo json_encode($order);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Order not found']);
            }
            exit();
        }

        // Get all orders for user
        $stmt = $db->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$_SESSION['user_id']]);
        $orders = $stmt->fetchAll();

        echo json_encode(['orders' => $orders]);
        break;

    case 'POST':
        requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['items']) || empty($data['items'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No items in order']);
            exit();
        }

        try {
            $db->beginTransaction();

            // Calculate total amount and verify stock
            $total_amount = 0;
            foreach ($data['items'] as $item) {
                $stmt = $db->prepare('SELECT price_kes, stock FROM products WHERE id = ?');
                $stmt->execute([$item['product_id']]);
                $product = $stmt->fetch();

                if (!$product) {
                    throw new Exception("Product not found: " . $item['product_id']);
                }

                if ($product['stock'] < $item['quantity']) {
                    throw new Exception("Insufficient stock for product: " . $item['product_id']);
                }

                $total_amount += $product['price_kes'] * $item['quantity'];
            }

            // Create order
            $stmt = $db->prepare('INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, ?)');
            $stmt->execute([
                $_SESSION['user_id'],
                $total_amount,
                'pending'
            ]);
            
            $orderId = $db->lastInsertId();

            // Create order items and update stock
            foreach ($data['items'] as $item) {
                $stmt = $db->prepare('INSERT INTO order_items (order_id, product_id, quantity, price_kes) VALUES (?, ?, ?, ?)');
                $stmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['quantity'],
                    $product['price_kes']
                ]);

                // Update product stock
                $stmt = $db->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }

            $db->commit();

            // Get complete order details
            $stmt = $db->prepare('SELECT * FROM orders WHERE id = ?');
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();

            echo json_encode([
                'success' => true,
                'order' => $order
            ]);
        } catch (Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'PUT':
        requireAuth();
        
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Order ID not specified']);
            exit();
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['status'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No status provided']);
            exit();
        }

        try {
            $stmt = $db->prepare('UPDATE orders SET status = ? WHERE id = ? AND user_id = ?');
            $stmt->execute([$data['status'], $_GET['id'], $_SESSION['user_id']]);
            
            if ($stmt->rowCount() > 0) {
                $stmt = $db->prepare('SELECT * FROM orders WHERE id = ?');
                $stmt->execute([$_GET['id']]);
                $order = $stmt->fetch();
                
                echo json_encode([
                    'success' => true,
                    'order' => $order
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Order not found']);
            }
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update order']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
?>
