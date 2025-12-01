<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');

// Kiểm tra đăng nhập - trả về JSON thay vì redirect cho AJAX
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để đăng câu hỏi'], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức không được phép'], JSON_UNESCAPED_UNICODE);
    exit();
}

// Lấy dữ liệu từ POST (FormData)
// Với multipart/form-data, dữ liệu sẽ có trong $_POST
// Debug: Kiểm tra dữ liệu nhận được
error_log('=== CREATE QUESTION DEBUG ===');
error_log('REQUEST_METHOD: ' . $_SERVER['REQUEST_METHOD']);
error_log('CONTENT_TYPE: ' . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
error_log('POST keys: ' . implode(', ', array_keys($_POST)));
error_log('POST data: ' . print_r($_POST, true));
error_log('FILES keys: ' . implode(', ', array_keys($_FILES)));
error_log('Raw input length: ' . strlen(file_get_contents('php://input')));

// Lấy dữ liệu từ $_POST
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$content = isset($_POST['content']) ? trim($_POST['content']) : '';
$module_id = isset($_POST['module_id']) && !empty($_POST['module_id']) ? (int)$_POST['module_id'] : null;

// Nếu không có trong $_POST, thử đọc từ input stream (fallback)
if (empty($title) && empty($content)) {
    $raw_input = file_get_contents('php://input');
    if (!empty($raw_input)) {
        parse_str($raw_input, $parsed_data);
        if (isset($parsed_data['title'])) {
            $title = trim($parsed_data['title']);
        }
        if (isset($parsed_data['content'])) {
            $content = trim($parsed_data['content']);
        }
        if (isset($parsed_data['module_id']) && !empty($parsed_data['module_id'])) {
            $module_id = (int)$parsed_data['module_id'];
        }
    }
}

$user_id = $_SESSION['user_id'];

error_log('Title: [' . $title . '] (length: ' . strlen($title) . ')');
error_log('Content: [' . substr($content, 0, 100) . '...] (length: ' . strlen($content) . ')');
error_log('============================');

// Kiểm tra dữ liệu
if (strlen($title) === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập tiêu đề'], JSON_UNESCAPED_UNICODE);
    exit();
}

if (strlen($content) === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập nội dung'], JSON_UNESCAPED_UNICODE);
    exit();
}

$image_path = null;

// Xử lý upload ảnh nếu có
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['image'];
    
    // Kiểm tra loại file
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = $file['type'];
    
    if (!in_array($file_type, $allowed_types)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WEBP)'], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    // Kiểm tra kích thước file (tối đa 5MB)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'File ảnh không được vượt quá 5MB'], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    // Tạo thư mục uploads nếu chưa có
    $upload_dir = __DIR__ . '/../../uploads/questions/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Tạo tên file duy nhất
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $file_name = uniqid('question_', true) . '_' . time() . '.' . $file_extension;
    $file_path = $upload_dir . $file_name;
    
    // Di chuyển file vào thư mục uploads
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        // Lưu đường dẫn tương đối (từ root của website)
        $image_path = 'uploads/questions/' . $file_name;
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Không thể upload file ảnh'], JSON_UNESCAPED_UNICODE);
        exit();
    }
}

try {
    $pdo = getDBConnection();
    
    // Kiểm tra các cột có tồn tại không
    $stmt = $pdo->query("SHOW COLUMNS FROM questions");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $has_images_column = in_array('images', $columns);
    $has_module_id_column = in_array('module_id', $columns);
    
    // Xây dựng query INSERT động dựa trên các cột có sẵn
    $fields = ['user_id', 'title', 'content'];
    $values = [$user_id, $title, $content];
    $placeholders = ['?', '?', '?'];
    
    if ($has_images_column) {
        $fields[] = 'images';
        $values[] = $image_path;
        $placeholders[] = '?';
    }
    
    if ($has_module_id_column && $module_id !== null) {
        // Kiểm tra module_id có tồn tại trong bảng modules không
        $checkModule = $pdo->prepare("SELECT module_id FROM modules WHERE module_id = ?");
        $checkModule->execute([$module_id]);
        if ($checkModule->fetch()) {
            $fields[] = 'module_id';
            $values[] = $module_id;
            $placeholders[] = '?';
        }
    }
    
    $sql = "INSERT INTO questions (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
    
    $question_id = $pdo->lastInsertId();
    echo json_encode([
        'success' => true,
        'message' => 'Đăng câu hỏi thành công',
        'question_id' => $question_id,
        'image_path' => $image_path
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    // Xóa file ảnh nếu đã upload nhưng insert database thất bại
    if ($image_path && file_exists(__DIR__ . '/../../' . $image_path)) {
        unlink(__DIR__ . '/../../' . $image_path);
    }
    
    http_response_code(500);
    error_log('Create question error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi đăng câu hỏi'], JSON_UNESCAPED_UNICODE);
}
?>

