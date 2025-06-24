<?php
// admin_results.php - Election Results & Analytics Dashboard
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'voting_system';  // Correct database name
$username = 'root';
$password = '';

// Initialize variables
$pdo = null;
$selected_election_id = null;
$selected_election = null;
$elections = [];
$results = [];
$voting_stats = [
    'total_votes' => 0,
    'eligible_voters' => 0,
    'turnout_percentage' => 0,
    'total_candidates' => 0
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

// Only proceed if database connection is successful
if ($pdo) {
    // Get selected election ID from GET parameter
    $selected_election_id = isset($_GET['election_id']) ? (int)$_GET['election_id'] : null;

    // Get all elections for dropdown
    try {
        $stmt = $pdo->query("SELECT id, title, status, start_date, end_date FROM elections ORDER BY created_at DESC");
        $elections = $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error fetching elections: " . $e->getMessage());
        $elections = [];
    }

    // If no election is selected but elections exist, select the first one
    if (!$selected_election_id && !empty($elections)) {
        $selected_election_id = $elections[0]['id'];
        header("Location: " . $_SERVER['PHP_SELF'] . "?election_id=" . $selected_election_id);
        exit;
    }

    // Find the selected election details
    if ($selected_election_id && !empty($elections)) {
        foreach ($elections as $election) {
            if ($election['id'] == $selected_election_id) {
                $selected_election = $election;
                break;
            }
        }
    }

    // If we have a selected election, fetch its results
    if ($selected_election) {
        try {
            // Get election statistics
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(DISTINCT v.voter_id) as total_votes,
                    COUNT(DISTINCT c.id) as total_candidates,
                    (SELECT COUNT(*) FROM voters WHERE status = 'active') as eligible_voters
                FROM votes v
                JOIN candidates c ON v.candidate_id = c.id
                WHERE c.election_id = ?
            ");
            $stmt->execute([$selected_election_id]);
            $stats = $stmt->fetch();
            
            if ($stats && $stats['total_votes'] !== null) {
                $voting_stats['total_votes'] = (int)$stats['total_votes'];
                $voting_stats['eligible_voters'] = (int)$stats['eligible_voters'];
                $voting_stats['total_candidates'] = (int)$stats['total_candidates'];
                
                if ($voting_stats['eligible_voters'] > 0) {
                    $voting_stats['turnout_percentage'] = round(
                        ($voting_stats['total_votes'] / $voting_stats['eligible_voters']) * 100, 1
                    );
                }
            }
            
            // Get candidate results grouped by position
            $stmt = $pdo->prepare("
                SELECT 
                    c.id,
                    c.full_name,
                    c.position,
                    c.profile_image,
                    COUNT(v.id) as vote_count,
                    ROUND((COUNT(v.id) * 100.0 / NULLIF((
                        SELECT COUNT(*) 
                        FROM votes v2 
                        JOIN candidates c2 ON v2.candidate_id = c2.id 
                        WHERE c2.election_id = ? AND c2.position = c.position
                    ), 0)), 2) as vote_percentage
                FROM candidates c
                LEFT JOIN votes v ON c.id = v.candidate_id
                WHERE c.election_id = ? AND c.status = 'active'
                GROUP BY c.id, c.position
                ORDER BY c.position, vote_count DESC
            ");
            $stmt->execute([$selected_election_id, $selected_election_id]);
            $candidate_results = $stmt->fetchAll();
            
            // Group results by position
            $results = [];
            foreach ($candidate_results as $candidate) {
                $position = $candidate['position'];
                if (!isset($results[$position])) {
                    $results[$position] = [];
                }
                $results[$position][] = $candidate;
            }
            
        } catch(PDOException $e) {
            error_log("Error fetching election results: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results & Analytics - Admin Dashboard</title>
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

        /* Modern Sidebar Styles - From first document */
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

        /* Admin Dashboard Specific Styles */
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

        /* Error Alert */
        .error-alert {
            background: #ef4444;
            color: white;
            padding: 1rem 2rem;
            margin: 1rem 2rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .error-alert i {
            font-size: 1.25rem;
        }

        /* Election Selector */
        .election-selector {
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

        .selector-label {
            font-weight: 600;
            color: #1e293b;
        }

        .election-select {
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            background: white;
            color: #1e293b;
            font-size: 1rem;
            min-width: 300px;
        }

        .election-status {
            padding: 0.375rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active { background: #dcfce7; color: #166534; }
        .status-completed { background: #f3e8ff; color: #6b21a8; }
        .status-draft { background: #fef3c7; color: #92400e; }

        /* Overview Cards */
        .overview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .overview-card {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .overview-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: #6366f1;
        }

        .overview-card.success::before { background: #10b981; }
        .overview-card.warning::before { background: #f59e0b; }
        .overview-card.info::before { background: #3b82f6; }

        .overview-card:hover {
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            transform: translateY(-2px);
        }

        .overview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .overview-icon {
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

        .overview-card.success .overview-icon { background: #10b981; }
        .overview-card.warning .overview-icon { background: #f59e0b; }
        .overview-card.info .overview-icon { background: #3b82f6; }

        .overview-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .overview-label {
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Results Grid */
        .results-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .results-card {
            background: white;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            overflow: hidden;
        }

        .results-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .results-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
        }

        .results-body {
            padding: 2rem;
        }

        /* Candidate Results */
        .position-section {
            margin-bottom: 2rem;
        }

        .position-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .candidate-result {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .candidate-result:last-child {
            border-bottom: none;
        }

        .candidate-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid #e2e8f0;
            flex-shrink: 0;
        }

        .candidate-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .candidate-info {
            flex: 1;
            min-width: 0;
        }

        .candidate-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: #1e293b;
        }

        .candidate-position {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 0.5rem;
        }

        .vote-bar {
            background: #e2e8f0;
            height: 6px;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 0.25rem;
        }

        .vote-progress {
            height: 100%;
            background: linear-gradient(90deg, #6366f1, #8b5cf6);
            border-radius: 3px;
            transition: width 0.5s ease;
        }

        .vote-stats {
            display: flex;
            justify-content: space-between;
            font-size: 0.875rem;
        }

        .vote-count {
            font-weight: 600;
            color: #1e293b;
        }

        .vote-percentage {
            color: #6366f1;
            font-weight: 600;
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

        /* Live Updates */
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

        .no-data {
            text-align: center;
            padding: 3rem;
            color: #64748b;
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #94a3b8;
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
            .results-grid {
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

            .election-selector {
                flex-direction: column;
                align-items: stretch;
            }

            .election-select {
                min-width: auto;
            }

            .overview-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .overview-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Display connection error if exists -->
        <?php if (isset($connection_error)): ?>
            <div class="error-alert">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Database Connection Error:</strong><br>
                    <?php echo htmlspecialchars($connection_error); ?><br>
                    <small>Please check your database configuration and ensure the 'voting_system' database exists.</small>
                </div>
            </div>
        <?php endif; ?>

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
                            <a href="admin_results.php" class="nav-link active">
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
                <h1>Election Results & Analytics</h1>
                <div class="top-bar-actions">
                    <div class="live-indicator">
                        <div class="live-dot"></div>
                        Live
                    </div>
                    <button class="btn btn-secondary" onclick="exportResults()">
                        <i class="fas fa-download"></i>
                        Export Report
                    </button>
                    <button class="btn btn-primary" onclick="refreshPage()">
                        <i class="fas fa-sync-alt"></i>
                        Refresh
                    </button>
                </div>
            </div>

            <div class="page-content">
                <?php if ($pdo): ?>
                    <!-- Election Selector -->
                    <div class="election-selector">
                        <label for="election-select" class="selector-label">Select Election:</label>
                        <select id="election-select" class="election-select" onchange="changeElection(this.value)">
                            <option value="">Choose an election...</option>
                            <?php foreach ($elections as $election): ?>
                                <option value="<?php echo htmlspecialchars($election['id']); ?>" 
                                        <?php echo ($selected_election_id == $election['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($election['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <?php if ($selected_election): ?>
                            <span class="election-status status-<?php echo htmlspecialchars($selected_election['status']); ?>">
                                <?php echo htmlspecialchars($selected_election['status']); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($selected_election): ?>
                        <!-- Overview Cards -->
                        <div class="overview-grid">
                            <div class="overview-card">
                                <div class="overview-header">
                                    <div class="overview-icon">
                                        <i class="fas fa-vote-yea"></i>
                                    </div>
                                </div>
                                <div class="overview-value"><?php echo number_format($voting_stats['total_votes']); ?></div>
                                <div class="overview-label">Total Votes Cast</div>
                            </div>

                            <div class="overview-card info">
                                <div class="overview-header">
                                    <div class="overview-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                                <div class="overview-value"><?php echo number_format($voting_stats['eligible_voters']); ?></div>
                                <div class="overview-label">Eligible Voters</div>
                            </div>

                            <div class="overview-card success">
                                <div class="overview-header">
                                    <div class="overview-icon">
                                        <i class="fas fa-percentage"></i>
                                    </div>
                                </div>
                                <div class="overview-value"><?php echo $voting_stats['turnout_percentage']; ?>%</div>
                                <div class="overview-label">Voter Turnout</div>
                            </div>

                            <div class="overview-card warning">
                                <div class="overview-header">
                                    <div class="overview-icon">
                                        <i class="fas fa-user-tie"></i>
                                    </div>
                                </div>
                                <div class="overview-value"><?php echo number_format($voting_stats['total_candidates']); ?></div>
                                <div class="overview-label">Total Candidates</div>
                            </div>
                        </div>

                        <!-- Results Display -->
                        <?php if (!empty($results)): ?>
                            <div class="results-grid">
                                <div class="results-card">
                                    <div class="results-header">
                                        <h2 class="results-title">Election Results</h2>
                                    </div>
                                    <div class="results-body">
                                        <?php foreach ($results as $position => $candidates): ?>
                                            <div class="position-section">
                                                <h3 class="position-title"><?php echo htmlspecialchars($position); ?></h3>
                                                
                                                <?php foreach ($candidates as $candidate): ?>
                                                    <div class="candidate-result">
                                                        <div class="candidate-avatar">
                                                            <img src="<?php echo htmlspecialchars($candidate['profile_image'] ?: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100&fit=crop&crop=face'); ?>" 
                                                                 alt="<?php echo htmlspecialchars($candidate['full_name']); ?>"
                                                                 onerror="this.src='https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100&fit=crop&crop=face'">
                                                        </div>
                                                        <div class="candidate-info">
                                                            <div class="candidate-name"><?php echo htmlspecialchars($candidate['full_name']); ?></div>
                                                            <div class="candidate-position"><?php echo htmlspecialchars($candidate['position']); ?></div>
                                                            <div class="vote-bar">
                                                                <div class="vote-progress" style="width: <?php echo $candidate['vote_percentage'] ?: 0; ?>%"></div>
                                                            </div>
                                                            <div class="vote-stats">
                                                                <span class="vote-count"><?php echo number_format($candidate['vote_count']); ?> votes</span>
                                                                <span class="vote-percentage"><?php echo number_format($candidate['vote_percentage'] ?: 0, 1); ?>%</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Quick Stats Sidebar -->
                                <div class="results-card">
                                    <div class="results-header">
                                        <h2 class="results-title">Quick Stats</h2>
                                    </div>
                                    <div class="results-body">
                                        <div style="text-align: center; padding: 1rem;">
                                            <i class="fas fa-chart-pie" style="font-size: 3rem; color: #6366f1; margin-bottom: 1rem;"></i>
                                            <h4 style="margin-bottom: 1rem; color: #1e293b;">Election Summary</h4>
                                            <div style="text-align: left;">
                                                <div style="margin-bottom: 1rem; padding: 1rem; background: #f1f5f9; border-radius: 0.5rem;">
                                                    <h5 style="color: #1e293b; margin-bottom: 0.5rem;">
                                                        <i class="fas fa-trophy"></i> Leading Positions
                                                    </h5>
                                                    <?php 
                                                    $position_count = 0;
                                                    foreach ($results as $position => $candidates): 
                                                        if ($position_count >= 3) break; // Show only top 3
                                                        if (!empty($candidates)):
                                                            $leader = $candidates[0]; // First candidate is the leader
                                                    ?>
                                                        <div style="margin-bottom: 0.75rem; padding-bottom: 0.75rem; border-bottom: 1px solid #e2e8f0;">
                                                            <p style="font-weight: 600; color: #1e293b; font-size: 0.875rem;">
                                                                <?php echo htmlspecialchars($position); ?>
                                                            </p>
                                                            <p style="color: #10b981; font-size: 0.8rem;">
                                                                <?php echo htmlspecialchars($leader['full_name']); ?>
                                                                (<?php echo $leader['vote_count']; ?> votes)
                                                            </p>
                                                        </div>
                                                    <?php 
                                                        endif;
                                                        $position_count++;
                                                    endforeach; 
                                                    ?>
                                                </div>
                                                
                                                <div style="padding: 1rem; background: #f1f5f9; border-radius: 0.5rem;">
                                                    <h5 style="color: #1e293b; margin-bottom: 0.5rem;">
                                                        <i class="fas fa-info-circle"></i> Election Details
                                                    </h5>
                                                    <p style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.5rem;">
                                                        <strong>Start:</strong> <?php echo date('M j, Y', strtotime($selected_election['start_date'])); ?>
                                                    </p>
                                                    <p style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.5rem;">
                                                        <strong>End:</strong> <?php echo date('M j, Y', strtotime($selected_election['end_date'])); ?>
                                                    </p>
                                                    <p style="font-size: 0.875rem; color: #64748b;">
                                                        <strong>Status:</strong> 
                                                        <span style="color: #6366f1; font-weight: 600;">
                                                            <?php echo ucfirst($selected_election['status']); ?>
                                                        </span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="results-card">
                                <div class="results-body">
                                    <div class="no-data">
                                        <i class="fas fa-chart-bar"></i>
                                        <h3>No Results Available</h3>
                                        <p>No voting results found for this election yet.</p>
                                        <small style="color: #94a3b8;">
                                            Results will appear here once voting begins.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Analytics Summary Table -->
                        <?php if (!empty($results)): ?>
                        <div style="margin-top: 2rem;">
                            <div class="results-card">
                                <div class="results-header">
                                    <h2 class="results-title">
                                        <i class="fas fa-table"></i>
                                        Position Summary
                                    </h2>
                                </div>
                                <div class="results-body">
                                    <div style="overflow-x: auto;">
                                        <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 0.5rem; overflow: hidden;">
                                            <thead>
                                                <tr style="background: #a5b4fc; color: #4f46e5;">
                                                    <th style="padding: 1rem; text-align: left; font-weight: 600;">Position</th>
                                                    <th style="padding: 1rem; text-align: center; font-weight: 600;">Candidates</th>
                                                    <th style="padding: 1rem; text-align: center; font-weight: 600;">Total Votes</th>
                                                    <th style="padding: 1rem; text-align: left; font-weight: 600;">Leading Candidate</th>
                                                    <th style="padding: 1rem; text-align: center; font-weight: 600;">Lead Votes</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($results as $position => $candidates): ?>
                                                    <?php 
                                                    $total_position_votes = array_sum(array_column($candidates, 'vote_count'));
                                                    $leading_candidate = !empty($candidates) ? $candidates[0] : null;
                                                    ?>
                                                    <tr style="border-bottom: 1px solid #e2e8f0;">
                                                        <td style="padding: 1rem; font-weight: 600; color: #1e293b;">
                                                            <?php echo htmlspecialchars($position); ?>
                                                        </td>
                                                        <td style="padding: 1rem; text-align: center; color: #64748b;">
                                                            <?php echo count($candidates); ?>
                                                        </td>
                                                        <td style="padding: 1rem; text-align: center; font-weight: 600; color: #1e293b;">
                                                            <?php echo number_format($total_position_votes); ?>
                                                        </td>
                                                        <td style="padding: 1rem;">
                                                            <?php if ($leading_candidate): ?>
                                                                <span style="font-weight: 600; color: #10b981;">
                                                                    <?php echo htmlspecialchars($leading_candidate['full_name']); ?>
                                                                </span>
                                                                <br>
                                                                <small style="color: #64748b;">
                                                                    <?php echo number_format($leading_candidate['vote_percentage'], 1); ?>% of votes
                                                                </small>
                                                            <?php else: ?>
                                                                <span style="color: #94a3b8; font-style: italic;">No votes yet</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td style="padding: 1rem; text-align: center;">
                                                            <?php if ($leading_candidate): ?>
                                                                <span style="font-weight: 600; color: #6366f1;">
                                                                    <?php echo number_format($leading_candidate['vote_count']); ?>
                                                                </span>
                                                            <?php else: ?>
                                                                <span style="color: #94a3b8;">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- No Election Selected -->
                        <div class="results-card">
                            <div class="results-body">
                                <div class="no-data">
                                    <i class="fas fa-ballot-check"></i>
                                    <h3>No Election Selected</h3>
                                    <p>Please select an election from the dropdown above to view results.</p>
                                    <?php if (empty($elections)): ?>
                                        <div style="margin-top: 2rem; padding: 1rem; background: #fef3c7; border-radius: 0.5rem; color: #f59e0b;">
                                            <i class="fas fa-info-circle"></i>
                                            <strong>No elections found in the system.</strong><br>
                                            <small>Please create an election first to view results.</small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- Database Connection Failed -->
                    <div class="results-card">
                        <div class="results-body">
                            <div class="no-data">
                                <i class="fas fa-database" style="color: #ef4444;"></i>
                                <h3>Database Connection Failed</h3>
                                <p>Unable to connect to the voting system database.</p>
                                <div style="margin-top: 2rem; padding: 1rem; background: #fef2f2; border-radius: 0.5rem; color: #ef4444; text-align: left;">
                                    <h4 style="margin-bottom: 1rem;">Troubleshooting Steps:</h4>
                                    <ol style="margin-left: 1.5rem;">
                                        <li>Ensure MySQL server is running</li>
                                        <li>Verify the database name is 'voting_system'</li>
                                        <li>Check database credentials (host, username, password)</li>
                                        <li>Make sure the database exists and has the required tables</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Footer -->
                <div style="margin-top: 3rem; padding: 2rem; text-align: center; color: #94a3b8; border-top: 1px solid #e2e8f0;">
                    <p style="margin-bottom: 0.5rem;">
                        <i class="fas fa-shield-alt"></i>
                        University Voting System Admin Dashboard
                    </p>
                    <p style="font-size: 0.875rem;">
                        Last updated: <?php echo date('F j, Y \a\t g:i A'); ?> | 
                        <a href="#" onclick="refreshPage()" style="color: #6366f1; text-decoration: none;">
                            <i class="fas fa-sync-alt"></i> Refresh Data
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

        // Auto-refresh functionality
        let autoRefresh = false;
        let refreshInterval;

        function toggleAutoRefresh() {
            autoRefresh = !autoRefresh;
            const refreshBtn = document.querySelector('.btn-primary');
            
            if (autoRefresh) {
                refreshBtn.innerHTML = '<i class="fas fa-pause"></i> Pause Auto-Refresh';
                refreshBtn.classList.add('btn-warning');
                refreshBtn.classList.remove('btn-primary');
                refreshInterval = setInterval(() => {
                    location.reload();
                }, 30000); // Refresh every 30 seconds
                
                showNotification('Auto-refresh enabled (30s intervals)', 'success');
            } else {
                refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
                refreshBtn.classList.add('btn-primary');
                refreshBtn.classList.remove('btn-warning');
                clearInterval(refreshInterval);
                
                showNotification('Auto-refresh disabled', 'info');
            }
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

        // Page refresh function
        function refreshPage() {
            const refreshBtn = document.querySelector('.btn-primary');
            if (refreshBtn.innerHTML.includes('Pause')) {
                toggleAutoRefresh();
            } else {
                refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
                refreshBtn.disabled = true;
                setTimeout(() => {
                    location.reload();
                }, 500);
            }
        }

        // Election change function
        function changeElection(electionId) {
            if (electionId) {
                showNotification('Loading election data...', 'info');
                setTimeout(() => {
                    window.location.href = '?election_id=' + electionId;
                }, 200);
            }
        }

        // Export results function
        function exportResults() {
            const electionId = document.getElementById('election-select').value;
            if (electionId) {
                showNotification('Preparing export...', 'info');
                // In a real implementation, this would trigger the actual export
                setTimeout(() => {
                    showNotification('Export feature coming soon!', 'success');
                }, 1500);
            } else {
                showNotification('Please select an election first', 'error');
            }
        }

        // Animate progress bars on load
        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.vote-progress');
            progressBars.forEach((bar, index) => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, index * 200 + 800); // Staggered animation
            });

            // Animate overview cards
            const overviewCards = document.querySelectorAll('.overview-card');
            overviewCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 150 + 300);
            });
        });

        // Real-time updates simulation
        function simulateRealTimeUpdates() {
            const liveDot = document.querySelector('.live-dot');
            if (liveDot) {
                setInterval(() => {
                    // Simulate random data updates
                    if (Math.random() < 0.15) { // 15% chance every 2 seconds
                        liveDot.style.animation = 'none';
                        liveDot.style.background = '#f59e0b'; // Orange flash
                        setTimeout(() => {
                            liveDot.style.animation = 'pulse-dot 2s infinite';
                            liveDot.style.background = '#10b981';
                        }, 300);
                    }
                }, 2000);
            }
        }

        simulateRealTimeUpdates();

        // Initialize page
        console.log('🗳 University Voting System Admin Dashboard loaded successfully');
        console.log('📊 Election Results & Analytics module active');
        console.log('🔧 Database: voting_system');
        
        // Show success message if data loaded
        if (document.querySelector('.overview-card')) {
            setTimeout(() => {
                showNotification('Election data loaded successfully! 📊', 'success');
            }, 1000);
        }

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        });
    </script>
</body>
</html>