<?php
/**
 * mailer.php — HOA Email Helper
 * ─────────────────────────────
 * SETUP (one-time):
 * 1. Manu-manong i-download mula sa https://github.com/PHPMailer/PHPMailer
 * at ilagay ang 'src' folder sa loob ng 'includes/' katabi ng file na ito.
 *
 * 2. Fill in your SMTP credentials below (Gmail, SMTP2GO, Brevo, etc.)
 *
 * HOW TO USE:
 * include("../includes/mailer.php");
 * sendHOAEmail("recipient@email.com", "Subject", "<p>HTML body here</p>", $user_id, $conn);
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ── MANU-MANONG PAG-LOAD (Nilaktawan si Composer dahil sa PHP version) ───────
require_once __DIR__ . '/src/Exception.php';
require_once __DIR__ . '/src/PHPMailer.php';
require_once __DIR__ . '/src/SMTP.php';

// ── SMTP Config — FILL THESE IN ──────────────────────────────────────────────
define('MAIL_HOST',     'smtp.gmail.com');      // e.g. smtp.gmail.com / smtp.brevo.com
define('MAIL_PORT',     587);                   // 587 for TLS, 465 for SSL
define('MAIL_USERNAME', 'soriano.aueec@gmail.com');
define('MAIL_PASSWORD', 'dflwdxkhrxponcae');    // App Password (walang space)
define('MAIL_FROM',     'soriano.aueec@gmail.com');
define('MAIL_FROM_NAME','Sapilara Lower HOA');

/**
 * sendHOAEmail()
 *
 * @param string      $to_email   Recipient email address
 * @param string      $subject    Email subject
 * @param string      $html_body  HTML content of the email
 * @param int|null    $user_id    If provided, checks notif_email preference first
 * @param mysqli|null $conn       DB connection (required if user_id is passed)
 * @return bool  true = sent, false = skipped or failed
 */
function sendHOAEmail(string $to_email, string $subject, string $html_body, ?int $user_id = null, $conn = null): bool
{
    // Check kung naka-enable ang email notifications ng user sa database
    if ($user_id && $conn) {
        $uid = (int) $user_id;
        $pref_q = mysqli_query($conn, "SELECT notif_email FROM users WHERE user_id='$uid' LIMIT 1");
        $pref   = mysqli_fetch_assoc($pref_q);
        if ($pref && (int)$pref['notif_email'] === 0) {
            // Naka-off ang email notification ng user — skip silently
            return false;
        }
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to_email);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html_body;
        $mail->AltBody = strip_tags($html_body);

        $mail->send();
        return true;

    } catch (Exception $e) {
        // Mag-lo-log ng error sa xampp/php/logs/php_error.log para hindi makita ng end-user
        error_log("HOA Mailer error: " . $mail->ErrorInfo);
        return false;
    }
}