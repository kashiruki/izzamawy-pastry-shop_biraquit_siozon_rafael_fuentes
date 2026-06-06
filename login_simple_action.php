<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// Detect AJAX
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);
function ajax_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Robust redirect that falls back to JS/meta if headers already sent
function safe_redirect($url) {
    if (!headers_sent()) {
        header('Location: ' . $url);
        exit;
    }
    // Fallback HTML redirect
    $escaped = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    echo '<!doctype html><html><head>';
    echo "<meta http-equiv=\"refresh\" content=\"0;url={$escaped}\">";
    echo "<script>window.location.replace('{$escaped}');</script>";
    echo '</head><body>If you are not redirected, <a href="' . $escaped . '">click here</a>.</body></html>';
    exit;
}

// Basic input
$login_input = sanitize_input($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$remember = !empty($_POST['remember']);
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Config for lockout and remember
$maxAttempts = 5;
$lockoutMinutes = 15;
$rememberDays = 30;

try {
    $db = Database::getInstance()->getConnection();
    // First, allow admin users to sign in from the same form
    // Allow admin sign-in by username OR email
    $stmtAdmin = $db->prepare('SELECT id, username, password_hash, full_name, email, role, is_active FROM admin_users WHERE (username = :input_username OR email = :input_email) LIMIT 1');
    $stmtAdmin->execute([':input_username' => $login_input, ':input_email' => $login_input]);
    $admin = $stmtAdmin->fetch();
    if ($admin) {
        // Check account active and password
        if (!empty($admin['is_active']) && !empty($admin['password_hash']) && password_verify($password, $admin['password_hash'])) {
            // Admin authenticated - set admin session fields similar to admin/login.php
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_role'] = $admin['role'] ?? 'admin';
            $_SESSION['admin_name'] = $admin['full_name'] ?? $admin['username'];
            $_SESSION['flash'] = 'Welcome back, ' . ($_SESSION['admin_name']) . '!';

            // Update last_login
            try {
                $upd = $db->prepare('UPDATE admin_users SET last_login = NOW() WHERE id = :id');
                $upd->execute([':id' => $admin['id']]);
            } catch (Throwable $e) {
                // ignore update errors
            }

            // Clear login attempts for IP
            $del = $db->prepare('DELETE FROM login_attempts WHERE ip_address = :ip');
            $del->execute([':ip' => $ip]);

            if ($isAjax) ajax_response(['success' => true, 'message' => 'Admin authenticated', 'admin' => true]);
            safe_redirect(SITE_URL . '/admin/dashboard.php');
            exit;
        } else {
            // Admin exists but password incorrect -> treat as failed login
            $stmt = $db->prepare('SELECT id, attempts FROM login_attempts WHERE ip_address = :ip LIMIT 1');
            $stmt->execute([':ip' => $ip]);
            $row = $stmt->fetch();
            if ($row) {
                $attempts = $row['attempts'] + 1;
                $locked_until = null;
                if ($attempts >= $maxAttempts) {
                    $locked_until = date('Y-m-d H:i:s', time() + $lockoutMinutes * 60);
                }
                $upd = $db->prepare('UPDATE login_attempts SET attempts = :attempts, last_attempt = NOW(), locked_until = :locked WHERE id = :id');
                $upd->execute([':attempts' => $attempts, ':locked' => $locked_until, ':id' => $row['id']]);
            } else {
                $ins = $db->prepare('INSERT INTO login_attempts (ip_address, attempts, last_attempt, locked_until) VALUES (:ip, 1, NOW(), NULL)');
                $ins->execute([':ip' => $ip]);
            }

            if ($isAjax) ajax_response(['success' => false, 'message' => 'Invalid username or password.'], 401);
            $_SESSION['flash'] = 'Invalid username or password.';
            safe_redirect(SITE_URL . '/login_simple.php');
            exit;
        }
    }

    // Check lockout state
    $stmtLA = $db->prepare('SELECT attempts, locked_until FROM login_attempts WHERE ip_address = :ip LIMIT 1');
    $stmtLA->execute([':ip' => $ip]);
    $la = $stmtLA->fetch();
    if ($la && !empty($la['locked_until']) && strtotime($la['locked_until']) > time()) {
        $wait = ceil((strtotime($la['locked_until']) - time())/60);
        if ($isAjax) ajax_response(['success' => false, 'message' => "Too many failed attempts. Try again in {$wait} minutes."], 429);
        $_SESSION['flash'] = 'Too many failed attempts. Try again later.';
        safe_redirect('login_simple.php');
    }

    if (empty($login_input) || empty($password)) {
        if ($isAjax) ajax_response(['success' => false, 'message' => 'Please fill in both fields.'], 422);
        $_SESSION['flash'] = 'Please fill in both fields.';
        safe_redirect(SITE_URL . '/login_simple.php');
    }

    // Try lookup by username in users table first
    $stmtUser = $db->prepare('SELECT id, username, customer_id, password_hash FROM users WHERE username = :username LIMIT 1');
    $stmtUser->execute([':username' => $login_input]);
    $u = $stmtUser->fetch();

    if ($u) {
        $stmtCust = $db->prepare('SELECT id, first_name, last_name, email, password_hash, is_verified FROM customers WHERE id = :id LIMIT 1');
        $stmtCust->execute([':id' => $u['customer_id']]);
        $user = $stmtCust->fetch();
        $hashToVerify = $u['password_hash'] ?: ($user['password_hash'] ?? null);
    } else {
        $stmt = $db->prepare('SELECT id, first_name, last_name, email, password_hash, is_verified FROM customers WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $login_input]);
        $user = $stmt->fetch();
        $hashToVerify = $user['password_hash'] ?? null;
    }

    if (empty($user) || empty($hashToVerify) || !password_verify($password, $hashToVerify)) {
        // increment failed attempts
        $stmt = $db->prepare('SELECT id, attempts FROM login_attempts WHERE ip_address = :ip LIMIT 1');
        $stmt->execute([':ip' => $ip]);
        $row = $stmt->fetch();
        if ($row) {
            $attempts = $row['attempts'] + 1;
            $locked_until = null;
            if ($attempts >= $maxAttempts) {
                $locked_until = date('Y-m-d H:i:s', time() + $lockoutMinutes * 60);
            }
            $upd = $db->prepare('UPDATE login_attempts SET attempts = :attempts, last_attempt = NOW(), locked_until = :locked WHERE id = :id');
            $upd->execute([':attempts' => $attempts, ':locked' => $locked_until, ':id' => $row['id']]);
        } else {
            $ins = $db->prepare('INSERT INTO login_attempts (ip_address, attempts, last_attempt, locked_until) VALUES (:ip, 1, NOW(), NULL)');
            $ins->execute([':ip' => $ip]);
        }

        if ($isAjax) ajax_response(['success' => false, 'message' => 'Invalid username or password.'], 401);
        $_SESSION['flash'] = 'Invalid username or password.';
        safe_redirect(SITE_URL . '/login_simple.php');
    }

    // Enforce email verification
    if (isset($user['is_verified']) && $user['is_verified'] == 0) {
        if ($isAjax) ajax_response(['success' => false, 'message' => 'Please verify your email before logging in. Check your inbox.'], 403);
        $_SESSION['flash'] = 'Please verify your email before logging in. Check your inbox.';
        redirect(SITE_URL . '/login_simple.php');
    }

    // Login success: set session
    $_SESSION['customer_id'] = $user['id'];
    $_SESSION['customer_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['flash'] = 'Welcome back, ' . $user['first_name'] . '!';

    // Clear login attempts for IP
    $del = $db->prepare('DELETE FROM login_attempts WHERE ip_address = :ip');
    $del->execute([':ip' => $ip]);

    // Remember me: create persistent token
    if ($remember) {
        $selector = bin2hex(random_bytes(8));
        $token = bin2hex(random_bytes(32));
        $tokenHash = password_hash($token, PASSWORD_DEFAULT);
        $expiresAt = date('Y-m-d H:i:s', time() + $rememberDays * 24 * 60 * 60);
        $ins = $db->prepare('INSERT INTO remember_tokens (selector, token_hash, customer_id, expires_at, user_agent, ip_address) VALUES (:selector, :token_hash, :customer_id, :expires_at, :ua, :ip)');
        $ins->execute([
            ':selector' => $selector,
            ':token_hash' => $tokenHash,
            ':customer_id' => $user['id'],
            ':expires_at' => $expiresAt,
            ':ua' => substr($userAgent, 0, 255),
            ':ip' => $ip
        ]);
        // set cookie: selector:token
        setcookie('remember', $selector . ':' . $token, time() + $rememberDays * 24 * 60 * 60, '/', '', false, true);
    }

    // Successful AJAX response
        if ($isAjax) {
            $cartCount = get_cart_count();
            ajax_response([
                'success' => true,
                'message' => 'Welcome back, ' . $user['first_name'] . '!',
                'customer_name' => $_SESSION['customer_name'],
                'cart_count' => $cartCount
            ]);
        }

        safe_redirect(SITE_URL . '/index.php');

} catch (Throwable $e) {
    // Detailed server-side logging for debugging
    $msg = '[' . date('Y-m-d H:i:s') . '] Login error: ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    $logDir = __DIR__ . '/storage/logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    @file_put_contents(__DIR__ . '/storage/logs/login_debug.log', $msg, FILE_APPEND);
    error_log('Login error: ' . $e->getMessage());
    if ($isAjax) ajax_response(['success' => false, 'message' => 'Server error. Please try again.'], 500);
    $_SESSION['flash'] = 'Login failed. Please try again.';
    redirect('login_simple.php');
}
