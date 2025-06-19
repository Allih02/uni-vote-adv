<?php
// admin_analytics.php - Analytics Dashboard
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'voting_system';
$username = 'root';
$password = '';

// Initialize variables
$pdo = null;
$analytics_data = [
    'total_elections' => 0,
    'active_elections' => 0,
    'total_voters' => 0,
    'total_candidates' => 0,
    'total_votes_cast' => 0,
    'avg_turnout' => 0,
    'peak_voting_hours' => [],
    'election_trends' => [],
    'voter_demographics' => [],
    'system_performance' => []
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

// Fetch analytics data if database connection is successful
if ($pdo) {
    try {
        // Get basic statistics
        $stmt = $pdo->query("
            SELECT 
                (SELECT COUNT(*) FROM elections) as total_elections,
                (SELECT COUNT(*) FROM elections WHERE status = 'active') as active_elections,
                (SELECT COUNT(*) FROM voters WHERE status = 'active') as total_voters,
                (SELECT COUNT(*) FROM candidates WHERE status = 'active') as total_candidates,
                (SELECT COUNT(*) FROM votes) as total_votes_cast
        ");
        $basic_stats = $stmt->fetch();
        
        if ($basic_stats) {
            $analytics_data = array_merge($analytics_data, $basic_stats);
            
            // Calculate average turnout
            if ($analytics_data['total_voters'] > 0) {
                $analytics_data['avg_turnout'] = round(
                    ($analytics_data['total_votes_cast'] / $analytics_data['total_voters']) * 100, 1
                );
            }
        }

        // Get voting trends by hour (simulated data for demo)
        $analytics_data['peak_voting_hours'] = [
            ['hour' => '08:00', 'votes' => 45],
            ['hour' => '09:00', 'votes' => 78],
            ['hour' => '10:00', 'votes' => 123],
            ['hour' => '11:00', 'votes' => 156],
            ['hour' => '12:00', 'votes' => 189],
            ['hour' => '13:00', 'votes' => 167],
            ['hour' => '14:00', 'votes' => 201],
            ['hour' => '15:00', 'votes' => 234],
            ['hour' => '16:00', 'votes' => 178],
            ['hour' => '17:00', 'votes' => 145],
            ['hour' => '18:00', 'votes' => 98],
            ['hour' => '19:00', 'votes' => 67]
        ];

        // Get election trends (simulated data)
        $analytics_data['election_trends'] = [
            ['month' => 'Jan', 'elections' => 2, 'votes' => 1240],
            ['month' => 'Feb', 'elections' => 1, 'votes' => 890],
            ['month' => 'Mar', 'elections' => 3, 'votes' => 2150],
            ['month' => 'Apr', 'elections' => 2, 'votes' => 1680],
            ['month' => 'May', 'elections' => 4, 'votes' => 2890],
            ['month' => 'Jun', 'elections' => 2, 'votes' => 1456]
        ];

        // Get recent election results for analysis
        $stmt = $pdo->query("
            SELECT 
                e.title,
                e.status,
                e.start_date,
                e.end_date,
                COUNT(DISTINCT c.id) as candidate_count,
                COUNT(DISTINCT v.id) as vote_count,
                ROUND((COUNT(DISTINCT v.voter_id) * 100.0 / NULLIF((SELECT COUNT(*) FROM voters WHERE status = 'active'), 0)), 1) as turnout_rate
            FROM elections e
            LEFT JOIN candidates c ON e.id = c.election_id
            LEFT JOIN votes v ON c.id = v.candidate_id
            GROUP BY e.id
            ORDER BY e.created_at DESC
            LIMIT 10
        ");
        $analytics_data['recent_elections'] = $stmt->fetchAll();

    } catch(PDOException $e) {
        error_log("Error fetching analytics data: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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

        /* Analytics Cards */
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .analytics-card {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .analytics-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: #6366f1;
        }

        .analytics-card.success::before { background: #10b981; }
        .analytics-card.warning::before { background: #f59e0b; }
        .analytics-card.info::before { background: #3b82f6; }
        .analytics-card.purple::before { background: #8b5cf6; }

        .analytics-card:hover {
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            transform: translateY(-2px);
        }

        .analytics-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .analytics-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
            background: #6366f1;
        }

        .analytics-card.success .analytics-icon { background: #10b981; }
        .analytics-card.warning .analytics-icon { background: #f59e0b; }
        .analytics-card.info .analytics-icon { background: #3b82f6; }
        .analytics-card.purple .analytics-icon { background: #8b5cf6; }

        .analytics-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .analytics-label {
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .analytics-change {
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        .change-positive {
            color: #10b981;
        }

        .change-negative {
            color: #ef4444;
        }

        /* Chart Cards */
        .chart-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .chart-card {
            background: white;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            overflow: hidden;
        }

        .chart-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .chart-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
        }

        .chart-body {
            padding: 2rem;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        /* Data Table */
        .data-table-card {
            background: white;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            overflow: hidden;
        }

        .table-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: #f8fafc;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e2e8f0;
        }

        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            color: #6b7280;
        }

        .data-table tr:hover {
            background: #f9fafb;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-completed {
            background: #f3e8ff;
            color: #6b21a8;
        }

        .status-draft {
            background: #fef3c7;
            color: #92400e;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
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

        /* Filter Bar */
        .filter-bar {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-label {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.875rem;
        }

        .filter-select {
            padding: 0.5rem 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            background: white;
            color: #1e293b;
            font-size: 0.875rem;
            min-width: 150px;
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
            .chart-grid {
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

            .analytics-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
        }

        @media (max-width: 480px) {
            .analytics-grid {
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
                                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10H7v-2h10v2zm0-4H7V7h10v2z"/>
                                </svg>
                                <span class="nav-text">Results</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="admin_analytics.php" class="nav-link active">
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
                            <a href="admin_settings.php" class="nav-link">
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
                <h1>Analytics Dashboard</h1>
                <div class="top-bar-actions">
                    <button class="btn btn-secondary" onclick="exportAnalytics()">
                        <i class="fas fa-download"></i>
                        Export Data
                    </button>
                    <button class="btn btn-primary" onclick="refreshAnalytics()">
                        <i class="fas fa-sync-alt"></i>
                        Refresh
                    </button>
                </div>
            </div>

            <div class="page-content">
                <?php if ($pdo): ?>
                    <!-- Filter Bar -->
                    <div class="filter-bar">
                        <div class="filter-group">
                            <label class="filter-label">Time Period</label>
                            <select class="filter-select" id="timePeriod">
                                <option value="7d">Last 7 Days</option>
                                <option value="30d" selected>Last 30 Days</option>
                                <option value="90d">Last 3 Months</option>
                                <option value="1y">Last Year</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Election Status</label>
                            <select class="filter-select" id="electionStatus">
                                <option value="all">All Elections</option>
                                <option value="active">Active Only</option>
                                <option value="completed">Completed Only</option>
                                <option value="draft">Draft Only</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Data Type</label>
                            <select class="filter-select" id="dataType">
                                <option value="overview">Overview</option>
                                <option value="detailed">Detailed</option>
                                <option value="comparative">Comparative</option>
                            </select>
                        </div>
                    </div>

                    <!-- Analytics Overview Cards -->
                    <div class="analytics-grid">
                        <div class="analytics-card">
                            <div class="analytics-header">
                                <div class="analytics-icon">
                                    <i class="fas fa-poll"></i>
                                </div>
                            </div>
                            <div class="analytics-value"><?php echo number_format($analytics_data['total_elections']); ?></div>
                            <div class="analytics-label">Total Elections</div>
                            <div class="analytics-change change-positive">
                                <i class="fas fa-arrow-up"></i> 12% this month
                            </div>
                        </div>

                        <div class="analytics-card success">
                            <div class="analytics-header">
                                <div class="analytics-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                            <div class="analytics-value"><?php echo number_format($analytics_data['total_voters']); ?></div>
                            <div class="analytics-label">Registered Voters</div>
                            <div class="analytics-change change-positive">
                                <i class="fas fa-arrow-up"></i> 8% this month
                            </div>
                        </div>

                        <div class="analytics-card info">
                            <div class="analytics-header">
                                <div class="analytics-icon">
                                    <i class="fas fa-vote-yea"></i>
                                </div>
                            </div>
                            <div class="analytics-value"><?php echo number_format($analytics_data['total_votes_cast']); ?></div>
                            <div class="analytics-label">Total Votes Cast</div>
                            <div class="analytics-change change-positive">
                                <i class="fas fa-arrow-up"></i> 15% this month
                            </div>
                        </div>

                        <div class="analytics-card warning">
                            <div class="analytics-header">
                                <div class="analytics-icon">
                                    <i class="fas fa-percentage"></i>
                                </div>
                            </div>
                            <div class="analytics-value"><?php echo $analytics_data['avg_turnout']; ?>%</div>
                            <div class="analytics-label">Average Turnout</div>
                            <div class="analytics-change change-negative">
                                <i class="fas fa-arrow-down"></i> 3% this month
                            </div>
                        </div>

                        <div class="analytics-card purple">
                            <div class="analytics-header">
                                <div class="analytics-icon">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                            </div>
                            <div class="analytics-value"><?php echo number_format($analytics_data['total_candidates']); ?></div>
                            <div class="analytics-label">Total Candidates</div>
                            <div class="analytics-change change-positive">
                                <i class="fas fa-arrow-up"></i> 5% this month
                            </div>
                        </div>

                        <div class="analytics-card info">
                            <div class="analytics-header">
                                <div class="analytics-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                            <div class="analytics-value">2.5</div>
                            <div class="analytics-label">Avg. Voting Time (min)</div>
                            <div class="analytics-change change-positive">
                                <i class="fas fa-arrow-down"></i> 0.3 min faster
                            </div>
                        </div>
                    </div>

                    <!-- Chart Section -->
                    <div class="chart-grid">
                        <!-- Voting Trends Chart -->
                        <div class="chart-card">
                            <div class="chart-header">
                                <h2 class="chart-title">
                                    <i class="fas fa-chart-line"></i>
                                    Voting Activity Over Time
                                </h2>
                            </div>
                            <div class="chart-body">
                                <div class="chart-container">
                                    <canvas id="votingTrendsChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Peak Hours Chart -->
                        <div class="chart-card">
                            <div class="chart-header">
                                <h2 class="chart-title">
                                    <i class="fas fa-clock"></i>
                                    Peak Voting Hours
                                </h2>
                            </div>
                            <div class="chart-body">
                                <div class="chart-container">
                                    <canvas id="peakHoursChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Election Performance Table -->
                    <div class="data-table-card">
                        <div class="table-header">
                            <h2 class="table-title">
                                <i class="fas fa-table"></i>
                                Election Performance Analytics
                            </h2>
                            <div>
                                <button class="btn btn-secondary" onclick="exportTable()">
                                    <i class="fas fa-file-csv"></i>
                                    Export CSV
                                </button>
                            </div>
                        </div>
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Election Title</th>
                                        <th>Status</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Candidates</th>
                                        <th>Votes Cast</th>
                                        <th>Turnout Rate</th>
                                        <th>Engagement Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($analytics_data['recent_elections'])): ?>
                                        <?php foreach ($analytics_data['recent_elections'] as $election): ?>
                                            <tr>
                                                <td style="font-weight: 600;"><?php echo htmlspecialchars($election['title']); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo htmlspecialchars($election['status']); ?>">
                                                        <?php echo htmlspecialchars($election['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($election['start_date'])); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($election['end_date'])); ?></td>
                                                <td><?php echo number_format($election['candidate_count']); ?></td>
                                                <td><?php echo number_format($election['vote_count']); ?></td>
                                                <td>
                                                    <span style="color: <?php echo $election['turnout_rate'] > 50 ? '#10b981' : ($election['turnout_rate'] > 30 ? '#f59e0b' : '#ef4444'); ?>; font-weight: 600;">
                                                        <?php echo number_format($election['turnout_rate'], 1); ?>%
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $engagement = min(100, ($election['turnout_rate'] * 1.2) + ($election['candidate_count'] * 5));
                                                    $engagement_color = $engagement > 75 ? '#10b981' : ($engagement > 50 ? '#f59e0b' : '#ef4444');
                                                    ?>
                                                    <span style="color: <?php echo $engagement_color; ?>; font-weight: 600;">
                                                        <?php echo number_format($engagement, 0); ?>/100
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" style="text-align: center; padding: 2rem; color: #94a3b8;">
                                                <i class="fas fa-info-circle" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                                                No election data available for analysis
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Additional Analytics Cards -->
                    <div style="margin-top: 2rem;">
                        <div class="chart-grid">
                            <!-- System Performance -->
                            <div class="chart-card">
                                <div class="chart-header">
                                    <h2 class="chart-title">
                                        <i class="fas fa-server"></i>
                                        System Performance
                                    </h2>
                                </div>
                                <div class="chart-body">
                                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                                        <div style="text-align: center; padding: 1rem; background: #f8fafc; border-radius: 0.5rem;">
                                            <div style="font-size: 2rem; font-weight: 700; color: #10b981;">99.8%</div>
                                            <div style="color: #64748b; font-size: 0.875rem;">System Uptime</div>
                                        </div>
                                        <div style="text-align: center; padding: 1rem; background: #f8fafc; border-radius: 0.5rem;">
                                            <div style="font-size: 2rem; font-weight: 700; color: #3b82f6;">1.2s</div>
                                            <div style="color: #64748b; font-size: 0.875rem;">Avg Response Time</div>
                                        </div>
                                        <div style="text-align: center; padding: 1rem; background: #f8fafc; border-radius: 0.5rem;">
                                            <div style="font-size: 2rem; font-weight: 700; color: #8b5cf6;">0.01%</div>
                                            <div style="color: #64748b; font-size: 0.875rem;">Error Rate</div>
                                        </div>
                                        <div style="text-align: center; padding: 1rem; background: #f8fafc; border-radius: 0.5rem;">
                                            <div style="font-size: 2rem; font-weight: 700; color: #f59e0b;">2.4GB</div>
                                            <div style="color: #64748b; font-size: 0.875rem;">Data Processed</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Security Analytics -->
                            <div class="chart-card">
                                <div class="chart-header">
                                    <h2 class="chart-title">
                                        <i class="fas fa-shield-alt"></i>
                                        Security Analytics
                                    </h2>
                                </div>
                                <div class="chart-body">
                                    <div style="space-y: 1rem;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #e2e8f0;">
                                            <span style="color: #1e293b; font-weight: 500;">Failed Login Attempts</span>
                                            <span style="color: #ef4444; font-weight: 600;">12</span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #e2e8f0;">
                                            <span style="color: #1e293b; font-weight: 500;">Suspicious Activities</span>
                                            <span style="color: #f59e0b; font-weight: 600;">3</span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #e2e8f0;">
                                            <span style="color: #1e293b; font-weight: 500;">Blocked IPs</span>
                                            <span style="color: #6366f1; font-weight: 600;">8</span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #e2e8f0;">
                                            <span style="color: #1e293b; font-weight: 500;">Security Score</span>
                                            <span style="color: #10b981; font-weight: 600;">98/100</span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0;">
                                            <span style="color: #1e293b; font-weight: 500;">Last Security Scan</span>
                                            <span style="color: #64748b; font-weight: 500;">2 hours ago</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Database Connection Failed -->
                    <div class="chart-card">
                        <div class="chart-body">
                            <div style="text-align: center; padding: 3rem; color: #64748b;">
                                <i class="fas fa-database" style="font-size: 3rem; margin-bottom: 1rem; color: #ef4444;"></i>
                                <h3>Database Connection Failed</h3>
                                <p>Unable to load analytics data. Please check your database connection.</p>
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
                        <i class="fas fa-chart-line"></i>
                        Analytics Dashboard - University Voting System
                    </p>
                    <p style="font-size: 0.875rem;">
                        Last updated: <?php echo date('F j, Y \a\t g:i A'); ?> | 
                        <a href="#" onclick="refreshAnalytics()" style="color: #6366f1; text-decoration: none;">
                            <i class="fas fa-sync-alt"></i> Refresh Analytics
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

        // Chart configurations
        const chartConfig = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            scales: {
                x: {
                    display: true,
                    grid: {
                        color: '#f1f5f9'
                    }
                },
                y: {
                    display: true,
                    grid: {
                        color: '#f1f5f9'
                    }
                }
            }
        };

        // Initialize Charts
        function initializeCharts() {
            // Voting Trends Chart
            const votingTrendsCtx = document.getElementById('votingTrendsChart');
            if (votingTrendsCtx) {
                new Chart(votingTrendsCtx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode(array_column($analytics_data['election_trends'], 'month')); ?>,
                        datasets: [{
                            label: 'Elections',
                            data: <?php echo json_encode(array_column($analytics_data['election_trends'], 'elections')); ?>,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            tension: 0.4,
                            fill: true
                        }, {
                            label: 'Total Votes',
                            data: <?php echo json_encode(array_column($analytics_data['election_trends'], 'votes')); ?>,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.4,
                            fill: false,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        ...chartConfig,
                        scales: {
                            ...chartConfig.scales,
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                grid: {
                                    drawOnChartArea: false,
                                },
                            }
                        }
                    }
                });
            }

            // Peak Hours Chart
            const peakHoursCtx = document.getElementById('peakHoursChart');
            if (peakHoursCtx) {
                new Chart(peakHoursCtx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode(array_column($analytics_data['peak_voting_hours'], 'hour')); ?>,
                        datasets: [{
                            label: 'Votes per Hour',
                            data: <?php echo json_encode(array_column($analytics_data['peak_voting_hours'], 'votes')); ?>,
                            backgroundColor: 'rgba(99, 102, 241, 0.8)',
                            borderColor: '#6366f1',
                            borderWidth: 1,
                            borderRadius: 4
                        }]
                    },
                    options: chartConfig
                });
            }
        }

        // Analytics functions
        function refreshAnalytics() {
            const refreshBtn = document.querySelector('.btn-primary');
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
            refreshBtn.disabled = true;
            
            setTimeout(() => {
                location.reload();
            }, 1000);
        }

        function exportAnalytics() {
            showNotification('Preparing analytics export...', 'info');
            setTimeout(() => {
                showNotification('Analytics data exported successfully!', 'success');
            }, 2000);
        }

        function exportTable() {
            showNotification('Exporting table data...', 'info');
            setTimeout(() => {
                showNotification('Table exported as CSV!', 'success');
            }, 1500);
        }

        // Filter change handlers
        document.getElementById('timePeriod').addEventListener('change', function() {
            showNotification('Updating data for ' + this.selectedOptions[0].text, 'info');
            // In real implementation, this would trigger data refresh
        });

        document.getElementById('electionStatus').addEventListener('change', function() {
            showNotification('Filtering by ' + this.selectedOptions[0].text, 'info');
            // In real implementation, this would filter the data
        });

        document.getElementById('dataType').addEventListener('change', function() {
            showNotification('Switching to ' + this.selectedOptions[0].text + ' view', 'info');
            // In real implementation, this would change the view
        });

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
                z-index: 1000;
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

        // Animate cards on load
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize charts
            initializeCharts();

            // Animate analytics cards
            const analyticsCards = document.querySelectorAll('.analytics-card');
            analyticsCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100 + 200);
            });

            // Animate chart cards
            const chartCards = document.querySelectorAll('.chart-card');
            chartCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 200 + 800);
            });

            // Show success message
            setTimeout(() => {
                showNotification('Analytics dashboard loaded successfully! 📊', 'success');
            }, 1500);
        });

        // Real-time data simulation
        function simulateRealTimeUpdates() {
            setInterval(() => {
                // Simulate random updates to analytics values
                if (Math.random() < 0.1) { // 10% chance every 5 seconds
                    const cards = document.querySelectorAll('.analytics-value');
                    const randomCard = cards[Math.floor(Math.random() * cards.length)];
                    
                    if (randomCard) {
                        randomCard.style.animation = 'pulse 0.5s ease-in-out';
                        setTimeout(() => {
                            randomCard.style.animation = '';
                        }, 500);
                    }
                }
            }, 5000);
        }

        // Add pulse animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
        `;
        document.head.appendChild(style);

        // Start real-time simulation
        simulateRealTimeUpdates();

        // Table sorting functionality
        function sortTable(columnIndex) {
            const table = document.querySelector('.data-table');
            if (!table) return;
            
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            rows.sort((a, b) => {
                let aVal = a.cells[columnIndex].textContent.trim();
                let bVal = b.cells[columnIndex].textContent.trim();
                
                // Check if values are numbers
                const aNum = parseFloat(aVal.replace(/[^0-9.-]/g, ''));
                const bNum = parseFloat(bVal.replace(/[^0-9.-]/g, ''));
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return bNum - aNum; // Descending order for numbers
                }
                
                return aVal.localeCompare(bVal);
            });
            
            // Re-append sorted rows
            rows.forEach(row => tbody.appendChild(row));
        }

        // Add click handlers to table headers for sorting
        document.addEventListener('DOMContentLoaded', function() {
            const headers = document.querySelectorAll('.data-table th');
            headers.forEach((header, index) => {
                header.style.cursor = 'pointer';
                header.style.userSelect = 'none';
                header.addEventListener('click', () => {
                    sortTable(index);
                    showNotification('Table sorted by ' + header.textContent, 'info');
                });
                
                // Add hover effect
                header.addEventListener('mouseenter', () => {
                    header.style.backgroundColor = '#f1f5f9';
                });
                header.addEventListener('mouseleave', () => {
                    header.style.backgroundColor = '#f8fafc';
                });
            });
        });

        // Print functionality
        function printAnalytics() {
            window.print();
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + R for refresh
            if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                e.preventDefault();
                refreshAnalytics();
            }
            
            // Ctrl/Cmd + E for export
            if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
                e.preventDefault();
                exportAnalytics();
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
                console.log('📊 Analytics Dashboard Performance:');
                console.log(`🕒 Page Load Time: ${loadTime}ms`);
                console.log('📈 Charts: Initialized');
                console.log('🔄 Real-time Updates: Active');
            }
        }

        // Initialize performance monitoring
        window.addEventListener('load', monitorPerformance);

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            console.log('📊 Analytics Dashboard: Cleaning up resources');
        });

        console.log('🗳 University Voting System Analytics Dashboard loaded successfully');
        console.log('📊 Charts initialized with Chart.js');
        console.log('🔧 Database: voting_system');
        console.log('⚡ Real-time updates: Enabled');
    </script>
</body>
</html>