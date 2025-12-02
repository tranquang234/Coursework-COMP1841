<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
header('Content-Type: application/json');

requireLogin();

// Accept PUT and POST (POST cho FormData, PUT cho JSON)
if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Check if FormData or JSON
// FormData only working with POST method in PHP
$isFormData = ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['question_id']) || isset($_FILES['image'])));

if ($isFormData) {
    // Handle FormData (POST method)
    $question_id = isset($_POST['question_id']) ? (int)$_POST['question_id'] : 0;
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $module_id = isset($_POST['module_id']) && $_POST['module_id'] !== '' ? (int)$_POST['module_id'] : null;
} else {
    // Handle JSON (PUT method)
    $data = json_decode(file_get_contents('php://input'), true);
    $question_id = isset($data['question_id']) ? (int)$data['question_id'] : 0;
    $title = trim($data['title'] ?? '');
    $content = trim($data['content'] ?? '');
    $module_id = isset($data['module_id']) && $data['module_id'] !== '' ? (int)$data['module_id'] : null;
}

$user_id = $_SESSION['user_id'];

if ($question_id <= 0 || empty($title) || empty($content)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid Data']);
    exit();
}

try {
    $pdo = getDBConnection();

    // Check ownership or admin rights
    $stmt = $pdo->prepare("SELECT user_id, images FROM questions WHERE question_id = ?");
    $stmt->execute([$question_id]);
    $question = $stmt->fetch();

    if (!$question) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Answer not found']);
        exit();
    }

    $user = getCurrentUser();

    if ($question['user_id'] != $user_id && !isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You do not have permission to edit this question']);
        exit();
    }

    // Process image uploads if have any
    $imagePath = $question['images']; // Keep old photo if no upload
    
    if ($isFormData && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        
        // Check file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $file['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WEBP)']);
            exit();
        }
        
        // Check file size (max 5MB)
        $max_size = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $max_size) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Image files must not exceed 5MB']);
            exit();
        }
        
        // Create uploads folder if it doesn't exist (use absolute path)
        $upload_dir = __DIR__ . '/../../uploads/questions/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Create unique file names
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $file_name = uniqid() . '_' . time() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        
        // Upload file
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            // Delete old photos if any (use absolute path)
            if ($question['images'] && !empty($question['images'])) {
                $old_image_path = __DIR__ . '/../../' . $question['images'];
                if (file_exists($old_image_path)) {
                    @unlink($old_image_path);
                }
            }
            // Save relative path to database (like create.php)
            $imagePath = 'uploads/questions/' . $file_name;
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to upload image. Please check file write permissions.']);
            exit();
        }
    }
    
    // Update question
    if ($module_id !== null) {
        $stmt = $pdo->prepare("UPDATE questions SET title = ?, content = ?, module_id = ?, images = ? WHERE question_id = ?");
        $stmt->execute([$title, $content, $module_id, $imagePath, $question_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE questions SET title = ?, content = ?, images = ? WHERE question_id = ?");
        $stmt->execute([$title, $content, $imagePath, $question_id]);
    }

    echo json_encode(['success' => true, 'message' => 'Update question successfully']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating.']);
}
?>

