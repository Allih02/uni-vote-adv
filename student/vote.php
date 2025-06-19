<?php
// vote.php - Student Voting Interface
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

function logAuditAction($user_type, $user_id, $action, $entity_type, $entity_id, $new_values) {
    $query = "INSERT INTO audit_logs (user_type, user_id, action, entity_type, entity_id, new_values, ip_address, user_agent)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params = [
        $user_type,
        $user_id,
        $action,
        $entity_type,
        $entity_id,
        json_encode($new_values),
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ];
    
    executeQuery($query, $params);
}

// Check if user is logged in
if (!isset($_SESSION['voter_id'])) {
    header("Location: student_login.php");
    exit();
}

// Get election ID from URL
$election_id = $_GET['election_id'] ?? 0;
if (!$election_id) {
    header("Location: dashboard.php?error=no_election_selected");
    exit();
}

// Get voter information
$voter = fetchOne("SELECT * FROM voters WHERE id = ?", [$_SESSION['voter_id']]);
if (!$voter) {
    session_destroy();
    header("Location: student_login.php?error=session_expired");
    exit();
}

// Get election details and verify eligibility
$election = fetchOne("
    SELECT * FROM elections 
    WHERE id = ? 
    AND status = 'active' 
    AND start_date <= NOW() 
    AND end_date >= NOW()
    AND JSON_CONTAINS(eligible_years, ?)
    AND JSON_CONTAINS(eligible_faculties, ?)
", [
    $election_id,
    '"' . $voter['year'] . '"',
    '"' . $voter['faculty'] . '"'
]);

if (!$election) {
    header("Location: dashboard.php?error=election_not_available");
    exit();
}

// Check if voter has already voted in this election
$existing_vote = fetchOne("SELECT * FROM votes WHERE voter_id = ? AND election_id = ?", [$voter['id'], $election_id]);
if ($existing_vote) {
    header("Location: dashboard.php?error=already_voted");
    exit();
}

