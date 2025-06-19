<?php
// Start session and check authentication
session_start();

// Database connection
$host = 'localhost';
$dbname = 'voting_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $database_connected = true;
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get admin user data from database (remove mock data)
$admin_user = null;
if (isset($_SESSION["admin_id"])) {
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
    $stmt->execute([$_SESSION["admin_id"]]);
    $admin_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin_user) {
        session_destroy();
        header("Location: admin_login.php?error=session_expired");
        exit();
    }
} else {
    // For demo purposes, use the first admin from database
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE status = 'active' LIMIT 1");
    $stmt->execute();
    $admin_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin_user) {
        $_SESSION["admin_id"] = $admin_user['id'];
        $_SESSION["admin_name"] = $admin_user['fullname'];
        $_SESSION["role"] = $admin_user['role'];
    }
}

// Handle image upload function
function uploadCandidateImage($file, $candidate_name) {
    if (empty($file['name'])) {
        return null; // No file uploaded
    }
    
    // Create images directory if it doesn't exist
    $upload_dir = 'images/candidates/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $file_type = $file['type'];
    
    if (!in_array($file_type, $allowed_types)) {
        throw new Exception("Invalid file type. Only JPEG, PNG, and GIF files are allowed.");
    }
    
    // Validate file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception("File size too large. Maximum 5MB allowed.");
    }
    
    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $candidate_name);
    $filename = $safe_name . '_' . time() . '.' . $file_extension;
    $filepath = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filepath;
    } else {
        throw new Exception("Failed to upload image file.");
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create':
                    // Handle image upload
                    $profile_image = null;
                    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                        try {
                            $profile_image = uploadCandidateImage($_FILES['profile_image'], $_POST['name']);
                        } catch (Exception $e) {
                            $error_message = "Image upload error: " . $e->getMessage();
                            break;
                        }
                    }
                    
                    // Check if voter exists and get voter_id
                    $voter_id = null;
                    if (!empty($_POST['student_id'])) {
                        $stmt = $pdo->prepare("SELECT id FROM voters WHERE student_id = ?");
                        $stmt->execute([$_POST['student_id']]);
                        $voter = $stmt->fetch();
                        $voter_id = $voter ? $voter['id'] : null;
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO candidates (election_id, voter_id, full_name, student_id, email, program, year, faculty, position, manifesto, profile_image, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())");
                    $stmt->execute([
                        $_POST['election_id'],
                        $voter_id,
                        $_POST['name'],
                        $_POST['student_id'],
                        $_POST['email'],
                        $_POST['program'] ?? '',
                        $_POST['year'] ?? '',
                        $_POST['faculty'] ?? '',
                        $_POST['position'],
                        $_POST['platform'],
                        $profile_image
                    ]);
                    
                    // Log the action
                    $audit_data = $_POST;
                    $audit_data['profile_image'] = $profile_image;
                    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_type, user_id, action, entity_type, entity_id, new_values, ip_address, created_at) VALUES ('admin', ?, 'CREATE', 'candidate', LAST_INSERT_ID(), ?, ?, NOW())");
                    $stmt->execute([$admin_user['id'], json_encode($audit_data), $_SERVER['REMOTE_ADDR']]);
                    
                    header("Location: admin_candidates.php?success=created");
                    exit();
                    break;
                    
                case 'approve':
                    $stmt = $pdo->prepare("UPDATE candidates SET status = 'active', updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$_POST['candidate_id']]);
                    
                    // Log the action
                    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_type, user_id, action, entity_type, entity_id, new_values, ip_address, created_at) VALUES ('admin', ?, 'APPROVE', 'candidate', ?, ?, ?, NOW())");
                    $stmt->execute([$admin_user['id'], $_POST['candidate_id'], json_encode(['status' => 'active']), $_SERVER['REMOTE_ADDR']]);
                    
                    header("Location: admin_candidates.php?success=approved");
                    exit();
                    break;
                    
                case 'reject':
                    $stmt = $pdo->prepare("UPDATE candidates SET status = 'inactive', updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$_POST['candidate_id']]);
                    
                    // Log the action
                    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_type, user_id, action, entity_type, entity_id, new_values, ip_address, created_at) VALUES ('admin', ?, 'REJECT', 'candidate', ?, ?, ?, NOW())");
                    $stmt->execute([$admin_user['id'], $_POST['candidate_id'], json_encode(['status' => 'inactive', 'rejection_reason' => $_POST['rejection_reason'] ?? '']), $_SERVER['REMOTE_ADDR']]);
                    
                    header("Location: admin_candidates.php?success=rejected");
                    exit();
                    break;
                    
                case 'delete':
                    // Get candidate data before deletion to remove image file
                    $stmt = $pdo->prepare("SELECT profile_image FROM candidates WHERE id = ?");
                    $stmt->execute([$_POST['candidate_id']]);
                    $candidate_data = $stmt->fetch();
                    
                    // First delete related votes
                    $stmt = $pdo->prepare("DELETE FROM votes WHERE candidate_id = ?");
                    $stmt->execute([$_POST['candidate_id']]);
                    
                    // Then delete the candidate
                    $stmt = $pdo->prepare("DELETE FROM candidates WHERE id = ?");
                    $stmt->execute([$_POST['candidate_id']]);
                    
                    // Delete image file if exists
                    if ($candidate_data && $candidate_data['profile_image'] && file_exists($candidate_data['profile_image'])) {
                        unlink($candidate_data['profile_image']);
                    }
                    
                    // Log the action
                    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_type, user_id, action, entity_type, entity_id, new_values, ip_address, created_at) VALUES ('admin', ?, 'DELETE', 'candidate', ?, ?, ?, NOW())");
                    $stmt->execute([$admin_user['id'], $_POST['candidate_id'], json_encode(['deleted' => true, 'image_deleted' => $candidate_data['profile_image']]), $_SERVER['REMOTE_ADDR']]);
                    
                    header("Location: admin_candidates.php?success=deleted");
                    exit();
                    break;
                    
                case 'update':
                    // Handle image upload for updates
                    $profile_image = $_POST['existing_image'] ?? null; // Keep existing image by default
                    
                    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                        try {
                            $new_image = uploadCandidateImage($_FILES['profile_image'], $_POST['name']);
                            
                            // Delete old image if exists and upload was successful
                            if ($profile_image && file_exists($profile_image)) {
                                unlink($profile_image);
                            }
                            
                            $profile_image = $new_image;
                        } catch (Exception $e) {
                            $error_message = "Image upload error: " . $e->getMessage();
                            break;
                        }
                    }
                    
                    // Check if voter exists and get voter_id
                    $voter_id = null;
                    if (!empty($_POST['student_id'])) {
                        $stmt = $pdo->prepare("SELECT id FROM voters WHERE student_id = ?");
                        $stmt->execute([$_POST['student_id']]);
                        $voter = $stmt->fetch();
                        $voter_id = $voter ? $voter['id'] : null;
                    }
                    
                    $stmt = $pdo->prepare("UPDATE candidates SET election_id = ?, voter_id = ?, full_name = ?, student_id = ?, email = ?, program = ?, year = ?, faculty = ?, position = ?, manifesto = ?, profile_image = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([
                        $_POST['election_id'],
                        $voter_id,
                        $_POST['name'],
                        $_POST['student_id'],
                        $_POST['email'],
                        $_POST['program'] ?? '',
                        $_POST['year'] ?? '',
                        $_POST['faculty'] ?? '',
                        $_POST['position'],
                        $_POST['platform'],
                        $profile_image,
                        $_POST['candidate_id']
                    ]);
                    
                    // Log the action
                    $audit_data = $_POST;
                    $audit_data['profile_image'] = $profile_image;
                    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_type, user_id, action, entity_type, entity_id, new_values, ip_address, created_at) VALUES ('admin', ?, 'UPDATE', 'candidate', ?, ?, ?, NOW())");
                    $stmt->execute([$admin_user['id'], $_POST['candidate_id'], json_encode($audit_data), $_SERVER['REMOTE_ADDR']]);
                    
                    header("Location: admin_candidates.php?success=updated");
                    exit();
                    break;
            }
        }
    } catch(PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}

