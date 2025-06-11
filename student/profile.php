<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Database connection
$host = 'localhost';
$db = 'university_voting_system';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch user data from database
try {
    $stmt = $pdo->prepare("
        SELECT u.*, 
               COUNT(v.id) as vote_count,
               MAX(v.voted_at) as last_vote_date
        FROM users u 
        LEFT JOIN votes v ON u.id = v.user_id 
        WHERE u.id = ? 
        GROUP BY u.id
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception("User not found");
    }
    
    // Fetch voting history
    $stmt = $pdo->prepare("
        SELECT v.voted_at, c.name as candidate_name, c.position, e.title as election_name
        FROM votes v
        JOIN candidates c ON v.candidate_id = c.id
        LEFT JOIN elections e ON c.election_id = e.id
        WHERE v.user_id = ?
        ORDER BY v.voted_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $voting_history = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Profile data fetch error: " . $e->getMessage());
    // Fallback data
    $user = [
        'id' => $_SESSION['user_id'],
        'fullname' => $_SESSION['fullname'] ?? 'Student',
        'registration_number' => $_SESSION['registration_number'] ?? 'Unknown',
        'email' => 'student@university.edu',
        'department' => 'Computer Science',
        'year_of_study' => '3rd Year',
        'phone' => '',
        'address' => '',
        'date_of_birth' => '',
        'vote_count' => 0,
        'last_vote_date' => null,
        'created_at' => date('Y-m-d H:i:s')
    ];
    $voting_history = [];
}

// Handle form submissions
$success_message = null;
$error_message = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Generate CSRF token if not exists
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error_message = "Security validation failed. Please try again.";
    } else {
        if (isset($_POST['update_profile'])) {
            try {
                // Validate and sanitize input
                $fullname = trim($_POST['fullname']);
                $email = trim($_POST['email']);
                $department = trim($_POST['department']);
                $year_of_study = trim($_POST['year_of_study']);
                $phone = trim($_POST['phone'] ?? '');
                $address = trim($_POST['address'] ?? '');
                $date_of_birth = $_POST['date_of_birth'] ?? null;
                
                // Validation
                if (empty($fullname) || empty($email)) {
                    throw new Exception("Full name and email are required.");
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Please enter a valid email address.");
                }
                
                // Check if email is already taken by another user
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $_SESSION['user_id']]);
                if ($stmt->fetch()) {
                    throw new Exception("This email is already registered to another user.");
                }
                
                // Update user profile
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET fullname = ?, email = ?, department = ?, year_of_study = ?, 
                        phone = ?, address = ?, date_of_birth = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $fullname, $email, $department, $year_of_study,
                    $phone, $address, $date_of_birth ?: null, $_SESSION['user_id']
                ]);
                
                // Update session variables
                $_SESSION['fullname'] = $fullname;
                
                // Update local user data
                $user['fullname'] = $fullname;
                $user['email'] = $email;
                $user['department'] = $department;
                $user['year_of_study'] = $year_of_study;
                $user['phone'] = $phone;
                $user['address'] = $address;
                $user['date_of_birth'] = $date_of_birth;
                
                $success_message = "Profile updated successfully!";
                
                // Log the update
                error_log("Profile updated for user ID: " . $_SESSION['user_id']);
                
            } catch (Exception $e) {
                $error_message = $e->getMessage();
                error_log("Profile update error: " . $e->getMessage());
            }
        }
        
        if (isset($_POST['change_password'])) {
            try {
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                // Validation
                if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                    throw new Exception("All password fields are required.");
                }
                
                if ($new_password !== $confirm_password) {
                    throw new Exception("New passwords do not match.");
                }
                
                if (strlen($new_password) < 8) {
                    throw new Exception("New password must be at least 8 characters long.");
                }
                
                // Verify current password
                $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $stored_password = $stmt->fetchColumn();
                
                if (!password_verify($current_password, $stored_password)) {
                    throw new Exception("Current password is incorrect.");
                }
                
                // Update password
                $new_password_hash = password_hash($new_password, PASSWORD_ARGON2ID);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$new_password_hash, $_SESSION['user_id']]);
                
                $success_message = "Password changed successfully!";
                
                // Log the password change
                error_log("Password changed for user ID: " . $_SESSION['user_id']);
                
            } catch (Exception $e) {
                $error_message = $e->getMessage();
                error_log("Password change error: " . $e->getMessage());
            }
        }
    }
}

