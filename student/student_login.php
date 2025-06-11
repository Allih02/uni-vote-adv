<?php
// student_login.php - Enhanced Student Login with Default Password Handling
session_start();

// Database Configuration
class Database {
    private $host = 'localhost';
    private $db_name = 'voting_system';
    private $username = 'root';  // Change to your MySQL username
    private $password = '';      // Change to your MySQL password
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
            return null;
        }

        return $this->conn;
    }
}

// Helper functions
function executeQuery($sql, $params = []) {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) return false;
    
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

function getVoterByStudentId($student_id) {
    $sql = "SELECT * FROM voters WHERE student_id = :student_id";
    return fetchOne($sql, ['student_id' => $student_id]);
}

function updateRecord($table, $data, $condition, $conditionParams = []) {
    $setClause = [];
    foreach (array_keys($data) as $key) {
        $setClause[] = "{$key} = :{$key}";
    }
    $setClause = implode(', ', $setClause);
    
    $sql = "UPDATE {$table} SET {$setClause} WHERE {$condition}";
    $params = array_merge($data, $conditionParams);
    
    return executeQuery($sql, $params);
}

function setupDefaultPassword($voter, $password) {
    // Hash the password and update the voter record
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    return updateRecord('voters', ['password' => $hashedPassword], 'id = :id', ['id' => $voter['id']]);
}

function authenticateVoter($student_id, $password) {
    $voter = getVoterByStudentId($student_id);
    
    if (!$voter) {
        return false;
    }
    
    // Handle cases where password is not set or is the student_id (default)
    if (empty($voter['password']) || $voter['password'] === $voter['student_id']) {
        // Set up default password as the student ID
        if ($password === $student_id) {
            setupDefaultPassword($voter, $student_id);
            $voter['password'] = password_hash($student_id, PASSWORD_DEFAULT);
            
            // Update last login
            updateRecord('voters', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $voter['id']]);
            return $voter;
        }
        return false;
    }
    
    // Normal password verification
    if (password_verify($password, $voter['password'])) {
        // Update last login
        updateRecord('voters', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $voter['id']]);
        return $voter;
    }
    
    // Fallback: check if password matches student_id (for initial setup)
    if ($password === $student_id) {
        setupDefaultPassword($voter, $student_id);
        updateRecord('voters', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $voter['id']]);
        return $voter;
    }
    
    return false;
}

// Check database connection
$database = new Database();
$db_connection = $database->getConnection();
$db_error = null;

if (!$db_connection) {
    $db_error = "Cannot connect to database. Please check your database configuration.";
}

