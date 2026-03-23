<?php

// Load PHPMailer classes
require_once __DIR__ . '/../../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../../../vendor/phpmailer/phpmailer/src/SMTP.php';
require_once __DIR__ . '/../../../vendor/phpmailer/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private $mailer;
    
    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->setupSMTP();
    }
    
    private function setupSMTP()
    {
        // Load email configuration
        $emailConfig = require __DIR__ . '/../../config/email.php';
        
        error_log("[EmailService] Setting up SMTP with config: " . print_r([
            'host' => $emailConfig['host'],
            'username' => $emailConfig['username'],
            'port' => $emailConfig['port'],
            'encryption' => $emailConfig['encryption']
        ], true));
        
        // Enable verbose debugging
        $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER; // Enable verbose debug output
        $this->mailer->Debugoutput = function($str, $level) {
            error_log("[EmailService] SMTP Debug [$level]: $str");
        };
        
        // SMTP configuration
        $this->mailer->isSMTP();
        $this->mailer->Host       = $emailConfig['host'];
        $this->mailer->SMTPAuth   = true;
        $this->mailer->Username   = $emailConfig['username'];
        $this->mailer->Password   = $emailConfig['password'];
        $this->mailer->SMTPSecure = $emailConfig['encryption'] === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
        $this->mailer->Port       = $emailConfig['port'];
        
        // Set sender
        $this->mailer->setFrom($emailConfig['from_email'], $emailConfig['from_name']);
        $this->mailer->addReplyTo($emailConfig['reply_to'], $emailConfig['from_name']);
        
        error_log("[EmailService] SMTP setup completed");
    }
    
    public function sendPasswordResetEmail($userEmail, $userName, $resetToken, $userRole = 'customer')
    {
        try {
            error_log("[EmailService] Starting password reset email send to: " . $userEmail);
            error_log("[EmailService] User role: " . $userRole);
            
            $resetLink = "https://undappled-bea-schemeful.ngrok-free.dev/HFABS/frontend/views/reset-password.html?token=" . urlencode($resetToken) . "&user_type=" . urlencode($userRole);
            error_log("[EmailService] Generated reset link: " . $resetLink);
            
            $this->mailer->addAddress($userEmail, $userName);
            $this->mailer->Subject = 'Password Reset Request - Happy Face & Body Spa';
            
            // HTML email template
            $this->mailer->isHTML(true);
            $this->mailer->Body = $this->getPasswordResetTemplate($userName, $resetLink);
            $this->mailer->AltBody = $this->getPasswordResetTextTemplate($userName, $resetLink);
            
            error_log("[EmailService] Attempting to send email...");
            $result = $this->mailer->send();
            
            if ($result) {
                error_log("[EmailService] Password reset email sent successfully to: " . $userEmail);
            } else {
                error_log("[EmailService] Email send failed - no error thrown but result is false");
            }
            
            return $result;
            
        } catch (Exception $e) {
            $errorMessage = "Email sending failed: " . $e->getMessage();
            error_log("[EmailService] ERROR: " . $errorMessage);
            error_log("[EmailService] SMTP Error: " . $this->mailer->ErrorInfo);
            error_log("[EmailService] Full exception: " . $e->getTraceAsString());
            return false;
        } catch (\Throwable $e) {
            $errorMessage = "Unexpected error in email sending: " . $e->getMessage();
            error_log("[EmailService] FATAL ERROR: " . $errorMessage);
            error_log("[EmailService] Full exception: " . $e->getTraceAsString());
            return false;
        }
    }
    
    public function sendEmailVerificationOTP($userEmail, $userName, $otpCode)
    {
        try {
            error_log("[EmailService] Starting OTP email send to: " . $userEmail);
            error_log("[EmailService] OTP Code: " . $otpCode);
            
            $this->mailer->addAddress($userEmail, $userName);
            $this->mailer->Subject = 'Email Verification - Happy Face & Body Spa';
            
            // HTML email template
            $this->mailer->isHTML(true);
            $this->mailer->Body = $this->getOTPTemplate($userName, $otpCode);
            $this->mailer->AltBody = $this->getOTPTextTemplate($userName, $otpCode);
            
            error_log("[EmailService] Attempting to send OTP email...");
            $result = $this->mailer->send();
            
            if ($result) {
                error_log("[EmailService] OTP email sent successfully to: " . $userEmail);
            } else {
                error_log("[EmailService] OTP email send failed - no error thrown but result is false");
            }
            
            return $result;
            
        } catch (Exception $e) {
            $errorMessage = "OTP email sending failed: " . $e->getMessage();
            error_log("[EmailService] ERROR: " . $errorMessage);
            error_log("[EmailService] SMTP Error: " . $this->mailer->ErrorInfo);
            error_log("[EmailService] Full exception: " . $e->getTraceAsString());
            return false;
        } catch (\Throwable $e) {
            $errorMessage = "Unexpected error in OTP email sending: " . $e->getMessage();
            error_log("[EmailService] FATAL ERROR: " . $errorMessage);
            error_log("[EmailService] Full exception: " . $e->getTraceAsString());
            return false;
        }
    }
    
    private function getPasswordResetTemplate($userName, $resetLink)
    {
        error_log("[EmailService] Email template reset link: " . $resetLink);
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset=\"UTF-8\">
            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
            <title>Password Reset - Happy Face & Body Spa</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #f8f9fa; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
                .content { background: #ffffff; padding: 30px; border-radius: 0 0 8px 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .button { display: inline-block; background: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class=\"container\">
                <div class=\"header\">
                    <h2>Happy Face & Body Spa</h2>
                    <p>Password Reset Request</p>
                </div>
                <div class=\"content\">
                    <p>Hi " . htmlspecialchars($userName) . ",</p>
                    <p>You requested a password reset for your account. Click the button below to reset your password:</p>
                    <p><a href=\"" . htmlspecialchars($resetLink) . "\" class=\"button\">Reset Password</a></p>
                    <p>If you didn't request this password reset, you can safely ignore this email.</p>
                    <p>This link will expire in 1 hour for security reasons.</p>
                </div>
                <div class=\"footer\">
                    <p>&copy; 2026 Happy Face & Body Spa. All rights reserved.</p>
                    <p>This is an automated message, please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function getPasswordResetTextTemplate($userName, $resetLink)
    {
        return "
        Happy Face & Body Spa - Password Reset Request
        
        Hi {$userName},
        
        We received a request to reset your password for your Happy Face & Body Spa account.
        
        Please visit this link to reset your password:
        {$resetLink}
        
        This link will expire in 1 hour for security reasons.
        
        If you didn't request this password reset, please ignore this email. Your password will remain unchanged.
        
        Best regards,
        The Happy Face & Body Spa Team
        
        © 2026 Happy Face & Body Spa. All rights reserved.
        ";
    }
    
    private function getOTPTemplate($userName, $otpCode)
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset=\"UTF-8\">
            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
            <title>Email Verification - Happy Face & Body Spa</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #f8f9fa; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
                .content { background: #ffffff; padding: 30px; border-radius: 0 0 8px 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .otp-box { background: #f8f9fa; border: 2px dashed #007bff; padding: 20px; margin: 20px 0; text-align: center; border-radius: 8px; }
                .otp-code { font-size: 32px; font-weight: bold; color: #007bff; letter-spacing: 5px; margin: 10px 0; }
                .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class=\"container\">
                <div class=\"header\">
                    <h2>Happy Face & Body Spa</h2>
                    <p>Email Verification</p>
                </div>
                <div class=\"content\">
                    <p>Hi " . htmlspecialchars($userName) . ",</p>
                    <p>Thank you for registering with Happy Face & Body Spa! To complete your registration, please use the verification code below:</p>
                    
                    <div class=\"otp-box\">
                        <p>Your verification code is:</p>
                        <div class=\"otp-code\">" . htmlspecialchars($otpCode) . "</div>
                    </div>
                    
                    <p>This code will expire in 10 minutes for security reasons.</p>
                    <p>If you didn't request this verification, please ignore this email.</p>
                </div>
                <div class=\"footer\">
                    <p>&copy; 2026 Happy Face & Body Spa. All rights reserved.</p>
                    <p>This is an automated message, please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function getOTPTextTemplate($userName, $otpCode)
    {
        return "
        Happy Face & Body Spa - Email Verification
        
        Hi {$userName},
        
        Thank you for registering with Happy Face & Body Spa! 
        To complete your registration, please use this verification code:
        
        VERIFICATION CODE: {$otpCode}
        
        This code will expire in 10 minutes for security reasons.
        
        If you didn't request this verification, please ignore this email.
        
        Best regards,
        The Happy Face & Body Spa Team
        
        © 2026 Happy Face & Body Spa. All rights reserved.
        ";
    }
}