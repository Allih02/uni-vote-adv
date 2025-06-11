<?php
// login.php - Admin Login/Register Page
session_start();
require_once 'config/database.php';

// Check if already logged in
if(isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Initialize variables for messages
$error = '';
$success = '';

// Check for timeout message
if(isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $error = 'Your session has expired. Please login again.';
}

// Handle form submissions
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if($action === 'register') {
        // Handle registration
        $fullname = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate inputs
        if(empty($fullname) || empty($email) || empty($password) || empty($confirm_password)) {
            $error = 'All fields are required';
        } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format';
        } elseif($password !== $confirm_password) {
            $error = 'Passwords do not match';
        } elseif(strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long';
        } else {
            try {
                $conn = getDBConnection();
                
                // Check if email already exists
                $stmt = $conn->prepare("SELECT admin_id FROM admins WHERE email = ?");
                $stmt->execute([$email]);
                
                if($stmt->rowCount() > 0) {
                    $error = 'Email already exists';
                } else {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert new admin
                    $stmt = $conn->prepare("INSERT INTO admins (full_name, email, password) VALUES (?, ?, ?)");
                    $stmt->execute([$fullname, $email, $hashed_password]);
                    
                    $success = 'Registration successful! Please login with your credentials.';
                }
            } catch(PDOException $e) {
                $error = 'Registration failed. Please try again.';
                error_log($e->getMessage());
            }
        }
    } elseif($action === 'login') {
        // Handle login
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        if(empty($email) || empty($password)) {
            $error = 'Email and password are required';
        } else {
            try {
                $conn = getDBConnection();
                
                // Get admin details
                $stmt = $conn->prepare("SELECT admin_id, full_name, password, status FROM admins WHERE email = ?");
                $stmt->execute([$email]);
                $admin = $stmt->fetch();
                
                if($admin && password_verify($password, $admin['password'])) {
                    if($admin['status'] !== 'active') {
                        $error = 'Your account is not active. Please contact administrator.';
                    } else {
                        // Login successful
                        $_SESSION['admin_id'] = $admin['admin_id'];
                        $_SESSION['admin_name'] = $admin['full_name'];
                        $_SESSION['admin_email'] = $email;
                        $_SESSION['login_time'] = time(); // Track login time
                        $_SESSION['last_activity'] = time(); // Track last activity
                        $_SESSION['session_lifetime'] = 900; // 15 minutes in seconds
                        
                        // Update last login
                        $stmt = $conn->prepare("UPDATE admins SET last_login = CURRENT_TIMESTAMP WHERE admin_id = ?");
                        $stmt->execute([$admin['admin_id']]);
                        
                        // Handle remember me
                        if($remember) {
                            $token = bin2hex(random_bytes(32));
                            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                            
                            $stmt = $conn->prepare("INSERT INTO admin_sessions (admin_id, session_token, expires_at) VALUES (?, ?, ?)");
                            $stmt->execute([$admin['admin_id'], $token, $expires]);
                            
                            setcookie('admin_remember', $token, strtotime('+30 days'), '/', '', true, true);
                        }
                        
                        // Redirect to dashboard
                        header("Location: dashboard.php");
                        exit();
                    }
                } else {
                    $error = 'Invalid email or password';
                    
                    // Log failed attempt
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $stmt = $conn->prepare("INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, FALSE)");
                    $stmt->execute([$email, $ip]);
                }
            } catch(PDOException $e) {
                $error = 'Login failed. Please try again.';
                error_log($e->getMessage());
            }
        }
    }
}

