<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// If already logged in as admin, redirect to dashboard
// Allow ?force=1 to bypass and show the login form (useful for testing)
if (function_exists('is_admin_logged_in') && is_admin_logged_in() && !(isset($_GET['force']) && $_GET['force'] === '1')) {
    redirect(SITE_URL . '/admin/dashboard.php');
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body{font-family:Segoe UI, Tahoma, sans-serif;background:#f6f6f6}
        .login-wrap{min-height:80vh;display:flex;align-items:center;justify-content:center}
        .card{background:#fff;padding:24px;border-radius:10px;box-shadow:0 12px 30px rgba(0,0,0,0.06);width:420px}
        .field{margin-bottom:12px}
        .field input{width:100%;padding:10px;border:1px solid #ddd;border-radius:6px}
        .flash{background:#fff4f4;color:#b91c1c;padding:10px;border-radius:6px;margin-bottom:12px}
    </style>
</head>
<body>
    <div class="login-wrap">
        <div class="card">
            <h2 style="margin:0 0 8px">Admin Login</h2>
            <p style="margin:0 0 14px;color:#666">Sign in with your admin username and password</p>

            <?php if (!empty($flash)): ?>
                <div class="flash"><?php echo htmlspecialchars($flash); ?></div>
            <?php endif; ?>

            <form method="post" action="admin_login_action.php">
                <div class="field">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" required autofocus>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required>
                </div>

                <div style="display:flex;justify-content:flex-start;align-items:center;gap:12px">
                    <button type="submit" class="btn btn-primary">Sign in</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
