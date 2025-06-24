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
    <title>Privacy Policy - University Voting System</title>
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

        /* Privacy Policy Content */
        .privacy-container {
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

        .privacy-content {
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

            .privacy-container {
                max-width: 100%;
            }

            .page-header {
                padding: 1.5rem;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .privacy-content {
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
                    <span class="current">Privacy Policy</span>
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

            <!-- Privacy Policy Content -->
            <div class="privacy-container">
                <!-- Page Header -->
                <div class="page-header">
                    <h1 class="page-title">Privacy Policy</h1>
                    <p class="page-subtitle">How we collect, use, and protect your information in our voting system</p>
                </div>

                <div class="last-updated">
                    <strong>Last Updated:</strong> <?php echo date('F j, Y'); ?>
                </div>

                <!-- Privacy Policy Content -->
                <div class="privacy-content">
                    <div class="section">
                        <h2 class="section-title">1. Introduction</h2>
                        <div class="section-content">
                            <p>The University Voting System is committed to protecting the privacy and security of all users. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our electronic voting platform.</p>
                            <p>By using our voting system, you agree to the collection and use of information in accordance with this policy. We take your privacy seriously and are committed to maintaining the confidentiality of your personal information and voting choices.</p>
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title">2. Information We Collect</h2>
                        <div class="section-content">
                            <p>We collect several types of information to provide and improve our voting services:</p>
                            
                            <h3>Personal Information</h3>
                            <ul>
                                <li>Name and student/employee identification</li>
                                <li>Email address for communications</li>
                                <li>Academic department or organizational affiliation</li>
                                <li>Authentication credentials</li>
                            </ul>

                            <h3>Voting Information</h3>
                            <ul>
                                <li>Vote selections (anonymized and encrypted)</li>
                                <li>Voting timestamps</li>
                                <li>Election participation records</li>
                            </ul>

                            <h3>Technical Information</h3>
                            <ul>
                                <li>IP addresses for security monitoring</li>
                                <li>Browser and device information</li>
                                <li>System access logs</li>
                                <li>Security audit trails</li>
                            </ul>
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title">3. How We Use Your Information</h2>
                        <div class="section-content">
                            <p>Your information is used exclusively for legitimate voting and administrative purposes:</p>
                            <ul>
                                <li><strong>Voter Authentication:</strong> Verifying your eligibility to participate in elections</li>
                                <li><strong>Election Administration:</strong> Managing voting processes and ensuring election integrity</li>
                                <li><strong>Communication:</strong> Sending important notifications about elections and system updates</li>
                                <li><strong>Security:</strong> Preventing fraud, unauthorized access, and maintaining system security</li>
                                <li><strong>Compliance:</strong> Meeting legal and regulatory requirements for electoral processes</li>
                                <li><strong>System Improvement:</strong> Analyzing usage patterns to enhance user experience</li>
                            </ul>
                            
                            <div class="highlight-box">
                                <strong>Important:</strong> Your vote choices are immediately anonymized and cannot be traced back to your identity. We employ cryptographic techniques to ensure ballot secrecy while maintaining election integrity.
                            </div>
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title">4. Information Sharing and Disclosure</h2>
                        <div class="section-content">
                            <p>We do not sell, trade, or rent your personal information to third parties. Information may be shared only in the following circumstances:</p>
                            
                            <h3>University Officials</h3>
                            <p>Authorized university administrators may access voter registration information for election management purposes, but never individual vote choices.</p>

                            <h3>Election Committees</h3>
                            <p>Designated election officials may receive aggregated voting statistics and participation data, but not individual voter information.</p>

                            <h3>Legal Requirements</h3>
                            <p>We may disclose information when required by law, court order, or to protect the rights, property, or safety of the university community.</p>

                            <h3>Security Incidents</h3>
                            <p>In case of security breaches or system compromises, we may share necessary information with law enforcement or cybersecurity professionals.</p>
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title">5. Data Security</h2>
                        <div class="section-content">
                            <p>We implement comprehensive security measures to protect your information:</p>
                            <ul>
                                <li><strong>Encryption:</strong> All data is encrypted both in transit and at rest using industry-standard protocols</li>
                                <li><strong>Access Controls:</strong> Strict role-based access controls limit who can view different types of information</li>
                                <li><strong>Audit Trails:</strong> Comprehensive logging tracks all system access and changes</li>
                                <li><strong>Regular Security Reviews:</strong> Periodic security assessments and penetration testing</li>
                                <li><strong>Secure Infrastructure:</strong> Hosting on secure, monitored servers with regular security updates</li>
                                <li><strong>Backup and Recovery:</strong> Secure backup procedures with tested recovery processes</li>
                            </ul>
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title">6. Your Rights and Choices</h2>
                        <div class="section-content">
                            <p>You have several rights regarding your personal information:</p>
                            
                            <h3>Access and Correction</h3>
                            <p>You can request to view and update your personal information through your user profile or by contacting system administrators.</p>

                            <h3>Data Portability</h3>
                            <p>You may request a copy of your personal data in a structured, machine-readable format.</p>

                            <h3>Account Deletion</h3>
                            <p>You can request deletion of your account and associated personal data, subject to legal retention requirements.</p>

                            <h3>Communication Preferences</h3>
                            <p>You can manage your email notification preferences through your account settings.</p>

                            <div class="highlight-box">
                                <strong>Note:</strong> While you can request deletion of personal information, anonymized voting records may be retained for historical and audit purposes as required by institutional policies.
                            </div>
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title">7. Data Retention</h2>
                        <div class="section-content">
                            <p>We retain information for different periods based on its purpose:</p>
                            <ul>
                                <li><strong>Personal Information:</strong> Retained while you are an active member of the university community, plus 7 years for audit purposes</li>
                                <li><strong>Voting Records:</strong> Anonymized voting data retained permanently for historical record-keeping</li>
                                <li><strong>System Logs:</strong> Security and access logs retained for 2 years</li>
                                <li><strong>Election Data:</strong> Election results and metadata retained permanently for institutional records</li>
                            </ul>
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title">8. Cookies and Tracking</h2>
                        <div class="section-content">
                            <p>Our voting system uses minimal tracking technologies:</p>
                            <ul>
                                <li><strong>Session Cookies:</strong> Essential for maintaining your login session and system functionality</li>
                                <li><strong>Security Cookies:</strong> Used to prevent cross-site request forgery and other security threats</li>
                                <li><strong>Preference Cookies:</strong> Store your interface preferences and accessibility settings</li>
                            </ul>
                            <p>We do not use advertising cookies or third-party tracking scripts. All cookies are essential for system operation or user experience.</p>
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title">9. Children's Privacy</h2>
                        <div class="section-content">
                            <p>The voting system is designed for university community members and is not intended for use by individuals under 13 years of age. We do not knowingly collect personal information from children under 13. If we become aware of such collection, we will take immediate steps to delete the information.</p>
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title">10. Changes to This Policy</h2>
                        <div class="section-content">
                            <p>We may update this Privacy Policy periodically to reflect changes in our practices or legal requirements. We will:</p>
                            <ul>
                                <li>Post the updated policy on this page with a new "Last Updated" date</li>
                                <li>Notify users of significant changes via email or system announcements</li>
                                <li>Provide a 30-day notice period for material changes that affect your rights</li>
                                <li>Maintain archived versions of previous policies for reference</li>
                            </ul>
                        </div>
                    </div>

                    <div class="contact-info">
                        <h3>Contact Information</h3>
                        <p>If you have questions about this Privacy Policy or our data practices, please contact:</p>
                        <p><strong>Privacy Officer:</strong> privacy@university.edu</p>
                        <p><strong>System Administrator:</strong> voting-admin@university.edu</p>
                        <p><strong>Phone:</strong> (555) 123-4567</p>
                        <p><strong>Office:</strong> Information Technology Services, Administration Building</p>
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

        // Initialize privacy policy page
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

            console.log('Privacy Policy page initialized successfully');
        });
    </script>
</body>
</html>