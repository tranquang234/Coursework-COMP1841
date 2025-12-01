<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';
header('Content-Type: application/json');

// Only admin can access
requireAdmin();

try {
    $pdo = getDBConnection();

    // Get list of users with statistics
    $query = "SELECT 
        u.user_id,
        u.username,
        u.email,
        u.full_name,
        u.role,
        u.created_at,
        (SELECT COUNT(*) FROM questions WHERE user_id = u.user_id) as questions_count,
        (SELECT COUNT(*) FROM answers WHERE user_id = u.user_id) as answers_count,
        (SELECT COUNT(*) FROM comments WHERE user_id = u.user_id) as comments_count
        FROM users u
        ORDER BY u.created_at DESC";

    $stmt = $pdo->query($query);
    $users = [];

    while ($row = $stmt->fetch()) {
        $users[] = [
            'user_id' => (int)$row['user_id'],
            'username' => $row['username'],
            'email' => $row['email'],
            'full_name' => $row['full_name'],
            'role' => $row['role'],
            'created_at' => $row['created_at'],
            'questions_count' => (int)$row['questions_count'],
            'answers_count' => (int)$row['answers_count'],
            'comments_count' => (int)$row['comments_count']
        ];
    }

    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching the user list']);
}
?>

