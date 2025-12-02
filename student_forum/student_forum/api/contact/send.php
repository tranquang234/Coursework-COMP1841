<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/email.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit();
}

// Get data from form
$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$content = trim($_POST['content'] ?? '');

// Validation
if (empty($full_name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please enter your full name'], JSON_UNESCAPED_UNICODE);
    exit();
}

if (empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please enter your email'], JSON_UNESCAPED_UNICODE);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email'], JSON_UNESCAPED_UNICODE);
    exit();
}

if (empty($content)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please enter your feedback'], JSON_UNESCAPED_UNICODE);
    exit();
}

// Prepare email content
$admin_email = ADMIN_EMAIL;
$site_name = SITE_NAME;
$subject = "[$site_name] Contact - Feedback from: $full_name";

$message = "You have received contact - feedback from the forum:\n\n";
$message .= "Full Name: $full_name\n";
$message .= "Email: $email\n";
$message .= "Content:\n$content\n\n";
$message .= "---\n";
$message .= "Time: " . date('d/m/Y H:i:s') . "\n";
$message .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "\n";

// Send email via Gmail SMTP
try {
    // Check if Gmail app password is configured
    if (empty(SMTP_PASSWORD)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Gmail app password not configured. Please contact administrator.'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    // Send email via Gmail SMTP
    $mail_sent = sendEmailViaGmail($admin_email, $subject, $message, $email);
    
    if ($mail_sent) {
        echo json_encode([
            'success' => true,
            'message' => 'Thank you for contacting us! We will respond as soon as possible.'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        // Log error
        error_log('Failed to send contact email to: ' . $admin_email);
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while sending email. Please check Gmail SMTP configuration or try again later.'
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    error_log('Contact email error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>

