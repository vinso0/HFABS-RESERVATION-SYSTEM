<?php
// Simple email test script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Email Configuration Test</h1>";

// Load email config
$emailConfig = require __DIR__ . '/backend/config/email.php';

echo "<h2>Configuration:</h2>";
echo "<pre>";
print_r([
    'host' => $emailConfig['host'],
    'username' => $emailConfig['username'],
    'port' => $emailConfig['port'],
    'encryption' => $emailConfig['encryption'],
    'from_email' => $emailConfig['from_email'],
    'from_name' => $emailConfig['from_name']
]);
echo "</pre>";

// Test PHPMailer
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/SMTP.php';
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

echo "<h2>Testing PHPMailer...</h2>";

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = $emailConfig['host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $emailConfig['username'];
    $mail->Password   = $emailConfig['password'];
    $mail->SMTPSecure = $emailConfig['encryption'] === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = $emailConfig['port'];
    
    // Enable debugging
    $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
    
    echo "<h3>SMTP Connection Test:</h3>";
    echo "<pre>";
    
    // Test sending email directly
    $mail->setFrom($emailConfig['from_email'], $emailConfig['from_name']);
    $mail->addAddress($emailConfig['username'], 'Test Recipient');
    $mail->Subject = 'Test Email - HFABS';
    $mail->Body = 'This is a test email from HFABS password reset system.';
    
    if ($mail->send()) {
        echo "✅ Test email sent successfully!";
    } else {
        echo "❌ Test email failed: " . $mail->ErrorInfo;
    }
    
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<h3>Exception Details:</h3>";
    echo "<pre>";
    echo "Error: " . $e->getMessage() . "\n";
    echo "SMTP Error: " . $mail->ErrorInfo . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString();
    echo "</pre>";
}

echo "<h2>PHP Mail Configuration:</h2>";
echo "<pre>";
echo "sendmail_path: " . ini_get('sendmail_path') . "\n";
echo "SMTP: " . ini_get('SMTP') . "\n";
echo "smtp_port: " . ini_get('smtp_port') . "\n";
echo "mail.add_x_header: " . ini_get('mail.add_x_header') . "\n";
echo "</pre>";

?>
