<?php
require_once '../config.php';
header('Content-Type: application/json');

switch($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        requireAuth();
        
        // Get single appointment
        if (isset($_GET['id'])) {
            $stmt = $db->prepare('
                SELECT a.*, d.name as doctor_name, d.specialization 
                FROM appointments a 
                JOIN doctors d ON a.doctor_id = d.id 
                WHERE a.id = ? AND a.user_id = ?
            ');
            $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
            $appointment = $stmt->fetch();
            
            if ($appointment) {
                echo json_encode($appointment);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Appointment not found']);
            }
            exit();
        }

        // Get all appointments for user
        $stmt = $db->prepare('
            SELECT a.*, d.name as doctor_name, d.specialization 
            FROM appointments a 
            JOIN doctors d ON a.doctor_id = d.id 
            WHERE a.user_id = ? 
            ORDER BY a.appointment_date ASC, a.appointment_time ASC
        ');
        $stmt->execute([$_SESSION['user_id']]);
        $appointments = $stmt->fetchAll();

        echo json_encode(['appointments' => $appointments]);
        break;

    case 'POST':
        requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['doctor_id']) || !isset($data['appointment_date']) || !isset($data['appointment_time'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit();
        }

        try {
            // Check if doctor exists
            $stmt = $db->prepare('SELECT id FROM doctors WHERE id = ?');
            $stmt->execute([$data['doctor_id']]);
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Doctor not found']);
                exit();
            }

            // Check if timeslot is available
            $stmt = $db->prepare('
                SELECT id FROM appointments 
                WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status != "cancelled"
            ');
            $stmt->execute([
                $data['doctor_id'],
                $data['appointment_date'],
                $data['appointment_time']
            ]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Time slot not available']);
                exit();
            }

            // Create appointment
            $stmt = $db->prepare('
                INSERT INTO appointments (user_id, doctor_id, appointment_date, appointment_time, notes, status) 
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $_SESSION['user_id'],
                $data['doctor_id'],
                $data['appointment_date'],
                $data['appointment_time'],
                $data['notes'] ?? null,
                'pending'
            ]);
            
            $appointmentId = $db->lastInsertId();

            // Get complete appointment details
            $stmt = $db->prepare('
                SELECT a.*, d.name as doctor_name, d.specialization 
                FROM appointments a 
                JOIN doctors d ON a.doctor_id = d.id 
                WHERE a.id = ?
            ');
            $stmt->execute([$appointmentId]);
            $appointment = $stmt->fetch();

            echo json_encode([
                'success' => true,
                'appointment' => $appointment
            ]);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create appointment']);
        }
        break;

    case 'PUT':
        requireAuth();
        
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Appointment ID not specified']);
            exit();
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $fields = [];
            $params = [];
            
            if (isset($data['status'])) {
                $fields[] = 'status = ?';
                $params[] = $data['status'];
            }
            if (isset($data['notes'])) {
                $fields[] = 'notes = ?';
                $params[] = $data['notes'];
            }
            if (isset($data['appointment_date'])) {
                $fields[] = 'appointment_date = ?';
                $params[] = $data['appointment_date'];
            }
            if (isset($data['appointment_time'])) {
                $fields[] = 'appointment_time = ?';
                $params[] = $data['appointment_time'];
            }

            if (empty($fields)) {
                http_response_code(400);
                echo json_encode(['error' => 'No fields to update']);
                exit();
            }

            $params[] = $_GET['id'];
            $params[] = $_SESSION['user_id'];
            
            $stmt = $db->prepare('UPDATE appointments SET ' . implode(', ', $fields) . ' WHERE id = ? AND user_id = ?');
            $stmt->execute($params);
            
            if ($stmt->rowCount() > 0) {
                $stmt = $db->prepare('
                    SELECT a.*, d.name as doctor_name, d.specialization 
                    FROM appointments a 
                    JOIN doctors d ON a.doctor_id = d.id 
                    WHERE a.id = ?
                ');
                $stmt->execute([$_GET['id']]);
                $appointment = $stmt->fetch();
                
                echo json_encode([
                    'success' => true,
                    'appointment' => $appointment
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Appointment not found']);
            }
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update appointment']);
        }
        break;

    case 'DELETE':
        requireAuth();
        
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Appointment ID not specified']);
            exit();
        }

        try {
            $stmt = $db->prepare('UPDATE appointments SET status = "cancelled" WHERE id = ? AND user_id = ?');
            $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Appointment not found']);
            }
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to cancel appointment']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
?>
