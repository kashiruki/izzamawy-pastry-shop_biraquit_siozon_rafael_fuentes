<?php
require_once __DIR__ . '/config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-no-nav">
    <?php include __DIR__ . '/inc/header.php'; ?>
    <style>
    /* Page-specific: hide full header for the simple login page and remove large top spacing */
    body.auth-no-nav .header { display: none !important; }
    body.auth-no-nav .auth-page { min-height: 100vh !important; padding: 40px 20px !important; }
    </style>

    <main class="auth-page">
        <div class="auth-container">
            <aside class="auth-brand">
                <div class="brand-logo">
                    <img src="images/logo.png" alt="<?php echo SITE_NAME; ?>">
                </div>
                <h1><?php echo SITE_NAME; ?></h1>
                <p class="brand-sub">Admin Access Only.</p>
            </aside>

            <section class="auth-card">
                <h2>Sign In</h2>
                <?php if (!empty($_SESSION['flash'])): ?>
                    <div class="flash">
                        <?php echo $_SESSION['flash']; unset($_SESSION['flash']); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="login_simple_action.php" class="form-login" novalidate>
                    <div class="field">
                        <label for="username">Username or Email</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user"></i>
                            <input id="username" name="username" type="text" required autofocus placeholder="Username or email">
                        </div>
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input id="password" name="password" type="password" class="password-field" required placeholder="Enter your password">
                            <button type="button" class="btn-icon toggle-password" aria-label="Show password"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>

                    <div class="auth-actions">
                        <button type="submit" class="btn btn-primary btn-block">Proceed</button>
                        <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
                    </div>
                    <div style="margin-top:14px">
                        <a href="<?php echo SITE_URL; ?>/index.php" class="btn btn-secondary" style="display:inline-block;width:100%;text-align:center">← Back to Customer Portal</a>
                    </div>
                </form>

            </section>
        </div>
    </main>

    <script>
    const ADMIN_DASHBOARD_URL = '<?php echo SITE_URL; ?>/admin/dashboard.php';
    document.addEventListener('DOMContentLoaded', function(){
        const form = document.querySelector('.form-login');
        if (!form) return;
        const flashContainer = document.querySelector('.flash');
        function showFlash(msg, isError){
            let el = flashContainer;
            if (!el) {
                el = document.createElement('div');
                el.className = 'flash';
                form.parentNode.insertBefore(el, form);
            }
            el.textContent = msg;
            el.style.color = isError ? '#842029' : '#1b5e20';
            el.style.background = isError ? '#fff4f4' : '#e6ffed';
            el.style.padding = '10px';
            el.style.borderRadius = '6px';
            el.style.marginBottom = '12px';
        }

        form.addEventListener('submit', async function(e){
            e.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            const origText = btn ? btn.textContent : null;
            if (btn) { btn.disabled = true; btn.textContent = 'Signing in...'; }

            try {
                const fd = new FormData(form);
                const resp = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: fd,
                    credentials: 'same-origin'
                });
                const data = await resp.json().catch(() => null);
                if (!data) {
                    showFlash('Server error. Please try again.', true);
                    if (btn) { btn.disabled = false; btn.textContent = origText; }
                    return;
                }
                if (data.success) {
                    // If admin login, redirect to admin dashboard; otherwise use redirect or home
                    if (data.admin) {
                        window.location.href = ADMIN_DASHBOARD_URL;
                        return;
                    }
                    if (data.redirect) {
                        window.location.href = data.redirect;
                        return;
                    }
                    window.location.href = '/index.php';
                    return;
                }
                // Show error message
                showFlash(data.message || 'Invalid username or password.', true);
                if (btn) { btn.disabled = false; btn.textContent = origText; }
            } catch (err) {
                console.error('Login request failed', err);
                showFlash('Network error. Please try again.', true);
                if (btn) { btn.disabled = false; btn.textContent = origText; }
            }
        });
    });
    </script>

    <?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>
