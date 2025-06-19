<?php
// admin_elections.php - Complete Elections Management System
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Database configuration
$host = 'localhost';
$dbname = 'voting_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Database helper functions
function fetchAll($query, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [];
    }
}

function executeQuery($query, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($query);
        return $stmt->execute($params);
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

function fetchOne($query, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// Election management functions
function getElectionStats() {
    $stats = [
        'total_elections' => 0,
        'active_elections' => 0,
        'draft_elections' => 0,
        'completed_elections' => 0,
        'cancelled_elections' => 0,
        'total_candidates' => 0,
        'total_votes' => 0
    ];
    
    // Get election counts by status
    $result = fetchAll("SELECT status, COUNT(*) as count FROM elections GROUP BY status");
    foreach ($result as $row) {
        $stats[$row['status'] . '_elections'] = (int)$row['count'];
        $stats['total_elections'] += (int)$row['count'];
    }
    
    // Get total candidates
    $candidates = fetchAll("SELECT COUNT(*) as count FROM candidates");
    if (!empty($candidates)) {
        $stats['total_candidates'] = (int)$candidates[0]['count'];
    }
    
    // Get total votes
    $votes = fetchAll("SELECT COUNT(*) as count FROM votes");
    if (!empty($votes)) {
        $stats['total_votes'] = (int)$votes[0]['count'];
    }
    
    return $stats;
}

function getElections($filters = [], $limit = null, $offset = 0) {
    $where_conditions = [];
    $params = [];
    
    // Build WHERE clause based on filters
    if (!empty($filters['status'])) {
        $where_conditions[] = "e.status = ?";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['election_type'])) {
        $where_conditions[] = "e.election_type = ?";
        $params[] = $filters['election_type'];
    }
    
    if (!empty($filters['search'])) {
        $where_conditions[] = "(e.title LIKE ? OR e.description LIKE ?)";
        $search_term = '%' . $filters['search'] . '%';
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    $limit_clause = $limit ? "LIMIT $limit OFFSET $offset" : '';
    
    $query = "
        SELECT e.*, 
               au.fullname as created_by_name,
               COUNT(DISTINCT c.id) as candidate_count,
               COUNT(DISTINCT v.id) as vote_count
        FROM elections e 
        LEFT JOIN admin_users au ON e.created_by = au.id
        LEFT JOIN candidates c ON e.id = c.election_id
        LEFT JOIN votes v ON e.id = v.election_id
        $where_clause
        GROUP BY e.id
        ORDER BY e.created_at DESC
        $limit_clause
    ";
    
    return fetchAll($query, $params);
}

function createElection($data) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Insert election
        $query = "
            INSERT INTO elections (
                title, description, election_type, start_date, end_date, 
                status, eligible_years, eligible_faculties, max_votes_per_voter,
                allow_multiple_candidates, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $eligible_years = !empty($data['eligible_years']) ? json_encode($data['eligible_years']) : null;
        $eligible_faculties = !empty($data['eligible_faculties']) ? json_encode($data['eligible_faculties']) : null;
        
        $params = [
            $data['title'],
            $data['description'],
            $data['election_type'],
            $data['start_date'],
            $data['end_date'],
            $data['status'],
            $eligible_years,
            $eligible_faculties,
            $data['max_votes_per_voter'] ?? 1,
            isset($data['allow_multiple_candidates']) ? 1 : 0,
            $_SESSION['admin_id']
        ];
        
        executeQuery($query, $params);
        $election_id = $pdo->lastInsertId();
        
        // Create positions if provided
        if (!empty($data['positions'])) {
            foreach ($data['positions'] as $position) {
                if (!empty(trim($position['title']))) {
                    $pos_query = "INSERT INTO positions (election_id, title, description, max_candidates, display_order) VALUES (?, ?, ?, ?, ?)";
                    executeQuery($pos_query, [
                        $election_id,
                        $position['title'],
                        $position['description'] ?? '',
                        $position['max_candidates'] ?? 10,
                        $position['display_order'] ?? 0
                    ]);
                }
            }
        }
        
        // Log the action
        logAuditAction('admin', $_SESSION['admin_id'], 'CREATE', 'election', $election_id, null, $data);
        
        $pdo->commit();
        return $election_id;
        
    } catch (Exception $e) {
        $pdo->rollback();
        error_log("Error creating election: " . $e->getMessage());
        return false;
    }
}

function updateElection($election_id, $data) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Get old values for audit log
        $old_election = fetchOne("SELECT * FROM elections WHERE id = ?", [$election_id]);
        
        $query = "
            UPDATE elections SET 
                title = ?, description = ?, election_type = ?, 
                start_date = ?, end_date = ?, status = ?,
                eligible_years = ?, eligible_faculties = ?,
                max_votes_per_voter = ?, allow_multiple_candidates = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ";
        
        $eligible_years = !empty($data['eligible_years']) ? json_encode($data['eligible_years']) : null;
        $eligible_faculties = !empty($data['eligible_faculties']) ? json_encode($data['eligible_faculties']) : null;
        
        $params = [
            $data['title'],
            $data['description'],
            $data['election_type'],
            $data['start_date'],
            $data['end_date'],
            $data['status'],
            $eligible_years,
            $eligible_faculties,
            $data['max_votes_per_voter'] ?? 1,
            isset($data['allow_multiple_candidates']) ? 1 : 0,
            $election_id
        ];
        
        executeQuery($query, $params);
        
        // Log the action
        logAuditAction('admin', $_SESSION['admin_id'], 'UPDATE', 'election', $election_id, $old_election, $data);
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollback();
        error_log("Error updating election: " . $e->getMessage());
        return false;
    }
}

