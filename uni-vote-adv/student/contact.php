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
    $stmt = $pdo->prepare("SELECT username, full_name, program, email FROM users WHERE id = ?");
    $stmt->execute([$studentId]);
    $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userInfo) {
        // Fallback if user not found
        $userInfo = [
            'username' => 'User',
            'full_name' => 'Student',
            'program' => 'University Program',
            'email' => 'student@university.edu'
        ];
    }
    
} catch (PDOException $e) {
    // Fallback data if there's an error
    $userInfo = [
        'username' => 'User',
        'full_name' => 'Student',
        'program' => 'University Program',
        'email' => 'student@university.edu'
    ];
}

// Process form submission
$messageSent = false;
$formError = false;
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requiredFields = ['subject', 'message', 'categoryId'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        $formError = true;
        $errorMessage = 'Please fill in all required fields.';
    } else {
        try {
            // Sanitize inputs
            $subject = htmlspecialchars($_POST['subject']);
            $message = htmlspecialchars($_POST['message']);
            $categoryId = intval($_POST['categoryId']);
            
            // Insert support ticket into database
            $stmt = $pdo->prepare("INSERT INTO support_tickets (user_id, subject, message, category_id, created_at, status) VALUES (?, ?, ?, ?, NOW(), 'Open')");
            $stmt->execute([$studentId, $subject, $message, $categoryId]);
            
            // Set success flag
            $messageSent = true;
            
            // Optional: Send email notification to support team
            // mail("support@university.edu", "New Support Ticket: $subject", "From: {$userInfo['full_name']}\nStudent ID: $studentId\n\n$message");
            
        } catch (PDOException $e) {
            $formError = true;
            $errorMessage = 'There was a problem submitting your request. Please try again later.';
        }
    }
}

// Get support categories (if needed)
try {
    $stmt = $pdo->prepare("SELECT id, name FROM support_categories ORDER BY display_order");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fallback data
    $categories = [
        ['id' => 1, 'name' => 'Technical Issue'],
        ['id' => 2, 'name' => 'Account Access'],
        ['id' => 3, 'name' => 'Voting Process'],
        ['id' => 4, 'name' => 'Candidate Information'],
        ['id' => 5, 'name' => 'Election Rules & Guidelines'],
        ['id' => 6, 'name' => 'Other']
    ];
}

