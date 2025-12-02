<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
header('Content-Type: application/json');

$question_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($question_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid question ID']);
    exit();
}

try {
    $pdo = getDBConnection();

    // Increase views
    $stmt = $pdo->prepare("UPDATE questions SET views = views + 1 WHERE question_id = ?");
    $stmt->execute([$question_id]);

    // Check if module_image column exists
    $has_module_image = false;
    try {
        $checkColumn = $pdo->query("SHOW COLUMNS FROM modules LIKE 'module_image'");
        $has_module_image = $checkColumn->rowCount() > 0;
    } catch (PDOException $e) {
        // If it cannot be checked, assume it does not exist.
        $has_module_image = false;
    }

    // Check if user is logged in
    $current_user_id = null;
    if (isset($_SESSION['user_id'])) {
        $current_user_id = $_SESSION['user_id'];
    }

    // Get question information
    $module_fields = $has_module_image ? 'm.module_name, m.module_code, m.module_image' : 'm.module_name, m.module_code, NULL as module_image';
    $stmt = $pdo->prepare("SELECT q.*, u.username, u.full_name, u.user_id as author_id, {$module_fields},
              (SELECT COUNT(*) FROM answers WHERE question_id = q.question_id) as answer_count,
              (SELECT COUNT(*) FROM likes WHERE question_id = q.question_id) as like_count
              FROM questions q
              LEFT JOIN users u ON q.user_id = u.user_id
              LEFT JOIN modules m ON q.module_id = m.module_id
              WHERE q.question_id = ?");
    $stmt->execute([$question_id]);
    $question = $stmt->fetch();

    if (!$question) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'No question found']);
        exit();
    }

    // Check if current user has liked the question
    $question['is_liked'] = false;
    if ($current_user_id) {
        $like_check = $pdo->prepare("SELECT like_id FROM likes WHERE user_id = ? AND question_id = ? AND answer_id IS NULL");
        $like_check->execute([$current_user_id, $question_id]);
        $question['is_liked'] = $like_check->fetch() !== false;
    }

    // Get the list of answers
    $stmt2 = $pdo->prepare("SELECT a.*, u.username, u.full_name, u.user_id as author_id,
              (SELECT COUNT(*) FROM comments WHERE answer_id = a.answer_id) as comment_count,
              (SELECT COUNT(*) FROM likes WHERE answer_id = a.answer_id) as like_count
              FROM answers a
              LEFT JOIN users u ON a.user_id = u.user_id
              WHERE a.question_id = ?
              ORDER BY a.is_accepted DESC, a.created_at ASC");
    $stmt2->execute([$question_id]);
    $answers = $stmt2->fetchAll();

    // Check if user has liked each answer
    foreach ($answers as &$row) {
        $answer_id = $row['answer_id'];
        $row['is_liked'] = false;
        if ($current_user_id) {
            $answer_like_check = $pdo->prepare("SELECT like_id FROM likes WHERE user_id = ? AND answer_id = ? AND question_id IS NULL");
            $answer_like_check->execute([$current_user_id, $answer_id]);
            $row['is_liked'] = $answer_like_check->fetch() !== false;
        }
        
        // Get comments for each answer
        $stmt3 = $pdo->prepare("SELECT c.*, u.username, u.full_name 
                                 FROM comments c
                                 LEFT JOIN users u ON c.user_id = u.user_id
                                 WHERE c.answer_id = ?
                                 ORDER BY c.created_at ASC");
        $stmt3->execute([$answer_id]);
        $row['comments'] = $stmt3->fetchAll();
    }

    $question['answers'] = $answers;

    echo json_encode([
        'success' => true,
        'question' => $question
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while retrieving question information.']);
}
?>
