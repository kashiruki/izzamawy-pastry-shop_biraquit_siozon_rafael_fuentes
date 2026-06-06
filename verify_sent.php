<?php
require_once __DIR__ . '/config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Verify Your Email - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-no-nav">
    <?php include __DIR__ . '/inc/header.php'; ?>

    <main class="auth-page">
        <div class="auth-container">
            <section class="auth-card" style="max-width:700px;margin:0 auto;">
                <h2>Check Your Email</h2>
                <p>If the email you provided exists, you should receive a verification link shortly. Please click the link in the email to verify your account.</p>
                <a href="index.php" class="btn btn-primary">Return to Home</a>
            </section>
        </div>
    </main>

    <?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>
