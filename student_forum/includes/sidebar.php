<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();

    // Get overall statistics
    $stats_query = "SELECT 
        (SELECT COUNT(*) FROM questions) as total_questions,
        (SELECT COUNT(*) FROM answers) as total_answers,
        (SELECT COUNT(*) FROM users) as total_users";
    $stmt = $pdo->query($stats_query);
    $stats = $stmt->fetch();

    // Get latest questions (top 5)
    $recent_query = "SELECT q.question_id, q.title, q.created_at, 
        (SELECT COUNT(*) FROM answers WHERE question_id = q.question_id) as answer_count,
        (SELECT COUNT(*) FROM likes WHERE question_id = q.question_id) as like_count
        FROM questions q
        ORDER BY q.created_at DESC
        LIMIT 5";
    $stmt = $pdo->query($recent_query);
    $recent_questions = $stmt->fetchAll();

    // Get most popular questions (top 5 - by likes and views)
    $popular_query = "SELECT q.question_id, q.title, q.views,
        (SELECT COUNT(*) FROM answers WHERE question_id = q.question_id) as answer_count,
        (SELECT COUNT(*) FROM likes WHERE question_id = q.question_id) as like_count
        FROM questions q
        ORDER BY (q.views + (SELECT COUNT(*) FROM likes WHERE question_id = q.question_id) * 2) DESC
        LIMIT 5";
    $stmt = $pdo->query($popular_query);
    $popular_questions = $stmt->fetchAll();
} catch (PDOException $e) {
    // Handle error - set default values
    $stats = ['total_questions' => 0, 'total_answers' => 0, 'total_users' => 0];
    $recent_questions = [];
    $popular_questions = [];
}
?>

<aside class="sidebar">
    <!-- Overall Statistics -->
    <div class="sidebar-widget" id="stats-widget">
        <h3><i class="fas fa-chart-bar"></i> Statistics</h3>
        <div class="stats-list" id="stats-list">
            <div class="stat-item-small">
                <i class="fas fa-question-circle"></i>
                <span class="stat-label">Questions:</span>
                <span class="stat-value" id="stat-questions"><?php echo number_format($stats['total_questions']); ?></span>
            </div>
            <div class="stat-item-small">
                <i class="fas fa-comments"></i>
                <span class="stat-label">Answers:</span>
                <span class="stat-value" id="stat-answers"><?php echo number_format($stats['total_answers']); ?></span>
            </div>
            <div class="stat-item-small">
                <i class="fas fa-users"></i>
                <span class="stat-label">Members:</span>
                <span class="stat-value" id="stat-users"><?php echo number_format($stats['total_users']); ?></span>
            </div>
        </div>
    </div>

    <!-- Latest Questions -->
    <div class="sidebar-widget">
        <h3><i class="fas fa-clock"></i> Latest Questions</h3>
        <ul class="question-list-sidebar">
            <?php if (empty($recent_questions)): ?>
                <li class="no-items">No questions yet</li>
            <?php else: ?>
                <?php foreach ($recent_questions as $question): ?>
                    <li>
                        <a href="question.php?id=<?php echo $question['question_id']; ?>" class="question-link-sidebar">
                            <?php echo htmlspecialchars($question['title']); ?>
                        </a>
                        <div class="question-meta-sidebar">
                            <span><i class="fas fa-comments"></i> <?php echo $question['answer_count']; ?></span>
                            <span><i class="fas fa-thumbs-up"></i> <?php echo $question['like_count']; ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Popular Questions -->
    <div class="sidebar-widget">
        <h3><i class="fas fa-fire"></i> Popular Questions</h3>
        <ul class="question-list-sidebar">
            <?php if (empty($popular_questions)): ?>
                <li class="no-items">No questions yet</li>
            <?php else: ?>
                <?php foreach ($popular_questions as $question): ?>
                    <li>
                        <a href="question.php?id=<?php echo $question['question_id']; ?>" class="question-link-sidebar">
                            <?php echo htmlspecialchars($question['title']); ?>
                        </a>
                        <div class="question-meta-sidebar">
                            <span><i class="fas fa-eye"></i> <?php echo $question['views']; ?></span>
                            <span><i class="fas fa-comments"></i> <?php echo $question['answer_count']; ?></span>
                            <span><i class="fas fa-thumbs-up"></i> <?php echo $question['like_count']; ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</aside>


