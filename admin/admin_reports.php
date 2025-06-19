<?php
// admin_reports.php - Reports Management Dashboard
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'voting_system';
$username = 'root';
$password = '';

// Initialize variables
$pdo = null;
$reports_data = [
    'scheduled_reports' => [],
    'recent_reports' => [],
    'report_stats' => [
        'total_generated' => 0,
        'pending_reports' => 0,
        'scheduled_reports' => 0,
        'failed_reports' => 0
    ]
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

// Fetch reports data if database connection is successful
if ($pdo) {
    try {
        // Get elections for report generation
        $stmt = $pdo->query("
            SELECT 
                id, 
                title, 
                status, 
                start_date, 
                end_date,
                (SELECT COUNT(*) FROM candidates WHERE election_id = elections.id) as candidate_count,
                (SELECT COUNT(*) FROM votes v JOIN candidates c ON v.candidate_id = c.id WHERE c.election_id = elections.id) as vote_count
            FROM elections 
            ORDER BY created_at DESC
        ");
        $elections = $stmt->fetchAll();

        // Simulate report statistics (in real app, this would be from reports table)
        $reports_data['report_stats'] = [
            'total_generated' => 156,
            'pending_reports' => 3,
            'scheduled_reports' => 8,
            'failed_reports' => 2
        ];

        // Simulate recent reports data
        $reports_data['recent_reports'] = [
            [
                'id' => 1,
                'name' => 'Monthly Election Summary',
                'type' => 'election_summary',
                'election_id' => !empty($elections) ? $elections[0]['id'] : 1,
                'election_title' => !empty($elections) ? $elections[0]['title'] : 'Sample Election',
                'generated_date' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                'status' => 'completed',
                'file_size' => '2.4 MB',
                'download_count' => 12
            ],
            [
                'id' => 2,
                'name' => 'Voter Turnout Analysis',
                'type' => 'voter_analysis',
                'election_id' => !empty($elections) ? $elections[0]['id'] : 1,
                'election_title' => 'All Elections',
                'generated_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'status' => 'completed',
                'file_size' => '1.8 MB',
                'download_count' => 8
            ],
            [
                'id' => 3,
                'name' => 'Security Audit Report',
                'type' => 'security_audit',
                'election_id' => null,
                'election_title' => 'System-wide',
                'generated_date' => date('Y-m-d H:i:s', strtotime('-3 days')),
                'status' => 'completed',
                'file_size' => '980 KB',
                'download_count' => 15
            ],
            [
                'id' => 4,
                'name' => 'Candidate Performance Report',
                'type' => 'candidate_report',
                'election_id' => !empty($elections) && count($elections) > 1 ? $elections[1]['id'] : 2,
                'election_title' => !empty($elections) && count($elections) > 1 ? $elections[1]['title'] : 'Previous Election',
                'generated_date' => date('Y-m-d H:i:s', strtotime('-1 week')),
                'status' => 'completed',
                'file_size' => '3.2 MB',
                'download_count' => 6
            ],
            [
                'id' => 5,
                'name' => 'System Performance Report',
                'type' => 'system_performance',
                'election_id' => null,
                'election_title' => 'System-wide',
                'generated_date' => date('Y-m-d H:i:s', strtotime('-2 weeks')),
                'status' => 'pending',
                'file_size' => '-',
                'download_count' => 0
            ]
        ];

        // Simulate scheduled reports
        $reports_data['scheduled_reports'] = [
            [
                'id' => 1,
                'name' => 'Weekly Election Summary',
                'type' => 'election_summary',
                'schedule' => 'weekly',
                'next_run' => date('Y-m-d H:i:s', strtotime('+3 days')),
                'last_run' => date('Y-m-d H:i:s', strtotime('-4 days')),
                'status' => 'active',
                'recipients' => 'admin@university.edu, dean@university.edu'
            ],
            [
                'id' => 2,
                'name' => 'Monthly Voter Statistics',
                'type' => 'voter_analysis',
                'schedule' => 'monthly',
                'next_run' => date('Y-m-d H:i:s', strtotime('+15 days')),
                'last_run' => date('Y-m-d H:i:s', strtotime('-15 days')),
                'status' => 'active',
                'recipients' => 'admin@university.edu'
            ],
            [
                'id' => 3,
                'name' => 'Daily Security Report',
                'type' => 'security_audit',
                'schedule' => 'daily',
                'next_run' => date('Y-m-d H:i:s', strtotime('+6 hours')),
                'last_run' => date('Y-m-d H:i:s', strtotime('-18 hours')),
                'status' => 'active',
                'recipients' => 'security@university.edu, admin@university.edu'
            ]
        ];

    } catch(PDOException $e) {
        error_log("Error fetching reports data: " . $e->getMessage());
    }
}

// Available report types
$report_types = [
    'election_summary' => [
        'name' => 'Election Summary Report',
        'description' => 'Comprehensive overview of election results, voter turnout, and candidate performance',
        'icon' => 'fas fa-poll',
        'estimated_time' => '2-3 minutes'
    ],
    'voter_analysis' => [
        'name' => 'Voter Analysis Report',
        'description' => 'Detailed voter demographics, participation patterns, and turnout analysis',
        'icon' => 'fas fa-users',
        'estimated_time' => '3-5 minutes'
    ],
    'candidate_report' => [
        'name' => 'Candidate Performance Report',
        'description' => 'Individual candidate statistics, vote distribution, and performance metrics',
        'icon' => 'fas fa-user-tie',
        'estimated_time' => '2-4 minutes'
    ],
    'security_audit' => [
        'name' => 'Security Audit Report',
        'description' => 'System security analysis, access logs, and potential security issues',
        'icon' => 'fas fa-shield-alt',
        'estimated_time' => '5-7 minutes'
    ],
    'system_performance' => [
        'name' => 'System Performance Report',
        'description' => 'Technical performance metrics, system health, and optimization recommendations',
        'icon' => 'fas fa-server',
        'estimated_time' => '3-6 minutes'
    ],
    'compliance_report' => [
        'name' => 'Compliance Report',
        'description' => 'Regulatory compliance status, audit trail, and compliance recommendations',
        'icon' => 'fas fa-clipboard-check',
        'estimated_time' => '4-8 minutes'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports Management - Admin Panel</title>
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

        /* Report Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stats-card {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: #6366f1;
        }

        .stats-card.success::before { background: #10b981; }
        .stats-card.warning::before { background: #f59e0b; }
        .stats-card.danger::before { background: #ef4444; }

        .stats-card:hover {
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            transform: translateY(-2px);
        }

        .stats-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stats-icon {
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

        .stats-card.success .stats-icon { background: #10b981; }
        .stats-card.warning .stats-icon { background: #f59e0b; }
        .stats-card.danger .stats-icon { background: #ef4444; }

        .stats-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .stats-label {
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Quick Actions Section */
        .quick-actions {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            margin-bottom: 2rem;
        }

        .quick-actions h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .report-types-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .report-type-card {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .report-type-card:hover {
            background: white;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            transform: translateY(-2px);
        }

        .report-type-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .report-type-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            background: #6366f1;
        }

        .report-type-info h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .report-type-info .time-estimate {
            font-size: 0.75rem;
            color: #64748b;
            font-weight: 500;
        }

        .report-type-description {
            color: #64748b;
            font-size: 0.875rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }

        .report-type-actions {
            display: flex;
            gap: 0.75rem;
        }

        /* Tables */
        .table-section {
            background: white;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            overflow: hidden;
            margin-bottom: 2rem;
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
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

        .status-completed {
            background: #dcfce7;
            color: #166534;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-failed {
            background: #fecaca;
            color: #991b1b;
        }

        .status-active {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-inactive {
            background: #f3f4f6;
            color: #6b7280;
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

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 2000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal.active {
            opacity: 1;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #6b7280;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 0.25rem;
            transition: all 0.2s ease;
        }

        .modal-close:hover {
            background: #f3f4f6;
            color: #374151;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            background: white;
            color: #1e293b;
            font-size: 0.875rem;
            transition: border-color 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
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
            .report-types-grid {
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

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .modal-content {
                width: 95%;
                padding: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .report-type-actions {
                flex-direction: column;
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
                            <a href="admin_analytics.php" class="nav-link">
                                <svg class="nav-icon" viewBox="0 0 24 24">
                                    <path d="M9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4zm2.5 2.25l1.25-1.25-2.75-2.75-.75.75L19.5 17.25zM12 2C6.48 2 2 6.48 2 12s4.48 10 10 10c1.19 0 2.34-.21 3.41-.6l-1.46-1.46C13.33 19.95 12.68 20 12 20c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8c0 1.19-.33 2.3-.9 3.26l1.46 1.46C21.17 15.35 22 13.75 22 12c0-5.52-4.48-10-10-10z"/>
                                </svg>
                                <span class="nav-text">Analytics</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="admin_reports.php" class="nav-link active">
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
                <h1>Reports Management</h1>
                <div class="top-bar-actions">
                    <button class="btn btn-secondary" onclick="openScheduleModal()">
                        <i class="fas fa-calendar-plus"></i>
                        Schedule Report
                    </button>
                    <button class="btn btn-primary" onclick="openGenerateModal()">
                        <i class="fas fa-plus"></i>
                        Generate Report
                    </button>
                </div>
            </div>

            <div class="page-content">
                <?php if ($pdo): ?>
                    <!-- Report Statistics -->
                    <div class="stats-grid">
                        <div class="stats-card">
                            <div class="stats-header">
                                <div class="stats-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                            </div>
                            <div class="stats-value"><?php echo number_format($reports_data['report_stats']['total_generated']); ?></div>
                            <div class="stats-label">Total Reports Generated</div>
                        </div>

                        <div class="stats-card warning">
                            <div class="stats-header">
                                <div class="stats-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                            <div class="stats-value"><?php echo number_format($reports_data['report_stats']['pending_reports']); ?></div>
                            <div class="stats-label">Pending Reports</div>
                        </div>

                        <div class="stats-card success">
                            <div class="stats-header">
                                <div class="stats-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                            </div>
                            <div class="stats-value"><?php echo number_format($reports_data['report_stats']['scheduled_reports']); ?></div>
                            <div class="stats-label">Scheduled Reports</div>
                        </div>

                        <div class="stats-card danger">
                            <div class="stats-header">
                                <div class="stats-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                            </div>
                            <div class="stats-value"><?php echo number_format($reports_data['report_stats']['failed_reports']); ?></div>
                            <div class="stats-label">Failed Reports</div>
                        </div>
                    </div>

                    <!-- Quick Report Generation -->
                    <div class="quick-actions">
                        <h2>
                            <i class="fas fa-bolt"></i>
                            Quick Report Generation
                        </h2>
                        <div class="report-types-grid">
                            <?php foreach ($report_types as $type_key => $type_info): ?>
                                <div class="report-type-card" onclick="selectReportType('<?php echo $type_key; ?>')">
                                    <div class="report-type-header">
                                        <div class="report-type-icon">
                                            <i class="<?php echo htmlspecialchars($type_info['icon']); ?>"></i>
                                        </div>
                                        <div class="report-type-info">
                                            <h3><?php echo htmlspecialchars($type_info['name']); ?></h3>
                                            <div class="time-estimate">
                                                <i class="fas fa-clock"></i>
                                                <?php echo htmlspecialchars($type_info['estimated_time']); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="report-type-description">
                                        <?php echo htmlspecialchars($type_info['description']); ?>
                                    </div>
                                    <div class="report-type-actions">
                                        <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); generateReport('<?php echo $type_key; ?>')">
                                            <i class="fas fa-play"></i>
                                            Generate Now
                                        </button>
                                        <button class="btn btn-secondary btn-sm" onclick="event.stopPropagation(); scheduleReport('<?php echo $type_key; ?>')">
                                            <i class="fas fa-calendar"></i>
                                            Schedule
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Recent Reports -->
                    <div class="table-section">
                        <div class="table-header">
                            <h2 class="table-title">
                                <i class="fas fa-history"></i>
                                Recent Reports
                            </h2>
                            <div>
                                <button class="btn btn-secondary" onclick="refreshReports()">
                                    <i class="fas fa-sync-alt"></i>
                                    Refresh
                                </button>
                            </div>
                        </div>
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Report Name</th>
                                        <th>Type</th>
                                        <th>Election</th>
                                        <th>Generated</th>
                                        <th>Status</th>
                                        <th>File Size</th>
                                        <th>Downloads</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reports_data['recent_reports'] as $report): ?>
                                        <tr>
                                            <td style="font-weight: 600;"><?php echo htmlspecialchars($report['name']); ?></td>
                                            <td>
                                                <span style="display: inline-flex; align-items: center; gap: 0.5rem;">
                                                    <i class="<?php echo htmlspecialchars($report_types[$report['type']]['icon']); ?>" style="color: #6366f1;"></i>
                                                    <?php echo htmlspecialchars($report_types[$report['type']]['name']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($report['election_title']); ?></td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($report['generated_date'])); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo htmlspecialchars($report['status']); ?>">
                                                    <?php echo htmlspecialchars($report['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($report['file_size']); ?></td>
                                            <td><?php echo number_format($report['download_count']); ?></td>
                                            <td>
                                                <div style="display: flex; gap: 0.5rem;">
                                                    <?php if ($report['status'] === 'completed'): ?>
                                                        <button class="btn btn-primary btn-sm" onclick="downloadReport(<?php echo $report['id']; ?>)">
                                                            <i class="fas fa-download"></i>
                                                        </button>
                                                        <button class="btn btn-secondary btn-sm" onclick="viewReport(<?php echo $report['id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    <?php elseif ($report['status'] === 'pending'): ?>
                                                        <button class="btn btn-warning btn-sm" disabled>
                                                            <i class="fas fa-spinner fa-spin"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-secondary btn-sm" onclick="retryReport(<?php echo $report['id']; ?>)">
                                                            <i class="fas fa-redo"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-secondary btn-sm" onclick="deleteReport(<?php echo $report['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Scheduled Reports -->
                    <div class="table-section">
                        <div class="table-header">
                            <h2 class="table-title">
                                <i class="fas fa-calendar-alt"></i>
                                Scheduled Reports
                            </h2>
                            <div>
                                <button class="btn btn-primary" onclick="openScheduleModal()">
                                    <i class="fas fa-plus"></i>
                                    Add Schedule
                                </button>
                            </div>
                        </div>
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Report Name</th>
                                        <th>Type</th>
                                        <th>Schedule</th>
                                        <th>Next Run</th>
                                        <th>Last Run</th>
                                        <th>Status</th>
                                        <th>Recipients</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reports_data['scheduled_reports'] as $schedule): ?>
                                        <tr>
                                            <td style="font-weight: 600;"><?php echo htmlspecialchars($schedule['name']); ?></td>
                                            <td>
                                                <span style="display: inline-flex; align-items: center; gap: 0.5rem;">
                                                    <i class="<?php echo htmlspecialchars($report_types[$schedule['type']]['icon']); ?>" style="color: #6366f1;"></i>
                                                    <?php echo htmlspecialchars($report_types[$schedule['type']]['name']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span style="text-transform: capitalize; font-weight: 500;">
                                                    <?php echo htmlspecialchars($schedule['schedule']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($schedule['next_run'])); ?></td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($schedule['last_run'])); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo htmlspecialchars($schedule['status']); ?>">
                                                    <?php echo htmlspecialchars($schedule['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span style="font-size: 0.8rem; color: #64748b;">
                                                    <?php 
                                                    $recipients = explode(', ', $schedule['recipients']);
                                                    echo count($recipients) . ' recipient' . (count($recipients) > 1 ? 's' : '');
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div style="display: flex; gap: 0.5rem;">
                                                    <button class="btn btn-primary btn-sm" onclick="runScheduledReport(<?php echo $schedule['id']; ?>)" title="Run Now">
                                                        <i class="fas fa-play"></i>
                                                    </button>
                                                    <button class="btn btn-secondary btn-sm" onclick="editSchedule(<?php echo $schedule['id']; ?>)" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-secondary btn-sm" onclick="toggleSchedule(<?php echo $schedule['id']; ?>)" title="Toggle Status">
                                                        <i class="fas fa-<?php echo $schedule['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                                    </button>
                                                    <button class="btn btn-secondary btn-sm" onclick="deleteSchedule(<?php echo $schedule['id']; ?>)" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Database Connection Failed -->
                    <div class="table-section">
                        <div style="text-align: center; padding: 3rem; color: #64748b;">
                            <i class="fas fa-database" style="font-size: 3rem; margin-bottom: 1rem; color: #ef4444;"></i>
                            <h3>Database Connection Failed</h3>
                            <p>Unable to load reports data. Please check your database connection.</p>
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
                <?php endif; ?>

                <!-- Footer -->
                <div style="margin-top: 3rem; padding: 2rem; text-align: center; color: #94a3b8; border-top: 1px solid #e2e8f0;">
                    <p style="margin-bottom: 0.5rem;">
                        <i class="fas fa-file-alt"></i>
                        Reports Management - University Voting System
                    </p>
                    <p style="font-size: 0.875rem;">
                        Last updated: <?php echo date('F j, Y \a\t g:i A'); ?> | 
                        <a href="#" onclick="refreshReports()" style="color: #6366f1; text-decoration: none;">
                            <i class="fas fa-sync-alt"></i> Refresh Reports
                        </a>
                    </p>
                </div>
            </div>
        </main>
    </div>

    <!-- Generate Report Modal -->
    <div class="modal" id="generateModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Generate New Report</h3>
                <button class="modal-close" onclick="closeModal('generateModal')">&times;</button>
            </div>
            <form id="generateReportForm">
                <div class="form-group">
                    <label class="form-label">Report Type</label>
                    <select class="form-control" id="reportType" required>
                        <option value="">Select report type...</option>
                        <?php foreach ($report_types as $type_key => $type_info): ?>
                            <option value="<?php echo htmlspecialchars($type_key); ?>">
                                <?php echo htmlspecialchars($type_info['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Election (Optional)</label>
                    <select class="form-control" id="electionId">
                        <option value="">All Elections</option>
                        <?php if (isset($elections)): ?>
                            <?php foreach ($elections as $election): ?>
                                <option value="<?php echo htmlspecialchars($election['id']); ?>">
                                    <?php echo htmlspecialchars($election['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Report Name</label>
                    <input type="text" class="form-control" id="reportName" placeholder="Enter custom report name..." required>
                </div>
                <div class="form-group">
                    <label class="form-label">Date Range</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <input type="date" class="form-control" id="startDate">
                        <input type="date" class="form-control" id="endDate">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Format</label>
                    <select class="form-control" id="reportFormat">
                        <option value="pdf">PDF Document</option>
                        <option value="excel">Excel Spreadsheet</option>
                        <option value="csv">CSV File</option>
                    </select>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('generateModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-cog fa-spin" id="generateSpinner" style="display: none;"></i>
                        <i class="fas fa-play" id="generateIcon"></i>
                        Generate Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Schedule Report Modal -->
    <div class="modal" id="scheduleModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Schedule Report</h3>
                <button class="modal-close" onclick="closeModal('scheduleModal')">&times;</button>
            </div>
            <form id="scheduleReportForm">
                <div class="form-group">
                    <label class="form-label">Report Type</label>
                    <select class="form-control" id="scheduleReportType" required>
                        <option value="">Select report type...</option>
                        <?php foreach ($report_types as $type_key => $type_info): ?>
                            <option value="<?php echo htmlspecialchars($type_key); ?>">
                                <?php echo htmlspecialchars($type_info['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Schedule Name</label>
                    <input type="text" class="form-control" id="scheduleName" placeholder="Enter schedule name..." required>
                </div>
                <div class="form-group">
                    <label class="form-label">Frequency</label>
                    <select class="form-control" id="scheduleFrequency" required>
                        <option value="">Select frequency...</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Start Time</label>
                    <input type="time" class="form-control" id="scheduleTime" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Recipients</label>
                    <textarea class="form-control" id="recipients" placeholder="Enter email addresses separated by commas..." rows="3" required></textarea>
                    <small style="color: #64748b; font-size: 0.75rem; margin-top: 0.5rem; display: block;">
                        Separate multiple email addresses with commas
                    </small>
                </div>
                <div class="form-group">
                    <label class="form-label">Format</label>
                    <select class="form-control" id="scheduleFormat">
                        <option value="pdf">PDF Document</option>
                        <option value="excel">Excel Spreadsheet</option>
                        <option value="csv">CSV File</option>
                    </select>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('scheduleModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calendar-plus"></i>
                        Create Schedule
                    </button>
                </div>
            </form>
        </div>
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

        // Modal Functions
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.style.display = 'block';
            setTimeout(() => {
                modal.classList.add('active');
            }, 10);
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        function openGenerateModal() {
            openModal('generateModal');
        }

        function openScheduleModal() {
            openModal('scheduleModal');
        }

        // Close modal when clicking outside
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('active');
                setTimeout(() => {
                    e.target.style.display = 'none';
                }, 300);
            }
        });

        // Report Generation Functions
        function selectReportType(type) {
            document.getElementById('reportType').value = type;
            openGenerateModal();
        }

        function generateReport(type) {
            if (type) {
                document.getElementById('reportType').value = type;
            }
            
            showNotification('Generating report...', 'info');
            
            // Simulate report generation
            setTimeout(() => {
                showNotification('Report generated successfully!', 'success');
                refreshReports();
            }, 3000);
        }

        function scheduleReport(type) {
            if (type) {
                document.getElementById('scheduleReportType').value = type;
            }
            openScheduleModal();
        }

        // Report Actions
        function downloadReport(reportId) {
            showNotification('Downloading report...', 'info');
            
            // Simulate download
            setTimeout(() => {
                showNotification('Report downloaded successfully!', 'success');
            }, 1000);
        }

        function viewReport(reportId) {
            showNotification('Opening report preview...', 'info');
            
            // In real implementation, this would open a preview modal or new window
            setTimeout(() => {
                showNotification('Report preview opened!', 'success');
            }, 500);
        }

        function retryReport(reportId) {
            showNotification('Retrying report generation...', 'info');
            
            setTimeout(() => {
                showNotification('Report generation restarted!', 'success');
                refreshReports();
            }, 2000);
        }

        function deleteReport(reportId) {
            if (confirm('Are you sure you want to delete this report?')) {
                showNotification('Deleting report...', 'info');
                
                setTimeout(() => {
                    showNotification('Report deleted successfully!', 'success');
                    refreshReports();
                }, 1000);
            }
        }

        // Scheduled Report Functions
        function runScheduledReport(scheduleId) {
            showNotification('Running scheduled report...', 'info');
            
            setTimeout(() => {
                showNotification('Scheduled report executed successfully!', 'success');
                refreshReports();
            }, 2000);
        }

        function editSchedule(scheduleId) {
            showNotification('Opening schedule editor...', 'info');
            openScheduleModal();
        }

        function toggleSchedule(scheduleId) {
            showNotification('Updating schedule status...', 'info');
            
            setTimeout(() => {
                showNotification('Schedule status updated!', 'success');
                refreshReports();
            }, 1000);
        }

        function deleteSchedule(scheduleId) {
            if (confirm('Are you sure you want to delete this scheduled report?')) {
                showNotification('Deleting schedule...', 'info');
                
                setTimeout(() => {
                    showNotification('Schedule deleted successfully!', 'success');
                    refreshReports();
                }, 1000);
            }
        }

        // Utility Functions
        function refreshReports() {
            showNotification('Refreshing reports data...', 'info');
            
            setTimeout(() => {
                location.reload();
            }, 1000);
        }

        // Form Handling
        document.getElementById('generateReportForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const reportType = document.getElementById('reportType').value;
            const reportName = document.getElementById('reportName').value;
            
            // Show loading state
            const spinner = document.getElementById('generateSpinner');
            const icon = document.getElementById('generateIcon');
            const submitBtn = e.target.querySelector('button[type="submit"]');
            
            spinner.style.display = 'inline-block';
            icon.style.display = 'none';
            submitBtn.disabled = true;
            
            showNotification('Starting report generation...', 'info');
            
            // Simulate API call
            setTimeout(() => {
                spinner.style.display = 'none';
                icon.style.display = 'inline-block';
                submitBtn.disabled = false;
                
                showNotification(`${reportName} report generated successfully!`, 'success');
                closeModal('generateModal');
                refreshReports();
            }, 4000);
        });

        document.getElementById('scheduleReportForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const scheduleName = document.getElementById('scheduleName').value;
            const frequency = document.getElementById('scheduleFrequency').value;
            
            showNotification(`Creating ${frequency} schedule for ${scheduleName}...`, 'info');
            
            setTimeout(() => {
                showNotification('Report schedule created successfully!', 'success');
                closeModal('scheduleModal');
                refreshReports();
            }, 2000);
        });

        // Auto-populate report name based on type selection
        document.getElementById('reportType').addEventListener('change', function() {
            const reportNameField = document.getElementById('reportName');
            const selectedOption = this.options[this.selectedIndex];
            
            if (selectedOption.value) {
                const reportName = selectedOption.text + ' - ' + new Date().toLocaleDateString();
                reportNameField.value = reportName;
            }
        });

        document.getElementById('scheduleReportType').addEventListener('change', function() {
            const scheduleNameField = document.getElementById('scheduleName');
            const selectedOption = this.options[this.selectedIndex];
            
            if (selectedOption.value) {
                const scheduleName = 'Auto ' + selectedOption.text;
                scheduleNameField.value = scheduleName;
            }
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

        // Progress tracking for report generation
        function trackReportProgress(reportId) {
            const progressSteps = [
                'Initializing report...',
                'Collecting data...',
                'Processing information...',
                'Generating charts...',
                'Formatting document...',
                'Finalizing report...'
            ];
            
            let currentStep = 0;
            const interval = setInterval(() => {
                if (currentStep < progressSteps.length) {
                    showNotification(progressSteps[currentStep], 'info');
                    currentStep++;
                } else {
                    clearInterval(interval);
                    showNotification('Report completed successfully!', 'success');
                }
            }, 1000);
        }

        // Table sorting functionality
        function sortTable(table, columnIndex) {
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            rows.sort((a, b) => {
                let aVal = a.cells[columnIndex].textContent.trim();
                let bVal = b.cells[columnIndex].textContent.trim();
                
                // Check if values are dates
                const aDate = new Date(aVal);
                const bDate = new Date(bVal);
                
                if (!isNaN(aDate.getTime()) && !isNaN(bDate.getTime())) {
                    return bDate - aDate; // Latest first
                }
                
                // Check if values are numbers
                const aNum = parseFloat(aVal.replace(/[^0-9.-]/g, ''));
                const bNum = parseFloat(bVal.replace(/[^0-9.-]/g, ''));
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return bNum - aNum; // Highest first
                }
                
                return aVal.localeCompare(bVal);
            });
            
            // Re-append sorted rows
            rows.forEach(row => tbody.appendChild(row));
        }

        // Add click handlers to table headers for sorting
        document.addEventListener('DOMContentLoaded', function() {
            const tables = document.querySelectorAll('.data-table');
            tables.forEach(table => {
                const headers = table.querySelectorAll('th');
                headers.forEach((header, index) => {
                    if (index < headers.length - 1) { // Don't sort actions column
                        header.style.cursor = 'pointer';
                        header.style.userSelect = 'none';
                        header.addEventListener('click', () => {
                            sortTable(table, index);
                            showNotification('Table sorted by ' + header.textContent, 'info');
                        });
                        
                        // Add hover effect
                        header.addEventListener('mouseenter', () => {
                            header.style.backgroundColor = '#f1f5f9';
                        });
                        header.addEventListener('mouseleave', () => {
                            header.style.backgroundColor = '#f8fafc';
                        });
                    }
                });
            });

            // Animate cards on load
            const statsCards = document.querySelectorAll('.stats-card');
            statsCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100 + 200);
            });

            // Animate report type cards
            const reportCards = document.querySelectorAll('.report-type-card');
            reportCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 150 + 600);
            });

            // Show success message
            setTimeout(() => {
                showNotification('Reports dashboard loaded successfully! 📊', 'success');
            }, 1000);
        });

        // Export functionality
        function exportReportsData() {
            showNotification('Preparing reports export...', 'info');
            
            setTimeout(() => {
                showNotification('Reports data exported successfully!', 'success');
            }, 2000);
        }

        // Real-time updates simulation
        function simulateRealTimeUpdates() {
            setInterval(() => {
                // Simulate pending reports updates
                if (Math.random() < 0.15) { // 15% chance every 10 seconds
                    const pendingBadges = document.querySelectorAll('.status-pending');
                    if (pendingBadges.length > 0) {
                        const randomBadge = pendingBadges[Math.floor(Math.random() * pendingBadges.length)];
                        randomBadge.className = 'status-badge status-completed';
                        randomBadge.textContent = 'completed';
                        
                        showNotification('Report generation completed!', 'success');
                    }
                }
            }, 10000);
        }

        // Start real-time simulation
        simulateRealTimeUpdates();

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + N for new report
            if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                e.preventDefault();
                openGenerateModal();
            }
            
            // Ctrl/Cmd + S for schedule
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                openScheduleModal();
            }
            
            // Ctrl/Cmd + R for refresh
            if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                e.preventDefault();
                refreshReports();
            }
            
            // Escape to close modals
            if (e.key === 'Escape') {
                const activeModals = document.querySelectorAll('.modal.active');
                activeModals.forEach(modal => {
                    modal.classList.remove('active');
                    setTimeout(() => {
                        modal.style.display = 'none';
                    }, 300);
                });
                
                // Also close sidebar on mobile
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                    hamburgerBtn.classList.remove('active');
                }
            }
        });

        // Performance monitoring
        function monitorPerformance() {
            if ('performance' in window) {
                const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
                console.log('📊 Reports Dashboard Performance:');
                console.log(`🕒 Page Load Time: ${loadTime}ms`);
                console.log('📋 Report Types: Loaded');
                console.log('🔄 Real-time Updates: Active');
            }
        }

        // Initialize performance monitoring
        window.addEventListener('load', monitorPerformance);

        // Print functionality
        function printReports() {
            window.print();
        }

        // Add print styles
        const printStyles = `
            @media print {
                .sidebar, .top-bar-actions, .btn, .modal { display: none !important; }
                .main-content { margin-left: 0 !important; }
                .top-bar { border-bottom: 2px solid #000; }
                .table-section, .quick-actions { break-inside: avoid; }
                .page-content { padding: 1rem !important; }
            }
        `;
        
        const styleSheet = document.createElement('style');
        styleSheet.textContent = printStyles;
        document.head.appendChild(styleSheet);

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            console.log('📊 Reports Dashboard: Cleaning up resources');
        });

        // Initialize page
        console.log('🗳 University Voting System Reports Dashboard loaded successfully');
        console.log('📋 Report Generation: Ready');
        console.log('📅 Report Scheduling: Active');
        console.log('🔧 Database: voting_system');
        console.log('⚡ Real-time Updates: Enabled');
    </script>
</body>
</html>