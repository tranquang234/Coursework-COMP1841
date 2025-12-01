<?php
// Databaseconnection
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'student_forum');

// Connect database using PDO
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        die("Kết nối thất bại: " . $e->getMessage());
    }
}

// Create session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Emai
define('ADMIN_EMAIL', 'tvmquang123@gmail.com'); // Admin Email to receive
define('SITE_NAME', 'Student Forum');

// Gmail SMTP
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'tvmquang123@gmail.com'); // Email
define('SMTP_PASSWORD', 'etnnejlkehhzewfi'); // Gmail Application Password
define('SMTP_FROM_EMAIL', 'tvmquang123@gmail.com'); // Email used to send
define('SMTP_FROM_NAME', 'Student Forum');

