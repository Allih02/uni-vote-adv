<?php
session_start();
include '../db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $registration_number = $_POST["registration"];
    $password = $_POST["password"];
    
    $sql = "SELECT id, fullname, password_hash FROM users WHERE registration_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $registration_number);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $fullname, $password_hash);
        $stmt->fetch();
        
        $password_valid = false;
        
        // Check if it's a bcrypt hash (starts with $2y$)
        if (strpos($password_hash, '$2y$') === 0) {
            $password_valid = password_verify($password, $password_hash);
        } else {
            // Fallback to SHA2 for old hashes
            $password_valid = ($password_hash === hash('sha256', $password));
        }
        
        if ($password_valid) {
            $_SESSION["user_id"] = $id;
            $_SESSION["fullname"] = $fullname;
            $_SESSION["registration_number"] = $registration_number;
            header("Location: dashboard.php");
            exit();
        } else {
            echo "<script>alert('Invalid password.'); window.location.href='login.php';</script>";
        }
    } else {
        echo "<script>alert('No account found with that registration number.'); window.location.href='login.php';</script>";
    }
    
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Institute of Accountancy Arusha</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #9ea8b2; /* Changed to navy blue from IAA logo */
            --secondary-color: #3a699d; /* Darker navy blue */
            --accent-color: #AD1B33; /* Red accent from IAA logo */
            --background-color: #f8f9fa;
            --text-color: #333;
            --error-color: #e63946;
            --success-color: #2a9d8f;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Cg fill-rule='evenodd'%3E%3Cg fill='%23003366' fill-opacity='0.05'%3E%3Cpath opacity='.5' d='M96 95h4v1h-4v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9zm-1 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm9-10v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm9-10v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm9-10v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            width: 100%;
            max-width: 1000px;
            display: flex;
            height: 600px;
            box-shadow: var(--box-shadow);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .image-section {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .image-section::before {
            content: "";
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: -100px;
            right: -100px;
        }
        
        .image-section::after {
            content: "";
            position: absolute;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            bottom: -50px;
            left: -50px;
        }
        
        .university-logo {
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .university-logo img {
            max-width: 180px;
            height: auto;
        }
        
        .image-section h1 {
            font-size: 2.2rem;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .image-section p {
            font-size: 1.1rem;
            max-width: 80%;
            text-align: center;
            line-height: 1.6;
        }
        
        .form-section {
            flex: 1;
            background: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .form-header {
            margin-bottom: 2rem;
        }
        
        .form-header h2 {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .form-header p {
            color: #777;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-color);
        }
        
        .form-group .input-with-icon {
            position: relative;
        }
        
        .form-group .icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }
        
        .form-group input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(0, 51, 102, 0.2);
            outline: none;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            cursor: pointer;
        }
        
        .submit-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 51, 102, 0.3);
            margin-top: 1rem;
            font-family: 'Poppins', sans-serif;
        }
        
        .submit-btn:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .register-link {
            margin-top: 1.5rem;
            text-align: center;
            color: #777;
        }
        
        .register-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .register-link a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        .forgot-password {
            text-align: right;
            margin-bottom: 1.5rem;
        }
        
        .forgot-password a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .forgot-password a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .container {
            animation: fadeIn 0.8s ease forwards;
        }
        
        /* Mobile logo styles */
        .mobile-logo {
            display: none;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .mobile-logo img {
            max-width: 120px;
            height: auto;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .container {
                max-width: 800px;
                height: auto;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                height: auto;
                max-width: 95%;
            }
            
            .image-section, .form-section {
                width: 100%;
                padding: 2rem;
            }
            
            .image-section {
                display: none;
            }
            
            .mobile-header {
                display: block;
                margin-bottom: 1.5rem;
                text-align: center;
            }
            
            .mobile-header h1 {
                color: var(--primary-color);
                font-size: 1.8rem;
                margin-bottom: 0.5rem;
            }
            
            .mobile-header p {
                color: #777;
                font-size: 0.9rem;
            }
            
            .mobile-logo {
                display: block;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="image-section">
            <div class="university-logo">
                <!-- IAA logo image -->
                <img src="logo-dark.webp" alt="Institute of Accountancy Arusha" onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48c3R5bGU+LmF7ZmlsbDojMDAzMzY2O30uYntmaWxsOiNBRDFCMzM7fTwvc3R5bGU+PGc+PHBhdGggY2xhc3M9ImEiIGQ9Ik03MCwzMCBMMTMwLDMwIEwxMDAsMTAgTDcwLDMwIFoiIC8+PHJlY3QgY2xhc3M9ImEiIHg9IjgwIiB5PSIzNSIgd2lkdGg9IjQwIiBoZWlnaHQ9IjQwIiAvPjxwYXRoIGNsYXNzPSJiIiBkPSJNMTAsNzAgTDUwLDcwIEw1MCw5MCBMMTAsOTAgWiIgLz48cGF0aCBjbGFzcz0iYiIgZD0iTTE1MCw3MCBMMTM1LDcwIEwxMzUsOTAgTDE1MCw5MCBaIiAvPjx0ZXh0IHg9IjEwMCIgeT0iMTAwIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTIiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGNsYXNzPSJhIj5JQUEgQVJVU0hBPC90ZXh0PjwvZz48L3N2Zz4=';">
            </div>
            <h1>Institute of Accountancy Arusha</h1>
            <p>Access your account to participate in campus voting and make your voice heard!</p>
        </div>
        
        <div class="form-section">
            <!-- Mobile only header (shows when image section is hidden) -->
            <div class="mobile-header" style="display: none;">
                <div class="mobile-logo">
                    <!-- Mobile IAA logo image -->
                    <img src="path/to/iaa-logo.png" alt="Institute of Accountancy Arusha" onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48c3R5bGU+LmF7ZmlsbDojMDAzMzY2O30uYntmaWxsOiNBRDFCMzM7fTwvc3R5bGU+PGc+PHBhdGggY2xhc3M9ImEiIGQ9Ik03MCwzMCBMMTMwLDMwIEwxMDAsMTAgTDcwLDMwIFoiIC8+PHJlY3QgY2xhc3M9ImEiIHg9IjgwIiB5PSIzNSIgd2lkdGg9IjQwIiBoZWlnaHQ9IjQwIiAvPjxwYXRoIGNsYXNzPSJiIiBkPSJNMTAsNzAgTDUwLDcwIEw1MCw5MCBMMTAsOTAgWiIgLz48cGF0aCBjbGFzcz0iYiIgZD0iTTE1MCw3MCBMMTM1LDcwIEwxMzUsOTAgTDE1MCw5MCBaIiAvPjx0ZXh0IHg9IjEwMCIgeT0iMTAwIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTIiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGNsYXNzPSJhIj5JQUEgQVJVU0hBPC90ZXh0PjwvZz48L3N2Zz4=';">
                </div>
                <h1>Institute of Accountancy Arusha</h1>
                <p>Sign in to your account</p>
            </div>
            
            <div class="form-header">
                <h2>Welcome Back</h2>
                <p>Sign in to continue to your account</p>
            </div>
            
            <form action="login.php" method="POST" id="loginForm">
                <div class="form-group">
                    <label for="registration">Registration Number</label>
                    <div class="input-with-icon">
                        <i class="fas fa-id-card icon"></i>
                        <input type="text" name="registration" id="registration" placeholder="Enter your registration number" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock icon"></i>
                        <input type="password" name="password" id="password" placeholder="Enter your password" required>
                        <i class="fas fa-eye password-toggle" id="passwordToggle"></i>
                    </div>
                </div>
                
                <div class="forgot-password">
                    <a href="#">Forgot password?</a>
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
            
        </div>
    </div>
    
    <script>
        // Toggle password visibility
        document.getElementById('passwordToggle').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const passwordToggle = document.getElementById('passwordToggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordToggle.classList.remove('fa-eye');
                passwordToggle.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordToggle.classList.remove('fa-eye-slash');
                passwordToggle.classList.add('fa-eye');
            }
        });
        
        // Show mobile header on smaller screens
        function checkScreenSize() {
            const mobileHeader = document.querySelector('.mobile-header');
            if (window.innerWidth <= 768) {
                mobileHeader.style.display = 'block';
            } else {
                mobileHeader.style.display = 'none';
            }
        }
        
        // Check on load and resize
        window.addEventListener('load', checkScreenSize);
        window.addEventListener('resize', checkScreenSize);
    </script>
</body>
</html>