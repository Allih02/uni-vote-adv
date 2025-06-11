<?php
// admin_voters.php - Complete Voters Management System
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Voter management functions
function getVoterStats() {
    $stats = [
        'total_voters' => 0,
        'active_voters' => 0,
        'pending_voters' => 0,
        'inactive_voters' => 0,
        'total_votes_cast' => 0,
        'voters_voted_today' => 0
    ];
    
    // Get basic voter counts
    $result = fetchAll("SELECT status, COUNT(*) as count FROM voters GROUP BY status");
    foreach ($result as $row) {
        $stats[$row['status'] . '_voters'] = (int)$row['count'];
        $stats['total_voters'] += (int)$row['count'];
    }
    
    // Get total votes cast
    $votes = fetchAll("SELECT COUNT(*) as count FROM votes");
    if (!empty($votes)) {
        $stats['total_votes_cast'] = (int)$votes[0]['count'];
    }
    
    // Get voters who voted today
    $today_votes = fetchAll("SELECT COUNT(DISTINCT voter_id) as count FROM votes WHERE DATE(voted_at) = CURDATE()");
    if (!empty($today_votes)) {
        $stats['voters_voted_today'] = (int)$today_votes[0]['count'];
    }
    
    return $stats;
}

function getVoters($filters = [], $limit = null, $offset = 0) {
    $where_conditions = [];
    $params = [];
    
    // Build WHERE clause based on filters
    if (!empty($filters['status'])) {
        $where_conditions[] = "status = ?";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['faculty'])) {
        $where_conditions[] = "faculty = ?";
        $params[] = $filters['faculty'];
    }
    
    if (!empty($filters['year'])) {
        $where_conditions[] = "year = ?";
        $params[] = $filters['year'];
    }
    
    if (!empty($filters['search'])) {
        $where_conditions[] = "(full_name LIKE ? OR email LIKE ? OR student_id LIKE ?)";
        $search_term = "%{$filters['search']}%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    $limit_clause = $limit ? "LIMIT $limit OFFSET $offset" : "";
    
    $query = "SELECT v.*, 
                     COALESCE(vote_counts.votes_cast, 0) as votes_cast,
                     COALESCE(election_counts.eligible_elections, 0) as eligible_elections
              FROM voters v
              LEFT JOIN (
                  SELECT voter_id, COUNT(*) as votes_cast 
                  FROM votes 
                  GROUP BY voter_id
              ) vote_counts ON v.id = vote_counts.voter_id
              LEFT JOIN (
                  SELECT COUNT(*) as eligible_elections 
                  FROM elections 
                  WHERE status = 'active'
              ) election_counts ON 1=1
              $where_clause 
              ORDER BY v.created_at DESC 
              $limit_clause";
    
    return fetchAll($query, $params);
}

function createVoter($data) {
    // Check if student_id or email already exists
    $existing = fetchOne("SELECT id FROM voters WHERE student_id = ? OR email = ?", 
                        [$data['student_id'], $data['email']]);
    if ($existing) {
        return false;
    }
    
    $query = "INSERT INTO voters (student_id, full_name, email, phone, program, year, faculty, gender, date_of_birth, nationality, address, status, password, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    // Hash the student ID as the default password
    $default_password = password_hash($data['student_id'], PASSWORD_DEFAULT);
    
    return executeQuery($query, [
        $data['student_id'],
        $data['full_name'], 
        $data['email'],
        $data['phone'],
        $data['program'],
        $data['year'],
        $data['faculty'],
        $data['gender'],
        $data['date_of_birth'],
        $data['nationality'],
        $data['address'],
        $data['status'],
        $default_password
    ]);
}

function updateVoterStatus($voter_id, $status) {
    $query = "UPDATE voters SET status = ?, updated_at = NOW() WHERE id = ?";
    return executeQuery($query, [$status, $voter_id]);
}

function deleteVoter($voter_id) {
    // First check if voter has any votes
    $votes = fetchOne("SELECT COUNT(*) as count FROM votes WHERE voter_id = ?", [$voter_id]);
    if ($votes && $votes['count'] > 0) {
        return false; // Cannot delete voter with votes
    }
    
    $query = "DELETE FROM voters WHERE id = ?";
    return executeQuery($query, [$voter_id]);
}

function getVoterById($voter_id) {
    return fetchOne("SELECT * FROM voters WHERE id = ?", [$voter_id]);
}

function updateVoter($voter_id, $data) {
    $query = "UPDATE voters SET 
              full_name = ?, email = ?, phone = ?, program = ?, 
              year = ?, faculty = ?, gender = ?, date_of_birth = ?, 
              nationality = ?, address = ?, updated_at = NOW() 
              WHERE id = ?";
    
    return executeQuery($query, [
        $data['full_name'],
        $data['email'],
        $data['phone'],
        $data['program'],
        $data['year'],
        $data['faculty'],
        $data['gender'],
        $data['date_of_birth'],
        $data['nationality'],
        $data['address'],
        $voter_id
    ]);
}

function processBulkImport($csvFile) {
    $results = ['success' => 0, 'errors' => []];
    
    if (($handle = fopen($csvFile['tmp_name'], "r")) !== FALSE) {
        $header = fgetcsv($handle); // Get header row
        $row_number = 1;
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            $row_number++;
            
            if (count($data) < 6) {
                $results['errors'][] = "Row $row_number: Insufficient data";
                continue;
            }
            
            $voter_data = [
                'student_id' => $data[0],
                'full_name' => $data[1],
                'email' => $data[2],
                'phone' => isset($data[3]) ? $data[3] : null,
                'program' => $data[4],
                'year' => $data[5],
                'faculty' => $data[6],
                'gender' => isset($data[7]) ? $data[7] : null,
                'date_of_birth' => isset($data[8]) ? $data[8] : null,
                'nationality' => isset($data[9]) ? $data[9] : null,
                'address' => isset($data[10]) ? $data[10] : null,
                'status' => 'pending'
            ];
            
            if (createVoter($voter_data)) {
                $results['success']++;
            } else {
                $results['errors'][] = "Row $row_number: Failed to create voter (duplicate ID/email or invalid data)";
            }
        }
        fclose($handle);
    } else {
        $results['errors'][] = "Could not open CSV file";
    }
    
    return $results;
}

// Add default profile image function
function getProfileImage($voter) {
    if (!empty($voter['profile_image']) && filter_var($voter['profile_image'], FILTER_VALIDATE_URL)) {
        return htmlspecialchars($voter['profile_image']);
    }
    // Generate a default avatar based on name initials
    $initials = '';
    $names = explode(' ', $voter['full_name']);
    foreach ($names as $name) {
        $initials .= strtoupper(substr($name, 0, 1));
    }
    return "data:image/svg+xml;base64," . base64_encode(
        '<svg width="40" height="40" xmlns="http://www.w3.org/2000/svg">
            <rect width="40" height="40" fill="#4f46e5"/>
            <text x="20" y="26" font-family="Arial" font-size="14" fill="white" text-anchor="middle">' . substr($initials, 0, 2) . '</text>
        </svg>'
    );
}