function deleteElection($election_id) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Get election data for audit log
        $election = fetchOne("SELECT * FROM elections WHERE id = ?", [$election_id]);
        
        if (!$election) {
            return false;
        }
        
        // Check if election has votes
        $vote_count = fetchOne("SELECT COUNT(*) as count FROM votes WHERE election_id = ?", [$election_id]);
        if ($vote_count['count'] > 0) {
            throw new Exception("Cannot delete election with existing votes");
        }
        
        // Delete related data (candidates, positions)
        executeQuery("DELETE FROM candidates WHERE election_id = ?", [$election_id]);
        executeQuery("DELETE FROM positions WHERE election_id = ?", [$election_id]);
        
        // Delete election
        executeQuery("DELETE FROM elections WHERE id = ?", [$election_id]);
        
        // Log the action
        logAuditAction('admin', $_SESSION['admin_id'], 'DELETE', 'election', $election_id, $election, null);
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollback();
        error_log("Error deleting election: " . $e->getMessage());
        return false;
    }
}

function getElectionById($election_id) {
    $election = fetchOne("
        SELECT e.*, 
               au.fullname as created_by_name,
               COUNT(DISTINCT c.id) as candidate_count,
               COUNT(DISTINCT v.id) as vote_count
        FROM elections e 
        LEFT JOIN admin_users au ON e.created_by = au.id
        LEFT JOIN candidates c ON e.id = c.election_id
        LEFT JOIN votes v ON e.id = v.election_id
        WHERE e.id = ?
        GROUP BY e.id
    ", [$election_id]);
    
    if ($election) {
        // Get positions for this election
        $election['positions'] = fetchAll("
            SELECT * FROM positions 
            WHERE election_id = ? 
            ORDER BY display_order, title
        ", [$election_id]);
        
        // Decode JSON fields
        $election['eligible_years'] = json_decode($election['eligible_years'], true) ?? [];
        $election['eligible_faculties'] = json_decode($election['eligible_faculties'], true) ?? [];
    }
    
    return $election;
}

function logAuditAction($user_type, $user_id, $action, $entity_type, $entity_id, $old_values, $new_values) {
    $query = "
        INSERT INTO audit_logs (user_type, user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $params = [
        $user_type,
        $user_id,
        $action,
        $entity_type,
        $entity_id,
        $old_values ? json_encode($old_values) : null,
        $new_values ? json_encode($new_values) : null,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ];
    
    executeQuery($query, $params);
}

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['ajax']) {
        case 'get_election':
            $election_id = $_GET['id'] ?? 0;
            $election = getElectionById($election_id);
            echo json_encode($election ?: []);
            exit;
            
        case 'get_stats':
            echo json_encode(getElectionStats());
            exit;
            
        case 'delete_election':
            $election_id = $_POST['id'] ?? 0;
            $result = deleteElection($election_id);
            echo json_encode(['success' => $result]);
            exit;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['ajax'])) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $data = [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'election_type' => $_POST['election_type'] ?? 'general',
                'start_date' => $_POST['start_date'] ?? '',
                'end_date' => $_POST['end_date'] ?? '',
                'status' => $_POST['status'] ?? 'draft',
                'eligible_years' => $_POST['eligible_years'] ?? [],
                'eligible_faculties' => $_POST['eligible_faculties'] ?? [],
                'max_votes_per_voter' => $_POST['max_votes_per_voter'] ?? 1,
                'allow_multiple_candidates' => isset($_POST['allow_multiple_candidates'])
            ];
            
            // Handle positions
            if (isset($_POST['positions'])) {
                $data['positions'] = $_POST['positions'];
            }
            
            $result = createElection($data);
            if ($result) {
                header('Location: admin_elections.php?success=created');
            } else {
                header('Location: admin_elections.php?error=create_failed');
            }
            exit;
            
        case 'update':
            $election_id = $_POST['election_id'] ?? 0;
            $data = [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'election_type' => $_POST['election_type'] ?? 'general',
                'start_date' => $_POST['start_date'] ?? '',
                'end_date' => $_POST['end_date'] ?? '',
                'status' => $_POST['status'] ?? 'draft',
                'eligible_years' => $_POST['eligible_years'] ?? [],
                'eligible_faculties' => $_POST['eligible_faculties'] ?? [],
                'max_votes_per_voter' => $_POST['max_votes_per_voter'] ?? 1,
                'allow_multiple_candidates' => isset($_POST['allow_multiple_candidates'])
            ];
            
            $result = updateElection($election_id, $data);
            if ($result) {
                header('Location: admin_elections.php?success=updated');
            } else {
                header('Location: admin_elections.php?error=update_failed');
            }
            exit;
            
        case 'update_status':
            $election_id = $_POST['election_id'] ?? 0;
            $status = $_POST['status'] ?? '';
            
            $result = executeQuery("UPDATE elections SET status = ? WHERE id = ?", [$status, $election_id]);
            if ($result) {
                logAuditAction('admin', $_SESSION['admin_id'], 'UPDATE_STATUS', 'election', $election_id, null, ['status' => $status]);
                header('Location: admin_elections.php?success=status_updated');
            } else {
                header('Location: admin_elections.php?error=status_update_failed');
            }
            exit;
    }
}

