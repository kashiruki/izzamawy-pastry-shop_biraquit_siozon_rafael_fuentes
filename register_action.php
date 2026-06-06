<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('register.php');
}

$first_name = sanitize_input($_POST['first_name'] ?? '');
$last_name = sanitize_input($_POST['last_name'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$phone = sanitize_input($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$address = sanitize_input($_POST['address'] ?? '');
$city = sanitize_input($_POST['city'] ?? '');
$province = sanitize_input($_POST['province'] ?? '');
$postal_code = sanitize_input($_POST['postal_code'] ?? '');

// Basic validation
if (!$first_name || !$last_name || !$email || !$password || !$confirm_password) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please fill in all required fields.'];
    redirect('register.php');
}

if ($password !== $confirm_password) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Passwords do not match.'];
    redirect('register.php');
}

if (strlen($password) < 6) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Password must be at least 6 characters.'];
    redirect('register.php');
}

try {
    $db = Database::getInstance()->getConnection();

    // Check for existing email
    $stmt = $db->prepare('SELECT id FROM customers WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'An account with that email already exists.'];
        redirect('register.php');
    }

    // Insert new customer
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $insert = $db->prepare('INSERT INTO customers (first_name, last_name, email, phone, password_hash, address, city, province, postal_code) VALUES (:first_name, :last_name, :email, :phone, :password_hash, :address, :city, :province, :postal_code)');
    $insert->execute([
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':email' => $email,
        ':phone' => $phone,
        ':password_hash' => $password_hash,
        ':address' => $address,
        ':city' => $city,
        ':province' => $province,
        ':postal_code' => $postal_code
    ]);

    $customer_id = $db->lastInsertId();

    // Log the user in
    $_SESSION['customer_id'] = $customer_id;
    $_SESSION['customer_name'] = $first_name . ' ' . $last_name;

    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Account created and logged in.'];
    redirect('index.php');

} catch (Exception $e) {
    // In production, do not expose the raw error message
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Registration failed. Please try again.'];
    redirect('register.php');
}
