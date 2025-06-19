<?php
// student_login.php - Enhanced Student Login with Real Database Integration
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
    
    // Check if using Student ID as password (default behavior)
    if ($password === $student_id) {
        // Update password hash if not already done
        if (empty($voter['password']) || $voter['password'] === $voter['student_id']) {
            setupDefaultPassword($voter, $student_id);
        }
        
        // Update last login
        updateRecord('voters', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $voter['id']]);
        return $voter;
    }
    
    // If password is set and not student_id, verify normally
    if (!empty($voter['password']) && $voter['password'] !== $voter['student_id']) {
        if (password_verify($password, $voter['password'])) {
            // Update last login
            updateRecord('voters', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $voter['id']]);
            return $voter;
        }
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
            $error_message = 'Invalid Student ID or password.';
        }
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
            --primary-blue: #1e40af;
            --secondary-blue: #3b82f6;
            --light-blue: #dbeafe;
            --white: #ffffff;
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
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            --radius: 1rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, var(--white) 0%, var(--white) 45%, var(--light-blue) 45%, var(--primary-blue) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            overflow-x: hidden;
        }

        .main-container {
            display: flex;
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            width: 100%;
            max-width: 1000px;
            min-height: 600px;
            position: relative;
        }

        .logo-section {
            flex: 1;
            background: var(--white);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            position: relative;
        }

        .university-logo {
            width: 200px;
            height: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            position: relative;
            animation: logoFloat 3s ease-in-out infinite;
        }

        .logo-image {
            width: 100%;
            height: auto;
            object-fit: contain;
            filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.1));
            animation: logoGlow 2s ease-in-out infinite alternate;
        }

        .university-name {
            text-align: center;
            color: var(--text-primary);
        }

        .university-name h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--primary-blue);
        }

        .university-name p {
            font-size: 1.1rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .login-section {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem;
            color: var(--white);
            position: relative;
        }

        .login-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="90" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.1;
            pointer-events: none;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
            position: relative;
            z-index: 1;
        }

        .login-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            font-weight: 400;
        }

        .login-form {
            position: relative;
            z-index: 1;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
            color: var(--white);
        }

        .form-control {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--radius);
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.1);
            color: var(--white);
            backdrop-filter: blur(10px);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--white);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
            z-index: 2;
        }

        .input-group .form-control {
            padding-left: 3.5rem;
        }

        .password-toggle {
            position: absolute;
            right: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            z-index: 2;
        }

        .password-toggle:hover {
            color: var(--white);
            background: rgba(255, 255, 255, 0.1);
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            color: #fecaca;
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
            border: 1px solid rgba(239, 68, 68, 0.3);
            backdrop-filter: blur(10px);
        }

        .success-message {
            background: rgba(16, 185, 129, 0.1);
            color: #a7f3d0;
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
            border: 1px solid rgba(16, 185, 129, 0.3);
            backdrop-filter: blur(10px);
        }

        .warning-message {
            background: rgba(245, 158, 11, 0.1);
            color: #fde68a;
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
            border: 1px solid rgba(245, 158, 11, 0.3);
            backdrop-filter: blur(10px);
        }

        .info-message {
            background: rgba(59, 130, 246, 0.1);
            color: #bfdbfe;
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            font-size: 0.85rem;
            border: 1px solid rgba(59, 130, 246, 0.3);
            backdrop-filter: blur(10px);
            line-height: 1.5;
        }

        .btn {
            width: 100%;
            padding: 1rem 1.5rem;
            background: var(--white);
            color: var(--primary-blue);
            border: none;
            border-radius: var(--radius);
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-md);
        }

        .btn:hover {
            background: #f3f4f6;
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .help-text {
            text-align: center;
            font-size: 0.9rem;
            opacity: 0.9;
            line-height: 1.6;
        }

        .help-link {
            color: var(--white);
            text-decoration: none;
            font-weight: 500;
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.2s ease;
        }

        .help-link:hover {
            border-bottom-color: var(--white);
        }

        .setup-link {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.75rem 1.25rem;
            background: rgba(245, 158, 11, 0.2);
            color: #fde68a;
            text-decoration: none;
            border-radius: var(--radius);
            font-size: 0.9rem;
            font-weight: 500;
            border: 1px solid rgba(245, 158, 11, 0.3);
            transition: all 0.2s ease;
        }

        .setup-link:hover {
            background: rgba(245, 158, 11, 0.3);
            transform: translateY(-1px);
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        @keyframes logoGlow {
            0% { filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.1)); }
            100% { filter: drop-shadow(0 15px 30px rgba(37, 99, 235, 0.3)); }
        }

        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
                max-width: 450px;
                margin: 1rem;
            }
            
            .logo-section {
                padding: 2rem;
            }
            
            .university-logo {
                width: 150px;
                height: auto;
            }
            
            .logo-icon {
                font-size: 2.5rem;
            }
            
            .university-name h1 {
                font-size: 1.8rem;
            }
            
            .university-name p {
                font-size: 1rem;
            }
            
            .login-section {
                padding: 2rem;
            }
            
            .login-title {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 1rem;
            }
            
            .main-container {
                margin: 0;
            }
            
            .logo-section, .login-section {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="logo-section">
            <div class="university-logo">
                <img src="iaa.png" alt="Institute of Accountancy Arusha" class="logo-image">
            </div>
            <div class="university-name">
                <h1>Institute of Accountancy Arusha</h1>
                <p>Excellence & Professionalism</p>
            </div>
        </div>

        <div class="login-section">
            <div class="login-header">
                <h1 class="login-title">Student Portal</h1>
                <p class="login-subtitle">Voting System Access</p>
            </div>

            <form class="login-form" method="POST">
                <?php if ($db_error): ?>
                    <div class="warning-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            Database connection failed. Please set up the database first.
                            <a href="setup_database.php" class="setup-link">Setup Database</a>
                        </div>
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

                <!-- Login Instructions -->
                <div class="info-message">
                    <i class="fas fa-info-circle" style="margin-top: 2px;"></i>
                    <div>
                        <strong>Login Instructions:</strong><br>
                        Use your <strong>Student ID</strong> as both your username and password.<br>
                        For example, if your Student ID is "ST2024001", enter "ST2024001" in both fields.
                    </div>
                </div>

                <div class="form-group">
                    <label for="student_id" class="form-label">Student ID</label>
                    <div class="input-group">
                        <i class="fas fa-id-card input-icon"></i>
                        <input type="text" 
                               id="student_id" 
                               name="student_id" 
                               class="form-control" 
                               placeholder="Enter your Student ID (e.g., ST2024001)"
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
                               placeholder="Enter your Student ID as password"
                               required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="passwordIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn" <?php echo $db_error ? 'disabled' : ''; ?>>
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>

                <div class="help-text">
                    Having trouble logging in? Contact the system administrator for assistance.
                    <?php if ($db_error): ?>
                        <br><br>
                        <a href="setup_database.php" class="help-link">
                            <i class="fas fa-database"></i> Setup Database
                        </a>
                    <?php endif; ?>
                </div>
            </form>
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

        // Auto-focus on student ID field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('student_id').focus();
            
            // Auto-fill password when student ID is entered
            const studentIdInput = document.getElementById('student_id');
            const passwordInput = document.getElementById('password');
            
            studentIdInput.addEventListener('input', function() {
                if (this.value.trim()) {
                    passwordInput.value = this.value.trim();
                } else {
                    passwordInput.value = '';
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

        // Add subtle animations on focus
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // Show helpful tooltips
        document.addEventListener('DOMContentLoaded', function() {
            const studentIdInput = document.getElementById('student_id');
            const passwordInput = document.getElementById('password');
            
            // Add placeholder update based on student ID input
            studentIdInput.addEventListener('input', function() {
                if (this.value.trim()) {
                    passwordInput.placeholder = 'Enter: ' + this.value.trim();
                } else {
                    passwordInput.placeholder = 'Enter your Student ID as password';
                }
            });
        });
    </script>
</body>
</html>