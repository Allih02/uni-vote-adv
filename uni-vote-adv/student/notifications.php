<?php
// notifications.php - Notification Settings
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

// Get voter information
$stmt = $db->prepare("SELECT * FROM voters WHERE id = :id");
$stmt->execute(['id' => $_SESSION['voter_id']]);
$voter = $stmt->fetch();

$message = '';
$error = '';

// Get current notification preferences (simulated for this example)
$notification_preferences = [
    'email_new_elections' => true,
    'email_voting_reminders' => true,
    'email_results' => true,
    'sms_urgent_updates' => false,
    'browser_notifications' => true
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // In a real implementation, you would save these to a database
        $notification_preferences = [
            'email_new_elections' => isset($_POST['email_new_elections']),
            'email_voting_reminders' => isset($_POST['email_voting_reminders']),
            'email_results' => isset($_POST['email_results']),
            'sms_urgent_updates' => isset($_POST['sms_urgent_updates']),
            'browser_notifications' => isset($_POST['browser_notifications'])
        ];
        
        $message = "Notification preferences updated successfully!";
    } catch(Exception $e) {
        $error = "Error updating preferences: " . $e->getMessage();
    }
}

// Get recent notifications (simulated)
$recent_notifications = [
    [
        'id' => 1,
        'title' => 'New Election Available',
        'message' => 'Student Council elections are now open for voting.',
        'type' => 'election',
        'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
        'read' => false
    ],
    [
        'id' => 2,
        'title' => 'Voting Reminder',
        'message' => 'Don\'t forget to vote in the Faculty Representative election ending tomorrow.',
        'type' => 'reminder',
        'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'read' => true
    ],
    [
        'id' => 3,
        'title' => 'Election Results Available',
        'message' => 'Results for the Student Union election are now available.',
        'type' => 'results',
        'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
        'read' => true
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - University Voting System</title>
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
            --error: #ef4444;
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

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .settings-section, .notifications-section {
            background: var(--surface);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: 0 2px 4px rgb(0 0 0 / 0.05);
        }

        .section-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .section-content {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .toggle-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            margin-bottom: 1rem;
            transition: all 0.2s;
        }

        .toggle-group:hover {
            background: #f8fafc;
        }

        .toggle-info {
            flex: 1;
        }

        .toggle-title {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .toggle-description {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .toggle-switch {
            position: relative;
            width: 48px;
            height: 24px;
            background: #cbd5e1;
            border-radius: 12px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .toggle-switch.active {
            background: var(--primary);
        }

        .toggle-switch::before {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            top: 2px;
            left: 2px;
            transition: transform 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .toggle-switch.active::before {
            transform: translateX(24px);
        }

        .notification-item {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            transition: background 0.2s;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item:hover {
            background: #f8fafc;
        }

        .notification-item.unread {
            background: #eff6ff;
        }

        .notification-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: white;
            flex-shrink: 0;
        }

        .icon-election { background: var(--info); }
        .icon-reminder { background: var(--warning); }
        .icon-results { background: var(--success); }

        .notification-content {
            flex: 1;
            min-width: 0;
        }

        .notification-title {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .notification-message {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .notification-time {
            font-size: 0.75rem;
            color: var(--text-secondary);
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

        .btn-secondary {
            background: var(--surface);
            color: var(--text-primary);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: #f1f5f9;
        }

        .alert {
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
        }

        .empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 0 0.5rem;
            }

            .toggle-group {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .notification-item {
                flex-direction: column;
                gap: 0.5rem;
            }

            .notification-icon {
                align-self: flex-start;
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
            <h1><i class="fas fa-bell"></i> Notifications & Settings</h1>
            <p>Manage your notification preferences and view recent updates</p>
        </div>

        <div class="content-grid">
            <!-- Notification Settings -->
            <div class="settings-section">
                <div class="section-header">
                    <h3 class="section-title">Notification Preferences</h3>
                    <p style="color: var(--text-secondary); font-size: 0.875rem;">Choose how you want to receive updates</p>
                </div>
                
                <div class="section-content">
                    <?php if ($message): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="toggle-group">
                            <div class="toggle-info">
                                <div class="toggle-title">New Election Notifications</div>
                                <div class="toggle-description">Get notified when new elections become available</div>
                            </div>
                            <div class="toggle-switch <?php echo $notification_preferences['email_new_elections'] ? 'active' : ''; ?>" 
                                 onclick="toggleSwitch(this, 'email_new_elections')">
                                <input type="checkbox" name="email_new_elections" style="display: none;" 
                                       <?php echo $notification_preferences['email_new_elections'] ? 'checked' : ''; ?>>
                            </div>
                        </div>

                        <div class="toggle-group">
                            <div class="toggle-info">
                                <div class="toggle-title">Voting Reminders</div>
                                <div class="toggle-description">Receive reminders before elections end</div>
                            </div>
                            <div class="toggle-switch <?php echo $notification_preferences['email_voting_reminders'] ? 'active' : ''; ?>" 
                                 onclick="toggleSwitch(this, 'email_voting_reminders')">
                                <input type="checkbox" name="email_voting_reminders" style="display: none;" 
                                       <?php echo $notification_preferences['email_voting_reminders'] ? 'checked' : ''; ?>>
                            </div>
                        </div>

                        <div class="toggle-group">
                            <div class="toggle-info">
                                <div class="toggle-title">Election Results</div>
                                <div class="toggle-description">Get notified when election results are published</div>
                            </div>
                            <div class="toggle-switch <?php echo $notification_preferences['email_results'] ? 'active' : ''; ?>" 
                                 onclick="toggleSwitch(this, 'email_results')">
                                <input type="checkbox" name="email_results" style="display: none;" 
                                       <?php echo $notification_preferences['email_results'] ? 'checked' : ''; ?>>
                            </div>
                        </div>

                        <div class="toggle-group">
                            <div class="toggle-info">
                                <div class="toggle-title">SMS Updates</div>
                                <div class="toggle-description">Receive urgent updates via SMS</div>
                            </div>
                            <div class="toggle-switch <?php echo $notification_preferences['sms_urgent_updates'] ? 'active' : ''; ?>" 
                                 onclick="toggleSwitch(this, 'sms_urgent_updates')">
                                <input type="checkbox" name="sms_urgent_updates" style="display: none;" 
                                       <?php echo $notification_preferences['sms_urgent_updates'] ? 'checked' : ''; ?>>
                            </div>
                        </div>

                        <div class="toggle-group">
                            <div class="toggle-info">
                                <div class="toggle-title">Browser Notifications</div>
                                <div class="toggle-description">Show notifications in your browser</div>
                            </div>
                            <div class="toggle-switch <?php echo $notification_preferences['browser_notifications'] ? 'active' : ''; ?>" 
                                 onclick="toggleSwitch(this, 'browser_notifications')">
                                <input type="checkbox" name="browser_notifications" style="display: none;" 
                                       <?php echo $notification_preferences['browser_notifications'] ? 'checked' : ''; ?>>
                            </div>
                        </div>

                        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                            <button type="submit" class="btn">
                                <i class="fas fa-save"></i>
                                Save Preferences
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="requestNotificationPermission()">
                                <i class="fas fa-bell"></i>
                                Enable Browser Notifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Recent Notifications -->
            <div class="notifications-section">
                <div class="section-header">
                    <h3 class="section-title">Recent Notifications</h3>
                    <p style="color: var(--text-secondary); font-size: 0.875rem;">Your latest updates and alerts</p>
                </div>
                
                <div class="section-content" style="padding: 0;">
                    <?php if (empty($recent_notifications)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-bell-slash"></i>
                            </div>
                            <p><strong>No notifications yet</strong></p>
                            <small>You'll see election updates and reminders here</small>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_notifications as $notification): ?>
                        <div class="notification-item <?php echo !$notification['read'] ? 'unread' : ''; ?>">
                            <div class="notification-icon icon-<?php echo $notification['type']; ?>">
                                <?php
                                $icons = [
                                    'election' => 'fa-vote-yea',
                                    'reminder' => 'fa-clock',
                                    'results' => 'fa-chart-bar'
                                ];
                                ?>
                                <i class="fas <?php echo $icons[$notification['type']]; ?>"></i>
                            </div>
                            <div class="notification-content">
                                <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                                <div class="notification-time">
                                    <i class="fas fa-clock"></i>
                                    <?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSwitch(element, inputName) {
            element.classList.toggle('active');
            const checkbox = element.querySelector('input') || element.parentNode.querySelector(`input[name="${inputName}"]`);
            if (checkbox) {
                checkbox.checked = element.classList.contains('active');
            }
        }

        function requestNotificationPermission() {
            if ('Notification' in window) {
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        new Notification('University Voting System', {
                            body: 'Browser notifications are now enabled!',
                            icon: '/favicon.ico'
                        });
                    }
                });
            } else {
                alert('Your browser does not support notifications');
            }
        }

        // Check notification permission status
        document.addEventListener('DOMContentLoaded', function() {
            if ('Notification' in window && Notification.permission === 'granted') {
                console.log('Browser notifications are enabled');
            }
        });
    </script>
</body>
</html>