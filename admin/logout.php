<?php
/**
 * Admin Logout
 * Izzamawy Pastry and Delicacies
 */

session_start();

// Destroy all session data
$_SESSION = [];
session_destroy();

// Redirect to public login page
header('Location: ../login_simple.php');
exit;
