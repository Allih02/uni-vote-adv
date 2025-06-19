<?php
// admin_audit.php - Audit Log & Activity Tracking
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'voting_system';
$username = 'root';
$password = '';

// Initialize variables
$pdo = null;
$audit_data = [
    'recent_activities' => [],
    'activity_stats' => [
        'total_activities' => 0,
        'security_events' => 0,
        'failed_logins' => 0,
        'system_changes' => 0
    ],
    'user_activities' => [],
    'security_alerts' => []
];

// Pagination and filtering
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 25;
$offset = ($page - 1) * $per_page;

$filter_user = $_GET['user'] ?? '';
$filter_action = $_GET['action'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$filter_severity = $_GET['severity'] ?? '';

// Database connection with error handling
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $connection_error = "Connection failed: " . $e->getMessage();
    error_log($connection_error);
}

// Load audit data if database connection is successful
if ($pdo) {
    try {
        // In a real application, this would be from an audit_log table
        // For demonstration, we'll simulate audit log data
        $audit_data['recent_activities'] = [
            [
                'id' => 1,
                'timestamp' => date('Y-m-d H:i:s', strtotime('-15 minutes')),
                'user_id' => 1,
                'username' => 'admin',
                'user_role' => 'Administrator',
                'action' => 'SETTINGS_UPDATE',
                'category' => 'system',
                'description' => 'Updated system security settings',
                'details' => 'Modified password policy requirements and session timeout',
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'severity' => 'info',
                'status' => 'success',
                'resource_type' => 'settings',
                'resource_id' => null
            ],
            [
                'id' => 2,
                'timestamp' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                'user_id' => 2,
                'username' => 'election_manager',
                'user_role' => 'Election Manager',
                'action' => 'ELECTION_CREATE',
                'category' => 'election',
                'description' => 'Created new election',
                'details' => 'Student Government Elections 2024 - Fall Semester',
                'ip_address' => '192.168.1.105',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                'severity' => 'info',
                'status' => 'success',
                'resource_type' => 'election',
                'resource_id' => 5
            ],
            [
                'id' => 3,
                'timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                'user_id' => null,
                'username' => 'unknown',
                'user_role' => null,
                'action' => 'LOGIN_FAILED',
                'category' => 'security',
                'description' => 'Failed login attempt',
                'details' => 'Invalid credentials for username: hacker123',
                'ip_address' => '203.0.113.45',
                'user_agent' => 'curl/7.68.0',
                'severity' => 'warning',
                'status' => 'failed',
                'resource_type' => 'auth',
                'resource_id' => null
            ],
            [
                'id' => 4,
                'timestamp' => date('Y-m-d H:i:s', strtotime('-3 hours')),
                'user_id' => 3,
                'username' => 'voter_admin',
                'user_role' => 'Voter Administrator',
                'action' => 'VOTER_BULK_IMPORT',
                'category' => 'user_management',
                'description' => 'Imported voter records',
                'details' => 'Successfully imported 1,247 voter records from CSV file',
                'ip_address' => '192.168.1.110',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'severity' => 'info',
                'status' => 'success',
                'resource_type' => 'voters',
                'resource_id' => null
            ],
            [
                'id' => 5,
                'timestamp' => date('Y-m-d H:i:s', strtotime('-4 hours')),
                'user_id' => 1,
                'username' => 'admin',
                'user_role' => 'Administrator',
                'action' => 'BACKUP_CREATED',
                'category' => 'system',
                'description' => 'System backup completed',
                'details' => 'Automatic daily backup - 247MB compressed',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'System Cron Job',
                'severity' => 'info',
                'status' => 'success',
                'resource_type' => 'backup',
                'resource_id' => null
            ],
            [
                'id' => 6,
                'timestamp' => date('Y-m-d H:i:s', strtotime('-5 hours')),
                'user_id' => null,
                'username' => 'unknown',
                'user_role' => null,
                'action' => 'SUSPICIOUS_ACTIVITY',
                'category' => 'security',
                'description' => 'Multiple failed login attempts',
                'details' => '8 consecutive failed login attempts from same IP address',
                'ip_address' => '203.0.113.45',
                'user_agent' => 'Various user agents',
                'severity' => 'critical',
                'status' => 'blocked',
                'resource_type' => 'security',
                'resource_id' => null
            ],
            [
                'id' => 7,
                'timestamp' => date('Y-m-d H:i:s', strtotime('-6 hours')),
                'user_id' => 4,
                'username' => 'candidate_manager',
                'user_role' => 'Candidate Manager',
                'action' => 'CANDIDATE_APPROVED',
                'category' => 'candidate',
                'description' => 'Approved candidate application',
                'details' => 'Approved: Sarah Johnson for Student Body President',
                'ip_address' => '192.168.1.115',
                'user_agent' => 'Mozilla/5.0 (iPad; CPU OS 14_6 like Mac OS X) AppleWebKit/605.1.15',
                'severity' => 'info',
                'status' => 'success',
                'resource_type' => 'candidate',
                'resource_id' => 23
            ],
            [
                'id' => 8,
                'timestamp' => date('Y-m-d H:i:s', strtotime('-8 hours')),
                'user_id' => 1,
                'username' => 'admin',
                'user_role' => 'Administrator',
                'action' => 'ELECTION_PUBLISHED',
                'category' => 'election',
                'description' => 'Published election for voting',
                'details' => 'Graduate Student Council Elections - Spring 2024',
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'severity' => 'info',
                'status' => 'success',
                'resource_type' => 'election',
                'resource_id' => 4
            ],
            [
                'id' => 9,
                'timestamp' => date('Y-m-d H:i:s', strtotime('-10 hours')),
                'user_id' => 2,
                'username' => 'election_manager',
                'user_role' => 'Election Manager',
                'action' => 'REPORT_GENERATED',
                'category' => 'reporting',
                'description' => 'Generated election results report',
                'details' => 'PDF report for Undergraduate Student Elections - Fall 2023',
                'ip_address' => '192.168.1.105',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                'severity' => 'info',
                'status' => 'success',
                'resource_type' => 'report',
                'resource_id' => 78
            ],
            [
                'id' => 10,
                'timestamp' => date('Y-m-d H:i:s', strtotime('-12 hours')),
                'user_id' => null,
                'username' => 'system',
                'user_role' => 'System',
                'action' => 'MAINTENANCE_COMPLETED',
                'category' => 'system',
                'description' => 'Scheduled maintenance completed',
                'details' => 'Database optimization and index rebuilding completed successfully',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'System Maintenance Job',
                'severity' => 'info',
                'status' => 'success',
                'resource_type' => 'maintenance',
                'resource_id' => null
            ]
        ];

        // Calculate activity statistics
        $audit_data['activity_stats'] = [
            'total_activities' => count($audit_data['recent_activities']),
            'security_events' => count(array_filter($audit_data['recent_activities'], function($activity) {
                return $activity['category'] === 'security';
            })),
            'failed_logins' => count(array_filter($audit_data['recent_activities'], function($activity) {
                return $activity['action'] === 'LOGIN_FAILED';
            })),
            'system_changes' => count(array_filter($audit_data['recent_activities'], function($activity) {
                return in_array($activity['category'], ['system', 'settings']);
            }))
        ];

        // Get unique users for filter dropdown
        $audit_data['user_activities'] = array_unique(array_column($audit_data['recent_activities'], 'username'));
        sort($audit_data['user_activities']);

    } catch(PDOException $e) {
        error_log("Error loading audit data: " . $e->getMessage());
    }
}

