<?php
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

try {
    $pdo = getDBConnection();

    // Get overall statistics
    $stats_query = "SELECT 
        (SELECT COUNT(*) FROM questions) as total_questions,
        (SELECT COUNT(*) FROM answers) as total_answers,
        (SELECT COUNT(*) FROM users) as total_users";
    $stmt = $pdo->query($stats_query);
    $stats = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_questions' => (int)$stats['total_questions'],
            'total_answers' => (int)$stats['total_answers'],
            'total_users' => (int)$stats['total_users']
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching statistics']);
}
?>


