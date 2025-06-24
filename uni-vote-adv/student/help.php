<?php
// help.php - Help & Support Page
session_start();

// Check if user is logged in
if (!isset($_SESSION['voter_id'])) {
    header("Location: student_login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help & Support - University Voting System</title>
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
            --warning: #f59e0b;
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
            text-align: center;
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

        .help-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .help-section {
            background: var(--surface);
            padding: 2rem;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: 0 2px 4px rgb(0 0 0 / 0.05);
        }

        .help-icon {
            width: 3rem;
            height: 3rem;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }

        .help-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .faq-section {
            background: var(--surface);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .faq-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-bottom: 1px solid var(--border);
        }

        .faq-item {
            border-bottom: 1px solid var(--border);
        }

        .faq-question {
            padding: 1rem 1.5rem;
            background: none;
            border: none;
            width: 100%;
            text-align: left;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.2s;
        }

        .faq-question:hover {
            background: #f8fafc;
        }

        .faq-answer {
            padding: 0 1.5rem 1rem 1.5rem;
            color: var(--text-secondary);
            display: none;
        }

        .faq-answer.active {
            display: block;
        }

        .contact-info {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem;
            border-radius: var(--radius);
            text-align: center;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .contact-item {
            padding: 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: var(--radius);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-white {
            background: white;
            color: var(--primary);
        }

        .btn-white:hover {
            background: #f8fafc;
        }

        @media (max-width: 768px) {
            .help-grid {
                grid-template-columns: 1fr;
            }

            .contact-grid {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 0 0.5rem;
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
            <h1><i class="fas fa-life-ring"></i> Help & Support</h1>
            <p>Get assistance with the University Voting System</p>
        </div>

        <div class="help-grid">
            <div class="help-section">
                <div class="help-icon" style="background: var(--info);">
                    <i class="fas fa-question-circle"></i>
                </div>
                <h3 class="help-title">How to Vote</h3>
                <p>Learn the step-by-step process of participating in university elections, from accessing the ballot to confirming your vote.</p>
                <ul style="margin-top: 1rem; padding-left: 1rem;">
                    <li>Log in to your student account</li>
                    <li>View available elections on your dashboard</li>
                    <li>Click on an active election to view details</li>
                    <li>Review candidates and make your selection</li>
                    <li>Confirm and submit your vote</li>
                </ul>
            </div>

            <div class="help-section">
                <div class="help-icon" style="background: var(--success);">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 class="help-title">Vote Security</h3>
                <p>Your vote is completely secure and anonymous. We use advanced encryption and security measures to protect your privacy.</p>
                <ul style="margin-top: 1rem; padding-left: 1rem;">
                    <li>All votes are encrypted and anonymized</li>
                    <li>No one can see how you voted</li>
                    <li>Secure authentication prevents fraud</li>
                    <li>Regular security audits ensure integrity</li>
                </ul>
            </div>

            <div class="help-section">
                <div class="help-icon" style="background: var(--warning);">
                    <i class="fas fa-clock"></i>
                </div>
                <h3 class="help-title">Voting Deadlines</h3>
                <p>Important information about election schedules and deadlines to ensure you don't miss your opportunity to vote.</p>
                <ul style="margin-top: 1rem; padding-left: 1rem;">
                    <li>Check election start and end dates</li>
                    <li>Vote early to avoid last-minute issues</li>
                    <li>Receive email notifications for reminders</li>
                    <li>Contact support if you miss the deadline</li>
                </ul>
            </div>

            <div class="help-section">
                <div class="help-icon" style="background: var(--secondary);">
                    <i class="fas fa-user-check"></i>
                </div>
                <h3 class="help-title">Eligibility</h3>
                <p>Understand which elections you're eligible to participate in based on your academic program and year.</p>
                <ul style="margin-top: 1rem; padding-left: 1rem;">
                    <li>Faculty-specific elections</li>
                    <li>Year-level requirements</li>
                    <li>Program-based eligibility</li>
                    <li>University-wide elections</li>
                </ul>
            </div>
        </div>

        <div class="faq-section">
            <div class="faq-header">
                <h3><i class="fas fa-comments"></i> Frequently Asked Questions</h3>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    Can I change my vote after submitting it?
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    No, once you submit your vote, it cannot be changed. This ensures the integrity of the election process. Please review your choices carefully before submitting.
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    What if I'm having technical difficulties?
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    If you experience any technical issues, please contact our IT support team immediately. We recommend using a modern web browser and ensuring you have a stable internet connection.
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    How do I know if my vote was counted?
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    After submitting your vote, you'll receive a confirmation message. You can also check your voting history on your dashboard to see all elections you've participated in.
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    What happens if I miss the voting deadline?
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    Unfortunately, votes cannot be accepted after the election deadline. We strongly recommend voting early and setting reminders for important election dates.
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    Can I vote from my mobile device?
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    Yes, the voting system is fully responsive and works on mobile devices, tablets, and desktop computers. We recommend using the latest version of your browser for the best experience.
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">
                    Who can see the election results?
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    Election results are published after the voting period ends and are visible to all eligible voters. Individual votes remain anonymous and cannot be traced back to specific voters.
                </div>
            </div>
        </div>

        <div class="contact-info" style="margin-top: 2rem;">
            <h3><i class="fas fa-headset"></i> Need More Help?</h3>
            <p>Our support team is here to assist you with any questions or issues</p>
            
            <div class="contact-grid">
                <div class="contact-item">
                    <i class="fas fa-envelope" style="font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                    <h4>Email Support</h4>
                    <p>voting-support@university.edu</p>
                </div>
                
                <div class="contact-item">
                    <i class="fas fa-phone" style="font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                    <h4>Phone Support</h4>
                    <p>+1 (555) 123-VOTE</p>
                </div>
                
                <div class="contact-item">
                    <i class="fas fa-clock" style="font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                    <h4>Support Hours</h4>
                    <p>Mon-Fri: 8AM-6PM</p>
                </div>
                
                <div class="contact-item">
                    <i class="fas fa-map-marker-alt" style="font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                    <h4>Office Location</h4>
                    <p>Student Services Building</p>
                </div>
            </div>
            
            <div style="margin-top: 1.5rem;">
                <a href="mailto:voting-support@university.edu" class="btn btn-white">
                    <i class="fas fa-envelope"></i>
                    Contact Support
                </a>
            </div>
        </div>
    </div>

    <script>
        function toggleFaq(button) {
            const answer = button.nextElementSibling;
            const icon = button.querySelector('i');
            
            // Close all other FAQ items
            document.querySelectorAll('.faq-answer').forEach(item => {
                if (item !== answer) {
                    item.classList.remove('active');
                }
            });
            
            document.querySelectorAll('.faq-question i').forEach(item => {
                if (item !== icon) {
                    item.style.transform = 'rotate(0deg)';
                }
            });
            
            // Toggle current FAQ item
            answer.classList.toggle('active');
            
            if (answer.classList.contains('active')) {
                icon.style.transform = 'rotate(180deg)';
            } else {
                icon.style.transform = 'rotate(0deg)';
            }
        }

        // Add smooth transitions
        document.querySelectorAll('.faq-question i').forEach(icon => {
            icon.style.transition = 'transform 0.3s ease';
        });
    </script>
</body>
</html>