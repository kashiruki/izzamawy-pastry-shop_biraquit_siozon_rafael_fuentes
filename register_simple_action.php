<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// Debug logging for diagnosing redirect issue
ini_set('display_errors', 1);
error_reporting(E_ALL);
// Buffer output so AJAX JSON isn't corrupted by warnings/notices
ob_start();
function dbg($msg) {
    $log = __DIR__ . '/storage/logs/register_debug.log';
    @file_put_contents($log, date('[Y-m-d H:i:s] ') . $msg . "\n", FILE_APPEND);
}
dbg('register_simple_action.php invoked');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    dbg('Not a POST request - redirecting to register_simple.php');
    redirect('register_simple.php');
}

dbg('Request method is POST');

// Detect AJAX requests
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

function ajax_response($data, $code = 200) {
    // capture any unexpected output and log it
    $buf = '';
    if (ob_get_level()) {
        $buf = ob_get_clean();
    }
    if ($buf) {
        $log = __DIR__ . '/storage/logs/register_debug.log';
        @file_put_contents($log, date('[Y-m-d H:i:s] ') . "Unexpected output before JSON: " . trim($buf) . "\n", FILE_APPEND);
    }
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Accept separate username and email
$raw_username = trim($_POST['username'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if (!$raw_username || !$email || !$password || !$confirm) {
    dbg('Validation failed: missing fields');
    $errors = [];
    if (!$raw_username) $errors['username'] = 'Please choose a username.';
    if (!$email) $errors['email'] = 'Please provide a valid email.';
    if (!$password) $errors['password'] = 'Please provide a password.';
    if (!$confirm) $errors['confirm_password'] = 'Please confirm your password.';
    if ($isAjax) ajax_response(['success' => false, 'message' => 'Validation failed.', 'errors' => $errors], 422);
    $_SESSION['flash'] = 'Please fill all fields.';
    redirect('register_simple.php');
}

// username rules: 3-30 chars, alnum and _-.
if (!preg_match('/^[a-zA-Z0-9_\-]{3,30}$/', $raw_username)) {
    dbg('Validation failed: invalid username: ' . $raw_username);
    $err = 'Username must be 3-30 characters and contain only letters, numbers, underscores or hyphens.';
    if ($isAjax) ajax_response(['success' => false, 'message' => $err, 'errors' => ['username' => $err]], 422);
    $_SESSION['flash'] = $err;
    redirect('register_simple.php');
}

if ($password !== $confirm) {
    dbg('Validation failed: passwords do not match');
    $err = 'Passwords do not match.';
    if ($isAjax) ajax_response(['success' => false, 'message' => $err, 'errors' => ['confirm_password' => $err]], 422);
    $_SESSION['flash'] = $err;
    redirect('register_simple.php');
}

if (strlen($password) < 6) {
    dbg('Validation failed: password too short');
    $err = 'Password must be at least 6 characters.';
    if ($isAjax) ajax_response(['success' => false, 'message' => $err, 'errors' => ['password' => $err]], 422);
    $_SESSION['flash'] = $err;
    redirect('register_simple.php');
}

try {
    dbg('Attempting DB connection');
    $db = Database::getInstance()->getConnection();
    dbg('DB connection established');
    // check existing by customers.email and users.username
    $stmt = $db->prepare('SELECT id FROM customers WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        $err = 'An account with that email already exists.';
        if ($isAjax) ajax_response(['success' => false, 'message' => $err, 'errors' => ['email' => $err]], 409);
        $_SESSION['flash'] = $err;
        redirect('register_simple.php');
    }
    $stmt2 = $db->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
    $stmt2->execute([':username' => $raw_username]);
    if ($stmt2->fetch()) {
        $err = 'That username is already taken.';
        if ($isAjax) ajax_response(['success' => false, 'message' => $err, 'errors' => ['username' => $err]], 409);
        $_SESSION['flash'] = $err;
        redirect('register_simple.php');
    }


    // derive first_name from email localpart
    $local = strstr($email, '@', true);
    $first_name = $local ? ucfirst($local) : 'Customer';
    $last_name = '';

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $insert = $db->prepare('INSERT INTO customers (first_name, last_name, email, password_hash, created_at, updated_at) VALUES (:first_name, :last_name, :email, :password_hash, NOW(), NOW())');
    $insert->execute([
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':email' => $email,
        ':password_hash' => $password_hash
    ]);

    dbg('Inserted customer id: ' . $db->lastInsertId());

    $customer_id = $db->lastInsertId();

    // Insert into users table to record credentials (username + password_hash)
    try {
        $insertUser = $db->prepare('INSERT INTO users (username, password_hash, customer_id, created_at, updated_at) VALUES (:username, :password_hash, :customer_id, NOW(), NOW())');
        $insertUser->execute([
            ':username' => $raw_username,
            ':password_hash' => $password_hash,
            ':customer_id' => $customer_id
        ]);
        dbg('Inserted users id: ' . $db->lastInsertId());
        $user_id = $db->lastInsertId();
    } catch (Exception $e) {
        // if users table doesn't exist or insertion fails, log and continue (customers row exists)
        dbg('users insertion failed: ' . $e->getMessage());
    }

    // Ensure customers table has is_verified and verified_at columns
    try {
        $db->exec("ALTER TABLE customers ADD COLUMN IF NOT EXISTS is_verified TINYINT(1) DEFAULT 0, ADD COLUMN IF NOT EXISTS verified_at DATETIME NULL");
    } catch (Exception $e) {
        // ignore if not supported; we'll still store verification separately
    }

    // Create email_verifications table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS email_verifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(150) NOT NULL,
        token_hash VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        used TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Generate numeric OTP (6 digits)
    $otp = random_int(100000, 999999);
    $token_hash = password_hash((string)$otp, PASSWORD_DEFAULT);
    $expires_at = date('Y-m-d H:i:s', time() + 60 * 15); // 15 minutes

    $insertToken = $db->prepare('INSERT INTO email_verifications (email, token_hash, expires_at) VALUES (:email, :token_hash, :expires_at)');
    $insertToken->execute([
        ':email' => $email,
        ':token_hash' => $token_hash,
        ':expires_at' => $expires_at
    ]);

    dbg('Inserted email_verifications row for ' . $email);

    // Send OTP email
    $subject = 'Your verification code for ' . SITE_NAME;
    $body = "Hello " . htmlspecialchars($first_name) . ",\n\n" .
        "Thanks for registering. Use the following One-Time Passcode (OTP) to verify your email address:\n\n" .
        "OTP: " . $otp . "\n\n" .
        "This code expires in 15 minutes. If you did not request this, please ignore this email.\n\n" .
        "Regards,\n" . SITE_NAME;

        // Send OTP email (HTML + plain)
        $subject = 'Your verification code for ' . SITE_NAME;
        $plain = "Hello " . htmlspecialchars($first_name) . "\n\n" .
            "Thanks for registering. Use the following One-Time Passcode (OTP) to verify your email address:\n\n" .
            "OTP: " . $otp . "\n\n" .
            "This code expires in 15 minutes. If you did not request this, please ignore this email.\n\n" .
            "Regards,\n" . SITE_NAME;

        $html = '<!doctype html><html><head><meta charset="utf-8"><title>' . htmlspecialchars($subject) . '</title></head><body style="font-family:Inter,system-ui,Arial,sans-serif;color:#222;">';
        $html .= '<div style="max-width:560px;margin:0 auto;padding:20px;border:1px solid #eee;border-radius:8px;">';
        $html .= '<h2 style="color:#B8860B;margin:0 0 6px;">' . htmlspecialchars(SITE_NAME) . '</h2>';
        $html .= '<p style="color:#555;">Hello ' . htmlspecialchars($first_name) . ',</p>';
        $html .= '<p style="color:#333;">Thanks for registering. Use the following One-Time Passcode (OTP) to verify your email address. This code expires in <strong>15 minutes</strong>.</p>';
        $html .= '<div style="margin:18px 0;padding:14px 18px;background:#f9f7f4;border-radius:8px;display:inline-block;font-size:20px;font-weight:700;letter-spacing:2px;color:#1f1f1f;">' . $otp . '</div>';
        $html .= '<p style="color:#777;margin-top:18px;">If you did not request this, you can safely ignore this email.</p>';
        $html .= '<p style="color:#777;margin-top:18px;">Regards,<br>' . htmlspecialchars(SITE_NAME) . '</p>';
        $html .= '</div></body></html>';

        $sent = false;
        try {
            $sent = (bool) send_email($email, $subject, $html, $plain);
        } catch (Throwable $e) {
            $sent = false;
            $log = __DIR__ . '/storage/logs/mail_debug.log';
            @file_put_contents($log, date('[Y-m-d H:i:s] ') . "send_email threw: " . $e->getMessage() . "\n", FILE_APPEND);
        }

        dbg('send_email called for ' . $email . ' result=' . ($sent ? '1' : '0'));

        if (!$sent) {
            $msg = 'Unable to send verification email. Please check mail settings or try again later.';
            $log = __DIR__ . '/storage/logs/mail_debug.log';
            @file_put_contents($log, date('[Y-m-d H:i:s] ') . "Failed to send OTP to: " . $email . "\n", FILE_APPEND);
            if ($isAjax) ajax_response(['success' => false, 'message' => $msg], 500);
            $_SESSION['flash'] = $msg;
            redirect('register_simple.php');
        }

    // Dev-only: write plaintext OTP to storage for local testing
    $storageDir = __DIR__ . '/storage';
    if (!is_dir($storageDir)) {
        @mkdir($storageDir, 0777, true);
    }
    $otpFile = $storageDir . '/last_otp.txt';
    $otpLine = date('[Y-m-d H:i:s] ') . $email . '|' . $otp . PHP_EOL;
    @file_put_contents($otpFile, $otpLine, FILE_APPEND | LOCK_EX);

    // Redirect user to OTP verification page (with email param)
    $_SESSION['flash'] = 'Account created. A verification code has been sent to your email.';
    $redirect = 'otp_verify.php?email=' . urlencode($email);
    if ($isAjax) {
        ajax_response(['success' => true, 'message' => 'Account created. Verification code sent.', 'redirect' => $redirect]);
    }
    redirect($redirect);

} catch (Exception $e) {
    $msg = 'Registration failed. Please try again.';
    dbg('Exception: ' . $e->getMessage());
    if ($isAjax) ajax_response(['success' => false, 'message' => $msg], 500);
    $_SESSION['flash'] = $msg;
    redirect('register_simple.php');
}
