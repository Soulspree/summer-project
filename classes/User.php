<?php
/**
 * User Management Class
 * Musician Booking System
 */

// Security check
if (!defined('SYSTEM_ACCESS')) {
    die('Direct access not permitted');
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../includes/functions.php';

/**
 * User Class for Authentication and User Management
 */
class User extends Database {
    
    public function __construct() {
        parent::__construct('users', 'user_id');
    }

    /**
     * Register a new user
     * @param array $userData
     * @return array
     */
    public function register($userData) {
        try {
            // Sanitize input data
            $userData = $this->sanitizeData($userData);
            
            // Validate required fields
            $required = ['username', 'email', 'password', 'user_type', 'first_name', 'last_name'];
            $missing = $this->validateRequired($userData, $required);
            
            if (!empty($missing)) {
                return [
                    'success' => false,
                    'message' => 'Missing required fields: ' . implode(', ', $missing)
                ];
            }
            
            // Validate individual fields
            $validation = $this->validateRegistrationData($userData);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => implode(', ', $validation['errors'])
                ];
            }
            
            // Check if email already exists
            if ($this->emailExists($userData['email'])) {
                return [
                    'success' => false,
                    'message' => ERROR_MESSAGES['email_exists']
                ];
            }
            
            // Check if username already exists
            if ($this->usernameExists($userData['username'])) {
                return [
                    'success' => false,
                    'message' => ERROR_MESSAGES['username_exists']
                ];
            }
            
            // Hash password
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // Prepare user data for insertion
            $insertData = [
                'username' => $userData['username'],
                'email' => strtolower($userData['email']),
                'password_hash' => $hashedPassword,
                'user_type' => $userData['user_type'],
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'phone' => $userData['phone'] ?? null,
                'account_status' => ACCOUNT_STATUS_ACTIVE,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->beginTransaction();
            
            // Insert user
            $user_id = $this->create($insertData);
            
            if ($user_id) {
                // Create user profile
                $this->createUserProfile($user_id, $userData);
                
                // Create specific profile based on user type
                if ($userData['user_type'] === USER_TYPE_MUSICIAN) {
                    $this->createMusicianProfile($user_id);
                }
                
                $this->commit();
                
                // Log activity
                logActivity(ACTIVITY_REGISTRATION, 'New user registered', $user_id);
                
                return [
                    'success' => true,
                    'message' => SUCCESS_MESSAGES['registration_success'],
                    'user_id' => $user_id
                ];
            }
            
            $this->rollback();
            return [
                'success' => false,
                'message' => 'Registration failed. Please try again.'
            ];
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Registration error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Registration failed. Please try again.'
            ];
        }
    }

    /**
     * Login user
     * @param string $email
     * @param string $password
     * @return array
     */
    public function login($email, $password) {
        try {
            $email = strtolower(trim($email));
            
            // Check rate limiting
            if ($this->checkRateLimit($email)) {
                return [
                    'success' => false,
                    'message' => 'Too many failed attempts. Please try again later.'
                ];
            }
            
            // Find user by email
            $user = $this->getUserByEmail($email);
            
            if (!$user) {
                $this->recordFailedLogin($email);
                return [
                    'success' => false,
                    'message' => ERROR_MESSAGES['invalid_credentials']
                ];
            }
            
            // Check account status
            if ($user['account_status'] !== ACCOUNT_STATUS_ACTIVE) {
                return [
                    'success' => false,
                    'message' => ERROR_MESSAGES['account_suspended']
                ];
            }
            
            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                $this->recordFailedLogin($email);
                return [
                    'success' => false,
                    'message' => ERROR_MESSAGES['invalid_credentials']
                ];
            }
            
            // Clear failed login attempts
            $this->clearLoginAttempts($email);
            
            // Update last login
            $this->update($user['user_id'], ['last_login' => date('Y-m-d H:i:s')]);
            
            // Set session data
            $this->setUserSession($user);
            
            // Log activity
            logActivity(ACTIVITY_LOGIN, 'User logged in', $user['user_id']);
            
            return [
                'success' => true,
                'message' => SUCCESS_MESSAGES['login_success'],
                'user' => $this->sanitizeUserData($user)
            ];
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Login failed. Please try again.'
            ];
        }
    }

    /**
     * Logout user
     * @return bool
     */
    public function logout() {
        if (isLoggedIn()) {
            logActivity(ACTIVITY_LOGOUT, 'User logged out', $_SESSION['user_id'] ?? null);
        }
        
        // Clear all session data
        session_unset();
        session_destroy();
        
        // Start new session
        session_start();
        
        return true;
    }

    /**
     * Get user by email
     * @param string $email
     * @return array|false
     */
    public function getUserByEmail($email) {
        $users = $this->read(['email' => strtolower($email)]);
        return !empty($users) ? $users[0] : false;
    }

    /**
     * Get user by username
     * @param string $username
     * @return array|false
     */
    public function getUserByUsername($username) {
        $users = $this->read(['username' => $username]);
        return !empty($users) ? $users[0] : false;
    }

    /**
     * Check if email exists
     * @param string $email
     * @return bool
     */
    public function emailExists($email) {
        return $this->count(['email' => strtolower($email)]) > 0;
    }

    /**
     * Check if username exists
     * @param string $username
     * @return bool
     */
    public function usernameExists($username) {
        return $this->count(['username' => $username]) > 0;
    }

    /**
     * Get user profile with extended information
     * @param int $user_id
     * @return array|false
     */
    public function getUserProfile($user_id) {
        try {
            $sql = "
                SELECT u.*, up.*, 
                       CASE WHEN mp.user_id IS NOT NULL THEN mp.* ELSE NULL END as musician_data
                FROM users u
                LEFT JOIN user_profiles up ON u.user_id = up.user_id
                LEFT JOIN musician_profiles mp ON u.user_id = mp.user_id AND u.user_type = 'musician'
                WHERE u.user_id = ?
            ";
            
            $stmt = $this->query($sql, [$user_id]);
            return $stmt ? $stmt->fetch() : false;
            
        } catch (Exception $e) {
            error_log("Get user profile error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user profile
     * @param int $user_id
     * @param array $data
     * @return bool
     */
    public function updateProfile($user_id, $data) {
        try {
            $this->beginTransaction();
            
            // Update basic user info if provided
            $userFields = ['first_name', 'last_name', 'phone'];
            $userData = array_intersect_key($data, array_flip($userFields));
            
            if (!empty($userData)) {
                $this->update($user_id, $userData);
            }
            
            // Update user profile
            $profileFields = ['bio', 'location', 'website', 'address', 'city', 'state', 'country', 'zip_code', 'date_of_birth'];
            $profileData = array_intersect_key($data, array_flip($profileFields));
            
            if (!empty($profileData)) {
                $profileDb = new Database('user_profiles', 'profile_id');
                $existingProfile = $profileDb->read(['user_id' => $user_id]);
                
                if (!empty($existingProfile)) {
                    $profileDb->update($existingProfile[0]['profile_id'], $profileData);
                } else {
                    $profileData['user_id'] = $user_id;
                    $profileDb->create($profileData);
                }
            }
            
            $this->commit();
            
            logActivity(ACTIVITY_PROFILE_UPDATED, 'Profile updated', $user_id);
            
            return true;
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Update profile error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Change user password
     * @param int $user_id
     * @param string $current_password
     * @param string $new_password
     * @return array
     */
    public function changePassword($user_id, $current_password, $new_password) {
        try {
            $user = $this->find($user_id);
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }
            
            // Verify current password
            if (!password_verify($current_password, $user['password_hash'])) {
                return [
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ];
            }
            
            // Validate new password
            $validation = $this->validatePassword($new_password);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => implode(', ', $validation['errors'])
                ];
            }
            
            // Update password
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            $result = $this->update($user_id, ['password_hash' => $hashedPassword]);
            
            if ($result) {
                logActivity(ACTIVITY_PASSWORD_CHANGED, 'Password changed', $user_id);
                return [
                    'success' => true,
                    'message' => 'Password updated successfully'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to update password'
            ];
            
        } catch (Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update password'
            ];
        }
    }

    /**
     * Get users with pagination and filters
     * @param array $filters
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getUsers($filters = [], $page = 1, $limit = 20) {
        $conditions = [];
        
        if (!empty($filters['user_type'])) {
            $conditions['user_type'] = $filters['user_type'];
        }
        
        if (!empty($filters['account_status'])) {
            $conditions['account_status'] = $filters['account_status'];
        }
        
        $orderBy = 'created_at DESC';
        
        return $this->paginate($page, $limit, $conditions, $orderBy);
    }

    /**
     * Search users
     * @param string $searchTerm
     * @param string $userType
     * @param int $limit
     * @return array
     */
    public function searchUsers($searchTerm, $userType = '', $limit = 20) {
        try {
            $sql = "
                SELECT u.*, up.bio, up.location, up.city
                FROM users u
                LEFT JOIN user_profiles up ON u.user_id = up.user_id
                WHERE (u.first_name LIKE ? OR u.last_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)
                AND u.account_status = 'active'
            ";
            
            $params = [];
            $searchPattern = '%' . $this->escapeLike($searchTerm) . '%';
            $params = [$searchPattern, $searchPattern, $searchPattern, $searchPattern];
            
            if (!empty($userType)) {
                $sql .= " AND u.user_type = ?";
                $params[] = $userType;
            }
            
            $sql .= " ORDER BY u.first_name, u.last_name LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->query($sql, $params);
            return $stmt ? $stmt->fetchAll() : [];
            
        } catch (Exception $e) {
            error_log("Search users error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user statistics
     * @return array
     */
    public function getUserStatistics() {
        try {
            $stats = [
                'total_users' => $this->count(),
                'musicians' => $this->count(['user_type' => USER_TYPE_MUSICIAN]),
                'clients' => $this->count(['user_type' => USER_TYPE_CLIENT]),
                'active_users' => $this->count(['account_status' => ACCOUNT_STATUS_ACTIVE]),
                'inactive_users' => $this->count(['account_status' => ACCOUNT_STATUS_INACTIVE]),
                'suspended_users' => $this->count(['account_status' => ACCOUNT_STATUS_SUSPENDED])
            ];
            
            // Recent registrations (last 30 days)
            $sql = "SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $stmt = $this->query($sql);
            $result = $stmt->fetch();
            $stats['recent_registrations'] = $result['count'];
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Get user statistics error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validate registration data
     * @param array $data
     * @return array
     */
    private function validateRegistrationData($data) {
        $errors = [];
        
        // Validate email
        if (!$this->validateEmail($data['email'])) {
            $errors[] = ERROR_MESSAGES['invalid_email'];
        }
        
        // Validate username
        $usernameValidation = $this->validateUsername($data['username']);
        if (!$usernameValidation['valid']) {
            $errors = array_merge($errors, $usernameValidation['errors']);
        }
        
        // Validate password
        $passwordValidation = $this->validatePassword($data['password']);
        if (!$passwordValidation['valid']) {
            $errors = array_merge($errors, $passwordValidation['errors']);
        }
        
        // Validate phone if provided
        if (!empty($data['phone'])) {
            $phoneValidation = $this->validatePhone($data['phone']);
            if (!$phoneValidation['valid']) {
                $errors = array_merge($errors, $phoneValidation['errors']);
            }
        }
        
        // Validate user type
        $validUserTypes = [USER_TYPE_MUSICIAN, USER_TYPE_CLIENT];
        if (!in_array($data['user_type'], $validUserTypes)) {
            $errors[] = 'Invalid user type';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate email format
     * @param string $email
     * @return bool
     */
    private function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate username
     * @param string $username
     * @return array
     */
    private function validateUsername($username) {
        $errors = [];
        
        if (strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters long';
        }
        
        if (strlen($username) > 30) {
            $errors[] = 'Username must be no more than 30 characters long';
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'Username can only contain letters, numbers, and underscores';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate password
     * @param string $password
     * @return array
     */
    private function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
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
    private function validatePhone($phone) {
        $errors = [];
        
        // Remove all non-numeric characters for validation
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($cleanPhone) < 10) {
            $errors[] = 'Phone number must be at least 10 digits long';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Check rate limiting for login attempts
     * @param string $email
     * @return bool
     */
    private function checkRateLimit($email) {
        try {
            $sql = "SELECT COUNT(*) as attempts FROM login_attempts 
                   WHERE email = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
            $stmt = $this->query($sql, [$email]);
            $result = $stmt->fetch();
            
            return $result['attempts'] >= 5; // Max 5 attempts in 15 minutes
            
        } catch (Exception $e) {
            error_log("Check rate limit error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Record failed login attempt
     * @param string $email
     */
    private function recordFailedLogin($email) {
        try {
            $sql = "INSERT INTO login_attempts (email, ip_address, attempt_time) VALUES (?, ?, NOW())";
            $this->query($sql, [$email, $_SERVER['REMOTE_ADDR'] ?? '']);
            
        } catch (Exception $e) {
            error_log("Record failed login error: " . $e->getMessage());
        }
    }

    /**
     * Clear login attempts for user
     * @param string $email
     */
    private function clearLoginAttempts($email) {
        try {
            $sql = "DELETE FROM login_attempts WHERE email = ?";
            $this->query($sql, [$email]);
            
        } catch (Exception $e) {
            error_log("Clear login attempts error: " . $e->getMessage());
        }
    }

    /**
     * Set user session data
     * @param array $user
     */
    private function setUserSession($user) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['account_status'] = $user['account_status'];
        $_SESSION['login_time'] = time();
        $_SESSION['is_logged_in'] = true;
    }

    /**
     * Create user profile
     * @param int $user_id
     * @param array $data
     */
    private function createUserProfile($user_id, $data) {
        $profileDb = new Database('user_profiles', 'profile_id');
        
        $profileData = [
            'user_id' => $user_id,
            'bio' => $data['bio'] ?? null,
            'location' => $data['location'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => 'Nepal', // Default for local platform
            'profile_completion_percentage' => 20, // Basic registration is 20%
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $profileDb->create($profileData);
    }

    /**
     * Create musician profile
     * @param int $user_id
     */
    private function createMusicianProfile($user_id) {
        $musicianDb = new Database('musician_profiles', 'musician_profile_id');
        
        $musicianData = [
            'user_id' => $user_id,
            'genres' => json_encode([]), // Empty array initially
            'instruments' => json_encode([]), // Empty array initially
            'experience_level' => DEFAULT_VALUES['experience_level'],
            'base_price_per_hour' => 0.00,
            'base_price_per_event' => 0.00,
            'pricing_negotiable' => DEFAULT_VALUES['pricing_negotiable'],
            'travel_radius' => DEFAULT_VALUES['travel_radius'],
            'equipment_provided' => DEFAULT_VALUES['equipment_provided'],
            'availability_status' => DEFAULT_VALUES['availability_status'],
            'rating' => DEFAULT_VALUES['rating'],
            'total_ratings' => 0,
            'total_bookings' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $musicianDb->create($musicianData);
    }

    /**
     * Sanitize user data for output
     * @param array $user
     * @return array
     */
    private function sanitizeUserData($user) {
        unset($user['password_hash']);
        return $user;
    }

    /**
     * Escape LIKE wildcards in search terms
     * @param string $string
     * @return string
     */
    public function escapeLike($string) {
        return str_replace(['%', '_'], ['\%', '\_'], $string);
    }

    /**
     * Validate required fields
     * @param array $data
     * @param array $required
     * @return array
     */
    protected function validateRequired($data, $required) {
        $missing = [];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $missing[] = $field;
            }
        }
        return $missing;
    }
}

?>