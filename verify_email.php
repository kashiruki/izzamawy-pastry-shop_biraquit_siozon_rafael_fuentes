<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$token = $_GET['token'] ?? '';
if (!$token) {
    $_SESSION['flash'] = 'Invalid verification link.';
    redirect('login_simple.php');
}

$db = Database::getInstance()->getConnection();

// Ensure email_verifications table exists
$db->exec("CREATE TABLE IF NOT EXISTS email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
)");

// Find matching token (check recent rows)
$stmt = $db->prepare('SELECT id, email, token_hash, expires_at, used FROM email_verifications WHERE used = 0 ORDER BY id DESC LIMIT 50');
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
    $_SESSION['flash'] = 'Invalid or expired verification link.';
    redirect('login_simple.php');
}

if (strtotime($found['expires_at']) < time()) {
    $_SESSION['flash'] = 'Verification link has expired. Please register again.';
    redirect('register_simple.php');
}

// Mark token used
$mark = $db->prepare('UPDATE email_verifications SET used = 1 WHERE id = :id');
$mark->execute([':id' => $found['id']]);

// Ensure customers table has is_verified column
try {
    $db->exec("ALTER TABLE customers ADD COLUMN IF NOT EXISTS is_verified TINYINT(1) DEFAULT 0, ADD COLUMN IF NOT EXISTS verified_at DATETIME NULL");
} catch (Exception $e) {
    // ignore
}

// Mark customer verified
$update = $db->prepare('UPDATE customers SET is_verified = 1, verified_at = NOW() WHERE email = :email LIMIT 1');
$update->execute([':email' => $found['email']]);

$_SESSION['flash'] = 'Email verified. You may now log in.';
redirect('login_simple.php');
