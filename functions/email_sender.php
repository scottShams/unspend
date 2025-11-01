<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

// Load environment variables
require_once __DIR__ . '/../config/env.php';

class EmailSender {
    private $mailer;

    public function __construct() {
        $this->mailer = new PHPMailer(true);

        // Server settings
        $this->mailer->isSMTP();
        $this->mailer->Host = Env::get('SMTP_HOST', 'sandbox.smtp.mailtrap.io');
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = Env::get('SMTP_USERNAME', '9cb47cf0a6ad40');
        $this->mailer->Password = Env::get('SMTP_PASSWORD', 'a7b6a7bfd88f9b');
        $this->mailer->SMTPSecure = Env::get('SMTP_ENCRYPTION', 'tls') === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
        $this->mailer->Port = (int) Env::get('SMTP_PORT', 587);

        // Default settings
        $this->mailer->setFrom('noreply@unspend.me', 'unSpend');
        $this->mailer->isHTML(true);
    }

    public function sendVerificationEmail($email, $name, $verificationToken) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email, $name);

            $this->mailer->Subject = 'Verify Your unSpend Account';

            $verificationUrl = "https://" . $_SERVER['HTTP_HOST'] . "/verify.php?token=" . $verificationToken;

            $this->mailer->Body = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #5b21b6; color: white; padding: 20px; text-align: center; }
                        .content { padding: 30px; background: #f9f9f9; }
                        .button { display: inline-block; padding: 12px 24px; background: #5b21b6; color: white; text-decoration: none; border-radius: 5px; }
                        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>Welcome to unSpend!</h1>
                        </div>
                        <div class='content'>
                            <h2>Hi {$name},</h2>
                            <p>Thank you for registering with unSpend! To complete your account setup and start analyzing your spending habits, please verify your email address.</p>

                            <p style='text-align: center; margin: 30px 0;'>
                                <a href='{$verificationUrl}' class='button'>Verify My Email</a>
                            </p>

                            <p>If the button above doesn't work, you can copy and paste this link into your browser:</p>
                            <p><a href='{$verificationUrl}'>{$verificationUrl}</a></p>

                            <p>This verification link will expire in 24 hours for security reasons.</p>

                            <p>If you didn't create an account with unSpend, please ignore this email.</p>
                        </div>
                        <div class='footer'>
                            <p>&copy; 2024 unSpend. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";

            $this->mailer->AltBody = "Hi {$name},\n\nThank you for registering with unSpend! To complete your account setup, please verify your email by clicking this link: {$verificationUrl}\n\nThis link will expire in 24 hours.\n\nIf you didn't create an account, please ignore this email.";

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $this->mailer->ErrorInfo);
            return false;
        }
    }
}
?>