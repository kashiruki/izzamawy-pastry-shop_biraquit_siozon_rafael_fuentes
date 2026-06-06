<?php
require_once __DIR__ . '/config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Check Your Email - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include __DIR__ . '/inc/header.php'; ?>

    <main class="container auth-page">
        <section class="auth-card">
            <h2>Check Your Email</h2>
            <p>If the email you provided exists, you should receive a password reset link shortly. The link will be valid for 15 minutes.</p>
            <p>After receiving the email, click the link to open the Change Password page.</p>
            <a href="index.php" class="btn btn-primary">Return to Home</a>
        </section>
    </main>

    <?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>
