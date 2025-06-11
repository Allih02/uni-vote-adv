<?php
// extend_session.php
session_start();

header('Content-Type: application/json');

if(isset($_SESSION['admin_id'])) {
    // Reset session timer
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    
    echo json_encode(['success' => true, 'message' => 'Session extended']);
} else {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
}
exit();
?>