<?php
require_once __DIR__ . '/config/config.php';

// Clear customer session
unset($_SESSION['customer_id']);
unset($_SESSION['customer_name']);
// Optionally clear cart
// unset($_SESSION['cart']);

redirect('index.php');
