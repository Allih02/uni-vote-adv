<?php
// login.php - Admin Login/Register Page
session_start();
require_once 'config/database.php';

// Check if already logged in
if(isset($_SESSION['admin_id'])) {
    header("Location: admin_dashboard.php");
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
                        $_SESSION['login_time'] = time();
                        $_SESSION['last_activity'] = time();
                        $_SESSION['session_lifetime'] = 900;
                        
                        // Update last login
                        $stmt = $conn->prepare("UPDATE admins SET last_login = CURRENT_TIMESTAMP WHERE admin_id = ?");
                        $stmt->execute([$admin['admin_id']]);
                        
                        // Redirect to dashboard
                        header("Location: admin_dashboard.php");
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - University Elections Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-blue: #2563eb;
            --primary-blue-dark: #1d4ed8;
            --primary-blue-light: #3b82f6;
            --secondary-blue: #1e40af;
            --accent-blue: #60a5fa;
            --background: #f8fafc;
            --surface: #ffffff;
            --surface-elevated: #f1f5f9;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --border: #e2e8f0;
            --border-focus: #3b82f6;
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.6;
        }

        /* University Header */
        .university-header {
            background: var(--surface);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            position: relative;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .university-brand {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .university-logo-container {
            width: 300px;
            height: 100px;
            background: var(--surface-elevated);
            border: 2px dashed var(--border);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-size: 0.875rem;
            font-weight: 500;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .university-logo-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            opacity: 0.05;
            z-index: 0;
        }

        .university-logo-container span {
            position: relative;
            z-index: 1;
        }

        .university-info {
            flex: 1;
        }

        .university-info h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .university-info p {
            color: var(--text-secondary);
            font-size: 1rem;
            font-weight: 500;
        }

        .back-home-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.875rem;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.2s ease;
            background: var(--surface-elevated);
            border: 1px solid var(--border);
        }

        .back-home-btn:hover {
            color: var(--primary-blue);
            background: white;
            box-shadow: var(--shadow-sm);
            transform: translateY(-1px);
        }

        /* Main Container */
        .login-container {
            min-height: calc(100vh - 116px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-wrapper {
            width: 100%;
            max-width: 1200px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: var(--surface);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--border);
        }

        /* Form Section */
        .form-section {
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-container {
            max-width: 400px;
            width: 100%;
            margin: 0 auto;
        }

        .form-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .form-header h2 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .form-header p {
            color: var(--text-secondary);
            font-size: 1rem;
            font-weight: 500;
        }

        /* Tabs */
        .auth-tabs {
            display: flex;
            background: var(--surface-elevated);
            border-radius: 12px;
            padding: 0.25rem;
            margin-bottom: 2rem;
        }

        .auth-tab {
            flex: 1;
            padding: 0.75rem 1rem;
            border: none;
            background: transparent;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
            color: var(--text-secondary);
        }

        .auth-tab.active {
            background: var(--primary-blue);
            color: white;
            box-shadow: var(--shadow-sm);
        }

        .auth-tab:hover:not(.active) {
            background: white;
            color: var(--text-primary);
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: var(--surface);
            color: var(--text-primary);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-input::placeholder {
            color: var(--text-muted);
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 4px;
            transition: color 0.2s ease;
        }

        .password-toggle:hover {
            color: var(--text-primary);
        }

        .submit-button {
            width: 100%;
            padding: 0.875rem 1rem;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-dark) 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-sm);
        }

        .submit-button:hover {
            background: linear-gradient(135deg, var(--primary-blue-dark) 0%, var(--secondary-blue) 100%);
            box-shadow: var(--shadow-md);
            transform: translateY(-1px);
        }

        .submit-button:active {
            transform: translateY(0);
        }

        .form-footer {
            text-align: center;
            margin-top: 1.5rem;
        }

        .form-footer a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        /* Info Section */
        .info-section {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .info-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Ccircle cx='30' cy='30' r='4'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            animation: backgroundMove 20s linear infinite;
        }

        @keyframes backgroundMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(-60px, -60px); }
        }

        .info-content {
            position: relative;
            z-index: 1;
        }

        .info-header {
            margin-bottom: 2rem;
        }

        .info-header h3 {
            font-size: 2.25rem;
            font-weight: 800;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .info-header p {
            font-size: 1.125rem;
            opacity: 0.9;
            line-height: 1.6;
        }

        .features-list {
            list-style: none;
            margin-top: 2rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            font-size: 1rem;
            font-weight: 500;
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: none;
            font-weight: 500;
            font-size: 0.875rem;
        }

        .alert.error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .alert.success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        .alert.show {
            display: block;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .login-wrapper {
                grid-template-columns: 1fr;
                max-width: 500px;
            }

            .info-section {
                display: none;
            }

            .form-section {
                padding: 2.5rem;
            }
        }

        @media (max-width: 768px) {
            .university-header {
                padding: 1rem;
            }

            .header-container {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .university-brand {
                gap: 1rem;
                width: 100%;
            }

            .university-logo-container {
                width: 100px;
                height: 80px;
                font-size: 0.75rem;
            }

            .university-info h1 {
                font-size: 1.25rem;
            }

            .university-info p {
                font-size: 0.875rem;
            }

            .back-home-btn {
                align-self: flex-end;
            }

            .login-container {
                padding: 1rem;
            }

            .form-section {
                padding: 2rem;
            }

            .form-header h2 {
                font-size: 1.75rem;
            }
        }

        @media (max-width: 480px) {
            .university-header {
                padding: 0.75rem;
            }

            .university-brand {
                gap: 0.75rem;
            }

            .form-section {
                padding: 1.5rem;
            }

            .form-header h2 {
                font-size: 1.5rem;
            }
        }

        /* Loading Animation */
        .loading {
            position: relative;
            overflow: hidden;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.2),
                transparent
            );
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { left: -100%; }
            100% { left: 100%; }
        }
    </style>
