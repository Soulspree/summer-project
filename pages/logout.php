<?php
/**
 * Logout Handler
 * Musician Booking System
 */

define('SYSTEM_ACCESS', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: /musician-booking-system/pages/login.php');
    exit;
}

// Handle logout
$user = new User();
$user->logout();

// Set success message
setFlashMessage('success', 'You have been successfully logged out');

// Redirect to home page
header('Location: /musician-booking-system/index.php');
exit;
?>