// Authentication check (basic implementation)
// if (!isset($_SESSION["admin_id"])) {
//     header("Location: admin_login.php");
//     exit();
// }

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $data = [
                    'student_id' => $_POST['student_id'] ?? '',
                    'full_name' => $_POST['full_name'] ?? '',
                    'email' => $_POST['email'] ?? '',
                    'phone' => $_POST['phone'] ?? null,
                    'program' => $_POST['program'] ?? '',
                    'year' => $_POST['year'] ?? '',
                    'faculty' => $_POST['faculty'] ?? '',
                    'gender' => $_POST['gender'] ?? null,
                    'date_of_birth' => $_POST['date_of_birth'] ?? null,
                    'nationality' => $_POST['nationality'] ?? null,
                    'address' => $_POST['address'] ?? null,
                    'status' => 'active'
                ];
                
                if (createVoter($data)) {
                    header("Location: admin_voters.php?success=created");
                } else {
                    header("Location: admin_voters.php?error=create_failed");
                }
                exit();
                
            case 'update':
                $voter_id = $_POST['voter_id'];
                $data = [
                    'full_name' => $_POST['full_name'] ?? '',
                    'email' => $_POST['email'] ?? '',
                    'phone' => $_POST['phone'] ?? null,
                    'program' => $_POST['program'] ?? '',
                    'year' => $_POST['year'] ?? '',
                    'faculty' => $_POST['faculty'] ?? '',
                    'gender' => $_POST['gender'] ?? null,
                    'date_of_birth' => $_POST['date_of_birth'] ?? null,
                    'nationality' => $_POST['nationality'] ?? null,
                    'address' => $_POST['address'] ?? null
                ];
                
                if (updateVoter($voter_id, $data)) {
                    header("Location: admin_voters.php?success=updated");
                } else {
                    header("Location: admin_voters.php?error=update_failed");
                }
                exit();
                
            case 'activate':
                if (updateVoterStatus($_POST['voter_id'], 'active')) {
                    header("Location: admin_voters.php?success=activated");
                } else {
                    header("Location: admin_voters.php?error=update_failed");
                }
                exit();
                
            case 'deactivate':
                if (updateVoterStatus($_POST['voter_id'], 'inactive')) {
                    header("Location: admin_voters.php?success=deactivated");
                } else {
                    header("Location: admin_voters.php?error=update_failed");
                }
                exit();
                
            case 'delete':
                if (deleteVoter($_POST['voter_id'])) {
                    header("Location: admin_voters.php?success=deleted");
                } else {
                    header("Location: admin_voters.php?error=delete_failed");
                }
                exit();
                
            case 'bulk_activate':
                $voter_ids = explode(',', $_POST['voter_ids']);
                $success_count = 0;
                foreach ($voter_ids as $voter_id) {
                    if (updateVoterStatus($voter_id, 'active')) {
                        $success_count++;
                    }
                }
                header("Location: admin_voters.php?success=bulk_activated&count=" . $success_count);
                exit();
                
            case 'bulk_deactivate':
                $voter_ids = explode(',', $_POST['voter_ids']);
                $success_count = 0;
                foreach ($voter_ids as $voter_id) {
                    if (updateVoterStatus($voter_id, 'inactive')) {
                        $success_count++;
                    }
                }
                header("Location: admin_voters.php?success=bulk_deactivated&count=" . $success_count);
                exit();
                
            case 'bulk_delete':
                $voter_ids = explode(',', $_POST['voter_ids']);
                $success_count = 0;
                foreach ($voter_ids as $voter_id) {
                    if (deleteVoter($voter_id)) {
                        $success_count++;
                    }
                }
                header("Location: admin_voters.php?success=bulk_deleted&count=" . $success_count);
                exit();
                
            case 'bulk_import':
                if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
                    $results = processBulkImport($_FILES['csv_file']);
                    if ($results['success'] > 0) {
                        $message = "Successfully imported {$results['success']} voter(s).";
                        if (!empty($results['errors'])) {
                            $message .= " " . count($results['errors']) . " errors occurred.";
                        }
                        header("Location: admin_voters.php?success=imported&count=" . $results['success']);
                    } else {
                        header("Location: admin_voters.php?error=import_failed");
                    }
                } else {
                    header("Location: admin_voters.php?error=no_file");
                }
                exit();
        }
    }
}

// Handle AJAX requests for voter details
if (isset($_GET['action']) && $_GET['action'] === 'get_voter' && isset($_GET['id'])) {
    $voter = getVoterById($_GET['id']);
    header('Content-Type: application/json');
    echo json_encode($voter);
    exit();
}

// Handle export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $voters = getVoters();
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="voters_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, [
        'Student ID', 'Full Name', 'Email', 'Phone', 'Program', 'Year', 
        'Faculty', 'Gender', 'Date of Birth', 'Nationality', 'Address', 
        'Status', 'Registration Date', 'Last Login', 'Votes Cast'
    ]);
    
    // CSV data
    foreach ($voters as $voter) {
        fputcsv($output, [
            $voter['student_id'],
            $voter['full_name'],
            $voter['email'],
            $voter['phone'],
            $voter['program'],
            $voter['year'],
            $voter['faculty'],
            $voter['gender'],
            $voter['date_of_birth'],
            $voter['nationality'],
            $voter['address'],
            $voter['status'],
            $voter['registration_date'],
            $voter['last_login'],
            $voter['votes_cast']
        ]);
    }
    
    fclose($output);
    exit();
}

