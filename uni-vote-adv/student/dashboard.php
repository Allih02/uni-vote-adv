<?php
// dashboard.php - Modern Student Dashboard with Active Elections Prioritized
session_start();

// Database Configuration
class Database {
    private $host = 'localhost';
    private $db_name = 'voting_system';
    private $username = 'root';  // Change to your MySQL username
    private $password = '';      // Change to your MySQL password
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                )
            );
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}

// Helper functions
function executeQuery($sql, $params = []) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $e) {
        error_log("Database query error: " . $e->getMessage());
        return false;
    }
}

function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetch() : null;
}

function getVoterById($id) {
    $sql = "SELECT v.*, 
                   COALESCE((SELECT COUNT(*) FROM votes WHERE voter_id = v.id), 0) as votes_cast,
                   COALESCE((SELECT COUNT(*) FROM elections e 
                            WHERE JSON_CONTAINS(e.eligible_years, CONCAT('\"', v.year, '\"'))
                            AND JSON_CONTAINS(e.eligible_faculties, CONCAT('\"', v.faculty, '\"'))
                            AND e.status = 'active'
                           ), 0) as eligible_elections
            FROM voters v
            WHERE v.id = :id";
    
    return fetchOne($sql, ['id' => $id]);
}

// Check if user is logged in
if (!isset($_SESSION['voter_id'])) {
    header("Location: student_login.php");
    exit();
}

// Get voter information from database
$voter = getVoterById($_SESSION['voter_id']);
if (!$voter) {
    session_destroy();
    header("Location: student_login.php?error=session_expired");
    exit();
}

// Update session data with latest voter information
$_SESSION['full_name'] = $voter['full_name'];
$_SESSION['email'] = $voter['email'];
$_SESSION['program'] = $voter['program'];
$_SESSION['year'] = $voter['year'];
$_SESSION['faculty'] = $voter['faculty'];
$_SESSION['status'] = $voter['status'];

// Get ONLY ACTIVE elections that the voter is eligible for and haven't voted in yet
$eligible_elections_sql = "SELECT e.*, 
                          CASE 
                              WHEN v.id IS NOT NULL THEN 1 
                              ELSE 0 
                          END as already_voted
                          FROM elections e
                          LEFT JOIN votes v ON e.id = v.election_id AND v.voter_id = :voter_id
                          WHERE e.status = 'active' 
                          AND JSON_CONTAINS(e.eligible_years, :year)
                          AND JSON_CONTAINS(e.eligible_faculties, :faculty)
                          AND e.start_date <= NOW() 
                          AND e.end_date >= NOW()
                          AND v.id IS NULL
                          ORDER BY e.end_date ASC";

$eligible_elections = fetchAll($eligible_elections_sql, [
    'voter_id' => $voter['id'],
    'year' => '"' . $voter['year'] . '"',
    'faculty' => '"' . $voter['faculty'] . '"'
]);

// Get elections the voter has already voted in (from active elections only)
$voted_elections_sql = "SELECT e.*, v.voted_at 
                       FROM elections e
                       INNER JOIN votes v ON e.id = v.election_id
                       WHERE v.voter_id = :voter_id
                       AND e.status = 'active'
                       ORDER BY v.voted_at DESC";

$voted_elections = fetchAll($voted_elections_sql, ['voter_id' => $voter['id']]);

// Get upcoming ACTIVE elections (not yet started but active status)
$upcoming_elections_sql = "SELECT e.* FROM elections e
                          WHERE e.status = 'active' 
                          AND JSON_CONTAINS(e.eligible_years, :year)
                          AND JSON_CONTAINS(e.eligible_faculties, :faculty)
                          AND e.start_date > NOW()
                          ORDER BY e.start_date ASC
                          LIMIT 3";

$upcoming_elections = fetchAll($upcoming_elections_sql, [
    'year' => '"' . $voter['year'] . '"',
    'faculty' => '"' . $voter['faculty'] . '"'
]);

// Get total count of active elections for this voter
$total_active_elections_sql = "SELECT COUNT(*) as count FROM elections e
                              WHERE e.status = 'active' 
                              AND JSON_CONTAINS(e.eligible_years, :year)
                              AND JSON_CONTAINS(e.eligible_faculties, :faculty)";

$total_active_result = fetchOne($total_active_elections_sql, [
    'year' => '"' . $voter['year'] . '"',
    'faculty' => '"' . $voter['faculty'] . '"'
]);

$total_active_elections = $total_active_result['count'] ?? 0;

// Calculate participation rate based on active elections
$participation_rate = $total_active_elections > 0 ? 
    round(($voter['votes_cast'] / $total_active_elections) * 100) : 0;

// Helper function to check if election is currently running
function isElectionRunning($start_date, $end_date) {
    $now = new DateTime();
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    
    return ($now >= $start && $now <= $end);
}

// Helper function to get election time remaining
function getTimeRemaining($end_date) {
    $now = new DateTime();
    $end = new DateTime($end_date);
    
    if ($now > $end) {
        return "Election ended";
    }
    
    $interval = $now->diff($end);
    
    if ($interval->days > 0) {
        return $interval->days . " day" . ($interval->days > 1 ? "s" : "") . " remaining";
    } elseif ($interval->h > 0) {
        return $interval->h . " hour" . ($interval->h > 1 ? "s" : "") . " remaining";
    } else {
        return $interval->i . " minute" . ($interval->i > 1 ? "s" : "") . " remaining";
    }
}

