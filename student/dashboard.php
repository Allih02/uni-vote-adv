<?php
// dashboard.php - Student Dashboard with Inline Database Configuration
session_start();

// Database Configuration (inline)
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

// Get voter information
$voter = getVoterById($_SESSION['voter_id']);
if (!$voter) {
    session_destroy();
    header("Location: student_login.php?error=session_expired");
    exit();
}

// Get active elections that the voter is eligible for
$eligible_elections_sql = "SELECT * FROM elections 
                          WHERE status = 'active' 
                          AND JSON_CONTAINS(eligible_years, :year)
                          AND JSON_CONTAINS(eligible_faculties, :faculty)
                          AND start_date <= NOW() 
                          AND end_date >= NOW()
                          ORDER BY end_date ASC";

$eligible_elections = fetchAll($eligible_elections_sql, [
    'year' => '"' . $voter['year'] . '"',
    'faculty' => '"' . $voter['faculty'] . '"'
]);

// Get elections the voter has already voted in
$voted_elections_sql = "SELECT e.*, v.voted_at 
                       FROM elections e
                       INNER JOIN votes v ON e.id = v.election_id
                       WHERE v.voter_id = :voter_id
                       ORDER BY v.voted_at DESC";

$voted_elections = fetchAll($voted_elections_sql, ['voter_id' => $voter['id']]);

// Get upcoming elections
$upcoming_elections_sql = "SELECT * FROM elections 
                          WHERE status = 'active' 
                          AND JSON_CONTAINS(eligible_years, :year)
                          AND JSON_CONTAINS(eligible_faculties, :faculty)
                          AND start_date > NOW()
                          ORDER BY start_date ASC
                          LIMIT 3";

$upcoming_elections = fetchAll($upcoming_elections_sql, [
    'year' => '"' . $voter['year'] . '"',
    'faculty' => '"' . $voter['faculty'] . '"'
]);

