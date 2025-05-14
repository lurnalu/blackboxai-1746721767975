<?php
require_once '../config.php';
header('Content-Type: application/json');

switch($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (isset($data['action'])) {
            switch($data['action']) {
                case 'login':
                    if (isset($data['email']) && isset($data['password'])) {
                        $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
                        $stmt->execute([$data['email']]);
                        $user = $stmt->fetch();
                        
                        if ($user && password_verify($data['password'], $user['password'])) {
                            $_SESSION['user_id'] = $user['id'];
                            echo json_encode([
                                'success' => true,
                                'user' => [
                                    'id' => $user['id'],
                                    'name' => $user['name'],
                                    'email' => $user['email']
                                ]
                            ]);
                        } else {
                            http_response_code(401);
                            echo json_encode(['error' => 'Invalid credentials']);
                        }
                    }
                    break;

                case 'register':
                    if (isset($data['email']) && isset($data['password']) && isset($data['name'])) {
                        try {
                            // Check if email already exists
                            $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
                            $stmt->execute([$data['email']]);
                            if ($stmt->fetch()) {
                                http_response_code(400);
                                echo json_encode(['error' => 'Email already exists']);
                                exit();
                            }

                            // Create new user
                            $stmt = $db->prepare('INSERT INTO users (email, password, name) VALUES (?, ?, ?)');
                            $stmt->execute([
                                $data['email'],
                                password_hash($data['password'], PASSWORD_DEFAULT),
                                $data['name']
                            ]);
                            
                            $userId = $db->lastInsertId();
                            $_SESSION['user_id'] = $userId;
                            
                            echo json_encode([
                                'success' => true,
                                'user' => [
                                    'id' => $userId,
                                    'name' => $data['name'],
                                    'email' => $data['email']
                                ]
                            ]);
                        } catch(PDOException $e) {
                            http_response_code(500);
                            echo json_encode(['error' => 'Registration failed']);
                        }
                    } else {
                        http_response_code(400);
                        echo json_encode(['error' => 'Missing required fields']);
                    }
                    break;

                case 'logout':
                    session_destroy();
                    echo json_encode(['success' => true]);
                    break;

                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid action']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Action not specified']);
        }
        break;

    case 'GET':
        if (isAuthenticated()) {
            $stmt = $db->prepare('SELECT id, name, email FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            if ($user) {
                echo json_encode(['user' => $user]);
            } else {
                session_destroy();
                http_response_code(401);
                echo json_encode(['error' => 'User not found']);
            }
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
?>
