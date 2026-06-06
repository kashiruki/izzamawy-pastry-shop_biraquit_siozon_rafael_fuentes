<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$action = $_POST['action'] ?? '';
$db = Database::getInstance()->getConnection();

try {
    if ($action === 'update_profile') {
        $first_name = sanitize_input($_POST['first_name'] ?? '');
        $last_name = sanitize_input($_POST['last_name'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);

        if (!$first_name || !$last_name || !$email) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please fill in required fields.'];
            redirect('account.php');
        }

        // Check if email is used by another account
        $stmt = $db->prepare('SELECT id FROM customers WHERE email = :email AND id != :id LIMIT 1');
        $stmt->execute([':email' => $email, ':id' => $_SESSION['customer_id']]);
        if ($stmt->fetch()) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'That email is already in use.'];
            redirect('account.php');
        }

        $update = $db->prepare('UPDATE customers SET first_name = :first_name, last_name = :last_name, phone = :phone, email = :email WHERE id = :id');
        $update->execute([
            ':first_name' => $first_name,
            ':last_name' => $last_name,
            ':phone' => $phone,
            ':email' => $email,
            ':id' => $_SESSION['customer_id']
        ]);

        $_SESSION['customer_name'] = $first_name . ' ' . $last_name;
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Profile updated successfully.'];
        redirect('account.php');
    }

    if ($action === 'update_address') {
        $address = sanitize_input($_POST['address'] ?? '');
        $city = sanitize_input($_POST['city'] ?? '');
        $province = sanitize_input($_POST['province'] ?? '');
        $postal_code = sanitize_input($_POST['postal_code'] ?? '');

        $update = $db->prepare('UPDATE customers SET address = :address, city = :city, province = :province, postal_code = :postal_code WHERE id = :id');
        $update->execute([
            ':address' => $address,
            ':city' => $city,
            ':province' => $province,
            ':postal_code' => $postal_code,
            ':id' => $_SESSION['customer_id']
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Address saved.'];
        redirect('account.php');
    }

} catch (Exception $e) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Unable to save changes.'];
    redirect('account.php');
}
