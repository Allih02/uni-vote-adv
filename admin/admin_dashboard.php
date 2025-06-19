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

// Get dashboard statistics
function getDashboardStats() {
    try {
        $conn = getDBConnection();
        
        // Get total voters
        $stmt = $conn->query("SELECT COUNT(*) as total FROM voters WHERE status = 'active'");
        $total_voters = $stmt->fetch()['total'];
        
        // Get active elections
        $stmt = $conn->query("SELECT COUNT(*) as total FROM elections WHERE status = 'active'");
        $active_elections = $stmt->fetch()['total'];
        
        // Get total votes cast
        $stmt = $conn->query("SELECT COUNT(*) as total FROM votes");
        $total_votes = $stmt->fetch()['total'];
        
        // Get total candidates
        $stmt = $conn->query("SELECT COUNT(*) as total FROM candidates WHERE status = 'active'");
        $total_candidates = $stmt->fetch()['total'];
        
        // Calculate participation rate
        $participation_rate = $total_voters > 0 ? round(($total_votes / $total_voters) * 100, 1) : 0;
        
        return [
            'total_voters' => $total_voters,
            'active_elections' => $active_elections,
            'total_votes' => $total_votes,
            'total_candidates' => $total_candidates,
            'participation_rate' => $participation_rate
        ];
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [
            'total_voters' => 0,
            'active_elections' => 0,
            'total_votes' => 0,
            'total_candidates' => 0,
            'participation_rate' => 0
        ];
    }
}

// Get recent activity
function getRecentActivity() {
    try {
        $conn = getDBConnection();
        $stmt = $conn->query("
            SELECT 
                action,
                entity_type,
                entity_id,
                new_values,
                created_at,
                CASE 
                    WHEN user_type = 'admin' THEN (SELECT fullname FROM admins WHERE admin_id = audit_logs.user_id)
                    WHEN user_type = 'voter' THEN (SELECT full_name FROM voters WHERE id = audit_logs.user_id)
                    ELSE 'System'
                END as user_name
            FROM audit_logs 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [];
    }
}

// Get notification count (pending approvals, etc.)
function getNotificationCount() {
    try {
        $conn = getDBConnection();
        
        // Count pending voters
        $stmt = $conn->query("SELECT COUNT(*) as count FROM voters WHERE status = 'pending'");
        $pending_voters = $stmt->fetch()['count'];
        
        // Count pending candidates
        $stmt = $conn->query("SELECT COUNT(*) as count FROM candidates WHERE status = 'pending'");
        $pending_candidates = $stmt->fetch()['count'];
        
        return $pending_voters + $pending_candidates;
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return 0;
    }
}

// Format activity description
function formatActivityDescription($activity) {
    $action = ucfirst(strtolower($activity['action']));
    $entity = ucfirst($activity['entity_type']);
    
    switch($activity['action']) {
        case 'CREATE':
            return "New {$entity} created";
        case 'UPDATE':
            return "{$entity} updated";
        case 'DELETE':
            return "{$entity} deleted";
        case 'VOTE':
            return "Vote cast in election";
        default:
            return "{$action} performed on {$entity}";
    }
}

// Time ago function
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    
    return date('M j, Y', strtotime($datetime));
}

