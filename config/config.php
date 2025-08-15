 <?php
/**
 * Main Configuration File
 * Musician Booking System
 */

// Prevent direct access
if (!defined('SYSTEM_ACCESS')) {
    define('SYSTEM_ACCESS', true);
}

// Environment Configuration
define('ENVIRONMENT', 'development'); // development, staging, production

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'musician_booking_system');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP password is empty
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'Musician Booking System');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/musician-booking-system');
define('APP_EMAIL', 'admin@musicbooking.local');

// Security Configuration
define('HASH_ALGORITHM', 'sha256');
define('SESSION_LIFETIME', 3600); // 1 hour in seconds
define('CSRF_TOKEN_LENGTH', 32);
define('PASSWORD_MIN_LENGTH', 8);

// File Upload Configuration
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10485760); // 10MB in bytes
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_AUDIO_TYPES', ['mp3', 'wav', 'm4a']);
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx']);

// Pagination Configuration
define('ITEMS_PER_PAGE', 20);
define('MAX_PAGINATION_LINKS', 10);

// Email Configuration (for future use)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_ENCRYPTION', 'tls');

// Timezone Configuration
date_default_timezone_set('Asia/Kathmandu');

// Error Reporting based on environment
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
}

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS in production

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auto-destroy expired sessions
if (isset($_SESSION['last_activity']) && 
    (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();

?>