// Calculate participation rate
$participation_rate = $voter['eligible_elections'] > 0 ? 
    round(($voter['votes_cast'] / $voter['eligible_elections']) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - University Voting System</title>
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

        .dashboard {
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 1rem 2rem;
            box-shadow: var(--shadow-sm);
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

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-info {
            text-align: right;
        }

        .user-name {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .user-role {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .logout-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: var(--radius);
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .logout-btn:hover {
            color: var(--error);
            background: var(--surface-hover);
        }

        /* Main Content */
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .welcome-section {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
            box-shadow: var(--shadow-md);
        }

        .welcome-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .welcome-subtitle {
            font-size: 1rem;
            opacity: 0.9;
        }

        /* Profile Card */
        .profile-card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: 700;
        }

        .profile-info h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .profile-info p {
            color: var(--text-secondary);
            margin-bottom: 0.125rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 0.5rem;
        }

        .status-active {
            background: #ecfdf5;
            color: #065f46;
        }

        .profile-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .detail-group h4 {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border);
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 500;
            color: var(--text-secondary);
        }

        .detail-value {
            font-weight: 600;
            color: var(--text-primary);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary);
        }

        .stat-card.success::before { background: var(--success); }
        .stat-card.warning::before { background: var(--warning); }
        .stat-card.info::before { background: var(--info); }

        .stat-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
            background: var(--primary);
        }

        .stat-card.success .stat-icon { background: var(--success); }
        .stat-card.warning .stat-icon { background: var(--warning); }
        .stat-card.info .stat-icon { background: var(--info); }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Elections Grid */
        .elections-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .elections-section {
            background: var(--surface);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
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

        .elections-list {
            padding: 1.5rem;
        }

        .election-item {
            padding: 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            margin-bottom: 1rem;
            transition: all 0.2s ease;
        }

        .election-item:last-child {
            margin-bottom: 0;
        }

        .election-item:hover {
            background: var(--surface-hover);
        }

        .election-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }

        .election-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .election-meta {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .election-date {
            font-size: 0.75rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .election-status {
            padding: 0.25rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: #ecfdf5;
            color: #065f46;
        }

        .status-voted {
            background: #eff6ff;
            color: #1e40af;
        }

        .status-upcoming {
            background: #fef3c7;
            color: #92400e;
        }

        .election-description {
            color: var(--text-secondary);
            font-size: 0.875rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }

        .election-actions {
            display: flex;
            gap: 0.75rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.375rem;
            padding: 0.5rem 1rem;
            border-radius: var(--radius);
            font-weight: 500;
            font-size: 0.875rem;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            white-space: nowrap;
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

        .btn-secondary {
            background: var(--surface);
            color: var(--text-primary);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--surface-hover);
        }

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

        /* Participation Progress */
        .participation-progress {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .progress-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .progress-percentage {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
        }

        .progress-bar {
            width: 100%;
            height: 12px;
            background: var(--border);
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 6px;
            transition: width 0.5s ease;
        }

        .progress-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
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

            .user-menu {
                width: 100%;
                justify-content: space-between;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .profile-details {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .elections-grid {
                grid-template-columns: 1fr;
            }

            .election-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .election-actions {
                flex-direction: column;
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .welcome-section {
                padding: 1.5rem;
            }

            .profile-card {
                padding: 1.5rem;
            }

            .elections-list {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-vote-yea"></i>
                    <span>University Voting System</span>
                </div>
                
                <div class="user-menu">
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($voter['full_name']); ?></div>
                        <div class="user-role"><?php echo htmlspecialchars($voter['student_id']); ?></div>
                    </div>
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($voter['full_name'], 0, 1)); ?>
                    </div>
                    <a href="logout.php" class="logout-btn" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars(explode(' ', $voter['full_name'])[0]); ?>!</h1>
                <p class="welcome-subtitle">Stay updated with the latest elections and manage your voting participation.</p>
            </div>

            <!-- Profile Card -->
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($voter['full_name'], 0, 1)); ?>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($voter['full_name']); ?></h2>
                        <p><?php echo htmlspecialchars($voter['email']); ?></p>
                        <p><?php echo htmlspecialchars($voter['student_id']); ?></p>
                        <span class="status-badge status-active">
                            <i class="fas fa-check-circle"></i>
                            <?php echo ucfirst($voter['status']); ?>
                        </span>
                    </div>
                </div>

                <div class="profile-details">
                    <div class="detail-group">
                        <h4>Academic Information</h4>
                        <div class="detail-item">
                            <span class="detail-label">Program</span>
                            <span class="detail-value"><?php echo htmlspecialchars($voter['program']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Year</span>
                            <span class="detail-value"><?php echo htmlspecialchars($voter['year']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Faculty</span>
                            <span class="detail-value"><?php echo htmlspecialchars($voter['faculty']); ?></span>
                        </div>
                    </div>

                    <div class="detail-group">
                        <h4>Personal Information</h4>
                        <?php if ($voter['phone']): ?>
                        <div class="detail-item">
                            <span class="detail-label">Phone</span>
                            <span class="detail-value"><?php echo htmlspecialchars($voter['phone']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($voter['nationality']): ?>
                        <div class="detail-item">
                            <span class="detail-label">Nationality</span>
                            <span class="detail-value"><?php echo htmlspecialchars($voter['nationality']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="detail-item">
                            <span class="detail-label">Registration Date</span>
                            <span class="detail-value"><?php echo date('M d, Y', strtotime($voter['registration_date'])); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Last Login</span>
                            <span class="detail-value">
                                <?php echo $voter['last_login'] ? date('M d, Y H:i', strtotime($voter['last_login'])) : 'Never'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card success">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-vote-yea"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $voter['votes_cast']; ?></div>
                    <div class="stat-label">Elections Participated</div>
                </div>

                <div class="stat-card info">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-poll"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo count($eligible_elections); ?></div>
                    <div class="stat-label">Active Elections</div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo count($upcoming_elections); ?></div>
                    <div class="stat-label">Upcoming Elections</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $participation_rate; ?>%</div>
                    <div class="stat-label">Participation Rate</div>
                </div>
            </div>

            <!-- Participation Progress -->
            <div class="participation-progress">
                <div class="progress-header">
                    <h3 class="progress-title">Voting Participation</h3>
                    <span class="progress-percentage"><?php echo $participation_rate; ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $participation_rate; ?>%"></div>
                </div>
                <div class="progress-stats">
                    <span>Voted in <?php echo $voter['votes_cast']; ?> out of <?php echo $voter['eligible_elections']; ?> eligible elections</span>
                    <span><?php echo $voter['eligible_elections'] - $voter['votes_cast']; ?> remaining</span>
                </div>
            </div>

            <!-- Elections Grid -->
            <div class="elections-grid">
                <!-- Active Elections -->
                <div class="elections-section">
                    <div class="section-header">
                        <h3 class="section-title">Active Elections</h3>
                        <p class="section-subtitle">Elections you can vote in right now</p>
                    </div>
                    <div class="elections-list">
                        <?php if (empty($eligible_elections)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-vote-yea"></i>
                                </div>
                                <p>No active elections available at the moment.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($eligible_elections as $election): ?>
                            <div class="election-item">
                                <div class="election-header">
                                    <div>
                                        <h4 class="election-title"><?php echo htmlspecialchars($election['title']); ?></h4>
                                        <div class="election-meta">
                                            <div class="election-date">
                                                <i class="fas fa-clock"></i>
                                                Ends: <?php echo date('M d, Y H:i', strtotime($election['end_date'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="election-status status-active">Active</span>
                                </div>
                                <p class="election-description"><?php echo htmlspecialchars($election['description']); ?></p>
                                <div class="election-actions">
                                    <a href="#" class="btn btn-primary" onclick="alert('Voting functionality would be implemented here')">
                                        <i class="fas fa-vote-yea"></i>
                                        Vote Now
                                    </a>
                                    <a href="#" class="btn btn-secondary" onclick="alert('Election details would be shown here')">
                                        <i class="fas fa-info-circle"></i>
                                        View Details
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Upcoming Elections -->
                <div class="elections-section">
                    <div class="section-header">
                        <h3 class="section-title">Upcoming Elections</h3>
                        <p class="section-subtitle">Elections starting soon</p>
                    </div>
                    <div class="elections-list">
                        <?php if (empty($upcoming_elections)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <p>No upcoming elections scheduled.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($upcoming_elections as $election): ?>
                            <div class="election-item">
                                <div class="election-header">
                                    <div>
                                        <h4 class="election-title"><?php echo htmlspecialchars($election['title']); ?></h4>
                                        <div class="election-meta">
                                            <div class="election-date">
                                                <i class="fas fa-calendar"></i>
                                                Starts: <?php echo date('M d, Y H:i', strtotime($election['start_date'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="election-status status-upcoming">Upcoming</span>
                                </div>
                                <p class="election-description"><?php echo htmlspecialchars($election['description']); ?></p>
                                <div class="election-actions">
                                    <a href="#" class="btn btn-secondary" onclick="alert('Election details would be shown here')">
                                        <i class="fas fa-info-circle"></i>
                                        View Details
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Voting History -->
            <?php if (!empty($voted_elections)): ?>
            <div class="elections-section">
                <div class="section-header">
                    <h3 class="section-title">Voting History</h3>
                    <p class="section-subtitle">Elections you have participated in</p>
                </div>
                <div class="elections-list">
                    <?php foreach ($voted_elections as $election): ?>
                    <div class="election-item">
                        <div class="election-header">
                            <div>
                                <h4 class="election-title"><?php echo htmlspecialchars($election['title']); ?></h4>
                                <div class="election-meta">
                                    <div class="election-date">
                                        <i class="fas fa-check"></i>
                                        Voted: <?php echo date('M d, Y H:i', strtotime($election['voted_at'])); ?>
                                    </div>
                                </div>
                            </div>
                            <span class="election-status status-voted">Voted</span>
                        </div>
                        <p class="election-description"><?php echo htmlspecialchars($election['description']); ?></p>
                        <div class="election-actions">
                            <a href="#" class="btn btn-secondary" onclick="alert('Election results would be shown here')">
                                <i class="fas fa-chart-bar"></i>
                                View Results
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Show notification for active elections
        document.addEventListener('DOMContentLoaded', function() {
            const activeElections = <?php echo count($eligible_elections); ?>;
            if (activeElections > 0) {
                showNotification(`You have ${activeElections} active election(s) available for voting!`, 'info');
            }
        });

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            
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
            
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 0.625rem;">
                    <i class="fas ${iconMap[type]}" style="color: ${colorMap[type]};"></i>
                    <span>${message}</span>
                </div>
            `;
            
            notification.style.cssText = `
                position: fixed;
                top: 1.5rem;
                right: 1.5rem;
                background: white;
                padding: 0.875rem 1.25rem;
                border-radius: var(--radius);
                box-shadow: var(--shadow-lg);
                border-left: 4px solid ${colorMap[type]};
                z-index: 1001;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease;
                max-width: 400px;
                font-size: 0.875rem;
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
    </script>
</body>
</html>