// Get filters from URL
$filters = [];
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $filters['status'] = $_GET['status'];
}
if (isset($_GET['type']) && $_GET['type'] !== '') {
    $filters['election_type'] = $_GET['type'];
}
if (isset($_GET['search']) && $_GET['search'] !== '') {
    $filters['search'] = $_GET['search'];
}

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Get elections and stats
$elections = getElections($filters, $limit, $offset);
$stats = getElectionStats();

// Get total count for pagination
$total_query = "SELECT COUNT(*) as count FROM elections e";
$where_conditions = [];
$count_params = [];

if (!empty($filters['status'])) {
    $where_conditions[] = "e.status = ?";
    $count_params[] = $filters['status'];
}
if (!empty($filters['election_type'])) {
    $where_conditions[] = "e.election_type = ?";
    $count_params[] = $filters['election_type'];
}
if (!empty($filters['search'])) {
    $where_conditions[] = "(e.title LIKE ? OR e.description LIKE ?)";
    $search_term = '%' . $filters['search'] . '%';
    $count_params[] = $search_term;
    $count_params[] = $search_term;
}

if (!empty($where_conditions)) {
    $total_query .= " WHERE " . implode(' AND ', $where_conditions);
}

$total_result = fetchOne($total_query, $count_params);
$total_elections = $total_result['count'] ?? 0;
$total_pages = ceil($total_elections / $limit);

// Get distinct values for filters
$years = fetchAll("SELECT DISTINCT year FROM voters ORDER BY year");
$faculties = fetchAll("SELECT DISTINCT faculty FROM voters ORDER BY faculty");

