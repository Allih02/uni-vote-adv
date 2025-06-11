<?php
// includes/session_check.php
session_start();

// Check if user is logged in
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Check session timeout (15 minutes)
$session_lifetime = $_SESSION['session_lifetime'] ?? 900; // 15 minutes default
$current_time = time();
$login_time = $_SESSION['login_time'] ?? 0;
$time_elapsed = $current_time - $login_time;

if($time_elapsed > $session_lifetime) {
    // Session expired
    $_SESSION = array();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = $current_time;

// Calculate remaining time
$remaining_time = $session_lifetime - $time_elapsed;

// HTML string for the script instead of variable
function getSessionScript($session_lifetime, $login_time) {
    return <<<HTML
<script>
// Session timeout management
let sessionLifetime = {$session_lifetime}; // 15 minutes in seconds
let loginTime = {$login_time};
let warningTime = 180; // 3 minutes warning
let warningShown = false;

function checkSessionTimeout() {
    let currentTime = Math.floor(Date.now() / 1000);
    let elapsedTime = currentTime - loginTime;
    let remainingTime = sessionLifetime - elapsedTime;
    
    // Update remaining time display (if you have one)
    updateRemainingTimeDisplay(remainingTime);
    
    if (remainingTime <= 0) {
        // Session expired
        alert('Your session has expired. You will be redirected to the login page.');
        window.location.href = 'login.php?timeout=1';
    } else if (remainingTime <= warningTime && !warningShown) {
        // Show warning
        warningShown = true;
        showSessionWarning(remainingTime);
    }
}

function updateRemainingTimeDisplay(seconds) {
    let displayElement = document.getElementById('session-time-remaining');
    if (displayElement) {
        let minutes = Math.floor(seconds / 60);
        let remainingSeconds = seconds % 60;
        displayElement.textContent = minutes + ':' + remainingSeconds.toString().padStart(2, '0');
    }
}

function showSessionWarning(remainingSeconds) {
    let minutes = Math.floor(remainingSeconds / 60);
    let seconds = remainingSeconds % 60;
    
    // Create warning modal
    let modal = document.createElement('div');
    modal.id = 'session-warning-modal';
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; z-index: 9999;';
    
    let modalContent = document.createElement('div');
    modalContent.style.cssText = 'background: white; padding: 2rem; border-radius: 0.5rem; max-width: 400px; text-align: center; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);';
    
    modalContent.innerHTML = '<h2 style="color: #ef4444; margin-bottom: 1rem;">Session Timeout Warning</h2>' +
        '<p style="margin-bottom: 1.5rem;">Your session will expire in <span id="countdown-timer">' + minutes + ':' + seconds.toString().padStart(2, '0') + '</span></p>' +
        '<p style="margin-bottom: 1.5rem;">Please save your work. You will be automatically logged out when the time expires.</p>' +
        '<button id="extend-session" style="background: #3b82f6; color: white; border: none; padding: 0.5rem 1rem; border-radius: 0.375rem; cursor: pointer; margin-right: 0.5rem;">Extend Session</button>' +
        '<button id="logout-now" style="background: #ef4444; color: white; border: none; padding: 0.5rem 1rem; border-radius: 0.375rem; cursor: pointer;">Logout Now</button>';
    
    modal.appendChild(modalContent);
    document.body.appendChild(modal);
    
    // Start countdown
    let countdownInterval = setInterval(() => {
        remainingSeconds--;
        let minutes = Math.floor(remainingSeconds / 60);
        let seconds = remainingSeconds % 60;
        
        let timerElement = document.getElementById('countdown-timer');
        if (timerElement) {
            timerElement.textContent = minutes + ':' + seconds.toString().padStart(2, '0');
        }
        
        if (remainingSeconds <= 0) {
            clearInterval(countdownInterval);
            window.location.href = 'login.php?timeout=1';
        }
    }, 1000);
    
    // Handle extend session button
    document.getElementById('extend-session').addEventListener('click', function() {
        // Make AJAX call to extend session
        fetch('extend_session.php', {
            method: 'POST',
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                clearInterval(countdownInterval);
                document.getElementById('session-warning-modal').remove();
                warningShown = false;
                loginTime = Math.floor(Date.now() / 1000); // Reset login time
                alert('Session extended for another 15 minutes');
            }
        })
        .catch(error => {
            console.error('Error extending session:', error);
        });
    });
    
    // Handle logout now button
    document.getElementById('logout-now').addEventListener('click', function() {
        window.location.href = 'logout.php';
    });
}

// Check session every 30 seconds
setInterval(checkSessionTimeout, 30000);

// Initial check
checkSessionTimeout();
</script>
HTML;
}
?>