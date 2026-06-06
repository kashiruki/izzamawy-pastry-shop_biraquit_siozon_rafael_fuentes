<?php
/**
 * General Configuration
 * Izzamawy Pastry Shop
 */

// Site Configuration
define('SITE_NAME', 'Izzamawy Pastry and Delicacies');
define('SITE_TAGLINE', 'Authentic Filipino Pasalubong & Delicacies');
define('SITE_URL', 'http://localhost/izzamawy-pastry-shop');
define('ADMIN_EMAIL', 'admin@izzamawy.com');

// Admin force-login safety: set a secret key and allowed IPs to protect ?force=1
// By default, allow only localhost addresses. Set `ADMIN_FORCE_SECRET` to a
// random value to allow remote force-login using ?force_key=THE_SECRET.
define('ADMIN_FORCE_SECRET', ''); // e.g. 'change_this_to_a_random_string'
define('ADMIN_FORCE_ALLOWED_IPS', ['127.0.0.1', '::1']);

// Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Remember-me: check cookie and auto-login if valid
if (!isset($_SESSION['customer_id']) && !empty($_COOKIE['remember'])) {
    // Cookie format: selector:token
    $parts = explode(':', $_COOKIE['remember']);
    if (count($parts) === 2) {
        $selector = $parts[0];
        $token = $parts[1];
        // Attempt to validate against DB
        try {
            require_once __DIR__ . '/database.php';
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare('SELECT id, customer_id, token_hash, expires_at FROM remember_tokens WHERE selector = :selector LIMIT 1');
            $stmt->execute([':selector' => $selector]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                if (strtotime($row['expires_at']) >= time() && password_verify($token, $row['token_hash'])) {
                    // valid token, log the user in
                    $custStmt = $db->prepare('SELECT id, first_name, last_name FROM customers WHERE id = :id LIMIT 1');
                    $custStmt->execute([':id' => $row['customer_id']]);
                    $cust = $custStmt->fetch(PDO::FETCH_ASSOC);
                    if ($cust) {
                        $_SESSION['customer_id'] = $cust['id'];
                        $_SESSION['customer_name'] = $cust['first_name'] . ' ' . $cust['last_name'];
                        // rotate token to prevent reuse
                        $newToken = bin2hex(random_bytes(16));
                        $newHash = password_hash($newToken, PASSWORD_DEFAULT);
                        $newExpires = date('Y-m-d H:i:s', time() + 30*24*60*60);
                        $upd = $db->prepare('UPDATE remember_tokens SET token_hash = :token_hash, expires_at = :expires_at, last_used = NOW() WHERE id = :id');
                        $upd->execute([':token_hash' => $newHash, ':expires_at' => $newExpires, ':id' => $row['id']]);
                        setcookie('remember', $selector . ':' . $newToken, time() + 30*24*60*60, '/', '', false, true);
                    }
                } else {
                    // invalid or expired: remove
                    $del = $db->prepare('DELETE FROM remember_tokens WHERE id = :id');
                    $del->execute([':id' => $row['id']]);
                    setcookie('remember', '', time() - 3600, '/', '', false, true);
                }
            }
        } catch (Throwable $e) {
            // ignore remember-me failures
        }
    }
}

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Manila');

// Shipping Configuration
define('SHIPPING_FEE_METRO_MANILA', 100);
define('SHIPPING_FEE_PROVINCIAL', 200);
define('FREE_SHIPPING_THRESHOLD', 1000);
// Per-municipality shipping rates (editable in one central place)
define('SHIPPING_RATES', [
    'Apalit' => 120,
    'Arayat' => 120,
    'Bacolor' => 140,
    'Candaba' => 180,
    'Floridablanca' => 140,
    'Guagua' => 120,
    'Lubao' => 180,
    'Macabebe' => 160,
    'Magalang' => 130,
    'Masantol' => 150,
    'Mexico' => 100,
    'Minalin' => 155,
    'Porac' => 220,
    'San Luis' => 200,
    'San Simon' => 130,
    'Santa Ana' => 130,
    'Santa Rita' => 130,
    'Santo Tomas' => 140,
    'Sasmuan' => 160,
]);
// Per-municipality estimated delivery in days (editable centrally)
define('SHIPPING_ESTIMATES', [
    'Apalit' => 1,
    'Arayat' => 2,
    'Bacolor' => 1,
    'Candaba' => 3,
    'Floridablanca' => 2,
    'Guagua' => 1,
    'Lubao' => 3,
    'Macabebe' => 2,
    'Magalang' => 2,
    'Masantol' => 2,
    'Mexico' => 1,
    'Minalin' => 2,
    'Porac' => 4,
    'San Luis' => 3,
    'San Simon' => 2,
    'Santa Ana' => 2,
    'Santa Rita' => 2,
    'Santo Tomas' => 1,
    'Sasmuan' => 2,
]);

// Payment Methods (only enabled methods shown in checkout)
define('PAYMENT_METHODS', [
    'cod' => 'Cash on Delivery',
    'gcash' => 'GCash'
]);

// Image Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/../images/products/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Helper Functions
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function format_price($price) {
    return '₱' . number_format($price, 2);
}

function generate_order_number() {
    return 'IPS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function redirect($url) {
    header("Location: " . $url);
    exit;
}

