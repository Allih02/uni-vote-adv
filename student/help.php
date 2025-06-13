<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}

// Initialize message variable
$success_message = null;

// Handle contact form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit_ticket"])) {
    // In a real implementation, you would process and store the support ticket
    // This is just a simulation for the UI
    $success_message = "Your support ticket has been submitted successfully. Our team will respond to you shortly.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help & Support - University Voting System</title>
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
            --warning: #f59e0b;
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
        
        .nav-link.active {
            color: var(--primary);
            font-weight: 600;
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
        
        /* Main Content */
        .main-content {
            flex: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 1.5rem;
            width: 100%;
        }
        
        .page-header {
            margin-bottom: 2.5rem;
            text-align: center;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            padding-bottom: 15px;
            display: inline-block;
        }
        
        .page-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--gradient);
            border-radius: 2px;
        }
        
        .page-description {
            color: var(--gray);
            max-width: 700px;
            margin: 0 auto;
        }
        
        /* Alert */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 10px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--secondary);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .alert i {
            font-size: 1.2rem;
        }
        
        /* Help Content */
        .help-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }
        
        /* Help Navigation */
        .help-nav {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
            position: sticky;
            top: 100px;
            align-self: start;
        }
        
        .help-nav-header {
            background: var(--gradient);
            color: white;
            padding: 1.5rem;
        }
        
        .help-nav-title {
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .help-nav-links {
            list-style: none;
        }
        
        .help-nav-link {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            color: var(--dark);
            text-decoration: none;
            transition: var(--transition);
            border-left: 3px solid transparent;
            font-size: 0.95rem;
        }
        
        .help-nav-link:hover {
            background-color: rgba(99, 102, 241, 0.05);
            border-left-color: var(--primary-light);
        }
        
        .help-nav-link.active {
            background-color: rgba(99, 102, 241, 0.1);
            border-left-color: var(--primary);
            font-weight: 600;
        }
        
        .help-nav-link i {
            margin-right: 10px;
            color: var(--primary);
            width: 20px;
            text-align: center;
        }
        
        /* Help Main */
        .help-main {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        
        .help-section {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
            scroll-margin-top: 100px;
        }
        
        .help-section-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .help-section-icon {
            width: 40px;
            height: 40px;
            background-color: var(--primary-light);
            color: var(--primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .help-section-title {
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .help-section-body {
            padding: 1.5rem;
        }
        
        /* FAQ Section */
        .faq-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .faq-item {
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .faq-question {
            padding: 1rem 1.5rem;
            background-color: var(--light);
            cursor: pointer;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--transition);
        }
        
        .faq-question:hover {
            background-color: rgba(99, 102, 241, 0.05);
        }
        
        .faq-question i {
            color: var(--primary);
            transition: var(--transition);
        }
        
        .faq-answer {
            padding: 0 1.5rem;
            max-height: 0;
            overflow: hidden;
            transition: var(--transition);
        }
        
        .faq-answer-content {
            padding: 1rem 0 1.5rem;
            border-top: 1px solid var(--light-gray);
        }
        
        .faq-item.active .faq-question {
            background-color: rgba(99, 102, 241, 0.1);
        }
        
        .faq-item.active .faq-question i {
            transform: rotate(180deg);
        }
        
        .faq-item.active .faq-answer {
            max-height: 500px;
        }
        
        /* How-to Guides */
        .guides-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .guide-card {
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            overflow: hidden;
            transition: var(--transition);
        }
        
        .guide-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .guide-image {
            height: 160px;
            background-color: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 3rem;
        }
        
        .guide-content {
            padding: 1.5rem;
        }
        
        .guide-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .guide-description {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .guide-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: var(--transition);
        }
        
        .guide-link:hover {
            color: var(--primary-dark);
        }
        
        /* Contact Form */
        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .contact-method {
            background-color: var(--light);
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            transition: var(--transition);
        }
        
        .contact-method:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .contact-icon {
            width: 60px;
            height: 60px;
            background: var(--gradient);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }
        
        .contact-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .contact-details {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .contact-link {
            color: var(--primary);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .contact-link:hover {
            color: var(--primary-dark);
        }
        
        .contact-form {
            background-color: var(--light);
            padding: 2rem;
            border-radius: 8px;
        }
        
        .form-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .form-control:focus {
            border-color: var(--primary-light);
            outline: none;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: var(--gradient);
            color: white;
            box-shadow: var(--button-shadow);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 10px rgba(99, 102, 241, 0.4);
        }
        
        .form-submit {
            text-align: center;
        }
        
        /* Footer */
        .footer {
            background-color: var(--dark);
            color: white;
            padding: 3rem 2rem;
            margin-top: 3rem;
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
            animation: fadeIn 0.5s ease forwards;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .help-content {
                grid-template-columns: 1fr;
            }
            
            .help-nav {
                position: relative;
                top: 0;
                margin-bottom: 2rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }
        
        @media (max-width: 768px) {
            .navbar {
                padding: 1rem;
            }
            
            .logo h1 {
                font-size: 1.1rem;
            }
            
            .main-content {
                padding: 2rem 1rem;
            }
            
            .page-title {
                font-size: 1.8rem;
            }
            
            .guides-list {
                grid-template-columns: 1fr;
            }
            
            .contact-info {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 576px) {
            .nav-links {
                display: none;
            }
            
            .help-section-header {
                flex-direction: column;
                align-items: flex-start;
                text-align: center;
            }
            
            .help-section-icon {
                margin: 0 auto 0.5rem;
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
                <a href="help.php" class="nav-link active">Help & Support</a>
            </div>
            <a href="logout.php" class="logout-button">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header fade-in">
                <h1 class="page-title">Help & Support</h1>
                <p class="page-description">Find answers to common questions, learn how to use the voting system, and get assistance if you need it.</p>
            </div>
            
            <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success_message; ?></span>
            </div>
            <?php endif; ?>
            
            <!-- Help Content -->
            <div class="help-content">
                <!-- Help Navigation -->
                <div class="help-nav fade-in">
                    <div class="help-nav-header">
                        <h3 class="help-nav-title">
                            <i class="fas fa-book"></i> Help Topics
                        </h3>
                    </div>
                    <ul class="help-nav-links">
                        <li>
                            <a href="#faq" class="help-nav-link active">
                                <i class="fas fa-question-circle"></i> Frequently Asked Questions
                            </a>
                        </li>
                        <li>
                            <a href="#guides" class="help-nav-link">
                                <i class="fas fa-clipboard-list"></i> How-to Guides
                            </a>
                        </li>
                        <li>
                            <a href="#voting-process" class="help-nav-link">
                                <i class="fas fa-vote-yea"></i> Voting Process
                            </a>
                        </li>
                        <li>
                            <a href="#security" class="help-nav-link">
                                <i class="fas fa-shield-alt"></i> Security Information
                            </a>
                        </li>
                        <li>
                            <a href="#troubleshooting" class="help-nav-link">
                                <i class="fas fa-tools"></i> Troubleshooting
                            </a>
                        </li>
                        <li>
                            <a href="#contact" class="help-nav-link">
                                <i class="fas fa-headset"></i> Contact Support
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Help Main -->
                <div class="help-main">
                    <!-- FAQ Section -->
                    <section id="faq" class="help-section fade-in">
                        <div class="help-section-header">
                            <div class="help-section-icon">
                                <i class="fas fa-question-circle"></i>
                            </div>
                            <h2 class="help-section-title">Frequently Asked Questions</h2>
                        </div>
                        <div class="help-section-body">
                            <div class="faq-list">
                                <div class="faq-item">
                                    <div class="faq-question">
                                        <span>How do I vote in an election?</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                    <div class="faq-answer">
                                        <div class="faq-answer-content">
                                            <p>To vote in an election, you need to be logged into your account. From the dashboard, you'll see active elections. Click the "Vote Now" button to access the ballot, select your preferred candidate(s), review your choices, and submit your vote. You'll receive a confirmation once your vote is recorded.</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="faq-item">
                                    <div class="faq-question">
                                        <span>Can I change my vote after submitting?</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                    <div class="faq-answer">
                                        <div class="faq-answer-content">
                                            <p>No, once you submit your vote, it cannot be changed. This is to ensure the integrity of the election process. Please review your selections carefully before submitting your final vote.</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="faq-item">
                                    <div class="faq-question">
                                        <span>When will election results be announced?</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                    <div class="faq-answer">
                                        <div class="faq-answer-content">
                                            <p>Election results are typically announced within 24 hours after the voting period ends. You'll receive a notification when results are published, and they will be accessible on the election results page.</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="faq-item">
                                    <div class="faq-question">
                                        <span>What if I forgot my password?</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                    <div class="faq-answer">
                                        <div class="faq-answer-content">
                                            <p>If you forgot your password, click on the "Forgot Password" link on the login page. You'll be prompted to enter your university email address, and a password reset link will be sent to you. Follow the instructions in the email to create a new password.</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="faq-item">
                                    <div class="faq-question">
                                        <span>Is my vote anonymous?</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                    <div class="faq-answer">
                                        <div class="faq-answer-content">
                                            <p>Yes, your vote is anonymous. While the system records that you have participated in an election, it does not link your identity to your specific voting choices. The voting data is stored securely and separately from user identification data.</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="faq-item">
                                    <div class="faq-question">
                                        <span>How can I run as a candidate in an election?</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                    <div class="faq-answer">
                                        <div class="faq-answer-content">
                                            <p>To run as a candidate, you need to submit a candidate application during the nomination period. Contact the Student Affairs Office or visit their website for specific requirements and deadlines for each election. Candidate applications typically include a statement of intent, qualifications, and endorsements.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                    
                    <!-- How-to Guides Section -->
                    <section id="guides" class="help-section fade-in">
                        <div class="help-section-header">
                            <div class="help-section-icon">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <h2 class="help-section-title">How-to Guides</h2>
                        </div>
                        <div class="help-section-body">
                            <div class="guides-list">
                                <div class="guide-card">
                                    <div class="guide-image">
                                        <i class="fas fa-user-circle"></i>
                                    </div>
                                    <div class="guide-content">
                                        <h3 class="guide-title">Setting Up Your Profile</h3>
                                        <p class="guide-description">Learn how to complete your profile and customize your account settings.</p>
                                        <a href="#" class="guide-link">
                                            Read Guide <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="guide-card">
                                    <div class="guide-image">
                                        <i class="fas fa-vote-yea"></i>
                                    </div>
                                    <div class="guide-content">
                                        <h3 class="guide-title">Casting Your Vote</h3>
                                        <p class="guide-description">A step-by-step guide to participating in university elections.</p>
                                        <a href="#" class="guide-link">
                                            Read Guide <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="guide-card">
                                    <div class="guide-image">
                                        <i class="fas fa-chart-bar"></i>
                                    </div>
                                    <div class="guide-content">
                                        <h3 class="guide-title">Viewing Election Results</h3>
                                        <p class="guide-description">How to access and interpret election outcome data.</p>
                                        <a href="#" class="guide-link">
                                            Read Guide <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="guide-card">
                                    <div class="guide-image">
                                        <i class="fas fa-lock"></i>
                                    </div>
                                    <div class="guide-content">
                                        <h3 class="guide-title">Securing Your Account</h3>
                                        <p class="guide-description">Best practices for keeping your voting account secure.</p>
                                        <a href="#" class="guide-link">
                                            Read Guide <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Voting Process Section -->
                    <section id="voting-process" class="help-section fade-in">
                        <div class="help-section-header">
                            <div class="help-section-icon">
                                <i class="fas fa-vote-yea"></i>
                            </div>
                            <h2 class="help-section-title">Voting Process</h2>
                        </div>
                        <div class="help-section-body">
                            <div class="process-steps">
                                <div style="margin-bottom: 2rem;">
                                    <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">Understanding the Election Cycle</h3>
                                    <p>The university voting process follows a structured timeline to ensure fairness and transparency. Each election cycle consists of several key stages:</p>
                                    
                                    <ol style="margin-top: 1rem; margin-left: 1.5rem;">
                                        <li style="margin-bottom: 0.8rem;"><strong>Announcement Phase:</strong> Official notification of upcoming elections with important dates and positions.</li>
                                        <li style="margin-bottom: 0.8rem;"><strong>Nomination Period:</strong> Eligible students submit applications to run for positions.</li>
                                        <li style="margin-bottom: 0.8rem;"><strong>Campaign Period:</strong> Approved candidates present their platforms to the student body.</li>
                                        <li style="margin-bottom: 0.8rem;"><strong>Voting Period:</strong> Active voting window when eligible students cast their ballots.</li>
                                        <li style="margin-bottom: 0.8rem;"><strong>Results Announcement:</strong> Official publication of election outcomes.</li>
                                    </ol>
                                </div>
                                
                                <div style="margin-bottom: 2rem;">
                                    <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">How to Cast Your Vote</h3>
                                    <p>Follow these steps to participate in university elections:</p>
                                    
                                    <div style="display: flex; margin-top: 1.5rem; flex-wrap: wrap; gap: 1.5rem;">
                                        <div style="flex: 1; min-width: 250px; background-color: var(--light); padding: 1.2rem; border-radius: 8px; position: relative;">
                                            <div style="position: absolute; top: -15px; left: 1.2rem; background: var(--gradient); color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">1</div>
                                            <h4 style="margin-top: 0.5rem; margin-bottom: 0.5rem; font-size: 1rem;">Log In</h4>
                                            <p style="font-size: 0.9rem; color: var(--gray);">Access your account using your university credentials through the secure login portal.</p>
                                        </div>
                                        
                                        <div style="flex: 1; min-width: 250px; background-color: var(--light); padding: 1.2rem; border-radius: 8px; position: relative;">
                                            <div style="position: absolute; top: -15px; left: 1.2rem; background: var(--gradient); color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">2</div>
                                            <h4 style="margin-top: 0.5rem; margin-bottom: 0.5rem; font-size: 1rem;">Access Active Elections</h4>
                                            <p style="font-size: 0.9rem; color: var(--gray);">From your dashboard, view and select the current active election you wish to participate in.</p>
                                        </div>
                                        
                                        <div style="flex: 1; min-width: 250px; background-color: var(--light); padding: 1.2rem; border-radius: 8px; position: relative;">
                                            <div style="position: absolute; top: -15px; left: 1.2rem; background: var(--gradient); color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">3</div>
                                            <h4 style="margin-top: 0.5rem; margin-bottom: 0.5rem; font-size: 1rem;">Review Candidates</h4>
                                            <p style="font-size: 0.9rem; color: var(--gray);">Read through candidate profiles, platforms, and qualifications before making your selection.</p>
                                        </div>
                                        
                                        <div style="flex: 1; min-width: 250px; background-color: var(--light); padding: 1.2rem; border-radius: 8px; position: relative;">
                                            <div style="position: absolute; top: -15px; left: 1.2rem; background: var(--gradient); color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">4</div>
                                            <h4 style="margin-top: 0.5rem; margin-bottom: 0.5rem; font-size: 1rem;">Submit Your Vote</h4>
                                            <p style="font-size: 0.9rem; color: var(--gray);">Select your preferred candidates and confirm your choices to officially cast your vote.</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">Vote Verification</h3>
                                    <p>After submitting your vote, you'll receive a digital receipt with a unique identifier confirming your participation. This receipt does not show your specific choices but serves as proof that you voted. The system employs encryption technology to ensure votes remain anonymous while allowing for verification of the overall election integrity.</p>
                                </div>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Security Section -->
                    <section id="security" class="help-section fade-in">
                        <div class="help-section-header">
                            <div class="help-section-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h2 class="help-section-title">Security Information</h2>
                        </div>
                        <div class="help-section-body">
                            <div style="margin-bottom: 2rem;">
                                <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">System Security Features</h3>
                                <p>The University Voting System incorporates several security measures to protect the integrity of the electoral process:</p>
                                
                                <ul style="margin-top: 1rem; list-style: none;">
                                    <li style="margin-bottom: 0.8rem; display: flex; align-items: flex-start; gap: 10px;">
                                        <i class="fas fa-check-circle" style="color: var(--secondary); margin-top: 3px;"></i>
                                        <div>
                                            <strong>End-to-End Encryption</strong>
                                            <p style="font-size: 0.9rem; color: var(--gray);">All data transmitted between your browser and our servers is encrypted using industry-standard protocols.</p>
                                        </div>
                                    </li>
                                    <li style="margin-bottom: 0.8rem; display: flex; align-items: flex-start; gap: 10px;">
                                        <i class="fas fa-check-circle" style="color: var(--secondary); margin-top: 3px;"></i>
                                        <div>
                                            <strong>Two-Factor Authentication</strong>
                                            <p style="font-size: 0.9rem; color: var(--gray);">Additional verification is required for sensitive actions, ensuring only authorized users can access the system.</p>
                                        </div>
                                    </li>
                                    <li style="margin-bottom: 0.8rem; display: flex; align-items: flex-start; gap: 10px;">
                                        <i class="fas fa-check-circle" style="color: var(--secondary); margin-top: 3px;"></i>
                                        <div>
                                            <strong>Vote Anonymization</strong>
                                            <p style="font-size: 0.9rem; color: var(--gray);">Ballots are stored separately from voter identities to ensure anonymity while preventing duplicate voting.</p>
                                        </div>
                                    </li>
                                    <li style="margin-bottom: 0.8rem; display: flex; align-items: flex-start; gap: 10px;">
                                        <i class="fas fa-check-circle" style="color: var(--secondary); margin-top: 3px;"></i>
                                        <div>
                                            <strong>Audit Trails</strong>
                                            <p style="font-size: 0.9rem; color: var(--gray);">System activities are logged for security review, without compromising voter privacy.</p>
                                        </div>
                                    </li>
                                    <li style="display: flex; align-items: flex-start; gap: 10px;">
                                        <i class="fas fa-check-circle" style="color: var(--secondary); margin-top: 3px;"></i>
                                        <div>
                                            <strong>Regular Security Testing</strong>
                                            <p style="font-size: 0.9rem; color: var(--gray);">Independent security audits and penetration testing ensure system integrity.</p>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            
                            <div>
                                <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">Protecting Your Account</h3>
                                <p>Take these steps to ensure your voting account remains secure:</p>
                                
                                <div style="margin-top: 1.5rem; display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.2rem;">
                                    <div style="background-color: var(--light); padding: 1.2rem; border-radius: 8px;">
                                        <div style="display: flex; align-items: center; margin-bottom: 0.8rem; gap: 10px;">
                                            <div style="width: 36px; height: 36px; background-color: rgba(99, 102, 241, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary);">
                                                <i class="fas fa-key"></i>
                                            </div>
                                            <h4 style="font-size: 1rem;">Strong Passwords</h4>
                                        </div>
                                        <p style="font-size: 0.9rem; color: var(--gray);">Use a unique, complex password with a mix of letters, numbers, and symbols.</p>
                                    </div>
                                    
                                    <div style="background-color: var(--light); padding: 1.2rem; border-radius: 8px;">
                                        <div style="display: flex; align-items: center; margin-bottom: 0.8rem; gap: 10px;">
                                            <div style="width: 36px; height: 36px; background-color: rgba(99, 102, 241, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary);">
                                                <i class="fas fa-sign-out-alt"></i>
                                            </div>
                                            <h4 style="font-size: 1rem;">Log Out When Finished</h4>
                                        </div>
                                        <p style="font-size: 0.9rem; color: var(--gray);">Always log out when you're done using the system, especially on shared devices.</p>
                                    </div>
                                    
                                    <div style="background-color: var(--light); padding: 1.2rem; border-radius: 8px;">
                                        <div style="display: flex; align-items: center; margin-bottom: 0.8rem; gap: 10px;">
                                            <div style="width: 36px; height: 36px; background-color: rgba(99, 102, 241, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary);">
                                                <i class="fas fa-envelope"></i>
                                            </div>
                                            <h4 style="font-size: 1rem;">Verify Email Communications</h4>
                                        </div>
                                        <p style="font-size: 0.9rem; color: var(--gray);">Official communications will come from university domains. Be cautious of suspicious emails.</p>
                                    </div>
                                    
                                    <div style="background-color: var(--light); padding: 1.2rem; border-radius: 8px;">
                                        <div style="display: flex; align-items: center; margin-bottom: 0.8rem; gap: 10px;">
                                            <div style="width: 36px; height: 36px; background-color: rgba(99, 102, 241, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary);">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </div>
                                            <h4 style="font-size: 1rem;">Report Suspicious Activity</h4>
                                        </div>
                                        <p style="font-size: 0.9rem; color: var(--gray);">Contact support immediately if you notice any unusual activity on your account.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Troubleshooting Section -->
                    <section id="troubleshooting" class="help-section fade-in">
                        <div class="help-section-header">
                            <div class="help-section-icon">
                                <i class="fas fa-tools"></i>
                            </div>
                            <h2 class="help-section-title">Troubleshooting</h2>
                        </div>
                        <div class="help-section-body">
                            <div class="faq-list">
                                <div class="faq-item">
                                    <div class="faq-question">
                                        <span>I can't log in to my account</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                    <div class="faq-answer">
                                        <div class="faq-answer-content">
                                            <p>If you're having trouble logging in, try these solutions:</p>
                                            <ul style="margin-top: 0.8rem; margin-left: 1.5rem;">
                                                <li style="margin-bottom: 0.5rem;">Verify that you're using the correct username/email and password</li>
                                                <li style="margin-bottom: 0.5rem;">Clear your browser cache and cookies</li>
                                                <li style="margin-bottom: 0.5rem;">Try a different browser</li>
                                                <li style="margin-bottom: 0.5rem;">Use the "Forgot Password" link to reset your password</li>
                                                <li>If you continue to experience issues, contact the IT support desk</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="faq-item">
                                    <div class="faq-question">
                                        <span>The voting page isn't loading properly</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                    <div class="faq-answer">
                                        <div class="faq-answer-content">
                                            <p>If you're experiencing display issues with the voting page:</p>
                                            <ul style="margin-top: 0.8rem; margin-left: 1.5rem;">
                                                <li style="margin-bottom: 0.5rem;">Refresh the page</li>
                                                <li style="margin-bottom: 0.5rem;">Update your browser to the latest version</li>
                                                <li style="margin-bottom: 0.5rem;">Disable browser extensions that might interfere with the page</li>
                                                <li style="margin-bottom: 0.5rem;">Try using a different browser</li>
                                                <li>If the problem persists, take a screenshot of the issue and submit it to technical support</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="faq-item">
                                    <div class="faq-question">
                                        <span>I received an error message when submitting my vote</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                    <div class="faq-answer">
                                        <div class="faq-answer-content">
                                            <p>If you encounter an error when submitting your vote:</p>
                                            <ul style="margin-top: 0.8rem; margin-left: 1.5rem;">
                                                <li style="margin-bottom: 0.5rem;">Note the exact error message</li>
                                                <li style="margin-bottom: 0.5rem;">Check your internet connection</li>
                                                <li style="margin-bottom: 0.5rem;">Try submitting again after a few minutes</li>
                                                <li style="margin-bottom: 0.5rem;">Verify that the election is still active and within the voting period</li>
                                                <li>Contact support with the error message and the time the error occurred</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="faq-item">
                                    <div class="faq-question">
                                        <span>I don't see an active election on my dashboard</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                    <div class="faq-answer">
                                        <div class="faq-answer-content">
                                            <p>If you don't see an election that you believe should be available:</p>
                                            <ul style="margin-top: 0.8rem; margin-left: 1.5rem;">
                                                <li style="margin-bottom: 0.5rem;">Verify the election dates and make sure it's currently active</li>
                                                <li style="margin-bottom: 0.5rem;">Check if you're eligible to vote in this particular election (some elections may be restricted to certain departments or years)</li>
                                                <li style="margin-bottom: 0.5rem;">Log out and log back in to refresh your dashboard</li>
                                                <li>If you believe this is an error, contact the election administrator</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="faq-item">
                                    <div class="faq-question">
                                        <span>The system is slow or unresponsive</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                    <div class="faq-answer">
                                        <div class="faq-answer-content">
                                            <p>During peak voting times, the system may experience higher than normal traffic. If the system is responding slowly:</p>
                                            <ul style="margin-top: 0.8rem; margin-left: 1.5rem;">
                                                <li style="margin-bottom: 0.5rem;">Try again during non-peak hours</li>
                                                <li style="margin-bottom: 0.5rem;">Check your internet connection</li>
                                                <li style="margin-bottom: 0.5rem;">Close unnecessary browser tabs and applications</li>
                                                <li style="margin-bottom: 0.5rem;">Clear your browser cache</li>
                                                <li>If the problem continues for an extended period, report it to technical support</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Contact Support Section -->
                    <section id="contact" class="help-section fade-in">
                        <div class="help-section-header">
                            <div class="help-section-icon">
                                <i class="fas fa-headset"></i>
                            </div>
                            <h2 class="help-section-title">Contact Support</h2>
                        </div>
                        <div class="help-section-body">
                            <div class="contact-info">
                                <div class="contact-method">
                                    <div class="contact-icon">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <h3 class="contact-title">Email Support</h3>
                                    <p class="contact-details">
                                        <a href="mailto:voting-support@university.edu" class="contact-link">voting-support@university.edu</a>
                                    </p>
                                    <p class="contact-details">Response within 24 hours</p>
                                </div>
                                
                                <div class="contact-method">
                                    <div class="contact-icon">
                                        <i class="fas fa-phone-alt"></i>
                                    </div>
                                    <h3 class="contact-title">Phone Support</h3>
                                    <p class="contact-details">
                                        <a href="tel:+11234567890" class="contact-link">+1 (123) 456-7890</a>
                                    </p>
                                    <p class="contact-details">Mon-Fri, 9AM-5PM</p>
                                </div>
                                
                                <div class="contact-method">
                                    <div class="contact-icon">
                                        <i class="fas fa-comment-alt"></i>
                                    </div>
                                    <h3 class="contact-title">Live Chat</h3>
                                    <p class="contact-details">
                                        <a href="#" class="contact-link">Start a Chat Session</a>
                                    </p>
                                    <p class="contact-details">Available during business hours</p>
                                </div>
                            </div>
                            
                            <div class="contact-form">
                                <h3 class="form-title">Submit a Support Ticket</h3>
                                <form action="" method="post">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="name">Your Name</label>
                                            <input type="text" id="name" name="name" class="form-control" value="<?php echo $_SESSION["fullname"]; ?>" required readonly>
                                        </div>
                                        <div class="form-group">
                                            <label for="email">Email Address</label>
                                            <input type="email" id="email" name="email" class="form-control" value="student@university.edu" required readonly>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="subject">Subject</label>
                                        <input type="text" id="subject" name="subject" class="form-control" required placeholder="Brief description of your issue">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="category">Issue Category</label>
                                        <select id="category" name="category" class="form-control" required>
                                            <option value="">Select a category</option>
                                            <option value="login">Login Problems</option>
                                            <option value="voting">Voting Issues</option>
                                            <option value="technical">Technical Problems</option>
                                            <option value="account">Account Management</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="message">Describe Your Issue</label>
                                        <textarea id="message" name="message" class="form-control" rows="5" required placeholder="Please provide as much detail as possible about the issue you're experiencing"></textarea>
                                    </div>
                                    
                                    <div class="form-submit">
                                        <button type="submit" name="submit_ticket" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i> Submit Ticket
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </section>
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
                        <li><a href="mailto:support@university.edu"><i class="fas fa-envelope"></i> support@university.edu</a></li>
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
        // FAQ Accordion
        const faqQuestions = document.querySelectorAll('.faq-question');
        faqQuestions.forEach(question => {
            question.addEventListener('click', () => {
                const faqItem = question.parentElement;
                const isActive = faqItem.classList.contains('active');
                
                // Close all FAQ items
                document.querySelectorAll('.faq-item').forEach(item => {
                    item.classList.remove('active');
                });
                
                // If the clicked item wasn't active, make it active
                if (!isActive) {
                    faqItem.classList.add('active');
                }
            });
        });
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    // Update active class on nav links
                    document.querySelectorAll('.help-nav-link').forEach(link => {
                        link.classList.remove('active');
                    });
                    this.classList.add('active');
                    
                    // Scroll to the target element
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Auto hide alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            }, 5000);
        });
        
        // Highlight active section on scroll
        window.addEventListener('scroll', () => {
            const sections = document.querySelectorAll('.help-section');
            const navLinks = document.querySelectorAll('.help-nav-link');
            
            let currentSection = '';
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop - 120;
                const sectionBottom = sectionTop + section.offsetHeight;
                
                if (window.scrollY >= sectionTop && window.scrollY < sectionBottom) {
                    currentSection = section.getAttribute('id');
                }
            });
            
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${currentSection}`) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>