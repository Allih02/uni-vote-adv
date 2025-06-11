<?php
// setup_database.php - Database Setup and Verification Script
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
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .step {
            margin-bottom: 30px;
            padding: 20px;
            border-left: 4px solid #4f46e5;
            background-color: #f8fafc;
        }
        .success {
            color: #10b981;
            background-color: #ecfdf5;
            border-left-color: #10b981;
        }
        .error {
            color: #ef4444;
            background-color: #fef2f2;
            border-left-color: #ef4444;
        }
        .warning {
            color: #f59e0b;
            background-color: #fffbeb;
            border-left-color: #f59e0b;
        }
        .btn {
            background: #4f46e5;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #3730a3;
        }
        .code {
            background: #1e293b;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            white-space: pre-wrap;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>University Voting System - Database Setup</h1>
        
        <?php
        // Database Configuration
        $host = 'localhost';
        $db_name = 'voting_system';
        $username = 'root';  // Change this to your MySQL username
        $password = '';      // Change this to your MySQL password
        
        echo "<div class='step'>";
        echo "<h2>Step 1: Database Connection Test</h2>";
        
        try {
            $pdo = new PDO("mysql:host=$host", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "<p class='success'>✓ Successfully connected to MySQL server</p>";
            
            // Check if database exists
            $stmt = $pdo->query("SHOW DATABASES LIKE '$db_name'");
            if ($stmt->rowCount() > 0) {
                echo "<p class='success'>✓ Database '$db_name' exists</p>";
                $database_exists = true;
            } else {
                echo "<p class='warning'>⚠ Database '$db_name' does not exist</p>";
                $database_exists = false;
            }
            
        } catch(PDOException $e) {
            echo "<p class='error'>✗ Connection failed: " . $e->getMessage() . "</p>";
            echo "<p>Please check your database credentials above and make sure MySQL is running.</p>";
            $database_exists = false;
        }
        echo "</div>";
        
        // Create database and tables if needed
        if (isset($_GET['create_db']) && $_GET['create_db'] == '1') {
            echo "<div class='step'>";
            echo "<h2>Step 2: Creating Database and Tables</h2>";
            
            try {
                // Create database
                $pdo->exec("CREATE DATABASE IF NOT EXISTS $db_name");
                echo "<p class='success'>✓ Database '$db_name' created successfully</p>";
                
                // Use the database
                $pdo->exec("USE $db_name");
                
                // Create tables
                $sql = "
                -- Create voters table
                CREATE TABLE IF NOT EXISTS voters (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    student_id VARCHAR(20) UNIQUE NOT NULL,
                    full_name VARCHAR(100) NOT NULL,
                    email VARCHAR(100) UNIQUE NOT NULL,
                    phone VARCHAR(20),
                    program VARCHAR(100) NOT NULL,
                    year VARCHAR(20) NOT NULL,
                    faculty VARCHAR(100) NOT NULL,
                    gender ENUM('Male', 'Female', 'Other', 'Prefer not to say'),
                    date_of_birth DATE,
                    nationality VARCHAR(50),
                    address TEXT,
                    password VARCHAR(255) DEFAULT NULL,
                    status ENUM('active', 'pending', 'inactive') DEFAULT 'pending',
                    profile_image VARCHAR(255) DEFAULT 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100&fit=crop&crop=face',
                    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    last_login TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                );
                ";
                
                $pdo->exec($sql);
                echo "<p class='success'>✓ Voters table created successfully</p>";
                
                // Create admin users table
                $sql = "
                CREATE TABLE IF NOT EXISTS admin_users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    email VARCHAR(100) UNIQUE NOT NULL,
                    fullname VARCHAR(100) NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    role ENUM('admin', 'super_admin') DEFAULT 'admin',
                    status ENUM('active', 'inactive') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                );
                ";
                
                $pdo->exec($sql);
                echo "<p class='success'>✓ Admin users table created successfully</p>";
                
                // Create elections table
                $sql = "
                CREATE TABLE IF NOT EXISTS elections (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(200) NOT NULL,
                    description TEXT,
                    start_date DATETIME NOT NULL,
                    end_date DATETIME NOT NULL,
                    status ENUM('draft', 'active', 'completed', 'cancelled') DEFAULT 'draft',
                    eligible_years JSON,
                    eligible_faculties JSON,
                    created_by INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                );
                ";
                
                $pdo->exec($sql);
                echo "<p class='success'>✓ Elections table created successfully</p>";
                
                // Create votes table
                $sql = "
                CREATE TABLE IF NOT EXISTS votes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    voter_id INT NOT NULL,
                    election_id INT NOT NULL,
                    candidate_id INT,
                    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_vote (voter_id, election_id)
                );
                ";
                
                $pdo->exec($sql);
                echo "<p class='success'>✓ Votes table created successfully</p>";
                
            } catch(PDOException $e) {
                echo "<p class='error'>✗ Error creating database/tables: " . $e->getMessage() . "</p>";
            }
            echo "</div>";
        }
        
        // Insert sample data if needed
        if (isset($_GET['insert_data']) && $_GET['insert_data'] == '1') {
            echo "<div class='step'>";
            echo "<h2>Step 3: Inserting Sample Data</h2>";
            
            try {
                $pdo->exec("USE $db_name");
                
                // Check if sample data already exists
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM voters WHERE student_id = 'ST2024001'");
                $result = $stmt->fetch();
                
                if ($result['count'] > 0) {
                    echo "<p class='warning'>⚠ Sample data already exists</p>";
                } else {
                    // Insert sample voters
                    $password_hash = password_hash('ST2024001', PASSWORD_DEFAULT);
                    
                    $sql = "INSERT INTO voters (student_id, full_name, email, phone, program, year, faculty, gender, date_of_birth, nationality, address, password, status, profile_image) VALUES
                    ('ST2024001', 'John Smith', 'john.smith@university.edu', '+255 123 456 789', 'Computer Science', '3rd Year', 'Science & Technology', 'Male', '2001-03-15', 'Tanzanian', 'Arusha, Tanzania', '$password_hash', 'active', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&h=100&fit=crop&crop=face'),
                    ('ST2024002', 'Sarah Johnson', 'sarah.johnson@university.edu', '+255 987 654 321', 'Business Administration', '4th Year', 'Business & Economics', 'Female', '2000-07-22', 'Tanzanian', 'Dar es Salaam, Tanzania', '$password_hash', 'active', 'https://images.unsplash.com/photo-1494790108755-2616b169b9c0?w=100&h=100&fit=crop&crop=face'),
                    ('ST2024003', 'Michael Chen', 'michael.chen@university.edu', '+255 555 123 456', 'Engineering', '2nd Year', 'Engineering', 'Male', '2002-11-08', 'Chinese', 'Mwanza, Tanzania', '$password_hash', 'active', 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100&fit=crop&crop=face')";
                    
                    $pdo->exec($sql);
                    echo "<p class='success'>✓ Sample voters inserted successfully</p>";
                    
                    // Insert sample elections
                    $sql = "INSERT INTO elections (title, description, start_date, end_date, status, eligible_years, eligible_faculties) VALUES
                    ('Student President Election 2025', 'Annual election for Student Government President', '2025-06-15 08:00:00', '2025-06-17 18:00:00', 'active', '[\"1st Year\", \"2nd Year\", \"3rd Year\", \"4th Year\", \"5th Year\"]', '[\"Science & Technology\", \"Business & Economics\", \"Engineering\", \"Health Sciences\", \"Arts & Humanities\", \"Law & Governance\"]'),
                    ('Faculty Representative Election', 'Election for Science & Technology Faculty Representative', '2025-06-20 09:00:00', '2025-06-22 17:00:00', 'draft', '[\"1st Year\", \"2nd Year\", \"3rd Year\", \"4th Year\"]', '[\"Science & Technology\"]')";
                    
                    $pdo->exec($sql);
                    echo "<p class='success'>✓ Sample elections inserted successfully</p>";
                }
                
            } catch(PDOException $e) {
                echo "<p class='error'>✗ Error inserting sample data: " . $e->getMessage() . "</p>";
            }
            echo "</div>";
        }
        
        // Verify data
        if (isset($_GET['verify']) && $_GET['verify'] == '1') {
            echo "<div class='step'>";
            echo "<h2>Step 4: Data Verification</h2>";
            
            try {
                $pdo->exec("USE $db_name");
                
                // Check voters
                $stmt = $pdo->query("SELECT student_id, full_name, email, status FROM voters LIMIT 5");
                $voters = $stmt->fetchAll();
                
                if (count($voters) > 0) {
                    echo "<p class='success'>✓ Found " . count($voters) . " voters in database</p>";
                    echo "<table>";
                    echo "<tr><th>Student ID</th><th>Name</th><th>Email</th><th>Status</th></tr>";
                    foreach ($voters as $voter) {
                        echo "<tr><td>" . htmlspecialchars($voter['student_id']) . "</td>";
                        echo "<td>" . htmlspecialchars($voter['full_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($voter['email']) . "</td>";
                        echo "<td>" . htmlspecialchars($voter['status']) . "</td></tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p class='error'>✗ No voters found in database</p>";
                }
                
                // Test login credentials
                $stmt = $pdo->prepare("SELECT * FROM voters WHERE student_id = ?");
                $stmt->execute(['ST2024001']);
                $test_voter = $stmt->fetch();
                
                if ($test_voter) {
                    echo "<p class='success'>✓ Test voter ST2024001 found</p>";
                    if (password_verify('ST2024001', $test_voter['password'])) {
                        echo "<p class='success'>✓ Password verification working correctly</p>";
                        echo "<div class='step success'>";
                        echo "<h3>🎉 Setup Complete!</h3>";
                        echo "<p>You can now login with:</p>";
                        echo "<ul>";
                        echo "<li><strong>Student ID:</strong> ST2024001</li>";
                        echo "<li><strong>Password:</strong> ST2024001</li>";
                        echo "</ul>";
                        echo "<a href='student_login.php' class='btn'>Go to Login Page</a>";
                        echo "</div>";
                    } else {
                        echo "<p class='error'>✗ Password verification failed</p>";
                    }
                } else {
                    echo "<p class='error'>✗ Test voter ST2024001 not found</p>";
                }
                
            } catch(PDOException $e) {
                echo "<p class='error'>✗ Error verifying data: " . $e->getMessage() . "</p>";
            }
            echo "</div>";
        }
        ?>
        
        <div class="step">
            <h2>Quick Setup Actions</h2>
            <p>Click the buttons below to set up your database:</p>
            
            <?php if (!$database_exists): ?>
                <a href="?create_db=1" class="btn">1. Create Database & Tables</a>
            <?php else: ?>
                <span style="color: #10b981;">✓ Database exists</span>
            <?php endif; ?>
            
            <a href="?insert_data=1" class="btn">2. Insert Sample Data</a>
            <a href="?verify=1" class="btn">3. Verify Setup</a>
            <a href="student_login.php" class="btn">4. Go to Login</a>
        </div>
        
        <div class="step">
            <h2>Manual Database Setup (Alternative)</h2>
            <p>If the automatic setup doesn't work, you can manually run this SQL in phpMyAdmin:</p>
            <div class="code">CREATE DATABASE voting_system;
USE voting_system;

CREATE TABLE voters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    program VARCHAR(100) NOT NULL,
    year VARCHAR(20) NOT NULL,
    faculty VARCHAR(100) NOT NULL,
    gender ENUM('Male', 'Female', 'Other', 'Prefer not to say'),
    date_of_birth DATE,
    nationality VARCHAR(50),
    address TEXT,
    password VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'pending', 'inactive') DEFAULT 'pending',
    profile_image VARCHAR(255) DEFAULT 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100&fit=crop&crop=face',
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert test user (password is 'ST2024001')
INSERT INTO voters (student_id, full_name, email, program, year, faculty, password, status) 
VALUES ('ST2024001', 'John Smith', 'john.smith@university.edu', 'Computer Science', '3rd Year', 'Science & Technology', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active');</div>
        </div>
        
        <div class="step">
            <h2>Troubleshooting</h2>
            <p>If you're still having issues:</p>
            <ul>
                <li>Make sure MySQL/XAMPP is running</li>
                <li>Check database credentials in the PHP files</li>
                <li>Ensure the 'voting_system' database exists</li>
                <li>Verify the voters table has data</li>
                <li>Check that passwords are properly hashed</li>
            </ul>
        </div>
    </div>
</body>
</html>