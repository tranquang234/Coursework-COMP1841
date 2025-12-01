<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
header('Content-Type: application/json');

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức không được phép']);
    exit();
}

$question_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($question_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID câu hỏi không hợp lệ']);
    exit();
}

try {
    $pdo = getDBConnection();

    // Kiểm tra quyền sở hữu hoặc quyền admin
    $stmt = $pdo->prepare("SELECT user_id FROM questions WHERE question_id = ?");
    $stmt->execute([$question_id]);
    $question = $stmt->fetch();

    if (!$question) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy câu hỏi']);
        exit();
    }

    $user_id = $_SESSION['user_id'];

    if ($question['user_id'] != $user_id && !isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa câu hỏi này']);
        exit();
    }

    // Xóa câu hỏi (cascade sẽ xóa các câu trả lời và bình luận liên quan)
    $stmt = $pdo->prepare("DELETE FROM questions WHERE question_id = ?");
    $stmt->execute([$question_id]);

    echo json_encode(['success' => true, 'message' => 'Xóa câu hỏi thành công']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi xóa']);
}
?>