// Fetch real candidates data from database with election details and vote counts
$candidates_query = "
    SELECT 
        c.*,
        e.title as election_title,
        e.election_type,
        v.full_name as voter_name,
        v.profile_image as voter_profile_image,
        COALESCE(vote_counts.vote_count, 0) as vote_count
    FROM candidates c
    LEFT JOIN elections e ON c.election_id = e.id
    LEFT JOIN voters v ON c.voter_id = v.id
    LEFT JOIN (
        SELECT candidate_id, COUNT(*) as vote_count 
        FROM votes 
        GROUP BY candidate_id
    ) vote_counts ON c.id = vote_counts.candidate_id
    ORDER BY c.created_at DESC
";

$stmt = $pdo->prepare($candidates_query);
$stmt->execute();
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get elections for filter dropdown
$elections_stmt = $pdo->prepare("SELECT id, title, status FROM elections ORDER BY created_at DESC");
$elections_stmt->execute();
$elections = $elections_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all voters for candidate creation
$voters_stmt = $pdo->prepare("SELECT id, student_id, full_name, email, program, year, faculty FROM voters WHERE status = 'active' ORDER BY full_name");
$voters_stmt->execute();
$voters = $voters_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate real statistics from database
$total_candidates = count($candidates);
$approved_candidates = count(array_filter($candidates, function($c) { return $c['status'] === 'active'; }));
$pending_candidates = count(array_filter($candidates, function($c) { return $c['status'] === 'pending'; }));
$rejected_candidates = count(array_filter($candidates, function($c) { return $c['status'] === 'inactive' || $c['status'] === 'disqualified'; }));

// Get total votes from database
$total_votes_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM votes");
$total_votes_stmt->execute();
$total_votes = $total_votes_stmt->fetch()['total'];

// Get unique positions for filter
$positions = array_unique(array_column($candidates, 'position'));
sort($positions);

// Generate default avatar for candidates without images
function getDefaultAvatar($name) {
    $initials = '';
    $nameParts = explode(' ', trim($name));
    foreach ($nameParts as $part) {
        if (!empty($part)) {
            $initials .= strtoupper($part[0]);
            if (strlen($initials) >= 2) break;
        }
    }
    return "https://ui-avatars.com/api/?name=" . urlencode($name) . "&background=6366f1&color=ffffff&size=400";
}

// Get dashboard statistics
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM voters WHERE status = 'active') as total_voters,
        (SELECT COUNT(*) FROM elections WHERE status = 'active') as active_elections,
        (SELECT COUNT(*) FROM candidates WHERE status = 'active') as active_candidates,
        (SELECT COUNT(*) FROM votes) as total_votes_cast
