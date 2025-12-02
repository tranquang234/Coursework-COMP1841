<?php
/**
 * Helper function to send email via Gmail SMTP
 * Uses PHPMailer if available, otherwise falls back to SMTP socket
 */

function sendEmailViaGmail($to, $subject, $message, $replyTo = null) {
    // Try to use PHPMailer if available
    $phpmailer_path = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($phpmailer_path)) {
        require_once $phpmailer_path;
        return sendEmailWithPHPMailer($to, $subject, $message, $replyTo);
    }
    
    // Fallback: Use SMTP socket directly
    return sendEmailWithSMTP($to, $subject, $message, $replyTo);
}

function sendEmailWithPHPMailer($to, $subject, $message, $replyTo = null) {
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        
        if ($replyTo) {
            $mail->addReplyTo($replyTo);
        }
        
        // Content
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}

function sendEmailWithSMTP($to, $subject, $message, $replyTo = null) {
    $smtp_host = SMTP_HOST;
    $smtp_port = SMTP_PORT;
    $smtp_user = SMTP_USERNAME;
    $smtp_pass = SMTP_PASSWORD;
    
    if (empty($smtp_pass)) {
        error_log('SMTP password not configured');
        return false;
    }
    
    $socket = null;
    try {
        // Connect to SMTP server with TLS
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
        
        $socket = @stream_socket_client(
            "tcp://$smtp_host:$smtp_port",
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        if (!$socket) {
            error_log("SMTP Connection failed: $errstr ($errno)");
            return false;
        }
        
        // Read initial response
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) != '220') {
            error_log("SMTP Error: $response");
            fclose($socket);
            return false;
        }
        
        // EHLO
        fwrite($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
        
        // STARTTLS
        fwrite($socket, "STARTTLS\r\n");
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) != '220') {
            error_log("STARTTLS failed: $response");
            fclose($socket);
            return false;
        }
        
        // Enable TLS
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            error_log("TLS encryption failed");
            fclose($socket);
            return false;
        }
        
        // EHLO again after TLS
        fwrite($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
        
        // AUTH LOGIN
        fwrite($socket, "AUTH LOGIN\r\n");
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) != '334') {
            error_log("AUTH LOGIN failed: $response");
            fclose($socket);
            return false;
        }
        
        // Send username (base64)
        fwrite($socket, base64_encode($smtp_user) . "\r\n");
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) != '334') {
            error_log("Username authentication failed: $response");
            fclose($socket);
            return false;
        }
        
        // Send password (base64)
        fwrite($socket, base64_encode($smtp_pass) . "\r\n");
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) != '235') {
            error_log("SMTP Authentication failed: $response");
            fclose($socket);
            return false;
        }
        
        // MAIL FROM
        fwrite($socket, "MAIL FROM: <" . SMTP_FROM_EMAIL . ">\r\n");
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) != '250') {
            error_log("MAIL FROM failed: $response");
            fclose($socket);
            return false;
        }
        
        // RCPT TO
        fwrite($socket, "RCPT TO: <$to>\r\n");
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) != '250') {
            error_log("RCPT TO failed: $response");
            fclose($socket);
            return false;
        }
        
        // DATA
        fwrite($socket, "DATA\r\n");
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) != '354') {
            error_log("DATA command failed: $response");
            fclose($socket);
            return false;
        }
        
        // Headers and body
        $email_headers = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
        if ($replyTo) {
            $email_headers .= "Reply-To: $replyTo\r\n";
        }
        $email_headers .= "To: <$to>\r\n";
        $email_headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $email_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $email_headers .= "Content-Transfer-Encoding: 8bit\r\n";
        $email_headers .= "MIME-Version: 1.0\r\n";
        
        // Send email
        fwrite($socket, $email_headers . "\r\n" . $message . "\r\n.\r\n");
        $response = fgets($socket, 515);
        
        if (substr($response, 0, 3) != '250') {
            error_log("SMTP Send failed: $response");
            fclose($socket);
            return false;
        }
        
        // QUIT
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        
        return true;
    } catch (Exception $e) {
        error_log('SMTP Error: ' . $e->getMessage());
        if ($socket) {
            fclose($socket);
        }
        return false;
    }
}
?>

