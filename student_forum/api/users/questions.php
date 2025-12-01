<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'You need to login to view your questions list'
    ]);
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    $pdo = getDBConnection();
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;

    // Get total number of user's questions
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM questions WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total = (int)$stmt->fetch()['total'];
    $total_pages = ceil($total / $limit);

    // Get list of user's questions (including images)
    $query = "SELECT q.*, m.module_name, m.module_code,
              (SELECT COUNT(*) FROM answers WHERE question_id = q.question_id) as answer_count,
              (SELECT COUNT(*) FROM likes WHERE question_id = q.question_id) as like_count
              FROM questions q
              LEFT JOIN modules m ON q.module_id = m.module_id
              WHERE q.user_id = ?
              ORDER BY q.created_at DESC
              LIMIT ? OFFSET ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id, $limit, $offset]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'questions' => $questions,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total' => $total,
            'limit' => $limit
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error'
    ]);
}
?>

