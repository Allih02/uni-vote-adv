<?php
// setup.php - Database Setup Script
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - University Voting System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success {
            color: #28a745;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #28a745;
            border-radius: 5px;
            background: #d4edda;
        }
        .error {
            color: #dc3545;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #dc3545;
            border-radius: 5px;
            background: #f8d7da;
        }
        .info {
            color: #0c5460;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #bee5eb;
            border-radius: 5px;
            background: #d1ecf1;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #0056b3;
        }
        .code {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 10px 0;
            font-family: monospace;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>University Voting System - Database Setup</h1>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $host = $_POST['db_host'] ?? 'localhost';
            $user = $_POST['db_user'] ?? 'root';
            $pass = $_POST['db_pass'] ?? '';
            $name = $_POST['db_name'] ?? 'university_voting_system';
            
            try {
                // Connect to MySQL server
                $conn = new PDO(
                    "mysql:host=$host;charset=utf8mb4",
                    $user,
                    $pass,
                    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
                );
                
                // Create database if it doesn't exist
                $conn->exec("CREATE DATABASE IF NOT EXISTS `$name`");
                $conn->exec("USE `$name`");
                
                // Create tables
                $sql = "
                -- Create admins table
                CREATE TABLE IF NOT EXISTS admins (
                    admin_id INT PRIMARY KEY AUTO_INCREMENT,
                    full_name VARCHAR(100) NOT NULL,
                    email VARCHAR(100) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    status ENUM('pending', 'active', 'suspended') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    last_login TIMESTAMP NULL,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                );

                -- Create admin_sessions table
                CREATE TABLE IF NOT EXISTS admin_sessions (
                    session_id INT PRIMARY KEY AUTO_INCREMENT,
                    admin_id INT NOT NULL,
                    session_token VARCHAR(255) NOT NULL,
                    expires_at DATETIME NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (admin_id) REFERENCES admins(admin_id) ON DELETE CASCADE
                );

                -- Create login_attempts table
                CREATE TABLE IF NOT EXISTS login_attempts (
                    attempt_id INT PRIMARY KEY AUTO_INCREMENT,
                    email VARCHAR(100) NOT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    success BOOLEAN DEFAULT FALSE
                );

                -- Create password_resets table
                CREATE TABLE IF NOT EXISTS password_resets (
                    reset_id INT PRIMARY KEY AUTO_INCREMENT,
                    admin_id INT NOT NULL,
                    reset_token VARCHAR(255) NOT NULL,
                    expires_at DATETIME NOT NULL,
                    used BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (admin_id) REFERENCES admins(admin_id) ON DELETE CASCADE
                );";
                
                $conn->exec($sql);
                
                // Create indexes
                $indexes = "
                CREATE INDEX IF NOT EXISTS idx_admin_email ON admins(email);
                CREATE INDEX IF NOT EXISTS idx_admin_status ON admins(status);
                CREATE INDEX IF NOT EXISTS idx_session_token ON admin_sessions(session_token);
                CREATE INDEX IF NOT EXISTS idx_login_attempts_email ON login_attempts(email);
                CREATE INDEX IF NOT EXISTS idx_password_resets_token ON password_resets(reset_token);";
                
                $conn->exec($indexes);
                
                // Check if default admin exists
                $stmt = $conn->prepare("SELECT admin_id FROM admins WHERE email = ?");
                $stmt->execute(['admin@university.edu']);
                
                if ($stmt->rowCount() == 0) {
                    // Insert default admin account (password: admin123)
                    $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO admins (full_name, email, password, status) VALUES (?, ?, ?, ?)");
                    $stmt->execute(['System Administrator', 'admin@university.edu', $defaultPassword, 'active']);
                    
                    echo '<div class="success">Default admin account created successfully!</div>';
                    echo '<div class="info">Email: admin@university.edu<br>Password: admin123</div>';
                }
                
                echo '<div class="success">Database setup completed successfully!</div>';
                
                // Create config file content
                $configContent = "<?php
// config/database.php
define('DB_HOST', '$host');
define('DB_USER', '$user');
define('DB_PASS', '$pass');
define('DB_NAME', '$name');

// Create database connection
function getDBConnection() {
    try {
        \$conn = new PDO(
            \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8mb4\",
            DB_USER,
            DB_PASS,
            array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            )
        );
        return \$conn;
    } catch(PDOException \$e) {
        die(\"Connection failed: \" . \$e->getMessage());
    }
}
?>";
                
                echo '<div class="info">Please create a file named <strong>config/database.php</strong> with the following content:</div>';
                echo '<div class="code">' . htmlspecialchars($configContent) . '</div>';
                
            } catch(PDOException $e) {
                echo '<div class="error">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        } else {
            ?>
            <p>This script will set up the database for your University Voting System.</p>
            
            <form method="POST">
                <div style="margin-bottom: 15px;">
                    <label for="db_host">Database Host:</label><br>
                    <input type="text" id="db_host" name="db_host" value="localhost" style="width: 100%; padding: 8px;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label for="db_user">Database Username:</label><br>
                    <input type="text" id="db_user" name="db_user" value="root" style="width: 100%; padding: 8px;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label for="db_pass">Database Password:</label><br>
                    <input type="password" id="db_pass" name="db_pass" value="" style="width: 100%; padding: 8px;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label for="db_name">Database Name:</label><br>
                    <input type="text" id="db_name" name="db_name" value="university_voting_system" style="width: 100%; padding: 8px;">
                </div>
                
                <button type="submit">Setup Database</button>
            </form>
            <?php
        }
        ?>
        
        <div class="info" style="margin-top: 30px;">
            <h3>Setup Instructions:</h3>
            <ol>
                <li>Create a folder structure:
                    <pre>
project-folder/
├── admin/
│   ├── config/
│   │   └── database.php
│   ├── index.html
│   ├── login.php
│   ├── dashboard.php
│   ├── logout.php
│   └── setup.php (this file)
                    </pre>
                </li>
                <li>Access this setup page at: http://localhost/project-folder/admin/setup.php</li>
                <li>Fill in your database credentials and click "Setup Database"</li>
                <li>Create the config/database.php file with the provided content</li>
                <li>Delete this setup.php file after successful setup</li>
                <li>Access your admin portal at: http://localhost/project-folder/admin/</li>
            </ol>
        </div>
    </div>
</body>
</html>