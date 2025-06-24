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

// Help categories and topics
$help_categories = [
    'getting_started' => [
        'title' => 'Getting Started',
        'icon' => 'fas fa-rocket',
        'topics' => [
            'First Time Setup' => 'Learn how to configure your voting system for the first time',
            'Creating Your First Election' => 'Step-by-step guide to setting up an election',
            'User Management Basics' => 'Understanding roles and permissions',
            'Dashboard Overview' => 'Navigate the admin dashboard effectively'
        ]
    ],
    'elections' => [
        'title' => 'Election Management',
        'icon' => 'fas fa-poll',
        'topics' => [
            'Creating Elections' => 'How to set up new voting campaigns',
            'Election Settings' => 'Configure voting periods, rules, and restrictions',
            'Managing Candidates' => 'Add, approve, and organize candidate information',
            'Election Monitoring' => 'Track progress and manage ongoing elections'
        ]
    ],
    'voters' => [
        'title' => 'Voter Management',
        'icon' => 'fas fa-users',
        'topics' => [
            'Voter Registration' => 'Process and approve voter applications',
            'Bulk Import' => 'Import multiple voters using CSV files',
            'Voter Verification' => 'Verify voter eligibility and credentials',
            'Access Control' => 'Manage voter permissions and restrictions'
        ]
    ],
    'results' => [
        'title' => 'Results & Analytics',
        'icon' => 'fas fa-chart-bar',
        'topics' => [
            'Viewing Results' => 'Access and interpret election results',
            'Generating Reports' => 'Create detailed voting reports',
            'Data Export' => 'Export results in various formats',
            'Analytics Dashboard' => 'Understand voting patterns and trends'
        ]
    ],
    'security' => [
        'title' => 'Security & Privacy',
        'icon' => 'fas fa-shield-alt',
        'topics' => [
            'Security Best Practices' => 'Protect your voting system from threats',
            'Data Privacy' => 'Ensure voter privacy and data protection',
            'Audit Logs' => 'Monitor system activity and changes',
            'Backup & Recovery' => 'Safeguard your election data'
        ]
    ],
    'troubleshooting' => [
        'title' => 'Troubleshooting',
        'icon' => 'fas fa-tools',
        'topics' => [
            'Common Issues' => 'Solutions to frequently encountered problems',
            'System Errors' => 'Understanding and resolving error messages',
            'Performance Issues' => 'Optimize system performance',
            'Browser Compatibility' => 'Ensure compatibility across browsers'
        ]
    ]
];

