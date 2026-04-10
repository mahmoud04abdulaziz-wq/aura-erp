<?php
/**
 * MiskStone ERP — Email Mailer Utility
 * Provides sendEmail() function using Gmail SMTP via PHPMailer or native mail().
 * 
 * Configuration: Set SMTP credentials below or in environment variables.
 * The system will attempt SMTP first, then fall back to PHP mail().
 * 
 * To use a real Gmail account:
 * 1. Enable 2-Step Verification on the Gmail account.
 * 2. Generate an App Password at https://myaccount.google.com/apppasswords
 * 3. Set SMTP_USER and SMTP_PASS below.
 */

// ============================================
// SMTP Configuration (Gmail)
// Replace with real credentials when ready to test
// ============================================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', '');  // e.g. miskstone.erp@gmail.com
define('SMTP_PASS', '');  // Gmail App Password (NOT regular password)
define('SMTP_FROM_NAME', 'MiskStone ERP');

/**
 * Send an email.
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject line
 * @param string $htmlBody HTML email body
 * @param string|null $replyTo Optional reply-to address
 * @return bool Whether the email was sent successfully
 */
function sendEmail(string $to, string $subject, string $htmlBody, ?string $replyTo = null): bool
{
    // Attempt PHPMailer if available
    $phpmailerPath = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($phpmailerPath) && !empty(SMTP_USER)) {
        try {
            require_once $phpmailerPath;
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;

            $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
            $mail->addAddress($to);
            if ($replyTo) $mail->addReplyTo($replyTo);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody);

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer error: " . $e->getMessage());
            return false;
        }
    }

    // Fallback: native PHP mail()
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . (SMTP_USER ?: 'noreply@miskstone.jo') . ">\r\n";
    if ($replyTo) {
        $headers .= "Reply-To: {$replyTo}\r\n";
    }

    $sent = @mail($to, $subject, $htmlBody, $headers);
    if (!$sent) {
        error_log("Native mail() failed for: {$to}");
    }
    return $sent;
}

/**
 * Generate a styled email template for transactional emails.
 */
function emailTemplate(string $title, string $bodyContent): string
{
    return '<!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"></head>
    <body style="font-family:Inter,Arial,sans-serif; background:#f1f5f9; padding:2rem;">
        <div style="max-width:600px; margin:auto; background:white; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.08);">
            <div style="background:linear-gradient(135deg,#1e293b,#6366f1); padding:2rem; text-align:center;">
                <h1 style="color:white; margin:0; font-size:1.5rem;">' . htmlspecialchars($title) . '</h1>
                <p style="color:rgba(255,255,255,0.7); margin:0.5rem 0 0; font-size:0.9rem;">MiskStone ERP · مسك للحجر الصناعي والديكور</p>
            </div>
            <div style="padding:2rem; color:#334155; line-height:1.7;">
                ' . $bodyContent . '
            </div>
            <div style="padding:1rem 2rem; background:#f8fafc; text-align:center; font-size:0.8rem; color:#94a3b8;">
                This is an automated message from MiskStone ERP. Do not reply directly.
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Send an OTP verification email.
 */
function sendOtpEmail(string $to, string $otp, string $userName): bool
{
    $body = emailTemplate('Verify Your Identity', "
        <p>Hello <strong>{$userName}</strong>,</p>
        <p>Your one-time verification code is:</p>
        <div style='text-align:center; margin:1.5rem 0;'>
            <span style='font-size:2.5rem; font-weight:800; letter-spacing:8px; color:#6366f1; background:#e0e7ff; padding:0.75rem 2rem; border-radius:12px; display:inline-block;'>{$otp}</span>
        </div>
        <p>This code expires in <strong>5 minutes</strong>. If you did not request this, please contact your IT administrator.</p>
    ");
    return sendEmail($to, 'MiskStone ERP — Verification Code', $body);
}
