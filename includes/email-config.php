<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/../vendor/autoload.php';

function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USERNAME'];
        $mail->Password   = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $_ENV['SMTP_PORT'];
        $mail->CharSet    = 'UTF-8';

        // Recipients
        $mail->setFrom($_ENV['SMTP_FROM_EMAIL'], $_ENV['SMTP_FROM_NAME']);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Error: {$mail->ErrorInfo}");
        return false;
    }
}

function sendVerificationEmail($email, $name, $token) {
    $verifyUrl = "http://localhost/php/verify-email.php?token=" . urlencode($token);
    
    $subject = "Verify Your Email - Rebah Massage";
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #a0735e 0%, #8b5e3c 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; padding: 15px 30px; background: #a0735e; color: white; text-decoration: none; border-radius: 8px; margin: 20px 0; font-weight: bold; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Welcome to Rebah Massage!</h1>
            </div>
            <div class='content'>
                <p>Hello <strong>{$name}</strong>,</p>
                <p>Thank you for registering at Rebah Massage. To activate your account, please verify your email by clicking the button below:</p>
                <div style='text-align: center;'>
                    <a href='{$verifyUrl}' class='button'>Verify Email</a>
                </div>
                <p>Or copy and paste the following link into your browser:</p>
                <p style='background: #e9ecef; padding: 10px; border-radius: 5px; word-break: break-all;'>{$verifyUrl}</p>
                <p><strong>The verification link will expire in 24 hours.</strong></p>
                <p>If you did not register at Rebah Massage, please ignore this email.</p>
            </div>
            <div class='footer'>
                <p>&copy; 2025 Rebah Massage. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $body);
}

function sendPasswordResetEmail($email, $name, $token) {
    $resetUrl = "http://localhost/php/reset-password.php?token=" . urlencode($token);
    
    $subject = "Reset Your Password - Rebah Massage";
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #a0735e 0%, #8b5e3c 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; padding: 15px 30px; background: #a0735e; color: white; text-decoration: none; border-radius: 8px; margin: 20px 0; font-weight: bold; }
            .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 15px 0; border-radius: 5px; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Reset Password</h1>
            </div>
            <div class='content'>
                <p>Hello <strong>{$name}</strong>,</p>
                <p>We received a request to reset your account password. Click the button below to create a new password:</p>
                <div style='text-align: center;'>
                    <a href='{$resetUrl}' class='button'>Reset Password</a>
                </div>
                <p>Or copy and paste the following link into your browser:</p>
                <p style='background: #e9ecef; padding: 10px; border-radius: 5px; word-break: break-all;'>{$resetUrl}</p>
                <div class='warning'>
                    <strong>⚠️ Important:</strong>
                    <ul style='margin: 10px 0 0 0;'>
                        <li>This link will expire in 1 hour</li>
                        <li>If you did not request a password reset, please ignore this email</li>
                        <li>Your password will not change until you click the link above</li>
                    </ul>
                </div>
            </div>
            <div class='footer'>
                <p>&copy; 2025 Rebah Massage. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $body);
}

/**
 * Send booking update notification email
 * Used when admin changes room or therapist
 */
