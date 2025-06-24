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

// Documentation sections
$documentation_sections = [
    'overview' => [
        'title' => 'System Overview',
        'icon' => 'fas fa-eye',
        'content' => [
            'introduction' => 'The University Voting System is a comprehensive digital platform designed to facilitate secure, transparent, and efficient elections within educational institutions.',
            'features' => [
                'Secure voter authentication and registration',
                'Real-time election monitoring and results',
                'Comprehensive audit trails and reporting',
                'Mobile-responsive design for accessibility',
                'Role-based access control',
                'Automated email notifications'
            ]
        ]
    ],
    'installation' => [
        'title' => 'Installation & Setup',
        'icon' => 'fas fa-download',
        'content' => [
            'requirements' => [
                'PHP 7.4 or higher',
                'MySQL 5.7 or higher',
                'Apache/Nginx web server',
                'SSL certificate (recommended)'
            ],
            'steps' => [
                'Download the system files',
                'Configure database connection',
                'Set up virtual host',
                'Configure email settings',
                'Run initial setup script',
                'Create admin account'
            ]
        ]
    ],
    'admin_guide' => [
        'title' => 'Administrator Guide',
        'icon' => 'fas fa-user-shield',
        'content' => [
            'responsibilities' => [
                'Managing elections and candidates',
                'Approving voter registrations',
                'Monitoring system security',
                'Generating reports and analytics',
                'Configuring system settings'
            ]
        ]
    ],
    'api' => [
        'title' => 'API Reference',
        'icon' => 'fas fa-code',
        'content' => [
            'introduction' => 'The voting system provides a RESTful API for integration with external systems.',
            'endpoints' => [
                'GET /api/elections - List all elections',
                'POST /api/votes - Submit a vote',
                'GET /api/results/{id} - Get election results',
                'GET /api/voters - List registered voters'
            ]
        ]
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentation - University Voting System</title>
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

        /* Documentation Content */
        .documentation-container {
            max-width: 100%;
            display: flex;
            gap: 2rem;
        }

        .doc-sidebar {
            width: 300px;
            background: var(--surface);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            padding: 1.5rem;
            height: fit-content;
            position: sticky;
            top: 2rem;
        }

        .doc-nav-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .doc-nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
            margin-bottom: 0.5rem;
        }

        .doc-nav-item:hover {
            background: var(--surface-alt);
            color: var(--primary);
        }

        .doc-nav-item.active {
            background: var(--primary);
            color: white;
        }

        .doc-content {
            flex: 1;
            background: var(--surface);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            padding: 2rem;
        }

        .page-header {
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

        .doc-section {
            margin-bottom: 3rem;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .section-icon {
            width: 3rem;
            height: 3rem;
            border-radius: var(--radius-lg);
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .section-content {
            line-height: 1.7;
        }

        .section-content p {
            margin-bottom: 1rem;
        }

        .feature-list, .requirement-list, .step-list, .endpoint-list {
            list-style: none;
            margin: 1rem 0;
        }

        .feature-list li, .requirement-list li, .step-list li, .endpoint-list li {
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .feature-list li:last-child, .requirement-list li:last-child, 
        .step-list li:last-child, .endpoint-list li:last-child {
            border-bottom: none;
        }

        .feature-list li::before {
            content: '✓';
            color: var(--success);
            font-weight: bold;
        }

        .requirement-list li::before {
            content: '•';
            color: var(--primary);
            font-weight: bold;
        }

        .step-list li::before {
            content: counter(step-counter);
            counter-increment: step-counter;
            background: var(--primary);
            color: white;
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .step-list {
            counter-reset: step-counter;
        }

        .endpoint-list li {
            background: var(--surface-alt);
            padding: 1rem;
            border-radius: var(--radius-md);
            font-family: monospace;
            margin-bottom: 0.5rem;
            border: none;
        }

        .code-block {
            background: var(--surface-alt);
            padding: 1rem;
            border-radius: var(--radius-md);
            font-family: monospace;
            margin: 1rem 0;
            border-left: 4px solid var(--primary);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .documentation-container {
                flex-direction: column;
            }

            .doc-sidebar {
                width: 100%;
                position: static;
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
                padding: 1.5rem;
                width: 100vw;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .doc-content {
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
                    <span class="current">Documentation</span>
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

            <!-- Documentation Content -->
            <div class="documentation-container">
                <!-- Documentation Sidebar -->
                <aside class="doc-sidebar">
                    <h3 class="doc-nav-title">Documentation</h3>
                    <nav>
                        <?php foreach ($documentation_sections as $key => $section): ?>
                            <a href="#<?php echo $key; ?>" class="doc-nav-item <?php echo $key === 'overview' ? 'active' : ''; ?>" data-section="<?php echo $key; ?>">
                                <i class="<?php echo $section['icon']; ?>"></i>
                                <span><?php echo $section['title']; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                </aside>

                <!-- Main Documentation Content -->
                <div class="doc-content">
                    <div class="page-header">
                        <h1 class="page-title">System Documentation</h1>
                        <p class="page-subtitle">Comprehensive guide to understanding and using the University Voting System</p>
                    </div>

                    <!-- System Overview Section -->
                    <section id="overview" class="doc-section">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="fas fa-eye"></i>
                            </div>
                            <h2 class="section-title">System Overview</h2>
                        </div>
                        <div class="section-content">
                            <p><?php echo $documentation_sections['overview']['content']['introduction']; ?></p>
                            
                            <h3>Key Features</h3>
                            <ul class="feature-list">
                                <?php foreach ($documentation_sections['overview']['content']['features'] as $feature): ?>
                                    <li><?php echo $feature; ?></li>
                                <?php endforeach; ?>
                            </ul>

                            <h3>System Architecture</h3>
                            <p>The system follows a three-tier architecture:</p>
                            <ul class="feature-list">
                                <li><strong>Presentation Layer:</strong> Web-based interface built with HTML5, CSS3, and JavaScript</li>
                                <li><strong>Application Layer:</strong> PHP backend with secure authentication and business logic</li>
                                <li><strong>Data Layer:</strong> MySQL database with encrypted vote storage and audit trails</li>
                            </ul>
                        </div>
                    </section>

                    <!-- Installation Section -->
                    <section id="installation" class="doc-section">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="fas fa-download"></i>
                            </div>
                            <h2 class="section-title">Installation & Setup</h2>
                        </div>
                        <div class="section-content">
                            <h3>System Requirements</h3>
                            <ul class="requirement-list">
                                <?php foreach ($documentation_sections['installation']['content']['requirements'] as $requirement): ?>
                                    <li><?php echo $requirement; ?></li>
                                <?php endforeach; ?>
                            </ul>

                            <h3>Installation Steps</h3>
                            <ol class="step-list">
                                <?php foreach ($documentation_sections['installation']['content']['steps'] as $step): ?>
                                    <li><?php echo $step; ?></li>
                                <?php endforeach; ?>
                            </ol>

                            <h3>Database Configuration</h3>
                            <div class="code-block">
                                &lt;?php<br>
                                define('DB_HOST', 'localhost');<br>
                                define('DB_NAME', 'voting_system');<br>
                                define('DB_USER', 'your_username');<br>
                                define('DB_PASS', 'your_password');<br>
                                ?&gt;
                            </div>

                            <h3>Email Configuration</h3>
                            <p>Configure SMTP settings in the config/email.php file for automated notifications:</p>
                            <div class="code-block">
                                $config = [<br>
                                &nbsp;&nbsp;'smtp_host' => 'smtp.your-domain.com',<br>
                                &nbsp;&nbsp;'smtp_port' => 587,<br>
                                &nbsp;&nbsp;'smtp_user' => 'noreply@your-domain.com',<br>
                                &nbsp;&nbsp;'smtp_pass' => 'your_email_password'<br>
                                ];
                            </div>
                        </div>
                    </section>

                    <!-- Administrator Guide Section -->
                    <section id="admin_guide" class="doc-section">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <h2 class="section-title">Administrator Guide</h2>
                        </div>
                        <div class="section-content">
                            <h3>Administrator Responsibilities</h3>
                            <ul class="feature-list">
                                <?php foreach ($documentation_sections['admin_guide']['content']['responsibilities'] as $responsibility): ?>
                                    <li><?php echo $responsibility; ?></li>
                                <?php endforeach; ?>
                            </ul>

                            <h3>Creating Elections</h3>
                            <ol class="step-list">
                                <li>Navigate to Elections > Create New Election</li>
                                <li>Fill in election details (title, description, dates)</li>
                                <li>Configure voting settings and restrictions</li>
                                <li>Add candidates or enable candidate registration</li>
                                <li>Set voter eligibility criteria</li>
                                <li>Review and publish the election</li>
                            </ol>

                            <h3>Managing Voters</h3>
                            <p>Administrators can manage voter registrations through several methods:</p>
                            <ul class="feature-list">
                                <li><strong>Individual Approval:</strong> Review and approve voter applications one by one</li>
                                <li><strong>Bulk Import:</strong> Upload CSV files with voter information</li>
                                <li><strong>Automatic Approval:</strong> Configure criteria for automatic voter approval</li>
                                <li><strong>Verification:</strong> Verify voter credentials against institutional databases</li>
                            </ul>

                            <h3>Security Best Practices</h3>
                            <ul class="feature-list">
                                <li>Regularly update admin passwords</li>
                                <li>Enable two-factor authentication</li>
                                <li>Monitor audit logs for suspicious activity</li>
                                <li>Backup election data regularly</li>
                                <li>Use HTTPS for all connections</li>
                                <li>Limit admin access to authorized personnel only</li>
                            </ul>
                        </div>
                    </section>

                    <!-- API Reference Section -->
                    <section id="api" class="doc-section">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="fas fa-code"></i>
                            </div>
                            <h2 class="section-title">API Reference</h2>
                        </div>
                        <div class="section-content">
                            <p><?php echo $documentation_sections['api']['content']['introduction']; ?></p>

                            <h3>Authentication</h3>
                            <p>All API requests require authentication using API keys or session tokens:</p>
                            <div class="code-block">
                                Authorization: Bearer YOUR_API_TOKEN<br>
                                Content-Type: application/json
                            </div>

                            <h3>Available Endpoints</h3>
                            <div class="endpoint-list">
                                <?php foreach ($documentation_sections['api']['content']['endpoints'] as $endpoint): ?>
                                    <div><?php echo $endpoint; ?></div>
                                <?php endforeach; ?>
                            </div>

                            <h3>Example Request</h3>
                            <div class="code-block">
                                curl -X GET "https://your-domain.com/api/elections" \<br>
                                &nbsp;&nbsp;-H "Authorization: Bearer YOUR_API_TOKEN" \<br>
                                &nbsp;&nbsp;-H "Content-Type: application/json"
                            </div>

                            <h3>Example Response</h3>
                            <div class="code-block">
                                {<br>
                                &nbsp;&nbsp;"success": true,<br>
                                &nbsp;&nbsp;"data": [<br>
                                &nbsp;&nbsp;&nbsp;&nbsp;{<br>
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"id": 1,<br>
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"title": "Student Council Election 2024",<br>
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"status": "active",<br>
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"start_date": "2024-03-01",<br>
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"end_date": "2024-03-07"<br>
                                &nbsp;&nbsp;&nbsp;&nbsp;}<br>
                                &nbsp;&nbsp;]<br>
                                }
                            </div>

                            <h3>Error Handling</h3>
                            <p>The API returns standard HTTP status codes and error messages:</p>
                            <ul class="feature-list">
                                <li><strong>200:</strong> Success</li>
                                <li><strong>400:</strong> Bad Request - Invalid parameters</li>
                                <li><strong>401:</strong> Unauthorized - Invalid or missing authentication</li>
                                <li><strong>403:</strong> Forbidden - Insufficient permissions</li>
                                <li><strong>404:</strong> Not Found - Resource does not exist</li>
                                <li><strong>500:</strong> Internal Server Error</li>
                            </ul>
                        </div>
                    </section>
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

        // Documentation navigation
        document.querySelectorAll('.doc-nav-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all nav items
                document.querySelectorAll('.doc-nav-item').forEach(nav => nav.classList.remove('active'));
                
                // Add active class to clicked item
                this.classList.add('active');
                
                // Hide all sections
                document.querySelectorAll('.doc-section').forEach(section => {
                    section.style.display = 'none';
                });
                
                // Show selected section
                const sectionId = this.getAttribute('data-section');
                document.getElementById(sectionId).style.display = 'block';
                
                // Scroll to top of content
                document.querySelector('.doc-content').scrollTop = 0;
            });
        });

        // Initialize documentation page
        document.addEventListener('DOMContentLoaded', function() {
            // Hide all sections except overview
            document.querySelectorAll('.doc-section').forEach((section, index) => {
                if (index > 0) {
                    section.style.display = 'none';
                }
            });

            // Add keyboard navigation support
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const sidebar = document.getElementById('sidebar');
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                    hamburgerBtn.classList.remove('active');
                }
            });

            console.log('Documentation page initialized successfully');
        });
    </script>
</body>
</html>