 
<?php
/**
 * Registration Page
 * Musician Booking System
 */

define('SYSTEM_ACCESS', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Redirect if already logged in
redirectIfAuthenticated();

$error_message = '';
$success_message = '';
$form_data = [];

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCSRF();
    
    // Sanitize form data
    $form_data = [
        'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
        'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
        'username' => sanitizeInput($_POST['username'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'user_type' => sanitizeInput($_POST['user_type'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'terms' => isset($_POST['terms'])
    ];
    
    // Basic validation
    $validation_errors = [];
    
    if (empty($form_data['first_name'])) {
        $validation_errors[] = 'First name is required';
    }
    
    if (empty($form_data['last_name'])) {
        $validation_errors[] = 'Last name is required';
    }
    
    if (empty($form_data['username'])) {
        $validation_errors[] = 'Username is required';
    }
    
    if (empty($form_data['email'])) {
        $validation_errors[] = 'Email is required';
    }
    
    if (empty($form_data['user_type']) || !in_array($form_data['user_type'], [USER_TYPE_MUSICIAN, USER_TYPE_CLIENT])) {
        $validation_errors[] = 'Please select a valid account type';
    }
    
    if (empty($form_data['password'])) {
        $validation_errors[] = 'Password is required';
    }
    
    if ($form_data['password'] !== $form_data['confirm_password']) {
        $validation_errors[] = 'Passwords do not match';
    }
    
    if (!$form_data['terms']) {
        $validation_errors[] = 'You must accept the terms and conditions';
    }
    
    if (empty($validation_errors)) {
        $user = new User();
        $result = $user->register($form_data);
        
        if ($result['success']) {
            $success_message = $result['message'];
            $form_data = []; // Clear form data on success
        } else {
            $error_message = $result['message'];
        }
    } else {
        $error_message = implode(', ', $validation_errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo APP_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .register-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .register-form {
            padding: 2rem;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 8px rgba(102, 126, 234, 0.5);
        }
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: transform 0.3s ease;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        .user-type-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }
        .user-type-card:hover {
            border-color: #667eea;
            background: #f8f9fa;
        }
        .user-type-card.active {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        }
        .user-type-card input[type="radio"] {
            display: none;
        }
        .password-strength {
            margin-top: 0.5rem;
        }
        .strength-bar {
            height: 4px;
            border-radius: 2px;
            background: #e9ecef;
            overflow: hidden;
        }
        .strength-fill {
            height: 100%;
            transition: all 0.3s ease;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7">
                <div class="register-container">
                    <div class="register-header">
                        <i class="fas fa-user-plus fa-3x mb-3"></i>
                        <h2 class="mb-0">Create Account</h2>
                        <p class="mb-0 opacity-75">Join our music community</p>
                    </div>
                    
                    <div class="register-form">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($success_message); ?>
                                <div class="mt-2">
                                    <a href="login.php" class="btn btn-success btn-sm">
                                        <i class="fas fa-sign-in-alt me-1"></i>
                                        Sign In Now
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <!-- Account Type Selection -->
                            <div class="mb-4">
                                <label class="form-label">I am a:</label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="user-type-card" onclick="selectUserType('musician')">
                                            <input type="radio" name="user_type" value="musician" id="musician" 
                                                   <?php echo ($form_data['user_type'] ?? '') === 'musician' ? 'checked' : ''; ?>>
                                            <i class="fas fa-guitar fa-3x text-primary mb-2"></i>
                                            <h5>Musician</h5>
                                            <p class="text-muted mb-0">Offer your musical services</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="user-type-card" onclick="selectUserType('client')">
                                            <input type="radio" name="user_type" value="client" id="client"
                                                   <?php echo ($form_data['user_type'] ?? '') === 'client' ? 'checked' : ''; ?>>
                                            <i class="fas fa-calendar-alt fa-3x text-success mb-2"></i>
                                            <h5>Event Organizer</h5>
                                            <p class="text-muted mb-0">Book musicians for events</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Personal Information -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="first_name" 
                                           name="first_name" 
                                           value="<?php echo htmlspecialchars($form_data['first_name'] ?? ''); ?>" 
                                           placeholder="Enter first name"
                                           required>
                                    <div class="invalid-feedback" id="first_name-error"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="last_name" 
                                           name="last_name" 
                                           value="<?php echo htmlspecialchars($form_data['last_name'] ?? ''); ?>" 
                                           placeholder="Enter last name"
                                           required>
                                    <div class="invalid-feedback" id="last_name-error"></div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="username" 
                                       name="username" 
                                       value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>" 
                                       placeholder="Choose a unique username"
                                       required>
                                <div class="form-text">Username must be 3-50 characters, letters, numbers, underscore and hyphens only</div>
                                <div class="invalid-feedback" id="username-error"></div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" 
                                       placeholder="Enter your email address"
                                       required>
                                <div class="invalid-feedback" id="email-error"></div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number <span class="text-muted">(Optional)</span></label>
                                <input type="tel" 
                                       class="form-control" 
                                       id="phone" 
                                       name="phone" 
                                       value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>" 
                                       placeholder="Enter your phone number">
                                <div class="invalid-feedback" id="phone-error"></div>
                            </div>
                            
                            <!-- Password Fields -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="position-relative">
                                        <input type="password" 
                                               class="form-control" 
                                               id="password" 
                                               name="password" 
                                               placeholder="Enter password"
                                               required>
                                        <span class="position-absolute top-50 end-0 translate-middle-y me-3" 
                                              style="cursor: pointer;" 
                                              onclick="togglePassword('password')">
                                            <i class="fas fa-eye" id="password-toggle"></i>
                                        </span>
                                    </div>
                                    <div class="password-strength">
                                        <div class="strength-bar">
                                            <div class="strength-fill" id="strength-fill"></div>
                                        </div>
                                        <small class="text-muted" id="strength-text">Password strength</small>
                                    </div>
                                    <div class="invalid-feedback" id="password-error"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <div class="position-relative">
                                        <input type="password" 
                                               class="form-control" 
                                               id="confirm_password" 
                                               name="confirm_password" 
                                               placeholder="Confirm password"
                                               required>
                                        <span class="position-absolute top-50 end-0 translate-middle-y me-3" 
                                              style="cursor: pointer;" 
                                              onclick="togglePassword('confirm_password')">
                                            <i class="fas fa-eye" id="confirm_password-toggle"></i>
                                        </span>
                                    </div>
                                    <div class="invalid-feedback" id="confirm_password-error"></div>
                                </div>
                            </div>
                            
                            <!-- Terms and Conditions -->
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                    <label class="form-check-label" for="terms">
                                        I agree to the <a href="#" class="text-decoration-none">Terms of Service</a> 
                                        and <a href="#" class="text-decoration-none">Privacy Policy</a>
                                    </label>
                                    <div class="invalid-feedback" id="terms-error"></div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-register w-100 mb-3">
                                <i class="fas fa-user-plus me-2"></i>
                                Create Account
                            </button>
                        </form>
                        
                        <div class="text-center">
                            <p class="mb-2">Already have an account?</p>
                            <a href="login.php" class="btn btn-outline-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Sign In
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="../index.php" class="text-white text-decoration-none">
                        <i class="fas fa-arrow-left me-2"></i>
                        Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // User type selection
        function selectUserType(type) {
            // Remove active class from all cards
            document.querySelectorAll('.user-type-card').forEach(card => {
                card.classList.remove('active');
            });
            
            // Add active class to selected card
            document.querySelector(`input[value="${type}"]`).closest('.user-type-card').classList.add('active');
            
            // Check the radio button
            document.querySelector(`input[value="${type}"]`).checked = true;
        }
        
        // Initialize active state on page load
        document.addEventListener('DOMContentLoaded', function() {
            const checkedRadio = document.querySelector('input[name="user_type"]:checked');
            if (checkedRadio) {
                checkedRadio.closest('.user-type-card').classList.add('active');
            }
        });
        
        // Toggle password visibility
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleIcon = document.getElementById(fieldId + '-toggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }
        
        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            let feedback = [];
            
            if (password.length >= 8) strength += 1;
            else feedback.push('At least 8 characters');
            
            if (/[a-z]/.test(password)) strength += 1;
            else feedback.push('Lowercase letter');
            
            if (/[A-Z]/.test(password)) strength += 1;
            else feedback.push('Uppercase letter');
            
            if (/[0-9]/.test(password)) strength += 1;
            else feedback.push('Number');
            
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            else feedback.push('Special character');
            
            return { strength, feedback };
        }
        
        // Update password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const { strength, feedback } = checkPasswordStrength(password);
            
            const strengthFill = document.getElementById('strength-fill');
            const strengthText = document.getElementById('strength-text');
            
            const colors = ['#dc3545', '#fd7e14', '#ffc107', '#198754', '#28a745'];
            const labels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
            
            if (password.length === 0) {
                strengthFill.style.width = '0%';
                strengthFill.style.backgroundColor = '#e9ecef';
                strengthText.textContent = 'Password strength';
                strengthText.className = 'text-muted';
            } else {
                const percentage = (strength / 5) * 100;
                strengthFill.style.width = percentage + '%';
                strengthFill.style.backgroundColor = colors[strength - 1] || colors[0];
                strengthText.textContent = labels[strength - 1] || labels[0];
                strengthText.className = strength >= 3 ? 'text-success' : strength >= 2 ? 'text-warning' : 'text-danger';
            }
        });
        
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const firstName = document.getElementById('first_name');
            const lastName = document.getElementById('last_name');
            const username = document.getElementById('username');
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            const terms = document.getElementById('terms');

            [firstName, lastName, username, email, password, confirmPassword].forEach(input => {
                input.addEventListener('input', () => validateField(input));
            });

            document.querySelectorAll('input[name="user_type"]').forEach(radio => {
                radio.addEventListener('change', () => {
                    document.querySelectorAll('.user-type-card').forEach(card => card.classList.remove('is-invalid'));
                });
            });

            terms.addEventListener('change', () => validateField(terms));

            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                
                
                // Validate user type
                [firstName, lastName, username, email, password, confirmPassword, terms].forEach(input => {
                    validateField(input);
                    if (input.classList.contains('is-invalid')) {
                        isValid = false;
                    }
                });

                const userType = document.querySelector('input[name="user_type"]:checked');
                if (!userType) {
                    showError(document.querySelector('.user-type-card'), 'Please select account type');
                    isValid = false;
                }
                
                
                // Validate terms
                const terms = document.getElementById('terms');
                if (!terms.checked) {
                    showError(terms, 'You must accept the terms and conditions');
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
            function validateField(input) {
                switch (input.id) {
                    case 'first_name':
                        if (!input.value.trim()) {
                            showError(input, 'First name is required');
                        } else {
                            clearError(input);
                        }
                        break;
                    case 'last_name':
                        if (!input.value.trim()) {
                            showError(input, 'Last name is required');
                        } else {
                            clearError(input);
                        }
                        break;
                    case 'username':
                        if (!input.value.trim()) {
                            showError(input, 'Username is required');
                        } else if (input.value.length < 3) {
                            showError(input, 'Username must be at least 3 characters');
                        } else if (!/^[a-zA-Z0-9_-]+$/.test(input.value)) {
                            showError(input, 'Username can only contain letters, numbers, underscore and hyphens');
                        } else {
                            clearError(input);
                        }
                        break;
                    case 'email':
                        if (!input.value.trim()) {
                            showError(input, 'Email is required');
                        } else if (!isValidEmail(input.value)) {
                            showError(input, 'Please enter a valid email address');
                        } else {
                            clearError(input);
                        }
                        break;
                    case 'password':
                        if (!input.value.trim()) {
                            showError(input, 'Password is required');
                        } else {
                            const { strength } = checkPasswordStrength(input.value);
                            if (strength < 2) {
                                showError(input, 'Password is too weak');
                            } else {
                                clearError(input);
                            }
                        }
                        break;
                    case 'confirm_password':
                        const passwordEl = document.getElementById('password');
                        if (!input.value.trim()) {
                            showError(input, 'Please confirm your password');
                        } else if (passwordEl.value !== input.value) {
                            showError(input, 'Passwords do not match');
                        } else {
                            clearError(input);
                        }
                        break;
                    case 'terms':
                        if (!input.checked) {
                            showError(input, 'You must accept the terms and conditions');
                        } else {
                            clearError(input);
                        }
                        break;
                }
            }

            function showError(input, message) {
                input.classList.add('is-invalid');
                const errorDiv = document.getElementById(input.name + '-error');
                if (errorDiv) {
                    errorDiv.textContent = message;
                }
            }
            
            function clearError(input) {
                input.classList.remove('is-invalid');
                const errorDiv = document.getElementById(input.name + '-error');
                if (errorDiv) {
                    errorDiv.textContent = '';
                }
            }
            function isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }
        });
    </script>
</body>
</html>