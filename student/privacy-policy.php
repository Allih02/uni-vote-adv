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

// Get university details (optional - you can modify this based on your needs)
$universityName = "State University";
$universityAddress = "123 University Avenue, Campus City, ST 12345";
$universityContact = "privacy@university.edu";
$lastUpdated = "May 10, 2024";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - Student Elections</title>
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

        .policy-container {
            background: var(--card);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 3rem;
        }

        .policy-section {
            margin-bottom: 2.5rem;
        }

        .policy-section:last-child {
            margin-bottom: 0;
        }

        .policy-section h2 {
            font-family: var(--heading-font);
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--primary);
            position: relative;
            padding-bottom: 0.5rem;
        }

        .policy-section h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 3px;
        }

        .policy-section h3 {
            font-family: var(--heading-font);
            font-size: 1.2rem;
            margin: 1.5rem 0 0.75rem;
            color: var(--foreground);
        }

        .policy-section p {
            margin-bottom: 1rem;
            line-height: 1.6;
            color: var(--muted);
        }

        .policy-section ul, .policy-section ol {
            margin-bottom: 1.5rem;
            padding-left: 1.5rem;
        }

        .policy-section li {
            margin-bottom: 0.75rem;
            line-height: 1.6;
            color: var(--muted);
        }

        .policy-section strong {
            color: var(--foreground);
            font-weight: 600;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .data-table th {
            background: var(--primary-light);
            color: var(--primary-dark);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
        }

        .data-table td {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            color: var(--muted);
        }

        .data-table tr:nth-child(even) {
            background: rgba(0, 0, 0, 0.02);
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .cookie-banner {
            background: rgba(79, 70, 229, 0.08);
            border-radius: 12px;
            padding: 1.5rem;
            margin: 2rem 0;
            border-left: 4px solid var(--primary);
        }

        .cookie-banner h3 {
            margin-top: 0;
        }

        .highlighted-box {
            background: rgba(236, 72, 153, 0.08);
            border-radius: 12px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border-left: 4px solid var(--accent);
        }

        .highlighted-box h3 {
            margin-top: 0;
            color: var(--accent);
        }

        .highlighted-box p:last-child {
            margin-bottom: 0;
        }

        .contact-card {
            background: var(--primary-light);
            border-radius: 12px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .contact-item i {
            color: var(--primary);
            font-size: 1.2rem;
            margin-top: 0.25rem;
        }

        .contact-item div {
            flex: 1;
        }

        .contact-item h4 {
            margin: 0 0 0.25rem 0;
            font-size: 1rem;
            color: var(--foreground);
        }

        .contact-item p {
            margin: 0;
            font-size: 0.9rem;
            color: var(--muted);
        }

        .update-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 3rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            color: var(--muted);
            font-size: 0.85rem;
        }

        .update-info i {
            color: var(--primary);
        }

        .toc-container {
            background: rgba(0, 0, 0, 0.02);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .toc-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--foreground);
        }

        .toc-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .toc-item {
            margin-bottom: 0.75rem;
        }

        .toc-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--muted);
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .toc-link:hover {
            color: var(--primary);
            transform: translateX(3px);
        }

        .toc-link i {
            font-size: 0.75rem;
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
            
            .policy-container {
                padding: 1.5rem;
            }
            
            .data-table {
                display: block;
                overflow-x: auto;
            }
            
            .contact-card {
                padding: 1rem;
            }
            
            .toc-container {
                display: none;
            }
        }
        
        /* Additional Visual Enhancements */
        .policy-section {
            position: relative;
            transition: all 0.3s ease;
        }
        
        .policy-section:target {
            padding-top: 2rem;
            animation: highlight 2s ease-out;
        }
        
        @keyframes highlight {
            0% { background-color: rgba(79, 70, 229, 0.1); }
            100% { background-color: transparent; }
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
            <h1>Privacy Policy</h1>
            <p>How we collect, process, and protect your data in the University Voting System</p>
        </div>

        <div class="policy-container">
            <div class="toc-container">
                <div class="toc-title">Table of Contents</div>
                <ul class="toc-list">
                    <li class="toc-item"><a href="#introduction" class="toc-link"><i class="fas fa-chevron-right"></i> Introduction</a></li>
                    <li class="toc-item"><a href="#data-collection" class="toc-link"><i class="fas fa-chevron-right"></i> Data Collection and Usage</a></li>
                    <li class="toc-item"><a href="#data-storage" class="toc-link"><i class="fas fa-chevron-right"></i> Data Storage and Security</a></li>
                    <li class="toc-item"><a href="#data-rights" class="toc-link"><i class="fas fa-chevron-right"></i> Your Data Rights</a></li>
                    <li class="toc-item"><a href="#cookies" class="toc-link"><i class="fas fa-chevron-right"></i> Cookies and Tracking</a></li>
                    <li class="toc-item"><a href="#third-parties" class="toc-link"><i class="fas fa-chevron-right"></i> Third-Party Services</a></li>
                    <li class="toc-item"><a href="#updates" class="toc-link"><i class="fas fa-chevron-right"></i> Policy Updates</a></li>
                    <li class="toc-item"><a href="#contact" class="toc-link"><i class="fas fa-chevron-right"></i> Contact Information</a></li>
                </ul>
            </div>

            <div id="introduction" class="policy-section">
                <h2>Introduction</h2>
                <p>Welcome to the <?php echo htmlspecialchars($universityName); ?> Student Voting System. We are committed to protecting your privacy and ensuring that your personal data is handled securely and responsibly. This Privacy Policy explains how we collect, use, and safeguard your information when you use our online voting platform.</p>
                <p>By accessing and using the Student Voting System, you consent to the data practices described in this policy. We encourage you to read this document carefully to understand our practices regarding your personal data and how we will treat it.</p>
            </div>

            <div id="data-collection" class="policy-section">
                <h2>Data Collection and Usage</h2>
                <p>We collect and process certain personal information to facilitate the voting process, verify your identity, and ensure the integrity of the election results. Below is a description of the data we collect and how it is used:</p>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Type of Data</th>
                            <th>Purpose</th>
                            <th>Legal Basis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Student ID Number</td>
                            <td>User authentication, verification of voting eligibility</td>
                            <td>Legitimate interest</td>
                        </tr>
                        <tr>
                            <td>Full Name</td>
                            <td>User identification</td>
                            <td>Legitimate interest</td>
                        </tr>
                        <tr>
                            <td>Email Address</td>
                            <td>Communication, vote confirmation</td>
                            <td>Legitimate interest</td>
                        </tr>
                        <tr>
                            <td>Program/Department</td>
                            <td>Determining ballot access for department-specific positions</td>
                            <td>Legitimate interest</td>
                        </tr>
                        <tr>
                            <td>Voting Status</td>
                            <td>Preventing duplicate votes</td>
                            <td>Legitimate interest</td>
                        </tr>
                        <tr>
                            <td>Login Timestamps</td>
                            <td>Security audit, system integrity</td>
                            <td>Legitimate interest</td>
                        </tr>
                    </tbody>
                </table>
                
                <h3>How We Use Your Data</h3>
                <ul>
                    <li><strong>Authentication:</strong> We use your student ID and credentials to verify your identity and ensure only eligible students can access the voting system.</li>
                    <li><strong>Vote Processing:</strong> We record that you have voted to prevent duplicate voting, but we do not link your identity to your ballot selections.</li>
                    <li><strong>Communication:</strong> We may send you email notifications regarding the election process, including confirmation of your vote submission.</li>
                    <li><strong>Improvement:</strong> We analyze anonymized usage data to improve the platform's functionality and user experience.</li>
                    <li><strong>Security:</strong> We monitor system access to detect and prevent unauthorized use or security breaches.</li>
                </ul>
                
                <div class="highlighted-box">
                    <h3>Ballot Anonymity</h3>
                    <p>We employ technical measures to ensure that your specific voting choices cannot be linked back to your identity. While we record that you have participated in the election, the content of your ballot remains anonymous.</p>
                </div>
            </div>

            <div id="data-storage" class="policy-section">
                <h2>Data Storage and Security</h2>
                <p>Protecting your personal information is our priority. We implement robust security measures to prevent unauthorized access, disclosure, alteration, or destruction of your data.</p>
                
                <h3>Security Measures</h3>
                <ul>
                    <li><strong>Encryption:</strong> All data transmitted between your browser and our servers is encrypted using SSL technology.</li>
                    <li><strong>Database Security:</strong> Personal information is stored in secured databases with restricted access.</li>
                    <li><strong>Authentication:</strong> Multiple authentication factors are required for administrative access to the system.</li>
                    <li><strong>Regular Audits:</strong> We conduct security audits and vulnerability assessments to ensure our protection measures remain effective.</li>
                    <li><strong>Vote Segregation:</strong> Voting records are stored separately from voter identity information to ensure ballot secrecy.</li>
                </ul>
                
                <h3>Data Retention</h3>
                <p>We retain your personal information only for as long as necessary to fulfill the purposes outlined in this policy or as required by law. Specific retention periods are as follows:</p>
                <ul>
                    <li><strong>Voter Participation Records:</strong> Retained for one academic year after the election.</li>
                    <li><strong>Login Activity Logs:</strong> Retained for 90 days for security purposes.</li>
                    <li><strong>Aggregated Election Results:</strong> Permanently archived as part of institutional records.</li>
                </ul>
                
                <p>After the retention period expires, personal data is securely deleted or anonymized.</p>
            </div>

            <div id="data-rights" class="policy-section">
                <h2>Your Data Rights</h2>
                <p>As a user of the Student Voting System, you have certain rights regarding your personal data:</p>
                
                <h3>Your Rights Include</h3>
                <ul>
                    <li><strong>Access:</strong> You may request a copy of the personal information we hold about you.</li>
                    <li><strong>Correction:</strong> You may request that we correct inaccurate or incomplete information about you.</li>
                    <li><strong>Deletion:</strong> In certain circumstances, you may request that we delete your personal data.</li>
                    <li><strong>Restriction:</strong> You may request that we restrict the processing of your data under certain conditions.</li>
                    <li><strong>Objection:</strong> You may object to our processing of your personal data where we rely on legitimate interests.</li>
                    <li><strong>Data Portability:</strong> You may request to receive your data in a structured, commonly used format.</li>
                </ul>
                
                <p>Please note that some of these rights may be limited when it comes to election data due to institutional record-keeping requirements and the need to maintain election integrity.</p>
                
                <h3>How to Exercise Your Rights</h3>
                <p>To exercise any of these rights, please contact our Data Protection Officer using the contact information provided at the end of this policy. We will respond to your request within 30 days.</p>
            </div>

            <div id="cookies" class="policy-section">
                <h2>Cookies and Tracking</h2>
                <p>Our voting system uses cookies and similar technologies to enhance your experience and ensure the security of the platform.</p>
                
                <div class="cookie-banner">
                    <h3>Cookies We Use</h3>
                    <ul>
                        <li><strong>Essential Cookies:</strong> These cookies are necessary for the functioning of the voting system, including authentication and session management.</li>
                        <li><strong>Security Cookies:</strong> These help us detect and prevent fraudulent activity and protect the integrity of the voting process.</li>
                        <li><strong>Analytics Cookies:</strong> We use anonymized analytics to improve the system's performance and usability.</li>
                    </ul>
                    <p>We do not use cookies for advertising purposes or to track your online activities outside of our voting system.</p>
                </div>
                
                <h3>Cookie Management</h3>
                <p>You can control cookies through your browser settings. However, disabling essential cookies may affect the functionality of the voting system and could prevent you from casting your vote.</p>
            </div>

            <div id="third-parties" class="policy-section">
                <h2>Third-Party Services</h2>
                <p>We may use trusted third-party services to help us operate the voting system, analyze usage, and communicate with users.</p>
                
                <h3>Service Providers</h3>
                <ul>
                    <li><strong>Authentication Services:</strong> Integration with the university's single sign-on system.</li>
                    <li><strong>Database Management:</strong> Secure storage and processing of election data.</li>
                    <li><strong>Email Services:</strong> For sending vote confirmations and election notifications.</li>
                    <li><strong>Analytics:</strong> For collecting anonymized usage statistics to improve the platform.</li>
                </ul>
                
                <p>All third-party service providers are carefully selected and contractually obligated to ensure the confidentiality and security of your data. They are not permitted to use your personal information for any purpose other than providing the specific services we have contracted them to perform.</p>
                
                <h3>No Data Selling</h3>
                <p>We do not sell, trade, or otherwise transfer your personally identifiable information to outside parties for commercial purposes.</p>
            </div>

            <div id="updates" class="policy-section">
                <h2>Policy Updates</h2>
                <p>We may update this Privacy Policy periodically to reflect changes in our practices or for other operational, legal, or regulatory reasons. When we make changes, we will update the "Last Updated" date at the bottom of this policy.</p>
                
                <p>For significant changes, we will notify you through a prominent notice within the voting system before the changes become effective. We encourage you to review this policy regularly to stay informed about how we are protecting your information.</p>
            </div>

            <div id="contact" class="policy-section">
                <h2>Contact Information</h2>
                <p>If you have questions, concerns, or requests regarding this Privacy Policy or our data practices, please contact us using the information below:</p>
                
                <div class="contact-card">
                    <div class="contact-item">
                        <i class="fas fa-user-shield"></i>
                        <div>
                            <h4>Data Protection Officer</h4>
                            <p>For privacy concerns and data rights requests</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h4>Email</h4>
                            <p><?php echo htmlspecialchars($universityContact); ?></p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <h4>Address</h4>
                            <p><?php echo htmlspecialchars($universityAddress); ?></p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <h4>Phone</h4>
                            <p>(555) 123-4567</p>
                        </div>
                    </div>
                </div>
                
                <p>We strive to respond to all inquiries within 5 business days.</p>
            </div>

            <div class="update-info">
                <i class="fas fa-clock"></i>
                <span>Last Updated: <?php echo htmlspecialchars($lastUpdated); ?></span>
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
        // Smooth scrolling for table of contents links
document.addEventListener('DOMContentLoaded', function() {
    const tocLinks = document.querySelectorAll('.toc-link');
    
    tocLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            window.scrollTo({
                top: targetElement.offsetTop - 100,
                behavior: 'smooth'
            });
        });
    });
    
    // Add entrance animations for sections
    const sections = document.querySelectorAll('.policy-section');
    
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
    
    // Highlight active section while scrolling
    window.addEventListener('scroll', function() {
        const sections = document.querySelectorAll('.policy-section');
        const tocLinks = document.querySelectorAll('.toc-link');
        
        let currentSectionId = '';
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop - 120;
            const sectionHeight = section.offsetHeight;
            
            if (window.scrollY >= sectionTop && window.scrollY <script sectionTop + sectionHeight) {
                currentSectionId = '#' + section.getAttribute('id');
            }
        });
        
        tocLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === currentSectionId) {
                link.classList.add('active');
            }
        });
    });
    
    // Add copy functionality for email addresses
    const emailElements = document.querySelectorAll('.contact-item p');
    emailElements.forEach(element => {
        if (element.textContent.includes('@')) {
            element.style.cursor = 'pointer';
            element.setAttribute('title', 'Click to copy email address');
            
            element.addEventListener('click', function() {
                const email = this.textContent.trim();
                navigator.clipboard.writeText(email).then(() => {
                    // Show temporary tooltip
                    const tooltip = document.createElement('div');
                    tooltip.textContent = 'Email copied!';
                    tooltip.style.position = 'absolute';
                    tooltip.style.left = '50%';
                    tooltip.style.transform = 'translateX(-50%)';
                    tooltip.style.background = 'var(--primary-dark)';
                    tooltip.style.color = 'white';
                    tooltip.style.padding = '5px 10px';
                    tooltip.style.borderRadius = '4px';
                    tooltip.style.fontSize = '0.8rem';
                    tooltip.style.zIndex = '1000';
                    
                    this.parentNode.style.position = 'relative';
                    this.parentNode.appendChild(tooltip);
                    
                    setTimeout(() => {
                        tooltip.remove();
                    }, 2000);
                });
            });
        }
    });
    
    // Back to top button functionality
    const backToTopButton = document.createElement('button');
    backToTopButton.innerHTML = '<i class="fas fa-arrow-up"></i>';
    backToTopButton.className = 'back-to-top';
    backToTopButton.style.position = 'fixed';
    backToTopButton.style.bottom = '20px';
    backToTopButton.style.right = '20px';
    backToTopButton.style.borderRadius = '50%';
    backToTopButton.style.width = '50px';
    backToTopButton.style.height = '50px';
    backToTopButton.style.background = 'var(--primary)';
    backToTopButton.style.color = 'white';
    backToTopButton.style.border = 'none';
    backToTopButton.style.boxShadow = '0 2px 10px rgba(0,0,0,0.2)';
    backToTopButton.style.cursor = 'pointer';
    backToTopButton.style.display = 'none';
    backToTopButton.style.opacity = '0';
    backToTopButton.style.transition = 'opacity 0.3s ease';
    backToTopButton.style.zIndex = '99';
    
    document.body.appendChild(backToTopButton);
    
    backToTopButton.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 500) {
            backToTopButton.style.display = 'block';
            setTimeout(() => {
                backToTopButton.style.opacity = '1';
            }, 10);
        } else {
            backToTopButton.style.opacity = '0';
            setTimeout(() => {
                backToTopButton.style.display = 'none';
            }, 300);
        }
    });
    
    // Add print functionality
    const printButton = document.createElement('button');
    printButton.innerHTML = '<i class="fas fa-print"></i> Print Policy';
    printButton.className = 'print-button';
    printButton.style.background = 'transparent';
    printButton.style.border = '1px solid var(--muted)';
    printButton.style.color = 'var(--muted)';
    printButton.style.padding = '0.5rem 1rem';
    printButton.style.borderRadius = '8px';
    printButton.style.cursor = 'pointer';
    printButton.style.fontSize = '0.85rem';
    printButton.style.display = 'flex';
    printButton.style.alignItems = 'center';
    printButton.style.gap = '0.5rem';
    printButton.style.marginTop = '1rem';
    printButton.style.marginLeft = 'auto';
    printButton.style.transition = 'all 0.3s ease';
    
    printButton.addEventListener('mouseover', function() {
        this.style.background = 'var(--primary-light)';
        this.style.borderColor = 'var(--primary)';
        this.style.color = 'var(--primary)';
    });
    
    printButton.addEventListener('mouseout', function() {
        this.style.background = 'transparent';
        this.style.borderColor = 'var(--muted)';
        this.style.color = 'var(--muted)';
    });
    
    printButton.addEventListener('click', function() {
        window.print();
    });
    
    // Find the update-info div and add the print button before it
    const updateInfo = document.querySelector('.update-info');
    if (updateInfo) {
        updateInfo.parentNode.insertBefore(printButton, updateInfo);
    }
    
    // Add print styles
    const printStyles = document.createElement('style');
    printStyles.innerHTML = `
        @media print {
            .navbar, .back-btn, .footer, .back-to-top, .print-button {
                display: none !important;
            }
            
            .hero-section {
                background: none !important;
                padding: 1rem 0 !important;
                margin: 0 !important;
            }
            
            .hero-section::before {
                display: none !important;
            }
            
            .policy-container {
                box-shadow: none !important;
                padding: 0 !important;
            }
            
            body {
                font-size: 12pt;
                color: black !important;
            }
            
            h1, h2, h3, h4 {
                color: black !important;
                -webkit-text-fill-color: black !important;
            }
            
            .policy-section {
                break-inside: avoid;
                page-break-inside: avoid;
            }
            
            .data-table {
                width: 100% !important;
                border: 1px solid #ddd !important;
            }
            
            .data-table th, .data-table td {
                border: 1px solid #ddd !important;
            }
            
            a {
                text-decoration: none !important;
                color: black !important;
            }
            
            .toc-container {
                page-break-after: always;
            }
        }
    `;
    document.head.appendChild(printStyles);
});
</script>
</body>
</html>