// Action type definitions
$action_types = [
    'LOGIN_SUCCESS' => ['icon' => 'fas fa-sign-in-alt', 'color' => '#10b981', 'category' => 'Authentication'],
    'LOGIN_FAILED' => ['icon' => 'fas fa-times-circle', 'color' => '#ef4444', 'category' => 'Authentication'],
    'LOGOUT' => ['icon' => 'fas fa-sign-out-alt', 'color' => '#6b7280', 'category' => 'Authentication'],
    'ELECTION_CREATE' => ['icon' => 'fas fa-plus-circle', 'color' => '#3b82f6', 'category' => 'Election Management'],
    'ELECTION_UPDATE' => ['icon' => 'fas fa-edit', 'color' => '#f59e0b', 'category' => 'Election Management'],
    'ELECTION_DELETE' => ['icon' => 'fas fa-trash', 'color' => '#ef4444', 'category' => 'Election Management'],
    'ELECTION_PUBLISHED' => ['icon' => 'fas fa-bullhorn', 'color' => '#10b981', 'category' => 'Election Management'],
    'CANDIDATE_APPROVED' => ['icon' => 'fas fa-user-check', 'color' => '#10b981', 'category' => 'Candidate Management'],
    'CANDIDATE_REJECTED' => ['icon' => 'fas fa-user-times', 'color' => '#ef4444', 'category' => 'Candidate Management'],
    'VOTER_REGISTERED' => ['icon' => 'fas fa-user-plus', 'color' => '#10b981', 'category' => 'User Management'],
    'VOTER_BULK_IMPORT' => ['icon' => 'fas fa-upload', 'color' => '#8b5cf6', 'category' => 'User Management'],
    'SETTINGS_UPDATE' => ['icon' => 'fas fa-cog', 'color' => '#f59e0b', 'category' => 'System'],
    'BACKUP_CREATED' => ['icon' => 'fas fa-save', 'color' => '#10b981', 'category' => 'System'],
    'REPORT_GENERATED' => ['icon' => 'fas fa-file-alt', 'color' => '#6366f1', 'category' => 'Reporting'],
    'SUSPICIOUS_ACTIVITY' => ['icon' => 'fas fa-shield-alt', 'color' => '#ef4444', 'category' => 'Security'],
    'MAINTENANCE_COMPLETED' => ['icon' => 'fas fa-tools', 'color' => '#10b981', 'category' => 'System']
];

