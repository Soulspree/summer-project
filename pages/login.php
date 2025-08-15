<?php
/**
 * Login Page
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
$email = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCSRF();
    
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error_message = 'Please fill in all fields';
    } else {
        $user = new User();
        $result = $user->login($email, $password);
        
        if ($result['success']) {
            setFlashMessage('success', $result['message']);
            
            // Redirect to intended page or dashboard
            if (isset($_SESSION['redirect_after_login'])) {
                $redirect_url = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                header('Location: ' . $redirect_url);
            } else {
                redirectToDashboard();
            }
            exit;
        } else {
            $error_message = $result['message'];
        }
    }
}

// Handle URL parameters for error messages
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'login_required':
            $error_message = 'Please log in to access this page';
            break;
        case 'session_expired':
            $error_message = 'Your session has expired. Please log in again';
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-form {
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
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: transform 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        .input-group-text {
            background: transparent;
            border-right: none;
            color: #6c757d;
        }
        .input-group .form-control {
            border-left: none;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .divider {
            text-align: center;
            margin: 1.5rem 0;
            position: relative;
        }
        .divider:before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #dee2e6;
        }
        .divider span {
            background: white;
            padding: 0 1rem;
            color: #6c757d;
        }
        .social-login {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        .btn-social {
            flex: 1;
            padding: 12px;
            border-radius: 10px;
            border: 2px solid #e9ecef;
            background: white;
            transition: all 0.3s ease;
        }
        .btn-social:hover {
            border-color: #667eea;
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-container">
                    <div class="login-header">
                        <i class="fas fa-music fa-3x mb-3"></i>
                        <h2 class="mb-0">Welcome Back</h2>
                        <p class="mb-0 opacity-75">Sign in to your account</p>
                    </div>
                    
                    <div class="login-form">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php echo displayFlashMessages(); ?>
                        
                        <form method="POST" action="" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           name="email" 
                                           value="<?php echo htmlspecialchars($email); ?>" 
                                           placeholder="Enter your email"
                                           required>
                                </div>
                                <div class="invalid-feedback" id="email-error"></div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Enter your password"
                                           required>
                                    <span class="input-group-text toggle-password" 
                                          style="cursor: pointer;" 
                                          onclick="togglePassword()">
                                        <i class="fas fa-eye" id="password-toggle"></i>
                                    </span>
                                </div>
                                <div class="invalid-feedback" id="password-error"></div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    Remember me
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-login w-100 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Sign In
                            </button>
                        </form>
                        
                        <div class="text-center">
                            <a href="#" class="text-decoration-none">Forgot your password?</a>
                        </div>
                        
                        <div class="divider">
                            <span>Don't have an account?</span>
                        </div>
                        
                        <div class="text-center">
                            <a href="register.php" class="btn btn-outline-primary btn-register">
                                <i class="fas fa-user-plus me-2"></i>
                                Create Account
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
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('password-toggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }
        
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            emailInput.addEventListener('input', function() {
                if (!emailInput.value.trim()) {
                    showError(emailInput, 'Email is required');
                } else if (!isValidEmail(emailInput.value)) {
                    showError(emailInput, 'Please enter a valid email address');
                } else {
                    clearError(emailInput);
                }
            });

            passwordInput.addEventListener('input', function() {
                if (!passwordInput.value.trim()) {
                    showError(passwordInput, 'Password is required');
                } else if (passwordInput.value.length < 8) {
                    showError(passwordInput, 'Password must be at least 8 characters long');
                } else {
                    clearError(passwordInput);
                }
            });
            
            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Clear previous errors
                document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                
                // Validate email
                if (!emailInput.value.trim()) {
                    showError(emailInput, 'Email is required');
                    isValid = false;
                } else if (!isValidEmail(emailInput.value)) {
                    showError(emailInput, 'Please enter a valid email address');
                    isValid = false;
                }
                
                // Validate password
                if (!passwordInput.value.trim()) {
                    showError(passwordInput, 'Password is required');
                    isValid = false;
                } else if (passwordInput.value.length < 8) {
                    showError(passwordInput, 'Password must be at least 8 characters long');
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
            
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