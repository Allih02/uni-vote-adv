<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}

// Initialize messages
$success_message = null;
$error_message = null;

// Handle password change form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["change_password"])) {
    $current_password = $_POST["current_password"];
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];
    
    // Validate input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "All fields are required";
    } 
    elseif ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match";
    }
    elseif (strlen($new_password) < 8) {
        $error_message = "New password must be at least 8 characters long";
    }
    else {
        // In a real implementation, you would:
        // 1. Verify the current password matches what's in the database
        // 2. Hash the new password
        // 3. Update the database
        
        // This is a simulation - in a real app you'd verify against DB
        $current_password_correct = true; // Assume correct for demo
        
        if (!$current_password_correct) {
            $error_message = "Current password is incorrect";
        } else {
            // Simulate successful password change
            $success_message = "Your password has been changed successfully";
            
            // In a real application:
            // $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            // Update database with new hashed password
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - University Voting System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #a5b4fc;
            --secondary: #10b981;
            --secondary-light: #34d399;
            --dark: #1e293b;
            --light: #f8fafc;
            --gray: #64748b;
            --light-gray: #e2e8f0;
            --danger: #ef4444;
            --warning: #f59e0b;
            --gradient: linear-gradient(135deg, #6366f1, #8b5cf6);
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --button-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.4);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --border-radius: 12px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
        
        body {
            background-color: #f1f5f9;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Navigation Bar */
        .navbar {
            background-color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo i {
            color: var(--primary);
            font-size: 1.5rem;
        }
        
        .logo h1 {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .nav-link {
            color: var(--gray);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            font-size: 0.9rem;
        }
        
        .nav-link:hover {
            color: var(--primary);
        }
        
        .nav-link.active {
            color: var(--primary);
            font-weight: 600;
        }
        
        .logout-button {
            background-color: transparent;
            color: var(--dark);
            border: 1px solid var(--light-gray);
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }
        
        .logout-button:hover {
            background-color: var(--light-gray);
            color: var(--primary-dark);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 1.5rem;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .page-header {
            margin-bottom: 2rem;
            width: 100%;
            max-width: 600px;
        }
        
        .page-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            padding-bottom: 10px;
        }
        
        .page-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--gradient);
            border-radius: 2px;
        }
        
        .page-description {
            color: var(--gray);
            max-width: 700px;
        }
        
        /* Password Card */
        .password-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
            width: 100%;
            max-width: 600px;
            animation: fadeIn 0.5s ease;
        }
        
        .password-header {
            background: var(--gradient);
            padding: 2rem;
            color: white;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .password-icon {
            width: 50px;
            height: 50px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .password-header-text h2 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0.3rem;
        }
        
        .password-header-text p {
            opacity: 0.9;
            font-size: 0.95rem;
        }
        
        .password-body {
            padding: 2rem;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .form-control:focus {
            border-color: var(--primary-light);
            outline: none;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .password-input-wrapper {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray);
            cursor: pointer;
            font-size: 1rem;
        }
        
        .password-toggle:hover {
            color: var(--primary);
        }
        
        .password-strength {
            margin-top: 8px;
            height: 5px;
            border-radius: 3px;
            background-color: var(--light-gray);
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s;
        }
        
        .strength-text {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            margin-top: 5px;
        }
        
        .strength-label {
            font-weight: 500;
        }
        
        .password-requirements {
            margin-top: 1.5rem;
            padding: 1.2rem;
            background-color: var(--light);
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .password-requirements h4 {
            font-size: 1rem;
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .password-requirements h4 i {
            color: var(--primary);
        }
        
        .requirement-list {
            list-style: none;
        }
        
        .requirement-list li {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .requirement-list li i {
            width: 18px;
        }
        
        .requirement-valid {
            color: var(--secondary);
        }
        
        .requirement-invalid {
            color: var(--gray);
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 0.9rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: var(--gradient);
            color: white;
            box-shadow: var(--button-shadow);
            flex: 1;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 10px rgba(99, 102, 241, 0.4);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }
        
        .btn-outline:hover {
            background-color: rgba(99, 102, 241, 0.05);
        }
        
        /* Alert */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            max-width: 600px;
        }
        
        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--secondary);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        .alert i {
            font-size: 1.2rem;
        }
        
        /* Back Link */
        .back-link {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray);
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 1.5rem;
            transition: var(--transition);
            align-self: flex-start;
        }
        
        .back-link:hover {
            color: var(--primary);
        }
        
        /* Footer */
        .footer {
            background-color: var(--dark);
            color: white;
            padding: 3rem 2rem;
            margin-top: 3rem;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        .footer-section h4 {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
            position: relative;
            padding-bottom: 10px;
        }
        
        .footer-section h4::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 3px;
            background-color: var(--primary);
        }
        
        .footer-section p {
            margin-bottom: 1rem;
            opacity: 0.8;
            font-size: 0.9rem;
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 0.8rem;
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: var(--transition);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .footer-links a:hover {
            color: var(--primary-light);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            margin-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.9rem;
            opacity: 0.7;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar {
                padding: 1rem;
            }
            
            .logo h1 {
                font-size: 1.1rem;
            }
            
            .main-content {
                padding: 2rem 1rem;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .password-header {
                padding: 1.5rem;
            }
            
            .password-body {
                padding: 1.5rem;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
        
        @media (max-width: 576px) {
            .nav-links {
                display: none;
            }
            
            .password-header {
                flex-direction: column;
                text-align: center;
            }
            
            .password-icon {
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Navigation Bar -->
        <nav class="navbar">
            <div class="logo">
                <i class="fas fa-vote-yea"></i>
                <h1>University Voting System</h1>
            </div>
            <div class="nav-links">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="profile.php" class="nav-link">Profile</a>
            </div>
            <a href="logout.php" class="logout-button">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
        
        <!-- Main Content -->
        <main class="main-content">
            <a href="profile.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Profile
            </a>
            
            <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success_message; ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error_message; ?></span>
            </div>
            <?php endif; ?>
            
            <!-- Password Card -->
            <div class="password-card">
                <div class="password-header">
                    <div class="password-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <div class="password-header-text">
                        <h2>Change Your Password</h2>
                        <p>Create a strong, unique password to keep your account secure</p>
                    </div>
                </div>
                
                <div class="password-body">
                    <form action="" method="post" id="passwordForm">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <div class="password-input-wrapper">
                                <input type="password" id="current_password" name="current_password" class="form-control" required>
                                <button type="button" class="password-toggle" data-target="current_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <div class="password-input-wrapper">
                                <input type="password" id="new_password" name="new_password" class="form-control" required>
                                <button type="button" class="password-toggle" data-target="new_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength">
                                <div class="password-strength-bar" id="strengthBar"></div>
                            </div>
                            <div class="strength-text">
                                <span class="strength-label" id="strengthLabel">Strength: Too Weak</span>
                                <span id="strengthPercent">0%</span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <div class="password-input-wrapper">
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                                <button type="button" class="password-toggle" data-target="confirm_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="password-requirements">
                            <h4><i class="fas fa-shield-alt"></i> Password Requirements</h4>
                            <ul class="requirement-list">
                                <li id="length-req" class="requirement-invalid">
                                    <i class="fas fa-times-circle"></i>
                                    At least 8 characters long
                                </li>
                                <li id="uppercase-req" class="requirement-invalid">
                                    <i class="fas fa-times-circle"></i>
                                    Contains at least one uppercase letter
                                </li>
                                <li id="lowercase-req" class="requirement-invalid">
                                    <i class="fas fa-times-circle"></i>
                                    Contains at least one lowercase letter
                                </li>
                                <li id="number-req" class="requirement-invalid">
                                    <i class="fas fa-times-circle"></i>
                                    Contains at least one number
                                </li>
                                <li id="special-req" class="requirement-invalid">
                                    <i class="fas fa-times-circle"></i>
                                    Contains at least one special character
                                </li>
                                <li id="match-req" class="requirement-invalid">
                                    <i class="fas fa-times-circle"></i>
                                    Passwords match
                                </li>
                            </ul>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-lock"></i> Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
        
        <!-- Footer -->
        <footer class="footer">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>About</h4>
                    <p>The University Voting System is a secure platform designed to facilitate fair and transparent elections across the university community.</p>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                        <li><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                        <li><a href="help.php"><i class="fas fa-question-circle"></i> Help & Support</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Contact</h4>
                    <ul class="footer-links">
                        <li><a href="mailto:support@university.edu"><i class="fas fa-envelope"></i> support@university.edu</a></li>
                        <li><a href="tel:+11234567890"><i class="fas fa-phone"></i> +1 (123) 456-7890</a></li>
                        <li><a href="#"><i class="fas fa-map-marker-alt"></i> University Campus, Building A</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> University Voting System. All rights reserved.</p>
            </div>
        </footer>
    </div>
    
    <script>
        // Toggle password visibility
        const toggleButtons = document.querySelectorAll('.password-toggle');
        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const inputField = document.getElementById(targetId);
                const icon = this.querySelector('i');
                
                if (inputField.type === 'password') {
                    inputField.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    inputField.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
        
        // Password strength checker
        const newPasswordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthLabel = document.getElementById('strengthLabel');
        const strengthPercent = document.getElementById('strengthPercent');
        
        // Requirement elements
        const lengthReq = document.getElementById('length-req');
        const uppercaseReq = document.getElementById('uppercase-req');
        const lowercaseReq = document.getElementById('lowercase-req');
        const numberReq = document.getElementById('number-req');
        const specialReq = document.getElementById('special-req');
        const matchReq = document.getElementById('match-req');
        
        function updateRequirement(element, isValid) {
            if (isValid) {
                element.classList.remove('requirement-invalid');
                element.classList.add('requirement-valid');
                element.querySelector('i').classList.remove('fa-times-circle');
                element.querySelector('i').classList.add('fa-check-circle');
            } else {
                element.classList.remove('requirement-valid');
                element.classList.add('requirement-invalid');
                element.querySelector('i').classList.remove('fa-check-circle');
                element.querySelector('i').classList.add('fa-times-circle');
            }
        }
        
        function checkPasswordStrength(password) {
            // Initialize score
            let score = 0;
            
            // Check requirements
            const hasLength = password.length >= 8;
            const hasUppercase = /[A-Z]/.test(password);
            const hasLowercase = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[^A-Za-z0-9]/.test(password);
            
            // Update requirement indicators
            updateRequirement(lengthReq, hasLength);
            updateRequirement(uppercaseReq, hasUppercase);
            updateRequirement(lowercaseReq, hasLowercase);
            updateRequirement(numberReq, hasNumber);
            updateRequirement(specialReq, hasSpecial);
            
            // Calculate score based on requirements
            if (hasLength) score += 20;
            if (hasUppercase) score += 20;
            if (hasLowercase) score += 20;
            if (hasNumber) score += 20;
            if (hasSpecial) score += 20;
            
            // Update strength bar
            strengthBar.style.width = score + '%';
            strengthPercent.textContent = score + '%';
            
            // Set color based on score
            if (score < 40) {
                strengthBar.style.backgroundColor = '#ef4444';
                strengthLabel.textContent = 'Strength: Too Weak';
                strengthLabel.style.color = '#ef4444';
            } else if (score < 60) {
                strengthBar.style.backgroundColor = '#f59e0b';
                strengthLabel.textContent = 'Strength: Weak';
                strengthLabel.style.color = '#f59e0b';
            } else if (score < 80) {
                strengthBar.style.backgroundColor = '#10b981';
                strengthLabel.textContent = 'Strength: Good';
                strengthLabel.style.color = '#10b981';
            } else {
                strengthBar.style.backgroundColor = '#10b981';
                strengthLabel.textContent = 'Strength: Strong';
                strengthLabel.style.color = '#10b981';
            }
            
            return score;
        }
        
        function checkPasswordMatch() {
            const newPassword = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            const doMatch = newPassword === confirmPassword && confirmPassword !== '';
            
            updateRequirement(matchReq, doMatch);
            return doMatch;
        }
        
        // Add event listeners
        newPasswordInput.addEventListener('input', function() {
            checkPasswordStrength(this.value);
            if (confirmPasswordInput.value !== '') {
                checkPasswordMatch();
            }
        });
        
        confirmPasswordInput.addEventListener('input', function() {
            checkPasswordMatch();
        });
        
        // Form validation
        const passwordForm = document.getElementById('passwordForm');
        passwordForm.addEventListener('submit', function(e) {
            const newPassword = newPasswordInput.value;
            const score = checkPasswordStrength(newPassword);
            const passwordsMatch = checkPasswordMatch();
            
            if (score < 60) {
                e.preventDefault();
                alert('Please create a stronger password by following the requirements.');
            } else if (!passwordsMatch) {
                e.preventDefault();
                alert('Passwords do not match. Please try again.');
            }
        });
        
        // Auto hide alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            }, 5000);
        });
    </script>
</body>
</html>