$dashboardStats = getDashboardStats();
$recent_activity = getRecentActivity();
$notification_count = getNotificationCount();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - University Voting System</title>
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

        .notification-badge {
            position: absolute;
            top: 0.25rem;
            right: 0.25rem;
            background: var(--error);
            color: white;
            font-size: 0.625rem;
            padding: 0.125rem 0.25rem;
            border-radius: 50%;
            min-width: 1rem;
            height: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
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

        /* Dashboard Content */
        .dashboard-content {
            max-width: 100%;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--text-inverse);
            padding: 2rem 1.5rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            border-radius: var(--radius-xl);
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 1rem;
            background: linear-gradient(45deg, #fff, #e0e7ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
        }

        .hero-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 1.5rem;
            max-width: 600px;
            line-height: 1.5;
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .hero-stat {
            text-align: center;
        }

        .hero-stat-number {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            display: block;
        }

        .hero-stat-label {
            font-size: 0.8rem;
            opacity: 0.8;
        }

        .hero-bg {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            opacity: 0.1;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.4'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-icon {
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

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .stat-change {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius-sm);
        }

        .stat-change.positive {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .stat-change.negative {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error);
        }

        /* CTA Section */
        .cta-section {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            text-align: center;
            margin-bottom: 2rem;
            border: 1px solid var(--border);
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
        }

        .cta-button {
            background: var(--primary-gradient);
            color: var(--text-inverse);
            padding: 0.875rem 1.5rem;
            border: none;
            border-radius: var(--radius-lg);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: var(--shadow-md);
        }

        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Quick Actions */
        .quick-actions {
            margin-bottom: 2rem;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.25rem;
        }

        .action-card {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            border: 1px solid var(--border);
        }

        .action-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .action-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: var(--radius-lg);
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.125rem;
            margin-bottom: 1rem;
        }

        .action-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .action-description {
            color: var(--text-secondary);
            font-size: 0.8rem;
            line-height: 1.4;
        }

        /* Recent Activity */
        .recent-activity {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
            border: 1px solid var(--border);
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-light);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background: var(--surface-alt);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: var(--primary);
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .activity-time {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        /* Footer */
        .footer {
            background: var(--surface);
            padding: 1.5rem;
            border: 1px solid var(--border);
            text-align: center;
            color: var(--text-secondary);
            margin-top: 2rem;
            border-radius: var(--radius-xl);
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.2s ease;
        }

        .footer-links a:hover {
            color: var(--primary);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        @keyframes countUp {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }

        .animate-fade-in {
            animation: fadeInUp 0.6s ease forwards;
        }

        .animate-pulse {
            animation: pulse 2s ease-in-out infinite;
        }

        .animate-count {
            animation: countUp 1s ease forwards;
        }

        /* Loading Animation */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid var(--border);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
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

            .hero-title {
                font-size: 1.5rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .actions-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .footer-links {
                flex-direction: column;
                gap: 1rem;
            }

            .hero-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }

            .top-nav {
                padding: 1rem;
                margin-bottom: 1rem;
            }

            .hero-section {
                padding: 1.5rem 1rem;
                margin-bottom: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 1rem;
            }

            .hero-section {
                padding: 1rem;
            }

            .stat-card {
                padding: 1rem;
            }

            .action-card {
                padding: 1rem;
            }

            .recent-activity {
                padding: 1rem;
            }
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

        /* Utility Classes */
        .text-gradient {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .shadow-glow {
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.3);
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
                    <span class="current">Dashboard</span>
                </div>
                <div class="top-actions">
                    <button class="notification-btn" onclick="window.location.href='admin_notifications.php'">
                        <i class="fas fa-bell"></i>
                        <?php if ($notification_count > 0): ?>
                            <span class="notification-badge"><?php echo $notification_count; ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="user-menu" onclick="toggleUserDropdown()">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($admin_user['fullname'] ?? 'AU', 0, 2)); ?>
                        </div>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
            </nav>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Hero Section -->
                <section class="hero-section animate-fade-in">
                    <div class="hero-bg"></div>
                    <div class="hero-content">
                        <h1 class="hero-title">Welcome to University Voting System</h1>
                        <p class="hero-subtitle">
                            Manage elections, monitor voter engagement, and ensure democratic processes 
                            across your institution with our comprehensive voting platform.
                        </p>
                        <div class="hero-stats">
                            <div class="hero-stat">
                                <span class="hero-stat-number animate-count"><?php echo number_format($dashboardStats['total_voters']); ?></span>
                                <span class="hero-stat-label">Registered Voters</span>
                            </div>
                            <div class="hero-stat">
                                <span class="hero-stat-number animate-count"><?php echo $dashboardStats['active_elections']; ?></span>
                                <span class="hero-stat-label">Active Elections</span>
                            </div>
                            <div class="hero-stat">
                                <span class="hero-stat-number animate-count"><?php echo $dashboardStats['participation_rate']; ?>%</span>
                                <span class="hero-stat-label">Participation Rate</span>
                            </div>
                            <div class="hero-stat">
                                <span class="hero-stat-number animate-count"><?php echo number_format($dashboardStats['total_candidates']); ?></span>
                                <span class="hero-stat-label">Candidates</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Stats Grid -->
                <section class="stats-grid">
                    <div class="stat-card animate-fade-in">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-vote-yea"></i>
                            </div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i> +12.5%
                            </div>
                        </div>
                        <div class="stat-number"><?php echo number_format($dashboardStats['total_votes']); ?></div>
                        <div class="stat-label">Total Votes Cast</div>
                    </div>

                    <div class="stat-card animate-fade-in">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-poll"></i>
                            </div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i> +8.2%
                            </div>
                        </div>
                        <div class="stat-number"><?php echo $dashboardStats['active_elections']; ?></div>
                        <div class="stat-label">Active Elections</div>
                    </div>

                    <div class="stat-card animate-fade-in">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i> +5.7%
                            </div>
                        </div>
                        <div class="stat-number"><?php echo number_format($dashboardStats['total_voters']); ?></div>
                        <div class="stat-label">Registered Voters</div>
                    </div>

                    <div class="stat-card animate-fade-in">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="stat-change positive">
                                <i class="fas fa-arrow-up"></i> +15.3%
                            </div>
                        </div>
                        <div class="stat-number"><?php echo number_format($dashboardStats['total_candidates']); ?></div>
                        <div class="stat-label">Total Candidates</div>
                    </div>
                </section>

                <!-- CTA Section -->
                <section class="cta-section animate-fade-in">
                    <h2 class="section-title">Ready to Create Your Next Election?</h2>
                    <p style="margin-bottom: 2rem; color: var(--text-secondary);">
                        Set up a new election campaign with our intuitive election management tools.
                    </p>
                    <a href="admin_elections.php" class="cta-button">
                        <i class="fas fa-plus"></i>
                        Create New Election
                    </a>
                </section>

                <!-- Quick Actions -->
                <section class="quick-actions">
                    <h2 class="section-title">Quick Actions</h2>
                    <div class="actions-grid">
                        <a href="admin_elections.php" class="action-card animate-fade-in">
                            <div class="action-icon">
                                <i class="fas fa-poll"></i>
                            </div>
                            <div class="action-title">Manage Elections</div>
                            <div class="action-description">
                                Create, edit, and monitor election campaigns across your institution
                            </div>
                        </a>

                        <a href="admin_voters.php" class="action-card animate-fade-in">
                            <div class="action-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="action-title">Voter Management</div>
                            <div class="action-description">
                                Register new voters, verify eligibility, and manage voter database
                            </div>
                        </a>

                        <a href="admin_candidates.php" class="action-card animate-fade-in">
                            <div class="action-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="action-title">Candidate Registration</div>
                            <div class="action-description">
                                Approve candidate applications and manage candidate profiles
                            </div>
                        </a>

                        <a href="admin_results.php" class="action-card animate-fade-in">
                            <div class="action-icon">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <div class="action-title">View Results</div>
                            <div class="action-description">
                                Monitor real-time voting results and generate comprehensive reports
                            </div>
                        </a>

                        <a href="admin_analytics.php" class="action-card animate-fade-in">
                            <div class="action-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="action-title">Analytics Dashboard</div>
                            <div class="action-description">
                                Track voter engagement, participation trends, and system performance
                            </div>
                        </a>

                        <a href="admin_settings.php" class="action-card animate-fade-in">
                            <div class="action-icon">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div class="action-title">System Settings</div>
                            <div class="action-description">
                                Configure system parameters, security settings, and platform preferences
                            </div>
                        </a>
                    </div>
                </section>

                <!-- Recent Activity -->
                <section class="recent-activity animate-fade-in">
                    <h2 class="section-title">Recent Activity</h2>
                    <div class="activity-list">
                        <?php if (!empty($recent_activity)): ?>
                            <?php foreach (array_slice($recent_activity, 0, 5) as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <?php
                                        $icon = 'fas fa-info';
                                        switch($activity['action']) {
                                            case 'CREATE': $icon = 'fas fa-plus'; break;
                                            case 'UPDATE': $icon = 'fas fa-edit'; break;
                                            case 'DELETE': $icon = 'fas fa-trash'; break;
                                            case 'VOTE': $icon = 'fas fa-vote-yea'; break;
                                        }
                                        ?>
                                        <i class="<?php echo $icon; ?>"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title"><?php echo formatActivityDescription($activity); ?></div>
                                        <div class="activity-time"><?php echo timeAgo($activity['created_at']); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-info"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">Welcome to the admin dashboard</div>
                                    <div class="activity-time">Start managing your voting system</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Footer -->
                <footer class="footer">
                    <div class="footer-content">
                        <div class="footer-links">
                            <a href="admin_help.php">Help & Support</a>
                            <a href="admin_documentation.php">Documentation</a>
                            <a href="admin_privacy.php">Privacy Policy</a>
                            <a href="admin_terms.php">Terms of Service</a>
                            <a href="admin_contact.php">Contact Us</a>
                        </div>
                        <p>&copy; <?php echo date('Y'); ?> University Voting System. All rights reserved. Developed with ❤️ for democratic institutions.</p>
                    </div>
                </footer>
            </div>
        </main>
    </div>

    <!-- JavaScript for Interactivity -->
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

        // Animate numbers on load
        function animateNumbers() {
            const numberElements = document.querySelectorAll('.hero-stat-number, .stat-number');
            
            numberElements.forEach(element => {
                const finalText = element.textContent;
                const finalNumber = parseFloat(finalText.replace(/[,%]/g, ''));
                const isPercentage = finalText.includes('%');
                const hasComma = finalText.includes(',');
                
                if (!isNaN(finalNumber)) {
                    const duration = 2000;
                    const start = performance.now();
                    
                    function updateNumber(currentTime) {
                        const elapsed = currentTime - start;
                        const progress = Math.min(elapsed / duration, 1);
                        
                        const easeOutQuart = 1 - Math.pow(1 - progress, 4);
                        const currentNumber = Math.floor(finalNumber * easeOutQuart);
                        
                        let displayNumber = currentNumber.toString();
                        if (hasComma && currentNumber >= 1000) {
                            displayNumber = currentNumber.toLocaleString();
                        }
                        if (isPercentage) {
                            displayNumber += '%';
                        }
                        
                        element.textContent = displayNumber;
                        
                        if (progress < 1) {
                            requestAnimationFrame(updateNumber);
                        } else {
                            element.textContent = finalText;
                        }
                    }
                    
                    element.textContent = '0';
                    requestAnimationFrame(updateNumber);
                }
            });
        }

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(animateNumbers, 500);
            
            // Add keyboard navigation support
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const sidebar = document.getElementById('sidebar');
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                    hamburgerBtn.classList.remove('active');
                }
            });

            console.log('Dashboard with Modern Sidebar initialized successfully');
        });

        // Session timeout handling
        <?php if (isset($_SESSION['login_time'])): ?>
        const sessionTimeout = <?php echo (ini_get('session.gc_maxlifetime') ?: 1440) * 1000; ?>;
        
        setTimeout(function() {
            if (confirm('Your session is about to expire. Click OK to extend your session.')) {
                window.location.reload();
            } else {
                window.location.href = 'logout.php';
            }
        }, sessionTimeout - 300000);
        <?php endif; ?>

        console.log('Admin Dashboard with Modern Sidebar initialized');
        console.log('Navigation features:', {
            'mobileToggle': 'Hamburger menu for mobile devices',
            'responsiveDesign': 'Adaptive layout for all screen sizes',
            'keyboardSupport': 'ESC key closes mobile menu',
            'animations': 'Smooth transitions and number animations'
        });
    </script>
</body>
</html>