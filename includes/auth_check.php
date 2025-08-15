<?php
/**
 * Authentication Check Middleware
 * Musician Booking System
 */

// Security check
if (!defined('SYSTEM_ACCESS')) {
    die('Direct access not permitted');
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

/**
 * Check if user is authenticated for musician pages
 */
function requireMusicianAuth() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /musician-booking-system/pages/login.php?error=login_required');
        exit;
    }
    
    if (!hasRole(USER_TYPE_MUSICIAN)) {
        header('Location: /musician-booking-system/pages/home.php?error=access_denied');
        exit;
    }
}

/**
 * Check if user is authenticated for client pages
 */
function requireClientAuth() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /musician-booking-system/pages/login.php?error=login_required');
        exit;
    }
    
    if (!hasRole(USER_TYPE_CLIENT)) {
        header('Location: /musician-booking-system/pages/home.php?error=access_denied');
        exit;
    }
}

/**
 * Check if user is authenticated for admin pages
 */
function requireAdminAuth() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /musician-booking-system/pages/login.php?error=login_required');
        exit;
    }
    
    if (!hasRole(USER_TYPE_ADMIN)) {
        header('Location: /musician-booking-system/pages/home.php?error=access_denied');
        exit;
    }
}

/**
 * Redirect authenticated users away from auth pages
 */
function redirectIfAuthenticated() {
    if (isLoggedIn()) {
        redirectToDashboard();
    }
}

/**
 * Check CSRF token for POST requests
 */
function checkCSRF() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCSRFToken($token)) {
            setFlashMessage('error', 'Security token expired. Please try again.');
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }
}

/**
 * Initialize session timeout check
 */
function checkSessionTimeout() {
    if (isLoggedIn()) {
        $timeout = SESSION_LIFETIME;
        
        if (isset($_SESSION['last_activity'])) {
            $inactive_time = time() - $_SESSION['last_activity'];
            
            if ($inactive_time > $timeout) {
                session_unset();
                session_destroy();
                session_start();
                
                setFlashMessage('warning', 'Your session has expired. Please log in again.');
                header('Location: /musician-booking-system/pages/login.php');
                exit;
            }
        }
        
        $_SESSION['last_activity'] = time();
    }
}

// Auto-run session timeout check
checkSessionTimeout();

?> 
