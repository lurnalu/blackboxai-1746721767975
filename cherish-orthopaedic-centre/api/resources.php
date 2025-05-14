<?php
require_once '../config.php';
header('Content-Type: application/json');

switch($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get educational resources and materials
        try {
            $query = 'SELECT * FROM resources WHERE 1=1';
            $params = [];

            // Category filter
            if (isset($_GET['category'])) {
                $query .= ' AND category = ?';
                $params[] = $_GET['category'];
            }

            // Search filter
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $query .= ' AND (title LIKE ? OR description LIKE ?)';
                $searchTerm = '%' . $_GET['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            $query .= ' ORDER BY created_at DESC';

            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $resources = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'resources' => $resources
            ]);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch resources']);
        }
        break;

    case 'POST':
        requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['title']) || !isset($data['content'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit();
        }

        try {
            $stmt = $db->prepare('
                INSERT INTO resources (title, description, content, category, file_url, created_by) 
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $data['title'],
                $data['description'] ?? null,
                $data['content'],
                $data['category'] ?? null,
                $data['file_url'] ?? null,
                $_SESSION['user_id']
            ]);
            
            $resourceId = $db->lastInsertId();
            
            $stmt = $db->prepare('SELECT * FROM resources WHERE id = ?');
            $stmt->execute([$resourceId]);
            $resource = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'resource' => $resource
            ]);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create resource']);
        }
        break;

    case 'PUT':
        requireAuth();
        
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Resource ID not specified']);
            exit();
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $fields = [];
            $params = [];
            
            if (isset($data['title'])) {
                $fields[] = 'title = ?';
                $params[] = $data['title'];
            }
            if (isset($data['description'])) {
                $fields[] = 'description = ?';
                $params[] = $data['description'];
            }
            if (isset($data['content'])) {
                $fields[] = 'content = ?';
                $params[] = $data['content'];
            }
            if (isset($data['category'])) {
                $fields[] = 'category = ?';
                $params[] = $data['category'];
            }
            if (isset($data['file_url'])) {
                $fields[] = 'file_url = ?';
                $params[] = $data['file_url'];
            }

            if (empty($fields)) {
                http_response_code(400);
                echo json_encode(['error' => 'No fields to update']);
                exit();
            }

            $params[] = $_GET['id'];
            
            $stmt = $db->prepare('UPDATE resources SET ' . implode(', ', $fields) . ' WHERE id = ?');
            $stmt->execute($params);
            
            if ($stmt->rowCount() > 0) {
                $stmt = $db->prepare('SELECT * FROM resources WHERE id = ?');
                $stmt->execute([$_GET['id']]);
                $resource = $stmt->fetch();
                
                echo json_encode([
                    'success' => true,
                    'resource' => $resource
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Resource not found']);
            }
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update resource']);
        }
        break;

    case 'DELETE':
        requireAuth();
        
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Resource ID not specified']);
            exit();
        }

        try {
            $stmt = $db->prepare('DELETE FROM resources WHERE id = ?');
            $stmt->execute([$_GET['id']]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Resource not found']);
            }
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete resource']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
?>
