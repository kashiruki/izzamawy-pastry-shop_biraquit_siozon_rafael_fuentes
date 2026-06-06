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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Register - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-no-nav">
    <?php include __DIR__ . '/inc/header.php'; ?>

    <main class="auth-page">
        <div class="auth-container">
            <aside class="auth-brand">
                <div class="brand-logo">
                    <img src="images/logo.png" alt="<?php echo SITE_NAME; ?>">
                </div>
                <h1><?php echo SITE_NAME; ?></h1>
                <p class="brand-sub">Create an account to save your details</p>
            </aside>

            <section class="auth-card">
                <h2>Create Account</h2>
                <?php if (!empty($_SESSION['flash'])): ?>
                    <div class="flash"><?php echo $_SESSION['flash']; unset($_SESSION['flash']); ?></div>
                <?php endif; ?>

                <div class="auth-errors" aria-live="polite" style="display:none;margin-bottom:12px"></div>

                <form id="registerForm" method="post" action="register_simple_action.php" class="form-login" novalidate>
                    <div class="field">
                        <label for="username">Username</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user"></i>
                            <input id="username" name="username" type="text" required placeholder="choose a username (3-30 chars)">
                        </div>
                    </div>

                    <div class="field">
                        <label for="email">Email</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope"></i>
                            <input id="email" name="email" type="email" required placeholder="you@example.com">
                        </div>
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input id="password" name="password" type="password" class="password-field" required placeholder="Choose a secure password">
                            <button type="button" class="btn-icon toggle-password" aria-label="Show password"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>

                    <div class="field">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input id="confirm_password" name="confirm_password" type="password" required placeholder="Rewrite password">
                        </div>
                    </div>

                        <div class="auth-actions">
                            <button type="submit" class="btn btn-primary btn-block">Register</button>
                            <a href="login_simple.php" class="forgot-link">Back to Login</a>
                        </div>
                </form>
                    <script>
                        (function(){
                            var form = document.getElementById('registerForm');
                            if (!form) return;

                            function showFieldErrors(errors) {
                                // clear existing
                                form.querySelectorAll('.error-text').forEach(e => e.remove());
                                form.querySelectorAll('.field input').forEach(inp => inp.classList.remove('field-error'));
                                for (var k in errors) {
                                    var input = form.querySelector('[name="' + k + '"]');
                                    if (input) {
                                        input.classList.add('field-error');
                                        var span = document.createElement('div');
                                        span.className = 'error-text';
                                        span.innerText = errors[k];
                                        input.parentElement.appendChild(span);
                                    }
                                }
                            }

                            form.addEventListener('submit', async function(e){
                                if (!window.fetch) return; // keep normal submit for no-JS
                                e.preventDefault();
                                const submitBtn = form.querySelector('button[type="submit"]');
                                const errorsBox = document.querySelector('.auth-errors');
                                if (submitBtn) { submitBtn.disabled = true; submitBtn.innerText = 'Registering...'; }
                                if (errorsBox) { errorsBox.style.display = 'none'; errorsBox.innerText = ''; }
                                // basic client validation
                                const username = form.querySelector('[name="username"]').value.trim();
                                const email = form.querySelector('[name="email"]').value.trim();
                                const password = form.querySelector('[name="password"]').value;
                                const confirm = form.querySelector('[name="confirm_password"]').value;
                                const clientErrors = {};
                                if (!username) clientErrors.username = 'Please choose a username.';
                                if (!email) clientErrors.email = 'Please enter your email.';
                                if (!password) clientErrors.password = 'Please enter a password.';
                                if (password !== confirm) clientErrors.confirm_password = 'Passwords do not match.';
                                if (Object.keys(clientErrors).length) {
                                    if (errorsBox) { errorsBox.innerText = 'Please fix the highlighted fields.'; errorsBox.style.display = 'block'; }
                                    showFieldErrors(clientErrors);
                                    if (submitBtn) { submitBtn.disabled = false; submitBtn.innerText = 'Register'; }
                                    return;
                                }

                                try {
                                    const formData = new FormData(form);
                                    const resp = await fetch(form.action, {
                                        method: 'POST',
                                        headers: {
                                            'X-Requested-With': 'XMLHttpRequest',
                                            'Accept': 'application/json'
                                        },
                                        body: formData,
                                        credentials: 'same-origin'
                                    });
                                    const data = await resp.json().catch(() => null);
                                    if (!data) {
                                        if (errorsBox) { errorsBox.innerText = 'Server error. Please try again.'; errorsBox.style.display = 'block'; }
                                        if (submitBtn) { submitBtn.disabled = false; submitBtn.innerText = 'Register'; }
                                        return;
                                    }

                                    if (!data.success) {
                                        if (data.errors) {
                                            showFieldErrors(data.errors);
                                            if (errorsBox) { errorsBox.innerText = data.message || 'Please fix the highlighted fields.'; errorsBox.style.display = 'block'; }
                                        } else {
                                            // toast notification fallback
                                            if (typeof showNotification === 'function') showNotification(data.message || 'Registration failed', 'error');
                                            if (errorsBox) { errorsBox.innerText = data.message || 'Registration failed'; errorsBox.style.display = 'block'; }
                                        }
                                        if (submitBtn) { submitBtn.disabled = false; submitBtn.innerText = 'Register'; }
                                        return;
                                    }

                                    // success: redirect to OTP page
                                    if (data.redirect) {
                                        window.location.href = data.redirect;
                                    } else {
                                        window.location.href = 'otp_verify.php?email=' + encodeURIComponent(email);
                                    }
                                } catch (err) {
                                    console.error(err);
                                    if (errorsBox) { errorsBox.innerText = 'Network error. Please try again.'; errorsBox.style.display = 'block'; }
                                    if (submitBtn) { submitBtn.disabled = false; submitBtn.innerText = 'Register'; }
                                }
                            });
                        })();
                    </script>
            </section>
        </div>
    </main>

    <?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>
