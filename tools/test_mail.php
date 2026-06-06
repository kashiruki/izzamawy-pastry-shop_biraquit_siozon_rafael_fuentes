<?php
// Simple test script to exercise send_email().
require_once __DIR__ . '/../config/config.php';

$to = $_GET['to'] ?? ($argv[1] ?? '');
if (!$to) {
    echo "Usage: php tools/test_mail.php you@example.com\nOr open in browser: ?to=you@example.com\n";
    exit(1);
}

$subject = 'Test email from Izzamawy Pastry Shop';
$body = "This is a test email sent by the application's send_email() function.\n\nIf you receive this, SMTP is configured correctly.\n\nRegards,\n" . SITE_NAME;

$ok = send_email($to, $subject, $body);
if ($ok) {
    echo "OK: Email send attempted to $to\n";
} else {
    echo "ERROR: send_email() returned false. Check storage/logs/mail_debug.log and PHP error logs.\n";
}
