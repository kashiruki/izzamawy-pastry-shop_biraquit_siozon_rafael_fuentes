<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Allow force-login via GET ?force=1 but require safety checks:
    // - request IP must be in ADMIN_FORCE_ALLOWED_IPS OR
    // - a correct secret provided via ?force_key=SECRET when ADMIN_FORCE_SECRET is set
    if (isset($_GET['force']) && $_GET['force'] === '1') {
        $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
        $allowed = false;

        if (defined('ADMIN_FORCE_ALLOWED_IPS') && is_array(ADMIN_FORCE_ALLOWED_IPS)) {
            if (in_array($remoteIp, ADMIN_FORCE_ALLOWED_IPS, true)) {
                $allowed = true;
            }
        }

        if (!$allowed && defined('ADMIN_FORCE_SECRET') && ADMIN_FORCE_SECRET !== '' && isset($_GET['force_key'])) {
            // Use hash_equals to mitigate timing attacks
            if (hash_equals(ADMIN_FORCE_SECRET, $_GET['force_key'])) {
                $allowed = true;
            }
        }

        if (!$allowed) {
            error_log('Unauthorized force-login attempt from IP: ' . $remoteIp);
            $_SESSION['flash'] = 'Force-login not permitted from your location.';
            redirect(SITE_URL . '/admin/admin_login.php');
        }

        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare('SELECT id, username, full_name, is_active FROM admin_users WHERE is_active = 1 ORDER BY id ASC LIMIT 1');
            $stmt->execute();
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin) {
                // set admin session without password (force mode)
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_name'] = $admin['full_name'] ?? $admin['username'];

                // update last_login
                $u = $db->prepare('UPDATE admin_users SET last_login = NOW() WHERE id = :id');
                $u->execute([':id' => $admin['id']]);

                redirect(SITE_URL . '/admin/dashboard.php');
            }
            $_SESSION['flash'] = 'No active admin user found for force-login.';
            redirect(SITE_URL . '/admin/admin_login.php');
        } catch (Throwable $e) {
            error_log('Force admin login error: ' . $e->getMessage());
            $_SESSION['flash'] = 'Force login failed.';
            redirect(SITE_URL . '/admin/admin_login.php');
        }
    }

    redirect(SITE_URL . '/admin/admin_login.php');
}

$username = sanitize_input($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    $_SESSION['flash'] = 'Please enter username and password.';
    redirect(SITE_URL . '/admin/admin_login.php');
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare('SELECT id, username, full_name, password_hash, is_active FROM admin_users WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => $username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || empty($admin['is_active'])) {
        $_SESSION['flash'] = 'Invalid username or password.';
        redirect(SITE_URL . '/admin/admin_login.php');
    }

    if (!password_verify($password, $admin['password_hash'])) {
        $_SESSION['flash'] = 'Invalid username or password.';
        redirect(SITE_URL . '/admin/admin_login.php');
    }

    // success
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_name'] = $admin['full_name'] ?? $admin['username'];

    // update last_login
    $u = $db->prepare('UPDATE admin_users SET last_login = NOW() WHERE id = :id');
    $u->execute([':id' => $admin['id']]);

    redirect(SITE_URL . '/admin/dashboard.php');

} catch (Throwable $e) {
    error_log('Admin login error: ' . $e->getMessage());
    $_SESSION['flash'] = 'Login failed. Please try again.';
    redirect(SITE_URL . '/admin/admin_login.php');
}
