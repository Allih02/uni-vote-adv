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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - University Voting System</title>
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

        /* Terms Content */
        .terms-container {
            max-width: 800px;
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

        .last-updated {
            background: var(--surface-alt);
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 2rem;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .terms-content {
            background: var(--surface);
            padding: 2rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            line-height: 1.7;
        }

        .section {
            margin-bottom: 3rem;
        }

        .section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary);
        }

        .section-content p {
            margin-bottom: 1rem;
        }

        .section-content ul {
            margin: 1rem 0;
            padding-left: 2rem;
        }

        .section-content li {
            margin-bottom: 0.5rem;
        }

        .highlight-box {
            background: var(--surface-alt);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            border-left: 4px solid var(--primary);
            margin: 1.5rem 0;
        }

        .warning-box {
            background: rgba(239, 68, 68, 0.1);
            border-left: 4px solid var(--error);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            margin: 1.5rem 0;
        }

        .contact-info {
            background: var(--primary-gradient);
            color: white;
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            margin-top: 2rem;
            text-align: center;
        }

        .contact-info h3 {
            margin-bottom: 1rem;
        }

        .contact-info p {
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

            .terms-container {
                max-width: 100%;
            }

            .page-header {
                padding: 1.5rem;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .terms-content {
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
                    <span class="current">Terms of Service</span>
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

            <!-- Terms of Service Content -->
            <div class="terms-container">
                <!-- Page Header -->
                <div class="page-header">
                    <h1 class="page-title">Terms of Service</h1>
                    <p class="page-subtitle">Your agreement for using the University Voting System</p>
                </div>

                <div class="last-updated">
                    <strong>Last Updated:</strong> <?php echo date('F j, Y'); ?>
                </div>

                <!-- Terms Content -->
                <div class="terms-content">
                    <div class="section">
                        <h2 class="section-title">1. Acceptance of Terms</h2>
                        <div class="section-content">
                            <p>By accessing and using the University Voting System, you agree to be bound by these Terms of Service and all applicable laws and regulations. If you do not agree with any of these terms, you are prohibited from using or accessing this system.</p>
                            <p>These terms constitute a legally binding agreement between you and the University regarding your use of the voting platform.</p>
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title">2. Eligibility and Registration</h2>
                        <div class="section-content">
                            <h3>Voter Eligibility</h3>
                            <p>To participate in university elections, you must:</p>
                            <ul>
                                <li>Be a currently enrolled student, active faculty member, or eligible staff member</li>
                                <li>Maintain good standing with the university</li>
                                <li>Meet any additional eligibility requirements specified for particular elections</li>
                                <li>Provide accurate and complete registration information</li>
                            </ul>

                            <h3>Account Registration</h3>
                            <p>You are responsible for:</p>
                            <ul>
                                <li>Maintaining the confidentiality of your login credentials</li>
                                <li>All activities that occur under your account</li>
                                <li>Immediately notifying administrators of any unauthorized use</li>
                                <li>Providing accurate and up-to-date contact information</li>
                            </ul>
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title">3. Acceptable Use</h2>
                        <div class="section-content">
                            <p>When using the voting system, you agree to:</p>
                            <ul>
                                <li><strong>Vote Responsibly:</strong> Cast your vote thoughtfully and in accordance with your genuine preferences</li>
                                <li><strong>Respect Privacy:</strong> Not attempt to access other users' voting choices or personal information</li>
                                <li><strong>Maintain Security:</strong> Protect your login credentials and report any security concerns</li>
                                <li><strong>Follow Instructions:</strong> Comply with all voting procedures and system guidelines</li>
                                <li><strong>Report Issues:</strong> Immediately report any technical problems or suspicious activity</li>
                            </ul>

                            <div class="warning-box">
                                <strong>Prohibited Activities:</strong> You must not attempt to hack, compromise, or manipulate the voting system in any way. Such actions may result in criminal charges and university disciplinary action.
                            </div>
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title">4. Voting Procedures</h2>
                        <div class="section-content">
                            <h3>Voting Process</h3>
                            <ul>
                                <li>Each eligible voter may cast one vote per election</li>
                                <li>Votes are final once submitted (unless otherwise specified)</li>
                                <li>The system will confirm successful vote submission</li>
                                <li>Technical support is available during voting periods</li>
                            </ul>

                            <h3>Vote Secrecy</h3>
                            <p>The system is designed to ensure ballot secrecy while maintaining election integrity. Your vote choices are:</p>
                            <ul>
                                <li>Immediately anonymized upon submission</li>
                                <li>Encrypted and stored securely</li>
                                <li>Not traceable to your identity in results</li>
                                <li>Protected by comprehensive audit controls</li>
                            </ul>

                            <h3>Election Disputes</h3>
                            <p>Any concerns about election procedures or results must be reported through official university channels within 48 hours of the election conclusion.</p>
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title">5. System Availability</h2>
                        <div class="section-content">
                            <p>While we strive to maintain continuous system availability, we cannot guarantee uninterrupted service. The university reserves the right to:</p>
                            <ul>
                                <li>Perform scheduled maintenance during non-critical periods</li>
                                <li>Temporarily suspend service for security or technical reasons</li>
                                <li>Extend voting periods in case of significant technical issues</li>
                                <li>Implement emergency procedures if system integrity is compromised</li>
                            </ul>

                            <div class="highlight-box">
                                <strong>Service Availability:</strong> We target 99.9% uptime during active voting periods and provide 24/7 technical support during elections.
                            </div>
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title">6. Data Protection and Privacy</h2>
                        <div class="section-content">
                            <p>Your privacy is protected in accordance with our Privacy Policy. Key protections include:</p>
                            <ul>
                                <li><strong>Data Security:</strong> All personal and voting data is encrypted and securely stored</li>
                                <li><strong>Limited Access:</strong> Only authorized personnel can access voter registration data</li>
                                <li><strong>Audit Trails:</strong> Comprehensive logging tracks all system access and changes</li>
                                <li><strong>Data Retention:</strong> Information is retained only as long as necessary for legitimate purposes</li>
                            </ul>
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title">7. Intellectual Property</h2>
                        <div class="section-content">
                            <p>The voting system, including its software, design, and documentation, is owned by the university. You agree that:</p>
                            <ul>
                                <li>You will not copy, modify, or distribute any part of the system</li>
                                <li>All system interfaces and content are protected by copyright</li>
                                <li>You may not reverse engineer or attempt to extract source code</li>
                                <li>Screenshots or recordings of the system may only be used for legitimate university purposes</li>
                            </ul>
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title">8. Limitation of Liability</h2>
                        <div class="section-content">
                            <p>The university provides the voting system "as is" and makes no warranties about:</p>
                            <ul>
                                <li>Continuous availability or error-free operation</li>
                                <li>Compatibility with all devices or browsers</li>
                                <li>Prevention of all security incidents</li>
                                <li>Elimination of all technical issues</li>
                            </ul>

                            <p>The university's liability is limited to providing reasonable efforts to maintain system security and integrity. Users participate in elections at their own discretion.</p>
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title">9. Disciplinary Actions</h2>
                        <div class="section-content">
                            <p>Violations of these terms may result in:</p>
                            <ul>
                                <li><strong>Account Suspension:</strong> Temporary or permanent loss of voting privileges</li>
                                <li><strong>University Discipline:</strong> Academic or employment disciplinary action</li>
                                <li><strong>Legal Action:</strong> Criminal charges for serious violations such as hacking</li>
                                <li><strong>Election Nullification:</strong> In extreme cases, elections may be invalidated</li>
                            </ul>

                            <div class="warning-box">
                                <strong>Zero Tolerance:</strong> The university has zero tolerance for attempts to compromise election integrity. All suspected violations will be thoroughly investigated.
                            </div>
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title">10. System Updates and Changes</h2>
                        <div class="section-content">
                            <p>The university reserves the right to:</p>
                            <ul>
                                <li>Update system features and functionality</li>
                                <li>Modify security procedures as needed</li>
                                <li>Change voting procedures with appropriate notice</li>
                                <li>Update these Terms of Service</li>
                            </ul>

                            <p>Significant changes will be communicated through official university channels with adequate notice before implementation.</p>
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title">11. Technical Support</h2>
                        <div class="section-content">
                            <p>Technical support is available through:</p>
                            <ul>
                                <li><strong>Help Desk:</strong> Available during business hours and extended hours during elections</li>
                                <li><strong>Online Documentation:</strong> Comprehensive user guides and FAQs</li>
                                <li><strong>Email Support:</strong> Technical assistance via voting-support@university.edu</li>
                                <li><strong>Emergency Support:</strong> 24/7 support during active voting periods</li>
                            </ul>
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title">12. Governing Law</h2>
                        <div class="section-content">
                            <p>These Terms of Service are governed by the laws of the state where the university is located and applicable federal laws. Any disputes arising from use of the voting system will be resolved through the university's established grievance procedures or appropriate legal channels.</p>
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title">13. Contact Information</h2>
                        <div class="section-content">
                            <p>For questions about these Terms of Service or the voting system, contact:</p>
                            <ul>
                                <li><strong>Election Administrator:</strong> elections@university.edu</li>
                                <li><strong>Technical Support:</strong> voting-support@university.edu</li>
                                <li><strong>Legal Questions:</strong> legal@university.edu</li>
                                <li><strong>Privacy Concerns:</strong> privacy@university.edu</li>
                            </ul>
                        </div>
                    </div>

                    <div class="contact-info">
                        <h3>Agreement Acknowledgment</h3>
                        <p>By using the University Voting System, you acknowledge that you have read, understood, and agree to be bound by these Terms of Service.</p>
                        <p><strong>University Voting System Administration</strong></p>
                        <p>Information Technology Services</p>
                        <p>Phone: (555) 123-4567</p>
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

        // Initialize terms page
        document.addEventListener('DOMContentLoaded', function() {
            // Add keyboard navigation support
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const sidebar = document.getElementById('sidebar');
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                    hamburgerBtn.classList.remove('active');
                }
            });

            console.log('Terms of Service page initialized successfully');
        });
    </script>
</body>
</html>