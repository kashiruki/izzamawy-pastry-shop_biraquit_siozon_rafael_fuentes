<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('otp_verify.php');
}

$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$otp = trim($_POST['otp'] ?? '');

if (!$email || !$otp) {
    $_SESSION['flash'] = 'Please provide your email and the verification code.';
    redirect('otp_verify.php?email=' . urlencode($email));
}

$db = Database::getInstance()->getConnection();

// Fetch recent unused verifications for this email
$stmt = $db->prepare('SELECT id, token_hash, expires_at, used FROM email_verifications WHERE email = :email AND used = 0 ORDER BY id DESC LIMIT 10');
$stmt->execute([':email' => $email]);
$rows = $stmt->fetchAll();

$found = null;
foreach ($rows as $row) {
    if (password_verify($otp, $row['token_hash'])) {
        $found = $row;
        break;
    }
}

if (!$found) {
    $_SESSION['flash'] = 'Invalid verification code.';
    redirect('otp_verify.php?email=' . urlencode($email));
}

if (strtotime($found['expires_at']) < time()) {
    $_SESSION['flash'] = 'Verification code has expired. Please register again.';
    redirect('register_simple.php');
}

// Mark verification used
$mark = $db->prepare('UPDATE email_verifications SET used = 1 WHERE id = :id');
$mark->execute([':id' => $found['id']]);

// Mark customer as verified
try {
    $db->exec("ALTER TABLE customers ADD COLUMN IF NOT EXISTS is_verified TINYINT(1) DEFAULT 0, ADD COLUMN IF NOT EXISTS verified_at DATETIME NULL");
} catch (Exception $e) {
    // ignore
}

$update = $db->prepare('UPDATE customers SET is_verified = 1, verified_at = NOW() WHERE email = :email LIMIT 1');
$update->execute([':email' => $email]);

// Log the user in if account exists
$stmtUser = $db->prepare('SELECT id, first_name, last_name FROM customers WHERE email = :email LIMIT 1');
$stmtUser->execute([':email' => $email]);
$user = $stmtUser->fetch();
if ($user) {
    $_SESSION['customer_id'] = $user['id'];
    $_SESSION['customer_name'] = trim($user['first_name'] . ' ' . $user['last_name']);
}

$_SESSION['flash'] = 'Email verified. You are now logged in.';
redirect('index.php');