// Redirect if already logged in
if (isset($_SESSION['voter_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error_message = '';
$success_message = '';

// Handle logout message
if (isset($_GET['message']) && $_GET['message'] === 'logged_out') {
    $success_message = 'You have been logged out successfully.';
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id']);
    $password = trim($_POST['password']);
    
    if ($db_error) {
        $error_message = $db_error;
    } elseif (empty($student_id) || empty($password)) {
        $error_message = 'Please enter both Student ID and password.';
    } else {
        // Authenticate voter
        $voter = authenticateVoter($student_id, $password);
        
        if ($voter) {
            if ($voter['status'] === 'active') {
                // Set session variables
                $_SESSION['voter_id'] = $voter['id'];
                $_SESSION['student_id'] = $voter['student_id'];
                $_SESSION['full_name'] = $voter['full_name'];
                $_SESSION['email'] = $voter['email'];
                $_SESSION['program'] = $voter['program'];
                $_SESSION['year'] = $voter['year'];
                $_SESSION['faculty'] = $voter['faculty'];
                $_SESSION['status'] = $voter['status'];
                
                header("Location: dashboard.php");
                exit();
            } else {
                $error_message = 'Your account is not active. Please contact the administrator.';
            }
        } else {
            $error_message = 'Invalid Student ID or password. Use your Student ID as both username and password.';
        }
    }
}

// Get sample voters for demo
$sample_voters = [];
if ($db_connection) {
    $stmt = executeQuery("SELECT student_id, full_name FROM voters WHERE status = 'active' LIMIT 3");
    if ($stmt) {
        $sample_voters = $stmt->fetchAll();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - University Voting System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #3730a3;
            --primary-light: #a5b4fc;
            --secondary: #7c3aed;
            --background: #f8fafc;
            --surface: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --border: #e2e8f0;
            --error: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
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
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .login-container {
            background: var(--surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            width: 100%;
            max-width: 420px;
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .login-logo {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .login-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .login-form {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-size: 1rem;
            transition: all 0.2s ease;
            background: var(--surface);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .input-group .form-control {
            padding-left: 3rem;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0.25rem;
        }

        .password-toggle:hover {
            color: var(--text-primary);
        }

        .error-message {
            background: #fef2f2;
            color: #991b1b;
            padding: 0.875rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            border: 1px solid #fecaca;
        }

        .success-message {
            background: #ecfdf5;
            color: #065f46;
            padding: 0.875rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            border: 1px solid #a7f3d0;
        }

        .warning-message {
            background: #fffbeb;
            color: #92400e;
            padding: 0.875rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            border: 1px solid #fde68a;
        }

        .btn {
            width: 100%;
            padding: 0.875rem 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .login-footer {
            padding: 1rem 2rem 2rem;
            text-align: center;
            border-top: 1px solid var(--border);
            background: var(--background);
        }

        .help-text {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .help-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .help-link:hover {
            text-decoration: underline;
        }

        .demo-credentials {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: var(--radius);
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .demo-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .demo-list {
            list-style: none;
            padding: 0;
        }

        .demo-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            padding: 0.5rem;
            background: white;
            border-radius: 0.375rem;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .demo-item:hover {
            background: #e0f2fe;
            transform: translateX(2px);
        }

        .demo-label {
            color: var(--text-secondary);
            font-weight: 500;
        }

        .demo-value {
            color: var(--text-primary);
            font-weight: 600;
            font-family: monospace;
        }

        .info-box {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: var(--radius);
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            color: #92400e;
        }

        .info-box strong {
            color: #78350f;
        }

        .setup-link {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            background: var(--warning);
            color: white;
            text-decoration: none;
            border-radius: var(--radius);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .setup-link:hover {
            background: #d97706;
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 0.5rem;
            }
            
            .login-header {
                padding: 1.5rem;
            }
            
            .login-form {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">
                <i class="fas fa-vote-yea"></i>
            </div>
            <h1 class="login-title">Student Portal</h1>
            <p class="login-subtitle">University Voting System</p>
        </div>

        <form class="login-form" method="POST">
            <?php if ($db_error): ?>
                <div class="warning-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    Database connection failed. Please set up the database first.
                    <a href="setup_database.php" class="setup-link">Setup Database</a>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <div class="info-box">
                <strong><i class="fas fa-info-circle"></i> Default Login:</strong><br>
                Use your <strong>Student ID</strong> as both username and password. 
                <?php if (empty($sample_voters)): ?>
                Example: ST2024001 / ST2024001
                <?php endif; ?>
            </div>

            <!-- Demo Credentials -->
            <?php if (!empty($sample_voters)): ?>
            <div class="demo-credentials">
                <div class="demo-title">
                    <i class="fas fa-users"></i>
                    Available Demo Accounts:
                </div>
                <ul class="demo-list">
                    <?php foreach ($sample_voters as $voter): ?>
                    <li class="demo-item" onclick="fillCredentials('<?php echo htmlspecialchars($voter['student_id']); ?>')">
                        <span class="demo-label"><?php echo htmlspecialchars($voter['full_name']); ?></span>
                        <span class="demo-value"><?php echo htmlspecialchars($voter['student_id']); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <small style="color: var(--text-muted); font-style: italic;">
                    <i class="fas fa-hand-pointer"></i> Click any account above to auto-fill credentials
                </small>
            </div>
            <?php else: ?>
            <div class="demo-credentials">
                <div class="demo-title">
                    <i class="fas fa-user-circle"></i>
                    Default Demo Account:
                </div>
                <div class="demo-item" onclick="fillCredentials('ST2024001')">
                    <span class="demo-label">Student ID:</span>
                    <span class="demo-value">ST2024001</span>
                </div>
                <div class="demo-item" onclick="fillCredentials('ST2024001')">
                    <span class="demo-label">Password:</span>
                    <span class="demo-value">ST2024001</span>
                </div>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="student_id" class="form-label">Student ID</label>
                <div class="input-group">
                    <i class="fas fa-id-card input-icon"></i>
                    <input type="text" 
                           id="student_id" 
                           name="student_id" 
                           class="form-control" 
                           placeholder="Enter your Student ID"
                           value="<?php echo isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : ''; ?>"
                           required>
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           placeholder="Enter your password (same as Student ID)"
                           required>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="passwordIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn" <?php echo $db_error ? 'disabled' : ''; ?>>
                <i class="fas fa-sign-in-alt"></i>
                Login
            </button>
        </form>

        <div class="login-footer">
            <p class="help-text">
                <strong>First time login?</strong> Use your Student ID as password
            </p>
            <p class="help-text">
                Need help? <a href="#" class="help-link">Contact Administrator</a>
            </p>
            <?php if ($db_error): ?>
            <p class="help-text">
                <a href="setup_database.php" class="help-link">
                    <i class="fas fa-database"></i> Setup Database
                </a>
            </p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('passwordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                passwordIcon.className = 'fas fa-eye';
            }
        }

        function fillCredentials(studentId) {
            document.getElementById('student_id').value = studentId;
            document.getElementById('password').value = studentId;
            
            // Add visual feedback
            const studentIdField = document.getElementById('student_id');
            const passwordField = document.getElementById('password');
            
            studentIdField.style.background = '#ecfdf5';
            passwordField.style.background = '#ecfdf5';
            
            setTimeout(() => {
                studentIdField.style.background = '';
                passwordField.style.background = '';
            }, 1000);
        }

        // Auto-focus on student ID field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('student_id').focus();
            
            // Auto-fill password when student ID is entered
            document.getElementById('student_id').addEventListener('input', function() {
                const studentId = this.value;
                if (studentId && !document.getElementById('password').value) {
                    document.getElementById('password').value = studentId;
                }
            });
        });

        // Handle form submission with loading state
        document.querySelector('.login-form').addEventListener('submit', function() {
            const submitBtn = document.querySelector('.btn');
            if (!submitBtn.disabled) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
            }
        });

        // Show helpful hints
        document.getElementById('student_id').addEventListener('focus', function() {
            if (!this.value) {
                showHint('Enter your Student ID (e.g., ST2024001)');
            }
        });

        document.getElementById('password').addEventListener('focus', function() {
            if (!this.value) {
                showHint('Use the same value as your Student ID for first login');
            }
        });

        function showHint(message) {
            // Remove existing hint
            const existingHint = document.querySelector('.login-hint');
            if (existingHint) {
                existingHint.remove();
            }

            // Create new hint
            const hint = document.createElement('div');
            hint.className = 'login-hint';
            hint.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: var(--primary);
                color: white;
                padding: 0.75rem 1rem;
                border-radius: var(--radius);
                font-size: 0.875rem;
                box-shadow: var(--shadow-lg);
                z-index: 1000;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease;
                max-width: 300px;
            `;
            hint.innerHTML = `<i class="fas fa-lightbulb"></i> ${message}`;
            
            document.body.appendChild(hint);
            
            setTimeout(() => {
                hint.style.opacity = '1';
                hint.style.transform = 'translateX(0)';
            }, 100);
            
            setTimeout(() => {
                hint.style.opacity = '0';
                hint.style.transform = 'translateX(100%)';
                setTimeout(() => hint.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>