function is_logged_in() {
    return isset($_SESSION['customer_id']);
}

function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

function get_cart_count() {
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        return array_sum(array_column($_SESSION['cart'], 'quantity'));
    }
    return 0;
}

function calculate_cart_total() {
    $total = 0;
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['quantity'];
        }
    }
    return $total;
}

// SMTP / Mail Configuration (fill with your SMTP provider credentials)
// Defaults set to Gmail SMTP. To deliver OTPs reliably to Gmail addresses,
// set `SMTP_USER` to your Gmail address and `SMTP_PASS` to an App Password.
// If you use Google 2FA, create an App Password and use it here.
// See: https://support.google.com/accounts/answer/185833
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your@gmail.com');
define('SMTP_PASS', 'your_app_password');
define('SMTP_SECURE', 'tls'); // 'tls' for port 587, or 'ssl' for port 465
define('MAIL_FROM_NAME', SITE_NAME);

// (Mailgun support removed) Use PHPMailer (SMTP) or PHP mail() instead.

// If SMTP_USER is set to a real address, prefer using it as the from address
// but only define the constant if it hasn't already been defined to avoid warnings.
if (!defined('MAIL_FROM_EMAIL') && defined('SMTP_USER') && !empty(SMTP_USER)) {
    define('MAIL_FROM_EMAIL', SMTP_USER);
}

// Ensure MAIL_FROM_EMAIL is always defined
if (!defined('MAIL_FROM_EMAIL')) {
    define('MAIL_FROM_EMAIL', ADMIN_EMAIL);
}

// send_email: uses PHPMailer if available (composer) otherwise falls back to mail()
function send_email($to, $subject, $body, $altBody = '') {
    // Mailgun support removed — fall through to PHPMailer (SMTP) or PHP mail().

    // Try PHPMailer via Composer autoload
    $composerAutoload = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($composerAutoload)) {
        try {
            require_once $composerAutoload;
        } catch (Throwable $e) {
            $composerAutoload = null;
        }

        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                // Server settings
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USER;
                $mail->Password = SMTP_PASS;
                // Allow empty SMTP_SECURE for no encryption
                if (!empty(SMTP_SECURE)) {
                    $mail->SMTPSecure = SMTP_SECURE;
                }
                $mail->Port = SMTP_PORT;
                // For local dev environments with self-signed certs
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    ],
                ];

                $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
                $mail->addAddress($to);
                $mail->Subject = $subject;
                // Prefer HTML body when provided
                if (!empty($altBody)) {
                    $mail->isHTML(true);
                    $mail->Body = $body;
                    $mail->AltBody = $altBody;
                } else {
                    $mail->isHTML(false);
                    $mail->Body = $body;
                }

                $mail->send();
                return true;
            } catch (Throwable $e) {
                $log = __DIR__ . '/../storage/logs/mail_debug.log';
                @file_put_contents($log, date('[Y-m-d H:i:s] ') . "PHPMailer error: " . $e->getMessage() . "\n", FILE_APPEND);
                // fallthrough to mail() fallback
            }
        } else {
            $log = __DIR__ . '/../storage/logs/mail_debug.log';
            @file_put_contents($log, date('[Y-m-d H:i:s] ') . "PHPMailer class not available via composer autoload\n", FILE_APPEND);
        }
    }

    // If SMTP credentials are configured but PHPMailer wasn't used, log a helpful message
    if (!defined('PHPMailer\\Loaded') && !empty(SMTP_USER) && !empty(SMTP_PASS)) {
        $log = __DIR__ . '/../storage/logs/mail_debug.log';
        @file_put_contents($log, date('[Y-m-d H:i:s] ') . "SMTP credentials present but PHPMailer not available; run: composer require phpmailer/phpmailer\n", FILE_APPEND);
    }

    // Fallback to PHP mail() — may not deliver reliably to Gmail.
    $headers = "From: " . MAIL_FROM_EMAIL . "\r\n" .
               "Reply-To: " . MAIL_FROM_EMAIL . "\r\n" .
               "X-Mailer: PHP/" . phpversion();
    return (bool) @mail($to, $subject, $body, $headers);
}

/**
 * Get product image or a fallback from product_pictures directory.
 * Returns a web-path relative to project root.
 */
function get_product_image($image_url = null) {
    // If an image URL is provided and file exists, use it
    if ($image_url) {
        $path = $image_url;
        // support both absolute and relative paths
        $full = strpos($path, '/') === 0 ? __DIR__ . '/..' . $path : __DIR__ . '/../' . ltrim($path, '/');
        if (file_exists($full)) {
            return $path;
        }
    }

    // Scan product_pictures for an available image
    $dir = __DIR__ . '/../product_pictures';
    $allowed = ['jpg','jpeg','png','gif','webp'];
    $files = [];
    if (is_dir($dir)) {
        foreach (scandir($dir) as $f) {
            if ($f === '.' || $f === '..') continue;
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed) && is_file($dir . '/' . $f)) {
                $files[] = $f;
            }
        }
    }

    if (empty($files)) {
        return 'images/placeholder.jpg';
    }

    // Return first image for consistency
    return 'product_pictures/' . $files[0];
}