// Get admin user info (you might need to adjust this based on your admin_users table structure)
$admin_user = fetchOne("SELECT * FROM admin_users WHERE id = ?", [$_SESSION['admin_id']]) ?? ['fullname' => 'Admin User', 'role' => 'Administrator'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elections Management - Voting System Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS Variables */
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #a5b4fc;
            --secondary: #8b5cf6;
            --accent: #f43f5e;
            --background: #f8fafc;
            --surface: #ffffff;
            --surface-hover: #f1f5f9;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --border: #e2e8f0;
            --border-dark: #cbd5e1;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --info: #3b82f6;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
        }

        /* Reset & Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--background);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
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

        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
            transition: margin-left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Header */
        .header {
            background: var(--surface);
            padding: 1.5rem 2rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid var(--border);
        }

        .header h1 {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .header .subtitle {
            color: var(--text-secondary);
            margin-top: 0.25rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }

        .stat-card .icon {
            width: 3rem;
            height: 3rem;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .stat-card .label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Filters */
        .filters {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
            border: 1px solid var(--border);
        }

        .filters-row {
            display: flex;
            gap: 1rem;
            align-items: end;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            transition: all 0.2s ease;
            background: var(--surface);
            color: var(--text-primary);
        }

        .form-control::placeholder {
            color: var(--text-muted);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgb(99 102 241 / 0.1);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }

        .btn-secondary {
            background: var(--text-secondary);
            color: white;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-danger {
            background: var(--error);
            color: white;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-primary);
        }

        .btn-outline:hover {
            background: var(--surface-hover);
            border-color: var(--border-dark);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
        }

        /* Table */
        .table-container {
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .table th {
            background: var(--surface-hover);
            font-weight: 600;
            color: var(--text-primary);
        }

        .table tbody tr {
            color: var(--text-primary);
        }

        .table tbody tr:hover {
            background: var(--surface-hover);
        }

        /* Status badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-draft { 
            background: rgb(107 114 128 / 0.1); 
            color: #374151; 
            border: 1px solid rgb(107 114 128 / 0.2);
        }
        .status-active { 
            background: rgb(16 185 129 / 0.1); 
            color: #059669; 
            border: 1px solid rgb(16 185 129 / 0.2);
        }
        .status-completed { 
            background: rgb(59 130 246 / 0.1); 
            color: #2563eb; 
            border: 1px solid rgb(59 130 246 / 0.2);
        }
        .status-cancelled { 
            background: rgb(239 68 68 / 0.1); 
            color: #dc2626; 
            border: 1px solid rgb(239 68 68 / 0.2);
        }

        /* Modal */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--surface);
            border-radius: var(--radius-lg);
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            padding: 1.5rem;
            border-top: 1px solid var(--border);
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        /* Form styling */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .checkbox-item input[type="checkbox"] {
            accent-color: var(--primary);
        }

        .checkbox-item label {
            color: var(--text-primary);
            font-size: 0.875rem;
            margin: 0;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            text-decoration: none;
            color: var(--text-primary);
            background: var(--surface);
            transition: all 0.2s ease;
        }

        .pagination a:hover {
            background: var(--surface-hover);
            border-color: var(--border-dark);
        }

        .pagination .current {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
            border: 1px solid transparent;
            backdrop-filter: blur(10px);
        }

        .alert-success {
            background: rgb(16 185 129 / 0.1);
            border-color: rgb(16 185 129 / 0.2);
            color: #059669;
        }

        .alert-error {
            background: rgb(239 68 68 / 0.1);
            border-color: rgb(239 68 68 / 0.2);
            color: #dc2626;
        }

        .alert-info {
            background: rgb(59 130 246 / 0.1);
            border-color: rgb(59 130 246 / 0.2);
            color: #2563eb;
        }

        /* Responsive */
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
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .filters-row {
                flex-direction: column;
                align-items: stretch;
            }

            .table-container {
                overflow-x: auto;
            }

            .modal-content {
                width: 95%;
                margin: 1rem;
            }

            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 1rem;
            }

            .header {
                padding: 1rem;
            }

            .stat-card {
                padding: 1rem;
            }

            .filters {
                padding: 1rem;
            }
        }

        /* Loading spinner */
        .spinner {
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            width: 1rem;
            height: 1rem;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-secondary);
            padding: 0;
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            transition: all 0.2s ease;
        }

        .close-btn:hover {
            background: var(--surface-hover);
            color: var(--text-primary);
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--text-muted);
        }

        .empty-state h3 {
            color: var(--text-primary);
            margin-bottom: 0.5rem;
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

        /* Status menu styling */
        .status-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            z-index: 100;
            min-width: 150px;
        }

        .status-menu button {
            width: 100%;
            text-align: left;
            padding: 0.75rem 1rem;
            border: none;
            background: none;
            cursor: pointer;
            border-bottom: 1px solid var(--border);
            color: var(--text-primary);
            transition: background-color 0.2s ease;
        }

        .status-menu button:hover {
            background: var(--surface-hover);
        }

        .status-menu button:last-child {
            border-bottom: none;
        }

        .status-menu button i {
            margin-right: 0.5rem;
            color: var(--text-secondary);
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

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="admin_dashboard.php" class="logo">
                <i class="fas fa-shield-alt"></i>
                <span>VoteAdmin</span>
            </a>
            <div class="admin-info">
                <div class="admin-name"><?php echo htmlspecialchars($admin_user['fullname'] ?? 'Admin User'); ?></div>
                <div class="admin-role"><?php echo htmlspecialchars($admin_user['role'] ?? 'Administrator'); ?></div>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <a href="admin_dashboard.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="admin_elections.php" class="nav-item active">
                    <i class="fas fa-poll"></i>
                    <span>Elections</span>
                </a>
                <a href="admin_candidates.php" class="nav-item">
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
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div>
                <h1>Elections Management</h1>
                <p class="subtitle">Create and manage voting elections</p>
            </div>
            <button class="btn btn-primary" onclick="openCreateModal()">
                <i class="fas fa-plus"></i> New Election
            </button>
        </div>

        <!-- Alerts -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php
                switch ($_GET['success']) {
                    case 'created': echo 'Election created successfully!'; break;
                    case 'updated': echo 'Election updated successfully!'; break;
                    case 'deleted': echo 'Election deleted successfully!'; break;
                    case 'status_updated': echo 'Election status updated successfully!'; break;
                    default: echo 'Operation completed successfully!';
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php
                switch ($_GET['error']) {
                    case 'create_failed': echo 'Failed to create election. Please try again.'; break;
                    case 'update_failed': echo 'Failed to update election. Please try again.'; break;
                    case 'delete_failed': echo 'Failed to delete election. Please try again.'; break;
                    case 'status_update_failed': echo 'Failed to update election status. Please try again.'; break;
                    default: echo 'An error occurred. Please try again.';
                }
                ?>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon" style="background: rgba(99, 102, 241, 0.2); color: #a5b4fc;">
                    <i class="fas fa-poll"></i>
                </div>
                <div class="value"><?= number_format($stats['total_elections']) ?></div>
                <div class="label">Total Elections</div>
            </div>

            <div class="stat-card">
                <div class="icon" style="background: rgba(16, 185, 129, 0.2); color: #6ee7b7;">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="value"><?= number_format($stats['active_elections']) ?></div>
                <div class="label">Active Elections</div>
            </div>

            <div class="stat-card">
                <div class="icon" style="background: rgba(245, 158, 11, 0.2); color: #fbbf24;">
                    <i class="fas fa-edit"></i>
                </div>
                <div class="value"><?= number_format($stats['draft_elections']) ?></div>
                <div class="label">Draft Elections</div>
            </div>

            <div class="stat-card">
                <div class="icon" style="background: rgba(59, 130, 246, 0.2); color: #93c5fd;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="value"><?= number_format($stats['completed_elections']) ?></div>
                <div class="label">Completed Elections</div>
            </div>

            <div class="stat-card">
                <div class="icon" style="background: rgba(139, 92, 246, 0.2); color: #c4b5fd;">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="value"><?= number_format($stats['total_candidates']) ?></div>
                <div class="label">Total Candidates</div>
            </div>

            <div class="stat-card">
                <div class="icon" style="background: rgba(244, 63, 94, 0.2); color: #fb7185;">
                    <i class="fas fa-vote-yea"></i>
                </div>
                <div class="value"><?= number_format($stats['total_votes']) ?></div>
                <div class="label">Total Votes</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" action="">
                <div class="filters-row">
                    <div class="filter-group">
                        <label for="search">Search Elections</label>
                        <input type="text" id="search" name="search" class="form-control" 
                               placeholder="Search by title or description..." 
                               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>

                    <div class="filter-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="draft" <?= ($_GET['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="active" <?= ($_GET['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="completed" <?= ($_GET['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= ($_GET['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="type">Election Type</label>
                        <select id="type" name="type" class="form-control">
                            <option value="">All Types</option>
                            <option value="general" <?= ($_GET['type'] ?? '') === 'general' ? 'selected' : '' ?>>General</option>
                            <option value="faculty" <?= ($_GET['type'] ?? '') === 'faculty' ? 'selected' : '' ?>>Faculty</option>
                            <option value="departmental" <?= ($_GET['type'] ?? '') === 'departmental' ? 'selected' : '' ?>>Departmental</option>
                            <option value="class" <?= ($_GET['type'] ?? '') === 'class' ? 'selected' : '' ?>>Class</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="admin_elections.php" class="btn btn-outline">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Elections Table -->
        <div class="table-container">
            <?php if (empty($elections)): ?>
                <div class="empty-state">
                    <i class="fas fa-poll"></i>
                    <h3>No Elections Found</h3>
                    <p>No elections match your current filters. Try adjusting your search criteria or create a new election.</p>
                    <button class="btn btn-primary" onclick="openCreateModal()" style="margin-top: 1rem;">
                        <i class="fas fa-plus"></i> Create First Election
                    </button>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Election Details</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Duration</th>
                            <th>Candidates</th>
                            <th>Votes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($elections as $election): ?>
                            <tr>
                                <td>
                                    <div>
                                        <div style="font-weight: 600; margin-bottom: 0.25rem; color: white;">
                                            <?= htmlspecialchars($election['title']) ?>
                                        </div>
                                        <div style="font-size: 0.875rem; color: var(--text-secondary);">
                                            <?= htmlspecialchars(substr($election['description'], 0, 100)) ?>
                                            <?= strlen($election['description']) > 100 ? '...' : '' ?>
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
                                            Created by: <?= htmlspecialchars($election['created_by_name'] ?? 'Unknown') ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span style="text-transform: capitalize; color: var(--text-primary);">
                                        <?= htmlspecialchars($election['election_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $election['status'] ?>">
                                        <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                                        <?= ucfirst($election['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-size: 0.875rem; color: var(--text-primary);">
                                        <div>Start: <?= date('M j, Y g:i A', strtotime($election['start_date'])) ?></div>
                                        <div>End: <?= date('M j, Y g:i A', strtotime($election['end_date'])) ?></div>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-weight: 600; font-size: 1.125rem; color: var(--text-primary);">
                                        <?= number_format($election['candidate_count']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="font-weight: 600; font-size: 1.125rem; color: var(--text-primary);">
                                        <?= number_format($election['vote_count']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-outline" 
                                                onclick="viewElection(<?= $election['id'] ?>)"
                                                title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-primary" 
                                                onclick="editElection(<?= $election['id'] ?>)"
                                                title="Edit Election">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <div style="position: relative; display: inline-block;">
                                            <button class="btn btn-sm btn-secondary" 
                                                    onclick="toggleStatusMenu(<?= $election['id'] ?>)"
                                                    title="Change Status">
                                                <i class="fas fa-exchange-alt"></i>
                                            </button>
                                            <div id="status-menu-<?= $election['id'] ?>" class="status-menu" style="display: none;">
                                                <form method="POST" style="margin: 0;">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="election_id" value="<?= $election['id'] ?>">
                                                    <button type="submit" name="status" value="draft">
                                                        <i class="fas fa-edit"></i> Draft
                                                    </button>
                                                    <button type="submit" name="status" value="active">
                                                        <i class="fas fa-play"></i> Active
                                                    </button>
                                                    <button type="submit" name="status" value="completed">
                                                        <i class="fas fa-check"></i> Completed
                                                    </button>
                                                    <button type="submit" name="status" value="cancelled">
                                                        <i class="fas fa-times"></i> Cancelled
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        <?php if ($election['vote_count'] == 0): ?>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="deleteElection(<?= $election['id'] ?>)"
                                                    title="Delete Election">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">&laquo; Previous</a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Create/Edit Election Modal -->
    <div id="electionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Create New Election</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="electionForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="election_id" id="electionId">
                
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="title">Election Title *</label> 
                            <input type="text" id="title" name="title" class="form-control" required
                                   placeholder="e.g., Student Union President Election 2025">
                        </div>

                        <div class="form-group">
                            <label for="election_type">Election Type *</label>
                            <select id="election_type" name="election_type" class="form-control" required>
                                <option value="general">General Election</option>
                                <option value="faculty">Faculty Election</option>
                                <option value="departmental">Departmental Election</option>
                                <option value="class">Class Election</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="4"
                                  placeholder="Provide details about this election..."></textarea>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="start_date">Start Date & Time *</label>
                            <input type="datetime-local" id="start_date" name="start_date" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="end_date">End Date & Time *</label>
                            <input type="datetime-local" id="end_date" name="end_date" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="max_votes_per_voter">Max Votes Per Voter</label>
                            <input type="number" id="max_votes_per_voter" name="max_votes_per_voter" 
                                   class="form-control" min="1" value="1">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="allow_multiple_candidates" name="allow_multiple_candidates">
                            Allow multiple candidates per position
                        </label>
                    </div>

                    <div class="form-group">
                        <label>Eligible Years</label>
                        <div class="checkbox-group">
                            <?php foreach ($years as $year): ?>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="eligible_years[]" value="<?= htmlspecialchars($year['year']) ?>" 
                                           id="year_<?= htmlspecialchars($year['year']) ?>">
                                    <label for="year_<?= htmlspecialchars($year['year']) ?>"><?= htmlspecialchars($year['year']) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Eligible Faculties</label>
                        <div class="checkbox-group">
                            <?php foreach ($faculties as $faculty): ?>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="eligible_faculties[]" value="<?= htmlspecialchars($faculty['faculty']) ?>" 
                                           id="faculty_<?= str_replace(' ', '_', $faculty['faculty']) ?>">
                                    <label for="faculty_<?= str_replace(' ', '_', $faculty['faculty']) ?>"><?= htmlspecialchars($faculty['faculty']) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span id="submitText">Create Election</span>
                        <div class="spinner" id="submitSpinner" style="display: none; margin-left: 0.5rem;"></div>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Election Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Election Details</h3>
                <button class="close-btn" onclick="closeViewModal()">&times;</button>
            </div>
            <div class="modal-body" id="viewModalBody">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeViewModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let statusMenus = {};

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

        // Modal functions
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Create New Election';
            document.getElementById('formAction').value = 'create';
            document.getElementById('submitText').textContent = 'Create Election';
            document.getElementById('electionForm').reset();
            document.getElementById('electionModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('electionModal').classList.remove('active');
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.remove('active');
        }

        // Election functions
        async function editElection(id) {
            try {
                const response = await fetch(`?ajax=get_election&id=${id}`);
                const election = await response.json();
                
                if (!election.id) {
                    alert('Election not found');
                    return;
                }

                // Fill form with election data
                document.getElementById('modalTitle').textContent = 'Edit Election';
                document.getElementById('formAction').value = 'update';
                document.getElementById('electionId').value = election.id;
                document.getElementById('submitText').textContent = 'Update Election';
                
                document.getElementById('title').value = election.title;
                document.getElementById('description').value = election.description || '';
                document.getElementById('election_type').value = election.election_type;
                document.getElementById('start_date').value = election.start_date.replace(' ', 'T');
                document.getElementById('end_date').value = election.end_date.replace(' ', 'T');
                document.getElementById('status').value = election.status;
                document.getElementById('max_votes_per_voter').value = election.max_votes_per_voter;
                document.getElementById('allow_multiple_candidates').checked = election.allow_multiple_candidates == 1;

                // Check eligible years
                const eligibleYears = election.eligible_years || [];
                document.querySelectorAll('input[name="eligible_years[]"]').forEach(checkbox => {
                    checkbox.checked = eligibleYears.includes(checkbox.value);
                });

                // Check eligible faculties
                const eligibleFaculties = election.eligible_faculties || [];
                document.querySelectorAll('input[name="eligible_faculties[]"]').forEach(checkbox => {
                    checkbox.checked = eligibleFaculties.includes(checkbox.value);
                });

                document.getElementById('electionModal').classList.add('active');
            } catch (error) {
                console.error('Error fetching election:', error);
                alert('Error loading election data');
            }
        }

        async function viewElection(id) {
            try {
                const response = await fetch(`?ajax=get_election&id=${id}`);
                const election = await response.json();
                
                if (!election.id) {
                    alert('Election not found');
                    return;
                }

                const modalBody = document.getElementById('viewModalBody');
                modalBody.innerHTML = `
                    <div style="display: grid; gap: 1.5rem; color: var(--text-primary);">
                        <div>
                            <h4 style="margin-bottom: 0.5rem; color: var(--text-primary);">${election.title}</h4>
                            <p style="color: var(--text-secondary); margin-bottom: 1rem;">${election.description || 'No description provided'}</p>
                            
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                                <div>
                                    <strong>Type:</strong> ${election.election_type.charAt(0).toUpperCase() + election.election_type.slice(1)}
                                </div>
                                <div>
                                    <strong>Status:</strong> 
                                    <span class="status-badge status-${election.status}">
                                        <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                                        ${election.status.charAt(0).toUpperCase() + election.status.slice(1)}
                                    </span>
                                </div>
                                <div>
                                    <strong>Candidates:</strong> ${election.candidate_count}
                                </div>
                                <div>
                                    <strong>Votes:</strong> ${election.vote_count}
                                </div>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div>
                                    <strong>Start Date:</strong><br>
                                    ${new Date(election.start_date).toLocaleString()}
                                </div>
                                <div>
                                    <strong>End Date:</strong><br>
                                    ${new Date(election.end_date).toLocaleString()}
                                </div>
                            </div>
                            
                            <div style="margin-bottom: 1rem;">
                                <strong>Max Votes Per Voter:</strong> ${election.max_votes_per_voter}<br>
                                <strong>Multiple Candidates:</strong> ${election.allow_multiple_candidates ? 'Yes' : 'No'}
                            </div>
                            
                            ${election.eligible_years && election.eligible_years.length > 0 ? `
                                <div style="margin-bottom: 1rem;">
                                    <strong>Eligible Years:</strong> ${election.eligible_years.join(', ')}
                                </div>
                            ` : ''}
                            
                            ${election.eligible_faculties && election.eligible_faculties.length > 0 ? `
                                <div style="margin-bottom: 1rem;">
                                    <strong>Eligible Faculties:</strong> ${election.eligible_faculties.join(', ')}
                                </div>
                            ` : ''}
                            
                            <div style="margin-bottom: 1rem;">
                                <strong>Created By:</strong> ${election.created_by_name || 'Unknown'}<br>
                                <strong>Created At:</strong> ${new Date(election.created_at).toLocaleString()}
                            </div>
                            
                            ${election.positions && election.positions.length > 0 ? `
                                <div>
                                <strong>Positions:</strong>
                                <ul style="margin-top: 0.5rem; padding-left: 1.5rem;">
                                    ${election.positions.map(position => `
                                        <li style="margin-bottom: 0.5rem;">
                                            <strong>${position.title}</strong>
                                            ${position.description ? `<br><span style="color: var(--text-secondary); font-size: 0.875rem;">${position.description}</span>` : ''}
                                            <br><span style="font-size: 0.75rem; color: var(--text-muted);">Max candidates: ${position.max_candidates}</span>
                                        </li>
                                    `).join('')}
                                </ul>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;

                document.getElementById('viewModal').classList.add('active');
            } catch (error) {
                console.error('Error fetching election:', error);
                alert('Error loading election data');
            }
        }

        async function deleteElection(id) {
            if (!confirm('Are you sure you want to delete this election? This action cannot be undone.')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('id', id);

                const response = await fetch('?ajax=delete_election', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                
                if (result.success) {
                    window.location.href = 'admin_elections.php?success=deleted';
                } else {
                    alert('Failed to delete election. It may have existing votes.');
                }
            } catch (error) {
                console.error('Error deleting election:', error);
                alert('Error deleting election');
            }
        }

        function toggleStatusMenu(id) {
            // Close all other menus
            Object.keys(statusMenus).forEach(key => {
                if (key != id) {
                    const menu = document.getElementById(`status-menu-${key}`);
                    if (menu) {
                        menu.style.display = 'none';
                        statusMenus[key] = false;
                    }
                }
            });

            // Toggle current menu
            const menu = document.getElementById(`status-menu-${id}`);
            if (menu) {
                const isVisible = statusMenus[id] || false;
                menu.style.display = isVisible ? 'none' : 'block';
                statusMenus[id] = !isVisible;
            }
        }

        // Form validation and submission
        document.getElementById('electionForm').addEventListener('submit', function(e) {
            const startDate = new Date(document.getElementById('start_date').value);
            const endDate = new Date(document.getElementById('end_date').value);

            if (endDate <= startDate) {
                e.preventDefault();
                alert('End date must be after start date');
                return;
            }

            // Show loading spinner
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const spinner = document.getElementById('submitSpinner');
            const submitText = document.getElementById('submitText');
            
            submitBtn.disabled = true;
            spinner.style.display = 'block';
            submitText.textContent = 'Processing...';
        });

        // Close status menus when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('[onclick*="toggleStatusMenu"]') && !e.target.closest('[id*="status-menu"]')) {
                Object.keys(statusMenus).forEach(key => {
                    const menu = document.getElementById(`status-menu-${key}`);
                    if (menu) {
                        menu.style.display = 'none';
                        statusMenus[key] = false;
                    }
                });
            }
        });

        // Close modals when clicking outside
        document.getElementById('electionModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        document.getElementById('viewModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeViewModal();
            }
        });

        // Auto-refresh stats every 30 seconds
        setInterval(async function() {
            try {
                const response = await fetch('?ajax=get_stats');
                const stats = await response.json();
                
                // Update stat cards (if needed for real-time updates)
                // This could be implemented to update the numbers without page refresh
            } catch (error) {
                console.error('Error refreshing stats:', error);
            }
        }, 30000);

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Escape key to close modals
            if (e.key === 'Escape') {
                closeModal();
                closeViewModal();
            }
            
            // Ctrl+N for new election
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                openCreateModal();
            }
        });

        // Initialize tooltips and setup
        document.addEventListener('DOMContentLoaded', function() {
            // Set minimum date to current date for new elections
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            
            const currentDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
            document.getElementById('start_date').min = currentDateTime;
            document.getElementById('end_date').min = currentDateTime;
            
            // Update end date minimum when start date changes
            document.getElementById('start_date').addEventListener('change', function() {
                document.getElementById('end_date').min = this.value;
            });

            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });

        // Search functionality enhancement
        let searchTimeout;
        document.getElementById('search').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                // Could implement real-time search without form submission
                // document.getElementById('search').closest('form').submit();
            }, 500);
        });

        console.log('Elections Management System with Modern Sidebar Initialized');
        console.log('Available functions:', {
            'openCreateModal': 'Create new election',
            'editElection': 'Edit existing election',
            'viewElection': 'View election details',
            'deleteElection': 'Delete election',
            'toggleStatusMenu': 'Toggle status menu'
        });
    </script>
</body>
</html>