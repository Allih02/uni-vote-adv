<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}

// Get current election data (placeholder)
// In a real implementation, you would fetch this from your database
$current_election = [
    "id" => 1,
    "title" => "Student Council Elections 2025",
    "end_date" => "2025-04-30",
    "description" => "Cast your vote to elect the next Student Council representatives who will lead initiatives for the upcoming academic year."
];

// Check if user has already voted (placeholder)
$has_voted = false; // Set to true if user has already voted
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Voting System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #a5b4fc;
            --secondary: #10b981;
            --secondary-light: #34d399;
            --dark: #1e293b;
            --light: #f8fafc;
            --gray: #64748b;
            --light-gray: #e2e8f0;
            --danger: #ef4444;
            --gradient: linear-gradient(135deg, #6366f1, #8b5cf6);
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --button-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.4);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --border-radius: 12px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
        
        body {
            background-color: #f1f5f9;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Navigation Bar */
        .navbar {
            background-color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo i {
            color: var(--primary);
            font-size: 1.5rem;
        }
        
        .logo h1 {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .nav-link {
            color: var(--gray);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            font-size: 0.9rem;
        }
        
        .nav-link:hover {
            color: var(--primary);
        }
        
        .logout-button {
            background-color: transparent;
            color: var(--dark);
            border: 1px solid var(--light-gray);
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }
        
        .logout-button:hover {
            background-color: var(--light-gray);
            color: var(--primary-dark);
        }
        
        /* Hero Section */
        .hero {
            background: var(--gradient), url('/api/placeholder/1200/600') center/cover no-repeat;
            color: white;
            padding: 4rem 2rem;
            text-align: center;
            position: relative;
        }
        
        .hero-content {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }
        
        .hero h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        
        .hero p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .hero::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 1;
        }
        
        .cta-button {
            background-color: white;
            color: var(--primary);
            border: none;
            padding: 12px 28px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 50px;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: var(--button-shadow);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 10px -1px rgba(0, 0, 0, 0.2);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 1.5rem;
            width: 100%;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .greeting h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .greeting p {
            color: var(--gray);
        }
        
        .user-badge {
            background-color: white;
            border-radius: 50px;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background-color: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-dark);
            font-size: 1.2rem;
        }
        
        .user-info {
            line-height: 1.3;
        }
        
        .user-info p {
            font-size: 0.9rem;
            color: var(--gray);
        }
        
        /* Dashboard Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }
        
        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 1.5rem;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-icon {
            background-color: var(--primary-light);
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            color: var(--primary);
            font-size: 1.5rem;
        }
        
        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .card-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .card-description {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        /* Election Card */
        .election-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .election-header {
            padding: 2rem;
            background: var(--gradient);
            color: white;
            position: relative;
        }
        
        .election-header h3 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .election-header p {
            opacity: 0.9;
            font-size: 1rem;
            max-width: 700px;
        }
        
        .deadline-badge {
            position: absolute;
            top: 2rem;
            right: 2rem;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            padding: 8px 16px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .election-body {
            padding: 2rem;
            text-align: center;
        }
        
        .election-description {
            margin-bottom: 2rem;
            font-size: 1.05rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .vote-button {
            background: var(--gradient);
            color: white;
            border: none;
            padding: 15px 35px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: var(--button-shadow);
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        
        .vote-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(99, 102, 241, 0.4);
        }
        
        .vote-button i {
            font-size: 1.2rem;
        }
        
        /* Voting Process Section */
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 10px;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--gradient);
            border-radius: 2px;
        }
        
        .process-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .step-card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 1.5rem;
            position: relative;
            transition: var(--transition);
        }
        
        .step-card:hover {
            transform: translateY(-5px);
        }
        
        .step-number {
            position: absolute;
            top: -15px;
            left: 1.5rem;
            background: var(--gradient);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .step-icon {
            color: var(--primary);
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .step-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .step-description {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        /* Footer */
        .footer {
            background-color: var(--dark);
            color: white;
            padding: 3rem 2rem;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        .footer-section h4 {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
            position: relative;
            padding-bottom: 10px;
        }
        
        .footer-section h4::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 3px;
            background-color: var(--primary);
        }
        
        .footer-section p {
            margin-bottom: 1rem;
            opacity: 0.8;
            font-size: 0.9rem;
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 0.8rem;
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: var(--transition);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .footer-links a:hover {
            color: var(--primary-light);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            margin-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.9rem;
            opacity: 0.7;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.6s ease forwards;
        }
        
        .fade-in-delay-1 {
            animation: fadeIn 0.6s ease 0.2s forwards;
            opacity: 0;
        }
        
        .fade-in-delay-2 {
            animation: fadeIn 0.6s ease 0.4s forwards;
            opacity: 0;
        }
        
        .fade-in-delay-3 {
            animation: fadeIn 0.6s ease 0.6s forwards;
            opacity: 0;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .user-badge {
                align-self: flex-start;
            }
            
            .deadline-badge {
                position: relative;
                top: auto;
                right: auto;
                margin-top: 1rem;
                width: fit-content;
            }
        }
        
        @media (max-width: 768px) {
            .navbar {
                padding: 1rem;
            }
            
            .logo h1 {
                font-size: 1.1rem;
            }
            
            .hero {
                padding: 3rem 1rem;
            }
            
            .hero h2 {
                font-size: 2rem;
            }
            
            .main-content {
                padding: 2rem 1rem;
            }
            
            .greeting h2 {
                font-size: 1.5rem;
            }
            
            .election-header {
                padding: 1.5rem;
            }
            
            .election-header h3 {
                font-size: 1.4rem;
            }
        }
        
        @media (max-width: 576px) {
            .nav-links {
                display: none;
            }
            
            .dashboard-cards, .process-steps {
                grid-template-columns: 1fr;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Navigation Bar -->
        <nav class="navbar">
            <div class="logo">
                <i class="fas fa-vote-yea"></i>
                <h1>University Voting System</h1>
            </div>
            <div class="nav-links">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="profile.php" class="nav-link">Profile</a>
            </div>
            <a href="logout.php" class="logout-button">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
        
        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
                <h2>Your Voice Matters</h2>
                <p>Participate in the university elections and help shape the future of our academic community. Every vote counts in building a stronger institution.</p>
                <a href="vote.php?election_id=<?php echo $current_election["id"]; ?>" class="cta-button">
                    <i class="fas fa-vote-yea"></i> Vote Now
                </a>
            </div>
        </section>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Dashboard Header -->
            <div class="dashboard-header">
                <div class="greeting fade-in">
                    <h2>Welcome, <?php echo $_SESSION["fullname"]; ?>!</h2>
                    <p>Access your voting dashboard to participate in active elections.</p>
                </div>
                <div class="user-badge fade-in">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-info">
                        <strong><?php echo $_SESSION["fullname"]; ?></strong>
                        <p><?php echo $_SESSION["registration_number"]; ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Dashboard Cards -->
            <div class="dashboard-cards">
                <div class="card fade-in-delay-1">
                    <div class="card-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3 class="card-title">Active Elections</h3>
                    <p class="card-value">1</p>
                    <p class="card-description">Current ongoing election campaigns</p>
                </div>
                
                <div class="card fade-in-delay-2">
                    <div class="card-icon" style="background-color: rgba(16, 185, 129, 0.2); color: var(--secondary);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 class="card-title">Completed Votes</h3>
                    <p class="card-value">0</p>
                    <p class="card-description">Elections you've participated in</p>
                </div>
                
                <div class="card fade-in-delay-3">
                    <div class="card-icon" style="background-color: rgba(239, 68, 68, 0.2); color: var(--danger);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="card-title">Days Remaining</h3>
                    <p class="card-value">16</p>
                    <p class="card-description">Until current election closes</p>
                </div>
            </div>
            
            <!-- Current Election -->
            <div class="election-card fade-in">
                <div class="election-header">
                    <h3><?php echo $current_election["title"]; ?></h3>
                    <p>An opportunity to elect your student representatives for the upcoming academic year.</p>
                    
                    <div class="deadline-badge">
                        <i class="far fa-calendar-alt"></i>
                        Ends: <?php echo date('M d, Y', strtotime($current_election["end_date"])); ?>
                    </div>
                </div>
                
                <div class="election-body">
                    <p class="election-description">
                        <?php echo $current_election["description"]; ?>
                    </p>
                    
                    <?php if ($has_voted): ?>
                        <button class="vote-button" style="background: var(--secondary); cursor: default;">
                            <i class="fas fa-check-circle"></i> Vote Recorded
                        </button>
                    <?php else: ?>
                        <a href="vote.php?election_id=<?php echo $current_election["id"]; ?>" class="vote-button">
                            <i class="fas fa-vote-yea"></i> Vote Now
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Voting Process -->
            <h2 class="section-title fade-in">How Voting Works</h2>
            <div class="process-steps">
                <div class="step-card fade-in-delay-1">
                    <div class="step-number">1</div>
                    <div class="step-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <h3 class="step-title">Authentication</h3>
                    <p class="step-description">Login with your university credentials to access the secure voting portal.</p>
                </div>
                
                <div class="step-card fade-in-delay-2">
                    <div class="step-number">2</div>
                    <div class="step-icon">
                        <i class="fas fa-vote-yea"></i>
                    </div>
                    <h3 class="step-title">Cast Your Vote</h3>
                    <p class="step-description">Select your preferred candidate from the ballot and confirm your choice.</p>
                </div>
                
                <div class="step-card fade-in-delay-3">
                    <div class="step-number">3</div>
                    <div class="step-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="step-title">Secure Verification</h3>
                    <p class="step-description">Your vote is encrypted and securely recorded in our system.</p>
                </div>
            </div>
        </main>
        
        <!-- Footer -->
        <footer class="footer">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>About</h4>
                    <p>The University Voting System is a secure platform designed to facilitate fair and transparent elections across the university community.</p>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                        <li><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                        <li><a href="help.php"><i class="fas fa-question-circle"></i> Help & Support</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Contact</h4>
                    <ul class="footer-links">
                        <li><a href="backupallih@gmail.co"><i class="fas fa-envelope"></i> support@university.edu</a></li>
                        <li><a href="tel:+11234567890"><i class="fas fa-phone"></i> +1 (123) 456-7890</a></li>
                        <li><a href="#"><i class="fas fa-map-marker-alt"></i> University Campus, Building A</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> University Voting System. All rights reserved.</p>
            </div>
        </footer>
    </div>
    
    <script>
        // Check if elements are in viewport for animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = 1;
                    entry.target.style.transform = 'translateY(0)';
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        
        // Observe all elements with fade-in classes
        document.querySelectorAll('.fade-in, .fade-in-delay-1, .fade-in-delay-2, .fade-in-delay-3').forEach(element => {
            observer.observe(element);
        });
        
        // Add hover animations to buttons
        const buttons = document.querySelectorAll('.vote-button, .cta-button');
        buttons.forEach(button => {
            if (!button.textContent.includes('Vote Recorded')) {
                button.addEventListener('mouseenter', () => {
                    button.style.transform = 'translateY(-3px)';
                    button.style.boxShadow = '0 8px 15px rgba(99, 102, 241, 0.4)';
                });
                
                button.addEventListener('mouseleave', () => {
                    button.style.transform = '';
                    button.style.boxShadow = '';
                });
            }
        });
    </script>
</body>
</html>