// Get first name from full name
function getFirstName($fullName) {
    $parts = explode(' ', trim($fullName));
    return $parts[0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - University Voting System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Modern Color Palette */
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #a5b4fc;
            --secondary: #8b5cf6;
            --accent: #ec4899;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --info: #06b6d4;
            
            /* Neutral Colors */
            --background: #fafbfc;
            --surface: #ffffff;
            --surface-elevated: #ffffff;
            --surface-hover: #f8fafc;
            --border: #e5e7eb;
            --border-light: #f3f4f6;
            
            /* Text Colors */
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --text-muted: #9ca3af;
            --text-inverse: #ffffff;
            
            /* Shadows */
            --shadow-xs: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-sm: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            
            /* Border Radius */
            --radius-sm: 0.375rem;
            --radius: 0.5rem;
            --radius-md: 0.75rem;
            --radius-lg: 1rem;
            --radius-xl: 1.5rem;
            
            /* Transitions */
            --transition-fast: 150ms ease-in-out;
            --transition-normal: 250ms ease-in-out;
            --transition-slow: 350ms ease-in-out;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--background);
            color: var(--text-primary);
            line-height: 1.6;
            font-size: 16px;
            overflow-x: hidden;
        }

        /* Smooth Loading Animation */
        .page-loader {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--surface);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 1;
            transition: opacity 0.5s ease;
        }

        .page-loader.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .loader-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--border);
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Modern Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-light);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: all var(--transition-normal);
        }

        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: var(--shadow-sm);
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--primary);
            text-decoration: none;
            transition: transform var(--transition-fast);
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
        }

        .user-info {
            text-align: right;
            display: none;
        }

        .user-name {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.875rem;
            margin-bottom: 0.125rem;
        }

        .user-role {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .user-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
        }

        .user-avatar::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .user-avatar:hover::before {
            left: 100%;
        }

        .user-avatar:hover {
            transform: scale(1.1);
            box-shadow: var(--shadow-lg);
        }

        .logout-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0.75rem;
            border-radius: var(--radius);
            transition: all var(--transition-fast);
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .logout-btn:hover {
            color: var(--error);
            background: var(--surface-hover);
            transform: scale(1.1);
        }

        /* Priority Alert for Active Elections */
        .priority-alert {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #bae6fd 100%);
            border: 2px solid var(--info);
            border-radius: var(--radius-xl);
            padding: 1.5rem 2rem;
            margin: 2rem auto 0;
            max-width: 1400px;
            margin-left: 2rem;
            margin-right: 2rem;
            position: relative;
            overflow: hidden;
            animation: priorityPulse 2s ease-in-out infinite;
        }

        .priority-alert::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(6, 182, 212, 0.1), transparent);
            animation: priorityShimmer 3s ease-in-out infinite;
        }

        @keyframes priorityPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(6, 182, 212, 0.4); }
            50% { box-shadow: 0 0 0 10px rgba(6, 182, 212, 0); }
        }

        @keyframes priorityShimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .priority-content {
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
            z-index: 1;
        }

        .priority-icon {
            width: 3.5rem;
            height: 3.5rem;
            background: var(--info);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            animation: priorityBounce 1s ease-in-out infinite;
        }

        @keyframes priorityBounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-5px); }
            60% { transform: translateY(-3px); }
        }

        .priority-text {
            flex: 1;
        }

        .priority-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--info);
            margin-bottom: 0.25rem;
        }

        .priority-message {
            color: #075985;
            font-weight: 500;
        }

        .priority-cta {
            background: var(--info);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-lg);
            text-decoration: none;
            font-weight: 600;
            transition: all var(--transition-normal);
            white-space: nowrap;
        }

        .priority-cta:hover {
            background: #0891b2;
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Main Content */
        .main-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 2rem 4rem;
        }

        /* Welcome Header - Compact Version */
        .welcome-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 50%, var(--accent) 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--radius-xl);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .welcome-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><radialGradient id="a" cx="50%" cy="50%"><stop offset="0%" stop-color="%23ffffff" stop-opacity="0.1"/><stop offset="100%" stop-color="%23ffffff" stop-opacity="0"/></radialGradient></defs><circle cx="200" cy="200" r="150" fill="url(%23a)"/><circle cx="800" cy="300" r="100" fill="url(%23a)"/></svg>') no-repeat center center;
            background-size: cover;
            opacity: 0.1;
        }

        .welcome-content {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 2rem;
        }

        .welcome-text h1 {
            font-size: 1.75rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .welcome-text p {
            opacity: 0.9;
            font-size: 1rem;
        }

        .welcome-stats {
            display: flex;
            gap: 1.5rem;
        }

        .welcome-stat {
            text-align: center;
        }

        .welcome-stat-value {
            font-size: 1.5rem;
            font-weight: 800;
            display: block;
        }

        .welcome-stat-label {
            font-size: 0.75rem;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Modern Cards Grid */
        .dashboard-grid {
            display: grid;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .section-card {
            background: var(--surface);
            border-radius: var(--radius-xl);
            border: 1px solid var(--border-light);
            overflow: hidden;
            transition: all var(--transition-normal);
            animation: fadeInUp 0.6s ease-out;
        }

        .section-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
        }

        .card-header {
            padding: 2rem 2rem 1rem;
            background: linear-gradient(135deg, #fafbfc 0%, #f3f4f6 100%);
            border-bottom: 1px solid var(--border-light);
            position: relative;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary), var(--accent));
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-title-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: var(--radius);
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .card-subtitle {
            color: var(--text-secondary);
            font-size: 0.975rem;
            font-weight: 400;
        }

        .card-content {
            padding: 2rem;
        }

        /* Elections Grid */
        .elections-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
        }

        .election-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            padding: 1.5rem;
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
        }

        .election-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            transform: scaleX(0);
            transition: transform var(--transition-normal);
        }

        .election-card:hover::before {
            transform: scaleX(1);
        }

        .election-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-light);
        }

        .election-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            gap: 1rem;
        }

        .election-title {
            font-weight: 600;
            font-size: 1.125rem;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .election-meta {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .election-date {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
            box-shadow: 0 0 0 1px #10b981;
            animation: statusPulse 2s ease-in-out infinite;
        }

        @keyframes statusPulse {
            0%, 100% { box-shadow: 0 0 0 1px #10b981; }
            50% { box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.3); }
        }

        .status-voted {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-upcoming {
            background: #fef3c7;
            color: #92400e;
        }

        .election-description {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .time-remaining {
            background: linear-gradient(135deg, #fef3c7, #fed7aa);
            color: #92400e;
            padding: 0.5rem 1rem;
            border-radius: var(--radius);
            font-size: 0.875rem;
            font-weight: 600;
            margin: 1rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Priority Election Card */
        .election-card.priority {
            border: 2px solid var(--success);
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            transform: scale(1.02);
            box-shadow: var(--shadow-lg);
        }

        .election-card.priority::before {
            background: linear-gradient(90deg, var(--success), var(--info));
            transform: scaleX(1);
        }

        .election-card.priority .election-title {
            color: var(--success);
        }

        /* Modern Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.875rem;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all var(--transition-normal);
            white-space: nowrap;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-priority {
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
            box-shadow: var(--shadow-md);
            font-weight: 700;
            animation: btnPulse 2s ease-in-out infinite;
        }

        @keyframes btnPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .btn-priority:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: var(--shadow-xl);
            background: linear-gradient(135deg, #059669, #047857);
        }

        .btn-secondary {
            background: var(--surface);
            color: var(--text-primary);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--surface-hover);
            border-color: var(--primary);
            color: var(--primary);
        }

        .btn-group {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .action-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            text-align: center;
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
        }

        .action-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(99, 102, 241, 0.1), transparent);
            transform: rotate(45deg);
            transition: all 0.6s;
            opacity: 0;
        }

        .action-card:hover::before {
            opacity: 1;
            top: -25%;
            right: -25%;
        }

        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary-light);
        }

        .action-icon {
            width: 4rem;
            height: 4rem;
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            color: white;
            position: relative;
            z-index: 1;
        }

        .action-title {
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
        }

        .action-description {
            color: var(--text-secondary);
            font-size: 0.875rem;
            line-height: 1.5;
            margin-bottom: 1.5rem;
        }

        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-muted);
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.3;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .empty-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
        }

        .empty-description {
            font-size: 0.975rem;
            color: var(--text-muted);
        }

        /* Student Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .info-section {
            background: var(--surface-hover);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            border-left: 4px solid var(--primary);
        }

        .info-title {
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-list {
            display: grid;
            gap: 0.75rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
        }

        .info-label {
            font-weight: 500;
            color: var(--text-secondary);
        }

        .info-value {
            font-weight: 600;
            color: var(--text-primary);
        }

        /* Progress Bar */
        .progress-bar {
            width: 100%;
            height: 8px;
            background: var(--border-light);
            border-radius: 4px;
            overflow: hidden;
            margin: 1rem 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 4px;
            transition: width var(--transition-slow);
            position: relative;
        }

        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
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

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Mobile Navigation */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-primary);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: var(--radius);
            transition: all var(--transition-fast);
        }

        .mobile-menu-btn:hover {
            background: var(--surface-hover);
        }

        /* Toast Notification */
        .toast {
            position: fixed;
            top: 2rem;
            right: 2rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1rem 1.5rem;
            box-shadow: var(--shadow-xl);
            z-index: 10000;
            min-width: 350px;
            max-width: 500px;
            transform: translateX(100%);
            transition: transform var(--transition-normal);
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast-content {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .toast-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
        }

        .toast-text {
            flex: 1;
        }

        .toast-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--text-primary);
        }

        .toast-message {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .toast-close {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0.25rem;
            border-radius: var(--radius);
            transition: all var(--transition-fast);
        }

        .toast-close:hover {
            color: var(--text-primary);
            background: var(--surface-hover);
        }

        .toast-priority {
            border-left: 4px solid var(--success);
            box-shadow: var(--shadow-xl), 0 0 20px rgba(16, 185, 129, 0.3);
        }

        /* Footer */
        .footer {
            background: var(--surface);
            border-top: 1px solid var(--border-light);
            padding: 3rem 0;
            margin-top: 4rem;
        }

        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-section h4 {
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .footer-links {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .footer-link {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.875rem;
            transition: color var(--transition-fast);
        }

        .footer-link:hover {
            color: var(--primary);
        }

        .footer-bottom {
            border-top: 1px solid var(--border-light);
            padding-top: 2rem;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .nav-container,
            .main-content,
            .footer-container {
                padding-left: 1.5rem;
                padding-right: 1.5rem;
            }

            .priority-alert {
                margin-left: 1.5rem;
                margin-right: 1.5rem;
            }

            .elections-container {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }

            .user-info {
                display: none;
            }

            .nav-container,
            .main-content,
            .footer-container {
                padding-left: 1rem;
                padding-right: 1rem;
            }

            .priority-alert {
                margin-left: 1rem;
                margin-right: 1rem;
                padding: 1rem 1.5rem;
            }

            .priority-content {
                flex-direction: column;
                text-align: center;
                gap: 1.5rem;
            }

            .priority-cta {
                width: 100%;
                justify-content: center;
            }

            .welcome-content {
                flex-direction: column;
                text-align: center;
            }

            .welcome-stats {
                justify-content: center;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .election-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .btn-group {
                flex-direction: column;
                width: 100%;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .toast {
                left: 1rem;
                right: 1rem;
                min-width: auto;
                transform: translateY(-100%);
            }

            .toast.show {
                transform: translateY(0);
            }
        }

        @media (max-width: 480px) {
            .welcome-header {
                padding: 1.5rem;
            }

            .welcome-text h1 {
                font-size: 1.5rem;
            }

            .welcome-stats {
                flex-direction: column;
                gap: 1rem;
            }

            .card-content {
                padding: 1.5rem;
            }

            .action-card {
                padding: 1rem;
            }

            .action-icon {
                width: 3rem;
                height: 3rem;
                font-size: 1.5rem;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            :root {
                --background: #0f1419;
                --surface: #1a1f24;
                --surface-elevated: #242a30;
                --surface-hover: #2a3036;
                --border: #374151;
                --border-light: #2d3748;
                --text-primary: #f9fafb;
                --text-secondary: #d1d5db;
                --text-muted: #9ca3af;
            }

            .navbar {
                background: rgba(26, 31, 36, 0.95);
            }

            .card-header {
                background: linear-gradient(135deg, #1a1f24 0%, #2d3748 100%);
            }

            .priority-alert {
                background: linear-gradient(135deg, #0c4a6e 0%, #075985 50%, #0369a1 100%);
                border-color: #0891b2;
            }

            .priority-title {
                color: #0ea5e9;
            }

            .priority-message {
                color: #bae6fd;
            }

            .status-active {
                background: #065f46;
                color: #dcfce7;
            }

            .status-voted {
                background: #1e40af;
                color: #dbeafe;
            }

            .status-upcoming {
                background: #92400e;
                color: #fef3c7;
            }
        }

        /* Focus styles for accessibility */
        .btn:focus,
        .logout-btn:focus,
        .user-avatar:focus {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        /* Reduced motion */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Print styles */
        @media print {
            .priority-alert,
            .navbar,
            .footer,
            .btn,
            .page-loader {
                display: none !important;
            }
            
            .welcome-header {
                background: none !important;
                color: #000 !important;
                border: 1px solid #ccc;
            }
            
            .election-card {
                break-inside: avoid;
                border: 1px solid #ccc;
                margin-bottom: 1rem;
            }
            
            .section-card {
                box-shadow: none !important;
                border: 1px solid #ccc;
            }
        }
    </style>
</head>
<body>
    <div class="page-loader" id="pageLoader">
        <div class="loader-spinner"></div>
    </div>

    <!-- Modern Navigation -->
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="dashboard.php" class="logo">
                <div class="logo-icon">
                    <i class="fas fa-vote-yea"></i>
                </div>
                <span>VoteSystem</span>
            </a>
            
            <div class="nav-menu">
                <div class="user-profile">
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($voter['full_name']); ?></div>
                        <div class="user-role"><?php echo htmlspecialchars($voter['student_id']); ?> • <?php echo htmlspecialchars($voter['program']); ?></div>
                    </div>
                    <div class="user-avatar" title="<?php echo htmlspecialchars($voter['full_name']); ?>">
                        <?php echo strtoupper(substr($voter['full_name'], 0, 1)); ?>
                    </div>
                    <a href="logout.php" class="logout-btn" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Priority Alert for Active Elections -->
    <?php if (!empty($eligible_elections)): ?>
    <div class="priority-alert">
        <div class="priority-content">
            <div class="priority-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="priority-text">
                <div class="priority-title">🗳️ Active Elections Available!</div>
                <div class="priority-message">
                    You have <?php echo count($eligible_elections); ?> election<?php echo count($eligible_elections) > 1 ? 's' : ''; ?> available for voting. Don't miss your chance to make your voice heard!
                </div>
            </div>
            <a href="#active-elections" class="priority-cta">
                <i class="fas fa-arrow-down"></i>
                Vote Now
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Compact Welcome Header -->
        <div class="welcome-header">
            <div class="welcome-content">
                <div class="welcome-text">
                    <h1>Welcome back, <?php echo htmlspecialchars(getFirstName($voter['full_name'])); ?>!</h1>
                    <p>Your democratic participation matters. Stay engaged with your university community.</p>
                </div>
                <div class="welcome-stats">
                    <div class="welcome-stat">
                        <span class="welcome-stat-value"><?php echo $voter['votes_cast']; ?></span>
                        <span class="welcome-stat-label">Votes Cast</span>
                    </div>
                    <div class="welcome-stat">
                        <span class="welcome-stat-value"><?php echo count($eligible_elections); ?></span>
                        <span class="welcome-stat-label">Active</span>
                    </div>
                    <div class="welcome-stat">
                        <span class="welcome-stat-value"><?php echo $participation_rate; ?>%</span>
                        <span class="welcome-stat-label">Participation</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Elections Section - TOP PRIORITY -->
        <div class="section-card" id="active-elections" style="animation-delay: 0s;">
            <div class="card-header">
                <h2 class="card-title">
                    <div class="card-title-icon">
                        <i class="fas fa-vote-yea"></i>
                    </div>
                    🔥 Active Elections - Vote Now!
                </h2>
                <p class="card-subtitle">Elections you can participate in right now - Time sensitive!</p>
            </div>
            <div class="card-content">
                <?php if (empty($eligible_elections)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-vote-yea"></i>
                        </div>
                        <h3 class="empty-title">No Active Elections</h3>
                        <p class="empty-description">There are currently no elections available for voting. Check back soon or view upcoming elections below.</p>
                    </div>
                <?php else: ?>
                    <div class="elections-container">
                        <?php foreach ($eligible_elections as $index => $election): ?>
                        <div class="election-card priority" data-election-id="<?php echo $election['id']; ?>" style="animation: fadeInUp 0.4s ease-out <?php echo ($index * 0.1); ?>s both;">
                            <div class="election-header">
                                <div>
                                    <h3 class="election-title">
                                        <i class="fas fa-fire" style="color: var(--error); margin-right: 0.5rem;"></i>
                                        <?php echo htmlspecialchars($election['title']); ?>
                                    </h3>
                                    <div class="election-meta">
                                        <div class="election-date">
                                            <i class="fas fa-clock"></i>
                                            Ends: <?php echo date('M d, Y H:i', strtotime($election['end_date'])); ?>
                                        </div>
                                        <div class="election-date">
                                            <i class="fas fa-users"></i>
                                            <?php echo htmlspecialchars($election['type']); ?>
                                        </div>
                                    </div>
                                </div>
                                <span class="status-badge status-active">
                                    <i class="fas fa-circle"></i> Live Now
                                </span>
                            </div>

                            <?php if (isElectionRunning($election['start_date'], $election['end_date'])): ?>
                                <div class="time-remaining" data-end-date="<?php echo $election['end_date']; ?>">
                                    <i class="fas fa-hourglass-half"></i>
                                    ⏰ <?php echo getTimeRemaining($election['end_date']); ?>
                                </div>
                            <?php endif; ?>

                            <p class="election-description"><?php echo htmlspecialchars($election['description']); ?></p>

                            <div class="btn-group">
                                <a href="election_details.php?id=<?php echo $election['id']; ?>" class="btn btn-priority">
                                    <i class="fas fa-vote-yea"></i>
                                    🗳️ Vote Now
                                </a>
                                <a href="election_details.php?id=<?php echo $election['id']; ?>" class="btn btn-secondary">
                                    <i class="fas fa-info-circle"></i>
                                    Details
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions Section -->
        <div class="section-card" style="animation-delay: 0.2s;">
            <div class="card-header">
                <h2 class="card-title">
                    <div class="card-title-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    Quick Actions
                </h2>
                <p class="card-subtitle">Frequently used actions and information</p>
            </div>
            <div class="card-content">
                <div class="quick-actions">
                    <div class="action-card">
                        <div class="action-icon" style="background: linear-gradient(135deg, var(--info), var(--primary));">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <h3 class="action-title">Profile Management</h3>
                        <p class="action-description">View and update your personal information, contact details, and account settings.</p>
                        <a href="profile.php" class="btn btn-primary">
                            <i class="fas fa-edit"></i>
                            Edit Profile
                        </a>
                    </div>

                    <div class="action-card">
                        <div class="action-icon" style="background: linear-gradient(135deg, var(--success), var(--info));">
                            <i class="fas fa-history"></i>
                        </div>
                        <h3 class="action-title">Voting History</h3>
                        <p class="action-description">Review all your past voting activities and election participation records.</p>
                        <a href="voting_history.php" class="btn btn-primary">
                            <i class="fas fa-list"></i>
                            View History
                        </a>
                    </div>

                    <div class="action-card">
                        <div class="action-icon" style="background: linear-gradient(135deg, var(--warning), var(--accent));">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <h3 class="action-title">Help & Support</h3>
                        <p class="action-description">Get assistance with the voting process, technical issues, and system guidance.</p>
                        <a href="help.php" class="btn btn-primary">
                            <i class="fas fa-life-ring"></i>
                            Get Help
                        </a>
                    </div>

                    <div class="action-card">
                        <div class="action-icon" style="background: linear-gradient(135deg, var(--secondary), var(--accent));">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h3 class="action-title">Notifications</h3>
                        <p class="action-description">Manage your notification preferences and view recent election updates.</p>
                        <a href="notifications.php" class="btn btn-primary">
                            <i class="fas fa-cog"></i>
                            Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Voting History Section -->
        <?php if (!empty($voted_elections)): ?>
        <div class="section-card" style="animation-delay: 0.3s;">
            <div class="card-header">
                <h2 class="card-title">
                    <div class="card-title-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    Recent Voting Activity
                </h2>
                <p class="card-subtitle">Elections you have participated in</p>
            </div>
            <div class="card-content">
                <div class="elections-container">
                    <?php foreach (array_slice($voted_elections, 0, 3) as $index => $election): ?>
                    <div class="election-card" style="animation: fadeInUp 0.6s ease-out <?php echo (0.4 + $index * 0.1); ?>s both;">
                        <div class="election-header">
                            <div>
                                <h3 class="election-title"><?php echo htmlspecialchars($election['title']); ?></h3>
                                <div class="election-meta">
                                    <div class="election-date">
                                        <i class="fas fa-check"></i>
                                        Voted: <?php echo date('M d, Y H:i', strtotime($election['voted_at'])); ?>
                                    </div>
                                </div>
                            </div>
                            <span class="status-badge status-voted">
                                <i class="fas fa-check-circle"></i> Completed
                            </span>
                        </div>

                        <p class="election-description"><?php echo htmlspecialchars($election['description']); ?></p>

                        <div class="btn-group">
                            <a href="election_results.php?id=<?php echo $election['id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-chart-bar"></i>
                                View Results
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($voted_elections) > 3): ?>
                <div style="text-align: center; margin-top: 1.5rem;">
                    <a href="voting_history.php" class="btn btn-secondary">
                        <i class="fas fa-list"></i>
                        View All History (<?php echo count($voted_elections); ?> total)
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Upcoming Elections Section -->
        <?php if (!empty($upcoming_elections)): ?>
        <div class="section-card" style="animation-delay: 0.4s;">
            <div class="card-header">
                <h2 class="card-title">
                    <div class="card-title-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    Upcoming Elections
                </h2>
                <p class="card-subtitle">Elections scheduled to start soon</p>
            </div>
            <div class="card-content">
                <div class="elections-container">
                    <?php foreach ($upcoming_elections as $index => $election): ?>
                    <div class="election-card" style="animation: fadeInUp 0.6s ease-out <?php echo (0.5 + $index * 0.1); ?>s both;">
                        <div class="election-header">
                            <div>
                                <h3 class="election-title"><?php echo htmlspecialchars($election['title']); ?></h3>
                                <div class="election-meta">
                                    <div class="election-date">
                                        <i class="fas fa-calendar-plus"></i>
                                        Starts: <?php echo date('M d, Y H:i', strtotime($election['start_date'])); ?>
                                    </div>
                                    <div class="election-date">
                                        <i class="fas fa-users"></i>
                                        <?php echo htmlspecialchars($election['type']); ?>
                                    </div>
                                </div>
                            </div>
                            <span class="status-badge status-upcoming">
                                <i class="fas fa-clock"></i> Coming Soon
                            </span>
                        </div>

                        <p class="election-description"><?php echo htmlspecialchars($election['description']); ?></p>

                        <div class="btn-group">
                            <a href="election_details.php?id=<?php echo $election['id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-info-circle"></i>
                                Learn More
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Student Information Summary -->
        <div class="section-card" style="animation-delay: 0.5s;">
            <div class="card-header">
                <h2 class="card-title">
                    <div class="card-title-icon">
                        <i class="fas fa-id-card"></i>
                    </div>
                    Your Information
                </h2>
                <p class="card-subtitle">Current registration and academic details</p>
            </div>
            <div class="card-content">
                <div class="info-grid">
                    <div class="info-section">
                        <h4 class="info-title">
                            <i class="fas fa-user"></i>
                            Personal Details
                        </h4>
                        <div class="info-list">
                            <div class="info-item">
                                <span class="info-label">Full Name:</span>
                                <span class="info-value"><?php echo htmlspecialchars($voter['full_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Student ID:</span>
                                <span class="info-value"><?php echo htmlspecialchars($voter['student_id']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email:</span>
                                <span class="info-value"><?php echo htmlspecialchars($voter['email']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Phone:</span>
                                <span class="info-value"><?php echo htmlspecialchars($voter['phone'] ?? 'Not provided'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Status:</span>
                                <span class="info-value">
                                    <span class="status-badge status-<?php echo $voter['status']; ?>">
                                        <?php echo ucfirst($voter['status']); ?>
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="info-section">
                        <h4 class="info-title">
                            <i class="fas fa-graduation-cap"></i>
                            Academic Information
                        </h4>
                        <div class="info-list">
                            <div class="info-item">
                                <span class="info-label">Program:</span>
                                <span class="info-value"><?php echo htmlspecialchars($voter['program']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Year:</span>
                                <span class="info-value"><?php echo htmlspecialchars($voter['year']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Faculty:</span>
                                <span class="info-value"><?php echo htmlspecialchars($voter['faculty']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Registration Date:</span>
                                <span class="info-value"><?php echo date('M d, Y', strtotime($voter['registration_date'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Last Login:</span>
                                <span class="info-value"><?php echo $voter['last_login'] ? date('M d, Y H:i', strtotime($voter['last_login'])) : 'First login'; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="info-section">
                        <h4 class="info-title">
                            <i class="fas fa-chart-line"></i>
                            Voting Statistics
                        </h4>
                        <div class="info-list">
                            <div class="info-item">
                                <span class="info-label">Total Votes Cast:</span>
                                <span class="info-value"><?php echo $voter['votes_cast']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Eligible Elections:</span>
                                <span class="info-value"><?php echo $total_active_elections; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Participation Rate:</span>
                                <span class="info-value">
                                    <?php echo $participation_rate; ?>%
                                    <div class="progress-bar" style="margin-top: 0.5rem;">
                                        <div class="progress-fill" style="width: <?php echo $participation_rate; ?>%;"></div>
                                    </div>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Active Elections:</span>
                                <span class="info-value"><?php echo count($eligible_elections); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Upcoming Elections:</span>
                                <span class="info-value"><?php echo count($upcoming_elections); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <div class="footer-links">
                        <a href="dashboard.php" class="footer-link">Dashboard</a>
                        <a href="profile.php" class="footer-link">Profile</a>
                        <a href="voting_history.php" class="footer-link">Voting History</a>
                        <a href="help.php" class="footer-link">Help & Support</a>
                    </div>
                </div>
                <div class="footer-section">
                    <h4>Support</h4>
                    <div class="footer-links">
                        <a href="help.php" class="footer-link">Help Center</a>
                        <a href="mailto:support@university.edu" class="footer-link">Contact Support</a>
                        <a href="#" class="footer-link">System Status</a>
                        <a href="#" class="footer-link">Privacy Policy</a>
                    </div>
                </div>
                <div class="footer-section">
                    <h4>University</h4>
                    <div class="footer-links">
                        <a href="#" class="footer-link">Student Services</a>
                        <a href="#" class="footer-link">Academic Calendar</a>
                        <a href="#" class="footer-link">News & Events</a>
                        <a href="#" class="footer-link">Contact Us</a>
                    </div>
                </div>
                <div class="footer-section">
                    <h4>Connect</h4>
                    <div class="footer-links">
                        <a href="#" class="footer-link">Facebook</a>
                        <a href="#" class="footer-link">Twitter</a>
                        <a href="#" class="footer-link">Instagram</a>
                        <a href="#" class="footer-link">LinkedIn</a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> University Voting System. All rights reserved. | Designed for democratic participation.</p>
            </div>
        </div>
    </footer>

    <script>
        // Page initialization
        document.addEventListener('DOMContentLoaded', function() {
            // Hide page loader
            setTimeout(() => {
                document.getElementById('pageLoader').classList.add('hidden');
            }, 800);

            // Initialize navbar scroll effect
            const navbar = document.getElementById('navbar');
            let lastScrollY = window.scrollY;

            window.addEventListener('scroll', () => {
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            });

            // Show welcome notification with priority for active elections
            const activeElections = <?php echo count($eligible_elections); ?>;
            const voterName = "<?php echo addslashes(getFirstName($voter['full_name'])); ?>";
            
            setTimeout(() => {
                if (activeElections > 0) {
                    showToast(
                        '🗳️ Urgent: Elections Available!', 
                        `${voterName}, you have ${activeElections} active election(s) waiting for your vote!`,
                        'success',
                        'high'
                    );
                } else {
                    showToast(
                        'Welcome Back!', 
                        `Hello ${voterName}! No active elections at the moment, but stay tuned for updates.`,
                        'info'
                    );
                }
            }, 1000);

            // Initialize progress bars animation
            animateProgressBars();

            // Add smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Add loading states to buttons with priority styling
            addButtonLoadingStates();

            // Initialize mobile menu
            initializeMobileMenu();

            // Auto-refresh for real-time updates - more frequent for active elections
            startAutoRefresh();

            // Initialize countdown timers
            updateCountdowns();
            setInterval(updateCountdowns, 30000); // Update every 30 seconds for active elections

            // Add intersection observer for animations
            initializeAnimations();

            // Priority focus on active elections
            if (activeElections > 0) {
                // Auto-scroll to active elections after brief delay if user hasn't scrolled
                setTimeout(() => {
                    if (window.scrollY < 100) {
                        document.getElementById('active-elections').scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                }, 3000);
            }
        });

        // Enhanced Toast notification system with priority levels
        function showToast(title, message, type = 'info', priority = 'normal') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}${priority === 'high' ? ' toast-priority' : ''}`;
            
            const iconMap = {
                'info': 'fa-info-circle',
                'success': 'fa-check-circle',
                'warning': 'fa-exclamation-triangle',
                'error': 'fa-times-circle'
            };
            
            const colorMap = {
                'info': 'var(--info)',
                'success': 'var(--success)',
                'warning': 'var(--warning)',
                'error': 'var(--error)'
            };
            
            toast.innerHTML = `
                <div class="toast-content">
                    <div class="toast-icon" style="background: ${colorMap[type]};">
                        <i class="fas ${iconMap[type]}"></i>
                    </div>
                    <div class="toast-text">
                        <div class="toast-title">${title}</div>
                        <div class="toast-message">${message}</div>
                    </div>
                    <button class="toast-close" onclick="closeToast(this)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Show toast with enhanced animation for priority messages
            setTimeout(() => toast.classList.add('show'), 100);
            
            // Auto-remove after longer duration for priority messages
            const duration = priority === 'high' ? 8000 : 5000;
            setTimeout(() => {
                if (document.body.contains(toast)) {
                    closeToast(toast.querySelector('.toast-close'));
                }
            }, duration);
        }

        function closeToast(button) {
            const toast = button.closest('.toast');
            toast.classList.remove('show');
            setTimeout(() => {
                if (document.body.contains(toast)) {
                    document.body.removeChild(toast);
                }
            }, 300);
        }

        // Enhanced progress bar animation
        function animateProgressBars() {
            const progressBars = document.querySelectorAll('.progress-fill');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 500);
            });
        }

        // Enhanced button loading states with priority styling
        function addButtonLoadingStates() {
            document.querySelectorAll('.btn').forEach(button => {
                button.addEventListener('click', function() {
                    if (this.href && this.href.includes('.php')) {
                        this.style.opacity = '0.7';
                        this.style.pointerEvents = 'none';
                        const originalHTML = this.innerHTML;
                        
                        if (this.classList.contains('btn-priority')) {
                            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Vote...';
                        } else {
                            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                        }
                        
                        // Reset after 3 seconds in case navigation fails
                        setTimeout(() => {
                            this.innerHTML = originalHTML;
                            this.style.opacity = '1';
                            this.style.pointerEvents = 'auto';
                        }, 3000);
                    }
                });
            });
        }

        // Mobile menu functionality
        function initializeMobileMenu() {
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const navMenu = document.querySelector('.nav-menu');
            
            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', function() {
                    const icon = this.querySelector('i');
                    if (icon.classList.contains('fa-bars')) {
                        icon.classList.remove('fa-bars');
                        icon.classList.add('fa-times');
                    } else {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                });
            }
        }

        // Enhanced auto-refresh with priority for active elections
        function startAutoRefresh() {
            // More frequent checks when there are active elections
            const refreshInterval = <?php echo count($eligible_elections) > 0 ? 15000 : 30000; ?>; // 15s vs 30s
            
            setInterval(function() {
                fetch('check_active_elections.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.new_elections > 0) {
                            showToast(
                                '🚨 New Elections Available!',
                                `${data.new_elections} new election(s) are now open for voting!`,
                                'success',
                                'high'
                            );
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        }
                        
                        if (data.ending_soon > 0) {
                            showToast(
                                '⏰ Elections Ending Soon!',
                                `${data.ending_soon} election(s) will end within the next hour!`,
                                'warning',
                                'high'
                            );
                        }
                    })
                    .catch(error => console.log('Auto-refresh check failed:', error));
            }, refreshInterval);
        }

        // Enhanced countdown timer updates with urgency indicators
        function updateCountdowns() {
            document.querySelectorAll('.time-remaining').forEach(element => {
                const endDate = element.dataset.endDate;
                if (endDate) {
                    const now = new Date().getTime();
                    const end = new Date(endDate).getTime();
                    const distance = end - now;

                    if (distance > 0) {
                        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));

                        let timeText = '';
                        let urgencyClass = '';
                        
                        if (days > 0) {
                            timeText = `${days} day${days > 1 ? 's' : ''} remaining`;
                            urgencyClass = days <= 1 ? 'urgent' : '';
                        } else if (hours > 0) {
                            timeText = `${hours} hour${hours > 1 ? 's' : ''} remaining`;
                            urgencyClass = hours <= 6 ? 'critical' : 'urgent';
                        } else {
                            timeText = `${minutes} minute${minutes > 1 ? 's' : ''} remaining`;
                            urgencyClass = 'critical';
                        }

                        element.innerHTML = `<i class="fas fa-hourglass-half"></i> ⏰ ${timeText}`;
                        
                        // Add urgency styling
                        if (urgencyClass === 'critical') {
                            element.style.background = 'linear-gradient(135deg, #fecaca, #fca5a5)';
                            element.style.color = '#991b1b';
                            element.style.animation = 'pulse 1s ease-in-out infinite';
                        } else if (urgencyClass === 'urgent') {
                            element.style.background = 'linear-gradient(135deg, #fed7aa, #fdba74)';
                            element.style.color = '#9a3412';
                        }
                    } else {
                        element.innerHTML = '<i class="fas fa-clock"></i> Election ended';
                        element.style.background = 'linear-gradient(135deg, #f3f4f6, #e5e7eb)';
                        element.style.color = '#6b7280';
                    }
                }
            });
        }

        // Enhanced intersection observer for animations
        function initializeAnimations() {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.animationPlayState = 'running';
                        
                        // Add special effects for priority elements
                        if (entry.target.classList.contains('priority')) {
                            entry.target.style.transform = 'scale(1.02)';
                        }
                    }
                });
            }, observerOptions);

            // Observe all animated elements
            document.querySelectorAll('.section-card, .election-card, .action-card').forEach(el => {
                el.style.animationPlayState = 'paused';
                observer.observe(el);
            });
        }

        // Enhanced user experience features
        function enhanceUserExperience() {
            // Add ripple effect to buttons with enhanced effects for priority buttons
            document.querySelectorAll('.btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    const isPriority = this.classList.contains('btn-priority');
                    const rippleColor = isPriority ? 'rgba(16, 185, 129, 0.4)' : 'rgba(255, 255, 255, 0.3)';
                    
                    ripple.style.cssText = `
                        position: absolute;
                        width: ${size}px;
                        height: ${size}px;
                        left: ${x}px;
                        top: ${y}px;
                        background: ${rippleColor};
                        border-radius: 50%;
                        transform: scale(0);
                        animation: ripple 0.6s linear;
                        pointer-events: none;
                    `;
                    
                    this.style.position = 'relative';
                    this.style.overflow = 'hidden';
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        if (this.contains(ripple)) {
                            this.removeChild(ripple);
                        }
                    }, 600);
                });
            });
        }

        // Add enhanced animation keyframes
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
            
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.05); }
            }
            
            .floating {
                animation: float 3s ease-in-out infinite;
            }
            
            @keyframes float {
                0%, 100% { transform: translateY(0px); }
                50% { transform: translateY(-10px); }
            }
        `;
        document.head.appendChild(style);

        // Initialize enhanced UX features
        enhanceUserExperience();

        // Additional helper functions for enhanced functionality
        function checkElectionDeadlines() {
            const activeElections = <?php echo json_encode($eligible_elections); ?>;
            const now = new Date();
            
            activeElections.forEach(election => {
                const endTime = new Date(election.end_date);
                const timeLeft = endTime - now;
                const hoursLeft = timeLeft / (1000 * 60 * 60);
                
                // Show urgent notification if less than 2 hours remaining
                if (hoursLeft <= 2 && hoursLeft > 0) {
                    setTimeout(() => {
                        showToast(
                            '🚨 Urgent: Election Ending Soon!',
                            `"${election.title}" ends in less than 2 hours. Vote now!`,
                            'warning',
                            'high'
                        );
                    }, 2000);
                }
            });
        }

        // Initialize deadline checking
        checkElectionDeadlines();

        // Periodic check for urgent deadlines (every 10 minutes)
        setInterval(checkElectionDeadlines, 600000);

        // Monitor page visibility for real-time updates
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                // Page became visible, check for updates
                setTimeout(() => {
                    fetch('check_active_elections.php')
                        .then(response => response.json())
                        .then(data => {
                            if (data.updates_available) {
                                showToast(
                                    '🔄 Updates Available',
                                    'New election information is available. Refresh to see updates.',
                                    'info'
                                );
                            }
                        })
                        .catch(error => console.log('Visibility check failed:', error));
                }, 1000);
            }
        });

        // Accessibility enhancements
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                document.body.classList.add('keyboard-navigation');
            }
            
            // Quick access to active elections with keyboard shortcut
            if (e.ctrlKey && e.key === 'v') {
                e.preventDefault();
                const activeElectionsSection = document.getElementById('active-elections');
                if (activeElectionsSection) {
                    activeElectionsSection.scrollIntoView({ behavior: 'smooth' });
                    activeElectionsSection.focus();
                }
            }
        });

        document.addEventListener('mousedown', function() {
            document.body.classList.remove('keyboard-navigation');
        });

        // Add skip link for accessibility
        const skipLink = document.createElement('a');
        skipLink.href = '#active-elections';
        skipLink.textContent = 'Skip to Active Elections';
        skipLink.style.cssText = `
            position: absolute;
            top: -40px;
            left: 6px;
            background: var(--success);
            color: white;
            padding: 8px;
            text-decoration: none;
            border-radius: 4px;
            z-index: 10001;
            transition: top 0.3s;
            font-weight: 600;
        `;
        skipLink.addEventListener('focus', () => {
            skipLink.style.top = '6px';
        });
        skipLink.addEventListener('blur', () => {
            skipLink.style.top = '-40px';
        });
        document.body.insertBefore(skipLink, document.body.firstChild);

        // Error handling for failed operations
        window.addEventListener('error', function(e) {
            console.error('Dashboard error:', e.error);
            showToast(
                'System Notice',
                'A minor issue occurred. Please refresh if problems persist.',
                'warning'
            );
        });

        // Debug information for development
        console.log('🗳️ Modern Student Dashboard Initialized - Active Elections Priority Mode');
        console.log('Voter Details:', {
            name: "<?php echo addslashes($voter['full_name']); ?>",
            studentId: "<?php echo $voter['student_id']; ?>",
            program: "<?php echo addslashes($voter['program']); ?>",
            year: "<?php echo $voter['year']; ?>",
            faculty: "<?php echo addslashes($voter['faculty']); ?>",
            status: "<?php echo $voter['status']; ?>",
            votesCast: <?php echo $voter['votes_cast']; ?>,
            eligibleElections: <?php echo $voter['eligible_elections']; ?>,
            participationRate: <?php echo $participation_rate; ?>
        });
        console.log('🔥 Elections Summary (Priority Layout):', {
            activeElections: <?php echo count($eligible_elections); ?>,
            upcomingElections: <?php echo count($upcoming_elections); ?>,
            votedElections: <?php echo count($voted_elections); ?>,
            totalActiveElections: <?php echo $total_active_elections; ?>
        });

        // Final initialization check
        console.log('✅ Dashboard fully loaded and interactive');
        console.log('🔥 Priority mode active for', <?php echo count($eligible_elections); ?>, 'elections');
        
        // Add final loading state cleanup
        setTimeout(() => {
            document.body.classList.add('fully-loaded');
            
            // Trigger any pending animations
            document.querySelectorAll('[style*="animation-play-state: paused"]').forEach(el => {
                el.style.animationPlayState = 'running';
            });
        }, 1000);
    </script>
</body>
</html>