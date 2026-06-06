<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$token = $_POST['token'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($token) || empty($new_password) || empty($confirm_password)) {
    $_SESSION['flash'] = 'All fields are required.';
    redirect('change_password.php?token=' . urlencode($token));
}

if ($new_password !== $confirm_password) {
    $_SESSION['flash'] = 'Passwords do not match.';
    redirect('change_password.php?token=' . urlencode($token));
}

// Basic password strength check (adjust as needed)
if (strlen($new_password) < 6) {
    $_SESSION['flash'] = 'Password must be at least 6 characters.';
    redirect('change_password.php?token=' . urlencode($token));
}

$db = Database::getInstance()->getConnection();

// Find matching token row
$stmt = $db->prepare('SELECT id, email, token_hash, expires_at, used FROM password_resets WHERE used = 0 ORDER BY id DESC LIMIT 10');
$stmt->execute();
$rows = $stmt->fetchAll();
$found = null;
foreach ($rows as $row) {
    if (password_verify($token, $row['token_hash'])) {
        $found = $row;
        break;
    }
}

if (!$found) {
    $_SESSION['flash'] = 'Invalid or expired token.';
    redirect('forgot_password.php');
}

if (strtotime($found['expires_at']) < time()) {
    $_SESSION['flash'] = 'Token has expired. Please request a new password reset.';
    redirect('forgot_password.php');
}

// Update user's password
$update = $db->prepare('UPDATE customers SET password_hash = :hash, updated_at = NOW() WHERE email = :email');
$update->execute([
    ':hash' => password_hash($new_password, PASSWORD_DEFAULT),
    ':email' => $found['email']
]);

// Mark token as used
$mark = $db->prepare('UPDATE password_resets SET used = 1 WHERE id = :id');
$mark->execute([':id' => $found['id']]);

$_SESSION['flash'] = 'Your password has been changed. You can now log in.';
redirect('login_simple.php');