function sendBookingUpdateEmail($email, $customerName, $bookingData, $changesHtml) {
    $bookingDate = date('d M Y', strtotime($bookingData['booking_date']));
    $startTime = date('H:i', strtotime($bookingData['start_time']));
    $endTime = date('H:i', strtotime($bookingData['end_time']));
    
    $subject = "Booking Details Updated - Rebah Massage";
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                margin: 0;
                padding: 0;
                background-color: #f4f4f4;
            }
            .container { 
                max-width: 600px; 
                margin: 20px auto; 
                background: white;
                border-radius: 15px;
                overflow: hidden;
                box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            }
            .header { 
                background: linear-gradient(135deg, #a0735e 0%, #8b5e3c 100%); 
                color: white; 
                padding: 30px; 
                text-align: center; 
            }
            .header h1 {
                margin: 0;
                font-size: 24px;
            }
            .alert-box {
                background: #fff3cd;
                border-left: 4px solid #ffc107;
                padding: 15px;
                margin: 20px 30px;
                border-radius: 5px;
            }
            .alert-box strong {
                color: #856404;
            }
            .content { 
                padding: 30px; 
            }
            .booking-info {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 10px;
                margin: 20px 0;
            }
            .booking-info h3 {
                margin-top: 0;
                color: #8b5e3c;
                border-bottom: 2px solid #a0735e;
                padding-bottom: 10px;
            }
            .info-row {
                display: flex;
                padding: 8px 0;
                border-bottom: 1px solid #e9ecef;
            }
            .info-label {
                font-weight: 600;
                min-width: 120px;
                color: #666;
            }
            .info-value {
                color: #333;
            }
            .changes-table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
                background: white;
                border-radius: 8px;
                overflow: hidden;
            }
            .changes-table th {
                background: #8b5e3c;
                color: white;
                padding: 12px;
                text-align: left;
                font-weight: 600;
            }
            .changes-table td {
                padding: 10px;
                border-bottom: 1px solid #e9ecef;
            }
            .old-value {
                color: #dc3545;
                text-decoration: line-through;
            }
            .new-value {
                color: #28a745;
                font-weight: bold;
            }
            .footer { 
                text-align: center; 
                padding: 20px;
                background: #f8f9fa;
                color: #666; 
                font-size: 12px; 
            }
            .support-box {
                background: #e7f3ff;
                border-left: 4px solid #2196F3;
                padding: 15px;
                margin: 20px 0;
                border-radius: 5px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔔 Booking Details Updated</h1>
            </div>
            
            <div class='alert-box'>
                <strong>⚠️ Important Notice</strong><br>
                There have been changes to your booking at Rebah Massage. Please review the details below.
            </div>
            
            <div class='content'>
                <p>Hello <strong>{$customerName}</strong>,</p>
                <p>Your booking has been updated by our admin for operational reasons.</p>
                
                <div class='booking-info'>
                    <h3>📋 Booking Information</h3>
                    <div class='info-row'>
                        <div class='info-label'>Branch:</div>
                        <div class='info-value'>{$bookingData['branch_name']}</div>
                    </div>
                    <div class='info-row'>
                        <div class='info-label'>Category:</div>
                        <div class='info-value'>{$bookingData['category_name']}</div>
                    </div>
                    <div class='info-row'>
                        <div class='info-label'>Date:</div>
                        <div class='info-value'>{$bookingDate}</div>
                    </div>
                    <div class='info-row'>
                        <div class='info-label'>Time:</div>
                        <div class='info-value'>{$startTime} - {$endTime}</div>
                    </div>
                    <div class='info-row'>
                        <div class='info-label'>Duration:</div>
                        <div class='info-value'>{$bookingData['duration']} minutes</div>
                    </div>
                </div>
                
                <h3 style='color: #8b5e3c;'>🔄 Changes Made:</h3>
                <table class='changes-table'>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Previous</th>
                            <th>Updated To</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$changesHtml}
                    </tbody>
                </table>
                
                <div class='support-box'>
                    <strong>💡 Note:</strong><br>
                    Your booking schedule and price remain the same. Only the room and/or therapist have been changed to ensure the best service for you.
                </div>
                
                <p>If you have any questions or concerns regarding these changes, please contact us immediately.</p>
                
                <p style='margin-top: 30px;'>Thank you for your understanding.</p>
                <p><strong>Rebah Massage Team</strong></p>
            </div>
            
            <div class='footer'>
                <p><strong>Rebah Massage</strong></p>
                <p>Email: massagerebah@gmail.com | Phone: +62 822-9999-4263 (Menteng) +62 822-9999-4259 (Fatmawati)</p>
                <p>&copy; 2025 Rebah Massage. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $body);
}
?>