// Get election positions
$positions = fetchAll("
    SELECT * FROM positions 
    WHERE election_id = ? 
    ORDER BY display_order, title
", [$election_id]);

// Get candidates grouped by position
$candidates_by_position = [];
if (!empty($positions)) {
    foreach ($positions as $position) {
        $candidates_by_position[$position['title']] = fetchAll("
            SELECT c.*, v.profile_image 
            FROM candidates c
            LEFT JOIN voters v ON c.voter_id = v.id
            WHERE c.election_id = ? 
            AND c.position = ? 
            AND c.status = 'active'
            ORDER BY c.full_name
        ", [$election_id, $position['title']]);
    }
} else {
    // If no positions defined, get all candidates
    $candidates_by_position['General'] = fetchAll("
        SELECT c.*, v.profile_image 
        FROM candidates c
        LEFT JOIN voters v ON c.voter_id = v.id
        WHERE c.election_id = ? 
        AND c.status = 'active'
        ORDER BY c.full_name
    ", [$election_id]);
}

// Handle vote submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_vote'])) {
    $selected_candidates = $_POST['candidates'] ?? [];
    
    if (empty($selected_candidates)) {
        $error_message = "Please select at least one candidate to vote for.";
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            $db->beginTransaction();
            
            // Verify election is still active and voter hasn't voted
            $election_check = fetchOne("
                SELECT * FROM elections 
                WHERE id = ? AND status = 'active' 
                AND start_date <= NOW() AND end_date >= NOW()
            ", [$election_id]);
            
            $vote_check = fetchOne("SELECT * FROM votes WHERE voter_id = ? AND election_id = ?", [$voter['id'], $election_id]);
            
            if (!$election_check) {
                throw new Exception("Election is no longer active");
            }
            
            if ($vote_check) {
                throw new Exception("You have already voted in this election");
            }
            
            // Insert votes for selected candidates
            $vote_data = [];
            foreach ($selected_candidates as $candidate_id) {
                // Verify candidate belongs to this election
                $candidate = fetchOne("SELECT * FROM candidates WHERE id = ? AND election_id = ? AND status = 'active'", [$candidate_id, $election_id]);
                
                if ($candidate) {
                    // Generate vote hash for anonymity
                    $vote_hash = hash('sha256', $voter['id'] . $election_id . $candidate_id . time() . rand());
                    
                    // Insert vote
                    $vote_query = "INSERT INTO votes (voter_id, election_id, candidate_id, vote_hash, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)";
                    executeQuery($vote_query, [
                        $voter['id'],
                        $election_id,
                        $candidate_id,
                        $vote_hash,
                        $_SERVER['REMOTE_ADDR'] ?? null,
                        $_SERVER['HTTP_USER_AGENT'] ?? null
                    ]);
                    
                    // Update candidate vote count
                    executeQuery("UPDATE candidates SET vote_count = vote_count + 1 WHERE id = ?", [$candidate_id]);
                    
                    $vote_data[] = [
                        'candidate_id' => $candidate_id,
                        'candidate_name' => $candidate['full_name'],
                        'position' => $candidate['position']
                    ];
                }
            }
            
            // Log audit action
            logAuditAction('voter', $voter['id'], 'VOTE', 'election', $election_id, [
                'election_title' => $election['title'],
                'candidates_voted' => $vote_data
            ]);
            
            $db->commit();
            
            // Redirect to success page
            header("Location: vote_success.php?election_id=" . $election_id);
            exit();
            
        } catch (Exception $e) {
            $db->rollback();
            $error_message = "An error occurred while submitting your vote: " . $e->getMessage();
            error_log("Voting error for voter " . $voter['id'] . ": " . $e->getMessage());
        }
    }
}

// Calculate time remaining
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

$time_remaining = getTimeRemaining($election['end_date']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote - <?php echo htmlspecialchars($election['title']); ?></title>
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

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-primary);
        }

        .btn-outline:hover {
            background: var(--surface-hover);
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-primary:disabled {
            background: var(--text-muted);
            cursor: not-allowed;
        }

        /* Election Header */
        .election-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
            box-shadow: var(--shadow-md);
        }

        .election-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .election-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            margin-top: 1rem;
            opacity: 0.9;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
            border: 1px solid transparent;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-error {
            background: rgb(239 68 68 / 0.1);
            border-color: rgb(239 68 68 / 0.2);
            color: #dc2626;
        }

        .alert-warning {
            background: rgb(245 158 11 / 0.1);
            border-color: rgb(245 158 11 / 0.2);
            color: #d97706;
        }

        .alert-info {
            background: rgb(59 130 246 / 0.1);
            border-color: rgb(59 130 246 / 0.2);
            color: #2563eb;
        }

        /* Voting Form */
        .voting-form {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
        }

        .position-section {
            margin-bottom: 3rem;
        }

        .position-section:last-child {
            margin-bottom: 0;
        }

        .position-header {
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            border: 1px solid var(--border);
        }

        .position-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .position-subtitle {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        /* Candidate Cards */
        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .candidate-card {
            background: var(--surface);
            border: 2px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .candidate-card:hover {
            border-color: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .candidate-card.selected {
            border-color: var(--primary);
            background: rgb(99 102 241 / 0.05);
        }

        .candidate-card.selected::after {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--primary);
            color: white;
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
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

        /* Hidden Radio Inputs */
        .candidate-radio {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        /* Vote Summary */
        .vote-summary {
            background: rgb(59 130 246 / 0.05);
            border: 1px solid rgb(59 130 246 / 0.2);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin: 2rem 0;
            display: none;
        }

        .vote-summary.show {
            display: block;
        }

        .summary-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .selected-candidate {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: var(--surface);
            border-radius: var(--radius);
            margin-bottom: 0.5rem;
            border: 1px solid var(--border);
        }

        .selected-candidate:last-child {
            margin-bottom: 0;
        }

        /* Submit Section */
        .submit-section {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 2rem;
            text-align: center;
            margin-top: 2rem;
        }

        .submit-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .submit-subtitle {
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }

        .submit-btn {
            background: var(--primary);
            color: white;
            padding: 1rem 3rem;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
        }

        .submit-btn:hover:not(:disabled) {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .submit-btn:disabled {
            background: var(--text-muted);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Loading State */
        .loading {
            display: none;
            align-items: center;
            gap: 0.5rem;
        }

        .spinner {
            width: 1rem;
            height: 1rem;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
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
                font-size: 1.5rem;
            }

            .election-meta {
                flex-direction: column;
                gap: 0.5rem;
            }

            .candidates-grid {
                grid-template-columns: 1fr;
            }

            .candidate-header {
                flex-direction: column;
                text-align: center;
            }

            .submit-btn {
                width: 100%;
                justify-content: center;
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
        }

        .time-warning.show {
            display: block;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }

        /* Accessibility */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        /* Focus States */
        .candidate-card:focus-within {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        /* Print Styles */
        @media print {
            .header, .submit-section, .time-warning {
                display: none !important;
            }
            
            .candidate-card {
                break-inside: avoid;
            }
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
        <!-- Election Header -->
        <div class="election-header">
            <h1 class="election-title"><?php echo htmlspecialchars($election['title']); ?></h1>
            <p><?php echo htmlspecialchars($election['description']); ?></p>
            
            <div class="election-meta">
                <div class="meta-item">
                    <i class="fas fa-user"></i>
                    <span>Voting as: <?php echo htmlspecialchars($voter['full_name']); ?></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-graduation-cap"></i>
                    <span><?php echo htmlspecialchars($voter['program']); ?> - <?php echo htmlspecialchars($voter['year']); ?></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-building"></i>
                    <span><?php echo htmlspecialchars($voter['faculty']); ?></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-clock"></i>
                    <span><?php echo $time_remaining; ?></span>
                </div>
            </div>
        </div>

        <!-- Error Messages -->
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Important Notice -->
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>Important:</strong> You can only vote once in this election. Please review your choices carefully before submitting.
        </div>

        <!-- Voting Form -->
        <form method="POST" id="votingForm" class="voting-form">
            <?php foreach ($candidates_by_position as $position => $candidates): ?>
                <?php if (empty($candidates)) continue; ?>
                
                <div class="position-section">
                    <div class="position-header">
                        <h2 class="position-title"><?php echo htmlspecialchars($position); ?></h2>
                        <p class="position-subtitle">Select one candidate for this position</p>
                    </div>
                    
                    <div class="candidates-grid">
                        <?php foreach ($candidates as $candidate): ?>
                            <div class="candidate-card" data-candidate-id="<?php echo $candidate['id']; ?>" data-position="<?php echo htmlspecialchars($position); ?>">
                                <input type="radio" 
                                       name="candidates[<?php echo htmlspecialchars($position); ?>]" 
                                       value="<?php echo $candidate['id']; ?>" 
                                       id="candidate_<?php echo $candidate['id']; ?>"
                                       class="candidate-radio">
                                
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
                                                <div>ID: <?php echo htmlspecialchars($candidate['student_id']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($candidate['program']): ?>
                                                <div><?php echo htmlspecialchars($candidate['program']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($candidate['year']): ?>
                                                <div><?php echo htmlspecialchars($candidate['year']); ?></div>
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
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Vote Summary -->
            <div class="vote-summary" id="voteSummary">
                <h3 class="summary-title">Your Selections</h3>
                <div id="selectedCandidates"></div>
            </div>

            <!-- Submit Section -->
            <div class="submit-section">
                <h3 class="submit-title">Submit Your Vote</h3>
                <p class="submit-subtitle">
                    Please review your selections above. Once submitted, your vote cannot be changed.
                </p>
                
                <button type="submit" name="submit_vote" class="submit-btn" id="submitBtn" disabled>
                    <span class="submit-text">
                        <i class="fas fa-vote-yea"></i>
                        Cast My Vote
                    </span>
                    <span class="loading" id="submitLoading">
                        <div class="spinner"></div>
                        Submitting...
                    </span>
                </button>
                
                <div style="margin-top: 1rem; font-size: 0.875rem; color: var(--text-secondary);">
                    <i class="fas fa-shield-alt"></i>
                    Your vote is anonymous and secure
                </div>
            </div>
        </form>
    </div>

    <script>
        // Global variables
        let selectedCandidates = {};
        let timeRemaining = new Date('<?php echo $election['end_date']; ?>').getTime();
        
        document.addEventListener('DOMContentLoaded', function() {
            initializeVoting();
            startTimeWarning();
            
            // Add keyboard navigation
            document.addEventListener('keydown', handleKeyboardNavigation);
        });

        function initializeVoting() {
            // Add click handlers to candidate cards
            document.querySelectorAll('.candidate-card').forEach(card => {
                card.addEventListener('click', function() {
                    const candidateId = this.dataset.candidateId;
                    const position = this.dataset.position;
                    const radio = this.querySelector('.candidate-radio');
                    
                    // Clear other selections for this position
                    document.querySelectorAll(`input[name="candidates[${position}]"]`).forEach(r => {
                        r.checked = false;
                        r.closest('.candidate-card').classList.remove('selected');
                    });
                    
                    // Select this candidate
                    radio.checked = true;
                    this.classList.add('selected');
                    
                    // Update selections
                    selectedCandidates[position] = {
                        id: candidateId,
                        name: this.querySelector('.candidate-info h3').textContent,
                        position: position
                    };
                    
                    updateVoteSummary();
                    updateSubmitButton();
                });
            });

            // Add change handlers to radio buttons
            document.querySelectorAll('.candidate-radio').forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.checked) {
                        this.closest('.candidate-card').click();
                    }
                });
            });

            // Form submission handler
            document.getElementById('votingForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (Object.keys(selectedCandidates).length === 0) {
                    showAlert('Please select at least one candidate before submitting your vote.', 'error');
                    return;
                }
                
                // Show confirmation dialog
                if (confirmVoteSubmission()) {
                    submitVote();
                }
            });
        }

        function updateVoteSummary() {
            const summaryDiv = document.getElementById('voteSummary');
            const candidatesDiv = document.getElementById('selectedCandidates');
            
            if (Object.keys(selectedCandidates).length === 0) {
                summaryDiv.classList.remove('show');
                return;
            }
            
            let html = '';
            Object.values(selectedCandidates).forEach(candidate => {
                html += `
                    <div class="selected-candidate">
                        <i class="fas fa-check-circle" style="color: var(--success);"></i>
                        <div>
                            <strong>${escapeHtml(candidate.name)}</strong>
                            <div style="font-size: 0.875rem; color: var(--text-secondary);">
                                ${escapeHtml(candidate.position)}
                            </div>
                        </div>
                    </div>
                `;
            });
            
            candidatesDiv.innerHTML = html;
            summaryDiv.classList.add('show');
        }

        function updateSubmitButton() {
            const submitBtn = document.getElementById('submitBtn');
            const hasSelections = Object.keys(selectedCandidates).length > 0;
            
            submitBtn.disabled = !hasSelections;
            
            if (hasSelections) {
                submitBtn.classList.add('enabled');
                submitBtn.style.background = 'var(--primary)';
            } else {
                submitBtn.classList.remove('enabled');
                submitBtn.style.background = 'var(--text-muted)';
            }
        }

        function confirmVoteSubmission() {
            const candidateNames = Object.values(selectedCandidates).map(c => c.name).join(', ');
            
            return confirm(
                `Are you sure you want to cast your vote for: ${candidateNames}?\n\n` +
                'This action cannot be undone. You will not be able to vote again in this election.'
            );
        }

        function submitVote() {
            const submitBtn = document.getElementById('submitBtn');
            const submitText = submitBtn.querySelector('.submit-text');
            const submitLoading = document.getElementById('submitLoading');
            
            // Show loading state
            submitBtn.disabled = true;
            submitText.style.display = 'none';
            submitLoading.style.display = 'flex';
            
            // Convert selectedCandidates to form format
            const formData = new FormData();
            formData.append('submit_vote', '1');
            
            Object.values(selectedCandidates).forEach(candidate => {
                formData.append('candidates[]', candidate.id);
            });
            
            // Submit via AJAX for better UX (optional)
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                } else {
                    return response.text();
                }
            })
            .then(html => {
                if (html) {
                    // If there's an error, reload the page to show it
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error submitting vote:', error);
                showAlert('An error occurred while submitting your vote. Please try again.', 'error');
                
                // Reset button state
                submitBtn.disabled = false;
                submitText.style.display = 'flex';
                submitLoading.style.display = 'none';
            });
        }

        function startTimeWarning() {
            const warningDiv = document.getElementById('timeWarning');
            const warningText = document.getElementById('timeWarningText');
            
            function updateTimeWarning() {
                const now = new Date().getTime();
                const distance = timeRemaining - now;
                
                if (distance < 0) {
                    // Election ended
                    showAlert('This election has ended. You can no longer vote.', 'error');
                    document.getElementById('submitBtn').disabled = true;
                    return;
                }
                
                const hours = Math.floor(distance / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                
                // Show warning if less than 1 hour remaining
                if (distance < 3600000) { // 1 hour
                    warningText.textContent = `Election ends in ${hours}h ${minutes}m`;
                    warningDiv.classList.add('show');
                } else if (distance < 1800000) { // 30 minutes
                    warningText.textContent = `Only ${minutes} minutes left to vote!`;
                    warningDiv.style.background = 'var(--error)';
                    warningDiv.classList.add('show');
                }
            }
            
            // Update immediately and then every minute
            updateTimeWarning();
            setInterval(updateTimeWarning, 60000);
        }

        function handleKeyboardNavigation(e) {
            // Handle keyboard navigation for accessibility
            if (e.key === 'Enter' || e.key === ' ') {
                const focused = document.activeElement;
                if (focused.classList.contains('candidate-card')) {
                    e.preventDefault();
                    focused.click();
                }
            }
        }

        function showAlert(message, type = 'info') {
            // Create and show an alert
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            
            const icon = type === 'error' ? 'fa-exclamation-circle' : 
                        type === 'success' ? 'fa-check-circle' : 'fa-info-circle';
            
            alert.innerHTML = `
                <i class="fas ${icon}"></i>
                ${escapeHtml(message)}
            `;
            
            // Insert at top of container
            const container = document.querySelector('.container');
            container.insertBefore(alert, container.firstChild);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 5000);
            
            // Scroll to top to show alert
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Prevent accidental page reload
        window.addEventListener('beforeunload', function(e) {
            if (Object.keys(selectedCandidates).length > 0) {
                e.preventDefault();
                e.returnValue = 'You have unsaved selections. Are you sure you want to leave?';
                return e.returnValue;
            }
        });

        // Auto-save selections to sessionStorage
        function saveSelections() {
            sessionStorage.setItem('voting_selections', JSON.stringify(selectedCandidates));
        }

        function loadSelections() {
            const saved = sessionStorage.getItem('voting_selections');
            if (saved) {
                try {
                    const selections = JSON.parse(saved);
                    Object.entries(selections).forEach(([position, candidate]) => {
                        const radio = document.querySelector(`input[value="${candidate.id}"]`);
                        if (radio) {
                            radio.click();
                        }
                    });
                } catch (e) {
                    console.error('Error loading saved selections:', e);
                }
            }
        }

        // Load saved selections on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadSelections();
        });

        // Save selections when they change
        function updateVoteSummary() {
            const summaryDiv = document.getElementById('voteSummary');
            const candidatesDiv = document.getElementById('selectedCandidates');
            
            if (Object.keys(selectedCandidates).length === 0) {
                summaryDiv.classList.remove('show');
                return;
            }
            
            let html = '';
            Object.values(selectedCandidates).forEach(candidate => {
                html += `
                    <div class="selected-candidate">
                        <i class="fas fa-check-circle" style="color: var(--success);"></i>
                        <div>
                            <strong>${escapeHtml(candidate.name)}</strong>
                            <div style="font-size: 0.875rem; color: var(--text-secondary);">
                                ${escapeHtml(candidate.position)}
                            </div>
                        </div>
                    </div>
                `;
            });
            
            candidatesDiv.innerHTML = html;
            summaryDiv.classList.add('show');
            
            // Save selections
            saveSelections();
        }

        // Print functionality
        function printBallot() {
            window.print();
        }

        // Add print button (optional)
        document.addEventListener('DOMContentLoaded', function() {
            const headerNav = document.querySelector('.header-nav');
            const printBtn = document.createElement('button');
            printBtn.className = 'btn btn-outline';
            printBtn.innerHTML = '<i class="fas fa-print"></i> Print Ballot';
            printBtn.onclick = printBallot;
            headerNav.insertBefore(printBtn, headerNav.lastElementChild);
        });

        console.log('Voting interface initialized');
        console.log('Election ID:', <?php echo $election_id; ?>);
        console.log('Available positions:', <?php echo json_encode(array_keys($candidates_by_position)); ?>);
    </script>
</body>
</html>