";
$stats_stmt = $pdo->prepare($stats_query);
$stats_stmt->execute();
$dashboard_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidates Management - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Primary Colors */
            --primary: #6366f1;
            --primary-dark: #4338ca;
            --primary-light: #a5b4fc;
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            
            /* Secondary Colors */
            --secondary: #8b5cf6;
            --accent: #f59e0b;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --info: #3b82f6;
            
            /* Neutral Colors */
            --background: #f8fafc;
            --surface: #ffffff;
            --surface-alt: #f1f5f9;
            --surface-hover: #e2e8f0;
            --border: #e2e8f0;
            --border-light: #f1f5f9;
            
            /* Text Colors */
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #94a3b8;
            --text-inverse: #ffffff;
            
            /* Shadows */
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            
            /* Sizes */
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --sidebar-width: 280px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--background);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Modern Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            overflow-y: auto;
        }

        .sidebar.hidden {
            transform: translateX(-100%);
        }

        /* Sidebar Header */
        .sidebar-header {
            padding: 2rem 2rem 1.5rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1rem;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            margin-bottom: 1rem;
        }

        .logo i {
            font-size: 1.75rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .admin-info {
            text-align: left;
        }

        .admin-name {
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .admin-role {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }

        /* Sidebar Navigation */
        .sidebar-nav {
            padding: 0 1rem 2rem 1rem;
        }

        .nav-section {
            margin-bottom: 2rem;
        }

        .nav-section-title {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 0 1rem 0.75rem 1rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 0.875rem 1rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            margin-bottom: 0.25rem;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(4px);
        }

        .nav-item.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            box-shadow: 0 4px 20px rgba(255, 255, 255, 0.1);
        }

        .nav-item i {
            width: 20px;
            height: 20px;
            margin-right: 0.75rem;
            font-size: 1rem;
        }

        .nav-item span {
            font-weight: 500;
            letter-spacing: -0.01em;
        }

        /* Mobile Header */
        .mobile-header {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 70px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            z-index: 1001;
            padding: 0 1rem;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .mobile-logo {
            color: white;
            font-size: 1.25rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .hamburger {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }

        .hamburger:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .hamburger-icon {
            width: 24px;
            height: 24px;
            fill: white;
            transition: transform 0.3s ease;
        }

        .hamburger.active .hamburger-icon {
            transform: rotate(90deg);
        }

        /* Overlay for mobile */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .overlay.active {
            opacity: 1;
        }

        /* Dashboard Layout */
        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
            transition: margin-left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            width: calc(100vw - 280px);
            display: flex;
            flex-direction: column;
        }

        /* Top Bar */
        .top-bar {
            background: var(--surface);
            padding: 1.5rem 2rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .top-bar h1 {
            font-size: 1.875rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .top-bar-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .page-content {
            flex: 1;
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: var(--radius-xl);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary);
        }

        .stat-card.success::before { background: var(--success); }
        .stat-card.warning::before { background: var(--warning); }
        .stat-card.error::before { background: var(--error); }
        .stat-card.info::before { background: var(--info); }

        .stat-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
            background: var(--primary);
        }

        .stat-card.success .stat-icon { background: var(--success); }
        .stat-card.warning .stat-icon { background: var(--warning); }
        .stat-card.error .stat-icon { background: var(--error); }
        .stat-card.info .stat-icon { background: var(--info); }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Filter Bar */
        .filter-bar {
            background: var(--surface);
            padding: 1.5rem 2rem;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-label {
            font-weight: 500;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .filter-select {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            background: var(--surface);
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .search-box {
            flex: 1;
            max-width: 300px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            background: var(--surface);
            font-size: 0.875rem;
        }

        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success::before {
            content: "✓";
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.25rem;
            height: 1.25rem;
            background: var(--success);
            color: white;
            border-radius: 50%;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .alert-error::before {
            content: "!";
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.25rem;
            height: 1.25rem;
            background: var(--error);
            color: white;
            border-radius: 50%;
            font-size: 0.75rem;
            font-weight: bold;
        }

        /* Candidates Grid */
        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .candidate-card {
            background: var(--surface);
            border-radius: var(--radius-xl);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
            overflow: hidden;
            position: relative;
        }

        .candidate-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .candidate-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary);
        }

        .candidate-card.status-pending::before { background: var(--warning); }
        .candidate-card.status-active::before { background: var(--success); }
        .candidate-card.status-inactive::before { background: var(--error); }
        .candidate-card.status-disqualified::before { background: var(--error); }

        .candidate-header {
            padding: 1.5rem;
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }

        .candidate-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
            border: 3px solid var(--border);
        }

        .candidate-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .candidate-info {
            flex: 1;
            min-width: 0;
        }

        .candidate-name {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--text-primary);
        }

        .candidate-position {
            font-size: 0.875rem;
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .candidate-meta {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .candidate-status {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-lg);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-disqualified {
            background: #fee2e2;
            color: #991b1b;
        }

        .candidate-body {
            padding: 0 1.5rem 1.5rem;
        }

        .candidate-platform {
            color: var(--text-secondary);
            font-size: 0.875rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }

        .candidate-qualifications {
            background: var(--surface-alt);
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
        }

        .candidate-qualifications h4 {
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .candidate-qualifications p {
            font-size: 0.8rem;
            color: var(--text-secondary);
            line-height: 1.4;
        }

        .candidate-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: var(--surface-alt);
            border-radius: var(--radius-md);
        }

        .candidate-stat {
            text-align: center;
        }

        .candidate-stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
        }

        .candidate-stat-label {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .candidate-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-md);
            font-weight: 500;
            font-size: 0.875rem;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary {
            background: var(--surface);
            color: var(--text-primary);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--surface-hover);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-warning:hover {
            background: #d97706;
        }

        .btn-danger {
            background: var(--error);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8rem;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--surface);
            padding: 2rem;
            border-radius: var(--radius-xl);
            width: 100%;
            max-width: 500px;
            margin: 1rem;
            box-shadow: var(--shadow-lg);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: var(--radius-md);
        }

        .modal-close:hover {
            background: var(--surface-hover);
            color: var(--text-primary);
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-label {
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .form-control {
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 1rem;
            transition: all 0.2s ease;
            background: var(--surface);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }

        .form-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
            grid-column: 1 / -1;
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        /* Smooth scrollbar for webkit browsers */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        /* Image Upload Styles */
        .image-upload-area {
            border: 2px dashed var(--border);
            border-radius: var(--radius-md);
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .image-upload-area:hover {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.05);
        }

        .image-upload-area.drag-over {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.1);
            transform: scale(1.02);
        }

        .upload-icon {
            font-size: 3rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        .upload-text {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .image-preview {
            margin-top: 1rem;
            padding: 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            background: var(--surface-alt);
        }

        .preview-image {
            max-width: 150px;
            max-height: 150px;
            border-radius: 50%;
            border: 3px solid var(--primary);
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
        }

        .preview-image:hover {
            transform: scale(1.05);
            box-shadow: var(--shadow-lg);
        }

        .image-info {
            margin-top: 0.75rem;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .remove-image {
            background: var(--error);
            color: white;
            border: none;
            padding: 0.375rem 0.75rem;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            cursor: pointer;
            margin-top: 0.5rem;
            transition: all 0.2s ease;
        }

        .remove-image:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .drag-drop-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(99, 102, 241, 0.1);
            border: 2px dashed var(--primary);
            border-radius: var(--radius-md);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .drag-drop-overlay.active {
            display: flex;
        }

        .candidate-avatar {
            position: relative;
            overflow: hidden;
        }

        .candidate-avatar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 45%, rgba(99, 102, 241, 0.1) 50%, transparent 55%);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
            z-index: 1;
        }

        .candidate-card:hover .candidate-avatar::before {
            transform: translateX(100%);
        }
        @media (max-width: 768px) {
            .mobile-header {
                display: flex;
            }

            .sidebar {
                top: 70px;
                height: calc(100vh - 70px);
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .overlay {
                display: block;
                top: 70px;
                height: calc(100vh - 70px);
            }

            .main-content {
                margin-left: 0;
                margin-top: 70px;
                padding: 1.5rem;
                width: 100vw;
            }

            .top-bar {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .filter-bar {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }

            .candidates-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .candidate-actions {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .candidate-card {
                margin-bottom: 1rem;
            }

            .top-bar {
                padding: 1rem;
                margin-bottom: 1rem;
            }

            .filter-bar {
                padding: 1rem;
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Header -->
    <header class="mobile-header">
        <div class="mobile-logo">
            <i class="fas fa-shield-alt"></i>
            VoteAdmin
        </div>
        <button class="hamburger" id="hamburgerBtn">
            <svg class="hamburger-icon" viewBox="0 0 24 24">
                <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
            </svg>
        </button>
    </header>

    <!-- Overlay -->
    <div class="overlay" id="overlay"></div>

    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="admin_dashboard.php" class="logo">
                    <i class="fas fa-shield-alt"></i>
                    <span>VoteAdmin</span>
                </a>
                <div class="admin-info">
                    <div class="admin-name"><?php echo htmlspecialchars($admin_user['fullname'] ?? 'Admin User'); ?></div>
                    <div class="admin-role"><?php echo htmlspecialchars(ucfirst($admin_user['role']) ?? 'Administrator'); ?></div>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="admin_dashboard.php" class="nav-item">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="admin_elections.php" class="nav-item">
                        <i class="fas fa-poll"></i>
                        <span>Elections</span>
                    </a>
                    <a href="admin_candidates.php" class="nav-item active">
                        <i class="fas fa-user-tie"></i>
                        <span>Candidates</span>
                    </a>
                    <a href="admin_voters.php" class="nav-item">
                        <i class="fas fa-users"></i>
                        <span>Voters</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Reports & Analytics</div>
                    <a href="admin_results.php" class="nav-item">
                        <i class="fas fa-chart-bar"></i>
                        <span>Results</span>
                    </a>
                    <a href="admin_analytics.php" class="nav-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Analytics</span>
                    </a>
                    <a href="admin_reports.php" class="nav-item">
                        <i class="fas fa-file-alt"></i>
                        <span>Reports</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">System</div>
                    <a href="admin_settings.php" class="nav-item">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                    <a href="admin_audit.php" class="nav-item">
                        <i class="fas fa-history"></i>
                        <span>Audit Log</span>
                    </a>
                    <a href="logout.php" class="nav-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <h1>Candidates Management</h1>
                <div class="top-bar-actions">
                    <button class="btn btn-secondary" onclick="exportCandidates()">
                        <i class="fas fa-download"></i>
                        Export
                    </button>
                    <button class="btn btn-primary" onclick="showCreateModal()">
                        <i class="fas fa-plus"></i>
                        Add Candidate
                    </button>
                </div>
            </div>

            <!-- Page Content -->
            <div class="page-content">
                <!-- Success Messages -->
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        switch($_GET['success']) {
                            case 'created':
                                echo 'Candidate added successfully!';
                                break;
                            case 'approved':
                                echo 'Candidate approved successfully!';
                                break;
                            case 'rejected':
                                echo 'Candidate rejected successfully!';
                                break;
                            case 'deleted':
                                echo 'Candidate deleted successfully!';
                                break;
                            case 'updated':
                                echo 'Candidate updated successfully!';
                                break;
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <!-- Error Messages -->
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo $total_candidates; ?></div>
                        <div class="stat-label">Total Candidates</div>
                    </div>

                    <div class="stat-card success">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo $approved_candidates; ?></div>
                        <div class="stat-label">Active</div>
                    </div>

                    <div class="stat-card warning">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo $pending_candidates; ?></div>
                        <div class="stat-label">Pending Review</div>
                    </div>

                    <div class="stat-card error">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-times-circle"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo $rejected_candidates; ?></div>
                        <div class="stat-label">Inactive</div>
                    </div>

                    <div class="stat-card info">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-vote-yea"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo number_format($total_votes); ?></div>
                        <div class="stat-label">Total Votes</div>
                    </div>
                </div>

                <!-- Filter Bar -->
                <div class="filter-bar">
                    <div class="filter-group">
                        <span class="filter-label">Status:</span>
                        <select class="filter-select" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="disqualified">Disqualified</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <span class="filter-label">Election:</span>
                        <select class="filter-select" id="electionFilter">
                            <option value="">All Elections</option>
                            <?php foreach ($elections as $election): ?>
                            <option value="<?php echo $election['id']; ?>"><?php echo htmlspecialchars($election['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <span class="filter-label">Position:</span>
                        <select class="filter-select" id="positionFilter">
                            <option value="">All Positions</option>
                            <?php foreach ($positions as $position): ?>
                            <option value="<?php echo htmlspecialchars($position); ?>"><?php echo htmlspecialchars($position); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" placeholder="Search candidates..." id="searchInput">
                    </div>
                </div>

                <!-- Candidates List -->
                <div class="candidates-grid" id="candidatesGrid">
                    <?php if (empty($candidates)): ?>
                        <div class="empty-state">
                            <i class="fas fa-user-tie"></i>
                            <h3>No Candidates Found</h3>
                            <p>Start by adding your first candidate.</p>
                            <button class="btn btn-primary" onclick="showCreateModal()" style="margin-top: 1rem;">
                                <i class="fas fa-plus"></i>
                                Add Candidate
                            </button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($candidates as $candidate): ?>
                            <div class="candidate-card status-<?php echo $candidate['status']; ?>" 
                                 data-status="<?php echo $candidate['status']; ?>" 
                                 data-election="<?php echo $candidate['election_id']; ?>" 
                                 data-position="<?php echo strtolower($candidate['position']); ?>"
                                 data-name="<?php echo strtolower($candidate['full_name']); ?>">
                                
                                <div class="candidate-status status-<?php echo $candidate['status']; ?>">
                                    <?php echo ucfirst($candidate['status']); ?>
                                </div>
                                
                                <div class="candidate-header">
                                    <div class="candidate-avatar">
                                        <?php if ($candidate['profile_image'] && file_exists($candidate['profile_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($candidate['profile_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($candidate['full_name']); ?>"
                                                 onerror="this.src='<?php echo getDefaultAvatar($candidate['full_name']); ?>'">
                                        <?php elseif ($candidate['voter_profile_image']): ?>
                                            <img src="<?php echo htmlspecialchars($candidate['voter_profile_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($candidate['full_name']); ?>"
                                                 onerror="this.src='<?php echo getDefaultAvatar($candidate['full_name']); ?>'">
                                        <?php else: ?>
                                            <img src="<?php echo getDefaultAvatar($candidate['full_name']); ?>" 
                                                 alt="<?php echo htmlspecialchars($candidate['full_name']); ?>">
                                        <?php endif; ?>
                                    </div>
                                    <div class="candidate-info">
                                        <h3 class="candidate-name"><?php echo htmlspecialchars($candidate['full_name']); ?></h3>
                                        <div class="candidate-position"><?php echo htmlspecialchars($candidate['position']); ?></div>
                                        <div class="candidate-meta">
                                            <?php if ($candidate['student_id']): ?>
                                            <div><i class="fas fa-id-card"></i> <?php echo htmlspecialchars($candidate['student_id']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($candidate['email']): ?>
                                            <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($candidate['email']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($candidate['program']): ?>
                                            <div><i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($candidate['program']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($candidate['year']): ?>
                                            <div><i class="fas fa-calendar"></i> <?php echo htmlspecialchars($candidate['year']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($candidate['faculty']): ?>
                                            <div><i class="fas fa-building"></i> <?php echo htmlspecialchars($candidate['faculty']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="candidate-body">
                                    <?php if ($candidate['manifesto']): ?>
                                    <div class="candidate-platform">
                                        <strong>Manifesto:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($candidate['manifesto'])); ?>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($candidate['election_title']): ?>
                                    <div class="candidate-qualifications">
                                        <h4>Election Details</h4>
                                        <p><strong>Election:</strong> <?php echo htmlspecialchars($candidate['election_title']); ?></p>
                                        <p><strong>Type:</strong> <?php echo htmlspecialchars(ucfirst($candidate['election_type'])); ?></p>
                                        <p><strong>Created:</strong> <?php echo date('M d, Y', strtotime($candidate['created_at'])); ?></p>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($candidate['status'] === 'active'): ?>
                                    <div class="candidate-stats">
                                        <div class="candidate-stat">
                                            <div class="candidate-stat-value"><?php echo $candidate['vote_count']; ?></div>
                                            <div class="candidate-stat-label">Votes</div>
                                        </div>
                                        <div class="candidate-stat">
                                            <div class="candidate-stat-value"><?php echo $total_votes > 0 ? round(($candidate['vote_count'] / $total_votes) * 100, 1) : 0; ?>%</div>
                                            <div class="candidate-stat-label">Share</div>
                                        </div>
                                        <div class="candidate-stat">
                                            <div class="candidate-stat-value"><?php echo date('M d', strtotime($candidate['updated_at'])); ?></div>
                                            <div class="candidate-stat-label">Updated</div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <div class="candidate-actions">
                                        <button class="btn btn-secondary btn-sm" onclick="viewCandidate(<?php echo $candidate['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                            View
                                        </button>
                                        
                                        <?php if ($candidate['status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="candidate_id" value="<?php echo $candidate['id']; ?>">
                                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to approve this candidate?')">
                                                    <i class="fas fa-check"></i>
                                                    Approve
                                                </button>
                                            </form>
                                            <button class="btn btn-warning btn-sm" onclick="showRejectModal(<?php echo $candidate['id']; ?>)">
                                                <i class="fas fa-times"></i>
                                                Reject
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button class="btn btn-secondary btn-sm" onclick="editCandidate(<?php echo $candidate['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                            Edit
                                        </button>
                                        
                                        <button class="btn btn-danger btn-sm" onclick="deleteCandidate(<?php echo $candidate['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Candidate Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add New Candidate</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="form-grid">
                <input type="hidden" name="action" value="create">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="Enter full name..." required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Student ID</label>
                        <select name="student_id" class="form-control" onchange="populateVoterData(this.value)">
                            <option value="">Select Student or Enter Manually</option>
                            <?php foreach ($voters as $voter): ?>
                            <option value="<?php echo htmlspecialchars($voter['student_id']); ?>" 
                                    data-name="<?php echo htmlspecialchars($voter['full_name']); ?>"
                                    data-email="<?php echo htmlspecialchars($voter['email']); ?>"
                                    data-program="<?php echo htmlspecialchars($voter['program']); ?>"
                                    data-year="<?php echo htmlspecialchars($voter['year']); ?>"
                                    data-faculty="<?php echo htmlspecialchars($voter['faculty']); ?>">
                                <?php echo htmlspecialchars($voter['student_id'] . ' - ' . $voter['full_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" class="form-control" placeholder="student@university.edu" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Program</label>
                        <input type="text" name="program" class="form-control" placeholder="e.g., Computer Science">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Year</label>
                        <select name="year" class="form-control">
                            <option value="">Select Year</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                            <option value="5th Year">5th Year</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Faculty</label>
                        <select name="faculty" class="form-control">
                            <option value="">Select Faculty</option>
                            <option value="Science & Technology">Science & Technology</option>
                            <option value="Business & Economics">Business & Economics</option>
                            <option value="Engineering">Engineering</option>
                            <option value="Health Sciences">Health Sciences</option>
                            <option value="Arts & Humanities">Arts & Humanities</option>
                            <option value="Law & Governance">Law & Governance</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Election *</label>
                        <select name="election_id" class="form-control" required>
                            <option value="">Select Election</option>
                            <?php foreach ($elections as $election): ?>
                            <option value="<?php echo $election['id']; ?>"><?php echo htmlspecialchars($election['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Position *</label>
                        <select name="position" class="form-control" required>
                            <option value="">Select Position</option>
                            <option value="President">President</option>
                            <option value="Vice President">Vice President</option>
                            <option value="Secretary">Secretary</option>
                            <option value="Treasurer">Treasurer</option>
                            <option value="Social Events Coordinator">Social Events Coordinator</option>
                            <option value="Academic Affairs Representative">Academic Affairs Representative</option>
                            <option value="Faculty Representative">Faculty Representative</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Manifesto *</label>
                    <textarea name="platform" class="form-control" placeholder="Describe the candidate's platform and goals..." required></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Profile Image</label>
                    <input type="file" name="profile_image" class="form-control" accept="image/*" onchange="previewImage(this, 'createPreview')">
                    <small style="color: var(--text-muted); margin-top: 0.5rem; display: block;">Maximum file size: 5MB. Supported formats: JPEG, PNG, GIF</small>
                    <div id="createPreview" style="margin-top: 1rem; display: none;">
                        <img id="createPreviewImg" style="max-width: 100px; max-height: 100px; border-radius: 50%; border: 2px solid var(--border);">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Candidate</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Candidate Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Candidate</h2>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="form-grid" id="editForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="candidate_id" id="editCandidateId">
                <input type="hidden" name="existing_image" id="editExistingImage">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" id="editName" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Student ID</label>
                        <input type="text" name="student_id" id="editStudentId" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" id="editEmail" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Program</label>
                        <input type="text" name="program" id="editProgram" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Year</label>
                        <select name="year" id="editYear" class="form-control">
                            <option value="">Select Year</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                            <option value="5th Year">5th Year</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Faculty</label>
                        <select name="faculty" id="editFaculty" class="form-control">
                            <option value="">Select Faculty</option>
                            <option value="Science & Technology">Science & Technology</option>
                            <option value="Business & Economics">Business & Economics</option>
                            <option value="Engineering">Engineering</option>
                            <option value="Health Sciences">Health Sciences</option>
                            <option value="Arts & Humanities">Arts & Humanities</option>
                            <option value="Law & Governance">Law & Governance</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Election *</label>
                        <select name="election_id" id="editElectionId" class="form-control" required>
                            <option value="">Select Election</option>
                            <?php foreach ($elections as $election): ?>
                            <option value="<?php echo $election['id']; ?>"><?php echo htmlspecialchars($election['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Position *</label>
                        <select name="position" id="editPosition" class="form-control" required>
                            <option value="">Select Position</option>
                            <option value="President">President</option>
                            <option value="Vice President">Vice President</option>
                            <option value="Secretary">Secretary</option>
                            <option value="Treasurer">Treasurer</option>
                            <option value="Social Events Coordinator">Social Events Coordinator</option>
                            <option value="Academic Affairs Representative">Academic Affairs Representative</option>
                            <option value="Faculty Representative">Faculty Representative</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Manifesto *</label>
                    <textarea name="platform" id="editPlatform" class="form-control" required></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Profile Image</label>
                    <div id="editCurrentImage" style="margin-bottom: 1rem; display: none;">
                        <label style="font-size: 0.875rem; color: var(--text-secondary);">Current Image:</label>
                        <div style="margin-top: 0.5rem;">
                            <img id="editCurrentImagePreview" style="max-width: 100px; max-height: 100px; border-radius: 50%; border: 2px solid var(--border);">
                        </div>
                    </div>
                    <input type="file" name="profile_image" class="form-control" accept="image/*" onchange="previewImage(this, 'editPreview')">
                    <small style="color: var(--text-muted); margin-top: 0.5rem; display: block;">Maximum file size: 5MB. Supported formats: JPEG, PNG, GIF. Leave empty to keep current image.</small>
                    <div id="editPreview" style="margin-top: 1rem; display: none;">
                        <label style="font-size: 0.875rem; color: var(--text-secondary);">New Image Preview:</label>
                        <div style="margin-top: 0.5rem;">
                            <img id="editPreviewImg" style="max-width: 100px; max-height: 100px; border-radius: 50%; border: 2px solid var(--border);">
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Candidate</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reject Candidate Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Reject Candidate</h2>
                <button class="modal-close" onclick="closeRejectModal()">&times;</button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="candidate_id" id="rejectCandidateId">
                
                <div class="form-group">
                    <label class="form-label">Reason for Rejection *</label>
                    <textarea name="rejection_reason" class="form-control" placeholder="Please provide a reason for rejecting this candidate..." required></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Candidate</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h2 class="modal-title">Confirm Delete</h2>
                <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            
            <div style="margin-bottom: 2rem;">
                <p>Are you sure you want to delete this candidate? This action cannot be undone and will also remove all associated votes.</p>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="candidate_id" id="deleteCandidateId">
                    <button type="submit" class="btn btn-danger">Delete Candidate</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Store candidate data for editing from database
        const candidatesData = <?php echo json_encode($candidates); ?>;
        const votersData = <?php echo json_encode($voters); ?>;

        // Mobile navigation functions
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');

        // Toggle mobile menu
        hamburgerBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            hamburgerBtn.classList.toggle('active');
        });

        // Close menu when overlay is clicked
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            hamburgerBtn.classList.remove('active');
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                hamburgerBtn.classList.remove('active');
            }
        });

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            initializeFilters();
        });

        function initializeFilters() {
            const statusFilter = document.getElementById('statusFilter');
            const electionFilter = document.getElementById('electionFilter');
            const positionFilter = document.getElementById('positionFilter');
            const searchInput = document.getElementById('searchInput');

            statusFilter.addEventListener('change', filterCandidates);
            electionFilter.addEventListener('change', filterCandidates);
            positionFilter.addEventListener('change', filterCandidates);
            searchInput.addEventListener('input', filterCandidates);
        }

        function filterCandidates() {
            const statusFilter = document.getElementById('statusFilter').value;
            const electionFilter = document.getElementById('electionFilter').value;
            const positionFilter = document.getElementById('positionFilter').value;
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const cards = document.querySelectorAll('.candidate-card');

            cards.forEach(card => {
                const status = card.dataset.status;
                const election = card.dataset.election;
                const position = card.dataset.position;
                const name = card.dataset.name;
                
                const statusMatch = !statusFilter || status === statusFilter;
                const electionMatch = !electionFilter || election === electionFilter;
                const positionMatch = !positionFilter || position.includes(positionFilter.toLowerCase());
                const searchMatch = !searchTerm || name.includes(searchTerm);
                
                if (statusMatch && electionMatch && positionMatch && searchMatch) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Enhanced image preview with drag and drop
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const previewImg = document.getElementById(previewId + 'Img');
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    showNotification('File size too large. Maximum 5MB allowed.', 'error');
                    input.value = '';
                    preview.style.display = 'none';
                    return;
                }
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    showNotification('Invalid file type. Only JPEG, PNG, and GIF files are allowed.', 'error');
                    input.value = '';
                    preview.style.display = 'none';
                    return;
                }
                
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                    
                    // Add file info
                    const fileInfo = preview.querySelector('.image-info') || document.createElement('div');
                    fileInfo.className = 'image-info';
                    fileInfo.innerHTML = `
                        <strong>File:</strong> ${file.name}<br>
                        <strong>Size:</strong> ${(file.size / 1024).toFixed(1)} KB<br>
                        <strong>Type:</strong> ${file.type}
                    `;
                    if (!preview.querySelector('.image-info')) {
                        preview.appendChild(fileInfo);
                    }
                }
                
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        }

        // Drag and drop functionality
        function setupDragAndDrop() {
            const fileInputs = document.querySelectorAll('input[type="file"]');
            
            fileInputs.forEach(input => {
                const container = input.closest('.form-group');
                
                // Add drag and drop area
                const dragArea = document.createElement('div');
                dragArea.className = 'image-upload-area';
                dragArea.innerHTML = `
                    <i class="fas fa-cloud-upload-alt upload-icon"></i>
                    <div class="upload-text">
                        <strong>Drop image here</strong> or click to browse<br>
                        <small>Maximum 5MB • JPEG, PNG, GIF</small>
                    </div>
                    <div class="drag-drop-overlay">
                        <div style="text-align: center; color: var(--primary); font-weight: 600;">
                            <i class="fas fa-upload" style="font-size: 2rem; margin-bottom: 0.5rem;"></i><br>
                            Drop image here
                        </div>
                    </div>
                `;
                
                // Insert after input
                input.style.display = 'none';
                input.parentNode.insertBefore(dragArea, input.nextSibling);
                
                // Click to open file dialog
                dragArea.addEventListener('click', () => input.click());
                
                // Drag and drop events
                dragArea.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    dragArea.classList.add('drag-over');
                    dragArea.querySelector('.drag-drop-overlay').classList.add('active');
                });
                
                dragArea.addEventListener('dragleave', (e) => {
                    e.preventDefault();
                    if (!dragArea.contains(e.relatedTarget)) {
                        dragArea.classList.remove('drag-over');
                        dragArea.querySelector('.drag-drop-overlay').classList.remove('active');
                    }
                });
                
                dragArea.addEventListener('drop', (e) => {
                    e.preventDefault();
                    dragArea.classList.remove('drag-over');
                    dragArea.querySelector('.drag-drop-overlay').classList.remove('active');
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        input.files = files;
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            });
        }

        // Initialize drag and drop when page loads
        document.addEventListener('DOMContentLoaded', function() {
            setupDragAndDrop();
        });

        function populateVoterData(studentId) {
            const voter = votersData.find(v => v.student_id === studentId);
            if (voter) {
                document.querySelector('input[name="name"]').value = voter.full_name;
                document.querySelector('input[name="email"]').value = voter.email;
                document.querySelector('input[name="program"]').value = voter.program;
                document.querySelector('select[name="year"]').value = voter.year;
                document.querySelector('select[name="faculty"]').value = voter.faculty;
            }
        }

        function showCreateModal() {
            document.getElementById('createModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('createModal').classList.remove('active');
        }

        function showRejectModal(candidateId) {
            document.getElementById('rejectCandidateId').value = candidateId;
            document.getElementById('rejectModal').classList.add('active');
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.remove('active');
        }
        
        function viewCandidate(id) {
            const candidate = candidatesData.find(c => c.id == id);
            if (candidate) {
                const details = `
Candidate Details:

Name: ${candidate.full_name}
Student ID: ${candidate.student_id || 'N/A'}
Position: ${candidate.position}
Election: ${candidate.election_title || 'N/A'}
Status: ${candidate.status}
Votes: ${candidate.vote_count}
Program: ${candidate.program || 'N/A'}
Year: ${candidate.year || 'N/A'}
Faculty: ${candidate.faculty || 'N/A'}
Email: ${candidate.email || 'N/A'}

Manifesto: ${candidate.manifesto || 'No manifesto provided'}
                `;
                alert(details);
            }
        }

        function editCandidate(id) {
            const candidate = candidatesData.find(c => c.id == id);
            if (candidate) {
                // Populate edit form with real database data
                document.getElementById('editCandidateId').value = candidate.id;
                document.getElementById('editName').value = candidate.full_name;
                document.getElementById('editStudentId').value = candidate.student_id || '';
                document.getElementById('editEmail').value = candidate.email || '';
                document.getElementById('editProgram').value = candidate.program || '';
                document.getElementById('editYear').value = candidate.year || '';
                document.getElementById('editFaculty').value = candidate.faculty || '';
                document.getElementById('editElectionId').value = candidate.election_id;
                document.getElementById('editPosition').value = candidate.position;
                document.getElementById('editPlatform').value = candidate.manifesto || '';
                
                // Handle existing image
                const existingImage = candidate.profile_image || candidate.voter_profile_image;
                document.getElementById('editExistingImage').value = existingImage || '';
                
                const currentImageDiv = document.getElementById('editCurrentImage');
                const currentImagePreview = document.getElementById('editCurrentImagePreview');
                
                if (existingImage) {
                    currentImagePreview.src = existingImage;
                    currentImageDiv.style.display = 'block';
                } else {
                    currentImageDiv.style.display = 'none';
                }
                
                // Reset file input and preview
                const fileInput = document.querySelector('#editForm input[type="file"]');
                fileInput.value = '';
                document.getElementById('editPreview').style.display = 'none';
                
                // Show edit modal
                document.getElementById('editModal').classList.add('active');
            }
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        function deleteCandidate(id) {
            document.getElementById('deleteCandidateId').value = id;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }

        function exportCandidates() {
            // Create CSV content from real database data
            let csvContent = "data:text/csv;charset=utf-8,";
            csvContent += "Name,Student ID,Email,Program,Year,Faculty,Position,Election,Status,Manifesto,Vote Count,Created At\n";
            
            candidatesData.forEach(candidate => {
                const row = [
                    candidate.full_name,
                    candidate.student_id || '',
                    candidate.email || '',
                    candidate.program || '',
                    candidate.year || '',
                    candidate.faculty || '',
                    candidate.position,
                    candidate.election_title || '',
                    candidate.status,
                    (candidate.manifesto || '').replace(/"/g, '""'),
                    candidate.vote_count,
                    candidate.created_at
                ].map(field => `"${field}"`).join(",");
                csvContent += row + "\n";
            });

            // Create download link
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `candidates_${new Date().toISOString().split('T')[0]}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            showNotification('Candidates exported successfully!', 'success');
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-${type === 'info' ? 'info-circle' : type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                    <span>${message}</span>
                </div>
            `;
            
            notification.style.cssText = `
                position: fixed;
                top: 2rem;
                right: 2rem;
                background: white;
                padding: 1rem 1.5rem;
                border-radius: 0.5rem;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                border-left: 4px solid var(--${type === 'info' ? 'info' : type === 'success' ? 'success' : 'error'});
                z-index: 1001;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease;
                max-width: 400px;
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 5000);
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.classList.remove('active');
                }
            });
        }

        // Close modals with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.classList.remove('active');
                });
                
                // Also close mobile menu
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                hamburgerBtn.classList.remove('active');
            }
        });

        // Auto-hide success alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const requiredFields = form.querySelectorAll('[required]');
                    let isValid = true;
                    
                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            isValid = false;
                            field.style.borderColor = 'var(--error)';
                            field.addEventListener('input', function() {
                                field.style.borderColor = '';
                            }, { once: true });
                        }
                    });
                    
                    if (!isValid) {
                        e.preventDefault();
                        showNotification('Please fill in all required fields.', 'error');
                    }
                });
            });
        });

        // Real-time search with highlighting
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            if (searchTerm.length > 0) {
                const cards = document.querySelectorAll('.candidate-card');
                cards.forEach(card => {
                    const nameElement = card.querySelector('.candidate-name');
                    const originalName = nameElement.dataset.originalText || nameElement.textContent;
                    nameElement.dataset.originalText = originalName;
                    
                    if (originalName.toLowerCase().includes(searchTerm)) {
                        const highlightedName = originalName.replace(
                            new RegExp(`(${searchTerm})`, 'gi'),
                            '<mark style="background-color: #fef3c7; padding: 0 2px;">$1</mark>'
                        );
                        nameElement.innerHTML = highlightedName;
                    } else {
                        nameElement.textContent = originalName;
                    }
                });
            } else {
                // Remove highlights
                const nameElements = document.querySelectorAll('.candidate-name');
                nameElements.forEach(element => {
                    if (element.dataset.originalText) {
                        element.textContent = element.dataset.originalText;
                    }
                });
            }
        });

        console.log('Database-integrated Candidates Management initialized');
        console.log('Features available:', {
            'realTimeDatabase': 'All operations sync with voting_system database',
            'candidateManagement': 'Full CRUD operations with real data',
            'statusFiltering': 'Filter by actual candidate status',
            'searchFunctionality': 'Search through real candidate data',
            'csvExport': 'Export real candidates to CSV',
            'mobileResponsive': 'Optimized for all screen sizes',
            'modalInteractions': 'Add, edit, approve, reject with database updates',
            'formValidation': 'Client-side validation with server sync',
            'auditLogging': 'All actions logged to audit_logs table'
        });
        console.log('Database Statistics:', {
            'totalCandidates': <?php echo $total_candidates; ?>,
            'activeCandidates': <?php echo $approved_candidates; ?>,
            'pendingCandidates': <?php echo $pending_candidates; ?>,
            'totalVotes': <?php echo $total_votes; ?>
        });
    </script>
</body>
</html>