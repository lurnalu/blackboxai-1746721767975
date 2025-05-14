<?php
require_once '../config.php';
header('Content-Type: application/json');

// Check if user is authenticated
requireAuth();

// Configure upload settings
$uploadDir = '../uploads/';
$allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
$allowedDocTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
$maxFileSize = 5 * 1024 * 1024; // 5MB

// Create uploads directory if it doesn't exist
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Create subdirectories for different file types
$imageDir = $uploadDir . 'images/';
$docDir = $uploadDir . 'documents/';

if (!file_exists($imageDir)) {
    mkdir($imageDir, 0755, true);
}
if (!file_exists($docDir)) {
    mkdir($docDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_FILES['file'])) {
            throw new Exception('No file uploaded');
        }

        $file = $_FILES['file'];
        $fileType = $file['type'];
        $fileSize = $file['size'];
        $fileName = basename($file['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Validate file size
        if ($fileSize > $maxFileSize) {
            throw new Exception('File size exceeds limit');
        }

        // Generate unique filename
        $uniqueName = uniqid() . '_' . $fileName;

        // Handle different file types
        if (in_array($fileType, $allowedImageTypes)) {
            $targetPath = $imageDir . $uniqueName;
            $urlPath = 'uploads/images/' . $uniqueName;
        } elseif (in_array($fileType, $allowedDocTypes)) {
            $targetPath = $docDir . $uniqueName;
            $urlPath = 'uploads/documents/' . $uniqueName;
        } else {
            throw new Exception('File type not allowed');
        }

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Store file info in database
            $stmt = $db->prepare('
                INSERT INTO uploads (filename, filepath, filetype, filesize, uploaded_by) 
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $fileName,
                $urlPath,
                $fileType,
                $fileSize,
                $_SESSION['user_id']
            ]);

            echo json_encode([
                'success' => true,
                'file' => [
                    'name' => $fileName,
                    'url' => $urlPath,
                    'type' => $fileType,
                    'size' => $fileSize
                ]
            ]);
        } else {
            throw new Exception('Failed to move uploaded file');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

// Add upload table to database if it doesn't exist
function createUploadsTable() {
    global $db;
    
    $sql = "
    CREATE TABLE IF NOT EXISTS uploads (
        id INT PRIMARY KEY AUTO_INCREMENT,
        filename VARCHAR(255) NOT NULL,
        filepath VARCHAR(255) NOT NULL,
        filetype VARCHAR(100) NOT NULL,
        filesize INT NOT NULL,
        uploaded_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (uploaded_by) REFERENCES users(id)
    )";
    
    try {
        $db->exec($sql);
    } catch (PDOException $e) {
        error_log('Failed to create uploads table: ' . $e->getMessage());
    }
}

// Create uploads table when the file is first accessed
createUploadsTable();
?>