// Get filter parameters
$filters = [
    'status' => $_GET['status'] ?? '',
    'faculty' => $_GET['faculty'] ?? '',
    'year' => $_GET['year'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// Pagination
$items_per_page = 15;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Get voters data
$all_voters = getVoters($filters);
$total_voters = count($all_voters);
$total_pages = max(1, ceil($total_voters / $items_per_page));
$paginated_voters = getVoters($filters, $items_per_page, $offset);

// Get statistics
$stats = getVoterStats();

// Get unique faculties and years for filters
$faculties = fetchAll("SELECT DISTINCT faculty FROM voters WHERE faculty IS NOT NULL AND faculty != '' ORDER BY faculty");
$years = fetchAll("SELECT DISTINCT year FROM voters WHERE year IS NOT NULL AND year != '' ORDER BY year");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voters Management - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #3730a3;
            --primary-light: #a5b4fc;
            --secondary: #7c3aed;
            --accent: #ec4899;
            --background: #f8fafc;
            --surface: #ffffff;
            --surface-hover: #f1f5f9;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --border: #e2e8f0;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --info: #3b82f6;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --radius: 0.75rem;
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
            font-size: 14px;
        }

        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: var(--surface);
            border-right: 1px solid var(--border);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
            box-shadow: var(--shadow-sm);
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, var(--primary), var(--secondary));
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: white;
            font-size: 1.125rem;
            font-weight: 700;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-section {
            margin-bottom: 1.5rem;
        }

        .nav-section-title {
            padding: 0.5rem 1rem;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.2s ease;
            font-size: 0.875rem;
        }

        .nav-item:hover {
            background: var(--surface-hover);
            color: var(--text-primary);
        }

        .nav-item.active {
            background: linear-gradient(90deg, var(--primary-light), transparent);
            color: var(--primary-dark);
            font-weight: 600;
            border-right: 3px solid var(--primary);
        }

        .nav-item i {
            width: 1rem;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            min-height: 100vh;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.125rem;
            color: var(--text-primary);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: var(--radius);
        }

        .top-bar {
            background: var(--surface);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .top-bar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .top-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .page-content {
            padding: 1.5rem 2rem;
        }

        /* Statistics Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: var(--surface);
            padding: 1.25rem;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--primary);
        }

        .stat-card.success::before { background: var(--success); }
        .stat-card.warning::before { background: var(--warning); }
        .stat-card.error::before { background: var(--error); }
        .stat-card.info::before { background: var(--info); }

        .stat-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-1px);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }

        .stat-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: var(--radius);
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
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
            line-height: 1;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.8rem;
            font-weight: 500;
        }

        /* Controls Section */
        .controls-section {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        .controls-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .bulk-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .bulk-label {
            font-weight: 600;
            color: var(--text-secondary);
            margin-right: 0.5rem;
            font-size: 0.875rem;
        }

        .filters-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
        }

        .filter-label {
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.8rem;
        }

        .filter-select,
        .search-input {
            padding: 0.625rem 0.875rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--surface);
            color: var(--text-primary);
            font-size: 0.8rem;
            transition: all 0.2s ease;
        }

        .filter-select:focus,
        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.1);
        }

        .search-group {
            position: relative;
        }

        .search-input {
            padding-left: 2.5rem;
        }

        .search-icon {
            position: absolute;
            left: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        /* Alerts */
        .alert {
            padding: 0.875rem 1.25rem;
            border-radius: var(--radius);
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.625rem;
            font-weight: 500;
            font-size: 0.875rem;
            animation: slideIn 0.3s ease;
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success::before {
            content: "✓";
            background: var(--success);
            color: white;
            border-radius: 50%;
            width: 1.25rem;
            height: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
        }

        .alert-error::before {
            content: "✕";
            background: var(--error);
            color: white;
            border-radius: 50%;
            width: 1.25rem;
            height: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
        }

        /* Table Container */
        .table-container {
            background: var(--surface);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .table-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        }

        .table-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.375rem;
        }

        .table-info {
            color: var(--text-secondary);
            font-size: 0.8rem;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        /* Voters Table */
        .voters-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }

        .voters-table th,
        .voters-table td {
            padding: 1rem 0.875rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .voters-table th {
            background: var(--surface-hover);
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.8rem;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .voters-table tbody tr {
            transition: all 0.2s ease;
        }

        .voters-table tbody tr:hover {
            background: var(--surface-hover);
        }

        .voter-checkbox {
            width: 1rem;
            height: 1rem;
            cursor: pointer;
        }

        .voter-info {
            display: flex;
            align-items: center;
            gap: 0.875rem;
        }

        .voter-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid var(--border);
            flex-shrink: 0;
        }

        .voter-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .voter-details {
            min-width: 0;
            flex: 1;
        }

        .voter-details h4 {
            font-weight: 600;
            margin-bottom: 0.125rem;
            color: var(--text-primary);
            font-size: 0.875rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .voter-details p {
            font-size: 0.75rem;
            color: var(--text-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .student-details {
            display: flex;
            flex-direction: column;
            gap: 0.125rem;
        }

        .student-id {
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--text-primary);
        }

        .student-phone {
            font-size: 0.7rem;
            color: var(--text-secondary);
        }

        .academic-info {
            display: flex;
            flex-direction: column;
            gap: 0.125rem;
        }

        .program-name {
            font-weight: 500;
            font-size: 0.8rem;
            color: var(--text-primary);
            line-height: 1.2;
        }

        .faculty-name {
            font-size: 0.7rem;
            color: var(--text-secondary);
            line-height: 1.2;
        }

        .year-info {
            font-size: 0.7rem;
            color: var(--text-muted);
        }

        .voter-status {
            padding: 0.25rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            text-align: center;
            white-space: nowrap;
        }

        .status-active {
            background: #ecfdf5;
            color: #065f46;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .participation-stats {
            text-align: center;
        }

        .participation-value {
            font-weight: 700;
            color: var(--primary);
            font-size: 0.9rem;
            margin-bottom: 0.125rem;
        }

        .participation-label {
            font-size: 0.65rem;
            color: var(--text-muted);
        }

        .last-activity {
            display: flex;
            flex-direction: column;
            gap: 0.125rem;
        }

        .activity-date {
            font-size: 0.75rem;
            color: var(--text-primary);
        }

        .activity-time {
            font-size: 0.65rem;
            color: var(--text-muted);
        }

        .voter-actions {
            display: flex;
            gap: 0.375rem;
            align-items: center;
            flex-wrap: wrap;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.375rem;
            padding: 0.5rem 0.875rem;
            border-radius: var(--radius);
            font-weight: 500;
            font-size: 0.8rem;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            white-space: nowrap;
            line-height: 1;
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

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-danger {
            background: var(--error);
            color: white;
        }

        .btn-sm {
            padding: 0.375rem 0.625rem;
            font-size: 0.7rem;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-top: 1px solid var(--border);
        }

        .pagination-info {
            color: var(--text-secondary);
            font-size: 0.8rem;
        }

        .pagination-controls {
            display: flex;
            gap: 0.375rem;
            align-items: center;
        }

        .pagination-btn {
            padding: 0.375rem 0.75rem;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text-primary);
            text-decoration: none;
            border-radius: var(--radius);
            transition: all 0.2s ease;
            font-size: 0.8rem;
            min-width: 2rem;
            text-align: center;
        }

        .pagination-btn:hover {
            background: var(--surface-hover);
        }

        .pagination-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
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
            border-radius: var(--radius);
            width: 100%;
            max-width: 600px;
            margin: 1rem;
            box-shadow: var(--shadow-lg);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 1.5rem 1.5rem 1rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0.375rem;
            border-radius: var(--radius);
        }

        .modal-close:hover {
            background: var(--surface-hover);
            color: var(--text-primary);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .form-grid {
            display: grid;
            gap: 1.25rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.875rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.8rem;
        }

        .form-control {
            padding: 0.75rem 0.875rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-size: 0.875rem;
            transition: all 0.2s ease;
            background: var(--surface);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.1);
        }

        .form-actions {
            display: flex;
            gap: 0.875rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border);
        }

        .upload-area {
            border: 2px dashed var(--border);
            border-radius: var(--radius);
            padding: 2.5rem 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--surface-hover);
        }

        .upload-area:hover {
            border-color: var(--primary);
            background: rgba(79, 70, 229, 0.1);
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--border);
        }

        .empty-state h3 {
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-menu-btn {
                display: block;
            }
        }

        @media (max-width: 768px) {
            .page-content {
                padding: 1rem;
            }

            .top-bar {
                padding: 1rem;
            }

            .top-bar-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .top-actions {
                width: 100%;
                justify-content: flex-start;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .controls-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .filters-row {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .voter-actions {
                flex-direction: column;
                gap: 0.25rem;
            }

            .voters-table {
                min-width: 800px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .pagination {
                flex-direction: column;
                gap: 0.75rem;
                text-align: center;
            }
        }

        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Loading states */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .spinner {
            border: 2px solid var(--border);
            border-top: 2px solid var(--primary);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="admin_dashboard.php" class="logo">
                    <i class="fas fa-shield-alt"></i>
                    <span>VoteAdmin</span>
                </a>
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
                    <a href="admin_candidates.php" class="nav-item">
                        <i class="fas fa-user-tie"></i>
                        <span>Candidates</span>
                    </a>
                    <a href="admin_voters.php" class="nav-item active">
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
                    <a href="admin_users.php" class="nav-item">
                        <i class="fas fa-user-shield"></i>
                        <span>Admin Users</span>
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
                <div class="top-bar-content">
                    <div>
                        <button class="mobile-menu-btn" id="mobileMenuBtn">
                            <i class="fas fa-bars"></i>
                        </button>
                        <h1 class="page-title">Voters Management</h1>
                        <p class="page-subtitle">Manage voter registrations, statuses, and participation</p>
                    </div>
                    <div class="top-actions">
                        <button class="btn btn-secondary" onclick="showImportModal()">
                            <i class="fas fa-upload"></i>
                            Bulk Import
                        </button>
                        <a href="?export=csv" class="btn btn-secondary">
                            <i class="fas fa-download"></i>
                            Export Data
                        </a>
                        <button class="btn btn-primary" onclick="showCreateModal()">
                            <i class="fas fa-plus"></i>
                            Add Voter
                        </button>
                    </div>
                </div>
            </div>

            <!-- Page Content -->
            <div class="page-content">
                <!-- Success/Error Messages -->
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        switch($_GET['success']) {
                            case 'created':
                                echo 'Voter added successfully!';
                                break;
                            case 'updated':
                                echo 'Voter updated successfully!';
                                break;
                            case 'activated':
                                echo 'Voter activated successfully!';
                                break;
                            case 'deactivated':
                                echo 'Voter deactivated successfully!';
                                break;
                            case 'deleted':
                                echo 'Voter deleted successfully!';
                                break;
                            case 'imported':
                                $count = $_GET['count'] ?? 0;
                                echo "Successfully imported {$count} voter(s)!";
                                break;
                            case 'bulk_activated':
                                $count = $_GET['count'] ?? 0;
                                echo "Successfully activated {$count} voter(s)!";
                                break;
                            case 'bulk_deactivated':
                                $count = $_GET['count'] ?? 0;
                                echo "Successfully deactivated {$count} voter(s)!";
                                break;
                            case 'bulk_deleted':
                                $count = $_GET['count'] ?? 0;
                                echo "Successfully deleted {$count} voter(s)!";
                                break;
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-error">
                        <?php 
                        switch($_GET['error']) {
                            case 'create_failed':
                                echo 'Failed to create voter. Please check if Student ID or Email already exists.';
                                break;
                            case 'update_failed':
                                echo 'Failed to update voter.';
                                break;
                            case 'delete_failed':
                                echo 'Failed to delete voter. Voter may have existing votes.';
                                break;
                            case 'import_failed':
                                echo 'Failed to import voters. Please check your CSV file format.';
                                break;
                            case 'no_file':
                                echo 'No file selected for import.';
                                break;
                            default:
                                echo 'An error occurred. Please try again.';
                        }
                        ?>
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
                        <div class="stat-value"><?php echo number_format($stats['total_voters']); ?></div>
                        <div class="stat-label">Total Voters</div>
                    </div>

                    <div class="stat-card success">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-user-check"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['active_voters']); ?></div>
                        <div class="stat-label">Active Voters</div>
                    </div>

                    <div class="stat-card warning">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-user-clock"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['pending_voters']); ?></div>
                        <div class="stat-label">Pending Approval</div>
                    </div>

                    <div class="stat-card error">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-user-times"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['inactive_voters']); ?></div>
                        <div class="stat-label">Inactive</div>
                    </div>

                    <div class="stat-card info">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-vote-yea"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['total_votes_cast']); ?></div>
                        <div class="stat-label">Total Votes Cast</div>
                    </div>

                    <div class="stat-card info">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['voters_voted_today']); ?></div>
                        <div class="stat-label">Voted Today</div>
                    </div>
                </div>

                <!-- Controls Section -->
                <div class="controls-section">
                    <div class="controls-header">
                        <div class="bulk-actions">
                            <span class="bulk-label">Bulk Actions:</span>
                            <button class="btn btn-success btn-sm" onclick="bulkActivate()" disabled id="bulkActivateBtn">
                                <i class="fas fa-check"></i>
                                Activate
                            </button>
                            <button class="btn btn-warning btn-sm" onclick="bulkDeactivate()" disabled id="bulkDeactivateBtn">
                                <i class="fas fa-pause"></i>
                                Deactivate
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="bulkDelete()" disabled id="bulkDeleteBtn">
                                <i class="fas fa-trash"></i>
                                Delete
                            </button>
                        </div>
                    </div>
                    
                    <div class="filters-row">
                        <div class="filter-group">
                            <label class="filter-label">Status Filter</label>
                            <select class="filter-select" id="statusFilter" onchange="applyFilters()">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="pending" <?php echo $filters['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="inactive" <?php echo $filters['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label">Faculty Filter</label>
                            <select class="filter-select" id="facultyFilter" onchange="applyFilters()">
                                <option value="">All Faculties</option>
                                <?php foreach ($faculties as $faculty): ?>
                                <option value="<?php echo htmlspecialchars($faculty['faculty']); ?>" <?php echo $filters['faculty'] === $faculty['faculty'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($faculty['faculty']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label">Year Filter</label>
                            <select class="filter-select" id="yearFilter" onchange="applyFilters()">
                                <option value="">All Years</option>
                                <?php foreach ($years as $year): ?>
                                <option value="<?php echo htmlspecialchars($year['year']); ?>" <?php echo $filters['year'] === $year['year'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($year['year']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label">Search Voters</label>
                            <div class="search-group">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" class="search-input" placeholder="Search by name, email, student ID..." 
                                       id="searchInput" value="<?php echo htmlspecialchars($filters['search']); ?>" 
                                       onkeyup="debounceSearch()">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Voters Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h3 class="table-title">Registered Voters</h3>
                        <p class="table-info">
                            Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $items_per_page, $total_voters); ?> of <?php echo $total_voters; ?> voters
                        </p>
                    </div>
                    
                    <div class="table-wrapper">
                        <?php if (empty($paginated_voters)): ?>
                            <div class="empty-state">
                                <i class="fas fa-users"></i>
                                <h3>No voters found</h3>
                                <p>No voters match your current filters or search criteria.</p>
                            </div>
                        <?php else: ?>
                        <table class="voters-table" id="votersTable">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" class="voter-checkbox" id="selectAll" onchange="toggleSelectAll()">
                                    </th>
                                    <th>Voter Information</th>
                                    <th>Student Details</th>
                                    <th>Academic Information</th>
                                    <th>Status</th>
                                    <th>Participation</th>
                                    <th>Last Activity</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paginated_voters as $voter): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="voter-checkbox voter-select" value="<?php echo $voter['id']; ?>" onchange="updateBulkActions()">
                                    </td>
                                    <td>
                                        <div class="voter-info">
                                            <div class="voter-avatar">
                                                <img src="<?php echo getProfileImage($voter); ?>" alt="<?php echo htmlspecialchars($voter['full_name']); ?>">
                                            </div>
                                            <div class="voter-details">
                                                <h4><?php echo htmlspecialchars($voter['full_name']); ?></h4>
                                                <p><?php echo htmlspecialchars($voter['email']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="student-details">
                                            <div class="student-details">
                                            <div class="student-id"><?php echo htmlspecialchars($voter['student_id']); ?></div>
                                            <div class="student-phone"><?php echo htmlspecialchars($voter['phone'] ?? 'No phone'); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="academic-info">
                                            <div class="program-name"><?php echo htmlspecialchars($voter['program'] ?? 'N/A'); ?></div>
                                            <div class="faculty-name"><?php echo htmlspecialchars($voter['faculty'] ?? 'N/A'); ?></div>
                                            <div class="year-info"><?php echo htmlspecialchars($voter['year'] ?? 'N/A'); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="voter-status status-<?php echo $voter['status']; ?>">
                                            <?php echo ucfirst($voter['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="participation-stats">
                                            <div class="participation-value"><?php echo $voter['votes_cast']; ?>/<?php echo $voter['eligible_elections']; ?></div>
                                            <div class="participation-label">Elections</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="last-activity">
                                            <?php if (!empty($voter['last_login'])): ?>
                                                <div class="activity-date"><?php echo date('M d, Y', strtotime($voter['last_login'])); ?></div>
                                                <div class="activity-time"><?php echo date('H:i', strtotime($voter['last_login'])); ?></div>
                                            <?php else: ?>
                                                <div class="activity-date">Never logged in</div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="voter-actions">
                                            <button class="btn btn-secondary btn-sm" onclick="viewVoter(<?php echo $voter['id']; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <?php if ($voter['status'] === 'pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="activate">
                                                    <input type="hidden" name="voter_id" value="<?php echo $voter['id']; ?>">
                                                    <button type="submit" class="btn btn-success btn-sm" title="Activate">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            <?php elseif ($voter['status'] === 'active'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="deactivate">
                                                    <input type="hidden" name="voter_id" value="<?php echo $voter['id']; ?>">
                                                    <button type="submit" class="btn btn-warning btn-sm" title="Deactivate">
                                                        <i class="fas fa-pause"></i>
                                                    </button>
                                                </form>
                                            <?php elseif ($voter['status'] === 'inactive'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="activate">
                                                    <input type="hidden" name="voter_id" value="<?php echo $voter['id']; ?>">
                                                    <button type="submit" class="btn btn-success btn-sm" title="Reactivate">
                                                        <i class="fas fa-play"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <button class="btn btn-secondary btn-sm" onclick="editVoter(<?php echo $voter['id']; ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <button class="btn btn-danger btn-sm" onclick="deleteVoter(<?php echo $voter['id']; ?>)" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
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
                        <div class="pagination-info">
                            Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>
                        </div>
                        
                        <div class="pagination-controls">
                            <?php if ($current_page > 1): ?>
                                <a href="?page=<?php echo $current_page - 1; ?><?php echo !empty($filters) && array_filter($filters) ? '&' . http_build_query(array_filter($filters)) : ''; ?>" class="pagination-btn">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($filters) && array_filter($filters) ? '&' . http_build_query(array_filter($filters)) : ''; ?>" 
                                   class="pagination-btn <?php echo $i == $current_page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($current_page < $total_pages): ?>
                                <a href="?page=<?php echo $current_page + 1; ?><?php echo !empty($filters) && array_filter($filters) ? '&' . http_build_query(array_filter($filters)) : ''; ?>" class="pagination-btn">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Voter Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add New Voter</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            
            <div class="modal-body">
                <form method="POST" class="form-grid">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="full_name" class="form-control" placeholder="Enter full name..." required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Student ID *</label>
                            <input type="text" name="student_id" class="form-control" placeholder="ST2024XXX" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Email Address *</label>
                            <input type="email" name="email" class="form-control" placeholder="student@university.edu" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" placeholder="+255 123 456 789">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Program *</label>
                            <select name="program" class="form-control" required>
                                <option value="">Select Program</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Business Administration">Business Administration</option>
                                <option value="Engineering">Engineering</option>
                                <option value="Medicine">Medicine</option>
                                <option value="Communications">Communications</option>
                                <option value="Arts & Culture">Arts & Culture</option>
                                <option value="Mathematics">Mathematics</option>
                                <option value="Law">Law</option>
                                <option value="Information Technology">Information Technology</option>
                                <option value="Psychology">Psychology</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Year *</label>
                            <select name="year" class="form-control" required>
                                <option value="">Select Year</option>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                                <option value="5th Year">5th Year</option>
                                <option value="6th Year">6th Year</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Faculty *</label>
                            <select name="faculty" class="form-control" required>
                                <option value="">Select Faculty</option>
                                <option value="Science & Technology">Science & Technology</option>
                                <option value="Business & Economics">Business & Economics</option>
                                <option value="Engineering">Engineering</option>
                                <option value="Health Sciences">Health Sciences</option>
                                <option value="Arts & Humanities">Arts & Humanities</option>
                                <option value="Law & Governance">Law & Governance</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-control">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                                <option value="Prefer not to say">Prefer not to say</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Nationality</label>
                            <input type="text" name="nationality" class="form-control" placeholder="e.g., Tanzanian">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" class="form-control" placeholder="City, Country">
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Voter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Voter Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Voter</h2>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            
            <div class="modal-body">
                <form method="POST" class="form-grid" id="editForm">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="voter_id" id="editVoterId">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="full_name" id="editFullName" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Student ID</label>
                            <input type="text" id="editStudentId" class="form-control" readonly style="background: #f5f5f5;">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Email Address *</label>
                            <input type="email" name="email" id="editEmail" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" id="editPhone" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Program *</label>
                            <select name="program" id="editProgram" class="form-control" required>
                                <option value="">Select Program</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Business Administration">Business Administration</option>
                                <option value="Engineering">Engineering</option>
                                <option value="Medicine">Medicine</option>
                                <option value="Communications">Communications</option>
                                <option value="Arts & Culture">Arts & Culture</option>
                                <option value="Mathematics">Mathematics</option>
                                <option value="Law">Law</option>
                                <option value="Information Technology">Information Technology</option>
                                <option value="Psychology">Psychology</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Year *</label>
                            <select name="year" id="editYear" class="form-control" required>
                                <option value="">Select Year</option>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                                <option value="5th Year">5th Year</option>
                                <option value="6th Year">6th Year</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Faculty *</label>
                            <select name="faculty" id="editFaculty" class="form-control" required>
                                <option value="">Select Faculty</option>
                                <option value="Science & Technology">Science & Technology</option>
                                <option value="Business & Economics">Business & Economics</option>
                                <option value="Engineering">Engineering</option>
                                <option value="Health Sciences">Health Sciences</option>
                                <option value="Arts & Humanities">Arts & Humanities</option>
                                <option value="Law & Governance">Law & Governance</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Gender</label>
                            <select name="gender" id="editGender" class="form-control">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                                <option value="Prefer not to say">Prefer not to say</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" id="editDateOfBirth" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Nationality</label>
                            <input type="text" name="nationality" id="editNationality" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" id="editAddress" class="form-control">
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Voter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Voter Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Voter Details</h2>
                <button class="modal-close" onclick="closeViewModal()">&times;</button>
            </div>
            
            <div class="modal-body">
                <div id="voterDetails" class="loading">
                    <div style="display: flex; align-items: center; justify-content: center; padding: 2rem;">
                        <div class="spinner"></div>
                        <span style="margin-left: 0.5rem;">Loading voter details...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Import Modal -->
    <div id="importModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Bulk Import Voters</h2>
                <button class="modal-close" onclick="closeImportModal()">&times;</button>
            </div>
            
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="bulk_import">
                    
                    <div class="form-group">
                        <label class="form-label">Upload CSV File</label>
                        <div class="upload-area" onclick="document.getElementById('csvFile').click()">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 2.5rem; color: var(--primary); margin-bottom: 1rem;"></i>
                            <h4 style="margin-bottom: 0.5rem;">Click to select CSV file</h4>
                            <p style="color: var(--text-muted); margin-bottom: 1rem;">or drag and drop here</p>
                            <small style="color: var(--text-muted);">Maximum file size: 5MB</small>
                        </div>
                        <input type="file" id="csvFile" name="csv_file" accept=".csv" style="display: none;" required>
                    </div>
                    
                    <div style="background: var(--surface-hover); padding: 1.25rem; border-radius: var(--radius); margin: 1.25rem 0;">
                        <h4 style="margin-bottom: 1rem; color: var(--text-primary);"><i class="fas fa-info-circle"></i> CSV Format Requirements</h4>
                        <ul style="font-size: 0.8rem; color: var(--text-secondary); margin-left: 1.25rem; line-height: 1.6;">
                            <li><strong>Required Columns:</strong> student_id, full_name, email, program, year, faculty</li>
                            <li><strong>Optional Columns:</strong> phone, gender, address, date_of_birth, nationality</li>
                            <li><strong>Email addresses must be unique</strong></li>
                            <li><strong>Student IDs must be unique</strong></li>
                            <li><strong>Date format:</strong> YYYY-MM-DD</li>
                        </ul>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeImportModal()">Cancel</button>
                        <button type="button" class="btn btn-secondary" onclick="downloadTemplate()">
                            <i class="fas fa-download"></i>
                            Download Template
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i>
                            Import Voters
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Actions Confirmation Modal -->
    <div id="bulkModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h2 class="modal-title" id="bulkModalTitle">Confirm Action</h2>
                <button class="modal-close" onclick="closeBulkModal()">&times;</button>
            </div>
            
            <div class="modal-body">
                <p id="bulkModalMessage">Are you sure you want to perform this action on the selected voters?</p>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeBulkModal()">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" id="bulkAction">
                        <input type="hidden" name="voter_ids" id="bulkVoterIds">
                        <button type="submit" class="btn btn-primary" id="bulkConfirmBtn">Confirm</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h2 class="modal-title">Confirm Delete</h2>
                <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            
            <div class="modal-body">
                <p>Are you sure you want to delete this voter? This action cannot be undone.</p>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="voter_id" id="deleteVoterId">
                        <button type="submit" class="btn btn-danger">Delete Voter</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            initializeMobile();
            initializeFileUpload();
            updateBulkActions();
            initializeCheckboxes();
        });

        let searchTimeout;
        function debounceSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(applyFilters, 500);
        }

        function applyFilters() {
            const status = document.getElementById('statusFilter').value;
            const faculty = document.getElementById('facultyFilter').value;
            const year = document.getElementById('yearFilter').value;
            const search = document.getElementById('searchInput').value;
            
            const params = new URLSearchParams();
            if (status) params.set('status', status);
            if (faculty) params.set('faculty', faculty);
            if (year) params.set('year', year);
            if (search) params.set('search', search);
            
            window.location.href = 'admin_voters.php?' + params.toString();
        }

        function initializeMobile() {
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const sidebar = document.getElementById('sidebar');

            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('open');
                });

                // Close sidebar when clicking outside on mobile
                document.addEventListener('click', function(e) {
                    if (window.innerWidth <= 1024 && !sidebar.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                        sidebar.classList.remove('open');
                    }
                });
            }
        }

        function initializeFileUpload() {
            const uploadArea = document.querySelector('.upload-area');
            const fileInput = document.getElementById('csvFile');

            if (uploadArea && fileInput) {
                uploadArea.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    uploadArea.style.borderColor = 'var(--primary)';
                });

                uploadArea.addEventListener('dragleave', function() {
                    uploadArea.style.borderColor = 'var(--border)';
                });

                uploadArea.addEventListener('drop', function(e) {
                    e.preventDefault();
                    uploadArea.style.borderColor = 'var(--border)';
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        fileInput.files = files;
                        updateUploadArea(files[0].name);
                    }
                });

                fileInput.addEventListener('change', function() {
                    if (this.files.length > 0) {
                        updateUploadArea(this.files[0].name);
                    }
                });
            }
        }

        function initializeCheckboxes() {
            const voterCheckboxes = document.querySelectorAll('.voter-select');
            voterCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateBulkActions);
            });
        }

        function updateUploadArea(fileName) {
            const uploadArea = document.querySelector('.upload-area');
            uploadArea.innerHTML = `
                <i class="fas fa-file-csv" style="font-size: 2.5rem; color: var(--success); margin-bottom: 1rem;"></i>
                <h4 style="margin-bottom: 0.5rem; color: var(--success);">${fileName}</h4>
                <p style="color: var(--success);">File selected successfully</p>
            `;
        }

        // Selection Management
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.voter-select');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            updateBulkActions();
        }

        function updateBulkActions() {
            const checkboxes = document.querySelectorAll('.voter-select');
            const selectedCheckboxes = document.querySelectorAll('.voter-select:checked');
            const selectAll = document.getElementById('selectAll');
            
            // Update select all checkbox
            if (selectedCheckboxes.length === 0) {
                selectAll.indeterminate = false;
                selectAll.checked = false;
            } else if (selectedCheckboxes.length === checkboxes.length) {
                selectAll.indeterminate = false;
                selectAll.checked = true;
            } else {
                selectAll.indeterminate = true;
                selectAll.checked = false;
            }
            
            // Enable/disable bulk action buttons
            const bulkButtons = ['bulkActivateBtn', 'bulkDeactivateBtn', 'bulkDeleteBtn'];
            const hasSelection = selectedCheckboxes.length > 0;
            
            bulkButtons.forEach(btnId => {
                const btn = document.getElementById(btnId);
                if (btn) {
                    btn.disabled = !hasSelection;
                    btn.style.opacity = hasSelection ? '1' : '0.5';
                }
            });
        }

        // Bulk Actions
        function bulkActivate() {
            const selectedIds = getSelectedVoterIds();
            if (selectedIds.length === 0) return;
            
            showBulkModal(
                'Activate Voters',
                `Are you sure you want to activate ${selectedIds.length} selected voter(s)?`,
                'bulk_activate',
                selectedIds,
                'btn-success'
            );
        }

        function bulkDeactivate() {
            const selectedIds = getSelectedVoterIds();
            if (selectedIds.length === 0) return;
            
            showBulkModal(
                'Deactivate Voters',
                `Are you sure you want to deactivate ${selectedIds.length} selected voter(s)?`,
                'bulk_deactivate',
                selectedIds,
                'btn-warning'
            );
        }

        function bulkDelete() {
            const selectedIds = getSelectedVoterIds();
            if (selectedIds.length === 0) return;
            
            showBulkModal(
                'Delete Voters',
                `Are you sure you want to delete ${selectedIds.length} selected voter(s)? This action cannot be undone.`,
                'bulk_delete',
                selectedIds,
                'btn-danger'
            );
        }

        function getSelectedVoterIds() {
            const selectedCheckboxes = document.querySelectorAll('.voter-select:checked');
            return Array.from(selectedCheckboxes).map(cb => cb.value);
        }

        function showBulkModal(title, message, action, voterIds, buttonClass) {
            document.getElementById('bulkModalTitle').textContent = title;
            document.getElementById('bulkModalMessage').textContent = message;
            document.getElementById('bulkAction').value = action;
            document.getElementById('bulkVoterIds').value = voterIds.join(',');
            
            const confirmBtn = document.getElementById('bulkConfirmBtn');
            confirmBtn.className = `btn ${buttonClass}`;
            
            document.getElementById('bulkModal').classList.add('active');
        }

        function closeBulkModal() {
            document.getElementById('bulkModal').classList.remove('active');
        }

        // Modal Functions
        function showCreateModal() {
            document.getElementById('createModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('createModal').classList.remove('active');
        }

        function showImportModal() {
            document.getElementById('importModal').classList.add('active');
        }

        function closeImportModal() {
            document.getElementById('importModal').classList.remove('active');
        }
        
        // View Voter Function
        function viewVoter(id) {
            document.getElementById('viewModal').classList.add('active');
            
            // Fetch voter details via AJAX
            fetch(`admin_voters.php?action=get_voter&id=${id}`)
                .then(response => response.json())
                .then(voter => {
                    if (voter) {
                        displayVoterDetails(voter);
                    } else {
                        document.getElementById('voterDetails').innerHTML = 
                            '<p style="text-align: center; color: var(--error);">Voter not found</p>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching voter details:', error);
                    document.getElementById('voterDetails').innerHTML = 
                        '<p style="text-align: center; color: var(--error);">Error loading voter details</p>';
                });
        }

        function displayVoterDetails(voter) {
            const details = `
                <div style="display: grid; gap: 1.5rem;">
                    <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: var(--surface-hover); border-radius: var(--radius);">
                        <div style="width: 60px; height: 60px; border-radius: 50%; overflow: hidden; border: 3px solid var(--primary);">
                            <img src="${voter.profile_image || 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=60&h=60&fit=crop&crop=face'}" 
                                 alt="${voter.full_name}" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div>
                            <h3 style="margin: 0; color: var(--text-primary);">${voter.full_name}</h3>
                            <p style="margin: 0; color: var(--text-secondary); font-size: 0.9rem;">${voter.student_id}</p>
                            <span class="voter-status status-${voter.status}" style="font-size: 0.75rem; margin-top: 0.5rem; display: inline-block;">
                                ${voter.status.charAt(0).toUpperCase() + voter.status.slice(1)}
                            </span>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <h4 style="margin-bottom: 0.75rem; color: var(--text-primary); border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">Personal Information</h4>
                            <div style="display: grid; gap: 0.5rem; font-size: 0.875rem;">
                                <div><strong>Email:</strong> ${voter.email}</div>
                                <div><strong>Phone:</strong> ${voter.phone || 'Not provided'}</div>
                                <div><strong>Gender:</strong> ${voter.gender || 'Not specified'}</div>
                                <div><strong>Date of Birth:</strong> ${voter.date_of_birth || 'Not provided'}</div>
                                <div><strong>Nationality:</strong> ${voter.nationality || 'Not provided'}</div>
                                <div><strong>Address:</strong> ${voter.address || 'Not provided'}</div>
                            </div>
                        </div>
                        
                        <div>
                            <h4 style="margin-bottom: 0.75rem; color: var(--text-primary); border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">Academic Information</h4>
                            <div style="display: grid; gap: 0.5rem; font-size: 0.875rem;">
                                <div><strong>Program:</strong> ${voter.program}</div>
                                <div><strong>Year:</strong> ${voter.year}</div>
                                <div><strong>Faculty:</strong> ${voter.faculty}</div>
                                <div><strong>Registration Date:</strong> ${new Date(voter.registration_date).toLocaleDateString()}</div>
                                <div><strong>Last Login:</strong> ${voter.last_login ? new Date(voter.last_login).toLocaleString() : 'Never'}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="text-align: center; padding: 1rem; background: var(--surface-hover); border-radius: var(--radius);">
                        <button class="btn btn-primary" onclick="editVoter(${voter.id}); closeViewModal();">
                            <i class="fas fa-edit"></i> Edit Voter
                        </button>
                    </div>
                </div>
            `;
            
            document.getElementById('voterDetails').innerHTML = details;
            document.getElementById('voterDetails').classList.remove('loading');
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.remove('active');
            document.getElementById('voterDetails').innerHTML = `
                <div style="display: flex; align-items: center; justify-content: center; padding: 2rem;">
                    <div class="spinner"></div>
                    <span style="margin-left: 0.5rem;">Loading voter details...</span>
                </div>
            `;
            document.getElementById('voterDetails').classList.add('loading');
        }

        // Edit Voter Function
        function editVoter(id) {
            fetch(`admin_voters.php?action=get_voter&id=${id}`)
                .then(response => response.json())
                .then(voter => {
                    if (voter) {
                        populateEditForm(voter);
                        document.getElementById('editModal').classList.add('active');
                    } else {
                        showNotification('Voter not found', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error fetching voter details:', error);
                    showNotification('Error loading voter details', 'error');
                });
        }

        function populateEditForm(voter) {
            document.getElementById('editVoterId').value = voter.id;
            document.getElementById('editFullName').value = voter.full_name;
            document.getElementById('editStudentId').value = voter.student_id;
            document.getElementById('editEmail').value = voter.email;
            document.getElementById('editPhone').value = voter.phone || '';
            document.getElementById('editProgram').value = voter.program;
            document.getElementById('editYear').value = voter.year;
            document.getElementById('editFaculty').value = voter.faculty;
            document.getElementById('editGender').value = voter.gender || '';
            document.getElementById('editDateOfBirth').value = voter.date_of_birth || '';
            document.getElementById('editNationality').value = voter.nationality || '';
            document.getElementById('editAddress').value = voter.address || '';
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        function deleteVoter(id) {
            document.getElementById('deleteVoterId').value = id;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }

        function downloadTemplate() {
            showNotification('Downloading CSV template...', 'info');
            // Create and download CSV template
            const csvContent = 'student_id,full_name,email,phone,program,year,faculty,gender,address,date_of_birth,nationality\nST2024XXX,"John Doe",john.doe@university.edu,+255123456789,"Computer Science","1st Year","Science & Technology",Male,"Arusha, Tanzania",1990-01-01,Tanzanian';
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = 'voter_import_template.csv';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            
            const iconMap = {
                'info': 'fa-info-circle',
                'success': 'fa-check-circle',
                'warning': 'fa-exclamation-triangle',
                'error': 'fa-times-circle'
            };
            
            const colorMap = {
                'info': 'var(--info)',
                'success': 'var(--success)',
                'warning': 'var(--warning)',
                'error': 'var(--error)'
            };
            
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 0.625rem;">
                    <i class="fas ${iconMap[type]}" style="color: ${colorMap[type]};"></i>
                    <span>${message}</span>
                </div>
            `;
            
            notification.style.cssText = `
                position: fixed;
                top: 1.5rem;
                right: 1.5rem;
                background: white;
                padding: 0.875rem 1.25rem;
                border-radius: var(--radius);
                box-shadow: var(--shadow-lg);
                border-left: 4px solid ${colorMap[type]};
                z-index: 1001;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease;
                max-width: 400px;
                font-size: 0.875rem;
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
            }
        });

        // Auto-hide success alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.remove();
                        }
                    }, 300);
                }, 5000);
            });
        });

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const createForm = document.querySelector('#createModal form');
            const editForm = document.querySelector('#editModal form');

            if (createForm) {
                createForm.addEventListener('submit', function(e) {
                    if (!validateForm(this)) {
                        e.preventDefault();
                    }
                });
            }

            if (editForm) {
                editForm.addEventListener('submit', function(e) {
                    if (!validateForm(this)) {
                        e.preventDefault();
                    }
                });
            }
        });

        function validateForm(form) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = 'var(--error)';
                    isValid = false;
                } else {
                    field.style.borderColor = 'var(--border)';
                }
            });

            // Email validation
            const emailFields = form.querySelectorAll('input[type="email"]');
            emailFields.forEach(field => {
                if (field.value && !isValidEmail(field.value)) {
                    field.style.borderColor = 'var(--error)';
                    isValid = false;
                }
            });

            if (!isValid) {
                showNotification('Please fill in all required fields correctly', 'error');
            }

            return isValid;
        }

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // Reset form validation styles on input
        document.addEventListener('input', function(e) {
            if (e.target.matches('input, select')) {
                e.target.style.borderColor = 'var(--border)';
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+N or Cmd+N for new voter
            if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                e.preventDefault();
                showCreateModal();
            }
            
            // Ctrl+I or Cmd+I for import
            if ((e.ctrlKey || e.metaKey) && e.key === 'i') {
                e.preventDefault();
                showImportModal();
            }
            
            // Ctrl+E or Cmd+E for export
            if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
                e.preventDefault();
                window.location.href = '?export=csv';
            }
        });

        // Add loading states to buttons
        function addLoadingState(button) {
            button.disabled = true;
            const originalText = button.innerHTML;
            button.innerHTML = '<div class="spinner" style="width: 16px; height: 16px; margin-right: 0.5rem;"></div>Loading...';
            
            setTimeout(() => {
                button.disabled = false;
                button.innerHTML = originalText;
            }, 2000);
        }

        // Add click handlers for forms with loading states
        document.addEventListener('submit', function(e) {
            if (e.target.matches('form')) {
                const submitBtn = e.target.querySelector('button[type="submit"]');
                if (submitBtn) {
                    addLoadingState(submitBtn);
                }
            }
        });

        // Enhance table interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to table rows
            const tableRows = document.querySelectorAll('.voters-table tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = 'var(--surface-hover)';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = '';
                });
            });

            // Add double-click to view voter
            tableRows.forEach(row => {
                row.addEventListener('dblclick', function() {
                    const checkbox = this.querySelector('.voter-select');
                    if (checkbox) {
                        viewVoter(checkbox.value);
                    }
                });
            });
        });

        // Performance optimization: Debounced resize handler
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                // Handle responsive adjustments if needed
                if (window.innerWidth <= 1024) {
                    document.getElementById('sidebar').classList.remove('open');
                }
            }, 150);
        });

        // Add tooltips for better UX
        document.addEventListener('DOMContentLoaded', function() {
            const elementsWithTooltips = document.querySelectorAll('[title]');
            elementsWithTooltips.forEach(element => {
                element.addEventListener('mouseenter', function(e) {
                    if (this.title) {
                        const tooltip = document.createElement('div');
                        tooltip.className = 'tooltip';
                        tooltip.textContent = this.title;
                        tooltip.style.cssText = `
                            position: absolute;
                            background: rgba(0, 0, 0, 0.8);
                            color: white;
                            padding: 0.5rem;
                            border-radius: 4px;
                            font-size: 0.75rem;
                            z-index: 1000;
                            pointer-events: none;
                            white-space: nowrap;
                        `;
                        document.body.appendChild(tooltip);
                        
                        const rect = this.getBoundingClientRect();
                        tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
                        tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
                        
                        this.setAttribute('data-tooltip-id', tooltip.id = 'tooltip-' + Date.now());
                    }
                });
                
                element.addEventListener('mouseleave', function() {
                    const tooltipId = this.getAttribute('data-tooltip-id');
                    if (tooltipId) {
                        const tooltip = document.getElementById(tooltipId);
                        if (tooltip) {
                            tooltip.remove();
                        }
                        this.removeAttribute('data-tooltip-id');
                    }
                });
            });
        });
    </script>
</body>
</html>