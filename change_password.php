<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$token = $_GET['token'] ?? '';
$valid = false;
$email = '';

if ($token) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare('SELECT id, email, token_hash, expires_at, used FROM password_resets WHERE used = 0 ORDER BY id DESC LIMIT 10');
    $stmt->execute();
    $rows = $stmt->fetchAll();
    foreach ($rows as $row) {
        if (password_verify($token, $row['token_hash'])) {
            // check expiry
            if (strtotime($row['expires_at']) < time()) {
                $valid = false;
                break;
            }
            $valid = true;
            $email = $row['email'];
            break;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Change Password - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include __DIR__ . '/inc/header.php'; ?>

    <main class="container auth-page">
        <section class="auth-card">
            <h2>Change Password</h2>

            <?php if (!empty($_SESSION['flash'])): ?>
                <div class="flash"><?php echo $_SESSION['flash']; unset($_SESSION['flash']); ?></div>
            <?php endif; ?>

            <?php if (!$token || !$valid): ?>
                <p>Invalid or expired token. Please request a new password reset.</p>
                <a href="forgot_password.php" class="btn btn-primary">Request Reset</a>
            <?php else: ?>
                <form method="post" action="change_password_action.php" class="form-login">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <label for="new_password">New Password</label>
                    <input id="new_password" name="new_password" type="password" required autofocus>

                    <label for="confirm_password">Rewrite Password</label>
                    <input id="confirm_password" name="confirm_password" type="password" required>

                    <div class="auth-actions">
                        <button type="submit" class="btn btn-primary">Proceed</button>
                        <a href="index.php" class="btn btn-link">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        </section>
    </main>

    <?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>
