<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$question_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($question_id <= 0) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question Details - Student Forum</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <h1><i class="fas fa-graduation-cap"></i> Student Forum</h1>
            </div>
            <div class="nav-menu">
                <?php if (isLoggedIn()): ?>
                    <?php $user = getCurrentUser(); ?>
                    <span class="user-info"><i class="fas fa-user-circle"></i> Hello, <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></span>
                    <?php if (isAdmin()): ?>
                        <a href="admin.php" class="btn btn-warning"><i class="fas fa-cog"></i> Admin</a>
                    <?php endif; ?>
                    <a href="index.php" class="btn btn-secondary"><i class="fas fa-home"></i> Home</a>
                    <a href="profile.php" class="btn btn-secondary"><i class="fas fa-user"></i> Profile</a>
                    <a href="#" onclick="logout()" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="index.php" class="btn btn-secondary"><i class="fas fa-home"></i> Home</a>
                    <a href="login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="register.php" class="btn btn-secondary"><i class="fas fa-user-plus"></i> Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="content-wrapper">
            <div class="main-content">
                <div id="question-detail">
                    <!-- Question details will be loaded by JavaScript -->
                </div>

                <div id="answers-section">
                    <!-- Answer list will be loaded by JavaScript -->
                </div>

                <?php if (isLoggedIn()): ?>
                <div class="answer-form-section">
                    <h3><i class="fas fa-reply"></i> Answer Question</h3>
                    <form id="answerForm">
                        <div class="form-group">
                            <label for="answerContent"><i class="fas fa-align-left"></i> Answer Content:</label>
                            <textarea id="answerContent" name="content" rows="5" placeholder="Enter your answer..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit Answer</button>
                    </form>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <a href="login.php">Login</a> to answer this question.
                </div>
                <?php endif; ?>
            </div>

            <?php include 'includes/sidebar.php'; ?>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="confirm-modal">
        <div class="confirm-modal-content">
            <div class="confirm-modal-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="confirm-modal-title" id="confirmModalTitle">Confirm</h3>
            <p class="confirm-modal-message" id="confirmModalMessage"></p>
            <div class="confirm-modal-actions">
                <button class="confirm-modal-btn confirm-modal-btn-cancel" id="confirmModalCancel">Cancel</button>
                <button class="confirm-modal-btn confirm-modal-btn-confirm" id="confirmModalConfirm">Confirm</button>
            </div>
        </div>
    </div>

    <!-- Edit Answer Modal -->
    <div id="editAnswerModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditAnswerModal()">&times;</span>
            <h2><i class="fas fa-edit"></i> Edit Answer</h2>
            <form id="editAnswerForm" onsubmit="return false;">
                <div class="form-group">
                    <label for="editAnswerContent"><i class="fas fa-align-left"></i> Content: <span style="color: red;">*</span></label>
                    <textarea id="editAnswerContent" name="content" rows="6" placeholder="Enter answer content..." required></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-primary" onclick="submitEditAnswer()"><i class="fas fa-save"></i> Save Changes</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditAnswerModal()"><i class="fas fa-times"></i> Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const questionId = <?php echo $question_id; ?>;
        <?php if (isLoggedIn()): ?>
        currentUserId = <?php echo $_SESSION['user_id']; ?>;
        <?php else: ?>
        currentUserId = null;
        <?php endif; ?>
    </script>
    <script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script src="assets/js/question.js?v=<?php echo time(); ?>"></script>
</body>
</html>