// Generate CSRF token for forms
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - University Voting System</title>
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
            min-height: 100vh;
        }
        
        .container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Enhanced Responsive Navigation */
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
            flex-wrap: wrap;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }
        
        .logo i {
            color: var(--primary);
            font-size: 1.5rem;
        }
        
        .logo h1 {
            font-size: clamp(1.1rem, 2.5vw, 1.3rem);
            font-weight: 700;
            color: var(--dark);
        }
        
        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .nav-link {
            color: var(--gray);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            font-size: 0.9rem;
            white-space: nowrap;
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
            white-space: nowrap;
        }
        
        .logout-button:hover {
            background-color: var(--light-gray);
            color: var(--primary-dark);
        }
        
        /* Main Content - Responsive */
        .main-content {
            flex: 1;
            max-width: 1400px;
            margin: 0 auto;
            padding: clamp(1.5rem, 4vw, 3rem) clamp(1rem, 3vw, 1.5rem);
            width: 100%;
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: clamp(1.5rem, 4vw, 1.8rem);
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
            font-size: clamp(0.9rem, 2vw, 1rem);
        }
        
        /* Responsive Profile Layout */
        .profile-content {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }
        
        @media (min-width: 992px) {
            .profile-content {
                grid-template-columns: 350px 1fr;
            }
        }
        
        /* Profile Sidebar - Enhanced for Mobile */
        .profile-sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        @media (max-width: 991px) {
            .profile-sidebar {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .profile-sidebar > * {
                flex: 1;
                min-width: 280px;
                max-width: 400px;
            }
        }
        
        @media (max-width: 640px) {
            .profile-sidebar {
                flex-direction: column;
            }
            
            .profile-sidebar > * {
                width: 100%;
                max-width: none;
            }
        }
        
        .profile-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }
        
        .profile-header {
            background: var(--gradient);
            padding: 2rem;
            text-align: center;
            color: white;
        }
        
        .profile-avatar {
            width: clamp(80px, 15vw, 120px);
            height: clamp(80px, 15vw, 120px);
            background-color: white;
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: clamp(2rem, 6vw, 3rem);
            color: var(--primary);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .profile-name {
            font-size: clamp(1.2rem, 3vw, 1.5rem);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .profile-id {
            opacity: 0.9;
            font-size: clamp(0.8rem, 2vw, 0.9rem);
        }
        
        .profile-body {
            padding: 1.5rem;
        }
        
        .profile-info-item {
            margin-bottom: 1rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .profile-info-icon {
            width: 40px;
            height: 40px;
            background-color: var(--primary-light);
            color: var(--primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        
        .profile-info-details {
            flex: 1;
            min-width: 0;
        }
        
        .profile-info-details h4 {
            font-size: 0.9rem;
            color: var(--gray);
            font-weight: 500;
            margin-bottom: 0.2rem;
        }
        
        .profile-info-details p {
            font-weight: 600;
            word-wrap: break-word;
        }
        
        /* Enhanced Profile Navigation */
        .profile-navigation {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }
        
        .profile-nav-title {
            padding: 1.2rem 1.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .profile-nav-links {
            list-style: none;
        }
        
        .profile-nav-link {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            color: var(--dark);
            text-decoration: none;
            transition: var(--transition);
            border-left: 3px solid transparent;
            cursor: pointer;
        }
        
        .profile-nav-link:hover {
            background-color: rgba(99, 102, 241, 0.05);
            border-left-color: var(--primary-light);
        }
        
        .profile-nav-link.active {
            background-color: rgba(99, 102, 241, 0.1);
            border-left-color: var(--primary);
            font-weight: 600;
        }
        
        .profile-nav-link i {
            margin-right: 1rem;
            color: var(--primary);
            width: 20px;
            text-align: center;
        }
        
        /* Responsive Profile Main Content */
        .profile-main {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .profile-section {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }
        
        .profile-section-header {
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .profile-section-title {
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .profile-section-body {
            padding: 1.5rem;
        }
        
        /* Enhanced Responsive Form */
        .profile-form {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        
        @media (min-width: 640px) {
            .profile-form {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .form-group.full-width {
                grid-column: span 2;
            }
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.9rem;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            background-color: white;
        }
        
        .form-control:focus {
            border-color: var(--primary-light);
            outline: none;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .form-control[readonly] {
            background-color: var(--light);
            cursor: not-allowed;
            color: var(--gray);
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        
        select.form-control {
            cursor: pointer;
        }
        
        .form-submit {
            grid-column: span 1;
            margin-top: 1rem;
        }
        
        @media (min-width: 640px) {
            .form-submit {
                grid-column: span 2;
            }
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            justify-content: center;
            min-width: 120px;
        }
        
        .btn-primary {
            background: var(--gradient);
            color: white;
            box-shadow: var(--button-shadow);
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 10px rgba(99, 102, 241, 0.4);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }
        
        .btn-outline:hover {
            background-color: rgba(99, 102, 241, 0.05);
        }
        
        /* Alert Messages - Enhanced */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }
        
        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--secondary);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-left: 4px solid var(--secondary);
        }
        
        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-left: 4px solid var(--danger);
        }
        
        .alert i {
            font-size: 1.2rem;
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
        
        /* Voting History Table - Responsive */
        .voting-history-container {
            overflow-x: auto;
            margin: -1.5rem;
            padding: 1.5rem;
        }
        
        .voting-history-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }
        
        .voting-history-table th, 
        .voting-history-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .voting-history-table th {
            font-weight: 600;
            color: var(--gray);
            font-size: 0.9rem;
            background-color: var(--light);
        }
        
        .voting-history-table td {
            font-size: 0.9rem;
        }
        
        .voting-status {
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .voting-status-completed {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--secondary);
        }
        
        .voting-status-pending {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }
        
        /* Enhanced Password Section */
        .password-section .profile-form {
            grid-template-columns: 1fr;
            max-width: 500px;
        }
        
        .password-strength-meter {
            margin-top: 0.5rem;
            height: 4px;
            background-color: var(--light-gray);
            border-radius: 2px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
        }
        
        .strength-weak { background-color: var(--danger); }
        .strength-medium { background-color: var(--warning); }
        .strength-strong { background-color: var(--secondary); }
        
        .password-requirements {
            margin-top: 1rem;
            padding: 1rem;
            background-color: var(--light);
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .requirement-list {
            list-style: none;
            margin-top: 0.5rem;
        }
        
        .requirement-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.25rem;
        }
        
        .requirement-item i {
            width: 16px;
            font-size: 0.8rem;
        }
        
        .requirement-met {
            color: var(--secondary);
        }
        
        .requirement-unmet {
            color: var(--gray);
        }
        
        /* Stats Cards in Profile */
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background: var(--light);
            padding: 1.5rem 1rem;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: var(--gray);
        }
        
        /* Loading States */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--light-gray);
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Tab Content */
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Mobile-specific Styles */
        @media (max-width: 768px) {
            .navbar {
                padding: 1rem;
            }
            
            .main-content {
                padding: 1.5rem 1rem;
            }
            
            .profile-form {
                grid-template-columns: 1fr;
            }
            
            .form-submit {
                grid-column: span 1;
            }
            
            .voting-history-table {
                font-size: 0.8rem;
            }
            
            .voting-history-table th,
            .voting-history-table td {
                padding: 0.75rem 0.5rem;
            }
        }
        
        @media (max-width: 576px) {
            .profile-header {
                padding: 1.5rem 1rem;
            }
            
            .profile-body {
                padding: 1rem;
            }
            
            .profile-section-body {
                padding: 1rem;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
            
            .profile-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        /* Accessibility Improvements */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
        
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        /* High contrast mode */
        @media (prefers-contrast: high) {
            .card, .profile-section {
                border: 2px solid var(--dark);
            }
            
            .btn-primary {
                border: 2px solid var(--primary-dark);
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
                <a href="profile.php" class="nav-link active">Profile</a>
                <a href="contact.php" class="nav-link">Support</a>
            </div>
            <a href="logout.php" class="logout-button">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">My Profile</h1>
                <p class="page-description">Manage your account settings, view your voting history, and update your personal information.</p>
            </div>
            
            <!-- Alert Messages -->
            <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
            <?php endif; ?>
            
            <!-- Profile Content -->
            <div class="profile-content">
                <!-- Profile Sidebar -->
                <div class="profile-sidebar">
                    <!-- Profile Card -->
                    <div class="profile-card">
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <h2 class="profile-name"><?php echo htmlspecialchars($user['fullname']); ?></h2>
                            <p class="profile-id"><?php echo htmlspecialchars($user['registration_number']); ?></p>
                        </div>
                        <div class="profile-body">
                            <div class="profile-info-item">
                                <div class="profile-info-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="profile-info-details">
                                    <h4>Email</h4>
                                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                                </div>
                            </div>
                            <div class="profile-info-item">
                                <div class="profile-info-icon">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div class="profile-info-details">
                                    <h4>Department</h4>
                                    <p><?php echo htmlspecialchars($user['department']); ?></p>
                                </div>
                            </div>
                            <div class="profile-info-item">
                                <div class="profile-info-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="profile-info-details">
                                    <h4>Year of Study</h4>
                                    <p><?php echo htmlspecialchars($user['year_of_study']); ?></p>
                                </div>
                            </div>
                            <?php if (!empty($user['phone'])): ?>
                            <div class="profile-info-item">
                                <div class="profile-info-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div class="profile-info-details">
                                    <h4>Phone</h4>
                                    <p><?php echo htmlspecialchars($user['phone']); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Profile Navigation -->
                    <div class="profile-navigation">
                        <div class="profile-nav-title">
                            Settings
                        </div>
                        <ul class="profile-nav-links">
                            <li>
                                <a href="#" class="profile-nav-link active" data-tab="personal-info">
                                    <i class="fas fa-user"></i> Personal Information
                                </a>
                            </li>
                            <li>
                                <a href="#" class="profile-nav-link" data-tab="voting-history">
                                    <i class="fas fa-history"></i> Voting History
                                </a>
                            </li>
                            <li>
                                <a href="#" class="profile-nav-link" data-tab="password">
                                    <i class="fas fa-lock"></i> Change Password
                                </a>
                            </li>
                            <li>
                                <a href="#" class="profile-nav-link" data-tab="statistics">
                                    <i class="fas fa-chart-bar"></i> My Statistics
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Profile Main -->
                <div class="profile-main">
                    <!-- Personal Information Section -->
                    <div class="profile-section tab-content active" id="personal-info">
                        <div class="profile-section-header">
                            <h3 class="profile-section-title">Personal Information</h3>
                            <small style="color: var(--gray);">Update your profile details</small>
                        </div>
                        <div class="profile-section-body">
                            <form action="" method="post" class="profile-form" id="profileForm">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                
                                <div class="form-group">
                                    <label for="fullname">Full Name *</label>
                                    <input type="text" id="fullname" name="fullname" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="registration_number">Registration Number</label>
                                    <input type="text" id="registration_number" name="registration_number" 
                                           class="form-control" value="<?php echo htmlspecialchars($user['registration_number']); ?>" readonly>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email Address *</label>
                                    <input type="email" id="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="tel" id="phone" name="phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                           placeholder="+255 XXX XXX XXX">
                                </div>
                                
                                <div class="form-group">
                                    <label for="department">Department</label>
                                    <select id="department" name="department" class="form-control">
                                        <option value="Computer Science" <?php echo $user['department'] === 'Computer Science' ? 'selected' : ''; ?>>Computer Science</option>
                                        <option value="Electrical Engineering" <?php echo $user['department'] === 'Electrical Engineering' ? 'selected' : ''; ?>>Electrical Engineering</option>
                                        <option value="Business Administration" <?php echo $user['department'] === 'Business Administration' ? 'selected' : ''; ?>>Business Administration</option>
                                        <option value="Mathematics" <?php echo $user['department'] === 'Mathematics' ? 'selected' : ''; ?>>Mathematics</option>
                                        <option value="Physics" <?php echo $user['department'] === 'Physics' ? 'selected' : ''; ?>>Physics</option>
                                        <option value="Accounting" <?php echo $user['department'] === 'Accounting' ? 'selected' : ''; ?>>Accounting</option>
                                        <option value="Finance" <?php echo $user['department'] === 'Finance' ? 'selected' : ''; ?>>Finance</option>
                                        <option value="Economics" <?php echo $user['department'] === 'Economics' ? 'selected' : ''; ?>>Economics</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="year_of_study">Year of Study</label>
                                    <select id="year_of_study" name="year_of_study" class="form-control">
                                        <option value="1st Year" <?php echo $user['year_of_study'] === '1st Year' ? 'selected' : ''; ?>>1st Year</option>
                                        <option value="2nd Year" <?php echo $user['year_of_study'] === '2nd Year' ? 'selected' : ''; ?>>2nd Year</option>
                                        <option value="3rd Year" <?php echo $user['year_of_study'] === '3rd Year' ? 'selected' : ''; ?>>3rd Year</option>
                                        <option value="4th Year" <?php echo $user['year_of_study'] === '4th Year' ? 'selected' : ''; ?>>4th Year</option>
                                        <option value="5th Year" <?php echo $user['year_of_study'] === '5th Year' ? 'selected' : ''; ?>>5th Year</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="date_of_birth">Date of Birth</label>
                                    <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" 
                                           value="<?php echo $user['date_of_birth'] ?? ''; ?>">
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="address">Address</label>
                                    <textarea id="address" name="address" class="form-control" rows="3" 
                                              placeholder="Enter your address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-submit">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Voting History Section -->
                    <div class="profile-section tab-content" id="voting-history">
                        <div class="profile-section-header">
                            <h3 class="profile-section-title">Voting History</h3>
                            <small style="color: var(--gray);">Your participation record</small>
                        </div>
                        <div class="profile-section-body">
                            <?php if (empty($voting_history)): ?>
                                <div style="text-align: center; padding: 2rem;">
                                    <i class="fas fa-vote-yea" style="font-size: 3rem; color: var(--gray); margin-bottom: 1rem;"></i>
                                    <h3 style="color: var(--gray); margin-bottom: 0.5rem;">No Voting History</h3>
                                    <p style="color: var(--gray);">You haven't participated in any elections yet.</p>
                                    <a href="dashboard.php" class="btn btn-primary" style="margin-top: 1rem;">
                                        <i class="fas fa-vote-yea"></i> Go Vote Now
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="voting-history-container">
                                    <table class="voting-history-table">
                                        <thead>
                                            <tr>
                                                <th>Election</th>
                                                <th>Candidate</th>
                                                <th>Position</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($voting_history as $vote): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($vote['election_name'] ?? 'General Election'); ?></td>
                                                    <td><?php echo htmlspecialchars($vote['candidate_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($vote['position']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($vote['voted_at'])); ?></td>
                                                    <td>
                                                        <span class="voting-status voting-status-completed">
                                                            Completed
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Change Password Section -->
                    <div class="profile-section tab-content password-section" id="password">
                        <div class="profile-section-header">
                            <h3 class="profile-section-title">Change Password</h3>
                            <small style="color: var(--gray);">Update your account security</small>
                        </div>
                        <div class="profile-section-body">
                            <form action="" method="post" class="profile-form" id="passwordForm">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                
                                <div class="form-group">
                                    <label for="current_password">Current Password *</label>
                                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_password">New Password *</label>
                                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                                    <div class="password-strength-meter">
                                        <div class="password-strength-bar" id="strengthBar"></div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password *</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                                </div>
                                
                                <div class="password-requirements">
                                    <h4>Password Requirements:</h4>
                                    <ul class="requirement-list">
                                        <li class="requirement-item" id="length-req">
                                            <i class="fas fa-times-circle requirement-unmet"></i>
                                            At least 8 characters long
                                        </li>
                                        <li class="requirement-item" id="uppercase-req">
                                            <i class="fas fa-times-circle requirement-unmet"></i>
                                            Contains uppercase letter
                                        </li>
                                        <li class="requirement-item" id="lowercase-req">
                                            <i class="fas fa-times-circle requirement-unmet"></i>
                                            Contains lowercase letter
                                        </li>
                                        <li class="requirement-item" id="number-req">
                                            <i class="fas fa-times-circle requirement-unmet"></i>
                                            Contains number
                                        </li>
                                        <li class="requirement-item" id="special-req">
                                            <i class="fas fa-times-circle requirement-unmet"></i>
                                            Contains special character
                                        </li>
                                    </ul>
                                </div>
                                
                                <div class="form-submit">
                                    <button type="submit" name="change_password" class="btn btn-primary" id="passwordSubmitBtn" disabled>
                                        <i class="fas fa-lock"></i> Update Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Statistics Section -->
                    <div class="profile-section tab-content" id="statistics">
                        <div class="profile-section-header">
                            <h3 class="profile-section-title">My Statistics</h3>
                            <small style="color: var(--gray);">Your participation overview</small>
                        </div>
                        <div class="profile-section-body">
                            <div class="profile-stats">
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo $user['vote_count']; ?></div>
                                    <div class="stat-label">Total Votes</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value"><?php echo date('Y', strtotime($user['created_at'])); ?></div>
                                    <div class="stat-label">Member Since</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value">
                                        <?php 
                                        if ($user['last_vote_date']) {
                                            $days = (new DateTime())->diff(new DateTime($user['last_vote_date']))->days;
                                            echo $days;
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </div>
                                    <div class="stat-label">Days Since Last Vote</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value">
                                        <?php echo $user['vote_count'] > 0 ? '100%' : '0%'; ?>
                                    </div>
                                    <div class="stat-label">Participation Rate</div>
                                </div>
                            </div>
                            
                            <div style="background: var(--light); padding: 1.5rem; border-radius: 8px;">
                                <h4 style="margin-bottom: 1rem;">Account Information</h4>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; font-size: 0.9rem;">
                                    <div>
                                        <strong>Account Created:</strong><br>
                                        <?php echo date('F j, Y', strtotime($user['created_at'])); ?>
                                    </div>
                                    <div>
                                        <strong>Last Updated:</strong><br>
                                        <?php echo isset($user['updated_at']) ? date('F j, Y', strtotime($user['updated_at'])) : 'Never'; ?>
                                    </div>
                                    <div>
                                        <strong>Account Status:</strong><br>
                                        <span style="color: var(--secondary); font-weight: 600;">Active</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div