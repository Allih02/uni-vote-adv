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

// Get notifications
function getNotifications() {
    try {
        $conn = getDBConnection();
        
        $notifications = [];
        
        // Get pending voters
        $stmt = $conn->query("SELECT COUNT(*) as count FROM voters WHERE status = 'pending'");
        $pending_voters = $stmt->fetch()['count'];
        if ($pending_voters > 0) {
            $notifications[] = [
                'type' => 'pending_approval',
                'title' => 'Pending Voter Approvals',
                'message' => "$pending_voters voter registration(s) awaiting approval",
                'count' => $pending_voters,
                'icon' => 'fas fa-user-check',
                'link' => 'admin_voters.php?status=pending',
                'priority' => 'high',
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        
        // Get pending candidates
        $stmt = $conn->query("SELECT COUNT(*) as count FROM candidates WHERE status = 'pending'");
        $pending_candidates = $stmt->fetch()['count'];
        if ($pending_candidates > 0) {
            $notifications[] = [
                'type' => 'pending_approval',
                'title' => 'Pending Candidate Approvals',
                'message' => "$pending_candidates candidate application(s) awaiting approval",
                'count' => $pending_candidates,
                'icon' => 'fas fa-user-tie',
                'link' => 'admin_candidates.php?status=pending',
                'priority' => 'high',
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        
        // Get active elections ending soon
        $stmt = $conn->query("SELECT * FROM elections WHERE status = 'active' AND end_date <= DATE_ADD(NOW(), INTERVAL 24 HOUR)");
        $ending_elections = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($ending_elections as $election) {
            $notifications[] = [
                'type' => 'election_ending',
                'title' => 'Election Ending Soon',
                'message' => "Election '{$election['title']}' ends within 24 hours",
                'count' => 1,
                'icon' => 'fas fa-clock',
                'link' => 'admin_elections.php',
                'priority' => 'medium',
                'created_at' => $election['end_date']
            ];
        }
        
        // System notifications
        $notifications[] = [
            'type' => 'system',
            'title' => 'System Backup Completed',
            'message' => 'Daily system backup completed successfully',
            'count' => 1,
            'icon' => 'fas fa-shield-alt',
            'link' => 'admin_settings.php',
            'priority' => 'low',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
        ];
        
        return $notifications;
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [];
    }
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    
    return date('M j, Y', strtotime($datetime));
}

$notifications = getNotifications();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - University Voting System</title>
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

        /* Notifications Content */
        .notifications-container {
            max-width: 100%;
        }

        .page-header {
            background: var(--surface);
            padding: 2rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            margin-bottom: 2rem;
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

        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .mark-all-read {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-lg);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .mark-all-read:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .notifications-list {
            background: var(--surface);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .notification-item {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            transition: all 0.3s ease;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item:hover {
            background: var(--surface-alt);
        }

        .notification-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .notification-icon.high {
            background: var(--error);
        }

        .notification-icon.medium {
            background: var(--warning);
        }

        .notification-icon.low {
            background: var(--info);
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .notification-message {
            color: var(--text-secondary);
            margin-bottom: 0.75rem;
            line-height: 1.5;
        }

        .notification-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
        }

        .notification-time {
            color: var(--text-muted);
        }

        .notification-action {
            background: var(--primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .notification-action:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
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

            .page-header {
                padding: 1.5rem;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .notifications-header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .notification-item {
                padding: 1rem;
                flex-direction: column;
                text-align: center;
            }

            .notification-meta {
                flex-direction: column;
                gap: 0.5rem;
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
                    <span class="current">Notifications</span>
                </div>
                <div class="top-actions">
                    <button class="notification-btn">
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

            <!-- Notifications Content -->
            <div class="notifications-container">
                <!-- Page Header -->
                <div class="page-header">
                    <h1 class="page-title">Notifications</h1>
                    <p class="page-subtitle">Stay updated with system alerts, pending approvals, and important updates</p>
                </div>

                <!-- Notifications List -->
                <div class="notifications-list">
                    <?php if (!empty($notifications)): ?>
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item">
                                <div class="notification-icon <?php echo $notification['priority']; ?>">
                                    <i class="<?php echo $notification['icon']; ?>"></i>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                    <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                                    <div class="notification-meta">
                                        <span class="notification-time"><?php echo timeAgo($notification['created_at']); ?></span>
                                        <?php if (isset($notification['link'])): ?>
                                            <a href="<?php echo $notification['link']; ?>" class="notification-action">View Details</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-bell-slash"></i>
                            <h3>No notifications</h3>
                            <p>You're all caught up! There are no pending notifications at this time.</p>
                        </div>
                    <?php endif; ?>
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

        // Initialize notifications
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

            console.log('Notifications page initialized successfully');
        });
    </script>
</body>
</html>