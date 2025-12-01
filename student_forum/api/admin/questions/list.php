<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';
header('Content-Type: application/json');

// Only admin can access
requireAdmin();

try {
    $pdo = getDBConnection();
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = ($page - 1) * $limit;

    // Get total number of questions
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM questions");
    $total = (int)$stmt->fetch()['total'];
    $total_pages = ceil($total / $limit);

    // Get list of questions with detailed information
    $query = "SELECT 
        q.*,
        u.username,
        u.full_name,
        u.email,
        (SELECT COUNT(*) FROM answers WHERE question_id = q.question_id) as answer_count,
        (SELECT COUNT(*) FROM likes WHERE question_id = q.question_id) as like_count
        FROM questions q
        LEFT JOIN users u ON q.user_id = u.user_id
        ORDER BY q.created_at DESC
        LIMIT ? OFFSET ?";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$limit, $offset]);

    $questions = [];
    while ($row = $stmt->fetch()) {
        $questions[] = [
            'question_id' => (int)$row['question_id'],
            'title' => $row['title'],
            'content' => $row['content'],
            'views' => (int)$row['views'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'user_id' => (int)$row['user_id'],
            'username' => $row['username'],
            'full_name' => $row['full_name'],
            'email' => $row['email'],
            'answer_count' => (int)$row['answer_count'],
            'like_count' => (int)$row['like_count']
        ];
    }

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
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching the question list']);
}
?>

