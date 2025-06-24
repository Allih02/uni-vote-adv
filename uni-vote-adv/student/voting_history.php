<?php
// voting_history.php - Student Voting History
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
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                )
            );
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// Check if user is logged in
if (!isset($_SESSION['voter_id'])) {
    header("Location: student_login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get voter's voting history
$stmt = $db->prepare("
    SELECT e.*, v.voted_at, v.id as vote_id
    FROM elections e
    INNER JOIN votes v ON e.id = v.election_id
    WHERE v.voter_id = :voter_id
    ORDER BY v.voted_at DESC
");
$stmt->execute(['voter_id' => $_SESSION['voter_id']]);
$voting_history = $stmt->fetchAll();

// Get voter info
$stmt = $db->prepare("SELECT * FROM voters WHERE id = :id");
$stmt->execute(['id' => $_SESSION['voter_id']]);
$voter = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting History - University Voting System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #3730a3;
            --secondary: #7c3aed;
            --background: #f8fafc;
            --surface: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border: #e2e8f0;
            --success: #10b981;
            --info: #3b82f6;
            --radius: 0.75rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--background);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .header {
            background: var(--surface);
            padding: 2rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            margin-bottom: 1rem;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .history-grid {
            display: grid;
            gap: 1.5rem;
        }

        .history-item {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: 0 2px 4px rgb(0 0 0 / 0.05);
            transition: all 0.2s;
        }

        .history-item:hover {
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            transform: translateY(-1px);
        }

        .history-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 1rem;
            gap: 1rem;
        }

        .history-title {
            font-weight: 600;
            font-size: 1.125rem;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .history-meta {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .meta-item {
            font-size: 0.875rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            background: #ecfdf5;
            color: #065f46;
        }

        .description {
            color: var(--text-secondary);
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.5rem 1rem;
            border-radius: var(--radius);
            font-weight: 500;
            font-size: 0.875rem;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-secondary {
            background: var(--surface);
            color: var(--text-primary);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: #f1f5f9;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .stats-summary {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
            border: 1px solid var(--border);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 0.5rem;
            }

            .history-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>

        <div class="header">
            <h1><i class="fas fa-history"></i> Voting History</h1>
            <p>Complete record of your participation in university elections</p>
        </div>

        <div class="stats-summary">
            <h3 style="margin-bottom: 1rem;">Participation Summary</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value"><?php echo count($voting_history); ?></div>
                    <div class="stat-label">Total Votes</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo date('Y'); ?></div>
                    <div class="stat-label">Active Year</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo count($voting_history) > 0 ? '100%' : '0%'; ?></div>
                    <div class="stat-label">Completion Rate</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $voter['faculty']; ?></div>
                    <div class="stat-label">Faculty</div>
                </div>
            </div>
        </div>

        <div class="history-grid">
            <?php if (empty($voting_history)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-vote-yea"></i>
                    </div>
                    <h3>No Voting History</h3>
                    <p>You haven't participated in any elections yet.</p>
                    <a href="dashboard.php" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fas fa-poll"></i>
                        View Available Elections
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($voting_history as $history): ?>
                <div class="history-item">
                    <div class="history-header">
                        <div style="flex: 1;">
                            <h3 class="history-title"><?php echo htmlspecialchars($history['title']); ?></h3>
                            <div class="history-meta">
                                <div class="meta-item">
                                    <i class="fas fa-calendar"></i>
                                    Voted: <?php echo date('M d, Y H:i', strtotime($history['voted_at'])); ?>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-clock"></i>
                                    Election Period: <?php echo date('M d', strtotime($history['start_date'])); ?> - <?php echo date('M d, Y', strtotime($history['end_date'])); ?>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-building"></i>
                                    <?php echo htmlspecialchars($history['type']); ?>
                                </div>
                            </div>
                        </div>
                        <span class="status-badge">Completed</span>
                    </div>
                    
                    <p class="description"><?php echo htmlspecialchars($history['description']); ?></p>
                    
                    <div class="actions">
                        <a href="election_results.php?id=<?php echo $history['id']; ?>" class="btn btn-secondary">
                            <i class="fas fa-chart-bar"></i>
                            View Results
                        </a>
                        <a href="election_details.php?id=<?php echo $history['id']; ?>" class="btn btn-secondary">
                            <i class="fas fa-info-circle"></i>
                            Election Details
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>