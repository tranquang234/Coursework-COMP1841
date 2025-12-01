<?php
require_once __DIR__ . '/../config/database.php';

// Check LogIn
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Retrieve user information
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    try {
        $pdo = getDBConnection();
        $user_id = $_SESSION['user_id'];
        
        if (empty($user_id)) {
            return null;
        }
        
        $stmt = $pdo->prepare("SELECT user_id, username, email, full_name, role FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        return $user ? $user : null;
    } catch (PDOException $e) {
        error_log('Error in getCurrentUser(): ' . $e->getMessage());
        return null;
    } catch (Exception $e) {
        error_log('Error in getCurrentUser(): ' . $e->getMessage());
        return null;
    }
}

// Check admin authorization
function isAdmin() {
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

// Check teacher authorization
function isTeacher() {
    $user = getCurrentUser();
    return $user && ($user['role'] === 'teacher' || $user['role'] === 'admin');
}

// LogIn Request
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit();
    }
}

// Admin Request
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /index.php');
        exit();
    }
}
?>


