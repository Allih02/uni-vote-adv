<?php
session_start();
require_once 'config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Get admin information
try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM admins WHERE admin_id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin_user) {
        session_destroy();
        header("Location: login.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $admin_user = ['fullname' => 'Admin User', 'role' => 'Administrator'];
}

// Handle contact form submission
$message_sent = false;
$error_message = '';

if ($_POST && isset($_POST['submit_contact'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        // In a real application, you would send an email or save to database
        // For this example, we'll just show a success message
        $message_sent = true;
        
        // Log the contact request
        error_log("Contact form submission: $name ($email) - $subject");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - University Voting System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        :root {
            /* Primary Colors */
            --primary: #6366f1;
            --primary-dark: #4338ca;
            --primary-light: #a5b4fc;
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            
            /* Secondary Colors */
            --secondary: #8b5cf6;
            --accent: #f59e0b;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --info: #3b82f6;
            
            /* Neutral Colors */
            --background: #f8fafc;
            --surface: #ffffff;
            --surface-alt: #f1f5f9;
            --surface-hover: #e2e8f0;
            --border: #e2e8f0;
            --border-light: #f1f5f9;
            
            /* Text Colors */
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #94a3b8;
            --text-inverse: #ffffff;
            
            /* Shadows */
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            
            /* Sizes */
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --sidebar-width: 280px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--background);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Modern Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            overflow-y: auto;
        }

        .sidebar.hidden {
            transform: translateX(-100%);
        }

        /* Sidebar Header */
        .sidebar-header {
            padding: 2rem 2rem 1.5rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1rem;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            margin-bottom: 1rem;
        }

        .logo i {
            font-size: 1.75rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .admin-info {
            text-align: left;
        }

        .admin-name {
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .admin-role {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }

        /* Sidebar Navigation */
        .sidebar-nav {
            padding: 0 1rem 2rem 1rem;
        }

        .nav-section {
            margin-bottom: 2rem;
        }

        .nav-section-title {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 0 1rem 0.75rem 1rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 0.875rem 1rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            margin-bottom: 0.25rem;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(4px);
        }

        .nav-item.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            box-shadow: 0 4px 20px rgba(255, 255, 255, 0.1);
        }

        .nav-item i {
            width: 20px;
            height: 20px;
            margin-right: 0.75rem;
            font-size: 1rem;
        }

        .nav-item span {
            font-weight: 500;
            letter-spacing: -0.01em;
        }

        /* Mobile Header */
        .mobile-header {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 70px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            z-index: 1001;
            padding: 0 1rem;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .mobile-logo {
            color: white;
            font-size: 1.25rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .hamburger {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }

        .hamburger:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .hamburger-icon {
            width: 24px;
            height: 24px;
            fill: white;
            transition: transform 0.3s ease;
        }

        .hamburger.active .hamburger-icon {
            transform: rotate(90deg);
        }

        /* Overlay for mobile */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .overlay.active {
            opacity: 1;
        }

        /* Dashboard Layout */
        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
            transition: margin-left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            width: calc(100vw - 280px);
        }

        /* Top Navigation */
        .top-nav {
            background: var(--surface);
            padding: 1rem 2rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .breadcrumb .current {
            color: var(--text-primary);
            font-weight: 600;
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .notification-btn {
            position: relative;
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
        }

        .notification-btn:hover {
            background: var(--surface-alt);
            color: var(--primary);
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
        }

        .user-menu:hover {
            background: var(--surface-alt);
        }

        .user-avatar {
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
        }

        /* Contact Content */
        .contact-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            background: var(--surface);
            padding: 2rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            margin-bottom: 2rem;
            text-align: center;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .contact-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .contact-form-section {
            background: var(--surface);
            padding: 2rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
        }

        .contact-info-section {
            background: var(--surface);
            padding: 2rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--surface);
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-button {
            background: var(--primary-gradient);
            color: white;
            padding: 0.875rem 2rem;
            border: none;
            border-radius: var(--radius-lg);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .form-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .form-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: var(--success);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--error);
        }

        .contact-method {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .contact-method:hover {
            background: var(--surface-alt);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .contact-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
        }

        .contact-details h3 {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .contact-details p {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .hours-section {
            background: var(--surface-alt);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            margin-top: 2rem;
        }

        .hours-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .hours-list {
            list-style: none;
        }

        .hours-list li {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border-light);
        }

        .hours-list li:last-child {
            border-bottom: none;
        }

        .emergency-section {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem;
            border-radius: var(--radius-xl);
            text-align: center;
            margin-top: 2rem;
        }

        .emergency-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .emergency-number {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .mobile-header {
                display: flex;
            }

            .sidebar {
                top: 70px;
                height: calc(100vh - 70px);
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .overlay {
                display: block;
                top: 70px;
                height: calc(100vh - 70px);
            }

            .main-content {
                margin-left: 0;
                margin-top: 70px;
                padding: 1.5rem;
                width: 100vw;
            }

            .contact-container {
                max-width: 100%;
            }

            .contact-content {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .page-header {
                padding: 1.5rem;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .contact-form-section, .contact-info-section {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Header -->
    <header class="mobile-header">
        <div class="mobile-logo">
            <i class="fas fa-shield-alt"></i>
            VoteAdmin
        </div>
        <button class="hamburger" id="hamburgerBtn">
            <svg class="hamburger-icon" viewBox="0 0 24 24">
                <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
            </svg>
        </button>
    </header>

    <!-- Overlay -->
    <div class="overlay" id="overlay"></div>

    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="admin_dashboard.php" class="logo">
                    <i class="fas fa-shield-alt"></i>
                    <span>VoteAdmin</span>
                </a>
                <div class="admin-info">
                    <div class="admin-name"><?php echo htmlspecialchars($admin_user['fullname'] ?? 'Admin User'); ?></div>
                    <div class="admin-role"><?php echo htmlspecialchars($admin_user['role'] ?? 'Administrator'); ?></div>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="admin_dashboard.php" class="nav-item">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="admin_elections.php" class="nav-item">
                        <i class="fas fa-poll"></i>
                        <span>Elections</span>
                    </a>
                    <a href="admin_candidates.php" class="nav-item">
                        <i class="fas fa-user-tie"></i>
                        <span>Candidates</span>
                    </a>
                    <a href="admin_voters.php" class="nav-item">
                        <i class="fas fa-users"></i>
                        <span>Voters</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Reports & Analytics</div>
                    <a href="admin_results.php" class="nav-item">
                        <i class="fas fa-chart-bar"></i>
                        <span>Results</span>
                    </a>
                    <a href="admin_analytics.php" class="nav-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Analytics</span>
                    </a>
                    <a href="admin_reports.php" class="nav-item">
                        <i class="fas fa-file-alt"></i>
                        <span>Reports</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">System</div>
                    <a href="admin_settings.php" class="nav-item">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                    <a href="admin_audit.php" class="nav-item">
                        <i class="fas fa-history"></i>
                        <span>Audit Log</span>
                    </a>
                    <a href="logout.php" class="nav-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Navigation -->
            <nav class="top-nav">
                <div class="breadcrumb">
                    <i class="fas fa-home"></i>
                    <span>/</span>
                    <span class="current">Contact Us</span>
                </div>
                <div class="top-actions">
                    <button class="notification-btn" onclick="window.location.href='admin_notifications.php'">
                        <i class="fas fa-bell"></i>
                    </button>
                    <div class="user-menu" onclick="toggleUserDropdown()">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($admin_user['fullname'] ?? 'AU', 0, 2)); ?>
                        </div>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
            </nav>

            <!-- Contact Content -->
            <div class="contact-container">
                <!-- Page Header -->
                <div class="page-header">
                    <h1 class="page-title">Contact Support</h1>
                    <p class="page-subtitle">Get help with the voting system or report issues to our support team</p>
                </div>

                <!-- Main Contact Content -->
                <div class="contact-content">
                    <!-- Contact Form Section -->
                    <div class="contact-form-section">
                        <h2 class="section-title">Send us a Message</h2>
                        
                        <?php if ($message_sent): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                Thank you for your message! Our support team will get back to you within 24 hours.
                            </div>
                        <?php endif; ?>

                        <?php if ($error_message): ?>
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="name" class="form-label">Full Name *</label>
                                <input type="text" id="name" name="name" class="form-input" required 
                                       value="<?php echo htmlspecialchars($admin_user['fullname'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" id="email" name="email" class="form-input" required
                                       value="<?php echo htmlspecialchars($admin_user['email'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="priority" class="form-label">Priority Level</label>
                                <select id="priority" name="priority" class="form-select">
                                    <option value="low">Low - General inquiry</option>
                                    <option value="medium" selected>Medium - System issue</option>
                                    <option value="high">High - Election problem</option>
                                    <option value="urgent">Urgent - System down</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="subject" class="form-label">Subject *</label>
                                <input type="text" id="subject" name="subject" class="form-input" required
                                       placeholder="Brief description of your issue">
                            </div>

                            <div class="form-group">
                                <label for="message" class="form-label">Message *</label>
                                <textarea id="message" name="message" class="form-textarea" required
                                         placeholder="Please provide detailed information about your issue, including any error messages, steps to reproduce the problem, and the impact on your work."></textarea>
                            </div>

                            <button type="submit" name="submit_contact" class="form-button">
                                <i class="fas fa-paper-plane"></i>
                                Send Message
                            </button>
                        </form>
                    </div>

                    <!-- Contact Information Section -->
                    <div class="contact-info-section">
                        <h2 class="section-title">Contact Information</h2>
                        
                        <div class="contact-method">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-details">
                                <h3>Email Support</h3>
                                <p>voting-support@university.edu</p>
                                <p>Response time: Within 24 hours</p>
                            </div>
                        </div>

                        <div class="contact-method">
                            <div class="contact-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="contact-details">
                                <h3>Phone Support</h3>
                                <p>(555) 123-4567</p>
                                <p>Mon-Fri: 8:00 AM - 6:00 PM</p>
                            </div>
                        </div>

                        <div class="contact-method">
                            <div class="contact-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="contact-details">
                                <h3>Office Location</h3>
                                <p>Information Technology Services</p>
                                <p>Administration Building, Room 204</p>
                            </div>
                        </div>

                        <div class="contact-method">
                            <div class="contact-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <div class="contact-details">
                                <h3>Live Chat</h3>
                                <p>Available during business hours</p>
                                <p>Click the chat icon in bottom right</p>
                            </div>
                        </div>

                        <div class="hours-section">
                            <h3 class="hours-title">Support Hours</h3>
                            <ul class="hours-list">
                                <li>
                                    <span>Monday - Friday</span>
                                    <span>8:00 AM - 6:00 PM</span>
                                </li>
                                <li>
                                    <span>Saturday</span>
                                    <span>10:00 AM - 2:00 PM</span>
                                </li>
                                <li>
                                    <span>Sunday</span>
                                    <span>Closed</span>
                                </li>
                                <li>
                                    <span>Election Days</span>
                                    <span>24/7 Support</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Emergency Support Section -->
                <div class="emergency-section">
                    <h2 class="emergency-title">Emergency Support</h2>
                    <p>For critical system issues during active voting periods:</p>
                    <div class="emergency-number">(555) 123-VOTE (8683)</div>
                    <p>Available 24/7 during elections</p>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Mobile navigation functions
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');

        // Toggle mobile menu
        hamburgerBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            hamburgerBtn.classList.toggle('active');
        });

        // Close menu when overlay is clicked
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            hamburgerBtn.classList.remove('active');
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                hamburgerBtn.classList.remove('active');
            }
        });

        // User dropdown toggle
        function toggleUserDropdown() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggleButton = document.querySelector('.hamburger');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !toggleButton.contains(event.target)) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                hamburgerBtn.classList.remove('active');
            }
        });

        // Form validation and enhancement
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const submitButton = document.querySelector('.form-button');

            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });

            // Form submission handling
            form.addEventListener('submit', function(e) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            });

            // Character counter for message textarea
            const messageTextarea = document.getElementById('message');
            const charCounter = document.createElement('div');
            charCounter.style.cssText = 'text-align: right; font-size: 0.875rem; color: var(--text-muted); margin-top: 0.5rem;';
            messageTextarea.parentNode.appendChild(charCounter);

            function updateCharCounter() {
                const length = messageTextarea.value.length;
                charCounter.textContent = `${length}/1000 characters`;
                if (length > 900) {
                    charCounter.style.color = 'var(--warning)';
                } else {
                    charCounter.style.color = 'var(--text-muted)';
                }
            }

            messageTextarea.addEventListener('input', updateCharCounter);
            updateCharCounter();

            // Add keyboard navigation support
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const sidebar = document.getElementById('sidebar');
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                    hamburgerBtn.classList.remove('active');
                }
            });

            console.log('Contact page initialized successfully');
        });
    </script>
</body>
</html>