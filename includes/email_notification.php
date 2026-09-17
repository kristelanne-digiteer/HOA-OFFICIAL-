<?php
/**
 * Email Notification System for Support Tickets
 * * This file handles sending email notifications to homeowners
 * when admins reply to, resolve, or reject support tickets.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send ticket notification email
 * * @param string $to_email Recipient email address
 * @param string $homeowner_name Recipient name
 * @param string $ticket_subject Original ticket subject
 * @param string $action Type of notification: 'reply', 'resolved', 'rejected'
 * @param string $message Admin's reply or rejection reason
 * @return bool Success status
 */
function send_ticket_notification($to_email, $homeowner_name, $ticket_subject, $action, $message = '') {
    
    // Dynamic vendor autoload fallback check para hindi masira ang path kahit saan folder i-include
    $vendor_paths = [
        __DIR__ . '/../vendor/autoload.php',
        __DIR__ . '/../../vendor/autoload.php',
        __DIR__ . '/vendor/autoload.php'
    ];

    foreach ($vendor_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
    
    // ───────────────────────────────────────────────────────────
    // OPTION 1: Using PHPMailer (RECOMMENDED for production)
    // ───────────────────────────────────────────────────────────
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';  // Palitan ng iyong SMTP host
            $mail->SMTPAuth   = true;
            $mail->Username   = 'your-email@gmail.com';  // Ang iyong SMTP email account
            $mail->Password   = 'your-app-password';     // Ang iyong App Password (hindi regular password)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            
            // Recipients
            $mail->setFrom('noreply@hoa.com', 'HOA Management');
            $mail->addAddress($to_email, $homeowner_name);
            
            // Email subject based on action
            switch ($action) {
                case 'reply':
                    $subject = "New Reply to Your Support Ticket: {$ticket_subject}";
                    break;
                case 'resolved':
                    $subject = "Support Ticket Resolved: {$ticket_subject}";
                    break;
                case 'rejected':
                    $subject = "Support Ticket Rejected: {$ticket_subject}";
                    break;
                default:
                    $subject = "Support Ticket Update: {$ticket_subject}";
            }
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = generate_email_html($homeowner_name, $ticket_subject, $action, $message);
            $mail->AltBody = generate_email_text($homeowner_name, $ticket_subject, $action, $message);
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Email notification failed: {$mail->ErrorInfo}");
            return false;
        }
    }
    
    // ───────────────────────────────────────────────────────────
    // OPTION 2: Using PHP mail() function (Fallback kapag walang Composer/PHPMailer)
    // ───────────────────────────────────────────────────────────
    else {
        $subject = '';
        switch ($action) {
            case 'reply':
                $subject = "New Reply to Your Support Ticket: {$ticket_subject}";
                break;
            case 'resolved':
                $subject = "Support Ticket Resolved: {$ticket_subject}";
                break;
            case 'rejected':
                $subject = "Support Ticket Rejected: {$ticket_subject}";
                break;
            default:
                $subject = "Support Ticket Update: {$ticket_subject}";
        }
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: HOA Management <noreply@hoa.com>" . "\r\n";
        
        $email_body = generate_email_html($homeowner_name, $ticket_subject, $action, $message);
        
        return mail($to_email, $subject, $email_body, $headers);
    }
}

/**
 * Generate HTML email template
 */
function generate_email_html($name, $subject, $action, $message) {
    $action_title = '';
    $action_color = '';
    $action_icon = '';
    
    switch ($action) {
        case 'reply':
            $action_title = 'New Reply from HOA Admin';
            $action_color = '#3b82f6';
            $action_icon = '💬';
            break;
        case 'resolved':
            $action_title = 'Ticket Resolved';
            $action_color = '#10b981';
            $action_icon = '✓';
            break;
        case 'rejected':
            $action_title = 'Ticket Rejected';
            $action_color = '#ef4444';
            $action_icon = '✕';
            break;
    }
    
    // Awtomatikong kukunin ang kasalukuyang base URL ng localhost mo para hindi broken link
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $redirect_url = $protocol . $host . "/WMS%20HOA/user/support.php?tab=contact";
    
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f3f4f6;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6; padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e3a3a 0%, #0f1f22 100%); padding: 32px; text-align: center; border-radius: 12px 12px 0 0;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">HOA Management System</h1>
                        </td>
                    </tr>
                    
                    <tr>
                        <td style="padding: 32px 32px 0 32px; text-align: center;">
                            <div style="display: inline-block; padding: 12px 24px; background-color: {$action_color}; color: white; border-radius: 24px; font-weight: bold; font-size: 16px;">
                                {$action_icon} {$action_title}
                            </div>
                        </td>
                    </tr>
                    
                    <tr>
                        <td style="padding: 32px;">
                            <p style="margin: 0 0 16px; font-size: 16px; color: #374151;">
                                Hi <strong>{$name}</strong>,
                            </p>
                            
                            <p style="margin: 0 0 24px; font-size: 15px; color: #6b7280; line-height: 1.6;">
                                There's an update on your support ticket regarding <strong style="color: #1f2937;">"{$subject}"</strong>.
                            </p>
                            
                            <div style="background-color: #f9fafb; border-left: 4px solid {$action_color}; padding: 16px; border-radius: 6px; margin-bottom: 24px;">
                                <p style="margin: 0; font-size: 14px; color: #374151; line-height: 1.6; white-space: pre-wrap;">{$message}</p>
                            </div>
                            
                            <p style="margin: 0 0 24px; font-size: 15px; color: #6b7280; line-height: 1.6;">
                                You can view your ticket and reply history by logging into your account.
                            </p>
                            
                            <div style="text-align: center;">
                                <a href="{$redirect_url}" 
                                   style="display: inline-block; padding: 12px 32px; background-color: #dc1623; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 14px;">
                                    View My Tickets
                                </a>
                            </div>
                        </td>
                    </tr>
                    
                    <tr>
                        <td style="padding: 24px 32px; background-color: #f9fafb; border-radius: 0 0 12px 12px; text-align: center;">
                            <p style="margin: 0; font-size: 13px; color: #9ca3af;">
                                This is an automated message from HOA Management System.<br>
                                Please do not reply to this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
}

/**
 * Generate plain text email (fallback)
 */
function generate_email_text($name, $subject, $action, $message) {
    $action_title = '';
    
    switch ($action) {
        case 'reply':
            $action_title = 'NEW REPLY FROM HOA ADMIN';
            $action_color = '#3b82f6';
            break;
        case 'resolved':
            $action_title = 'TICKET RESOLVED';
            $action_color = '#10b981';
            break;
        case 'rejected':
            $action_title = 'TICKET REJECTED';
            $action_color = '#ef4444';
            break;
    }
    
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $redirect_url = $protocol . $host . "/WMS%20HOA/user/support.php?tab=contact";
    
    return <<<TEXT
HOA MANAGEMENT SYSTEM
{$action_title}

Hi {$name},

There's an update on your support ticket regarding "{$subject}".

Message:
{$message}

You can view your ticket and reply history by logging into your account at:
{$redirect_url}

---
This is an automated message from HOA Management System.
Please do not reply to this email.
TEXT;
}
?>