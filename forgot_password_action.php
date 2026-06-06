<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('forgot_password.php');
}

$email = sanitize_input($_POST['email'] ?? '');

if (empty($email)) {
    $_SESSION['flash'] = 'Please enter your email address.';
    redirect('forgot_password.php');
}

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare('SELECT id, email, first_name FROM customers WHERE email = :email LIMIT 1');
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

if (!$user) {
    // For privacy, don't reveal whether the email exists
    $_SESSION['flash'] = 'If that email exists in our system, you will receive a password reset email.';
    redirect('forgot_password.php');
}

// Create password_resets table if it doesn't exist
$db->exec("CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
)") ;

// Generate secure token and store its hash
$token = bin2hex(random_bytes(16));
$token_hash = password_hash($token, PASSWORD_DEFAULT);
$expires_at = date('Y-m-d H:i:s', time() + 60 * 15); // 15 minutes

$insert = $db->prepare('INSERT INTO password_resets (email, token_hash, expires_at) VALUES (:email, :token_hash, :expires_at)');
$insert->execute([
    ':email' => $user['email'],
    ':token_hash' => $token_hash,
    ':expires_at' => $expires_at
]);

$reset_link = SITE_URL . '/change_password.php?token=' . $token;

// Prepare email
$subject = 'Password Reset for ' . SITE_NAME;
$message = "Hello " . $user['first_name'] . ",\n\n" .
           "We received a request to reset your password. Click the link below to change your password (valid for 15 minutes):\n\n" .
           $reset_link . "\n\n" .
           "If you did not request this, please ignore this email.\n\n" .
           "Regards,\n" . SITE_NAME;

$headers = "From: " . ADMIN_EMAIL . "\r\n" .
           "Reply-To: " . ADMIN_EMAIL . "\r\n" .
           "X-Mailer: PHP/" . phpversion();

// Send the reset email using send_email() helper (PHPMailer/SMTP when available)
$sent = send_email($user['email'], $subject, $message);

// Always show generic message for privacy
$_SESSION['flash'] = 'If that email exists in our system, you will receive a password reset email.';
redirect('forgot_password_sent.php');
