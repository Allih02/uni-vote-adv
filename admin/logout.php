<?php
// logout.php - Admin Logout
session_start();
require_once 'config/database.php';

// Check if user is logged in and has a remember token
if(isset($_SESSION['admin_id']) && isset($_COOKIE['admin_remember'])) {
    try {
        $conn = getDBConnection();
        $token = $_COOKIE['admin_remember'];
        
        // Delete the session from database
        $stmt = $conn->prepare("DELETE FROM admin_sessions WHERE session_token = ?");
        $stmt->execute([$token]);
    } catch(PDOException $e) {
        error_log($e->getMessage());
    }
}

// Clear all session data
$_SESSION = array();

// Destroy the session
session_destroy();

// Delete the remember me cookie
if(isset($_COOKIE['admin_remember'])) {
    setcookie('admin_remember', '', time() - 3600, '/', '', true, true);
}

// Redirect to login page
header("Location: login.php");
exit();
?>