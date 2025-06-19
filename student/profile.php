<?php
// profile.php - Student Profile Management
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

// Get current voter information
$stmt = $db->prepare("SELECT * FROM voters WHERE id = :id");
$stmt->execute(['id' => $_SESSION['voter_id']]);
$voter = $stmt->fetch();

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        
        // Basic validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } else {
            // Update profile
            $stmt = $db->prepare("UPDATE voters SET phone = :phone, email = :email WHERE id = :id");
            $stmt->execute([
                'phone' => $phone,
                'email' => $email,
                'id' => $_SESSION['voter_id']
            ]);
            
            $message = "Profile updated successfully!";
            
            // Refresh voter data
            $stmt = $db->prepare("SELECT * FROM voters WHERE id = :id");
            $stmt->execute(['id' => $_SESSION['voter_id']]);
            $voter = $stmt->fetch();
        }
    } catch(PDOException $e) {
        $error = "Error updating profile: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - University Voting System</title>
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
            --error: #ef4444;
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
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .header {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }

        .form-container {
            background: var(--surface);
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
        }

        input[type="text"], input[type="email"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        input[type="text"]:focus, input[type="email"]:focus {
            outline: none;
            border-color: var(--primary);
        }

        .readonly {
            background: #f8fafc;
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
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>

        <div class="header">
            <h1><i class="fas fa-user-circle"></i> My Profile</h1>
            <p>Manage your personal information and account settings</p>
        </div>

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

        <div class="form-container">
            <form method="POST">
                <div class="form-group">
                    <label>Student ID</label>
                    <input type="text" value="<?php echo htmlspecialchars($voter['student_id']); ?>" readonly class="readonly">
                </div>

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" value="<?php echo htmlspecialchars($voter['full_name']); ?>" readonly class="readonly">
                </div>

                <div class="form-group">
                    <label>Program</label>
                    <input type="text" value="<?php echo htmlspecialchars($voter['program']); ?>" readonly class="readonly">
                </div>

                <div class="form-group">
                    <label>Year</label>
                    <input type="text" value="<?php echo htmlspecialchars($voter['year']); ?>" readonly class="readonly">
                </div>

                <div class="form-group">
                    <label>Faculty</label>
                    <input type="text" value="<?php echo htmlspecialchars($voter['faculty']); ?>" readonly class="readonly">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($voter['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($voter['phone'] ?? ''); ?>" placeholder="Enter your phone number">
                </div>

                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <button type="submit" class="btn">
                        <i class="fas fa-save"></i>
                        Update Profile
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>