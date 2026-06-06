<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// Simple JSON API to send/ resend OTP to a verified email owner during registration flow
header('Content-Type: application/json; charset=utf-8');

$email = filter_var($_POST['email'] ?? $_GET['email'] ?? '', FILTER_VALIDATE_EMAIL);
if (!$email) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Please provide a valid email.']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    // Ensure the account exists (we only send OTP for existing customers)
    $stmt = $db->prepare('SELECT id, first_name FROM customers WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $cust = $stmt->fetch();
    if (!$cust) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'No account found for that email.']);
        exit;
    }

    // Create OTP
    $otp = random_int(100000, 999999);
    $token_hash = password_hash((string)$otp, PASSWORD_DEFAULT);
    $expires_at = date('Y-m-d H:i:s', time() + 60 * 15);

    $insert = $db->prepare('INSERT INTO email_verifications (email, token_hash, expires_at) VALUES (:email, :token_hash, :expires_at)');
    $insert->execute([':email' => $email, ':token_hash' => $token_hash, ':expires_at' => $expires_at]);

    // Send email
    $subject = 'Your verification code for ' . SITE_NAME;
    $body = "Hello " . htmlspecialchars($cust['first_name']) . ",\n\n" .
        "Use the following One-Time Passcode (OTP) to verify your email address on " . SITE_NAME . ":\n\n" .
        "OTP: " . $otp . "\n\n" .
        "This code expires in 15 minutes. If you did not request this, please ignore this email.\n\n" .
        "Regards,\n" . SITE_NAME;

    $sent = send_email($email, $subject, $body);

    // Dev-only: write plaintext OTP to storage for local testing
    $storageDir = __DIR__ . '/storage';
    if (!is_dir($storageDir)) { @mkdir($storageDir, 0777, true); }
    $otpFile = $storageDir . '/last_otp.txt';
    $otpLine = date('[Y-m-d H:i:s] ') . $email . '|' . $otp . PHP_EOL;
    @file_put_contents($otpFile, $otpLine, FILE_APPEND | LOCK_EX);

    if ($sent) {
        echo json_encode(['ok' => true, 'message' => 'Verification code sent.']);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => 'Failed to send email. Check mail configuration.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Server error.']);
}

exit;
