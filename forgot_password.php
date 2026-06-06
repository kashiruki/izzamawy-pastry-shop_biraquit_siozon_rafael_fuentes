<?php
require_once __DIR__ . '/config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Forgot Password - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include __DIR__ . '/inc/header.php'; ?>

    <main class="container auth-page">
        <section class="auth-card">
            <h2>Forgot Password</h2>

            <?php if (!empty($_SESSION['flash'])): ?>
                <div class="flash">
                    <?php echo $_SESSION['flash']; unset($_SESSION['flash']); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="forgot_password_action.php" class="form-login">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" required autofocus>

                <div class="auth-actions">
                    <button type="submit" class="btn btn-primary">Proceed</button>
                    <a href="index.php" class="btn btn-link">Cancel</a>
                </div>
            </form>
        </section>
    </main>

    <?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>