</head>
<body>
    <!-- University Header -->
     <header class="university-header">
            <div class="header-container">
                <div class="university-brand">
                    <div class="university-logo-container">
                <img src="iaa.png" alt="University Logo" class="university-logo" />
            </div>
            <div class="university-info">
                <h1>Institute of Accountancy Arusha</h1>
                <p>Elections Management System</p>
            </div>
        </div>
        <a href="/project-folder/admin/adminhome.html" class="back-home-btn">
            <i class="fas fa-home"></i>
            Back Home
        </a>
    </div>
</header>

    <!-- Main Login Container -->
    <div class="login-container">
        <div class="login-wrapper">
            <!-- Form Section -->
            <div class="form-section">
                <div class="form-container">
                    <div class="form-header">
                        <h2>Admin Portal</h2>
                        <p>Sign in to access the administrative dashboard</p>
                    </div>

                    <!-- Auth Tabs -->
                    <div class="auth-tabs">
                        <button class="auth-tab active" data-target="login">Sign In</button>
                        <button class="auth-tab" data-target="register">Create Account</button>
                    </div>

                    <!-- Alerts -->
                    <div class="alert error" id="errorAlert"></div>
                    <div class="alert success" id="successAlert"></div>

                    <!-- Login Form -->
                    <form id="loginForm" class="auth-form" method="POST" action="">
                        <input type="hidden" name="action" value="login">
                        
                        <div class="form-group">
                            <label class="form-label" for="loginEmail">Email Address</label>
                            <div class="input-wrapper">
                                <input 
                                    type="email" 
                                    class="form-input" 
                                    id="loginEmail" 
                                    name="email" 
                                    placeholder="admin@university.edu"
                                    required
                                >
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="loginPassword">Password</label>
                            <div class="input-wrapper">
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

                        <button type="submit" class="submit-button">
                            <i class="fas fa-sign-in-alt"></i>
                            Sign In
                        </button>

                        <div class="form-footer">
                            <a href="forgot-password.php">Forgot your password?</a>
                        </div>
                    </form>

                    <!-- Register Form -->
                    <form id="registerForm" class="auth-form" style="display: none;" method="POST" action="">
                        <input type="hidden" name="action" value="register">
                        
                        <div class="form-group">
                            <label class="form-label" for="fullName">Full Name</label>
                            <div class="input-wrapper">
                                <input 
                                    type="text" 
                                    class="form-input" 
                                    id="fullName" 
                                    name="fullname" 
                                    placeholder="Enter your full name"
                                    required
                                >
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="registerEmail">Email Address</label>
                            <div class="input-wrapper">
                                <input 
                                    type="email" 
                                    class="form-input" 
                                    id="registerEmail" 
                                    name="email" 
                                    placeholder="admin@university.edu"
                                    required
                                >
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="registerPassword">Password</label>
                            <div class="input-wrapper">
                                <input 
                                    type="password" 
                                    class="form-input" 
                                    id="registerPassword" 
                                    name="password" 
                                    placeholder="Create a secure password"
                                    required
                                    minlength="8"
                                >
                                <button type="button" class="password-toggle" onclick="togglePassword('registerPassword')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="confirmPassword">Confirm Password</label>
                            <div class="input-wrapper">
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

                        <button type="submit" class="submit-button">
                            <i class="fas fa-user-plus"></i>
                            Create Account
                        </button>

                        <div class="form-footer">
                            <p style="color: var(--text-secondary); font-size: 0.875rem;">
                                Already have an account? 
                                <a href="#" onclick="switchTab('login')">Sign in here</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Info Section -->
            <div class="info-section">
                <div class="info-content">
                    <div class="info-header">
                        <h3>Secure University Election Management</h3>
                        <p>Access powerful administrative tools to manage university elections with confidence and transparency.</p>
                    </div>

                    <ul class="features-list">
                        <li class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <span>Advanced Security & Encryption</span>
                        </li>
                        <li class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <span>Real-time Analytics & Reporting</span>
                        </li>
                        <li class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-users-cog"></i>
                            </div>
                            <span>Comprehensive User Management</span>
                        </li>
                        <li class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-vote-yea"></i>
                            </div>
                            <span>Election Lifecycle Management</span>
                        </li>
                        <li class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <span>Mobile-Responsive Interface</span>
                        </li>
                        <li class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-history"></i>
                            </div>
                            <span>Complete Audit Trail</span>
                        </li>
                    </ul>

                    <div style="margin-top: 2rem; padding: 1.5rem; background: rgba(255, 255, 255, 0.1); border-radius: 12px; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                            <i class="fas fa-info-circle" style="color: rgba(255, 255, 255, 0.8);"></i>
                            <span style="font-weight: 600; font-size: 0.875rem;">System Status</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="font-size: 0.875rem; opacity: 0.9;">System Health</span>
                            <span style="font-size: 0.875rem; font-weight: 600; color: #10b981;">Operational</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="font-size: 0.875rem; opacity: 0.9;">Security Level</span>
                            <span style="font-size: 0.875rem; font-weight: 600; color: #10b981;">High</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="font-size: 0.875rem; opacity: 0.9;">Last Update</span>
                            <span style="font-size: 0.875rem; font-weight: 600;">Just now</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab switching functionality
        const tabs = document.querySelectorAll('.auth-tab');
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
                const header = document.querySelector('.form-header h2');
                const subheader = document.querySelector('.form-header p');
                
                if (target === 'login') {
                    header.textContent = 'Admin Portal';
                    subheader.textContent = 'Sign in to access the administrative dashboard';
                } else {
                    header.textContent = 'Create Admin Account';
                    subheader.textContent = 'Register for administrative access';
                }
            });
        });

        // Password toggle functionality
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.parentElement.querySelector('.password-toggle i');
            
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

        // Alert functions
        const errorAlert = document.getElementById('errorAlert');
        const successAlert = document.getElementById('successAlert');

        function showAlert(type, message) {
            const alert = type === 'error' ? errorAlert : successAlert;
            alert.textContent = message;
            alert.classList.add('show');
            
            setTimeout(() => {
                alert.classList.remove('show');
            }, 5000);
        }

        // Show PHP errors/success messages
        <?php if(!empty($error)): ?>
            showAlert('error', <?php echo json_encode($error); ?>);
        <?php elseif(!empty($success)): ?>
            showAlert('success', <?php echo json_encode($success); ?>);
        <?php endif; ?>

        // Form validation and submission
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');

        // Enhanced form submission with loading states
        function handleFormSubmission(form) {
            const submitButton = form.querySelector('.submit-button');
            const originalText = submitButton.innerHTML;
            
            submitButton.classList.add('loading');
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitButton.disabled = true;
            
            // Reset after 3 seconds if no redirect occurs
            setTimeout(() => {
                submitButton.classList.remove('loading');
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            }, 3000);
        }

        loginForm.addEventListener('submit', (e) => {
            handleFormSubmission(loginForm);
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
            
            handleFormSubmission(registerForm);
        });

        // Input focus effects
        const inputs = document.querySelectorAll('.form-input');
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                input.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', () => {
                input.parentElement.classList.remove('focused');
            });
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Alt + L to focus login email
            if (e.altKey && e.key === 'l') {
                e.preventDefault();
                if (document.getElementById('loginForm').style.display !== 'none') {
                    document.getElementById('loginEmail').focus();
                }
            }
            
            // Alt + R to switch to register
            if (e.altKey && e.key === 'r') {
                e.preventDefault();
                switchTab('register');
            }
            
            // Escape to clear alerts
            if (e.key === 'Escape') {
                errorAlert.classList.remove('show');
                successAlert.classList.remove('show');
            }
        });

        // Auto-hide alerts on click
        [errorAlert, successAlert].forEach(alert => {
            alert.addEventListener('click', () => {
                alert.classList.remove('show');
            });
        });

        // Add subtle animations on page load
        window.addEventListener('load', () => {
            const formSection = document.querySelector('.form-section');
            const infoSection = document.querySelector('.info-section');
            
            formSection.style.opacity = '0';
            formSection.style.transform = 'translateY(20px)';
            
            if (infoSection) {
                infoSection.style.opacity = '0';
                infoSection.style.transform = 'translateX(20px)';
            }
            
            setTimeout(() => {
                formSection.style.transition = 'all 0.6s ease';
                formSection.style.opacity = '1';
                formSection.style.transform = 'translateY(0)';
                
                if (infoSection) {
                    infoSection.style.transition = 'all 0.6s ease 0.2s';
                    infoSection.style.opacity = '1';
                    infoSection.style.transform = 'translateX(0)';
                }
            }, 100);
        });

        // Form field validation feedback
        inputs.forEach(input => {
            input.addEventListener('input', () => {
                if (input.validity.valid) {
                    input.style.borderColor = 'var(--success)';
                } else if (input.value.length > 0) {
                    input.style.borderColor = 'var(--error)';
                } else {
                    input.style.borderColor = 'var(--border)';
                }
            });
        });

        // Enhanced accessibility
        document.addEventListener('DOMContentLoaded', () => {
            // Add ARIA labels
            document.querySelectorAll('.form-input').forEach(input => {
                const label = input.parentElement.parentElement.querySelector('.form-label');
                if (label) {
                    input.setAttribute('aria-label', label.textContent);
                }
            });
            
            // Add form validation messages
            document.querySelectorAll('form').forEach(form => {
                form.setAttribute('novalidate', '');
            });
        });

        console.log('🎓 University Admin Login Portal loaded successfully');
        console.log('🔐 Security features active');
        console.log('📱 Responsive design enabled');
    </script>
</body>
</html>