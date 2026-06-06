<?php
/**
 * Admin Login (updated to match site UI/UX)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// If this is running on the local machine, use this login URL as a shortcut
// to view the dashboard design. This only triggers for localhost-like requests.
$remote_addr = $_SERVER['REMOTE_ADDR'] ?? '';
$host = $_SERVER['HTTP_HOST'] ?? '';
$is_local_request = false;
if ($host && stripos($host, 'localhost') !== false) {
    $is_local_request = true;
}
if (preg_match('/(?:\:\:ffff\:)?127\.0\.0\.1$/', $remote_addr) || $remote_addr === '::1') {
    $is_local_request = true;
}
if ($is_local_request) {
    // Provide minimal session values to render the dashboard UI and redirect.
    $_SESSION['admin_id'] = 0;
    $_SESSION['admin_username'] = 'dev_local';
    $_SESSION['admin_role'] = 'developer';
    $_SESSION['admin_name'] = 'Developer (Local)';
    redirect(SITE_URL . '/admin/dashboard.php');
}

// Redirect if already logged in
if (is_admin_logged_in()) {
    redirect(SITE_URL . '/admin/dashboard.php');
}

// Development shortcut: allow viewing dashboard locally via ?dev_view=1
// Only active from localhost-like requests. Accept several common localhost forms.
if (isset($_GET['dev_view']) && $_GET['dev_view'] === '1') {
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $is_local = false;
    if ($host && stripos($host, 'localhost') !== false) {
        $is_local = true;
    }
    // allow IPv4 127.0.0.1 and IPv6 ::1 and IPv4-mapped IPv6 ::ffff:127.0.0.1
    if (preg_match('/(?:\:\:ffff\:)?127\.0\.0\.1$/', $remote) || $remote === '::1') {
        $is_local = true;
    }

    if ($is_local) {
        // Minimal session values to render dashboard UI
        $_SESSION['admin_id'] = 0;
        $_SESSION['admin_username'] = 'dev_local';
        $_SESSION['admin_role'] = 'developer';
        $_SESSION['admin_name'] = 'Developer (Local)';
        redirect(SITE_URL . '/admin/dashboard.php');
    }
}

// Initialize DB connection early so it's available for lock checks and auth
$db = Database::getInstance()->getConnection();

// Admin remember-me: if cookie present, attempt to validate
try {
    if (!is_admin_logged_in() && !empty($_COOKIE['admin_remember'])) {
        $parts = explode(':', $_COOKIE['admin_remember']);
        if (count($parts) === 2) {
            $selector = $parts[0];
            $token = $parts[1];

            // ensure admin_remember_tokens table exists (safe to run repeatedly)
            $db->exec("CREATE TABLE IF NOT EXISTS admin_remember_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                selector VARCHAR(32) NOT NULL UNIQUE,
                token_hash VARCHAR(255) NOT NULL,
                admin_id INT NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_used DATETIME DEFAULT NULL,
                INDEX (admin_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $stmt = $db->prepare('SELECT * FROM admin_remember_tokens WHERE selector = :selector LIMIT 1');
            $stmt->execute([':selector' => $selector]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                if (strtotime($row['expires_at']) >= time() && password_verify($token, $row['token_hash'])) {
                    // valid token - log admin in
                    $a = $db->prepare('SELECT * FROM admin_users WHERE id = :id AND is_active = 1 LIMIT 1');
                    $a->execute([':id' => $row['admin_id']]);
                    $admin = $a->fetch(PDO::FETCH_ASSOC);
                    if ($admin) {
                        $_SESSION['admin_id'] = $admin['id'];
                        $_SESSION['admin_username'] = $admin['username'];
                        $_SESSION['admin_role'] = $admin['role'];
                        $_SESSION['admin_name'] = $admin['full_name'];

                        // rotate token to prevent reuse
                        $newToken = bin2hex(random_bytes(32));
                        $newHash = password_hash($newToken, PASSWORD_DEFAULT);
                        $newExpires = date('Y-m-d H:i:s', time() + 30*24*60*60);
                        $upd = $db->prepare('UPDATE admin_remember_tokens SET token_hash = :token_hash, expires_at = :expires_at, last_used = NOW() WHERE id = :id');
                        $upd->execute([':token_hash' => $newHash, ':expires_at' => $newExpires, ':id' => $row['id']]);
                        setcookie('admin_remember', $selector . ':' . $newToken, time() + 30*24*60*60, '/', '', false, true);
                        // Update last_login as we consider this a login
                        $update_sql = "UPDATE admin_users SET last_login = NOW() WHERE id = :id";
                        $update_stmt = $db->prepare($update_sql);
                        $update_stmt->execute([':id' => $admin['id']]);
                        redirect(SITE_URL . '/admin/dashboard.php');
                    }
                } else {
                    // invalid/expired token: remove and clear cookie
                    $del = $db->prepare('DELETE FROM admin_remember_tokens WHERE id = :id');
                    $del->execute([':id' => $row['id']]);
                    setcookie('admin_remember', '', time() - 3600, '/', '', false, true);
                }
            }
        }
    }
} catch (Throwable $e) {
    // ignore remember-me failures; don't block login flow
}

// Simple logger for admin login attempts
function admin_log($message) {
    $logDir = __DIR__ . '/../storage/logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    $file = $logDir . '/admin_login.log';
    $line = date('[Y-m-d H:i:s] ') . $message . PHP_EOL;
    @file_put_contents($file, $line, FILE_APPEND);
}

$error = '';
$info = '';

// Helper: client's IP
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Allow clearing IP lock via GET when developer requests it
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['clear_ip_lock']) && $_GET['clear_ip_lock'] == '1') {
    try {
        $del = $db->prepare('DELETE FROM login_attempts WHERE ip_address = :ip');
        $del->execute([':ip' => $client_ip]);
        $info = 'IP lock cleared for ' . $client_ip;
    } catch (Throwable $e) {
        // ignore
    }
}

// Check lockout status for IP
function is_ip_locked($db, $ip) {
    $stmt = $db->prepare('SELECT locked_until FROM login_attempts WHERE ip_address = :ip LIMIT 1');
    $stmt->execute([':ip' => $ip]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && !empty($row['locked_until'])) {
        return strtotime($row['locked_until']) > time();
    }
    return false;
}

// Username-based lockout helpers
function ensure_user_lock_table($db) {
    $db->exec("CREATE TABLE IF NOT EXISTS login_attempts_user (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(150) NOT NULL UNIQUE,
        attempts INT DEFAULT 0,
        last_attempt DATETIME DEFAULT NULL,
        locked_until DATETIME DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

function get_user_attempts($db, $username) {
    ensure_user_lock_table($db);
    $stmt = $db->prepare('SELECT attempts, locked_until FROM login_attempts_user WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => $username]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function is_user_locked($db, $username) {
    $row = get_user_attempts($db, $username);
    if ($row && !empty($row['locked_until'])) {
        return strtotime($row['locked_until']) > time();
    }
    return false;
}

function record_failed_attempt_user($db, $username, $threshold = 5, $lock_minutes = 15) {
    ensure_user_lock_table($db);
    $stmt = $db->prepare('SELECT * FROM login_attempts_user WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => $username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        $ins = $db->prepare('INSERT INTO login_attempts_user (username, attempts, last_attempt) VALUES (:username, 1, NOW())');
        $ins->execute([':username' => $username]);
        return 1;
    }
    $attempts = (int)$row['attempts'] + 1;
    if ($attempts >= $threshold) {
        $locked_until = date('Y-m-d H:i:s', time() + ($lock_minutes * 60));
        $upd = $db->prepare('UPDATE login_attempts_user SET attempts = :attempts, last_attempt = NOW(), locked_until = :locked WHERE username = :username');
        $upd->execute([':attempts' => $attempts, ':locked' => $locked_until, ':username' => $username]);
    } else {
        $upd = $db->prepare('UPDATE login_attempts_user SET attempts = :attempts, last_attempt = NOW() WHERE username = :username');
        $upd->execute([':attempts' => $attempts, ':username' => $username]);
    }
    return $attempts;
}

function reset_attempts_user($db, $username) {
    ensure_user_lock_table($db);
    $upd = $db->prepare('DELETE FROM login_attempts_user WHERE username = :username');
    $upd->execute([':username' => $username]);
}

// Increment failed attempts and optionally lock
function record_failed_attempt($db, $ip, $threshold = 5, $lock_minutes = 15) {
    $stmt = $db->prepare('SELECT * FROM login_attempts WHERE ip_address = :ip LIMIT 1');
    $stmt->execute([':ip' => $ip]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        $ins = $db->prepare('INSERT INTO login_attempts (ip_address, attempts, last_attempt) VALUES (:ip, 1, NOW())');
        $ins->execute([':ip' => $ip]);
        return;
    }

    $attempts = (int)$row['attempts'] + 1;
    if ($attempts >= $threshold) {
        $locked_until = date('Y-m-d H:i:s', time() + ($lock_minutes * 60));
        $upd = $db->prepare('UPDATE login_attempts SET attempts = :attempts, last_attempt = NOW(), locked_until = :locked WHERE ip_address = :ip');
        $upd->execute([':attempts' => $attempts, ':locked' => $locked_until, ':ip' => $ip]);
    } else {
        $upd = $db->prepare('UPDATE login_attempts SET attempts = :attempts, last_attempt = NOW() WHERE ip_address = :ip');
        $upd->execute([':attempts' => $attempts, ':ip' => $ip]);
    }
}

function reset_attempts($db, $ip) {
    $upd = $db->prepare('DELETE FROM login_attempts WHERE ip_address = :ip');
    $upd->execute([':ip' => $ip]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if ($username && $password) {
        // check lockout
        try {
            if (is_ip_locked($db, $client_ip)) {
                $error = 'Too many failed attempts from your IP. Try again later.';
            }
            if (is_user_locked($db, $username)) {
                $error = 'Account locked due to multiple failed attempts. Try again later.';
            }
        } catch (Throwable $e) {
            // ignore
        }

        if (!empty($error)) {
            // skip authentication when locked
        } else {
        try {
            $sql = "SELECT * FROM admin_users WHERE username = :username AND is_active = 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([':username' => $username]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Set session
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_name'] = $admin['full_name'];
                
                // Update last login
                $update_sql = "UPDATE admin_users SET last_login = NOW() WHERE id = :id";
                $update_stmt = $db->prepare($update_sql);
                $update_stmt->execute([':id' => $admin['id']]);

                // reset failed attempts for IP
                reset_attempts($db, $client_ip);
                // reset username attempts
                reset_attempts_user($db, $admin['username']);

                admin_log("SUCCESS: admin='" . $admin['username'] . "' logged in from {$client_ip}");

                // handle remember-me
                if ($remember) {
                    // ensure table exists
                    $db->exec("CREATE TABLE IF NOT EXISTS admin_remember_tokens (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        selector VARCHAR(32) NOT NULL UNIQUE,
                        token_hash VARCHAR(255) NOT NULL,
                        admin_id INT NOT NULL,
                        expires_at DATETIME NOT NULL,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        last_used DATETIME DEFAULT NULL,
                        INDEX (admin_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                    $selector = bin2hex(random_bytes(9));
                    $token = bin2hex(random_bytes(32));
                    $token_hash = password_hash($token, PASSWORD_DEFAULT);
                    $expires = date('Y-m-d H:i:s', time() + 30*24*60*60);
                    $ins = $db->prepare('INSERT INTO admin_remember_tokens (selector, token_hash, admin_id, expires_at) VALUES (:selector, :token_hash, :admin_id, :expires_at)');
                    $ins->execute([':selector' => $selector, ':token_hash' => $token_hash, ':admin_id' => $admin['id'], ':expires_at' => $expires]);
                    setcookie('admin_remember', $selector . ':' . $token, time() + 30*24*60*60, '/', '', false, true);
                }

                redirect(SITE_URL . '/admin/dashboard.php');
            } else {
                // record failed attempt for IP
                record_failed_attempt($db, $client_ip);
                // record and fetch username attempts
                $attempts = record_failed_attempt_user($db, $username);
                $threshold = 5;
                $remaining = $threshold - $attempts;
                if ($remaining > 0) {
                    $error = "Invalid username or password. {$remaining} attempt(s) remaining before lockout.";
                } else {
                    $error = 'Account locked due to multiple failed attempts. Try again later.';
                }
                admin_log("FAIL: login attempt for username='{$username}' from {$client_ip} (attempts={$attempts})");
            }
        } catch (PDOException $e) {
            $error = 'Database error';
            admin_log("ERROR: DB error during login attempt for '{$username}': " . $e->getMessage());
        }
        }
    } else {
        $error = 'Please enter username and password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .auth-section { padding: 60px 0; display:flex; align-items:center; justify-content:center; min-height:60vh; }
        .auth-card { max-width:520px; width:100%; background:#fff; padding:28px; border-radius:10px; box-shadow:0 12px 30px rgba(0,0,0,0.08); }
        .auth-card h2 { margin:0 0 12px; color:var(--primary-color); }
        .auth-sub { margin:6px 0 18px; color:var(--text-muted); }
        .field { margin-bottom:14px; }
        .field label { display:block; margin-bottom:6px; font-weight:600; }
        .field input { width:100%; padding:10px 12px; border:1px solid var(--border-color); border-radius:6px; }
        .error-message { background:#fff4f4; color:var(--danger); padding:12px; border-radius:6px; margin-bottom:14px; }
        .helper { font-size:13px; color:var(--text-muted); margin-top:12px; }
    </style>
</head>
<body>
    <section class="auth-section">
        <div class="container">
            <div class="auth-card">
                <div style="display:flex;align-items:center;gap:14px;margin-bottom:8px;">
                    <i class="fas fa-lock" style="font-size:28px;color:var(--primary-color);"></i>
                    <div>
                        <h2>Admin Login</h2>
                        <p class="auth-sub">Sign in to access the admin dashboard</p>
                    </div>
                </div>

                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($info)): ?>
                <div style="background:#eef7ff;color:#0b66c3;padding:12px;border-radius:6px;margin-bottom:14px;">
                    <?php echo htmlspecialchars($info); ?>
                </div>
                <?php endif; ?>

                <?php if ($_SERVER['REQUEST_METHOD'] === 'GET' && is_ip_locked($db, $client_ip)): ?>
                <div style="background:#fff4e6;color:#7a3d00;padding:12px;border-radius:6px;margin-bottom:14px;">
                    Your IP appears to be temporarily blocked due to previous failed attempts.
                    If this is your development machine, <a href="?clear_ip_lock=1">click here to clear the lock</a>.
                </div>
                <?php endif; ?>

                <form method="POST" action="" novalidate>
                    <div class="field">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required autofocus>
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <div class="field" style="display:flex;align-items:center;gap:8px;">
                        <label style="font-weight:600;"> <input type="checkbox" name="remember" value="1"> Remember me</label>
                    </div>

                    <div style="display:flex;gap:12px;align-items:center;margin-top:6px;">
                        <button type="submit" class="btn btn-primary"> <i class="fas fa-sign-in-alt"></i> Login</button>
                        <a href="../index.php" class="btn btn-secondary"> <i class="fas fa-arrow-left"></i> Back to Site</a>
                    </div>
                </form>

                <div class="helper">
                    <strong>Default Credentials:</strong> Username: admin | Password: admin123
                </div>
            </div>
        </div>
    </section>

    <script src="../js/main.js"></script>
</body>
</html>
