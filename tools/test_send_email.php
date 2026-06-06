<?php
// CLI test script to send a simple email using the site's send_email() helper.
// Usage: php tools/test_send_email.php recipient@example.com

require_once __DIR__ . '/../config/config.php';

if (PHP_SAPI !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

if ($argc < 2) {
    echo "Usage: php tools/test_send_email.php recipient@example.com\n";
    exit(1);
}

$to = $argv[1];
$subject = 'Test Email from ' . SITE_NAME;
$body = '<html><body>';
$body .= '<h2>Test email</h2>';
$body .= '<p>This is a test message sent from the ' . htmlspecialchars(SITE_NAME) . ' application using send_email().</p>';
$body .= '<p>If you receive this, SMTP is configured correctly.</p>';
$body .= '</body></html>';
$alt = 'Test email from ' . SITE_NAME;

// If PHPMailer is available, use it directly and enable SMTP debug output
$composer = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composer)) {
    require_once $composer;
}

if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
    // Use PHPMailer directly so we can enable detailed SMTP debug
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->SMTPDebug = 2; // show client/server messages
        $mail->Debugoutput = function($str, $level) { echo "[PHPMailer] " . $str . "\n"; };
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        if (!empty(SMTP_SECURE)) {
            $mail->SMTPSecure = SMTP_SECURE;
        }
        $mail->Port = SMTP_PORT;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];

        $fromEmail = defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : ADMIN_EMAIL;
        $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : SITE_NAME;
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $body;
        $mail->AltBody = $alt;

        $ok = $mail->send();
        if ($ok) {
            echo "Email sent successfully to {$to}\n";
            @file_put_contents(__DIR__ . '/../storage/logs/mail_debug.log', date('[Y-m-d H:i:s] ') . "Test email sent to {$to}\n", FILE_APPEND);
            exit(0);
        } else {
            echo "PHPMailer->send() returned false\n";
            @file_put_contents(__DIR__ . '/../storage/logs/mail_debug.log', date('[Y-m-d H:i:s] ') . "Test email failed for {$to}\n", FILE_APPEND);
            exit(2);
        }
    } catch (Exception $e) {
        echo "PHPMailer Exception: " . $e->getMessage() . "\n";
        @file_put_contents(__DIR__ . '/../storage/logs/mail_debug.log', date('[Y-m-d H:i:s] ') . "PHPMailer exception: " . $e->getMessage() . "\n", FILE_APPEND);
        exit(3);
    }
} else {
    // Fallback to existing helper
    try {
        $ok = send_email($to, $subject, $body, $alt);
        if ($ok) {
            echo "Email sent successfully to {$to}\n";
            @file_put_contents(__DIR__ . '/../storage/logs/mail_debug.log', date('[Y-m-d H:i:s] ') . "Test email sent to {$to}\n", FILE_APPEND);
            exit(0);
        } else {
            echo "send_email() returned false. Check SMTP settings and mail logs.\n";
            @file_put_contents(__DIR__ . '/../storage/logs/mail_debug.log', date('[Y-m-d H:i:s] ') . "Test email failed for {$to}\n", FILE_APPEND);
            exit(2);
        }
    } catch (Throwable $e) {
        echo "Exception while sending: " . $e->getMessage() . "\n";
        @file_put_contents(__DIR__ . '/../storage/logs/mail_debug.log', date('[Y-m-d H:i:s] ') . "Exception sending test email: " . $e->getMessage() . "\n", FILE_APPEND);
        exit(3);
    }
}