$faq_items = [
    [
        'question' => 'How do I create a new election?',
        'answer' => 'Navigate to Elections > Create New Election. Fill in the election details, set the voting period, add candidates, and configure voter eligibility. Review all settings before publishing the election.'
    ],
    [
        'question' => 'Can voters change their vote after submission?',
        'answer' => 'By default, votes are final once submitted. However, you can enable vote changes in the election settings if your institution allows it. This setting must be configured before the election begins.'
    ],
    [
        'question' => 'How do I approve pending voter registrations?',
        'answer' => 'Go to Voters > Pending Approvals. Review each application, verify the voter\'s eligibility, and either approve or reject the registration. Approved voters will receive email confirmation.'
    ],
    [
        'question' => 'What happens if there\'s a tie in the election?',
        'answer' => 'The system will clearly indicate tied results in the results dashboard. Your institution\'s election rules should specify the tie-breaking procedure, which you can implement manually through the admin interface.'
    ],
    [
        'question' => 'How can I export election results?',
        'answer' => 'In the Results section, select the election and click "Export Results." Choose your preferred format (PDF, Excel, CSV) and the system will generate a comprehensive report including vote counts, percentages, and voter statistics.'
    ],
    [
        'question' => 'Is the voting system secure?',
        'answer' => 'Yes, the system uses industry-standard encryption, secure authentication, and maintains comprehensive audit logs. All votes are anonymized while ensuring election integrity and preventing duplicate voting.'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help & Support - University Voting System</title>
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

        /* Help Content */
        .help-container {
            max-width: 100%;
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
            max-width: 600px;
            margin: 0 auto;
        }

        .search-section {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            margin-bottom: 2rem;
        }

        .search-box {
            position: relative;
            max-width: 500px;
            margin: 0 auto;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid var(--border);
            border-radius: var(--radius-lg);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .help-categories {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .category-card {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            transition: all 0.3s ease;
        }

        .category-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
        }

        .category-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .category-icon {
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

        .category-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .category-topics {
            list-style: none;
        }

        .topic-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-light);
        }

        .topic-item:last-child {
            border-bottom: none;
        }

        .topic-link {
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .topic-link:hover {
            color: var(--primary);
        }

        .topic-description {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        .faq-section {
            background: var(--surface);
            padding: 2rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .faq-item {
            border-bottom: 1px solid var(--border-light);
            padding: 1.5rem 0;
        }

        .faq-item:last-child {
            border-bottom: none;
        }

        .faq-question {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .faq-answer {
            color: var(--text-secondary);
            line-height: 1.6;
            padding-right: 2rem;
        }

        .contact-section {
            background: var(--surface);
            padding: 2rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            text-align: center;
        }

        .contact-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .contact-description {
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }

        .contact-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .contact-btn {
            background: var(--primary-gradient);
            color: white;
            padding: 0.875rem 1.5rem;
            border: none;
            border-radius: var(--radius-lg);
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .contact-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .contact-btn.secondary {
            background: var(--surface-alt);
            color: var(--text-primary);
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

            .help-categories {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .page-header {
                padding: 1.5rem;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .contact-buttons {
                flex-direction: column;
                align-items: center;
            }

            .contact-btn {
                width: 100%;
                max-width: 300px;
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
                    <span class="current">Help & Support</span>
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

            <!-- Help Content -->
            <div class="help-container">
                <!-- Page Header -->
                <div class="page-header">
                    <h1 class="page-title">Help & Support Center</h1>
                    <p class="page-subtitle">Find answers, learn about features, and get the most out of your university voting system</p>
                </div>

                <!-- Search Section -->
                <div class="search-section">
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" placeholder="Search for help topics, features, or common questions...">
                    </div>
                </div>

                <!-- Help Categories -->
                <div class="help-categories">
                    <?php foreach ($help_categories as $key => $category): ?>
                        <div class="category-card">
                            <div class="category-header">
                                <div class="category-icon">
                                    <i class="<?php echo $category['icon']; ?>"></i>
                                </div>
                                <h3 class="category-title"><?php echo $category['title']; ?></h3>
                            </div>
                            <ul class="category-topics">
                                <?php foreach ($category['topics'] as $topic => $description): ?>
                                    <li class="topic-item">
                                        <a href="#" class="topic-link">
                                            <span><?php echo $topic; ?></span>
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                        <div class="topic-description"><?php echo $description; ?></div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- FAQ Section -->
                <div class="faq-section">
                    <h2 class="section-title">Frequently Asked Questions</h2>
                    <div class="faq-list">
                        <?php foreach ($faq_items as $faq): ?>
                            <div class="faq-item">
                                <div class="faq-question">
                                    <span><?php echo htmlspecialchars($faq['question']); ?></span>
                                    <i class="fas fa-plus"></i>
                                </div>
                                <div class="faq-answer">
                                    <?php echo htmlspecialchars($faq['answer']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Contact Section -->
                <div class="contact-section">
                    <h2 class="contact-title">Still Need Help?</h2>
                    <p class="contact-description">
                        Can't find what you're looking for? Our support team is here to help you with any questions or issues.
                    </p>
                    <div class="contact-buttons">
                        <a href="admin_contact.php" class="contact-btn">
                            <i class="fas fa-envelope"></i>
                            Contact Support
                        </a>
                        <a href="admin_documentation.php" class="contact-btn secondary">
                            <i class="fas fa-book"></i>
                            View Documentation
                        </a>
                    </div>
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

        // Search functionality
        document.querySelector('.search-input').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const categoryCards = document.querySelectorAll('.category-card');
            
            categoryCards.forEach(card => {
                const topics = card.querySelectorAll('.topic-item');
                let hasMatch = false;
                
                topics.forEach(topic => {
                    const topicText = topic.textContent.toLowerCase();
                    if (topicText.includes(searchTerm)) {
                        topic.style.display = 'block';
                        hasMatch = true;
                    } else {
                        topic.style.display = searchTerm ? 'none' : 'block';
                    }
                });
                
                card.style.display = (hasMatch || !searchTerm) ? 'block' : 'none';
            });
        });

        // FAQ toggle functionality
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', () => {
                const faqItem = question.parentElement;
                const answer = faqItem.querySelector('.faq-answer');
                const icon = question.querySelector('i');
                
                // Toggle visibility
                if (answer.style.display === 'none' || !answer.style.display) {
                    answer.style.display = 'block';
                    icon.classList.remove('fa-plus');
                    icon.classList.add('fa-minus');
                } else {
                    answer.style.display = 'none';
                    icon.classList.remove('fa-minus');
                    icon.classList.add('fa-plus');
                }
            });
        });

        // Initialize help page
        document.addEventListener('DOMContentLoaded', function() {
            // Hide all FAQ answers by default
            document.querySelectorAll('.faq-answer').forEach(answer => {
                answer.style.display = 'none';
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

            console.log('Help & Support page initialized successfully');
        });
    </script>
</body>
</html>