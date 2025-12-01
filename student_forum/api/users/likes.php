<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'You need to login to view your likes list'
    ]);
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    $pdo = getDBConnection();
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;

    // Get total number of likes received
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM likes l
                            LEFT JOIN questions q ON l.question_id = q.question_id
                            LEFT JOIN answers a ON l.answer_id = a.answer_id
                            WHERE (q.user_id = ? OR a.user_id = ?)");
    $stmt->execute([$user_id, $user_id]);
    $total = (int)$stmt->fetch()['total'];
    $total_pages = ceil($total / $limit);

    // Get list of likes received
    // Get likes for user's questions
    $query_questions = "SELECT l.*, 
                        u.username as liker_username, u.full_name as liker_full_name,
                        q.question_id, q.title as question_title,
                        NULL as answer_id, NULL as answer_content,
                        'question' as like_type
                        FROM likes l
                        INNER JOIN users u ON l.user_id = u.user_id
                        INNER JOIN questions q ON l.question_id = q.question_id
                        WHERE q.user_id = ? AND l.answer_id IS NULL";
    
    // Get likes for user's answers
    $query_answers = "SELECT l.*, 
                      u.username as liker_username, u.full_name as liker_full_name,
                      a.answer_id, a.content as answer_content,
                      a.question_id, NULL as question_title,
                      'answer' as like_type
                      FROM likes l
                      INNER JOIN users u ON l.user_id = u.user_id
                      INNER JOIN answers a ON l.answer_id = a.answer_id
                      WHERE a.user_id = ? AND l.question_id IS NULL";
    
    // Union and sort
    $query = "($query_questions) UNION ($query_answers) ORDER BY created_at DESC LIMIT ? OFFSET ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id, $user_id, $limit, $offset]);
    $likes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'likes' => $likes,
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