// Get FAQ data (if needed)
$faqs = [
    [
        'question' => 'How long does it take to get a response?',
        'answer' => 'We aim to respond to all support inquiries within 24 hours during election periods, and within 48 hours at other times.'
    ],
    [
        'question' => 'I forgot my password. How can I reset it?',
        'answer' => 'You can reset your password by clicking the "Forgot Password" link on the login page and following the instructions sent to your university email.'
    ],
    [
        'question' => 'The voting system is not loading properly. What should I do?',
        'answer' => 'Try clearing your browser cache and cookies, then restart your browser. If the issue persists, try using a different browser or device.'
    ],
    [
        'question' => 'Can I change my vote after submitting it?',
        'answer' => 'No, votes are final once submitted. Please review your selections carefully before confirming your vote.'
    ],
    [
        'question' => 'When will the election results be announced?',
        'answer' => 'Election results will be announced on the dashboard within 24 hours after the voting period ends.'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Support - Student Elections</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4F46E5;
            --primary-dark: #4338CA;
            --primary-light: #C7D2FE;
            --secondary: #8B5CF6;
            --accent: #EC4899;
            --success: #10B981;
            --error: #EF4444;
            --warning: #F59E0B;
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

        .main-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        @media (min-width: 992px) {
            .main-container {
                grid-template-columns: 3fr 2fr;
            }
        }

        .contact-card {
            background: var(--card);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: var(--card-shadow);
        }

        .contact-card h2 {
            font-family: var(--heading-font);
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--foreground);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--foreground);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .select-wrapper {
            position: relative;
        }

        .select-wrapper::after {
            content: '\f107';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            pointer-events: none;
        }

        select.form-control {
            appearance: none;
            padding-right: 2.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(79, 70, 229, 0.3);
        }

        .btn-block {
            width: 100%;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border-left: 4px solid var(--success);
            color: var(--success);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border-left: 4px solid var(--error);
            color: var(--error);
        }

        .alert-icon {
            font-size: 1.2rem;
            margin-top: 0.2rem;
        }

        .alert-content {
            flex: 1;
        }

        .alert-content h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .alert-content p {
            font-size: 0.9rem;
            margin: 0;
        }

        .faq-section {
            background: var(--card);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: var(--card-shadow);
        }

        .faq-section h2 {
            font-family: var(--heading-font);
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--foreground);
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
            gap: 0.75rem;
            cursor: pointer;
            user-select: none;
        }

        .faq-question i {
            color: var(--primary);
            transition: transform 0.3s ease;
        }

        .faq-answer {
            display: none;
            padding-left: 1.75rem;
            color: var(--muted);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .faq-item.active .faq-answer {
            display: block;
            animation: fadeIn 0.5s ease forwards;
        }

        .faq-item.active .faq-question i {
            transform: rotate(180deg);
        }

        .contact-methods {
            margin-top: 2.5rem;
        }

        .contact-methods h3 {
            font-family: var(--heading-font);
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: var(--foreground);
        }

        .contact-method-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .contact-method-card {
            background: rgba(255, 255, 255, 0.8);
            border-radius: 12px;
            padding: 1.25rem;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .contact-method-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .contact-method-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 50%;
            font-size: 1.5rem;
        }

        .contact-method-title {
            font-weight: 600;
            color: var(--foreground);
            margin-bottom: 0.5rem;
        }

        .contact-method-info {
            color: var(--muted);
            font-size: 0.9rem;
        }

        .support-hours {
            margin-top: 2rem;
            background: rgba(79, 70, 229, 0.05);
            border-radius: 12px;
            padding: 1.25rem;
        }

        .support-hours h3 {
            font-family: var(--heading-font);
            font-size: 1.1rem;
            margin-bottom: 0.75rem;
            color: var(--foreground);
        }

        .hours-grid {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 0.5rem 1.5rem;
            font-size: 0.9rem;
        }

        .day {
            font-weight: 500;
            color: var(--foreground);
        }

        .time {
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

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
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
            
            .contact-card,
            .faq-section {
                padding: 1.5rem;
            }
            
            .contact-method-grid {
                grid-template-columns: 1fr;
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
            <h1>Contact Support</h1>
            <p>Need help with the voting system? Our support team is here to assist you.</p>
        </div>

        <div class="main-container">
            <div class="contact-form-section">
                <div class="contact-card">
                    <h2>Submit a Support Request</h2>
                    
                    <?php if ($messageSent): ?>
                    <div class="alert alert-success">
                        <div class="alert-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="alert-content">
                            <h3>Request Submitted!</h3>
                            <p>Thank you for contacting us. We've received your support request and will respond to you as soon as possible.</p>
                        </div>
                    </div>
                    <?php elseif ($formError): ?>
                    <div class="alert alert-error">
                        <div class="alert-icon">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="alert-content">
                            <h3>Submission Error</h3>
                            <p><?php echo $errorMessage; ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="contact.php" id="contactForm">
                        <div class="form-group">
                            <label for="name">Your Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($userInfo['full_name']); ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($userInfo['email']); ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="categoryId">Support Category</label>
                            <div class="select-wrapper">
                                <select class="form-control" id="categoryId" name="categoryId" required>
                                    <option value="">Select a category</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" placeholder="Brief description of your issue" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea class="form-control" id="message" name="message" placeholder="Please provide details about your issue..." required></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-paper-plane"></i>
                            Submit Request
                        </button>
                    </form>
                    
                    <div class="contact-methods">
                        <h3>Other Ways to Contact Us</h3>
                        <div class="contact-method-grid">
                            <div class="contact-method-card">
                                <div class="contact-method-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="contact-method-title">Email</div>
                                <div class="contact-method-info">support@university.edu</div>
                            </div>
                            
                            <div class="contact-method-card">
                                <div class="contact-method-icon">
                                    <i class="fas fa-phone-alt"></i>
                                </div>
                                <div class="contact-method-title">Phone</div>
                                <div class="contact-method-info">(555) 123-4567</div>
                            </div>
                            
                            <div class="contact-method-card">
                                <div class="contact-method-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="contact-method-title">Office</div>
                                <div class="contact-method-info">Student Center, Room 204</div>
                            </div>
                        </div>
                        
                        <div class="support-hours">
                            <h3>Support Hours</h3>
                            <div class="hours-grid">
                                <div class="day">Monday - Friday:</div>
                                <div class="time">8:00 AM - 6:00 PM</div>
                                
                                <div class="day">Saturday:</div>
                                <div class="time">10:00 AM - 2:00 PM</div>
                                
                                <div class="day">Sunday:</div>
                                <div class="time">Closed</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="faq-section">
                <h2>Frequently Asked Questions</h2>
                
                <?php foreach ($faqs as $index => $faq): ?>
                <div class="faq-item" id="faq-<?php echo $index; ?>">
                    <div class="faq-question" onclick="toggleFaq(<?php echo $index; ?>)">
                        <i class="fas fa-chevron-down"></i>
                        <span><?php echo htmlspecialchars($faq['question']); ?></span>
                    </div>
                    <div class="faq-answer">
                        <p><?php echo htmlspecialchars($faq['answer']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
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
        // Toggle FAQ items
        function toggleFaq(id) {
            const faqItem = document.getElementById('faq-' + id);
            
            // Close all other FAQs
            document.querySelectorAll('.faq-item').forEach(item => {
                if (item.id !== 'faq-' + id) {
                    item.classList.remove('active');
                }
            });
            
            // Toggle the clicked FAQ
            faqItem.classList.toggle('active');
        }
        
        // Auto open first FAQ item
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('faq-0').classList.add('active');
            
            // Add entrance animations
            const elements = document.querySelectorAll('.contact-card, .faq-section, .contact-method-card');
            
            elements.forEach((element, index) => {
                element.style.opacity = 0;
                element.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    element.style.transition = `all 0.5s ease ${index * 0.1}s`;
                    element.style.opacity = 1;
                    element.style.transform = 'translateY(0)';
                }, 100);
            });
            
            // Form field focus effects
            const formControls = document.querySelectorAll('.form-control');
            
            formControls.forEach(control => {
                control.addEventListener('focus', function() {
                    this.parentElement.querySelector('label')?.classList.add('focused');
                });
                
                control.addEventListener('blur', function() {
                    if (!this.value) {
                        this.parentElement.querySelector('label')?.classList.remove('focused');
                    }
                });
                
                // Check if control already has value on page load
                if (control.value) {
                    control.parentElement.querySelector('label')?.classList.add('focused');
                }
            });
            
            // Form validation
            const contactForm = document.getElementById('contactForm');
            
            if (contactForm) {
                contactForm.addEventListener('submit', function(e) {
                    const required = this.querySelectorAll('[required]');
                    let valid = true;
                    
                    required.forEach(field => {
                        if (!field.value.trim()) {
                            valid = false;
                            field.classList.add('error');
                            field.parentElement.classList.add('has-error');
                        } else {
                            field.classList.remove('error');
                            field.parentElement.classList.remove('has-error');
                        }
                    });
                    
                    if (!valid) {
                        e.preventDefault();
                        // Scroll to first error
                        const firstError = this.querySelector('.error');
                        if (firstError) {
                            firstError.focus();
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>