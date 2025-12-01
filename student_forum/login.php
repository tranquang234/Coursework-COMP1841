<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Student Forum</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <h1><i class="fas fa-graduation-cap"></i> Student Forum</h1>
            </div>
            <div class="nav-menu">
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-home"></i> Home</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="auth-container">
            <div class="auth-card">
                <h2>Login</h2>
                <form id="loginForm">
                    <div class="form-group">
                        <label for="username">Username or Email:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div id="error-message" class="alert alert-danger" style="display: none;"></div>
                    <button type="submit" class="btn btn-primary btn-block" style="display:flex; justify-content: center; align-items: center;">Login</button>
                </form>
                <p class="auth-link">Don't have an account? <a href="register.php">Sign up now</a></p>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script src="assets/js/auth.js?v=<?php echo time(); ?>"></script>
</body>
</html>


