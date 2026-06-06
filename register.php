<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

if (is_logged_in()) {
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/inc/header.php'; ?>

    <section class="page-header">
        <div class="container">
            <h1>Create Account</h1>
            <p>Register to save your details and speed up checkout</p>
        </div>
    </section>

    <section style="padding: 60px 0;">
        <div class="container">
            <div style="max-width: 700px; margin: 0 auto;">
                <!-- flash messages are rendered in the shared header -->

                <form action="register_action.php" method="post">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:12px;">
                        <input type="text" name="first_name" placeholder="First name" required>
                        <input type="text" name="last_name" placeholder="Last name" required>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:12px;">
                        <input type="email" name="email" placeholder="Email address" required>
                        <input type="text" name="phone" placeholder="Phone (optional)">
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:12px;">
                        <input type="password" name="password" placeholder="Password" required>
                        <input type="password" name="confirm_password" placeholder="Confirm password" required>
                    </div>

                    <textarea name="address" placeholder="Address (optional)" style="width:100%;min-height:80px;margin-bottom:12px;"></textarea>

                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:12px;">
                        <input type="text" name="city" placeholder="City">
                        <input type="text" name="province" placeholder="Province">
                        <input type="text" name="postal_code" placeholder="Postal code">
                    </div>

                    <button class="btn btn-primary" type="submit">Create Account</button>
                </form>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>
