<?php
/**
 * Helper Functions for Authentication and General Use
 * Musician Booking System
 */

// Security check
if (!defined('SYSTEM_ACCESS')) {
    die('Direct access not permitted');
}

/**
 * Generate CSRF Token
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    // Check if token is expired (1 hour)
    if (time() - $_SESSION['csrf_token_time'] > 3600) {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input data
 * @param mixed $data
 * @return mixed
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    if (is_string($data)) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    return $data;
}

/**
 * Validate email address
 * @param string $email
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 * @param string $password
 * @return array
 */
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < VALIDATION_RULES['password']['min_length']) {
        $errors[] = 'Password must be at least ' . VALIDATION_RULES['password']['min_length'] . ' characters long';
    }
    
    if (strlen($password) > VALIDATION_RULES['password']['max_length']) {
        $errors[] = 'Password must not exceed ' . VALIDATION_RULES['password']['max_length'] . ' characters';
    }
    
    if (VALIDATION_RULES['password']['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    
    if (VALIDATION_RULES['password']['require_lowercase'] && !preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    
    if (VALIDATION_RULES['password']['require_numbers'] && !preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    
    if (VALIDATION_RULES['password']['require_special_chars'] && !preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'Password must contain at least one special character';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validate username
 * @param string $username
 * @return array
 */
function validateUsername($username) {
    $errors = [];
    $rules = VALIDATION_RULES['username'];
    
    if (strlen($username) < $rules['min_length']) {
        $errors[] = "Username must be at least {$rules['min_length']} characters long";
    }
    
    if (strlen($username) > $rules['max_length']) {
        $errors[] = "Username must not exceed {$rules['max_length']} characters";
    }
    
    if (!preg_match($rules['pattern'], $username)) {
        $errors[] = 'Username can only contain letters, numbers, underscores, and hyphens';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validate phone number
 * @param string $phone
 * @return array
 */
function validatePhone($phone) {
    $errors = [];
    
    if (empty($phone)) {
        return ['valid' => true, 'errors' => []]; // Phone is optional
    }
    
    $rules = VALIDATION_RULES['phone'];
    
    if (!preg_match($rules['pattern'], $phone)) {
        $errors[] = 'Invalid phone number format';
    }
    
    if (strlen($phone) < $rules['min_length']) {
        $errors[] = "Phone number must be at least {$rules['min_length']} digits";
    }
    
    if (strlen($phone) > $rules['max_length']) {
        $errors[] = "Phone number must not exceed {$rules['max_length']} characters";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

/**
 * Check if user has specific role
 * @param string $role
 * @return bool
 */
function hasRole($role) {
    return isLoggedIn() && $_SESSION['user_type'] === $role;
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId() {
    return isLoggedIn() ? $_SESSION['user_id'] : null;
}

/**
 * Get current user type
 * @return string|null
 */
function getCurrentUserType() {
    return isLoggedIn() ? $_SESSION['user_type'] : null;
}

/**
 * Get current user info
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'user_type' => $_SESSION['user_type'],
        'first_name' => $_SESSION['first_name'] ?? '',
        'last_name' => $_SESSION['last_name'] ?? ''
    ];
}

/**
 * Redirect to login page if not authenticated
 * @param string $redirect_url URL to redirect after login
 */
function requireLogin($redirect_url = '') {
    if (!isLoggedIn()) {
        if (!empty($redirect_url)) {
            $_SESSION['redirect_after_login'] = $redirect_url;
        }
        header('Location: /musician-booking-system/pages/login.php');
        exit;
    }
}

/**
 * Redirect to appropriate dashboard based on user type
 */
function redirectToDashboard() {
    if (!isLoggedIn()) {
        header('Location: /musician-booking-system/pages/login.php');
        exit;
    }
    
    switch ($_SESSION['user_type']) {
        case USER_TYPE_MUSICIAN:
            header('Location: /musician-booking-system/musician/dashboard.php');
            break;
        case USER_TYPE_CLIENT:
            header('Location: /musician-booking-system/client/dashboard.php');
            break;
        case USER_TYPE_ADMIN:
            header('Location: /musician-booking-system/admin/index.php');
            break;
        default:
            header('Location: /musician-booking-system/pages/home.php');
            break;
    }
    exit;
}

/**
 * Set flash message
 * @param string $type success, error, warning, info
 * @param string $message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get flash messages and clear them
 * @return array
 */
function getFlashMessages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Display flash messages HTML
 * @return string
 */
function displayFlashMessages() {
    $messages = getFlashMessages();
    $html = '';
    
    foreach ($messages as $message) {
        $alertClass = '';
        switch ($message['type']) {
            case 'success':
                $alertClass = 'alert-success';
                break;
            case 'error':
                $alertClass = 'alert-danger';
                break;
            case 'warning':
                $alertClass = 'alert-warning';
                break;
            case 'info':
                $alertClass = 'alert-info';
                break;
        }
        
        $html .= '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
        $html .= htmlspecialchars($message['message']);
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        $html .= '</div>';
    }
    
    return $html;
}

/**
 * Format currency amount
 * @param float $amount
 * @return string
 */
function formatCurrency($amount) {
    $formatted = number_format($amount, DECIMAL_PLACES);
    
    if (CURRENCY_POSITION === 'before') {
        return CURRENCY_SYMBOL . ' ' . $formatted;
    } else {
        return $formatted . ' ' . CURRENCY_SYMBOL;
    }
}

/**
 * Format date for display
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = DISPLAY_DATE_FORMAT) {
    if (empty($date) || $date === '0000-00-00') {
        return '';
    }
    
    return date($format, strtotime($date));
}

/**
 * Format time for display
 * @param string $time
 * @param string $format
 * @return string
 */
function formatTime($time, $format = DISPLAY_TIME_FORMAT) {
    if (empty($time)) {
        return '';
    }
    
    return date($format, strtotime($time));
}

/**
 * Generate random string
 * @param int $length
 * @return string
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Log user activity
 * @param string $activity_type
 * @param string $description
 * @param int|null $user_id
 */
function logActivity($activity_type, $description = '', $user_id = null) {
    if ($user_id === null) {
        $user_id = getCurrentUserId();
    }
    
    try {
        $db = new Database('activity_logs');
        $db->create([
            'user_id' => $user_id,
            'activity_type' => $activity_type,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Get user's IP address
 * @return string
 */
function getUserIP() {
    $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            return trim($ips[0]);
        }
    }
    
    return 'unknown';
}

/**
 * Check rate limiting for login attempts
 * @param string $identifier Email or IP
 * @return bool True if rate limit exceeded
 */
function checkRateLimit($identifier) {
    $cache_key = 'login_attempts_' . md5($identifier);
    $attempts = $_SESSION[$cache_key] ?? [];
    
    // Clean old attempts (older than lockout duration)
    $current_time = time();
    $attempts = array_filter($attempts, function($timestamp) use ($current_time) {
        return ($current_time - $timestamp) < LOGIN_LOCKOUT_DURATION;
    });
    
    $_SESSION[$cache_key] = $attempts;
    
    return count($attempts) >= LOGIN_ATTEMPTS_LIMIT;
}

/**
 * Record failed login attempt
 * @param string $identifier Email or IP
 */
function recordFailedLogin($identifier) {
    $cache_key = 'login_attempts_' . md5($identifier);
    
    if (!isset($_SESSION[$cache_key])) {
        $_SESSION[$cache_key] = [];
    }
    
    $_SESSION[$cache_key][] = time();
}

/**
 * Clear login attempts
 * @param string $identifier Email or IP
 */
function clearLoginAttempts($identifier) {
    $cache_key = 'login_attempts_' . md5($identifier);
    unset($_SESSION[$cache_key]);
}

?>