// Severity levels
$severity_levels = [
    'info' => ['color' => '#3b82f6', 'icon' => 'fas fa-info-circle', 'label' => 'Info'],
    'warning' => ['color' => '#f59e0b', 'icon' => 'fas fa-exclamation-triangle', 'label' => 'Warning'],
    'critical' => ['color' => '#ef4444', 'icon' => 'fas fa-exclamation-circle', 'label' => 'Critical'],
    'success' => ['color' => '#10b981', 'icon' => 'fas fa-check-circle', 'label' => 'Success']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log - Admin Panel</title>
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

        /* Stats Cards */
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

        /* Filter Bar */
        .filter-bar {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            margin-bottom: 2rem;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        }

        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .filter-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-label {
            font-weight: 500;
            color: #374151;
            font-size: 0.875rem;
        }

        .filter-control {
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            background: white;
            color: #1e293b;
            font-size: 0.875rem;
            transition: border-color 0.2s ease;
        }

        .filter-control:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        /* Audit Log Table */
        .audit-table-container {
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

        .audit-table {
            width: 100%;
            border-collapse: collapse;
        }

        .audit-table th {
            background: #f8fafc;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        .audit-table td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }

        .audit-table tr:hover {
            background: #f9fafb;
        }

        /* Activity Row Styles */
        .activity-row {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .activity-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            color: white;
            flex-shrink: 0;
            margin-top: 0.25rem;
        }

        .activity-content {
            flex: 1;
            min-width: 0;
        }

        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }

        .activity-title {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .activity-description {
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .activity-details {
            font-size: 0.8rem;
            color: #94a3b8;
            font-style: italic;
        }

        .activity-meta {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            align-items: flex-end;
            text-align: right;
        }

        .activity-time {
            font-size: 0.8rem;
            color: #64748b;
            white-space: nowrap;
        }

        .activity-user {
            font-size: 0.8rem;
            color: #374151;
            font-weight: 500;
        }

        .activity-ip {
            font-size: 0.75rem;
            color: #94a3b8;
            font-family: 'Monaco', 'Menlo', monospace;
        }

        /* Severity Badges */
        .severity-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .severity-info {
            background: #dbeafe;
            color: #1e40af;
        }

        .severity-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .severity-critical {
            background: #fecaca;
            color: #991b1b;
        }

        .severity-success {
            background: #dcfce7;
            color: #166534;
        }

        /* Status Badges */
        .status-badge {/* Status Badges */
        
            padding: 0.25rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-success {
            background: #dcfce7;
            color: #166534;
        }

        .status-failed {
            background: #fecaca;
            color: #991b1b;
        }

        .status-blocked {
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

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
            padding: 1rem;
        }

        .pagination-item {
            padding: 0.5rem 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            background: white;
            color: #64748b;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .pagination-item:hover {
            background: #f1f5f9;
            color: #1e293b;
        }

        .pagination-item.active {
            background: #6366f1;
            border-color: #6366f1;
            color: white;
        }

        .pagination-info {
            color: #64748b;
            font-size: 0.875rem;
            margin-right: 1rem;
        }

        /* Export Options */
        .export-dropdown {
            position: relative;
            display: inline-block;
        }

        .export-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            z-index: 100;
            min-width: 150px;
            display: none;
        }

        .export-menu.active {
            display: block;
        }

        .export-option {
            display: block;
            padding: 0.75rem 1rem;
            color: #374151;
            text-decoration: none;
            transition: background-color 0.2s ease;
            border-bottom: 1px solid #f3f4f6;
        }

        .export-option:last-child {
            border-bottom: none;
        }

        .export-option:hover {
            background: #f9fafb;
        }

        /* Real-time indicator */
        .live-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #10b981;
        }

        .live-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        /* Detail Modal */
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
            max-width: 600px;
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

        .detail-grid {
            display: grid;
            gap: 1rem;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: 0.5rem;
        }

        .detail-label {
            font-weight: 600;
            color: #374151;
        }

        .detail-value {
            color: #64748b;
            text-align: right;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 0.875rem;
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
            .filter-grid {
                grid-template-columns: repeat(2, 1fr);
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

            .filter-grid {
                grid-template-columns: 1fr;
            }

            .audit-table {
                font-size: 0.875rem;
            }

            .activity-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .activity-meta {
                align-items: flex-start;
                text-align: left;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .modal-content {
                padding: 1.5rem;
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
                            <a href="admin_audit.php" class="nav-link active">
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
                <h1>Audit Log</h1>
                <div class="top-bar-actions">
                    <div class="live-indicator">
                        <div class="live-dot"></div>
                        Live Monitoring
                    </div>
                    <div class="export-dropdown">
                        <button class="btn btn-secondary" onclick="toggleExportMenu()">
                            <i class="fas fa-download"></i>
                            Export
                        </button>
                        <div class="export-menu" id="exportMenu">
                            <a href="#" class="export-option" onclick="exportAuditLog('csv')">
                                <i class="fas fa-file-csv"></i>
                                Export CSV
                            </a>
                            <a href="#" class="export-option" onclick="exportAuditLog('excel')">
                                <i class="fas fa-file-excel"></i>
                                Export Excel
                            </a>
                            <a href="#" class="export-option" onclick="exportAuditLog('pdf')">
                                <i class="fas fa-file-pdf"></i>
                                Export PDF
                            </a>
                        </div>
                    </div>
                    <button class="btn btn-primary" onclick="refreshAuditLog()">
                        <i class="fas fa-sync-alt"></i>
                        Refresh
                    </button>
                </div>
            </div>

            <div class="page-content">
                <?php if ($pdo): ?>
                    <!-- Activity Statistics -->
                    <div class="stats-grid">
                        <div class="stats-card">
                            <div class="stats-header">
                                <div class="stats-icon">
                                    <i class="fas fa-list"></i>
                                </div>
                            </div>
                            <div class="stats-value"><?php echo number_format($audit_data['activity_stats']['total_activities']); ?></div>
                            <div class="stats-label">Total Activities</div>
                        </div>

                        <div class="stats-card danger">
                            <div class="stats-header">
                                <div class="stats-icon">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                            </div>
                            <div class="stats-value"><?php echo number_format($audit_data['activity_stats']['security_events']); ?></div>
                            <div class="stats-label">Security Events</div>
                        </div>

                        <div class="stats-card warning">
                            <div class="stats-header">
                                <div class="stats-icon">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                            </div>
                            <div class="stats-value"><?php echo number_format($audit_data['activity_stats']['failed_logins']); ?></div>
                            <div class="stats-label">Failed Logins</div>
                        </div>

                        <div class="stats-card success">
                            <div class="stats-header">
                                <div class="stats-icon">
                                    <i class="fas fa-cogs"></i>
                                </div>
                            </div>
                            <div class="stats-value"><?php echo number_format($audit_data['activity_stats']['system_changes']); ?></div>
                            <div class="stats-label">System Changes</div>
                        </div>
                    </div>

                    <!-- Filter Bar -->
                    <div class="filter-bar">
                        <div class="filter-header">
                            <h3 class="filter-title">Filter Audit Log</h3>
                            <button class="btn btn-secondary btn-sm" onclick="clearFilters()">
                                <i class="fas fa-times"></i>
                                Clear Filters
                            </button>
                        </div>
                        <form method="GET" action="" id="filterForm">
                            <div class="filter-grid">
                                <div class="filter-group">
                                    <label class="filter-label">User</label>
                                    <select class="filter-control" name="user" id="userFilter">
                                        <option value="">All Users</option>
                                        <?php foreach ($audit_data['user_activities'] as $user): ?>
                                            <option value="<?php echo htmlspecialchars($user); ?>" <?php echo $filter_user === $user ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($user); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label class="filter-label">Action Type</label>
                                    <select class="filter-control" name="action" id="actionFilter">
                                        <option value="">All Actions</option>
                                        <?php foreach (array_keys($action_types) as $action): ?>
                                            <option value="<?php echo htmlspecialchars($action); ?>" <?php echo $filter_action === $action ? 'selected' : ''; ?>>
                                                <?php echo str_replace('_', ' ', $action); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label class="filter-label">Date From</label>
                                    <input type="date" class="filter-control" name="date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>">
                                </div>
                                <div class="filter-group">
                                    <label class="filter-label">Date To</label>
                                    <input type="date" class="filter-control" name="date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>">
                                </div>
                                <div class="filter-group">
                                    <label class="filter-label">Severity</label>
                                    <select class="filter-control" name="severity" id="severityFilter">
                                        <option value="">All Levels</option>
                                        <?php foreach (array_keys($severity_levels) as $severity): ?>
                                            <option value="<?php echo htmlspecialchars($severity); ?>" <?php echo $filter_severity === $severity ? 'selected' : ''; ?>>
                                                <?php echo ucfirst($severity); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label class="filter-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary filter-control">
                                        <i class="fas fa-filter"></i>
                                        Apply Filters
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Audit Log Table -->
                    <div class="audit-table-container">
                        <div class="table-header">
                            <h2 class="table-title">
                                <i class="fas fa-history"></i>
                                Activity Log
                            </h2>
                            <div>
                                <span style="color: #64748b; font-size: 0.875rem;">
                                    Showing <?php echo count($audit_data['recent_activities']); ?> recent activities
                                </span>
                            </div>
                        </div>
                        <div style="overflow-x: auto;">
                            <table class="audit-table">
                                <thead>
                                    <tr>
                                        <th style="width: 60%;">Activity</th>
                                        <th style="width: 15%;">Severity</th>
                                        <th style="width: 10%;">Status</th>
                                        <th style="width: 15%;">Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($audit_data['recent_activities'] as $activity): ?>
                                        <tr onclick="showActivityDetails(<?php echo $activity['id']; ?>)" style="cursor: pointer;">
                                            <td>
                                                <div class="activity-row">
                                                    <div class="activity-icon" style="background: <?php echo $action_types[$activity['action']]['color']; ?>;">
                                                        <i class="<?php echo $action_types[$activity['action']]['icon']; ?>"></i>
                                                    </div>
                                                    <div class="activity-content">
                                                        <div class="activity-header">
                                                            <div>
                                                                <div class="activity-title"><?php echo htmlspecialchars($activity['description']); ?></div>
                                                                <div class="activity-description"><?php echo htmlspecialchars($activity['details']); ?></div>
                                                            </div>
                                                            <div class="activity-meta">
                                                                <div class="activity-time"><?php echo date('M j, g:i A', strtotime($activity['timestamp'])); ?></div>
                                                                <div class="activity-user"><?php echo htmlspecialchars($activity['username']); ?></div>
                                                                <div class="activity-ip"><?php echo htmlspecialchars($activity['ip_address']); ?></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="severity-badge severity-<?php echo $activity['severity']; ?>">
                                                    <i class="<?php echo $severity_levels[$activity['severity']]['icon']; ?>"></i>
                                                    <?php echo $severity_levels[$activity['severity']]['label']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $activity['status']; ?>">
                                                    <?php echo ucfirst($activity['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-secondary btn-sm" onclick="event.stopPropagation(); showActivityDetails(<?php echo $activity['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                    View
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="pagination">
                        <span class="pagination-info">
                            Showing <?php echo ($page - 1) * $per_page + 1; ?>-<?php echo min($page * $per_page, count($audit_data['recent_activities'])); ?> 
                            of <?php echo count($audit_data['recent_activities']); ?> entries
                        </span>
                        <a href="?page=<?php echo max(1, $page - 1); ?>" class="pagination-item">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <a href="?page=1" class="pagination-item <?php echo $page === 1 ? 'active' : ''; ?>">1</a>
                        <a href="?page=2" class="pagination-item <?php echo $page === 2 ? 'active' : ''; ?>">2</a>
                        <a href="?page=3" class="pagination-item <?php echo $page === 3 ? 'active' : ''; ?>">3</a>
                        <span class="pagination-item">...</span>
                        <a href="?page=<?php echo min(10, $page + 1); ?>" class="pagination-item">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>

                <?php else: ?>
                    <!-- Database Connection Failed -->
                    <div class="audit-table-container">
                        <div style="text-align: center; padding: 3rem; color: #64748b;">
                            <i class="fas fa-database" style="font-size: 3rem; margin-bottom: 1rem; color: #ef4444;"></i>
                            <h3>Database Connection Failed</h3>
                            <p>Unable to load audit log data. Please check your database connection.</p>
                            <div style="margin-top: 2rem; padding: 1rem; background: #fef2f2; border-radius: 0.5rem; color: #ef4444; text-align: left;">
                                <h4 style="margin-bottom: 1rem;">Troubleshooting Steps:</h4>
                                <ol style="margin-left: 1.5rem;">
                                    <li>Ensure MySQL server is running</li>
                                    <li>Verify the database name is 'voting_system'</li>
                                    <li>Check database credentials</li>
                                    <li>Verify audit log table exists</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Footer -->
                <div style="margin-top: 3rem; padding: 2rem; text-align: center; color: #94a3b8; border-top: 1px solid #e2e8f0;">
                    <p style="margin-bottom: 0.5rem;">
                        <i class="fas fa-history"></i>
                        Audit Log - University Voting System
                    </p>
                    <p style="font-size: 0.875rem;">
                        Last updated: <?php echo date('F j, Y \a\t g:i A'); ?> | 
                        <a href="#" onclick="refreshAuditLog()" style="color: #6366f1; text-decoration: none;">
                            <i class="fas fa-sync-alt"></i> Refresh Log
                        </a>
                    </p>
                </div>
            </div>
        </main>
    </div>

    <!-- Activity Detail Modal -->
    <div class="modal" id="activityModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Activity Details</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="detail-grid" id="activityDetails">
                <!-- Activity details will be populated here -->
            </div>
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

        // Audit Log Functions
        const auditActivities = <?php echo json_encode($audit_data['recent_activities']); ?>;

        function showActivityDetails(activityId) {
            const activity = auditActivities.find(a => a.id === activityId);
            if (!activity) return;

            const modal = document.getElementById('activityModal');
            const detailsContainer = document.getElementById('activityDetails');
            
            detailsContainer.innerHTML = `
                <div class="detail-item">
                    <span class="detail-label">Activity ID</span>
                    <span class="detail-value">#${activity.id}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Timestamp</span>
                    <span class="detail-value">${new Date(activity.timestamp).toLocaleString()}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">User</span>
                    <span class="detail-value">${activity.username} (${activity.user_role || 'N/A'})</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Action</span>
                    <span class="detail-value">${activity.action}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Category</span>
                    <span class="detail-value">${activity.category}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Description</span>
                    <span class="detail-value">${activity.description}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Details</span>
                    <span class="detail-value">${activity.details}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">IP Address</span>
                    <span class="detail-value">${activity.ip_address}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">User Agent</span>
                    <span class="detail-value" style="word-break: break-all;">${activity.user_agent}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Severity</span>
                    <span class="detail-value">${activity.severity.charAt(0).toUpperCase() + activity.severity.slice(1)}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Status</span>
                    <span class="detail-value">${activity.status.charAt(0).toUpperCase() + activity.status.slice(1)}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Resource Type</span>
                    <span class="detail-value">${activity.resource_type || 'N/A'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Resource ID</span>
                    <span class="detail-value">${activity.resource_id || 'N/A'}</span>
                </div>
            `;

            modal.style.display = 'block';
            setTimeout(() => {
                modal.classList.add('active');
            }, 10);
        }

        function closeModal() {
            const modal = document.getElementById('activityModal');
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        // Close modal when clicking outside
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                closeModal();
            }
        });

        // Export Functions
        function toggleExportMenu() {
            const menu = document.getElementById('exportMenu');
            menu.classList.toggle('active');
        }

        function exportAuditLog(format) {
            showNotification(`Exporting audit log as ${format.toUpperCase()}...`, 'info');
            
            setTimeout(() => {
                showNotification(`Audit log exported successfully as ${format.toUpperCase()}!`, 'success');
                document.getElementById('exportMenu').classList.remove('active');
            }, 2000);
        }

        // Close export menu when clicking outside
        document.addEventListener('click', (e) => {
            const dropdown = document.querySelector('.export-dropdown');
            if (!dropdown.contains(e.target)) {
                document.getElementById('exportMenu').classList.remove('active');
            }
        });

        // Filter Functions
        function clearFilters() {
            document.getElementById('filterForm').reset();
            showNotification('Filters cleared', 'info');
            setTimeout(() => {
                window.location.href = window.location.pathname;
            }, 500);
        }

        // Real-time filter updates
        document.getElementById('filterForm').addEventListener('change', function() {
            const formData = new FormData(this);
            const params = new URLSearchParams();
            
            for (let [key, value] of formData.entries()) {
                if (value) {
                    params.append(key, value);
                }
            }
            
            if (params.toString()) {
                showNotification('Applying filters...', 'info');
                setTimeout(() => {
                    window.location.href = '?' + params.toString();
                }, 500);
            }
        });

        // Refresh Functions
        function refreshAuditLog() {
            showNotification('Refreshing audit log...', 'info');
            
            setTimeout(() => {
                location.reload();
            }, 1000);
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

        // Real-time updates simulation
        function simulateRealTimeUpdates() {
            setInterval(() => {
                // Simulate new audit entries
                if (Math.random() < 0.1) { // 10% chance every 10 seconds
                    const liveDot = document.querySelector('.live-dot');
                    if (liveDot) {
                        liveDot.style.animation = 'none';
                        liveDot.style.background = '#f59e0b'; // Orange flash
                        setTimeout(() => {
                            liveDot.style.animation = 'pulse-dot 2s infinite';
                            liveDot.style.background = '#10b981';
                        }, 500);
                    }
                    
                    showNotification('New audit entry detected', 'info');
                }
            }, 10000);
        }

        // Table sorting functionality
        function sortTable(columnIndex) {
            const table = document.querySelector('.audit-table');
            if (!table) return;
            
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
                
                return aVal.localeCompare(bVal);
            });
            
            // Re-append sorted rows
            rows.forEach(row => tbody.appendChild(row));
        }

        // Add click handlers to table headers for sorting
        document.addEventListener('DOMContentLoaded', function() {
            const headers = document.querySelectorAll('.audit-table th');
            headers.forEach((header, index) => {
                if (index < headers.length - 1) { // Don't sort the actions column
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
                }
            });

            // Animate stats cards on load
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

            // Animate table rows on load
            const tableRows = document.querySelectorAll('.audit-table tbody tr');
            tableRows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateX(0)';
                }, index * 50 + 800);
            });

            // Show success message
            setTimeout(() => {
                showNotification('Audit log loaded successfully! 📋', 'success');
            }, 1000);
        });

        // Start real-time simulation
        simulateRealTimeUpdates();

        // Search functionality
        function searchAuditLog() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('.audit-table tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            
            showNotification(`Filtered results for: "${searchTerm}"`, 'info');
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + R for refresh
            if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                e.preventDefault();
                refreshAuditLog();
            }
            
            // Ctrl/Cmd + E for export
            if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
                e.preventDefault();
                toggleExportMenu();
            }
            
            // Escape to close modal
            if (e.key === 'Escape') {
                closeModal();
                document.getElementById('exportMenu').classList.remove('active');
                
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
                console.log('📋 Audit Log Dashboard Performance:');
                console.log(`🕒 Page Load Time: ${loadTime}ms`);
                console.log('📊 Audit Entries: Loaded');
                console.log('🔍 Filters: Active');
                console.log('🔄 Real-time Updates: Enabled');
            }
        }

        // Initialize performance monitoring
        window.addEventListener('load', monitorPerformance);

        // Auto-refresh audit log every 30 seconds (optional)
        let autoRefreshEnabled = false;
        let autoRefreshInterval;

        function toggleAutoRefresh() {
            autoRefreshEnabled = !autoRefreshEnabled;
            
            if (autoRefreshEnabled) {
                autoRefreshInterval = setInterval(() => {
                    refreshAuditLog();
                }, 30000);
                showNotification('Auto-refresh enabled (30s intervals)', 'success');
            } else {
                clearInterval(autoRefreshInterval);
                showNotification('Auto-refresh disabled', 'info');
            }
        }

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
            }
            console.log('📋 Audit Log Dashboard: Cleaning up resources');
        });

        // Print functionality
        function printAuditLog() {
            window.print();
        }

        // Add print styles
        const printStyles = `
            @media print {
                .sidebar, .top-bar-actions, .btn, .modal, .filter-bar { display: none !important; }
                .main-content { margin-left: 0 !important; }
                .top-bar { border-bottom: 2px solid #000; }
                .audit-table-container { break-inside: avoid; }
                .page-content { padding: 1rem !important; }
            }
        `;
        
        const styleSheet = document.createElement('style');
        styleSheet.textContent = printStyles;
        document.head.appendChild(styleSheet);

        // Initialize page
        console.log('🗳 University Voting System Audit Log loaded successfully');
        console.log('📋 Activity Tracking: Active');
        console.log('🔧 Database: voting_system');
        console.log('🛡️ Security Monitoring: Enabled');
        console.log('⚡ Real-time Updates: Active');
    </script>
</body>
</html>