<?php
// Admin Dashboard - No authentication required for demo
// session_start();
// if (!isset($_SESSION["admin_id"]) || $_SESSION["role"] !== "admin") {
//     header("Location: admin_login.php");
//     exit();
// }

// Mock admin data
$admin_user = [
    "admin_id" => 1,
    "fullname" => "Dr. Sarah Johnson",
    "email" => "admin@university.edu",
    "role" => "Administrator"
];

// Mock election data
$elections = [
    [
        "id" => 1,
        "title" => "Student Council Elections 2025",
        "start_date" => "2025-06-01",
        "end_date" => "2025-06-30",
        "status" => "active",
        "total_votes" => 324,
        "eligible_voters" => 1250,
        "candidates" => 8
    ],
    [
        "id" => 2,
        "title" => "Faculty Senate Elections",
        "start_date" => "2025-07-01",
        "end_date" => "2025-07-15",
        "status" => "upcoming",
        "total_votes" => 0,
        "eligible_voters" => 450,
        "candidates" => 12
    ],
    [
        "id" => 3,
        "title" => "Graduation Committee Elections",
        "start_date" => "2025-04-01",
        "end_date" => "2025-04-15",
        "status" => "completed",
        "total_votes" => 156,
        "eligible_voters" => 200,
        "candidates" => 4
    ]
];

// Mock statistics
$stats = [
    "total_elections" => 5,
    "active_elections" => 1,
    "total_candidates" => 24,
    "total_voters" => 1250,
    "total_votes_cast" => 480,
    "voter_turnout" => 38.4,
    "pending_approvals" => 3,
    "system_alerts" => 2
];

// Mock recent activities
$recent_activities = [
    [
        "id" => 1,
        "action" => "New voter registered",
        "user" => "John Smith",
        "time" => "2 minutes ago",
        "icon" => "user-plus",
        "type" => "success"
    ],
    [
        "id" => 2,
        "action" => "Vote cast in Student Council Elections",
        "user" => "Anonymous Voter",
        "time" => "5 minutes ago",
        "icon" => "vote-yea",
        "type" => "info"
    ],
    [
        "id" => 3,
        "action" => "New candidate added",
        "user" => "Admin",
        "time" => "15 minutes ago",
        "icon" => "user-tie",
        "type" => "success"
    ],
    [
        "id" => 4,
        "action" => "Election status updated",
        "user" => "Dr. Johnson",
        "time" => "1 hour ago",
        "icon" => "edit",
        "type" => "warning"
    ],
    [
        "id" => 5,
        "action" => "System backup completed",
        "user" => "System",
        "time" => "2 hours ago",
        "icon" => "database",
        "type" => "info"
    ]
];

// Mock notifications
$notifications = [
    [
        "id" => 1,
        "title" => "High voter turnout achieved",
        "message" => "Student Council Elections has reached 30% turnout",
        "time" => "10 minutes ago",
        "read" => false,
        "type" => "success"
    ],
    [
        "id" => 2,
        "title" => "New candidate pending approval",
        "message" => "Michael Chen submitted application for review",
        "time" => "1 hour ago",
        "read" => false,
        "type" => "warning"
    ],
    [
        "id" => 3,
        "title" => "Election reminder",
        "message" => "Faculty Senate Elections starts in 2 days",
        "time" => "3 hours ago",
        "read" => true,
        "type" => "info"
    ]
];

