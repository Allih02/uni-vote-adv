<?php
// config/database.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'university_voting_system');

// Create database connection
function getDBConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            )
        );
        return $conn;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Function to check if database tables exist
function checkDatabaseSetup() {
    try {
        $conn = getDBConnection();
        $stmt = $conn->query("SHOW TABLES LIKE 'admins'");
        return $stmt->rowCount() > 0;
    } catch(PDOException $e) {
        return false;
    }
}

// Function to execute the database setup
function setupDatabase() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";charset=utf8mb4",
            DB_USER,
            DB_PASS
        );
        
        // Read and execute the SQL file content
        $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . ";
                USE " . DB_NAME . ";
                
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
                
                CREATE TABLE IF NOT EXISTS admin_sessions (
                    session_id INT PRIMARY KEY AUTO_INCREMENT,
                    admin_id INT NOT NULL,
                    session_token VARCHAR(255) NOT NULL,
                    expires_at DATETIME NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (admin_id) REFERENCES admins(admin_id) ON DELETE CASCADE
                );
                
                CREATE TABLE IF NOT EXISTS login_attempts (
                    attempt_id INT PRIMARY KEY AUTO_INCREMENT,
                    email VARCHAR(100) NOT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    success BOOLEAN DEFAULT FALSE
                );";
        
        $conn->exec($sql);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Automatically setup database if tables don't exist
if (!checkDatabaseSetup()) {
    setupDatabase();
}
?>