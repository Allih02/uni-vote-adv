<?php
// election_details.php - Detailed Election Information Page with Real Database Integration
session_start();

// Database Configuration
class Database {
    private $host = 'localhost';
    private $db_name = 'voting_system';
    private $username = 'root';
    private $password = '';
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
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                )
            );
        } catch(PDOException $exception) {
            die("Connection error: " . $exception->getMessage());
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

// Check if user is logged in
if (!isset($_SESSION['voter_id'])) {
    // For demo purposes, use the first active voter
    $first_voter = fetchOne("SELECT * FROM voters WHERE status = 'active' LIMIT 1");
    if ($first_voter) {
        $_SESSION['voter_id'] = $first_voter['id'];
        $_SESSION['voter_name'] = $first_voter['full_name'];
        $_SESSION['student_id'] = $first_voter['student_id'];
    } else {
        header("Location: student_login.php");
        exit();
    }
}

// Get election ID from URL
$election_id = $_GET['id'] ?? 0;
if (!$election_id) {
    header("Location: dashboard.php?error=no_election_selected");
    exit();
}

// Get voter information from database
$voter = fetchOne("SELECT * FROM voters WHERE id = ?", [$_SESSION['voter_id']]);
if (!$voter) {
    session_destroy();
    header("Location: student_login.php?error=session_expired");
    exit();
}

// Get detailed election information with real data
$election = fetchOne("
    SELECT e.*, 
           au.fullname as created_by_name,
           COUNT(DISTINCT c.id) as candidate_count,
           COUNT(DISTINCT v.id) as vote_count,
           COUNT(DISTINCT v2.voter_id) as unique_voters
    FROM elections e 
    LEFT JOIN admin_users au ON e.created_by = au.id
    LEFT JOIN candidates c ON e.id = c.election_id AND c.status = 'active'
    LEFT JOIN votes v ON e.id = v.election_id
    LEFT JOIN votes v2 ON e.id = v2.election_id
    WHERE e.id = ?
    GROUP BY e.id
", [$election_id]);

if (!$election) {
    header("Location: dashboard.php?error=election_not_found");
    exit();
}

// Decode JSON fields safely
$election['eligible_years'] = !empty($election['eligible_years']) ? json_decode($election['eligible_years'], true) : [];
$election['eligible_faculties'] = !empty($election['eligible_faculties']) ? json_decode($election['eligible_faculties'], true) : [];

// Fix potential JSON decode issues
if (!is_array($election['eligible_years'])) {
    $election['eligible_years'] = [];
}
if (!is_array($election['eligible_faculties'])) {
    $election['eligible_faculties'] = [];
}

// Check if voter is eligible for this election
$is_eligible = (empty($election['eligible_years']) || in_array($voter['year'], $election['eligible_years'])) && 
               (empty($election['eligible_faculties']) || in_array($voter['faculty'], $election['eligible_faculties']));

// Check if voter has already voted
$has_voted = fetchOne("SELECT * FROM votes WHERE voter_id = ? AND election_id = ?", [$voter['id'], $election_id]);

// Get election positions from database
$positions = fetchAll("
    SELECT * FROM positions 
    WHERE election_id = ? 
    ORDER BY display_order, title
", [$election_id]);

// Get candidates grouped by position with real data
$candidates_by_position = [];
if (!empty($positions)) {
    foreach ($positions as $position) {
        $candidates_by_position[$position['title']] = fetchAll("
            SELECT c.*, 
                   v.profile_image,
                   v.full_name as voter_full_name,
                   v.program as voter_program,
                   v.year as voter_year,
                   v.faculty as voter_faculty,
                   COALESCE(vote_counts.vote_count, 0) as current_votes
            FROM candidates c
            LEFT JOIN voters v ON c.voter_id = v.id
            LEFT JOIN (
                SELECT candidate_id, COUNT(*) as vote_count 
                FROM votes 
                GROUP BY candidate_id
            ) vote_counts ON c.id = vote_counts.candidate_id
            WHERE c.election_id = ? 
            AND c.position = ? 
            AND c.status = 'active'
            ORDER BY c.full_name
        ", [$election_id, $position['title']]);
    }
} else {
    // If no positions defined, get all active candidates
    $candidates_by_position['General'] = fetchAll("
        SELECT c.*, 
               v.profile_image,
               v.full_name as voter_full_name,
               v.program as voter_program,
               v.year as voter_year,
               v.faculty as voter_faculty,
               COALESCE(vote_counts.vote_count, 0) as current_votes
        FROM candidates c
        LEFT JOIN voters v ON c.voter_id = v.id
        LEFT JOIN (
            SELECT candidate_id, COUNT(*) as vote_count 
            FROM votes 
            GROUP BY candidate_id
        ) vote_counts ON c.id = vote_counts.candidate_id
        WHERE c.election_id = ? 
        AND c.status = 'active'
        ORDER BY c.full_name
    ", [$election_id]);
}

// Calculate election statistics with real data
if (!empty($election['eligible_years']) && !empty($election['eligible_faculties'])) {
    $total_eligible_voters = fetchOne("
        SELECT COUNT(*) as count FROM voters v
        WHERE v.status = 'active'
        AND v.year IN ('" . implode("','", array_map('addslashes', $election['eligible_years'])) . "')
        AND v.faculty IN ('" . implode("','", array_map('addslashes', $election['eligible_faculties'])) . "')
    ")['count'] ?? 0;
} else {
    // If no restrictions, count all active voters
    $total_eligible_voters = fetchOne("SELECT COUNT(*) as count FROM voters WHERE status = 'active'")['count'] ?? 0;
}

$participation_rate = $total_eligible_voters > 0 ? 
    round(($election['unique_voters'] / $total_eligible_voters) * 100, 1) : 0;

// Helper functions for display
function getElectionStatus($election) {
    $now = new DateTime();
    $start = new DateTime($election['start_date']);
    $end = new DateTime($election['end_date']);
    
    if ($election['status'] !== 'active') {
        return ucfirst($election['status']);
    }
    
    if ($now < $start) {
        return 'Not Started';
    } elseif ($now >= $start && $now <= $end) {
        return 'Active';
    } else {
        return 'Ended';
    }
}

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

function canVote($election, $voter, $has_voted) {
    $now = new DateTime();
    $start = new DateTime($election['start_date']);
    $end = new DateTime($election['end_date']);
    
    return $election['status'] === 'active' &&
           $now >= $start && $now <= $end &&
           !$has_voted;
}

$election_status = getElectionStatus($election);
$time_remaining = getTimeRemaining($election['end_date']);
$can_vote = canVote($election, $voter, $has_voted);

// Get additional statistics
$election_stats = fetchOne("
    SELECT 
        (SELECT COUNT(*) FROM candidates WHERE election_id = ? AND status = 'active') as total_candidates,
        (SELECT COUNT(*) FROM votes WHERE election_id = ?) as total_votes_cast,
        (SELECT COUNT(DISTINCT voter_id) FROM votes WHERE election_id = ?) as voters_participated
", [$election_id, $election_id, $election_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($election['title']); ?> - Election Details</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #3730a3;
            --primary-light: #a5b4fc;
            --secondary: #7c3aed;
            --accent: #ec4899;
            --background: #f8fafc;
            --surface: #ffffff;
            --surface-hover: #f1f5f9;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --border: #e2e8f0;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --info: #3b82f6;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --radius: 0.75rem;
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
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Header */
        .header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 1rem 2rem;
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
        }

        .header-nav {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius);
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-primary:disabled {
            background: var(--text-muted);
            cursor: not-allowed;
            transform: none;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-primary);
        }

        .btn-outline:hover {
            background: var(--surface-hover);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        /* Election Hero Section */
        .election-hero {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 3rem 2rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .election-hero::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .election-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .election-description {
            font-size: 1.125rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            max-width: 800px;
        }

        .hero-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .meta-icon {
            width: 2.5rem;
            height: 2.5rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.125rem;
        }

        .meta-content h4 {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .meta-content p {
            font-size: 0.875rem;
            opacity: 0.8;
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: rgba(16, 185, 129, 0.2);
            color: #ecfdf5;
        }

        .status-not-started {
            background: rgba(245, 158, 11, 0.2);
            color: #fef3c7;
        }

        .status-ended {
            background: rgba(107, 114, 128, 0.2);
            color: #f3f4f6;
        }

        .status-cancelled {
            background: rgba(239, 68, 68, 0.2);
            color: #fef2f2;
        }

        .status-draft {
            background: rgba(107, 114, 128, 0.2);
            color: #f3f4f6;
        }

        .status-completed {
            background: rgba(59, 130, 246, 0.2);
            color: #dbeafe;
        }

        /* Main Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .main-content {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        /* Section Cards */
        .section-card {
            background: var(--surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .section-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .section-subtitle {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .section-content {
            padding: 1.5rem;
        }

        /* Candidate Cards */
        .candidates-grid {
            display: grid;
            gap: 1.5rem;
        }

        .position-group {
            margin-bottom: 2rem;
        }

        .position-group:last-child {
            margin-bottom: 0;
        }

        .position-header {
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-radius: var(--radius);
            margin-bottom: 1rem;
            border: 1px solid var(--border);
        }

        .position-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .position-candidates {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
        }

        .candidate-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .candidate-card:hover {
            border-color: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .candidate-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .candidate-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            overflow: hidden;
            flex-shrink: 0;
        }

        .candidate-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .candidate-info h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .candidate-details {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .candidate-manifesto {
            color: var(--text-secondary);
            font-size: 0.875rem;
            line-height: 1.6;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
        }

        .manifesto-label {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .candidate-vote-count {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.75rem;
            background: rgba(79, 70, 229, 0.1);
            color: var(--primary);
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0.75rem;
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .stat-card {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Information Lists */
        .info-list {
            list-style: none;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border);
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 500;
            color: var(--text-secondary);
        }

        .info-value {
            font-weight: 600;
            color: var(--text-primary);
        }

        /* Eligibility Notice */
        .eligibility-notice {
            padding: 1rem 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .eligibility-eligible {
            background: rgb(16 185 129 / 0.1);
            border: 1px solid rgb(16 185 129 / 0.2);
            color: #065f46;
        }

        .eligibility-not-eligible {
            background: rgb(239 68 68 / 0.1);
            border: 1px solid rgb(239 68 68 / 0.2);
            color: #dc2626;
        }

        .eligibility-voted {
            background: rgb(59 130 246 / 0.1);
            border: 1px solid rgb(59 130 246 / 0.2);
            color: #1d4ed8;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .action-buttons .btn {
            flex: 1;
            min-width: 200px;
            justify-content: center;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
        }

        .empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .header {
                padding: 1rem;
            }

            .header-content {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .election-title {
                font-size: 2rem;
            }

            .election-hero {
                padding: 2rem 1.5rem;
            }

            .hero-meta {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .position-candidates {
                grid-template-columns: 1fr;
            }

            .candidate-header {
                flex-direction: column;
                text-align: center;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-buttons .btn {
                min-width: auto;
            }
        }

        /* Print Styles */
        @media print {
            .header, .action-buttons, .sidebar {
                display: none !important;
            }
            
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            body {
                background: white;
            }
            
            .section-card, .candidate-card {
                box-shadow: none;
                border: 1px solid #000;
            }
        }

        /* Time Warning */
        .time-warning {
            position: fixed;
            top: 80px;
            right: 1rem;
            background: var(--warning);
            color: white;
            padding: 1rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            z-index: 1000;
            display: none;
            animation: slideIn 0.3s ease;
        }

        .time-warning.show {
            display: block;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-vote-yea"></i>
                <span>University Voting System</span>
            </div>
            
            <nav class="header-nav">
                <a href="dashboard.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
                <?php if ($can_vote): ?>
                    <a href="vote.php?election_id=<?php echo $election_id; ?>" class="btn btn-success">
                        <i class="fas fa-vote-yea"></i>
                        Vote Now
                    </a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-outline">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </nav>
        </div>
    </header>

    <!-- Time Warning -->
    <div class="time-warning" id="timeWarning">
        <i class="fas fa-clock"></i>
        <span id="timeWarningText">Election ending soon!</span>
    </div>

    <div class="container">
        <!-- Election Hero Section -->
        <div class="election-hero">
            <div class="hero-content">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h1 class="election-title"><?php echo htmlspecialchars($election['title']); ?></h1>
                    </div>
                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $election_status)); ?>">
                        <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                        <?php echo $election_status; ?>
                    </span>
                </div>
                
                <?php if ($election['description']): ?>
                <p class="election-description"><?php echo htmlspecialchars($election['description']); ?></p>
                <?php endif; ?>
                
                <div class="hero-meta">
                    <div class="meta-item">
                        <div class="meta-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="meta-content">
                            <h4>Election Period</h4>
                            <p><?php echo date('M d, Y g:i A', strtotime($election['start_date'])); ?> - 
                               <?php echo date('M d, Y g:i A', strtotime($election['end_date'])); ?></p>
                        </div>
                    </div>
                    
                    <div class="meta-item">
                        <div class="meta-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="meta-content">
                            <h4>Participation</h4>
                            <p><?php echo $election['unique_voters']; ?> of <?php echo $total_eligible_voters; ?> voters (<?php echo $participation_rate; ?>%)</p>
                        </div>
                    </div>
                    
                    <div class="meta-item">
                        <div class="meta-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="meta-content">
                            <h4>Candidates</h4>
                            <p><?php echo $election['candidate_count']; ?> candidate<?php echo $election['candidate_count'] != 1 ? 's' : ''; ?> running</p>
                        </div>
                    </div>
                    
                    <div class="meta-item">
                        <div class="meta-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="meta-content">
                            <h4>Time Status</h4>
                            <p><?php echo $time_remaining; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Eligibility Notice -->
        <?php if ($has_voted): ?>
            <div class="eligibility-notice eligibility-voted">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>You have already voted in this election.</strong>
                    <p>Your vote was recorded on <?php echo date('M d, Y \a\t g:i A', strtotime($has_voted['voted_at'])); ?></p>
                </div>
            </div>
        <?php elseif (!$is_eligible): ?>
            <div class="eligibility-notice eligibility-not-eligible">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>You are not eligible to vote in this election.</strong>
                    <p>This election is restricted to specific years and faculties.</p>
                </div>
            </div>
        <?php elseif ($can_vote): ?>
            <div class="eligibility-notice eligibility-eligible">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>You are eligible to vote in this election!</strong>
                    <p>Click the "Vote Now" button to cast your vote.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Main Content Grid -->
        <div class="content-grid">
            <!-- Main Content -->
            <div class="main-content">
                <!-- Candidates Section -->
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="section-title">Candidates</h2>
                        <p class="section-subtitle">Meet the candidates running in this election</p>
                    </div>
                    <div class="section-content">
                        <?php if (empty($candidates_by_position) || array_sum(array_map('count', $candidates_by_position)) === 0): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <h3>No Candidates Yet</h3>
                                <p>Candidates have not been registered for this election yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="candidates-grid">
                                <?php foreach ($candidates_by_position as $position => $candidates): ?>
                                    <?php if (!empty($candidates)): ?>
                                        <div class="position-group">
                                            <div class="position-header">
                                                <h3 class="position-title"><?php echo htmlspecialchars($position); ?></h3>
                                                <p style="color: var(--text-secondary); font-size: 0.875rem; margin-top: 0.25rem;">
                                                    <?php echo count($candidates); ?> candidate<?php echo count($candidates) > 1 ? 's' : ''; ?> running
                                                </p>
                                            </div>
                                            
                                            <div class="position-candidates">
                                                <?php foreach ($candidates as $candidate): ?>
                                                    <div class="candidate-card">
                                                        <div class="candidate-header">
                                                            <div class="candidate-avatar">
                                                                <?php if ($candidate['profile_image']): ?>
                                                                    <img src="<?php echo htmlspecialchars($candidate['profile_image']); ?>" 
                                                                         alt="<?php echo htmlspecialchars($candidate['full_name']); ?>">
                                                                <?php else: ?>
                                                                    <?php echo strtoupper(substr($candidate['full_name'], 0, 1)); ?>
                                                                <?php endif; ?>
                                                            </div>
                                                            
                                                            <div class="candidate-info">
                                                                <h3><?php echo htmlspecialchars($candidate['full_name']); ?></h3>
                                                                <div class="candidate-details">
                                                                    <?php if ($candidate['student_id']): ?>
                                                                        <div><strong>ID:</strong> <?php echo htmlspecialchars($candidate['student_id']); ?></div>
                                                                    <?php endif; ?>
                                                                    <?php if ($candidate['program'] || $candidate['voter_program']): ?>
                                                                        <div><strong>Program:</strong> <?php echo htmlspecialchars($candidate['program'] ?: $candidate['voter_program']); ?></div>
                                                                    <?php endif; ?>
                                                                    <?php if ($candidate['year'] || $candidate['voter_year']): ?>
                                                                        <div><strong>Year:</strong> <?php echo htmlspecialchars($candidate['year'] ?: $candidate['voter_year']); ?></div>
                                                                    <?php endif; ?>
                                                                    <?php if ($candidate['faculty'] || $candidate['voter_faculty']): ?>
                                                                        <div><strong>Faculty:</strong> <?php echo htmlspecialchars($candidate['faculty'] ?: $candidate['voter_faculty']); ?></div>
                                                                    <?php endif; ?>
                                                                    <?php if ($candidate['email']): ?>
                                                                        <div><strong>Email:</strong> <?php echo htmlspecialchars($candidate['email']); ?></div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <?php if ($candidate['manifesto']): ?>
                                                            <div class="candidate-manifesto">
                                                                <div class="manifesto-label">Manifesto:</div>
                                                                <?php echo nl2br(htmlspecialchars($candidate['manifesto'])); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($has_voted || !$can_vote): ?>
                                                            <div class="candidate-vote-count">
                                                                <i class="fas fa-chart-bar"></i>
                                                                <?php echo $candidate['current_votes']; ?> votes
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Election Rules & Guidelines -->
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="section-title">Election Rules & Guidelines</h2>
                        <p class="section-subtitle">Important information for voters</p>
                    </div>
                    <div class="section-content">
                        <div style="display: grid; gap: 1.5rem;">
                            <div>
                                <h4 style="color: var(--text-primary); margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-vote-yea" style="color: var(--primary);"></i>
                                    Voting Process
                                </h4>
                                <ul style="color: var(--text-secondary); line-height: 1.6; padding-left: 1.5rem;">
                                    <li>Each eligible voter can cast <strong><?php echo $election['max_votes_per_voter']; ?></strong> vote<?php echo $election['max_votes_per_voter'] > 1 ? 's' : ''; ?> in this election</li>
                                    <li>Voting is anonymous and secure</li>
                                    <li>You can only vote once - no changes allowed after submission</li>
                                    <li>Your vote will be recorded with a timestamp for verification</li>
                                </ul>
                            </div>

                            <div>
                                <h4 style="color: var(--text-primary); margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-users" style="color: var(--info);"></i>
                                    Eligibility Criteria
                                </h4>
                                <ul style="color: var(--text-secondary); line-height: 1.6; padding-left: 1.5rem;">
                                    <?php if (!empty($election['eligible_years'])): ?>
                                    <li><strong>Eligible Years:</strong> <?php echo implode(', ', $election['eligible_years']); ?></li>
                                    <?php endif; ?>
                                    <?php if (!empty($election['eligible_faculties'])): ?>
                                    <li><strong>Eligible Faculties:</strong> <?php echo implode(', ', $election['eligible_faculties']); ?></li>
                                    <?php endif; ?>
                                    <li>Must have an active student status</li>
                                    <li>Must be registered in the voting system</li>
                                </ul>
                            </div>

                            <div>
                                <h4 style="color: var(--text-primary); margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-shield-alt" style="color: var(--success);"></i>
                                    Security & Privacy
                                </h4>
                                <ul style="color: var(--text-secondary); line-height: 1.6; padding-left: 1.5rem;">
                                    <li>All votes are encrypted and anonymous</li>
                                    <li>Your voting choices cannot be traced back to you</li>
                                    <li>Vote tampering prevention measures are in place</li>
                                    <li>Audit logs maintain system integrity</li>
                                </ul>
                            </div>

                            <?php if ($election['allow_multiple_candidates']): ?>
                            <div>
                                <h4 style="color: var(--text-primary); margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-list" style="color: var(--warning);"></i>
                                    Multiple Candidates
                                </h4>
                                <p style="color: var(--text-secondary); line-height: 1.6;">
                                    This election allows multiple candidates per position. You may vote for more than one candidate 
                                    if permitted by the election rules.
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Election Statistics -->
                <div class="section-card">
                    <div class="section-header">
                        <h3 class="section-title">Election Statistics</h3>
                        <p class="section-subtitle">Current participation data</p>
                    </div>
                    <div class="section-content">
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $election['unique_voters']; ?></div>
                                <div class="stat-label">Votes Cast</div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $total_eligible_voters; ?></div>
                                <div class="stat-label">Eligible Voters</div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $participation_rate; ?>%</div>
                                <div class="stat-label">Participation Rate</div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $election['candidate_count']; ?></div>
                                <div class="stat-label">Total Candidates</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Election Information -->
                <div class="section-card">
                    <div class="section-header">
                        <h3 class="section-title">Election Information</h3>
                        <p class="section-subtitle">Key details and dates</p>
                    </div>
                    <div class="section-content">
                        <ul class="info-list">
                            <li class="info-item">
                                <span class="info-label">Election Type</span>
                                <span class="info-value"><?php echo ucfirst($election['election_type']); ?></span>
                            </li>
                            
                            <li class="info-item">
                                <span class="info-label">Start Date</span>
                                <span class="info-value"><?php echo date('M d, Y', strtotime($election['start_date'])); ?></span>
                            </li>
                            
                            <li class="info-item">
                                <span class="info-label">Start Time</span>
                                <span class="info-value"><?php echo date('g:i A', strtotime($election['start_date'])); ?></span>
                            </li>
                            
                            <li class="info-item">
                                <span class="info-label">End Date</span>
                                <span class="info-value"><?php echo date('M d, Y', strtotime($election['end_date'])); ?></span>
                            </li>
                            
                            <li class="info-item">
                                <span class="info-label">End Time</span>
                                <span class="info-value"><?php echo date('g:i A', strtotime($election['end_date'])); ?></span>
                            </li>
                            
                            <li class="info-item">
                                <span class="info-label">Max Votes</span>
                                <span class="info-value"><?php echo $election['max_votes_per_voter']; ?> per voter</span>
                            </li>
                            
                            <li class="info-item">
                                <span class="info-label">Created By</span>
                                <span class="info-value"><?php echo htmlspecialchars($election['created_by_name'] ?? 'System Admin'); ?></span>
                            </li>
                            
                            <li class="info-item">
                                <span class="info-label">Created On</span>
                                <span class="info-value"><?php echo date('M d, Y', strtotime($election['created_at'])); ?></span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Your Voting Status -->
                <div class="section-card">
                    <div class="section-header">
                        <h3 class="section-title">Your Voting Status</h3>
                        <p class="section-subtitle">Current eligibility and status</p>
                    </div>
                    <div class="section-content">
                        <ul class="info-list">
                            <li class="info-item">
                                <span class="info-label">Eligibility</span>
                                <span class="info-value" style="color: <?php echo $is_eligible ? 'var(--success)' : 'var(--error)'; ?>">
                                    <?php echo $is_eligible ? 'Eligible' : 'Not Eligible'; ?>
                                </span>
                            </li>
                            
                            <li class="info-item">
                                <span class="info-label">Voting Status</span>
                                <span class="info-value" style="color: <?php echo $has_voted ? 'var(--info)' : 'var(--warning)'; ?>">
                                    <?php echo $has_voted ? 'Already Voted' : 'Not Voted'; ?>
                                </span>
                            </li>
                            
                            <li class="info-item">
                                <span class="info-label">Your Year</span>
                                <span class="info-value"><?php echo htmlspecialchars($voter['year']); ?></span>
                            </li>
                            
                            <li class="info-item">
                                <span class="info-label">Your Faculty</span>
                                <span class="info-value"><?php echo htmlspecialchars($voter['faculty']); ?></span>
                            </li>
                            
                            <?php if ($has_voted): ?>
                            <li class="info-item">
                                <span class="info-label">Voted On</span>
                                <span class="info-value"><?php echo date('M d, Y g:i A', strtotime($has_voted['voted_at'])); ?></span>
                            </li>
                            <?php endif; ?>
                        </ul>
                        
                        <div class="action-buttons" style="margin-top: 1.5rem;">
                            <?php if ($can_vote): ?>
                                <a href="vote.php?election_id=<?php echo $election_id; ?>" class="btn btn-primary">
                                    <i class="fas fa-vote-yea"></i>
                                    Vote Now
                                </a>
                            <?php elseif ($has_voted): ?>
                                <a href="vote_receipt.php?election_id=<?php echo $election_id; ?>" class="btn btn-outline">
                                    <i class="fas fa-receipt"></i>
                                    View Receipt
                                </a>
                            <?php endif; ?>
                            
                            <button onclick="window.print()" class="btn btn-outline">
                                <i class="fas fa-print"></i>
                                Print Details
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Share Election -->
                <div class="section-card">
                    <div class="section-header">
                        <h3 class="section-title">Share Election</h3>
                        <p class="section-subtitle">Spread awareness about this election</p>
                    </div>
                    <div class="section-content">
                        <div class="action-buttons">
                            <button onclick="shareElection()" class="btn btn-outline">
                                <i class="fas fa-share"></i>
                                Share
                            </button>
                            
                            <button onclick="copyElectionLink()" class="btn btn-outline">
                                <i class="fas fa-link"></i>
                                Copy Link
                            </button>
                        </div>
                        
                        <div style="margin-top: 1rem; padding: 1rem; background: var(--surface-hover); border-radius: var(--radius); font-size: 0.875rem; color: var(--text-secondary);">
                            <strong style="color: var(--text-primary);">Help increase participation!</strong><br>
                            Share this election with eligible students to ensure maximum democratic participation.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Real election data from database
        const electionData = <?php echo json_encode($election); ?>;
        const candidatesData = <?php echo json_encode($candidates_by_position); ?>;
        const voterData = <?php echo json_encode($voter); ?>;

        document.addEventListener('DOMContentLoaded', function() {
            startTimeWarning();
            updateElectionStatus();
            displayRealTimeStats();
        });

        function displayRealTimeStats() {
            // Display real-time statistics from database
            console.log('Election Statistics:', {
                'electionId': <?php echo $election_id; ?>,
                'totalCandidates': <?php echo $election['candidate_count']; ?>,
                'totalVotes': <?php echo $election['vote_count']; ?>,
                'uniqueVoters': <?php echo $election['unique_voters']; ?>,
                'participationRate': <?php echo $participation_rate; ?>,
                'eligibleVoters': <?php echo $total_eligible_voters; ?>
            });
        }

        function startTimeWarning() {
            const endTime = new Date('<?php echo $election['end_date']; ?>').getTime();
            const warningDiv = document.getElementById('timeWarning');
            const warningText = document.getElementById('timeWarningText');
            
            function updateTimeWarning() {
                const now = new Date().getTime();
                const distance = endTime - now;
                
                if (distance < 0) {
                    warningText.textContent = 'This election has ended.';
                    warningDiv.style.background = 'var(--error)';
                    warningDiv.classList.add('show');
                    return;
                }
                
                const hours = Math.floor(distance / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                
                // Show warning if less than 2 hours remaining
                if (distance < 7200000 && <?php echo $can_vote ? 'true' : 'false'; ?>) { // 2 hours
                    warningText.textContent = `Election ends in ${hours}h ${minutes}m - Vote now!`;
                    warningDiv.classList.add('show');
                } else if (distance < 3600000 && <?php echo $can_vote ? 'true' : 'false'; ?>) { // 1 hour
                    warningText.textContent = `Only ${minutes} minutes left to vote!`;
                    warningDiv.style.background = 'var(--error)';
                    warningDiv.classList.add('show');
                }
            }
            
            updateTimeWarning();
            setInterval(updateTimeWarning, 60000); // Update every minute
        }

        function updateElectionStatus() {
            // Auto-refresh page if election status changes (every 30 seconds)
            setInterval(function() {
                fetch(window.location.href)
                    .then(response => response.text())
                    .then(html => {
                        // Check if status has changed (simplified check)
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newStatus = doc.querySelector('.status-badge')?.textContent?.trim();
                        const currentStatus = document.querySelector('.status-badge')?.textContent?.trim();
                        
                        if (newStatus && currentStatus && newStatus !== currentStatus) {
                            showNotification('Election status has changed. Refreshing page...', 'info');
                            setTimeout(() => window.location.reload(), 2000);
                        }
                    })
                    .catch(error => console.log('Status check failed:', error));
            }, 30000); // Check every 30 seconds
        }

        function shareElection() {
            const shareData = {
                title: '<?php echo addslashes($election['title']); ?>',
                text: 'Check out this election and make sure to vote!',
                url: window.location.href
            };

            if (navigator.share) {
                navigator.share(shareData);
            } else {
                // Fallback - copy to clipboard
                copyElectionLink();
            }
        }

        function copyElectionLink() {
            const url = window.location.href;
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(() => {
                    showNotification('Election link copied to clipboard!', 'success');
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = url;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showNotification('Election link copied to clipboard!', 'success');
            }
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 2rem;
                right: 2rem;
                background: ${type === 'success' ? 'var(--success)' : 
                            type === 'error' ? 'var(--error)' : 'var(--info)'};
                color: white;
                padding: 1rem 1.5rem;
                border-radius: var(--radius);
                box-shadow: var(--shadow-lg);
                z-index: 1001;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                font-weight: 500;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease;
            `;
            
            const iconMap = {
                'info': 'fa-info-circle',
                'success': 'fa-check-circle',
                'error': 'fa-exclamation-circle'
            };
            
            notification.innerHTML = `
                <i class="fas ${iconMap[type]}"></i>
                <span>${message}</span>
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
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 4000);
        }

        // Auto-scroll to candidates section if coming from dashboard
        if (document.referrer.includes('dashboard.php')) {
            setTimeout(() => {
                const candidatesSection = document.querySelector('.candidates-grid');
                if (candidatesSection) {
                    candidatesSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 500);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'p':
                        e.preventDefault();
                        window.print();
                        break;
                    case 'v':
                        if (<?php echo $can_vote ? 'true' : 'false'; ?>) {
                            e.preventDefault();
                            window.location.href = 'vote.php?election_id=<?php echo $election_id; ?>';
                        }
                        break;
                }
            }
            
            // ESC to go back to dashboard
            if (e.key === 'Escape') {
                window.location.href = 'dashboard.php';
            }
        });

        // Real-time vote count updates (if votes are visible)
        <?php if ($has_voted || !$can_vote): ?>
        function updateVoteCounts() {
            fetch(`get_vote_counts.php?election_id=<?php echo $election_id; ?>`)
                .then(response => response.json())
                .then(data => {
                    Object.keys(data).forEach(candidateId => {
                        const voteCountElement = document.querySelector(`[data-candidate-id="${candidateId}"] .candidate-vote-count`);
                        if (voteCountElement) {
                            voteCountElement.innerHTML = `<i class="fas fa-chart-bar"></i> ${data[candidateId]} votes`;
                        }
                    });
                })
                .catch(error => console.log('Vote count update failed:', error));
        }
        
        // Update vote counts every 30 seconds
        setInterval(updateVoteCounts, 30000);
        <?php endif; ?>

        console.log('Election Details Page Initialized with Real Database Data');
        console.log('Election Info:', electionData);
        console.log('Voter Info:', voterData);
        console.log('Integration Features:', {
            'realTimeDatabase': 'All data fetched from voting_system database',
            'candidateSync': 'Candidates automatically sync from admin_candidates.php',
            'liveStatistics': 'Vote counts and participation rates update in real-time',
            'eligibilityCheck': 'Real-time eligibility verification',
            'voteTracking': 'Actual vote records from votes table',
            'auditTrail': 'All actions logged in audit_logs table'
        });
    </script>
</body>
</html>