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
        /* CSS Variables */
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

        /* Error Alert */
        .error-alert {
            background: var(--error);
            color: white;
            padding: 1rem 2rem;
            margin: 1rem 2rem;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .error-alert i {
            font-size: 1.25rem;
        }

        /* Election Selector */
        .election-selector {
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

        .selector-label {
            font-weight: 600;
            color: var(--text-primary);
        }

        .election-select {
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            background: var(--surface);
            color: var(--text-primary);
            font-size: 1rem;
            min-width: 300px;
        }

        .election-status {
            padding: 0.375rem 0.75rem;
            border-radius: var(--radius-md);
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
            background: var(--surface);
            padding: 1.5rem;
            border-radius: var(--radius-xl);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
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
            background: var(--primary);
        }

        .overview-card.success::before { background: var(--success); }
        .overview-card.warning::before { background: var(--warning); }
        .overview-card.info::before { background: var(--info); }

        .overview-card:hover {
            box-shadow: var(--shadow-md);
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
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
            background: var(--primary);
        }

        .overview-card.success .overview-icon { background: var(--success); }
        .overview-card.warning .overview-icon { background: var(--warning); }
        .overview-card.info .overview-icon { background: var(--info); }

        .overview-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .overview-label {
            color: var(--text-secondary);
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
            background: var(--surface);
            border-radius: var(--radius-xl);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .results-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .results-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
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
            color: var(--text-primary);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--border);
        }

        .candidate-result {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border);
        }

        .candidate-result:last-child {
            border-bottom: none;
        }

        .candidate-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid var(--border);
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
            color: var(--text-primary);
        }

        .candidate-position {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .vote-bar {
            background: var(--border);
            height: 6px;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 0.25rem;
        }

        .vote-progress {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
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
            color: var(--text-primary);
        }

        .vote-percentage {
            color: var(--primary);
            font-weight: 600;
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

        /* Live Updates */
        .live-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--success);
        }

        .live-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--success);
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
            color: var(--text-secondary);
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--text-muted);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .results-grid {
                grid-template-columns: 1fr;
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

        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="dashboard.php" class="logo">
                    <i class="fas fa-vote-yea"></i>
                    VoteAdmin
                </a>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="dashboard.php" class="nav-item">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                    <a href="admin_elections.php" class="nav-item">
                        <i class="fas fa-calendar-check"></i>
                        Elections
                    </a>
                    <a href="admin_candidates.php" class="nav-item">
                        <i class="fas fa-users"></i>
                        Candidates
                    </a>
                    <a href="admin_voters.php" class="nav-item">
                        <i class="fas fa-user-friends"></i>
                        Voters
                    </a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Reports & Analytics</div>
                    <a href="admin_results.php" class="nav-item active">
                        <i class="fas fa-chart-bar"></i>
                        Results
                    </a>
                    <a href="admin_analytics.php" class="nav-item">
                        <i class="fas fa-analytics"></i>
                        Analytics
                    </a>
                    <a href="admin_reports.php" class="nav-item">
                        <i class="fas fa-file-alt"></i>
                        Reports
                    </a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">System</div>
                    <a href="admin_settings.php" class="nav-item">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                    <a href="admin_users.php" class="nav-item">
                        <i class="fas fa-user-shield"></i>
                        Admin Users
                    </a>
                    <a href="logout.php" class="nav-item">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </nav>
        </aside>

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
                                            <i class="fas fa-chart-pie" style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;"></i>
                                            <h4 style="margin-bottom: 1rem; color: var(--text-primary);">Election Summary</h4>
                                            <div style="text-align: left;">
                                                <div style="margin-bottom: 1rem; padding: 1rem; background: var(--surface-hover); border-radius: var(--radius-md);">
                                                    <h5 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                                                        <i class="fas fa-trophy"></i> Leading Positions
                                                    </h5>
                                                    <?php 
                                                    $position_count = 0;
                                                    foreach ($results as $position => $candidates): 
                                                        if ($position_count >= 3) break; // Show only top 3
                                                        if (!empty($candidates)):
                                                            $leader = $candidates[0]; // First candidate is the leader
                                                    ?>
                                                        <div style="margin-bottom: 0.75rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border);">
                                                            <p style="font-weight: 600; color: var(--text-primary); font-size: 0.875rem;">
                                                                <?php echo htmlspecialchars($position); ?>
                                                            </p>
                                                            <p style="color: var(--success); font-size: 0.8rem;">
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
                                                
                                                <div style="padding: 1rem; background: var(--surface-hover); border-radius: var(--radius-md);">
                                                    <h5 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                                                        <i class="fas fa-info-circle"></i> Election Details
                                                    </h5>
                                                    <p style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.5rem;">
                                                        <strong>Start:</strong> <?php echo date('M j, Y', strtotime($selected_election['start_date'])); ?>
                                                    </p>
                                                    <p style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.5rem;">
                                                        <strong>End:</strong> <?php echo date('M j, Y', strtotime($selected_election['end_date'])); ?>
                                                    </p>
                                                    <p style="font-size: 0.875rem; color: var(--text-secondary);">
                                                        <strong>Status:</strong> 
                                                        <span style="color: var(--primary); font-weight: 600;">
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
                                        <small style="color: var(--text-muted);">
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
                                        <table style="width: 100%; border-collapse: collapse; background: white; border-radius: var(--radius-md); overflow: hidden;">
                                            <thead>
                                                <tr style="background: var(--primary-light); color: var(--primary-dark);">
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
                                                    <tr style="border-bottom: 1px solid var(--border);">
                                                        <td style="padding: 1rem; font-weight: 600; color: var(--text-primary);">
                                                            <?php echo htmlspecialchars($position); ?>
                                                        </td>
                                                        <td style="padding: 1rem; text-align: center; color: var(--text-secondary);">
                                                            <?php echo count($candidates); ?>
                                                        </td>
                                                        <td style="padding: 1rem; text-align: center; font-weight: 600; color: var(--text-primary);">
                                                            <?php echo number_format($total_position_votes); ?>
                                                        </td>
                                                        <td style="padding: 1rem;">
                                                            <?php if ($leading_candidate): ?>
                                                                <span style="font-weight: 600; color: var(--success);">
                                                                    <?php echo htmlspecialchars($leading_candidate['full_name']); ?>
                                                                </span>
                                                                <br>
                                                                <small style="color: var(--text-secondary);">
                                                                    <?php echo number_format($leading_candidate['vote_percentage'], 1); ?>% of votes
                                                                </small>
                                                            <?php else: ?>
                                                                <span style="color: var(--text-muted); font-style: italic;">No votes yet</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td style="padding: 1rem; text-align: center;">
                                                            <?php if ($leading_candidate): ?>
                                                                <span style="font-weight: 600; color: var(--primary);">
                                                                    <?php echo number_format($leading_candidate['vote_count']); ?>
                                                                </span>
                                                            <?php else: ?>
                                                                <span style="color: var(--text-muted);">-</span>
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
                                        <div style="margin-top: 2rem; padding: 1rem; background: var(--warning-light); border-radius: var(--radius-md); color: var(--warning);">
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
                                <i class="fas fa-database" style="color: var(--error);"></i>
                                <h3>Database Connection Failed</h3>
                                <p>Unable to connect to the voting system database.</p>
                                <div style="margin-top: 2rem; padding: 1rem; background: var(--error-light); border-radius: var(--radius-md); color: var(--error); text-align: left;">
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
                <div style="margin-top: 3rem; padding: 2rem; text-align: center; color: var(--text-muted); border-top: 1px solid var(--border);">
                    <p style="margin-bottom: 0.5rem;">
                        <i class="fas fa-shield-alt"></i>
                        University Voting System Admin Dashboard
                    </p>
                    <p style="font-size: 0.875rem;">
                        Last updated: <?php echo date('F j, Y \a\t g:i A'); ?> | 
                        <a href="#" onclick="refreshPage()" style="color: var(--primary); text-decoration: none;">
                            <i class="fas fa-sync-alt"></i> Refresh Data
                        </a>
                    </p>
                </div>
            </div>
        </main>
    </div>

    <script>
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
                background: var(--${type === 'success' ? 'success' : type === 'error' ? 'error' : 'info'});
                color: white;
                padding: 1rem 1.5rem;
                border-radius: var(--radius-md);
                box-shadow: var(--shadow-lg);
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
                            liveDot.style.background = 'var(--success)';
                        }, 300);
                    }
                }, 2000);
            }
        }

        simulateRealTimeUpdates();

        // Mobile sidebar toggle
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('open');
        }

        // Add mobile menu button for responsive design
        if (window.innerWidth <= 768) {
            const topBar = document.querySelector('.top-bar');
            const topBarActions = topBar.querySelector('.top-bar-actions');
            const menuButton = document.createElement('button');
            menuButton.innerHTML = '<i class="fas fa-bars"></i>';
            menuButton.className = 'btn btn-secondary';
            menuButton.onclick = toggleSidebar;
            topBarActions.insertBefore(menuButton, topBarActions.firstChild);
        }

        // Close sidebar on outside click (mobile)
        document.addEventListener('click', function(e) {
            const sidebar = document.querySelector('.sidebar');
            const menuButton = document.querySelector('.fa-bars')?.closest('button');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                (!menuButton || !menuButton.contains(e.target))) {
                sidebar.classList.remove('open');
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.querySelector('.sidebar');
            if (window.innerWidth > 768) {
                sidebar.classList.remove('open');
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + R for refresh
            if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                e.preventDefault();
                refreshPage();
            }
            
            // Escape to close sidebar on mobile
            if (e.key === 'Escape' && window.innerWidth <= 768) {
                document.querySelector('.sidebar').classList.remove('open');
            }
        });

        // Table sorting functionality
        function sortTable(columnIndex, ascending = true) {
            const table = document.querySelector('table');
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
                    return ascending ? aNum - bNum : bNum - aNum;
                }
                
                return ascending ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
            });
            
            // Re-append sorted rows
            rows.forEach(row => tbody.appendChild(row));
        }

        // Add click handlers to table headers for sorting
        document.addEventListener('DOMContentLoaded', function() {
            const headers = document.querySelectorAll('th');
            headers.forEach((header, index) => {
                header.style.cursor = 'pointer';
                header.style.userSelect = 'none';
                header.addEventListener('click', () => {
                    sortTable(index, !header.classList.contains('sorted-desc'));
                    
                    // Update header indicators
                    headers.forEach(h => h.classList.remove('sorted-asc', 'sorted-desc'));
                    header.classList.add(header.classList.contains('sorted-desc') ? 'sorted-asc' : 'sorted-desc');
                });
            });
        });

        // Print functionality
        function printResults() {
            window.print();
        }

        // Add print styles
        const printStyles = `
            @media print {
                .sidebar, .top-bar-actions, .btn { display: none !important; }
                .main-content { margin-left: 0 !important; }
                .top-bar { border-bottom: 2px solid #000; }
                .results-card { break-inside: avoid; }
                .page-content { padding: 1rem !important; }
            }
        `;
        
        const styleSheet = document.createElement('style');
        styleSheet.textContent = printStyles;
        document.head.appendChild(styleSheet);

        // Initialize page
        console.log('🗳️ University Voting System Admin Dashboard loaded successfully');
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