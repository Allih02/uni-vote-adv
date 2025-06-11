<?php
// results.php - Enhanced Election Results
require_once 'includes/session_check.php';
require_once 'config/database.php';

// Get election ID from query string
$election_id = $_GET['election_id'] ?? null;
$view_mode = $_GET['view'] ?? 'cards'; // cards, table, charts
$filter_status = $_GET['status'] ?? 'all';

// Get all elections for dropdown
try {
    $conn = getDBConnection();
    $stmt = $conn->query("SELECT election_id, title, status FROM elections ORDER BY created_at DESC");
    $elections = $stmt->fetchAll();
    
    // Get results for selected election
    if ($election_id) {
        // Get election details
        $stmt = $conn->prepare("SELECT * FROM elections WHERE election_id = ?");
        $stmt->execute([$election_id]);
        $current_election = $stmt->fetch();
        
        // Get comprehensive results with additional statistics
        $stmt = $conn->prepare("
            SELECT 
                p.position_id,
                p.title as position_title,
                p.description as position_description,
                c.candidate_id,
                c.full_name as candidate_name,
                c.photo as candidate_photo,
                c.party_affiliation,
                c.bio,
                COUNT(v.vote_id) as vote_count,
                (COUNT(v.vote_id) * 100.0 / NULLIF((
                    SELECT COUNT(DISTINCT voter_id) 
                    FROM votes v2 
                    JOIN candidates c2 ON v2.candidate_id = c2.candidate_id 
                    WHERE c2.position_id = p.position_id
                ), 0)) as vote_percentage,
                RANK() OVER (PARTITION BY p.position_id ORDER BY COUNT(v.vote_id) DESC) as candidate_rank
            FROM positions p
            LEFT JOIN candidates c ON p.position_id = c.position_id AND c.status = 'approved'
            LEFT JOIN votes v ON c.candidate_id = v.candidate_id
            WHERE p.election_id = ?
            GROUP BY p.position_id, c.candidate_id
            ORDER BY p.sort_order, vote_count DESC
        ");
        $stmt->execute([$election_id]);
        $results = $stmt->fetchAll();
        
        // Group results by position
        $grouped_results = [];
        foreach ($results as $result) {
            $position_id = $result['position_id'];
            if (!isset($grouped_results[$position_id])) {
                $grouped_results[$position_id] = [
                    'position_title' => $result['position_title'],
                    'position_description' => $result['position_description'],
                    'candidates' => [],
                    'total_votes' => 0
                ];
            }
            if ($result['candidate_id']) {
                $grouped_results[$position_id]['candidates'][] = $result;
                $grouped_results[$position_id]['total_votes'] += $result['vote_count'];
            }
        }
        
        // Get voting statistics and trends
        $stmt = $conn->prepare("
            SELECT 
                (SELECT COUNT(*) FROM voters) as total_voters,
                (SELECT COUNT(DISTINCT voter_id) FROM votes 
                 JOIN candidates ON votes.candidate_id = candidates.candidate_id 
                 JOIN positions ON candidates.position_id = positions.position_id 
                 WHERE positions.election_id = ?) as total_voted,
                (SELECT COUNT(*) FROM positions WHERE election_id = ?) as total_positions,
                (SELECT COUNT(*) FROM candidates 
                 WHERE position_id IN (SELECT position_id FROM positions WHERE election_id = ?) 
                 AND status = 'approved') as total_candidates
        ");
        $stmt->execute([$election_id, $election_id, $election_id]);
        $voting_stats = $stmt->fetch();
        
        // Get hourly voting trends (last 24 hours)
        $stmt = $conn->prepare("
            SELECT 
                DATE_FORMAT(v.voted_at, '%H:00') as hour,
                COUNT(*) as votes_count
            FROM votes v
            JOIN candidates c ON v.candidate_id = c.candidate_id
            JOIN positions p ON c.position_id = p.position_id
            WHERE p.election_id = ? 
            AND v.voted_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY DATE_FORMAT(v.voted_at, '%H:00')
            ORDER BY hour
        ");
        $stmt->execute([$election_id]);
        $hourly_trends = $stmt->fetchAll();
        
        $turnout_percentage = $voting_stats['total_voters'] > 0 
            ? round(($voting_stats['total_voted'] / $voting_stats['total_voters']) * 100, 2)
            : 0;
            
        // Calculate additional metrics
        $avg_votes_per_position = $voting_stats['total_positions'] > 0 
            ? round($voting_stats['total_voted'] / $voting_stats['total_positions'], 1)
            : 0;
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $elections = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    <?php echo getSessionScript($_SESSION['session_lifetime'], $_SESSION['login_time']); ?>
    <style>
        /* Enhanced CSS Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #8b5cf6;
            --accent: #f43f5e;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --info: #3b82f6;
            --background: #f8fafc;
            --surface: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-success: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
            --gradient-warning: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--background);
            color: var(--text-primary);
            line-height: 1.6;
        }

        /* Dashboard layout */
        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: var(--surface);
            border-right: 1px solid var(--border);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: var(--shadow);
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            min-height: 100vh;
            background: var(--background);
        }

        /* Enhanced top bar */
        .top-bar {
            background: var(--surface);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: between;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .top-bar h1 {
            flex: 1;
            font-size: 1.875rem;
            font-weight: 700;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-content {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Enhanced controls */
        .controls-bar {
            background: var(--surface);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
        }

        .controls-group {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .view-toggle {
            display: flex;
            background: var(--background);
            border-radius: 0.5rem;
            padding: 0.25rem;
            border: 1px solid var(--border);
        }

        .view-toggle button {
            padding: 0.5rem 1rem;
            border: none;
            background: transparent;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.2s;
            color: var(--text-secondary);
        }

        .view-toggle button.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        /* Enhanced statistics overview */
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--surface);
            padding: 2rem;
            border-radius: 1rem;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .stat-card.success::before { background: var(--gradient-success); }
        .stat-card.warning::before { background: var(--gradient-warning); }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            background: var(--primary);
        }

        .stat-content {
            text-align: left;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            line-height: 1;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .stat-change {
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0.25rem;
        }

        .stat-change.positive { color: var(--success); }
        .stat-change.negative { color: var(--error); }

        /* Enhanced position results */
        .position-results {
            background: var(--surface);
            border-radius: 1rem;
            border: 1px solid var(--border);
            margin-bottom: 2rem;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .position-results:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-lg);
        }

        .position-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 1.5rem 2rem;
            position: relative;
        }

        .position-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: rgba(255, 255, 255, 0.2);
        }

        .position-title {
            font-size: 1.375rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .position-description {
            opacity: 0.9;
            font-size: 0.875rem;
        }

        .position-meta {
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
            font-size: 0.875rem;
        }

        .candidates-results {
            padding: 2rem;
        }

        /* Enhanced candidate result cards */
        .candidate-result {
            background: var(--background);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid var(--border);
            transition: all 0.2s;
            position: relative;
        }

        .candidate-result:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
        }

        .candidate-result.winner {
            border-color: var(--success);
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(16, 185, 129, 0.02) 100%);
        }

        .candidate-result.winner::before {
            content: '🏆';
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.25rem;
        }

        .candidate-main {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .candidate-photo {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            object-fit: cover;
            background: var(--background);
            border: 3px solid var(--border);
            transition: border-color 0.2s;
        }

        .candidate-result:hover .candidate-photo {
            border-color: var(--primary);
        }

        .candidate-info {
            flex: 1;
        }

        .candidate-name {
            font-weight: 600;
            font-size: 1.125rem;
            margin-bottom: 0.25rem;
        }

        .candidate-party {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .candidate-rank {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            background: var(--primary);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Enhanced vote visualization */
        .vote-stats {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1rem;
            align-items: center;
        }

        .vote-bar-container {
            position: relative;
        }

        .vote-bar {
            background: var(--border);
            height: 32px;
            border-radius: 1rem;
            overflow: hidden;
            position: relative;
        }

        .vote-fill {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            height: 100%;
            transition: width 0.8s ease-out;
            position: relative;
        }

        .vote-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.2) 50%, transparent 70%);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .vote-text {
            position: absolute;
            top: 50%;
            left: 1rem;
            transform: translateY(-50%);
            font-size: 0.875rem;
            font-weight: 600;
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        .vote-count-display {
            text-align: right;
            min-width: 120px;
        }

        .vote-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .vote-percentage {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        /* Chart enhancements */
        .chart-container {
            background: var(--surface);
            border-radius: 1rem;
            border: 1px solid var(--border);
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: var(--shadow);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .chart-title {
            font-size: 1.125rem;
            font-weight: 600;
        }

        .chart-controls {
            display: flex;
            gap: 0.5rem;
        }

        /* Real-time indicator */
        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--success);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .live-dot {
            width: 8px;
            height: 8px;
            background: currentColor;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Table view styles */
        .results-table {
            background: var(--surface);
            border-radius: 1rem;
            overflow: hidden;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }

        .results-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .results-table th,
        .results-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .results-table th {
            background: var(--background);
            font-weight: 600;
            color: var(--text-primary);
        }

        .results-table tr:hover {
            background: rgba(99, 102, 241, 0.05);
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
            
            .sidebar {
                transform: translateX(-100%);
            }
            
            .controls-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .candidate-main {
                flex-direction: column;
                text-align: center;
            }
            
            .vote-stats {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
        }

        /* Button enhancements */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 2px 4px rgba(99, 102, 241, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(99, 102, 241, 0.3);
        }

        .btn-secondary {
            background: var(--surface);
            color: var(--text-primary);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        /* Form enhancements */
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: var(--surface);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        /* Navigation styles */
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }

        .sidebar-nav {
            padding: 1rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 0.5rem;
            margin-bottom: 0.25rem;
            transition: all 0.2s;
        }

        .nav-item:hover {
            background: var(--background);
            color: var(--text-primary);
        }

        .nav-item.active {
            background: var(--primary);
            color: white;
        }

        /* Alert styles */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            border: 1px solid;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            border-color: rgba(59, 130, 246, 0.2);
            color: var(--info);
        }

        /* Loading animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(99, 102, 241, 0.3);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Empty state */
        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }

        .no-results i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: var(--primary);
            opacity: 0.5;
        }

        .no-results h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="dashboard.php" class="logo">
                    <i class="fas fa-vote-yea"></i>
                    <span>VoteAdmin</span>
                </a>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="elections.php" class="nav-item">
                    <i class="fas fa-poll"></i>
                    <span>Elections</span>
                </a>
                <a href="candidates.php" class="nav-item">
                    <i class="fas fa-user-tie"></i>
                    <span>Candidates</span>
                </a>
                <a href="voters.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Voters</span>
                </a>
                <a href="results.php" class="nav-item active">
                    <i class="fas fa-chart-bar"></i>
                    <span>Results</span>
                </a>
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-file-alt"></i>
                    <span>Reports</span>
                </a>
                <a href="settings.php" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <h1>Election Results</h1>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <?php if ($election_id && isset($current_election) && $current_election['status'] === 'active'): ?>
                        <span class="live-indicator">
                            <span class="live-dot"></span>
                            Live Results
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($election_id && isset($current_election)): ?>
                        <button class="btn btn-secondary" onclick="refreshResults()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <button class="btn btn-primary" onclick="exportResults()">
                            <i class="fas fa-download"></i> Export Results
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="page-content">
                <!-- Enhanced Controls -->
                <div class="controls-bar">
                    <div class="controls-group">
                        <label for="election-select" style="font-weight: 500; color: var(--text-secondary);">Election:</label>
                        <form method="GET" style="display: inline;">
                            <select name="election_id" id="election-select" class="form-control" onchange="this.form.submit()" style="width: 300px;">
                                <option value="">Select Election</option>
                                <?php foreach ($elections as $election): ?>
                                    <option value="<?php echo $election['election_id']; ?>" <?php echo $election_id == $election['election_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($election['title']); ?> 
                                        (<?php echo ucfirst($election['status']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_mode); ?>">
                        </form>
                    </div>

                    <?php if ($election_id): ?>
                        <div class="controls-group">
                            <label style="font-weight: 500; color: var(--text-secondary);">View:</label>
                            <div class="view-toggle">
                                <button class="<?php echo $view_mode === 'cards' ? 'active' : ''; ?>" 
                                        onclick="changeView('cards')">
                                    <i class="fas fa-th-large"></i> Cards
                                </button>
                                <button class="<?php echo $view_mode === 'table' ? 'active' : ''; ?>" 
                                        onclick="changeView('table')">
                                    <i class="fas fa-table"></i> Table
                                </button>
                                <button class="<?php echo $view_mode === 'charts' ? 'active' : ''; ?>" 
                                        onclick="changeView('charts')">
                                    <i class="fas fa-chart-pie"></i> Charts
                                </button>
                            </div>
                        </div>

                        <div class="controls-group">
                            <button class="btn btn-secondary" onclick="toggleAutoRefresh()">
                                <i class="fas fa-clock" id="auto-refresh-icon"></i>
                                <span id="auto-refresh-text">Auto Refresh: OFF</span>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($election_id && isset($current_election)): ?>
                    <!-- Enhanced Statistics Overview -->
                    <div class="stats-overview">
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-content">
                                    <div class="stat-value"><?php echo number_format($voting_stats['total_voters']); ?></div>
                                    <div class="stat-label">Total Eligible Voters</div>
                                </div>
                                <div class="stat-icon" style="background: var(--info);">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card success">
                            <div class="stat-header">
                                <div class="stat-content">
                                    <div class="stat-value"><?php echo number_format($voting_stats['total_voted']); ?></div>
                                    <div class="stat-label">Total Votes Cast</div>
                                    <div class="stat-change positive">
                                        <i class="fas fa-arrow-up"></i> Active voting
                                    </div>
                                </div>
                                <div class="stat-icon" style="background: var(--success);">
                                    <i class="fas fa-vote-yea"></i>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card warning">
                            <div class="stat-header">
                                <div class="stat-content">
                                    <div class="stat-value"><?php echo $turnout_percentage; ?>%</div>
                                    <div class="stat-label">Voter Turnout</div>
                                    <?php if ($turnout_percentage >= 70): ?>
                                        <div class="stat-change positive">
                                            <i class="fas fa-arrow-up"></i> Excellent turnout
                                        </div>
                                    <?php elseif ($turnout_percentage >= 50): ?>
                                        <div class="stat-change">
                                            <i class="fas fa-minus"></i> Good turnout
                                        </div>
                                    <?php else: ?>
                                        <div class="stat-change negative">
                                            <i class="fas fa-arrow-down"></i> Low turnout
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="stat-icon" style="background: var(--warning);">
                                    <i class="fas fa-percentage"></i>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-content">
                                    <div class="stat-value"><?php echo $voting_stats['total_positions']; ?></div>
                                    <div class="stat-label">Total Positions</div>
                                    <div class="stat-change">
                                        <i class="fas fa-info-circle"></i> <?php echo $voting_stats['total_candidates']; ?> candidates
                                    </div>
                                </div>
                                <div class="stat-icon" style="background: var(--secondary);">
                                    <i class="fas fa-list"></i>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-content">
                                    <div class="stat-value"><?php echo $avg_votes_per_position; ?></div>
                                    <div class="stat-label">Avg Votes/Position</div>
                                    <div class="stat-change">
                                        <i class="fas fa-calculator"></i> Per position metric
                                    </div>
                                </div>
                                <div class="stat-icon" style="background: var(--accent);">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Live Status Alert -->
                    <?php if ($current_election['status'] === 'active'): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            This election is currently active. Results are updating in real-time as votes are cast.
                            <span style="margin-left: auto; font-size: 0.75rem;">
                                Last updated: <span id="last-updated">Just now</span>
                            </span>
                        </div>
                    <?php endif; ?>

                    <!-- Voting Trends Chart -->
                    <?php if (!empty($hourly_trends)): ?>
                        <div class="chart-container">
                            <div class="chart-header">
                                <h3 class="chart-title">
                                    <i class="fas fa-chart-line"></i> Voting Trends (Last 24 Hours)
                                </h3>
                                <div class="chart-controls">
                                    <button class="btn btn-secondary" onclick="toggleTrendChart()">
                                        <i class="fas fa-expand-alt"></i>
                                    </button>
                                </div>
                            </div>
                            <canvas id="voting-trends-chart" height="100"></canvas>
                        </div>
                    <?php endif; ?>

                    <!-- Results Display Based on View Mode -->
                    <?php if ($view_mode === 'table'): ?>
                        <!-- Table View -->
                        <div class="results-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Position</th>
                                        <th>Candidate</th>
                                        <th>Party</th>
                                        <th>Votes</th>
                                        <th>Percentage</th>
                                        <th>Rank</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($grouped_results as $position_id => $position): ?>
                                        <?php foreach ($position['candidates'] as $index => $candidate): ?>
                                            <tr class="<?php echo $candidate['candidate_rank'] == 1 ? 'winner' : ''; ?>">
                                                <?php if ($index === 0): ?>
                                                    <td rowspan="<?php echo count($position['candidates']); ?>" style="vertical-align: top; font-weight: 600;">
                                                        <?php echo htmlspecialchars($position['position_title']); ?>
                                                    </td>
                                                <?php endif; ?>
                                                <td>
                                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                        <?php if ($candidate['candidate_photo']): ?>
                                                            <img src="uploads/candidates/<?php echo htmlspecialchars($candidate['candidate_photo']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($candidate['candidate_name']); ?>" 
                                                                 style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                                                        <?php else: ?>
                                                            <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--background); display: flex; align-items: center; justify-content: center;">
                                                                <i class="fas fa-user" style="font-size: 0.75rem; color: var(--text-secondary);"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php echo htmlspecialchars($candidate['candidate_name']); ?>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($candidate['party_affiliation'] ?: 'Independent'); ?></td>
                                                <td><strong><?php echo number_format($candidate['vote_count']); ?></strong></td>
                                                <td><?php echo number_format($candidate['vote_percentage'], 1); ?>%</td>
                                                <td>
                                                    <span class="candidate-rank">
                                                        #<?php echo $candidate['candidate_rank']; ?>
                                                        <?php if ($candidate['candidate_rank'] == 1): ?>
                                                            <i class="fas fa-crown"></i>
                                                        <?php endif; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($candidate['candidate_rank'] == 1): ?>
                                                        <span style="color: var(--success); font-weight: 600;">Leading</span>
                                                    <?php else: ?>
                                                        <span style="color: var(--text-secondary);">Following</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    <?php elseif ($view_mode === 'charts'): ?>
                        <!-- Charts View -->
                        <?php foreach ($grouped_results as $position_id => $position): ?>
                            <div class="chart-container">
                                <div class="chart-header">
                                    <div>
                                        <h3 class="chart-title"><?php echo htmlspecialchars($position['position_title']); ?></h3>
                                        <?php if ($position['position_description']): ?>
                                            <p style="color: var(--text-secondary); font-size: 0.875rem; margin-top: 0.25rem;">
                                                <?php echo htmlspecialchars($position['position_description']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="chart-controls">
                                        <button class="btn btn-secondary" onclick="toggleChartType('chart-<?php echo $position_id; ?>')">
                                            <i class="fas fa-exchange-alt"></i> Toggle Chart
                                        </button>
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; align-items: center;">
                                    <div>
                                        <canvas id="chart-<?php echo $position_id; ?>" width="400" height="400"></canvas>
                                    </div>
                                    <div>
                                        <h4 style="margin-bottom: 1rem;">Candidate Summary</h4>
                                        <?php foreach ($position['candidates'] as $candidate): ?>
                                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; margin-bottom: 0.5rem; background: var(--background); border-radius: 0.5rem; border-left: 4px solid var(--primary);">
                                                <div>
                                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($candidate['candidate_name']); ?></div>
                                                    <div style="font-size: 0.875rem; color: var(--text-secondary);">
                                                        <?php echo htmlspecialchars($candidate['party_affiliation'] ?: 'Independent'); ?>
                                                    </div>
                                                </div>
                                                <div style="text-align: right;">
                                                    <div style="font-weight: 700; color: var(--primary);"><?php echo number_format($candidate['vote_count']); ?></div>
                                                    <div style="font-size: 0.875rem; color: var(--text-secondary);"><?php echo number_format($candidate['vote_percentage'], 1); ?>%</div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    <?php else: ?>
                        <!-- Cards View (Default) -->
                        <?php foreach ($grouped_results as $position_id => $position): ?>
                            <div class="position-results" id="position-<?php echo $position_id; ?>">
                                <div class="position-header">
                                    <h3 class="position-title"><?php echo htmlspecialchars($position['position_title']); ?></h3>
                                    <?php if ($position['position_description']): ?>
                                        <div class="position-description"><?php echo htmlspecialchars($position['position_description']); ?></div>
                                    <?php endif; ?>
                                    <div class="position-meta">
                                        <span><i class="fas fa-users"></i> <?php echo count($position['candidates']); ?> Candidates</span>
                                        <span><i class="fas fa-vote-yea"></i> <?php echo number_format($position['total_votes']); ?> Total Votes</span>
                                    </div>
                                </div>
                                <div class="candidates-results">
                                    <?php if (empty($position['candidates'])): ?>
                                        <div class="no-results">
                                            <i class="fas fa-inbox"></i>
                                            <h4>No candidates for this position</h4>
                                            <p>Candidates will appear here once they are approved.</p>
                                        </div>
                                    <?php else: ?>
                                        <?php 
                                        $max_votes = max(array_column($position['candidates'], 'vote_count'));
                                        foreach ($position['candidates'] as $candidate): 
                                            $percentage = $max_votes > 0 ? ($candidate['vote_count'] / $max_votes) * 100 : 0;
                                            $is_winner = $candidate['candidate_rank'] == 1;
                                        ?>
                                            <div class="candidate-result <?php echo $is_winner ? 'winner' : ''; ?>" 
                                                 data-candidate-id="<?php echo $candidate['candidate_id']; ?>">
                                                <div class="candidate-main">
                                                    <?php if ($candidate['candidate_photo']): ?>
                                                        <img src="uploads/candidates/<?php echo htmlspecialchars($candidate['candidate_photo']); ?>" 
                                                             alt="<?php echo htmlspecialchars($candidate['candidate_name']); ?>" 
                                                             class="candidate-photo">
                                                    <?php else: ?>
                                                        <div class="candidate-photo" style="display: flex; align-items: center; justify-content: center;">
                                                            <i class="fas fa-user" style="color: var(--text-secondary);"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <div class="candidate-info">
                                                        <div class="candidate-name"><?php echo htmlspecialchars($candidate['candidate_name']); ?></div>
                                                        <?php if ($candidate['party_affiliation']): ?>
                                                            <div class="candidate-party">
                                                                <i class="fas fa-flag"></i> <?php echo htmlspecialchars($candidate['party_affiliation']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <span class="candidate-rank">
                                                            <i class="fas fa-medal"></i> Rank #<?php echo $candidate['candidate_rank']; ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                
                                                <div class="vote-stats">
                                                    <div class="vote-bar-container">
                                                        <div class="vote-bar">
                                                            <div class="vote-fill" 
                                                                 style="width: <?php echo $percentage; ?>%" 
                                                                 data-percentage="<?php echo $candidate['vote_percentage']; ?>"></div>
                                                            <div class="vote-text"><?php echo number_format($candidate['vote_percentage'], 1); ?>%</div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="vote-count-display">
                                                        <div class="vote-number"><?php echo number_format($candidate['vote_count']); ?></div>
                                                        <div class="vote-percentage">votes</div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        
                                        <!-- Mini chart for position -->
                                        <div class="chart-container" style="margin-top: 2rem;">
                                            <div class="chart-header">
                                                <h4 class="chart-title">
                                                    <i class="fas fa-chart-bar"></i> Vote Distribution
                                                </h4>
                                            </div>
                                            <canvas id="mini-chart-<?php echo $position_id; ?>" height="80"></canvas>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Summary Chart -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3 class="chart-title">
                                <i class="fas fa-chart-pie"></i> Election Summary
                            </h3>
                            <div class="chart-controls">
                                <button class="btn btn-secondary" onclick="downloadChart('summary-chart')">
                                    <i class="fas fa-download"></i> Download
                                </button>
                            </div>
                        </div>
                        <canvas id="summary-chart" height="100"></canvas>
                    </div>

                <?php else: ?>
                    <!-- Enhanced No Election Selected State -->
                    <div class="no-results">
                        <i class="fas fa-poll"></i>
                        <h3>No Election Selected</h3>
                        <p>Please select an election from the dropdown above to view comprehensive results and analytics.</p>
                        <?php if (!empty($elections)): ?>
                            <div style="margin-top: 2rem;">
                                <h4 style="margin-bottom: 1rem;">Available Elections:</h4>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; max-width: 800px; margin: 0 auto;">
                                    <?php foreach (array_slice($elections, 0, 4) as $election): ?>
                                        <div style="background: var(--surface); padding: 1.5rem; border-radius: 0.75rem; border: 1px solid var(--border); text-align: left;">
                                            <h5 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($election['title']); ?></h5>
                                            <p style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 1rem;">
                                                Status: <span style="color: var(--primary); font-weight: 600;"><?php echo ucfirst($election['status']); ?></span>
                                            </p>
                                            <a href="?election_id=<?php echo $election['election_id']; ?>" class="btn btn-primary" style="width: 100%; justify-content: center;">
                                                View Results
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- JavaScript for Enhanced Functionality -->
    <script>
        let autoRefreshInterval;
        let isAutoRefreshEnabled = false;
        
        // Auto-refresh functionality
        function toggleAutoRefresh() {
            const icon = document.getElementById('auto-refresh-icon');
            const text = document.getElementById('auto-refresh-text');
            
            if (isAutoRefreshEnabled) {
                clearInterval(autoRefreshInterval);
                isAutoRefreshEnabled = false;
                icon.className = 'fas fa-clock';
                text.textContent = 'Auto Refresh: OFF';
            } else {
                autoRefreshInterval = setInterval(refreshResults, 30000); // 30 seconds
                isAutoRefreshEnabled = true;
                icon.className = 'fas fa-sync-alt';
                text.textContent = 'Auto Refresh: ON';
            }
        }
        
        // Refresh results
        function refreshResults() {
            if (<?php echo json_encode($election_id); ?>) {
                updateLastUpdatedTime();
                // In a real implementation, you would fetch new data via AJAX
                location.reload();
            }
        }
        
        // Update last updated time
        function updateLastUpdatedTime() {
            const lastUpdated = document.getElementById('last-updated');
            if (lastUpdated) {
                lastUpdated.textContent = new Date().toLocaleTimeString();
            }
        }
        
        // Change view mode
        function changeView(view) {
            const url = new URL(window.location);
            url.searchParams.set('view', view);
            window.location.href = url.toString();
        }
        
        // Export results
        function exportResults() {
            const electionId = <?php echo json_encode($election_id); ?>;
            if (electionId) {
                window.location.href = `export_results.php?election_id=${electionId}`;
            }
        }
        
        // Download chart
        function downloadChart(chartId) {
            const chart = Chart.getChart(chartId);
            if (chart) {
                const url = chart.toBase64Image();
                const a = document.createElement('a');
                a.href = url;
                a.download = `${chartId}-${new Date().toISOString().split('T')[0]}.png`;
                a.click();
            }
        }
        
        // Toggle chart type
        function toggleChartType(chartId) {
            const chart = Chart.getChart(chartId);
            if (chart) {
                chart.config.type = chart.config.type === 'pie' ? 'bar' : 'pie';
                chart.update();
            }
        }
        
        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            // Voting trends chart
            <?php if (!empty($hourly_trends)): ?>
            const trendsCtx = document.getElementById('voting-trends-chart');
            if (trendsCtx) {
                new Chart(trendsCtx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode(array_column($hourly_trends, 'hour')); ?>,
                        datasets: [{
                            label: 'Votes per Hour',
                            data: <?php echo json_encode(array_column($hourly_trends, 'votes_count')); ?>,
                            borderColor: 'rgb(99, 102, 241)',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }
            <?php endif; ?>
            
            // Position charts
            <?php foreach ($grouped_results as $position_id => $position): ?>
                <?php if (!empty($position['candidates'])): ?>
                    // Main chart for position
                    const ctx<?php echo $position_id; ?> = document.getElementById('chart-<?php echo $position_id; ?>');
                    if (ctx<?php echo $position_id; ?>) {
                        new Chart(ctx<?php echo $position_id; ?>, {
                            type: 'pie',
                            data: {
                                labels: <?php echo json_encode(array_column($position['candidates'], 'candidate_name')); ?>,
                                datasets: [{
                                    data: <?php echo json_encode(array_column($position['candidates'], 'vote_count')); ?>,
                                    backgroundColor: [
                                        '#6366f1', '#8b5cf6', '#f43f5e', '#10b981', '#f59e0b', 
                                        '#ef4444', '#3b82f6', '#6366f1', '#8b5cf6', '#f43f5e'
                                    ],
                                    borderWidth: 2,
                                    borderColor: '#ffffff'
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: {
                                        position: 'bottom'
                                    }
                                }
                            }
                        });
                    }
                    
                    // Mini chart for position
                    const miniCtx<?php echo $position_id; ?> = document.getElementById('mini-chart-<?php echo $position_id; ?>');
                    if (miniCtx<?php echo $position_id; ?>) {
                        new Chart(miniCtx<?php echo $position_id; ?>, {
                            type: 'bar',
                            data: {
                                labels: <?php echo json_encode(array_map(function($name) { 
                                    return strlen($name) > 15 ? substr($name, 0, 15) . '...' : $name; 
                                }, array_column($position['candidates'], 'candidate_name'))); ?>,
                                datasets: [{
                                    label: 'Votes',
                                    data: <?php echo json_encode(array_column($position['candidates'], 'vote_count')); ?>,
                                    backgroundColor: 'rgba(99, 102, 241, 0.8)',
                                    borderColor: 'rgba(99, 102, 241, 1)',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            stepSize: 1
                                        }
                                    },
                                    x: {
                                        ticks: {
                                            maxRotation: 45
                                        }
                                    }
                                }
                            }
                        });
                    }
                <?php endif; ?>
            <?php endforeach; ?>
            
            // Summary chart
            const summaryCtx = document.getElementById('summary-chart');
            if (summaryCtx) {
                const summaryData = [];
                const summaryLabels = [];
                
                <?php foreach ($grouped_results as $position_id => $position): ?>
                    summaryLabels.push('<?php echo addslashes($position['position_title']); ?>');
                    summaryData.push(<?php echo $position['total_votes']; ?>);
                <?php endforeach; ?>
                
                new Chart(summaryCtx, {
                    type: 'doughnut',
                    data: {
                        labels: summaryLabels,
                        datasets: [{
                            data: summaryData,
                            backgroundColor: [
                                '#6366f1', '#8b5cf6', '#f43f5e', '#10b981', '#f59e0b', 
                                '#ef4444', '#3b82f6', '#6366f1', '#8b5cf6', '#f43f5e'
                            ],
                            borderWidth: 3,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'right'
                            },
                            title: {
                                display: true,
                                text: 'Votes Distribution by Position'
                            }
                        }
                    }
                });
            }
            
            // Animate vote bars
            const voteFills = document.querySelectorAll('.vote-fill');
            setTimeout(() => {
                voteFills.forEach(fill => {
                    const percentage = fill.dataset.percentage;
                    fill.style.width = percentage + '%';
                });
            }, 500);
            
            // Update time every minute
            setInterval(updateLastUpdatedTime, 60000);
        });
    </script>
</body>
</html>