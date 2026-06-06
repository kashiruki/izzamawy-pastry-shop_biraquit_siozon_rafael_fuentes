<?php
/**
 * CLI helper: set admin password to 'admin123'
 * Usage: php tools/set_admin_password.php [username] [password]
 * Defaults: username=admin password=admin123
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$username = $argv[1] ?? 'admin';
$password = $argv[2] ?? 'admin123';

try {
    $db = Database::getInstance()->getConnection();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('UPDATE admin_users SET password_hash = :hash WHERE username = :username');
    $stmt->execute([':hash' => $hash, ':username' => $username]);
    if ($stmt->rowCount() > 0) {
        echo "Password for '{$username}' updated successfully.\n";
    } else {
        // maybe user doesn't exist — offer to insert
        echo "No admin user '{$username}' found. Do you want to create it? (y/N): ";
        $handle = fopen('php://stdin', 'r');
        $line = trim(fgets($handle));
        if (strtolower($line) === 'y') {
            $ins = $db->prepare('INSERT INTO admin_users (username, password_hash, full_name, email, role, is_active) VALUES (:username, :hash, :full, :email, :role, 1)');
            $ins->execute([':username' => $username, ':hash' => $hash, ':full' => 'Administrator', ':email' => 'admin@example.com', ':role' => 'admin']);
            echo "Admin user '{$username}' created with provided password.\n";
        } else {
            echo "Aborted.\n";
        }
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
