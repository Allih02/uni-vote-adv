<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Database connection
$host = 'localhost';
$db = 'university_voting_system';
$user = 'root';
$pass = ''; // Replace with your MySQL root password if any

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get user info from database
try {
    $studentId = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT username, full_name, program FROM users WHERE id = ?");
    $stmt->execute([$studentId]);
    $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userInfo) {
        // Fallback if user not found
        $userInfo = [
            'username' => 'User',
            'full_name' => 'Student',
            'program' => 'University Program'
        ];
    }
    
} catch (PDOException $e) {
    // Fallback data if there's an error
    $userInfo = [
        'username' => 'User',
        'full_name' => 'Student',
        'program' => 'University Program'
    ];
}

// Fetch election details (if needed)
try {
    $stmt = $pdo->prepare("SELECT title, start_date, end_date FROM elections WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $electionInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$electionInfo) {
        // Fallback data
        $electionInfo = [
            'title' => 'Student Body Elections',
            'start_date' => date('Y-m-d', strtotime('-2 days')),
            'end_date' => date('Y-m-d', strtotime('+5 days'))
        ];
    }
} catch (PDOException $e) {
    // Fallback data
    $electionInfo = [
        'title' => 'Student Body Elections',
        'start_date' => date('Y-m-d', strtotime('-2 days')),
        'end_date' => date('Y-m-d', strtotime('+5 days'))
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Guidelines - Student Elections</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4F46E5;
            --primary-dark: #4338CA;
            --primary-light: #C7D2FE;
            --secondary: #8B5CF6;
            --accent: #EC4899;
            --background: #F9FAFB;
            --foreground: #111827;
            --muted: #6B7280;
            --card: #FFFFFF;
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
            --heading-font: 'Poppins', sans-serif;
            --body-font: 'Inter', sans-serif;
        }

        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--background);
            min-height: 100vh;
            font-family: var(--body-font);
            color: var(--foreground);
            overflow-x: hidden;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 0;
            background-color: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .logo {
            font-family: var(--heading-font);
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo span {
            color: var(--accent);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1rem;
        }

        .user-details {
            display: none;
        }
        
        .user-details h3 {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--foreground);
        }

        .user-details p {
            font-size: 0.75rem;
            color: var(--muted);
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            color: var(--muted);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .back-btn:hover {
            color: var(--foreground);
            background: rgba(0, 0, 0, 0.03);
        }

        .hero-section {
            padding: 3rem 0;
            text-align: center;
            background: linear-gradient(135deg, #EEF2FF 0%, #F3E8FF 100%);
            border-radius: 20px;
            margin: 2rem 0;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.1) 0%, rgba(99, 102, 241, 0) 70%);
            animation: pulse 15s ease-in-out infinite alternate;
        }

        .hero-section h1 {
            font-family: var(--heading-font);
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
            position: relative;
        }

        .hero-section p {
            color: var(--muted);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
            position: relative;
        }

        .guidelines-container {
            background: var(--card);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 3rem;
        }

        .guidelines-section {
            margin-bottom: 2.5rem;
        }

        .guidelines-section:last-child {
            margin-bottom: 0;
        }

        .guidelines-section h2 {
            font-family: var(--heading-font);
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--primary);
            position: relative;
            padding-bottom: 0.5rem;
        }

        .guidelines-section h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 3px;
        }

        .guidelines-section h3 {
            font-family: var(--heading-font);
            font-size: 1.2rem;
            margin: 1.5rem 0 0.75rem;
            color: var(--foreground);
        }

        .guidelines-section p {
            margin-bottom: 1rem;
            line-height: 1.6;
            color: var(--muted);
        }

        .guidelines-section ul, .guidelines-section ol {
            margin-bottom: 1.5rem;
            padding-left: 1.5rem;
        }

        .guidelines-section li {
            margin-bottom: 0.75rem;
            line-height: 1.6;
            color: var(--muted);
        }

        .guidelines-section strong {
            color: var(--foreground);
            font-weight: 600;
        }

        .tag {
            display: inline-block;
            background: var(--primary-light);
            color: var(--primary-dark);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .important-notice {
            background: #FEF2F2;
            border-left: 4px solid #EF4444;
            padding: 1rem 1.5rem;
            border-radius: 0 8px 8px 0;
            margin: 1.5rem 0;
        }

        .important-notice p {
            color: #B91C1C;
            margin-bottom: 0;
        }

        .tip-box {
            background: #ECFDF5;
            border-left: 4px solid #10B981;
            padding: 1rem 1.5rem;
            border-radius: 0 8px 8px 0;
            margin: 1.5rem 0;
        }

        .tip-box p {
            color: #047857;
            margin-bottom: 0;
        }

        .timeline {
            margin: 2rem 0;
            position: relative;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 3px;
            height: 100%;
            background: var(--primary-light);
            border-radius: 3px;
        }

        .timeline-item {
            padding-left: 2rem;
            position: relative;
            margin-bottom: 1.5rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -7px;
            top: 4px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: var(--primary);
            border: 4px solid var(--primary-light);
        }

        .timeline-date {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.25rem;
        }

        .timeline-event {
            font-size: 1rem;
            font-weight: 500;
            color: var(--foreground);
            margin-bottom: 0.25rem;
        }

        .timeline-description {
            font-size: 0.9rem;
            color: var(--muted);
        }

        .signature {
            margin-top: 3rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            text-align: right;
        }

        .signature img {
            width: 150px;
            margin-bottom: 0.5rem;
            opacity: 0.9;
        }

        .signature p {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--foreground);
            margin-bottom: 0.25rem;
        }

        .signature span {
            font-size: 0.8rem;
            color: var(--muted);
        }

        .faq-item {
            margin-bottom: 1.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding-bottom: 1.5rem;
        }

        .faq-item:last-child {
            margin-bottom: 0;
            border-bottom: none;
            padding-bottom: 0;
        }

        .faq-question {
            font-weight: 600;
            color: var(--foreground);
            margin-bottom: 0.75rem;
            display: flex;
            gap: 0.5rem;
            align-items: flex-start;
        }

        .faq-question i {
            color: var(--primary);
            margin-top: 0.25rem;
        }

        .faq-answer {
            padding-left: 1.5rem;
            color: var(--muted);
        }

        .footer {
            margin-top: 3rem;
            padding: 2rem 0;
            text-align: center;
            color: var(--muted);
            font-size: 0.85rem;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 1rem;
        }

        .footer-link {
            color: var(--muted);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-link:hover {
            color: var(--primary);
        }

        /* Animations */
        @keyframes pulse {
            0% { transform: translate(0, 0) scale(1); opacity: 0.7; }
            50% { transform: translate(-2%, -2%) scale(1.05); opacity: 0.8; }
            100% { transform: translate(0, 0) scale(1); opacity: 0.7; }
        }

        /* Responsive styles */
        @media (min-width: 768px) {
            .user-details {
                display: block;
            }
        }

        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 2rem;
            }
            
            .hero-section p {
                font-size: 1rem;
            }
            
            .guidelines-container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <a href="dashboard.php" class="logo">
                <i class="fas fa-vote-yea"></i>
                Campus<span>Vote</span>
            </a>
            <div class="user-info">
                <div class="user-details">
                    <h3><?php echo htmlspecialchars($userInfo['full_name']); ?></h3>
                    <p><?php echo htmlspecialchars($userInfo['program']); ?></p>
                </div>
                <div class="user-avatar">
                    <?php echo substr($userInfo['full_name'], 0, 1); ?>
                </div>
            </div>
        </nav>

        <a href="dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>

        <div class="hero-section">
            <h1>Election Guidelines</h1>
            <p>Official rules and procedures for the <?php echo htmlspecialchars($electionInfo['title']); ?></p>
        </div>

        <div class="guidelines-container">
            <div class="guidelines-section">
                <h2>General Guidelines</h2>
                <p>The university student election system is designed to provide a fair, transparent, and accessible platform for students to elect their representatives. These guidelines ensure the integrity of the electoral process.</p>
                <div class="important-notice">
                    <p><strong>Important:</strong> All students are required to review these guidelines before participating in the election process.</p>
                </div>
                <h3>Eligibility</h3>
                <ul>
                    <li><strong>Voters:</strong> All currently enrolled students with valid student ID numbers are eligible to vote.</li>
                    <li><strong>Candidates:</strong> Students who have completed at least one full academic year at the university and maintain a minimum GPA of 2.5 are eligible to run for positions.</li>
                    <li><strong>Academic Standing:</strong> Candidates must be in good academic and disciplinary standing with the university.</li>
                    <li><strong>Commitment:</strong> Candidates must commit to serve for the full term if elected.</li>
                </ul>
            </div>

            <div class="guidelines-section">
                <h2>Election Timeline</h2>
                <p>The election process follows a structured timeline to ensure all candidates have equal opportunity to campaign and all voters have sufficient time to make informed decisions.</p>
                
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-date">Phase 1</div>
                        <div class="timeline-event">Nomination Period</div>
                        <div class="timeline-description">Candidates submit their applications and required documentation.</div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-date">Phase 2</div>
                        <div class="timeline-event">Verification and Approval</div>
                        <div class="timeline-description">Election committee reviews applications and announces approved candidates.</div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-date">Phase 3</div>
                        <div class="timeline-event">Campaign Period</div>
                        <div class="timeline-description">Candidates promote their platforms through approved channels.</div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-date">Phase 4</div>
                        <div class="timeline-event">Voting Period</div>
                        <div class="timeline-description">Students cast their votes through the secure online system.</div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-date">Phase 5</div>
                        <div class="timeline-event">Results Announcement</div>
                        <div class="timeline-description">Election results are tabulated and officially announced.</div>
                    </div>
                </div>
                
                <p>The current election voting period is from <strong><?php echo date('F j, Y', strtotime($electionInfo['start_date'])); ?></strong> to <strong><?php echo date('F j, Y', strtotime($electionInfo['end_date'])); ?></strong>.</p>
            </div>

            <div class="guidelines-section">
                <h2>Voting Process</h2>
                <p>The voting system is designed to be secure, anonymous, and accessible to all eligible students.</p>
                
                <h3>Steps to Vote</h3>
                <ol>
                    <li><strong>Authentication:</strong> Log in to the student portal using your university credentials.</li>
                    <li><strong>Verification:</strong> The system will verify your eligibility to vote.</li>
                    <li><strong>Candidate Selection:</strong> Review the candidate profiles and select your preferred candidate for each position.</li>
                    <li><strong>Confirmation:</strong> Confirm your selections to submit your ballot.</li>
                    <li><strong>Receipt:</strong> Receive a digital confirmation of your vote submission.</li>
                </ol>
                
                <div class="tip-box">
                    <p><strong>Tip:</strong> Take time to review each candidate's platform before making your selection. Your informed vote matters!</p>
                </div>
                
                <h3>Voting Rules</h3>
                <ul>
                    <li>Each student is entitled to one vote per position.</li>
                    <li>Once a vote is submitted, it cannot be changed or retracted.</li>
                    <li>Votes are confidential and cannot be traced back to individual voters.</li>
                    <li>Voting must be completed within the designated voting period.</li>
                    <li>Any attempt to manipulate the voting system or vote multiple times will result in disciplinary action.</li>
                </ul>
            </div>

            <div class="guidelines-section">
                <h2>Campaign Regulations</h2>
                <p>To ensure a fair and respectful campaign environment, all candidates must adhere to the following regulations:</p>
                
                <h3>Approved Campaign Methods</h3>
                <div class="tags-container">
                    <span class="tag">Social Media</span>
                    <span class="tag">Campus Posters</span>
                    <span class="tag">Email Campaigns</span>
                    <span class="tag">Candidate Forums</span>
                    <span class="tag">Class Visits</span>
                    <span class="tag">Digital Flyers</span>
                </div>
                
                <h3>Campaign Ethics</h3>
                <ul>
                    <li>Candidates must conduct campaigns with integrity and respect for opponents.</li>
                    <li>Negative campaigning, defamation, or personal attacks are strictly prohibited.</li>
                    <li>Campaign materials must be honest, accurate, and focus on candidate qualifications and platforms.</li>
                    <li>University resources may not be used for campaign purposes without explicit permission.</li>
                    <li>Campaign activities must not disrupt academic activities or violate university policies.</li>
                    <li>Campaign expenses must not exceed the established limit of $100 per candidate.</li>
                </ul>
                
                <div class="important-notice">
                    <p><strong>Important:</strong> Violation of campaign regulations may result in disqualification.</p>
                </div>
            </div>

            <div class="guidelines-section">
                <h2>Frequently Asked Questions</h2>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <i class="fas fa-question-circle"></i>
                        <span>Can I vote if I'm a part-time student?</span>
                    </div>
                    <div class="faq-answer">
                        <p>Yes, all registered students, including part-time students, are eligible to vote in the elections.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <i class="fas fa-question-circle"></i>
                        <span>What happens if there's a tie between candidates?</span>
                    </div>
                    <div class="faq-answer">
                        <p>In the event of a tie, a run-off election will be held between the tied candidates within one week of the original election conclusion.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <i class="fas fa-question-circle"></i>
                        <span>Can I change my vote after submitting?</span>
                    </div>
                    <div class="faq-answer">
                        <p>No, once your vote is submitted, it cannot be changed or retracted. Please review your selections carefully before confirming your vote.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <i class="fas fa-question-circle"></i>
                        <span>How is the privacy of my vote ensured?</span>
                    </div>
                    <div class="faq-answer">
                        <p>The voting system is designed to separate your identity from your ballot after verification. While the system records that you have voted, it does not track which candidate you voted for.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <i class="fas fa-question-circle"></i>
                        <span>What happens if I experience technical issues while voting?</span>
                    </div>
                    <div class="faq-answer">
                        <p>If you encounter any technical issues, please contact the Election Support Team at election.support@university.edu or visit the Student IT Help Desk in the University Center.</p>
                    </div>
                </div>
            </div>

            <div class="signature">
                <p>Dr. Sarah Williams</p>
                <span>Chair, Election Committee</span>
                <br>
                <span>Last Updated: May 15, 2024</span>
            </div>
        </div>
    </div>

    <div class="footer">
        <div class="container">
            <div class="footer-links">
                <a href="dashboard.php" class="footer-link">Dashboard</a>
                <a href="election-guidelines.php" class="footer-link">Election Guidelines</a>
                <a href="privacy-policy.php" class="footer-link">Privacy Policy</a>
                <a href="contact.php" class="footer-link">Contact Support</a>
            </div>
            <p>&copy; <?php echo date('Y'); ?> University Student Elections. All rights reserved.</p>
        </div>
    </div>

    <script>
        // Add entrance animations for sections
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.guidelines-section');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = 1;
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, {
                threshold: 0.1
            });
            
            sections.forEach((section, index) => {
                section.style.opacity = 0;
                section.style.transform = 'translateY(20px)';
                section.style.transition = `all 0.5s ease ${index * 0.1}s`;
                observer.observe(section);
            });
        });
    </script>
</body>
</html>