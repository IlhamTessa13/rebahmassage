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
    
    $subject = "Verifikasi Email Anda - Rebah Massage";
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
                <h1>Selamat Datang di Rebah Massage!</h1>
            </div>
            <div class='content'>
                <p>Halo <strong>{$name}</strong>,</p>
                <p>Terima kasih telah mendaftar di Rebah Massage. Untuk mengaktifkan akun Anda, silakan verifikasi email Anda dengan mengklik tombol di bawah ini:</p>
                <div style='text-align: center;'>
                    <a href='{$verifyUrl}' class='button'>Verifikasi Email</a>
                </div>
                <p>Atau salin dan tempel link berikut di browser Anda:</p>
                <p style='background: #e9ecef; padding: 10px; border-radius: 5px; word-break: break-all;'>{$verifyUrl}</p>
                <p><strong>Link verifikasi akan kadaluarsa dalam 24 jam.</strong></p>
                <p>Jika Anda tidak mendaftar di Rebah Massage, abaikan email ini.</p>
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
    
    $subject = "Reset Password Anda - Rebah Massage";
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
                <p>Halo <strong>{$name}</strong>,</p>
                <p>Kami menerima permintaan untuk reset password akun Anda. Klik tombol di bawah ini untuk membuat password baru:</p>
                <div style='text-align: center;'>
                    <a href='{$resetUrl}' class='button'>Reset Password</a>
                </div>
                <p>Atau salin dan tempel link berikut di browser Anda:</p>
                <p style='background: #e9ecef; padding: 10px; border-radius: 5px; word-break: break-all;'>{$resetUrl}</p>
                <div class='warning'>
                    <strong>⚠️ Penting:</strong>
                    <ul style='margin: 10px 0 0 0;'>
                        <li>Link ini akan kadaluarsa dalam 1 jam</li>
                        <li>Jika Anda tidak meminta reset password, abaikan email ini</li>
                        <li>Password Anda tidak akan berubah sampai Anda mengklik link di atas</li>
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
 * Digunakan ketika admin mengubah room atau therapist
 */
function sendBookingUpdateEmail($email, $customerName, $bookingData, $changesHtml) {
    $bookingDate = date('d M Y', strtotime($bookingData['booking_date']));
    $startTime = date('H:i', strtotime($bookingData['start_time']));
    $endTime = date('H:i', strtotime($bookingData['end_time']));
    
    $subject = "Perubahan Detail Booking Anda - Rebah Massage";
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
                <h1>🔔 Perubahan Detail Booking</h1>
            </div>
            
            <div class='alert-box'>
                <strong>⚠️ Pemberitahuan Penting</strong><br>
                Terdapat perubahan pada booking Anda di Rebah Massage. Mohon perhatikan detail di bawah ini.
            </div>
            
            <div class='content'>
                <p>Halo <strong>{$customerName}</strong>,</p>
                <p>Booking Anda telah diperbarui oleh admin karena alasan operasional.</p>
                
                <div class='booking-info'>
                    <h3>📋 Informasi Booking</h3>
                    <div class='info-row'>
                        <div class='info-label'>Cabang:</div>
                        <div class='info-value'>{$bookingData['branch_name']}</div>
                    </div>
                    <div class='info-row'>
                        <div class='info-label'>Kategori:</div>
                        <div class='info-value'>{$bookingData['category_name']}</div>
                    </div>
                    <div class='info-row'>
                        <div class='info-label'>Tanggal:</div>
                        <div class='info-value'>{$bookingDate}</div>
                    </div>
                    <div class='info-row'>
                        <div class='info-label'>Waktu:</div>
                        <div class='info-value'>{$startTime} - {$endTime}</div>
                    </div>
                    <div class='info-row'>
                        <div class='info-label'>Durasi:</div>
                        <div class='info-value'>{$bookingData['duration']} menit</div>
                    </div>
                </div>
                
                <h3 style='color: #8b5e3c;'>🔄 Detail Perubahan:</h3>
                <table class='changes-table'>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Sebelumnya</th>
                            <th>Menjadi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$changesHtml}
                    </tbody>
                </table>
                
                <div class='support-box'>
                    <strong>💡 Catatan:</strong><br>
                    Jadwal dan harga booking Anda tetap sama. Hanya room dan/atau therapist yang berubah untuk memastikan layanan terbaik bagi Anda.
                </div>
                
                <p>Jika Anda memiliki pertanyaan atau keberatan terkait perubahan ini, silakan hubungi kami segera.</p>
                
                <p style='margin-top: 30px;'>Terima kasih atas pengertian Anda.</p>
                <p><strong>Tim Rebah Massage</strong></p>
            </div>
            
            <div class='footer'>
                <p><strong>Rebah Massage</strong></p>
                <p>Email: massagerebah@gmail.com | Phone: +62 822-9999-4263(Menteng) +62 822-9999-4259(Fatmawati) </p>
                <p>&copy; 2025 Rebah Massage. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $body);
}
?>