// Calculate some derived stats
$unread_notifications = count(array_filter($notifications, function($n) { return !$n['read']; }));
$active_election = array_filter($elections, function($e) { return $e['status'] === 'active'; });
$active_election = !empty($active_election) ? reset($active_election) : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - University Voting System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #a5b4fc;
            --secondary: #8b5cf6;
            --accent: #f43f5e;
            --background: #f8fafc;
            --surface: #ffffff;
            --surface-hover: #f1f5f9;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --border: #e2e8f0;
            --border-dark: #cbd5e1;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --info: #3b82f6;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--background);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: var(--surface);
            border-right: 1px solid var(--border);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
            box-shadow: var(--shadow-sm);
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: white;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .logo i {
            font-size: 1.5rem;
        }

        .admin-profile {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }

        .admin-profile h4 {
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .admin-profile p {
            font-size: 0.75rem;
            opacity: 0.8;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-section {
            margin-bottom: 1.5rem;
        }

        .nav-section-title {
            padding: 0.5rem 1.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
            position: relative;
        }

        .nav-item:hover {
            background: var(--surface-hover);
            color: var(--text-primary);
        }

        .nav-item.active {
            background: var(--primary-light);
            color: var(--primary-dark);
            border-left-color: var(--primary);
            font-weight: 500;
        }

        .nav-item i {
            width: 1.25rem;
            text-align: center;
        }

        .nav-badge {
            position: absolute;
            right: 1rem;
            background: var(--error);
            color: white;
            font-size: 0.75rem;
            padding: 0.125rem 0.5rem;
            border-radius: 1rem;
            font-weight: 600;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .top-bar {
            background: var(--surface);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-sm);
        }

        .top-bar h1 {
            font-size: 1.875rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .top-bar-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .notification-icon {
            position: relative;
            padding: 0.75rem;
            border-radius: var(--radius-md);
            background: var(--surface-hover);
            color: var(--text-secondary);
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
        }

        .notification-icon:hover {
            background: var(--border);
            color: var(--text-primary);
        }

        .notification-badge {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            width: 0.5rem;
            height: 0.5rem;
            background: var(--error);
            border-radius: 50%;
        }

        /* Page Content */
        .page-content {
            flex: 1;
            padding: 2rem;
        }

        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem;
            border-radius: var(--radius-xl);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: pulse 15s ease-in-out infinite alternate;
        }

        .welcome-content {
            position: relative;
            z-index: 2;
        }

        .welcome-content h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .welcome-content p {
            opacity: 0.9;
            margin-bottom: 1rem;
        }

        .welcome-stats {
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
        }

        .welcome-stat {
            text-align: center;
        }

        .welcome-stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            display: block;
        }

        .welcome-stat-label {
            font-size: 0.875rem;
            opacity: 0.8;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--surface);
            padding: 2rem;
            border-radius: var(--radius-xl);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary);
        }

        .stat-card.success::before { background: var(--success); }
        .stat-card.warning::before { background: var(--warning); }
        .stat-card.error::before { background: var(--error); }
        .stat-card.info::before { background: var(--info); }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 3rem;
            height: 3rem;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            background: var(--primary);
        }

        .stat-card.success .stat-icon { background: var(--success); }
        .stat-card.warning .stat-icon { background: var(--warning); }
        .stat-card.error .stat-icon { background: var(--error); }
        .stat-card.info .stat-icon { background: var(--info); }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-family: 'Inter', monospace;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .stat-change {
            margin-top: 0.75rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .stat-change.positive {
            color: var(--success);
        }

        .stat-change.negative {
            color: var(--error);
        }

        .stat-change.neutral {
            color: var(--text-muted);
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .content-card {
            background: var(--surface);
            border-radius: var(--radius-xl);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .content-card-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .content-card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .content-card-body {
            padding: 0;
        }

        /* Elections List */
        .election-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border);
            transition: all 0.2s ease;
        }

        .election-item:last-child {
            border-bottom: none;
        }

        .election-item:hover {
            background: var(--surface-hover);
        }

        .election-info h4 {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .election-meta {
            font-size: 0.875rem;
            color: var(--text-secondary);
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .election-meta-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .election-status {
            padding: 0.375rem 0.75rem;
            border-radius: var(--radius-md);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-upcoming {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-completed {
            background: #f3e8ff;
            color: #6b21a8;
        }

        /* Activity Feed */
        .activity-item {
            display: flex;
            gap: 1rem;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border);
            transition: all 0.2s ease;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-item:hover {
            background: var(--surface-hover);
        }

        .activity-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-dark);
            font-size: 0.875rem;
            flex-shrink: 0;
        }

        .activity-icon.success {
            background: #dcfce7;
            color: #166534;
        }

        .activity-icon.warning {
            background: #fef3c7;
            color: #92400e;
        }

        .activity-icon.info {
            background: #dbeafe;
            color: #1e40af;
        }

        .activity-content {
            flex: 1;
        }

        .activity-action {
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .activity-meta {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .quick-action {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.2s ease;
            text-align: center;
        }

        .quick-action:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
            border-color: var(--primary);
        }

        .quick-action i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .quick-action h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .quick-action p {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            border-radius: var(--radius-md);
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
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary {
            background: var(--surface);
            color: var(--text-primary);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--surface-hover);
            border-color: var(--border-dark);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .welcome-stats {
                justify-content: center;
                flex-wrap: wrap;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .top-bar {
                padding: 1rem;
            }

            .top-bar h1 {
                font-size: 1.5rem;
            }

            .page-content {
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .election-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .welcome-section {
                padding: 1.5rem;
            }

            .welcome-stats {
                gap: 1rem;
            }
        }

        /* Animation classes */
        .fade-in {
            animation: fadeIn 0.5s ease forwards;
            opacity: 0;
            transform: translateY(20px);
        }

        @keyframes fadeIn {
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0% { transform: translate(0, 0) scale(1); opacity: 0.7; }
            50% { transform: translate(-2%, -2%) scale(1.05); opacity: 0.8; }
            100% { transform: translate(0, 0) scale(1); opacity: 0.7; }
        }

        .counter-animation {
            animation: countUp 2s ease forwards;
        }

        @keyframes countUp {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="admin_dashboard.php" class="logo">
                    <i class="fas fa-shield-alt"></i>
                    <span>VoteAdmin</span>
                </a>
                <div class="admin-profile">
                    <h4><?php echo htmlspecialchars($admin_user["fullname"]); ?></h4>
                    <p><?php echo htmlspecialchars($admin_user["role"]); ?></p>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="admin_dashboard.php" class="nav-item active">
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
                    <a href="admin_users.php" class="nav-item">
                        <i class="fas fa-user-shield"></i>
                        <span>Admin Users</span>
                    </a>
                    <a href="admin_logs.php" class="nav-item">
                        <i class="fas fa-list-alt"></i>
                        <span>System Logs</span>
                        <?php if ($stats['system_alerts'] > 0): ?>
                        <span class="nav-badge"><?php echo $stats['system_alerts']; ?></span>
                        <?php endif; ?>
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
            <!-- Top Bar -->
            <div class="top-bar">
                <h1>Dashboard Overview</h1>
                <div class="top-bar-actions">
                    <button class="notification-icon" onclick="showNotificationPanel()">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_notifications > 0): ?>
                        <div class="notification-badge"></div>
                        <?php endif; ?>
                    </button>
                    <a href="admin_elections.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        New Election
                    </a>
                </div>
            </div>

            <!-- Page Content -->
            <div class="page-content">
                <!-- Welcome Section -->
                <div class="welcome-section fade-in">
                    <div class="welcome-content">
                        <h2>Welcome back, <?php echo htmlspecialchars($admin_user["fullname"]); ?>!</h2>
                        <p>Here's an overview of your voting system's current status and recent activity.</p>
                        <?php if ($active_election): ?>
                        <div class="welcome-stats">
                            <div class="welcome-stat">
                                <strong class="welcome-stat-value"><?php echo $active_election['total_votes']; ?></strong>
                                <span class="welcome-stat-label">Votes Today</span>
                            </div>
                            <div class="welcome-stat">
                                <strong class="welcome-stat-value"><?php echo number_format(($active_election['total_votes'] / $active_election['eligible_voters']) * 100, 1); ?>%</strong>
                                <span class="welcome-stat-label">Turnout Rate</span>
                            </div>
                            <div class="welcome-stat">
                                <strong class="welcome-stat-value"><?php echo $active_election['candidates']; ?></strong>
                                <span class="welcome-stat-label">Candidates</span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="stats-grid fade-in">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-poll"></i>
                            </div>