// Check for remember me cookie
if(isset($_COOKIE['admin_remember']) && !isset($_SESSION['admin_id'])) {
    try {
        $conn = getDBConnection();
        $token = $_COOKIE['admin_remember'];
        
        $stmt = $conn->prepare("
            SELECT a.admin_id, a.full_name, a.email, a.status 
            FROM admins a 
            JOIN admin_sessions s ON a.admin_id = s.admin_id 
            WHERE s.session_token = ? AND s.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $admin = $stmt->fetch();
        
        if($admin && $admin['status'] === 'active') {
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_email'] = $admin['email'];
            
            header("Location: dashboard.php");
            exit();
        }
    } catch(PDOException $e) {
        error_log($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - University Voting System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #8b5cf6;
            --accent: #f43f5e;
            --background: #f8fafc;
            --surface: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border: #e2e8f0;
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--background);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .login-container {
            width: 100%;
            max-width: 1200px;
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            background: var(--surface);
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .login-form-section {
            padding: 3rem;
        }

        .login-image-section {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .login-image-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.133 7-7s-3.134-7-7-7-7 3.133-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.133 7-7s-3.134-7-7-7-7 3.133-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23FFFFFF' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
            animation: backgroundMove 20s linear infinite;
        }

        @keyframes backgroundMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(-50px, -50px); }
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo i {
            font-size: 1.8rem;
            color: var(--primary);
        }

        .form-container {
            max-width: 400px;
            width: 100%;
            margin: 0 auto;
        }

        .form-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .form-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .form-header p {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .tab {
            flex: 1;
            padding: 0.75rem;
            border: 2px solid var(--border);
            background: transparent;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            color: var(--text-secondary);
        }

        .tab.active {
            border-color: var(--primary);
            background: var(--primary);
            color: white;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border);
            border-radius: 0.75rem;
            font-size: 1rem;
            transition: all 0.2s;
            background: var(--surface);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-input::placeholder {
            color: var(--text-secondary);
        }

        .password-input-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .password-toggle:hover {
            color: var(--text-primary);
        }

        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .form-checkbox input[type="checkbox"] {
            width: 1.25rem;
            height: 1.25rem;
            border: 2px solid var(--border);
            border-radius: 0.375rem;
            cursor: pointer;
        }

        .form-checkbox label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            cursor: pointer;
        }

        .submit-btn {
            width: 100%;
            padding: 0.875rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .submit-btn:hover {
            background: var(--primary-dark);
        }

        .submit-btn:active {
            transform: translateY(1px);
        }

        .form-footer {
            margin-top: 1.5rem;
            text-align: center;
        }

        .form-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        .feature-list {
            list-style: none;
            margin-top: 2rem;
        }

        .feature-list li {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }

        .feature-list i {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .back-home {
            position: absolute;
            top: 2rem;
            left: 2rem;
            color: var(--text-secondary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: color 0.2s;
        }

        .back-home:hover {
            color: var(--primary);
        }

        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: none;
        }

        .alert.error {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .alert.success {
            background: #dcfce7;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 2rem 0;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid var(--border);
        }

        .divider span {
            padding: 0 1rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .social-login {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .social-btn {
            padding: 0.75rem;
            border: 2px solid var(--border);
            background: var(--surface);
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .social-btn:hover {
            border-color: var(--primary);
            background: var(--background);
        }

        @media (max-width: 968px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 500px;
            }

            .login-image-section {
                display: none;
            }

            .login-form-section {
                padding: 2rem;
            }
        }

        @media (max-width: 480px) {
            .login-form-section {
                padding: 1.5rem;
            }

            .form-header h1 {
                font-size: 1.75rem;
            }

            .social-login {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <a href="/project-folder/admin/adminhome.html" class="back-home">
        <i class="fas fa-arrow-left"></i>
        Back to Home
    </a>

    <div class="login-container">
        <div class="login-form-section">
            <div class="form-container">
                <div class="logo">
                    <i class="fas fa-vote-yea"></i>
                    VoteAdmin
                </div>

                <div class="form-header">
                    <h1>Welcome Back</h1>
                    <p>Sign in to access the admin dashboard</p>
                </div>

                <div class="tabs">
                    <button class="tab active" data-target="login">Login</button>
                    <button class="tab" data-target="register">Register</button>
                </div>

                <div class="alert error" id="errorAlert"></div>
                <div class="alert success" id="successAlert"></div>

                <!-- Login Form -->
                <form id="loginForm" class="auth-form" method="POST" action="">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="form-group">
                        <label class="form-label" for="loginEmail">Email address</label>
                        <input 
                            type="email" 
                            class="form-input" 
                            id="loginEmail" 
                            name="email" 
                            placeholder="admin@university.edu"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="loginPassword">Password</label>
                        <div class="password-input-wrapper">
                            <input 
                                type="password" 
                                class="form-input" 
                                id="loginPassword" 
                                name="password" 
                                placeholder="Enter your password"
                                required
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword('loginPassword')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-checkbox">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me for 30 days</label>
                    </div>

                    <button type="submit" class="submit-btn">Sign In</button>

                    <div class="form-footer">
                        <a href="forgot-password.php">Forgot your password?</a>
                    </div>
                </form>

                <!-- Register Form -->
                <form id="registerForm" class="auth-form" style="display: none;" method="POST" action="">
                    <input type="hidden" name="action" value="register">
                    
                    <div class="form-group">
                        <label class="form-label" for="fullName">Full name</label>
                        <input 
                            type="text" 
                            class="form-input" 
                            id="fullName" 
                            name="fullname" 
                            placeholder="John Doe"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="registerEmail">Email address</label>
                        <input 
                            type="email" 
                            class="form-input" 
                            id="registerEmail" 
                            name="email" 
                            placeholder="admin@university.edu"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="registerPassword">Password</label>
                        <div class="password-input-wrapper">
                            <input 
                                type="password" 
                                class="form-input" 
                                id="registerPassword" 
                                name="password" 
                                placeholder="Create a password"
                                required
                                minlength="8"
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword('registerPassword')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirmPassword">Confirm password</label>
                        <div class="password-input-wrapper">
                            <input 
                                type="password" 
                                class="form-input" 
                                id="confirmPassword" 
                                name="confirm_password" 
                                placeholder="Confirm your password"
                                required
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">Create Account</button>

                    <div class="form-footer">
                        <p>Already have an account? <a href="#" onclick="switchTab('login')">Sign in</a></p>
                    </div>
                </form>

                <div class="divider">
                    <span>or continue with</span>
                </div>

                <div class="social-login">
                    <button type="button" class="social-btn">
                        <i class="fab fa-google"></i>
                        Google
                    </button>
                    <button type="button" class="social-btn">
                        <i class="fab fa-microsoft"></i>
                        Microsoft
                    </button>
                </div>
            </div>
        </div>

        <div class="login-image-section">
            <h2 style="font-size: 2.5rem; margin-bottom: 1.5rem; position: relative; z-index: 1;">
                Secure Admin Portal
            </h2>
            <p style="font-size: 1.25rem; margin-bottom: 3rem; opacity: 0.9; position: relative; z-index: 1;">
                Manage university elections with confidence using our advanced administrative tools.
            </p>
            <ul class="feature-list" style="position: relative; z-index: 1;">
                <li>
                    <i class="fas fa-shield-alt"></i>
                    <span>Advanced Security</span>
                </li>
                <li>
                    <i class="fas fa-chart-bar"></i>
                    <span>Real-time Analytics</span>
                </li>
                <li>
                    <i class="fas fa-users"></i>
                    <span>Voter Management</span>
                </li>
                <li>
                    <i class="fas fa-cog"></i>
                    <span>System Configuration</span>
                </li>
            </ul>
        </div>
    </div>

    <script>
        // Tab switching
        const tabs = document.querySelectorAll('.tab');
        const forms = document.querySelectorAll('.auth-form');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const target = tab.dataset.target;
                
                // Update active tab
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                
                // Show corresponding form
                forms.forEach(form => {
                    if (form.id === target + 'Form') {
                        form.style.display = 'block';
                    } else {
                        form.style.display = 'none';
                    }
                });
                
                // Update header text
                const header = document.querySelector('.form-header h1');
                const subheader = document.querySelector('.form-header p');
                
                if (target === 'login') {
                    header.textContent = 'Welcome Back';
                    subheader.textContent = 'Sign in to access the admin dashboard';
                } else {
                    header.textContent = 'Create Account';
                    subheader.textContent = 'Register for admin access';
                }
            });
        });

        // Password toggle
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Switch tab function
        function switchTab(tabName) {
            document.querySelector(`[data-target="${tabName}"]`).click();
        }

        // Form validation
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        const errorAlert = document.getElementById('errorAlert');
        const successAlert = document.getElementById('successAlert');

        // Show PHP errors/success messages
        <?php if(!empty($error)): ?>
            showAlert('error', <?php echo json_encode($error); ?>);
        <?php elseif(!empty($success)): ?>
            showAlert('success', <?php echo json_encode($success); ?>);
        <?php endif; ?>
        
        function showAlert(type, message) {
            const alert = type === 'error' ? errorAlert : successAlert;
            alert.textContent = message;
            alert.style.display = 'block';
            
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }

        // Remove preventDefault to allow form submission
        loginForm.addEventListener('submit', (e) => {
            // Form will be submitted to PHP
        });

        registerForm.addEventListener('submit', (e) => {
            const password = document.getElementById('registerPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                showAlert('error', 'Passwords do not match');
                return;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                showAlert('error', 'Password must be at least 8 characters long');
                return;
            }
            
            // Form will be submitted to PHP
        });
    </script>
</body>
</html>