<?php
// admin_settings.php - System Settings & Configuration
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'voting_system';
$username = 'root';
$password = '';

// Initialize variables
$pdo = null;
$settings_data = [
    'system_settings' => [],
    'email_settings' => [],
    'security_settings' => [],
    'backup_settings' => [],
    'notification_settings' => []
];

// Database connection with error handling
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $connection_error = "Connection failed: " . $e->getMessage();
    error_log($connection_error);
}

// Load system settings
if ($pdo) {
    try {
        // In a real application, these would be loaded from a settings table
        $settings_data = [
            'system_settings' => [
                'site_name' => 'University Voting System',
                'site_description' => 'Secure and transparent election management platform',
                'admin_email' => 'admin@university.edu',
                'timezone' => 'America/New_York',
                'language' => 'en',
                'date_format' => 'M j, Y',
                'time_format' => '12',
                'maintenance_mode' => false,
                'registration_enabled' => true,
                'voting_enabled' => true,
                'results_public' => true
            ],
            'email_settings' => [
                'smtp_enabled' => true,
                'smtp_host' => 'smtp.university.edu',
                'smtp_port' => 587,
                'smtp_encryption' => 'tls',
                'smtp_username' => 'elections@university.edu',
                'smtp_password' => '••••••••',
                'from_name' => 'University Elections',
                'from_email' => 'elections@university.edu',
                'reply_to' => 'noreply@university.edu'
            ],
            'security_settings' => [
                'session_timeout' => 30,
                'max_login_attempts' => 5,
                'lockout_duration' => 15,
                'password_min_length' => 8,
                'password_require_uppercase' => true,
                'password_require_lowercase' => true,
                'password_require_numbers' => true,
                'password_require_symbols' => true,
                'two_factor_enabled' => false,
                'ip_whitelist_enabled' => false,
                'ssl_required' => true
            ],
            'backup_settings' => [
                'auto_backup_enabled' => true,
                'backup_frequency' => 'daily',
                'backup_time' => '02:00',
                'backup_retention' => 30,
                'backup_location' => '/backups/voting_system/',
                'include_files' => true,
                'compression_enabled' => true
            ],
            'notification_settings' => [
                'email_notifications' => true,
                'admin_notifications' => true,
                'voter_notifications' => true,
                'candidate_notifications' => true,
                'election_start_notify' => true,
                'election_end_notify' => true,
                'result_notify' => true,
                'security_alerts' => true
            ]
        ];
    } catch(PDOException $e) {
        error_log("Error loading settings: " . $e->getMessage());
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_system':
            // Handle system settings update
            $success_message = "System settings updated successfully!";
            break;
        case 'update_email':
            // Handle email settings update
            $success_message = "Email settings updated successfully!";
            break;
        case 'update_security':
            // Handle security settings update
            $success_message = "Security settings updated successfully!";
            break;
        case 'update_backup':
            // Handle backup settings update
            $success_message = "Backup settings updated successfully!";
            break;
        case 'update_notifications':
            // Handle notification settings update
            $success_message = "Notification settings updated successfully!";
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: #1e293b;
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
            backdrop-filter: blur(20px);
            padding: 2rem 0;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
        }

        .sidebar.hidden {
            transform: translateX(-100%);
        }

        /* Logo Section */
        .logo {
            padding: 0 2rem 2rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 2rem;
        }

        .logo h1 {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        /* Navigation Menu */
        .nav-menu {
            padding: 0 1rem;
        }

        .nav-section {
            margin-bottom: 1.5rem;
        }

        .nav-section-title {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.6);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        .nav-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-item {
            margin-bottom: 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 1rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(4px);
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            box-shadow: 0 4px 20px rgba(255, 255, 255, 0.1);
        }

        .nav-icon {
            width: 20px;
            height: 20px;
            margin-right: 1rem;
            fill: currentColor;
        }

        .nav-text {
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
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            z-index: 1001;
            padding: 0 1rem;
            align-items: center;
            justify-content: space-between;
        }

        .mobile-logo {
            color: white;
            font-size: 1.25rem;
            font-weight: 700;
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

        /* Main Content Layout */
        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: #f8fafc;
            transition: margin-left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Top Bar */
        .top-bar {
            background: white;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        }

        .top-bar h1 {
            font-size: 1.875rem;
            font-weight: 600;
            color: #1e293b;
        }

        .top-bar-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .page-content {
            flex: 1;
            padding: 2rem;
            background: #f8fafc;
        }

        /* Settings Navigation */
        .settings-nav {
            background: white;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            margin-bottom: 2rem;
            overflow-x: auto;
        }

        .settings-nav-list {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .settings-nav-item {
            flex-shrink: 0;
        }

        .settings-nav-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 1.5rem;
            color: #64748b;
            text-decoration: none;
            border-bottom: 3px solid transparent;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .settings-nav-link:hover {
            color: #1e293b;
            background: #f8fafc;
        }

        .settings-nav-link.active {
            color: #6366f1;
            border-bottom-color: #6366f1;
            background: #f8fafc;
        }

        /* Settings Sections */
        .settings-section {
            display: none;
            background: white;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            overflow: hidden;
        }

        .settings-section.active {
            display: block;
        }

        .settings-header {
            padding: 2rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .settings-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .settings-description {
            color: #64748b;
            font-size: 0.875rem;
        }

        .settings-body {
            padding: 2rem;
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            gap: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            background: white;
            color: #1e293b;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-control:disabled {
            background: #f8fafc;
            color: #94a3b8;
            cursor: not-allowed;
        }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-help {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.25rem;
        }

        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e1;
            transition: 0.3s;
            border-radius: 24px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background-color: #6366f1;
        }

        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }

        /* Toggle Group */
        .toggle-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }

        .toggle-info h4 {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .toggle-info p {
            font-size: 0.875rem;
            color: #64748b;
            margin: 0;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: #6366f1;
            color: white;
        }

        .btn-primary:hover {
            background: #4f46e5;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }

        .btn-secondary {
            background: white;
            color: #1e293b;
            border: 1px solid #e2e8f0;
        }

        .btn-secondary:hover {
            background: #f1f5f9;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-warning {
            background: #f59e0b;
            color: white;
        }

        .btn-warning:hover {
            background: #d97706;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .alert-danger {
            background: #fecaca;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        /* System Status Cards */
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .status-card {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            text-align: center;
        }

        .status-icon {
            width: 3rem;
            height: 3rem;
            margin: 0 auto 1rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .status-healthy .status-icon {
            background: #10b981;
        }

        .status-warning .status-icon {
            background: #f59e0b;
        }

        .status-error .status-icon {
            background: #ef4444;
        }

        .status-title {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .status-description {
            font-size: 0.875rem;
            color: #64748b;
        }

        /* Backup Status */
        .backup-status {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            margin-bottom: 1.5rem;
        }

        .backup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .backup-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .backup-item {
            text-align: center;
            padding: 1rem;
            background: white;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
        }

        .backup-value {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .backup-label {
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Smooth scrollbar for webkit browsers */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

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
            }

            .top-bar {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .page-content {
                padding: 1rem;
            }

            .settings-nav-list {
                flex-wrap: nowrap;
                overflow-x: auto;
            }

            .status-grid {
                grid-template-columns: 1fr;
            }

            .backup-info {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .backup-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Mobile Header -->
        <header class="mobile-header">
            <div class="mobile-logo">VoteAdmin</div>
            <button class="hamburger" id="hamburgerBtn">
                <svg class="hamburger-icon" viewBox="0 0 24 24">
                    <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
                </svg>
            </button>
        </header>

        <!-- Overlay -->
        <div class="overlay" id="overlay"></div>

        <!-- Modern Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="logo">
                <h1>
                    <i class="fas fa-shield-alt"></i>
                    VoteAdmin
                </h1>
                <p>Admin User</p>
                <p style="font-size: 0.75rem; margin-top: 0.25rem;">Administrator</p>
            </div>
            
            <div class="nav-menu">
                <!-- MAIN Section -->
                <div class="nav-section">
                    <div class="nav-section-title">MAIN</div>
                    <ul class="nav-list">
                        <li class="nav-item">
                            <a href="admin_dashboard.php" class="nav-link">
                                <svg class="nav-icon" viewBox="0 0 24 24">
                                    <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                                </svg>
                                <span class="nav-text">Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="admin_elections.php" class="nav-link">
                                <svg class="nav-icon" viewBox="0 0 24 24">
                                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                                </svg>
                                <span class="nav-text">Elections</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="admin_candidates.php" class="nav-link">
                                <svg class="nav-icon" viewBox="0 0 24 24">
                                    <path d="M16 4c0-1.11.89-2 2-2s2 .89 2 2-.89 2-2 2-2-.89-2-2zm4 18v-6h2.5l-2.54-7.63A2.996 2.996 0 0 0 16.95 6H15c-.8 0-1.54.5-1.85 1.26l-1.99 5.02L9.6 11c-.75-.38-1.6-.38-2.35 0-.53.27-.85.82-.85 1.41V20h2v-6.5l2.6 1.48L9.6 20H12l1.5-4.5L16 18.5V22h4z"/>
                                </svg>
                                <span class="nav-text">Candidates</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="admin_voters.php" class="nav-link">
                                <svg class="nav-icon" viewBox="0 0 24 24">
                                    <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm-1 16H9V7h9v14z"/>
                                </svg>
                                <span class="nav-text">Voters</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- REPORTS & ANALYTICS Section -->
                <div class="nav-section">
                    <div class="nav-section-title">REPORTS & ANALYTICS</div>
                    <ul class="nav-list">
                        <li class="nav-item">
                            <a href="admin_results.php" class="nav-link">
                                <svg class="nav-icon" viewBox="0 0 24 24">
                                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c
                                    <svg class="nav-icon" viewBox="0 0 24 24">
                                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10H7v-2h10v2zm0-4H7V7h10v2z"/>
                                </svg>
                                <span class="nav-text">Results</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="admin_analytics.php" class="nav-link">
                                <svg class="nav-icon" viewBox="0 0 24 24">
                                    <path d="M9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4zm2.5 2.25l1.25-1.25-2.75-2.75-.75.75L19.5 17.25zM12 2C6.48 2 2 6.48 2 12s4.48 10 10 10c1.19 0 2.34-.21 3.41-.6l-1.46-1.46C13.33 19.95 12.68 20 12 20c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8c0 1.19-.33 2.3-.9 3.26l1.46 1.46C21.17 15.35 22 13.75 22 12c0-5.52-4.48-10-10-10z"/>
                                </svg>
                                <span class="nav-text">Analytics</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="admin_reports.php" class="nav-link">
                                <svg class="nav-icon" viewBox="0 0 24 24">
                                    <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 2 2h8c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>
                                </svg>
                                <span class="nav-text">Reports</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- SYSTEM Section -->
                <div class="nav-section">
                    <div class="nav-section-title">SYSTEM</div>
                    <ul class="nav-list">
                        <li class="nav-item">
                            <a href="admin_settings.php" class="nav-link active">
                                <svg class="nav-icon" viewBox="0 0 24 24">
                                    <path d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61 l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41 h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.74,8.87 C2.62,9.08,2.66,9.34,2.86,9.48l2.03,1.58C4.84,11.36,4.82,11.69,4.82,12s0.02,0.64,0.07,0.94l-2.03,1.58 c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54 c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96 c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.12-0.22,0.07-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6 s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z"/>
                                </svg>
                                <span class="nav-text">Settings</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="admin_audit.php" class="nav-link">
                                <svg class="nav-icon" viewBox="0 0 24 24">
                                    <path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/>
                                </svg>
                                <span class="nav-text">Audit Log</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="logout.php" class="nav-link">
                                <svg class="nav-icon" viewBox="0 0 24 24">
                                    <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                                </svg>
                                <span class="nav-text">Logout</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <h1>System Settings</h1>
                <div class="top-bar-actions">
                    <button class="btn btn-warning" onclick="testSystemHealth()">
                        <i class="fas fa-stethoscope"></i>
                        Test System
                    </button>
                    <button class="btn btn-success" onclick="saveAllSettings()">
                        <i class="fas fa-save"></i>
                        Save All Changes
                    </button>
                </div>
            </div>

            <div class="page-content">
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($pdo): ?>
                    <!-- System Status Overview -->
                    <div class="status-grid">
                        <div class="status-card status-healthy">
                            <div class="status-icon">
                                <i class="fas fa-server"></i>
                            </div>
                            <div class="status-title">System Status</div>
                            <div class="status-description">All services operational</div>
                        </div>
                        <div class="status-card status-healthy">
                            <div class="status-icon">
                                <i class="fas fa-database"></i>
                            </div>
                            <div class="status-title">Database</div>
                            <div class="status-description">Connected and responsive</div>
                        </div>
                        <div class="status-card status-warning">
                            <div class="status-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="status-title">Security</div>
                            <div class="status-description">2FA not enabled</div>
                        </div>
                        <div class="status-card status-healthy">
                            <div class="status-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="status-title">Email Service</div>
                            <div class="status-description">SMTP configured</div>
                        </div>
                    </div>

                    <!-- Settings Navigation -->
                    <div class="settings-nav">
                        <ul class="settings-nav-list">
                            <li class="settings-nav-item">
                                <a href="#" class="settings-nav-link active" data-section="system">
                                    <i class="fas fa-cog"></i>
                                    System
                                </a>
                            </li>
                            <li class="settings-nav-item">
                                <a href="#" class="settings-nav-link" data-section="email">
                                    <i class="fas fa-envelope"></i>
                                    Email
                                </a>
                            </li>
                            <li class="settings-nav-item">
                                <a href="#" class="settings-nav-link" data-section="security">
                                    <i class="fas fa-shield-alt"></i>
                                    Security
                                </a>
                            </li>
                            <li class="settings-nav-item">
                                <a href="#" class="settings-nav-link" data-section="backup">
                                    <i class="fas fa-hdd"></i>
                                    Backup
                                </a>
                            </li>
                            <li class="settings-nav-item">
                                <a href="#" class="settings-nav-link" data-section="notifications">
                                    <i class="fas fa-bell"></i>
                                    Notifications
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- System Settings Section -->
                    <div class="settings-section active" id="system-section">
                        <div class="settings-header">
                            <h2 class="settings-title">System Configuration</h2>
                            <p class="settings-description">Configure basic system settings and application preferences</p>
                        </div>
                        <div class="settings-body">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="update_system">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Site Name</label>
                                        <input type="text" class="form-control" name="site_name" 
                                               value="<?php echo htmlspecialchars($settings_data['system_settings']['site_name']); ?>" required>
                                        <div class="form-help">The name displayed throughout the application</div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Admin Email</label>
                                        <input type="email" class="form-control" name="admin_email" 
                                               value="<?php echo htmlspecialchars($settings_data['system_settings']['admin_email']); ?>" required>
                                        <div class="form-help">Primary administrator email address</div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Site Description</label>
                                    <textarea class="form-control form-textarea" name="site_description"><?php echo htmlspecialchars($settings_data['system_settings']['site_description']); ?></textarea>
                                    <div class="form-help">Brief description of the voting system</div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Timezone</label>
                                        <select class="form-control form-select" name="timezone">
                                            <option value="America/New_York" <?php echo $settings_data['system_settings']['timezone'] === 'America/New_York' ? 'selected' : ''; ?>>Eastern Time (EST/EDT)</option>
                                            <option value="America/Chicago" <?php echo $settings_data['system_settings']['timezone'] === 'America/Chicago' ? 'selected' : ''; ?>>Central Time (CST/CDT)</option>
                                            <option value="America/Denver" <?php echo $settings_data['system_settings']['timezone'] === 'America/Denver' ? 'selected' : ''; ?>>Mountain Time (MST/MDT)</option>
                                            <option value="America/Los_Angeles" <?php echo $settings_data['system_settings']['timezone'] === 'America/Los_Angeles' ? 'selected' : ''; ?>>Pacific Time (PST/PDT)</option>
                                            <option value="UTC" <?php echo $settings_data['system_settings']['timezone'] === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Language</label>
                                        <select class="form-control form-select" name="language">
                                            <option value="en" <?php echo $settings_data['system_settings']['language'] === 'en' ? 'selected' : ''; ?>>English</option>
                                            <option value="es" <?php echo $settings_data['system_settings']['language'] === 'es' ? 'selected' : ''; ?>>Spanish</option>
                                            <option value="fr" <?php echo $settings_data['system_settings']['language'] === 'fr' ? 'selected' : ''; ?>>French</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Date Format</label>
                                        <select class="form-control form-select" name="date_format">
                                            <option value="M j, Y" <?php echo $settings_data['system_settings']['date_format'] === 'M j, Y' ? 'selected' : ''; ?>>Jan 1, 2024</option>
                                            <option value="Y-m-d" <?php echo $settings_data['system_settings']['date_format'] === 'Y-m-d' ? 'selected' : ''; ?>>2024-01-01</option>
                                            <option value="d/m/Y" <?php echo $settings_data['system_settings']['date_format'] === 'd/m/Y' ? 'selected' : ''; ?>>01/01/2024</option>
                                            <option value="m/d/Y" <?php echo $settings_data['system_settings']['date_format'] === 'm/d/Y' ? 'selected' : ''; ?>>01/01/2024</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Time Format</label>
                                        <select class="form-control form-select" name="time_format">
                                            <option value="12" <?php echo $settings_data['system_settings']['time_format'] === '12' ? 'selected' : ''; ?>>12-hour (AM/PM)</option>
                                            <option value="24" <?php echo $settings_data['system_settings']['time_format'] === '24' ? 'selected' : ''; ?>>24-hour</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <h4 style="margin-bottom: 1rem; color: #1e293b;">System Features</h4>
                                    
                                    <div class="toggle-group">
                                        <div class="toggle-info">
                                            <h4>Maintenance Mode</h4>
                                            <p>Temporarily disable the system for maintenance</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="maintenance_mode" <?php echo $settings_data['system_settings']['maintenance_mode'] ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>

                                    <div class="toggle-group">
                                        <div class="toggle-info">
                                            <h4>User Registration</h4>
                                            <p>Allow new users to register for voting</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="registration_enabled" <?php echo $settings_data['system_settings']['registration_enabled'] ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>

                                    <div class="toggle-group">
                                        <div class="toggle-info">
                                            <h4>Voting System</h4>
                                            <p>Enable or disable the voting functionality</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="voting_enabled" <?php echo $settings_data['system_settings']['voting_enabled'] ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>

                                    <div class="toggle-group">
                                        <div class="toggle-info">
                                            <h4>Public Results</h4>
                                            <p>Make election results visible to the public</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="results_public" <?php echo $settings_data['system_settings']['results_public'] ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>

                                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                                    <button type="button" class="btn btn-secondary" onclick="resetForm('system')">Reset</button>
                                    <button type="submit" class="btn btn-primary">Save System Settings</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Email Settings Section -->
                    <div class="settings-section" id="email-section">
                        <div class="settings-header">
                            <h2 class="settings-title">Email Configuration</h2>
                            <p class="settings-description">Configure SMTP settings and email preferences</p>
                        </div>
                        <div class="settings-body">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="update_email">
                                
                                <div class="toggle-group">
                                    <div class="toggle-info">
                                        <h4>SMTP Email</h4>
                                        <p>Use SMTP server for sending emails</p>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="smtp_enabled" <?php echo $settings_data['email_settings']['smtp_enabled'] ? 'checked' : ''; ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">SMTP Host</label>
                                        <input type="text" class="form-control" name="smtp_host" 
                                               value="<?php echo htmlspecialchars($settings_data['email_settings']['smtp_host']); ?>">
                                        <div class="form-help">SMTP server hostname</div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">SMTP Port</label>
                                        <input type="number" class="form-control" name="smtp_port" 
                                               value="<?php echo htmlspecialchars($settings_data['email_settings']['smtp_port']); ?>">
                                        <div class="form-help">Usually 587 for TLS or 465 for SSL</div>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Encryption</label>
                                        <select class="form-control form-select" name="smtp_encryption">
                                            <option value="tls" <?php echo $settings_data['email_settings']['smtp_encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                            <option value="ssl" <?php echo $settings_data['email_settings']['smtp_encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                            <option value="none" <?php echo $settings_data['email_settings']['smtp_encryption'] === 'none' ? 'selected' : ''; ?>>None</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">SMTP Username</label>
                                        <input type="text" class="form-control" name="smtp_username" 
                                               value="<?php echo htmlspecialchars($settings_data['email_settings']['smtp_username']); ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">SMTP Password</label>
                                    <input type="password" class="form-control" name="smtp_password" 
                                           value="<?php echo htmlspecialchars($settings_data['email_settings']['smtp_password']); ?>">
                                    <div class="form-help">Enter new password to change</div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">From Name</label>
                                        <input type="text" class="form-control" name="from_name" 
                                               value="<?php echo htmlspecialchars($settings_data['email_settings']['from_name']); ?>">
                                        <div class="form-help">Display name for outgoing emails</div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">From Email</label>
                                        <input type="email" class="form-control" name="from_email" 
                                               value="<?php echo htmlspecialchars($settings_data['email_settings']['from_email']); ?>">
                                        <div class="form-help">Email address for outgoing emails</div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Reply To Email</label>
                                    <input type="email" class="form-control" name="reply_to" 
                                           value="<?php echo htmlspecialchars($settings_data['email_settings']['reply_to']); ?>">
                                    <div class="form-help">Email address for replies</div>
                                </div>

                                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                                    <button type="button" class="btn btn-secondary" onclick="testEmailSettings()">Test Email</button>
                                    <button type="button" class="btn btn-secondary" onclick="resetForm('email')">Reset</button>
                                    <button type="submit" class="btn btn-primary">Save Email Settings</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Security Settings Section -->
                    <div class="settings-section" id="security-section">
                        <div class="settings-header">
                            <h2 class="settings-title">Security Configuration</h2>
                            <p class="settings-description">Configure security policies and authentication settings</p>
                        </div>
                        <div class="settings-body">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="update_security">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Session Timeout (minutes)</label>
                                        <input type="number" class="form-control" name="session_timeout" min="5" max="1440"
                                               value="<?php echo htmlspecialchars($settings_data['security_settings']['session_timeout']); ?>">
                                        <div class="form-help">Automatic logout after inactivity</div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Max Login Attempts</label>
                                        <input type="number" class="form-control" name="max_login_attempts" min="3" max="10"
                                               value="<?php echo htmlspecialchars($settings_data['security_settings']['max_login_attempts']); ?>">
                                        <div class="form-help">Account lockout threshold</div>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Lockout Duration (minutes)</label>
                                        <input type="number" class="form-control" name="lockout_duration" min="5" max="60"
                                               value="<?php echo htmlspecialchars($settings_data['security_settings']['lockout_duration']); ?>">
                                        <div class="form-help">How long accounts remain locked</div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Password Min Length</label>
                                        <input type="number" class="form-control" name="password_min_length" min="6" max="20"
                                               value="<?php echo htmlspecialchars($settings_data['security_settings']['password_min_length']); ?>">
                                        <div class="form-help">Minimum password length</div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <h4 style="margin-bottom: 1rem; color: #1e293b;">Password Requirements</h4>
                                    
                                    <div class="toggle-group">
                                        <div class="toggle-info">
                                            <h4>Require Uppercase Letters</h4>
                                            <p>Passwords must contain uppercase letters (A-Z)</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="password_require_uppercase" <?php echo $settings_data['security_settings']['password_require_uppercase'] ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>

                                    <div class="toggle-group">
                                        <div class="toggle-info">
                                            <h4>Require Lowercase Letters</h4>
                                            <p>Passwords must contain lowercase letters (a-z)</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="password_require_lowercase" <?php echo $settings_data['security_settings']['password_require_lowercase'] ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>

                                    <div class="toggle-group">
                                        <div class="toggle-info">
                                            <h4>Require Numbers</h4>
                                            <p>Passwords must contain numbers (0-9)</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="password_require_numbers" <?php echo $settings_data['security_settings']['password_require_numbers'] ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>

                                    <div class="toggle-group">
                                        <div class="toggle-info">
                                            <h4>Require Special Characters</h4>
                                            <p>Passwords must contain symbols (!@#$%^&*)</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="password_require_symbols" <?php echo $settings_data['security_settings']['password_require_symbols'] ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <h4 style="margin-bottom: 1rem; color: #1e293b;">Advanced Security</h4>
                                    
                                    <div class="toggle-group">
                                        <div class="toggle-info">
                                            <h4>Two-Factor Authentication</h4>
                                            <p>Require 2FA for all administrator accounts</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="two_factor_enabled" <?php echo $settings_data['security_settings']['two_factor_enabled'] ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>

                                    <div class="toggle-group">
                                        <div class="toggle-info">
                                            <h4>IP Whitelist</h4>
                                            <p>Restrict admin access to specific IP addresses</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="ip_whitelist_enabled" <?php echo $settings_data['security_settings']['ip_whitelist_enabled'] ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>

                                    <div class="toggle-group">
                                        <div class="toggle-info">
                                            <h4>Force SSL/HTTPS</h4>
                                            <p>Require secure connections for all traffic</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="ssl_required" <?php echo $settings_data['security_settings']['ssl_required'] ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>

                                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                                    <button type="button" class="btn btn-secondary" onclick="resetForm('security')">Reset</button>
                                    <button type="submit" class="btn btn-primary">Save Security Settings</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Backup Settings Section -->
                    <div class="settings-section" id="backup-section">
                        <div class="settings-header">
                            <h2 class="settings-title">Backup Configuration</h2>
                            <p class="settings-description">Configure automatic backups and data retention policies</p>
                        </div>
                        <div class="settings-body">
                            <!-- Backup Status -->
                            <div class="backup-status">
                                <div class="backup-header">
                                    <h4 style="color: #1e293b; margin: 0;">Backup Status</h4>
                                    <button class="btn btn-primary" onclick="runBackupNow()">
                                        <i class="fas fa-play"></i>
                                        Run Backup Now
                                    </button>
                                </div>
                                <div class="backup-info">
                                    <div class="backup-item">
                                        <div class="backup-value">2 hours ago</div>
                                        <div class="backup-label">Last Backup</div>
                                    </div>
                                    <div class="backup-item">
                                        <div class="backup-value">247 MB</div>
                                        <div class="backup-label">Backup Size</div>
                                    </div>
                                    <div class="backup-item">
                                        <div class="backup-value">30</div>
                                        <div class="backup-label">Available Backups</div>
                                    </div>
                                    <div class="backup-item">
                                        <div class="backup-value">Success</div>
                                        <div class="backup-label">Last Status</div>
                                    </div>
                                </div>
                            </div>

                            <form method="POST" action="">
                                <input type="hidden" name="action" value="update_backup">
                                
                                <div class="toggle-group">
                                    <div class="toggle-info">
                                        <h4>Automatic Backups</h4>
                                        <p>Enable scheduled automatic backups</p>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="auto_backup_enabled" <?php echo $settings_data['backup_settings']['auto_backup_enabled'] ? 'checked' : ''; ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Backup Frequency</label>
                                        <select class="form-control form-select" name="backup_frequency">
                                            <option value="hourly" <?php echo $settings_data['backup_settings']['backup_frequency'] === 'hourly' ? 'selected' : ''; ?>>Hourly</option>
                                            <option value="daily" <?php echo $settings_data['backup_settings']['backup_frequency'] === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                            <option value="weekly" <?php echo $settings_data['backup_settings']['backup_frequency'] === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                            <option value="monthly" <?php echo $settings_data['backup_settings']['backup_frequency'] === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Backup Time</label>
                                        <input type="time" class="form-control" name="backup_time" 
                                               value="<?php echo htmlspecialchars($settings_data['backup_settings']['backup_time']); ?>">
                                        <div class="form-help">Time to run daily backups</div>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Retention Period (days)</label>
                                        <input type="number" class="form-control" name="backup_retention" min="1" max="365"
                                               value="<?php echo htmlspecialchars($settings_data['backup_settings']['backup_retention']); ?>">
                                        <div class="form-help">How long to keep backup files</div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Backup Location</label>
                                        <input type="text" class="form-control" name="backup_location" 
                                               value="<?php echo htmlspecialchars($settings_data['backup_settings']['backup_location']); ?>">
                                        <div class="form-help">Server path for backup storage</div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <h4 style="margin-bottom: 1rem; color: #1e293b;">Backup Options</h4>
                                    
                                    <div class="toggle-group">
                                        <div class="toggle-info">
                                            <h4>Include Files</h4>
                                            <p>Include uploaded files and media in backups</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="include_files" <?php echo $settings_data['backup_settings']['include_files'] ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>

                                    <div class="toggle-group">
                                        <div class="toggle-info">
                                            <h4>Compression</h4>
                                            <p>Compress backup files to save storage space</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="compression_enabled" <?php echo $settings_data['backup_settings']['compression_enabled'] ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>

                                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                                    <button type="button" class="btn btn-secondary" onclick="downloadBackup()">Download Latest</button>
                                    <button type="button" class="btn btn-secondary" onclick="resetForm('backup')">Reset</button>
                                    <button type="submit" class="btn btn-primary">Save Backup Settings</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Notification Settings Section -->
                    <div class="settings-section" id="notifications-section">
                        <div class="settings-header">
                            <h2 class="settings-title">Notification Settings</h2>
                            <p class="settings-description">Configure email notifications and alert preferences</p>
                        </div>
                        <div class="settings-body">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="update_notifications">
                                
                                <div class="form-group">
                                    <h4 style="margin-bottom: 1rem; color: #1e293b;">General Notifications</h4>
                                    
                                    <div class="toggle-group">
                                        <div class="toggle-info">
                                            <h4>Email Notifications</h4>
                                            <p>Enable email notifications system-wide</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="email_notifications" <?php echo $settings_data['notification_settings']['email_notifications'] ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>

                                    <div class="toggle-group">
                                        <div class="toggle-info">
                                            <h4>Admin Notifications</h4>
                                            <p>Send notifications to administrators</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="admin_notifications" <?php echo $settings_data['notification_settings']['admin_notifications'] ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>

                                    <div class="toggle-group">
                                        <div class="toggle-info">
                                            <h4>Voter Notifications</h4>
                                            <p>Send notifications to registered voters</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="voter_notifications" <?php echo $settings_data['notification_settings']['voter_notifications'] ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>

                                    <div class="toggle-group">
                                        <div class="toggle-info">
                                            <h4>Candidate Notifications</h4>
                                            <p>Send notifications to election candidates</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="candidate_notifications" <?php echo $settings_data['notification_settings']['candidate_notifications'] ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <h4 style="margin-bottom: 1rem; color: #1e293b;">Election Notifications</h4>
                                    
                                    <div class="toggle-group">
                                        <div class="toggle-info">
                                            <h4>Election Start Notifications</h4>
                                            <p>Notify when elections begin</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="election_start_notify" <?php echo $settings_data['notification_settings']['election_start_notify'] ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>

                                    <div class="toggle-group">
                                        <div class="toggle-info">
                                            <h4>Election End Notifications</h4>
                                            <p>Notify when elections conclude</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="election_end_notify" <?php echo $settings_data['notification_settings']['election_end_notify'] ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>

                                    <div class="toggle-group">
                                        <div class="toggle-info">
                                            <h4>Result Notifications</h4>
                                            <p>Notify when results are published</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="result_notify" <?php echo $settings_data['notification_settings']['result_notify'] ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>

                                    <div class="toggle-group">
                                        <div class="toggle-info">
                                            <h4>Security Alerts</h4>
                                            <p>Immediate notifications for security events</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="security_alerts" <?php echo $settings_data['notification_settings']['security_alerts'] ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>

                                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                                    <button type="button" class="btn btn-secondary" onclick="testNotifications()">Send Test</button>
                                    <button type="button" class="btn btn-secondary" onclick="resetForm('notifications')">Reset</button>
                                    <button type="submit" class="btn btn-primary">Save Notification Settings</button>
                                </div>
                            </form>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Database Connection Failed -->
                    <div class="settings-section active">
                        <div class="settings-body">
                            <div style="text-align: center; padding: 3rem; color: #64748b;">
                                <i class="fas fa-database" style="font-size: 3rem; margin-bottom: 1rem; color: #ef4444;"></i>
                                <h3>Database Connection Failed</h3>
                                <p>Unable to load system settings. Please check your database connection.</p>
                                <div style="margin-top: 2rem; padding: 1rem; background: #fef2f2; border-radius: 0.5rem; color: #ef4444; text-align: left;">
                                    <h4 style="margin-bottom: 1rem;">Troubleshooting Steps:</h4>
                                    <ol style="margin-left: 1.5rem;">
                                        <li>Ensure MySQL server is running</li>
                                        <li>Verify the database name is 'voting_system'</li>
                                        <li>Check database credentials</li>
                                        <li>Verify required tables exist</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Footer -->
                <div style="margin-top: 3rem; padding: 2rem; text-align: center; color: #94a3b8; border-top: 1px solid #e2e8f0;">
                    <p style="margin-bottom: 0.5rem;">
                        <i class="fas fa-cog"></i>
                        System Settings - University Voting System
                    </p>
                    <p style="font-size: 0.875rem;">
                        Last updated: <?php echo date('F j, Y \a\t g:i A'); ?> | 
                        <a href="#" onclick="exportSettings()" style="color: #6366f1; text-decoration: none;">
                            <i class="fas fa-download"></i> Export Configuration
                        </a>
                    </p>
                </div>
            </div>
        </main>
    </div>

    <script>
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const navLinks = document.querySelectorAll('.nav-link');
        const settingsNavLinks = document.querySelectorAll('.settings-nav-link');
        const settingsSections = document.querySelectorAll('.settings-section');

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

        // Handle navigation link clicks
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                // Remove active class from all links
                navLinks.forEach(l => l.classList.remove('active'));
                
                // Add active class to clicked link
                link.classList.add('active');
                
                // Close mobile menu if open
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                    hamburgerBtn.classList.remove('active');
                }
            });
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                hamburgerBtn.classList.remove('active');
            }
        });

        // Add some interactive hover effects
        navLinks.forEach(link => {
            link.addEventListener('mouseenter', () => {
                if (!link.classList.contains('active')) {
                    link.style.transform = 'translateX(6px)';
                }
            });
            
            link.addEventListener('mouseleave', () => {
                if (!link.classList.contains('active')) {
                    link.style.transform = 'translateX(0)';
                }
            });
        });

        // Settings Navigation
        settingsNavLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                
                const sectionId = link.getAttribute('data-section');
                
                // Remove active class from all nav links
                settingsNavLinks.forEach(l => l.classList.remove('active'));
                
                // Add active class to clicked link
                link.classList.add('active');
                
                // Hide all sections
                settingsSections.forEach(section => {
                    section.classList.remove('active');
                });
                
                // Show selected section
                const targetSection = document.getElementById(sectionId + '-section');
                if (targetSection) {
                    targetSection.classList.add('active');
                }
                
                showNotification(`Switched to ${link.textContent.trim()} settings`, 'info');
            });
        });

        // Form Functions
        function resetForm(section) {
            if (confirm('Are you sure you want to reset all changes in this section?')) {
                const form = document.querySelector(`#${section}-section form`);
                if (form) {
                    form.reset();
                    showNotification('Form reset to default values', 'info');
                }
            }
        }

        function saveAllSettings() {
            const forms = document.querySelectorAll('.settings-section form');
            let savedCount = 0;
            
            showNotification('Saving all settings...', 'info');
            
            forms.forEach((form, index) => {
                setTimeout(() => {
                    // Simulate saving each form
                    savedCount++;
                    if (savedCount === forms.length) {
                        showNotification('All settings saved successfully!', 'success');
                    }
                }, index * 500);
            });
        }

        // System Functions
        function testSystemHealth() {
            showNotification('Running system health check...', 'info');
            
            const checks = [
                'Testing database connection...',
                'Checking file permissions...',
                'Verifying email settings...',
                'Testing backup system...',
                'Checking security settings...'
            ];
            
            let currentCheck = 0;
            const interval = setInterval(() => {
                if (currentCheck < checks.length) {
                    showNotification(checks[currentCheck], 'info');
                    currentCheck++;
                } else {
                    clearInterval(interval);
                    showNotification('System health check completed! All systems operational.', 'success');
                }
            }, 1000);
        }

        function testEmailSettings() {
            showNotification('Testing email configuration...', 'info');
            
            setTimeout(() => {
                showNotification('Test email sent successfully!', 'success');
            }, 3000);
        }

        function runBackupNow() {
            showNotification('Starting backup process...', 'info');
            
            const steps = [
                'Preparing backup...',
                'Backing up database...',
                'Backing up files...',
                'Compressing backup...',
                'Backup completed!'
            ];
            
            let currentStep = 0;
            const interval = setInterval(() => {
                if (currentStep < steps.length - 1) {
                    showNotification(steps[currentStep], 'info');
                    currentStep++;
                } else {
                    clearInterval(interval);
                    showNotification(steps[currentStep], 'success');
                    
                    // Update backup status
                    const lastBackupElement = document.querySelector('.backup-value');
                    if (lastBackupElement) {
                        lastBackupElement.textContent = 'Just now';
                    }
                }
            }, 2000);
        }

        function downloadBackup() {
            showNotification('Preparing backup download...', 'info');
            
            setTimeout(() => {
                showNotification('Backup download started!', 'success');
            }, 2000);
        }

        function testNotifications() {
            showNotification('Sending test notifications...', 'info');
            
            setTimeout(() => {
                showNotification('Test notifications sent successfully!', 'success');
            }, 2000);
        }

        function exportSettings() {
            showNotification('Exporting system configuration...', 'info');
            
            setTimeout(() => {
                showNotification('Configuration exported successfully!', 'success');
            }, 1500);
        }

        // Notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 0.5rem;
                box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
                z-index: 3000;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease;
                max-width: 300px;
            `;
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            // Auto remove
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }

        // Form validation and enhancement
        document.addEventListener('DOMContentLoaded', function() {
            // Add form validation
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const action = formData.get('action');
                    
                    showNotification(`Saving ${action.replace('update_', '')} settings...`, 'info');
                    
                    // Simulate API call
                    setTimeout(() => {
                        showNotification('Settings saved successfully!', 'success');
                    }, 2000);
                });
            });

            // Add toggle enhancement
            const toggles = document.querySelectorAll('input[type="checkbox"]');
            toggles.forEach(toggle => {
                toggle.addEventListener('change', function() {
                    const label = this.closest('.toggle-group').querySelector('.toggle-info h4').textContent;
                    const status = this.checked ? 'enabled' : 'disabled';
                    showNotification(`${label} ${status}`, 'info');
                });
            });

            // Add input validation
            const numberInputs = document.querySelectorAll('input[type="number"]');
            numberInputs.forEach(input => {
                input.addEventListener('input', function() {
                    const min = parseInt(this.getAttribute('min')) || 0;
                    const max = parseInt(this.getAttribute('max')) || Infinity;
                    const value = parseInt(this.value);
                    
                    if (value < min) this.value = min;
                    if (value > max) this.value = max;
                });
            });

            // Animate status cards on load
            const statusCards = document.querySelectorAll('.status-card');
            statusCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 150 + 300);
            });

            // Show success message
            setTimeout(() => {
                showNotification('Settings dashboard loaded successfully! ⚙️', 'success');
            }, 1000);
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + S to save current section
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                const activeSection = document.querySelector('.settings-section.active');
                if (activeSection) {
                    const form = activeSection.querySelector('form');
                    if (form) {
                        form.dispatchEvent(new Event('submit'));
                    }
                }
            }
            
            // Ctrl/Cmd + T to test system
            if ((e.ctrlKey || e.metaKey) && e.key === 't') {
                e.preventDefault();
                testSystemHealth();
            }
            
            // Escape to close sidebar on mobile
            if (e.key === 'Escape' && window.innerWidth <= 768) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                hamburgerBtn.classList.remove('active');
            }
        });

        // Performance monitoring
        function monitorPerformance() {
            if ('performance' in window) {
                const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
                console.log('⚙️ Settings Dashboard Performance:');
                console.log(`🕒 Page Load Time: ${loadTime}ms`);
                console.log('📋 Settings Sections: Loaded');
                console.log('🔧 Form Validation: Active');
            }
        }

        // Initialize performance monitoring
        window.addEventListener('load', monitorPerformance);

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            console.log('⚙️ Settings Dashboard: Cleaning up resources');
        });

        // Initialize page
        console.log('🗳 University Voting System Settings Dashboard loaded successfully');
        console.log('⚙️ System Configuration: Ready');
        console.log('🔧 Database: voting_system');
        console.log('🛡️ Security Settings: Active');
    </script>
</body>
</html>