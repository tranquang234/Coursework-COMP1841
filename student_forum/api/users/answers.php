<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'You need to login to view your answers list'
    ]);
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    $pdo = getDBConnection();
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;

    // Get total number of user's answers
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM answers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total = (int)$stmt->fetch()['total'];
    $total_pages = ceil($total / $limit);

    // Get list of user's answers with question information
    $query = "SELECT a.*, q.title as question_title, q.question_id,
              (SELECT COUNT(*) FROM comments WHERE answer_id = a.answer_id) as comment_count,
              (SELECT COUNT(*) FROM likes WHERE answer_id = a.answer_id) as like_count
              FROM answers a
              LEFT JOIN questions q ON a.question_id = q.question_id
              WHERE a.user_id = ?
              ORDER BY a.created_at DESC
              LIMIT ? OFFSET ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id, $limit, $offset]);
    $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'answers' => $answers,
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


