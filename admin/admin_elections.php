<?php
// Elections Management - No authentication required for demo
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

// Handle form submissions (simplified without database)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                // In real app, save to database
                header("Location: admin_elections.php?success=created");
                exit();
                break;
                
            case 'update_status':
                // In real app, update database
                header("Location: admin_elections.php?success=updated");
                exit();
                break;
                
            case 'delete':
                // In real app, delete from database
                header("Location: admin_elections.php?success=deleted");
                exit();
                break;
        }
    }
}

// Mock elections data
$elections = [
    [
        "election_id" => 1,
        "title" => "Student Council Elections 2025",
        "description" => "Annual election to choose student representatives for the upcoming academic year. This election will determine leadership for various student organizations and committees.",
        "start_date" => "2025-06-01 09:00:00",
        "end_date" => "2025-06-30 17:00:00",
        "status" => "active",
        "created_by_name" => "Dr. Sarah Johnson",
        "created_at" => "2025-05-15 10:30:00",
        "total_votes" => 324,
        "total_candidates" => 8,
        "eligible_voters" => 1250
    ],
    [
        "election_id" => 2,
        "title" => "Faculty Senate Elections",
        "description" => "Election for faculty representatives to the university senate. Faculty members will vote for their departmental representatives.",
        "start_date" => "2025-07-01 08:00:00",
        "end_date" => "2025-07-15 18:00:00",
        "status" => "upcoming",
        "created_by_name" => "Prof. Michael Davis",
        "created_at" => "2025-05-20 14:15:00",
        "total_votes" => 0,
        "total_candidates" => 12,
        "eligible_voters" => 450
    ],
    [
        "election_id" => 3,
        "title" => "Graduation Committee Elections",
        "description" => "Selection of student representatives for the graduation planning committee.",
        "start_date" => "2025-04-01 10:00:00",
        "end_date" => "2025-04-15 16:00:00",
        "status" => "completed",
        "created_by_name" => "Dr. Sarah Johnson",
        "created_at" => "2025-03-15 11:45:00",
        "total_votes" => 156,
        "total_candidates" => 4,
        "eligible_voters" => 200
    ],
    [
        "election_id" => 4,
        "title" => "Sports Committee Elections",
        "description" => "Annual election for student sports committee representatives.",
        "start_date" => "2025-08-01 09:00:00",
        "end_date" => "2025-08-10 17:00:00",
        "status" => "draft",
        "created_by_name" => "Dr. Sarah Johnson",
        "created_at" => "2025-05-25 16:20:00",
        "total_votes" => 0,
        "total_candidates" => 6,
        "eligible_voters" => 800
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elections Management - Admin Dashboard</title>
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

        .page-content {
            flex: 1;
            padding: 2rem;
        }

        /* Filter Bar */
        .filter-bar {
            background: var(--surface);
            padding: 1.5rem 2rem;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-label {
            font-weight: 500;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .filter-select {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            background: var(--surface);
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .search-box {
            flex: 1;
            max-width: 300px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            background: var(--surface);
            font-size: 0.875rem;
        }

        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-success::before {
            content: "✓";
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.25rem;
            height: 1.25rem;
            background: var(--success);
            color: white;
            border-radius: 50%;
            font-size: 0.75rem;
            font-weight: bold;
        }

        /* Elections Grid */
        .elections-grid {
            display: grid;
            gap: 1.5rem;
        }

        .election-card {
            background: var(--surface);
            border-radius: var(--radius-xl);
            padding: 2rem;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .election-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary);
        }

        .election-card.status-active::before { background: var(--success); }
        .election-card.status-upcoming::before { background: var(--info); }
        .election-card.status-completed::before { background: var(--secondary); }
        .election-card.status-draft::before { background: var(--warning); }

        .election-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .election-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            gap: 1rem;
        }

        .election-info {
            flex: 1;
        }

        .election-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }

        .election-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .election-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .election-meta-item i {
            width: 1rem;
            text-align: center;
            color: var(--text-muted);
        }

        .election-status {
            padding: 0.5rem 1rem;
            border-radius: var(--radius-lg);
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            white-space: nowrap;
        }

        .status-draft {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fbbf24;
        }

        .status-upcoming {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #60a5fa;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #34d399;
        }

        .status-completed {
            background: #f3e8ff;
            color: #6b21a8;
            border: 1px solid #a78bfa;
        }

        .election-description {
            color: var(--text-secondary);
            margin-bottom: 2rem;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .election-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: var(--surface-hover);
            border-radius: var(--radius-lg);
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .election-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
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
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-warning:hover {
            background: #d97706;
        }

        .btn-danger {
            background: var(--error);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--surface);
            padding: 2.5rem;
            border-radius: var(--radius-xl);
            width: 100%;
            max-width: 600px;
            margin: 1rem;
            box-shadow: var(--shadow-lg);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: var(--radius-md);
        }

        .modal-close:hover {
            background: var(--surface-hover);
            color: var(--text-primary);
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-label {
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .form-control {
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 1rem;
            transition: all 0.2s ease;
            background: var(--surface);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
        }

        .form-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        /* Responsive Design */
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
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .page-content {
                padding: 1rem;
            }

            .filter-bar {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }

            .election-header {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }

            .election-actions {
                justify-content: center;
            }

            .modal-content {
                margin: 0.5rem;
                padding: 1.5rem;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .election-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .election-stats {
                grid-template-columns: 1fr;
            }
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
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="admin_dashboard.php" class="nav-item">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="admin_elections.php" class="nav-item active">
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
                <h1>Elections Management</h1>
                <div class="top-bar-actions">
                    <button class="btn btn-secondary" onclick="exportElections()">
                        <i class="fas fa-download"></i>
                        Export
                    </button>
                    <button class="btn btn-primary" onclick="showCreateModal()">
                        <i class="fas fa-plus"></i>
                        Create Election
                    </button>
                </div>
            </div>

            <!-- Page Content -->
            <div class="page-content">
                <!-- Success Messages -->
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        switch($_GET['success']) {
                            case 'created':
                                echo 'Election created successfully!';
                                break;
                            case 'updated':
                                echo 'Election updated successfully!';
                                break;
                            case 'deleted':
                                echo 'Election deleted successfully!';
                                break;
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <!-- Filter Bar -->
                <div class="filter-bar">
                    <div class="filter-group">
                        <span class="filter-label">Status:</span>
                        <select class="filter-select" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="upcoming">Upcoming</option>
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <span class="filter-label">Sort by:</span>
                        <select class="filter-select" id="sortFilter">
                            <option value="created_desc">Newest First</option>
                            <option value="created_asc">Oldest First</option>
                            <option value="start_date_desc">Start Date (Recent)</option>
                            <option value="start_date_asc">Start Date (Earliest)</option>
                            <option value="title_asc">Title (A-Z)</option>
                        </select>
                    </div>
                    
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" placeholder="Search elections..." id="searchInput">
                    </div>
                </div>

                <!-- Elections List -->
                <div class="elections-grid" id="electionsGrid">
                    <?php if (empty($elections)): ?>
                        <div class="empty-state">
                            <i class="fas fa-poll"></i>
                            <h3>No Elections Found</h3>
                            <p>Get started by creating your first election.</p>
                            <button class="btn btn-primary" onclick="showCreateModal()" style="margin-top: 1rem;">
                                <i class="fas fa-plus"></i>
                                Create Election
                            </button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($elections as $election): ?>
                            <div class="election-card status-<?php echo $election['status']; ?>" data-status="<?php echo $election['status']; ?>" data-title="<?php echo strtolower($election['title']); ?>">
                                <div class="election-header">
                                    <div class="election-info">
                                        <h3 class="election-title"><?php echo htmlspecialchars($election['title']); ?></h3>
                                        <div class="election-meta">
                                            <div class="election-meta-item">
                                                <i class="fas fa-user"></i>
                                                <span>Created by: <?php echo htmlspecialchars($election['created_by_name']); ?></span>
                                            </div>
                                            <div class="election-meta-item">
                                                <i class="fas fa-calendar-alt"></i>
                                                <span>Start: <?php echo date('M d, Y \a\t H:i', strtotime($election['start_date'])); ?></span>
                                            </div>
                                            <div class="election-meta-item">
                                                <i class="fas fa-calendar-check"></i>
                                                <span>End: <?php echo date('M d, Y \a\t H:i', strtotime($election['end_date'])); ?></span>
                                            </div>
                                            <div class="election-meta-item">
                                                <i class="fas fa-clock"></i>
                                                <span>Created: <?php echo date('M d, Y', strtotime($election['created_at'])); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="election-status status-<?php echo $election['status']; ?>">
                                        <?php echo ucfirst($election['status']); ?>
                                    </span>
                                </div>
                                
                                <p class="election-description">
                                    <?php echo htmlspecialchars($election['description']); ?>
                                </p>

                                <div class="election-stats">
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo number_format($election['total_votes']); ?></div>
                                        <div class="stat-label">Votes Cast</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo $election['total_candidates']; ?></div>
                                        <div class="stat-label">Candidates</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo number_format($election['eligible_voters']); ?></div>
                                        <div class="stat-label">Eligible Voters</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo $election['total_votes'] > 0 ? number_format(($election['total_votes'] / $election['eligible_voters']) * 100, 1) : '0'; ?>%</div>
                                        <div class="stat-label">Turnout</div>
                                    </div>
                                </div>
                                
                                <div class="election-actions">
                                    <a href="admin_election_details.php?id=<?php echo $election['election_id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-eye"></i>
                                        View Details
                                    </a>
                                    <button class="btn btn-secondary" onclick="editElection(<?php echo $election['election_id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                        Edit
                                    </button>
                                    
                                    <?php if ($election['status'] === 'draft'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="election_id" value="<?php echo $election['election_id']; ?>">
                                            <input type="hidden" name="status" value="upcoming">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-check"></i>
                                                Publish
                                            </button>
                                        </form>
                                    <?php elseif ($election['status'] === 'upcoming'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="election_id" value="<?php echo $election['election_id']; ?>">
                                            <input type="hidden" name="status" value="active">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-play"></i>
                                                Start Now
                                            </button>
                                        </form>
                                    <?php elseif ($election['status'] === 'active'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="election_id" value="<?php echo $election['election_id']; ?>">
                                            <input type="hidden" name="status" value="completed">
                                            <button type="submit" class="btn btn-warning">
                                                <i class="fas fa-stop"></i>
                                                End Election
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($election['status'] === 'draft'): ?>
                                        <button class="btn btn-danger" onclick="deleteElection(<?php echo $election['election_id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                            Delete
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Create Election Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Create New Election</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            
            <form method="POST" class="form-grid">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label class="form-label">Election Title *</label>
                    <input type="text" name="title" class="form-control" placeholder="Enter election title..." required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description *</label>
                    <textarea name="description" class="form-control" placeholder="Describe the purpose and details of this election..." required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Start Date & Time *</label>
                        <input type="datetime-local" name="start_date" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">End Date & Time *</label>
                        <input type="datetime-local" name="end_date" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Election Type</label>
                    <select name="election_type" class="form-control">
                        <option value="general">General Election</option>
                        <option value="student_council">Student Council</option>
                        <option value="faculty">Faculty Election</option>
                        <option value="committee">Committee Election</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Election</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h2 class="modal-title">Confirm Delete</h2>
                <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            
            <div style="margin-bottom: 2rem;">
                <p>Are you sure you want to delete this election? This action cannot be undone.</p>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="election_id" id="deleteElectionId">
                    <button type="submit" class="btn btn-danger">Delete Election</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            initializeFilters();
            initializeMobile();
        });

        function initializeFilters() {
            const statusFilter = document.getElementById('statusFilter');
            const sortFilter = document.getElementById('sortFilter');
            const searchInput = document.getElementById('searchInput');

            statusFilter.addEventListener('change', filterElections);
            sortFilter.addEventListener('change', filterElections);
            searchInput.addEventListener('input', filterElections);
        }

        function initializeMobile() {
            // Add mobile sidebar toggle
            if (window.innerWidth <= 768) {
                const sidebarToggle = document.createElement('button');
                sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
                sidebarToggle.className = 'btn btn-secondary';
                sidebarToggle.style.marginRight = '1rem';
                
                document.querySelector('.top-bar h1').parentNode.insertBefore(sidebarToggle, document.querySelector('.top-bar h1'));
                
                sidebarToggle.addEventListener('click', function() {
                    document.getElementById('sidebar').classList.toggle('open');
                });
            }
        }

        function filterElections() {
            const statusFilter = document.getElementById('statusFilter').value;
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const cards = document.querySelectorAll('.election-card');

            cards.forEach(card => {
                const status = card.dataset.status;
                const title = card.dataset.title;
                
                const statusMatch = !statusFilter || status === statusFilter;
                const searchMatch = !searchTerm || title.includes(searchTerm);
                
                if (statusMatch && searchMatch) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function showCreateModal() {
            document.getElementById('createModal').classList.add('active');
            // Set minimum dates to today
            const now = new Date();
            const dateString = now.toISOString().slice(0, 16);
            document.querySelector('input[name="start_date"]').min = dateString;
            document.querySelector('input[name="end_date"]').min = dateString;
        }
        
        function closeModal() {
            document.getElementById('createModal').classList.remove('active');
        }
        
        function editElection(id) {
            window.location.href = `admin_edit_election.php?id=${id}`;
        }

        function deleteElection(id) {
            document.getElementById('deleteElectionId').value = id;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }

        function exportElections() {
            // In a real app, this would generate and download a CSV/Excel file
            showNotification('Export feature coming soon!', 'info');
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-${type === 'info' ? 'info-circle' : 'check-circle'}"></i>
                    <span>${message}</span>
                </div>
            `;
            
            notification.style.cssText = `
                position: fixed;
                top: 2rem;
                right: 2rem;
                background: white;
                padding: 1rem 1.5rem;
                border-radius: 0.5rem;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                border-left: 4px solid var(--${type === 'info' ? 'info' : 'success'});
                z-index: 1000;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease;
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 5000);
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.classList.remove('active');
                }
            });
        }

        // Close modals with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.classList.remove('active');
                });
            }
        });

        // Auto-hide success alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });

        // Validate form dates
        document.querySelector('input[name="start_date"]').addEventListener('change', function() {
            const startDate = new Date(this.value);
            const endDateInput = document.querySelector('input[name="end_date"]');
            endDateInput.min = this.value;
            
            if (endDateInput.value && new Date(endDateInput.value) <= startDate) {
                endDateInput.value = '';
            }
        });
    </script>
</body>
</html>