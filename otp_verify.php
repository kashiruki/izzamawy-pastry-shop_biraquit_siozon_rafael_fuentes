<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$prefill_email = isset($_GET['email']) ? sanitize_input($_GET['email']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Verify OTP - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Center the OTP form on the page */
        .auth-page .auth-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 220px);
            padding: 24px;
        }
        .auth-card { width:100%; max-width:480px; }
        .dev-otp { word-break:break-word; }
    </style>
</head>
<body class="auth-no-nav">
    <?php include __DIR__ . '/inc/header.php'; ?>

    <main class="auth-page">
        <div class="auth-container">
            <section class="auth-card" style="max-width:480px;margin:0 auto;">
                <h2>Enter Verification Code</h2>
                <?php if (!empty($_SESSION['flash'])): ?>
                    <div class="flash"><?php echo $_SESSION['flash']; unset($_SESSION['flash']); ?></div>
                <?php endif; ?>

                <form method="post" action="otp_verify_action.php" class="form-login" novalidate>
                    <div class="field">
                        <label for="email">Email</label>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <input id="email" name="email" type="email" required value="<?php echo htmlspecialchars($prefill_email); ?>" style="flex:1">
                            <button type="button" id="sendOtpBtn" class="btn">Send OTP</button>
                        </div>
                    </div>

                    <div class="field">
                        <label for="otp">Verification Code</label>
                        <input id="otp" name="otp" type="text" required placeholder="Enter 6-digit code" pattern="\d{6}" maxlength="6">
                    </div>

                    <div class="auth-actions">
                        <button type="submit" class="btn btn-primary btn-block">Verify</button>
                        <a href="register_simple.php" class="forgot-link">Back to Register</a>
                    </div>
                </form>
                <?php
                // Dev-only helper: show last OTP for this email if available
                $otp_file = __DIR__ . '/storage/last_otp.txt';
                if (file_exists($otp_file) && !empty($prefill_email)) {
                    $lines = array_reverse(file($otp_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
                    foreach ($lines as $ln) {
                        // lines are stored as: [datetime] email|otp
                        $parts = explode('|', $ln);
                        if (count($parts) === 2 && strpos($parts[0], $prefill_email) !== false) {
                            $shownOtp = trim($parts[1]);
                            echo '<div class="dev-otp" style="margin-top:12px;background:#fffbe6;border:1px solid #ffd966;padding:10px;border-radius:6px;color:#5b3e00">';
                            echo '<strong>DEV OTP:</strong> ' . htmlspecialchars($shownOtp) . ' (for ' . htmlspecialchars($prefill_email) . ')';
                            echo '</div>';
                            break;
                        }
                    }
                }
                ?>
                        <script>
                            (function(){
                                var btn = document.getElementById('sendOtpBtn');
                                var emailInput = document.getElementById('email');
                                if (!btn || !emailInput) return;
                                btn.addEventListener('click', function(){
                                    var email = emailInput.value.trim();
                                    if (!email) { alert('Please enter your email first.'); return; }
                                    btn.disabled = true; btn.innerText = 'Sending...';
                                    var fd = new FormData(); fd.append('email', email);
                                    fetch('otp_send.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                                        .then(function(r){ return r.json().catch(function(){ return {ok:false, message:'Unexpected response'} }); })
                                        .then(function(json){
                                            if (json.ok) {
                                                alert(json.message || 'OTP sent.');
                                            } else {
                                                alert(json.message || 'Failed to send OTP.');
                                            }
                                        }).catch(function(){ alert('Network error'); })
                                        .finally(function(){ btn.disabled = false; btn.innerText = 'Send OTP'; });
                                });
                            })();
                        </script>
            </section>
        </div>
    </main>

    <?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>
