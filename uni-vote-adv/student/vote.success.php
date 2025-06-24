<?php
// vote_success.php - Vote Confirmation Page
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

function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetch() : null;
}

function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

// Check if user is logged in
if (!isset($_SESSION['voter_id'])) {
    header("Location: student_login.php");
    exit();
}

// Get election ID from URL
$election_id = $_GET['election_id'] ?? 0;
if (!$election_id) {
    header("Location: dashboard.php");
    exit();
}

// Get voter information
$voter = fetchOne("SELECT * FROM voters WHERE id = ?", [$_SESSION['voter_id']]);
if (!$voter) {
    session_destroy();
    header("Location: student_login.php?error=session_expired");
    exit();
}

// Get election details
$election = fetchOne("SELECT * FROM elections WHERE id = ?", [$election_id]);
if (!$election) {
    header("Location: dashboard.php?error=election_not_found");
    exit();
}

// Verify that the voter has actually voted in this election
$vote_verification = fetchOne("
    SELECT v.voted_at, v.vote_hash, COUNT(*) as vote_count
    FROM votes v 
    WHERE v.voter_id = ? AND v.election_id = ?
    GROUP BY v.voted_at, v.vote_hash
", [$voter['id'], $election_id]);

if (!$vote_verification) {
    header("Location: dashboard.php?error=vote_not_found");
    exit();
}

// Get the candidates the voter selected
$voted_candidates = fetchAll("
    SELECT c.full_name, c.position, v.voted_at 
    FROM votes v
    JOIN candidates c ON v.candidate_id = c.id
    WHERE v.voter_id = ? AND v.election_id = ?
    ORDER BY c.position, c.full_name
", [$voter['id'], $election_id]);

// Clear any stored voting selections
if (isset($_SESSION['voting_selections'])) {
    unset($_SESSION['voting_selections']);
}

// Generate a unique confirmation number (for display only, not stored)
$confirmation_number = strtoupper(substr(md5($vote_verification['vote_hash']), 0, 8));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote Submitted Successfully - University Voting System</title>
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
            overflow-x: hidden;
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
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-primary);
        }

        .btn-outline:hover {
            background: var(--surface-hover);
        }

        /* Main Content */
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Success Animation */
        .success-animation {
            text-align: center;
            margin-bottom: 3rem;
            padding: 3rem 2rem;
        }

        .success-icon {
            width: 120px;
            height: 120px;
            margin: 0 auto 2rem;
            background: linear-gradient(135deg, var(--success), #059669);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            box-shadow: var(--shadow-lg);
            animation: successPulse 2s ease-in-out infinite alternate;
        }

        @keyframes successPulse {
            0% { transform: scale(1); box-shadow: var(--shadow-lg); }
            100% { transform: scale(1.05); box-shadow: var(--shadow-lg), 0 0 30px rgba(16, 185, 129, 0.3); }
        }

        .success-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .success-subtitle {
            font-size: 1.125rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }

        /* Confirmation Card */
        .confirmation-card {
            background: var(--surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border);
        }

        .confirmation-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .confirmation-icon {
            width: 3rem;
            height: 3rem;
            background: rgb(16 185 129 / 0.1);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--success);
            font-size: 1.25rem;
        }

        .confirmation-info h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .confirmation-info p {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .detail-item {
            padding: 1rem;
            background: var(--surface-hover);
            border-radius: var(--radius);
            border: 1px solid var(--border);
        }

        .detail-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        .detail-value {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        /* Voted Candidates */
        .voted-candidates {
            background: var(--surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border);
        }

        .candidates-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .candidates-icon {
            width: 2.5rem;
            height: 2.5rem;
            background: rgb(99 102 241 / 0.1);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1rem;
        }

        .candidates-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .candidate-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--surface-hover);
            border-radius: var(--radius);
            margin-bottom: 1rem;
            border: 1px solid var(--border);
        }

        .candidate-item:last-child {
            margin-bottom: 0;
        }

        .candidate-check {
            width: 2rem;
            height: 2rem;
            background: var(--success);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.875rem;
            flex-shrink: 0;
        }

        .candidate-info h4 {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .candidate-position {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        /* Security Notice */
        .security-notice {
            background: rgb(59 130 246 / 0.05);
            border: 1px solid rgb(59 130 246 / 0.2);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .security-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .security-icon {
            color: var(--info);
            font-size: 1.25rem;
        }

        .security-title {
            font-weight: 600;
            color: var(--text-primary);
        }

        .security-text {
            color: var(--text-secondary);
            font-size: 0.875rem;
            line-height: 1.6;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 2rem;
        }

        .action-buttons .btn {
            flex: 1;
            min-width: 200px;
            justify-content: center;
        }

        /* Confetti Animation */
        .confetti {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1000;
        }

        .confetti-piece {
            position: absolute;
            width: 10px;
            height: 10px;
            background: var(--primary);
            animation: confetti-fall 3s ease-out forwards;
        }

        @keyframes confetti-fall {
            0% {
                opacity: 1;
                transform: translateY(-100vh) rotate(0deg);
            }
            100% {
                opacity: 0;
                transform: translateY(100vh) rotate(720deg);
            }
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

            .success-title {
                font-size: 2rem;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-buttons .btn {
                min-width: auto;
            }

            .confirmation-header {
                flex-direction: column;
                text-align: center;
            }
        }

        /* Print Styles */
        @media print {
            .header, .action-buttons, .confetti {
                display: none !important;
            }
            
            body {
                background: white;
            }
            
            .confirmation-card, .voted-candidates, .security-notice {
                box-shadow: none;
                border: 1px solid #000;
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
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fas fa-home"></i>
                    Go to Dashboard
                </a>
                <a href="logout.php" class="btn btn-outline">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </nav>
        </div>
    </header>

    <!-- Confetti Container -->
    <div class="confetti" id="confetti"></div>

    <div class="container">
        <!-- Success Animation -->
        <div class="success-animation">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h1 class="success-title">Vote Submitted Successfully!</h1>
            <p class="success-subtitle">
                Thank you for participating in the democratic process. Your vote has been recorded securely.
            </p>
        </div>

        <!-- Confirmation Card -->
        <div class="confirmation-card">
            <div class="confirmation-header">
                <div class="confirmation-icon">
                    <i class="fas fa-certificate"></i>
                </div>
                <div class="confirmation-info">
                    <h3>Vote Confirmation</h3>
                    <p>Your vote has been successfully recorded and counted</p>
                </div>
            </div>

            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-label">Voter</div>
                    <div class="detail-value"><?php echo htmlspecialchars($voter['full_name']); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Student ID</div>
                    <div class="detail-value"><?php echo htmlspecialchars($voter['student_id']); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Election</div>
                    <div class="detail-value"><?php echo htmlspecialchars($election['title']); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Vote Time</div>
                    <div class="detail-value"><?php echo date('M d, Y \a\t g:i A', strtotime($vote_verification['voted_at'])); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Confirmation #</div>
                    <div class="detail-value" style="font-family: monospace; letter-spacing: 0.1em;">
                        <?php echo $confirmation_number; ?>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Votes Cast</div>
                    <div class="detail-value"><?php echo count($voted_candidates); ?> candidate<?php echo count($voted_candidates) > 1 ? 's' : ''; ?></div>
                </div>
            </div>
        </div>

        <!-- Voted Candidates -->
        <div class="voted-candidates">
            <div class="candidates-header">
                <div class="candidates-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3 class="candidates-title">Your Selections</h3>
            </div>

            <?php foreach ($voted_candidates as $candidate): ?>
                <div class="candidate-item">
                    <div class="candidate-check">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="candidate-info">
                        <h4><?php echo htmlspecialchars($candidate['full_name']); ?></h4>
                        <div class="candidate-position"><?php echo htmlspecialchars($candidate['position']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Security Notice -->
        <div class="security-notice">
            <div class="security-header">
                <i class="fas fa-shield-alt security-icon"></i>
                <h4 class="security-title">Security & Privacy</h4>
            </div>
            <div class="security-text">
                <p>
                    <strong>Your vote is secure and anonymous.</strong> While we can confirm that you have voted, 
                    your specific choices are encrypted and cannot be traced back to you. The confirmation number 
                    above is for your records only and does not reveal your voting choices.
                </p>
                <br>
                <p>
                    <strong>Important:</strong> You cannot vote again in this election. If you believe there was 
                    an error with your vote, please contact the election administrators immediately.
                </p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="dashboard.php" class="btn btn-primary">
                <i class="fas fa-home"></i>
                Return to Dashboard
            </a>
            <button onclick="window.print()" class="btn btn-outline">
                <i class="fas fa-print"></i>
                Print Confirmation
            </button>
            <a href="election_results.php?id=<?php echo $election_id; ?>" class="btn btn-outline">
                <i class="fas fa-chart-bar"></i>
                View Results
            </a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Create confetti animation
            createConfetti();
            
            // Clear any stored voting data
            clearVotingData();
            
            // Auto-hide after 10 seconds
            setTimeout(hideConfetti, 10000);
            
            // Show success notification
            showSuccessMessage();
        });

        function createConfetti() {
            const confettiContainer = document.getElementById('confetti');
            const colors = ['#4f46e5', '#7c3aed', '#ec4899', '#10b981', '#f59e0b'];
            const confettiCount = 50;

            for (let i = 0; i < confettiCount; i++) {
                createConfettiPiece(confettiContainer, colors);
            }
        }

        function createConfettiPiece(container, colors) {
            const confetti = document.createElement('div');
            confetti.className = 'confetti-piece';
            
            // Random properties
            const color = colors[Math.floor(Math.random() * colors.length)];
            const left = Math.random() * 100;
            const animationDelay = Math.random() * 2;
            const animationDuration = 3 + Math.random() * 2;
            
            confetti.style.backgroundColor = color;
            confetti.style.left = left + '%';
            confetti.style.animationDelay = animationDelay + 's';
            confetti.style.animationDuration = animationDuration + 's';
            
            container.appendChild(confetti);
            
            // Remove after animation
            setTimeout(() => {
                if (confetti.parentNode) {
                    confetti.parentNode.removeChild(confetti);
                }
            }, (animationDuration + animationDelay) * 1000);
        }

        function hideConfetti() {
            const confettiContainer = document.getElementById('confetti');
            confettiContainer.style.opacity = '0';
            setTimeout(() => {
                confettiContainer.style.display = 'none';
            }, 1000);
        }

        function clearVotingData() {
            // Clear any stored voting selections
            if (typeof(Storage) !== "undefined") {
                sessionStorage.removeItem('voting_selections');
                localStorage.removeItem('voting_selections');
            }
        }

        function showSuccessMessage() {
            // Create a temporary success message
            const message = document.createElement('div');
            message.style.cssText = `
                position: fixed;
                top: 2rem;
                right: 2rem;
                background: var(--success);
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
            
            message.innerHTML = `
                <i class="fas fa-check-circle"></i>
                <span>Your vote has been successfully recorded!</span>
            `;
            
            document.body.appendChild(message);
            
            // Animate in
            setTimeout(() => {
                message.style.opacity = '1';
                message.style.transform = 'translateX(0)';
            }, 500);
            
            // Animate out
            setTimeout(() => {
                message.style.opacity = '0';
                message.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (message.parentNode) {
                        message.parentNode.removeChild(message);
                    }
                }, 300);
            }, 5000);
        }

        // Prevent back button after voting
        history.pushState(null, '', location.href);
        window.addEventListener('popstate', function() {
            history.pushState(null, '', location.href);
        });

        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'p':
                        e.preventDefault();
                        window.print();
                        break;
                    case 'h':
                        e.preventDefault();
                        window.location.href = 'dashboard.php';
                        break;
                }
            }
            
            // ESC to go to dashboard
            if (e.key === 'Escape') {
                window.location.href = 'dashboard.php';
            }
        });

        // Track page visibility for analytics
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                console.log('User left vote confirmation page');
            } else {
                console.log('User returned to vote confirmation page');
            }
        });

        // Auto-redirect after 30 seconds (optional)
        let redirectTimer = 30;
        function updateRedirectTimer() {
            redirectTimer--;
            if (redirectTimer <= 0) {
                window.location.href = 'dashboard.php';
            }
        }
        
        // Uncomment to enable auto-redirect
        // setInterval(updateRedirectTimer, 1000);

        // Add smooth scroll for any anchor links
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

        // Success analytics (optional)
        function trackVoteSuccess() {
            console.log('Vote success page loaded');
            console.log('Election ID:', <?php echo json_encode($election_id); ?>);
            console.log('Voter ID:', <?php echo json_encode($voter['id']); ?>);
            console.log('Vote time:', <?php echo json_encode($vote_verification['voted_at']); ?>);
            console.log('Candidates voted for:', <?php echo json_encode(array_column($voted_candidates, 'full_name')); ?>);
        }

        trackVoteSuccess();

        // Social sharing (optional)
        function shareVoteSuccess() {
            const text = `I just voted in the ${<?php echo json_encode($election['title']); ?>}! Exercise your democratic right and vote too. 🗳️ #Vote2024 #Democracy`;
            
            if (navigator.share) {
                navigator.share({
                    title: 'I Voted!',
                    text: text,
                    url: window.location.origin
                });
            } else {
                // Fallback to copy to clipboard
                navigator.clipboard.writeText(text).then(() => {
                    alert('Vote message copied to clipboard!');
                });
            }
        }

        // Add share button (optional)
        function addShareButton() {
            const actionButtons = document.querySelector('.action-buttons');
            const shareBtn = document.createElement('button');
            shareBtn.className = 'btn btn-outline';
            shareBtn.innerHTML = '<i class="fas fa-share"></i> Share';
            shareBtn.onclick = shareVoteSuccess;
            actionButtons.appendChild(shareBtn);
        }

        // Uncomment to enable sharing
        // addShareButton();

        console.log('Vote success page initialized');
        console.log('Confirmation number:', <?php echo json_encode($confirmation_number); ?>);
    </script>
</body>
</html>