<?php
// admin_voters.php - Fixed Voters Management System
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
$host = 'localhost';
$dbname = 'voting_system';
$username = 'root';
$password = '';

// Create database connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<!-- Database connected successfully -->\n";
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Simple database helper functions
function simpleQuery($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $e) {
        echo "SQL Error: " . $e->getMessage() . "<br>";
        echo "Query: " . $sql . "<br>";
        return false;
    }
}

function getAll($sql, $params = []) {
    $stmt = simpleQuery($sql, $params);
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

function getOne($sql, $params = []) {
    $stmt = simpleQuery($sql, $params);
    return $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
}

// Set default admin session for testing
if (!isset($_SESSION['admin_id'])) {
    $_SESSION['admin_id'] = 1;
    $_SESSION['admin_name'] = 'System Administrator';
}

// Get admin info
$admin_user = [
    'id' => $_SESSION['admin_id'],
    'fullname' => $_SESSION['admin_name'] ?? 'Administrator',
    'role' => 'Administrator'
];

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Build export query with same filters as main query
    $status_filter = $_GET['status'] ?? '';
    $faculty_filter = $_GET['faculty'] ?? '';
    $year_filter = $_GET['year'] ?? '';
    $search_filter = $_GET['search'] ?? '';
    
    $sql = "SELECT full_name, email, student_id, phone, program, faculty, year, status, registration_date FROM voters WHERE 1=1";
    $params = [];
    
    if (!empty($status_filter)) {
        $sql .= " AND status = ?";
        $params[] = $status_filter;
    }
    
    if (!empty($faculty_filter)) {
        $sql .= " AND faculty = ?";
        $params[] = $faculty_filter;
    }
    
    if (!empty($year_filter)) {
        $sql .= " AND year = ?";
        $params[] = $year_filter;
    }
    
    if (!empty($search_filter)) {
        $sql .= " AND (full_name LIKE ? OR email LIKE ? OR student_id LIKE ?)";
        $search_term = '%' . $search_filter . '%';
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $voters = getAll($sql, $params);
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="voters_export_' . date('Y-m-d_H-i-s') . '.csv"');
    
    // Create file pointer
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, ['Full Name', 'Email', 'Student ID', 'Phone', 'Program', 'Faculty', 'Year', 'Status', 'Registration Date']);
    
    // Add data rows
    foreach ($voters as $voter) {
        fputcsv($output, [
            $voter['full_name'],
            $voter['email'],
            $voter['student_id'],
            $voter['phone'] ?? '',
            $voter['program'] ?? '',
            $voter['faculty'] ?? '',
            $voter['year'] ?? '',
            $voter['status'],
            $voter['registration_date']
        ]);
    }
    
    fclose($output);
    exit();
}

// Handle CSV import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    $upload_dir = 'uploads/';
    
    // Create uploads directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file = $_FILES['import_file'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if ($file['error'] === UPLOAD_ERR_OK && $file_extension === 'csv') {
        $upload_path = $upload_dir . uniqid() . '.csv';
        
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            // Process CSV file
            $imported_count = 0;
            $error_count = 0;
            
            if (($handle = fopen($upload_path, 'r')) !== FALSE) {
                // Skip header row
                fgetcsv($handle);
                
                while (($data = fgetcsv($handle)) !== FALSE) {
                    // Assuming CSV format: full_name, email, student_id, phone, program, faculty, year
                    if (count($data) >= 7) {
                        $sql = "INSERT INTO voters (full_name, email, student_id, phone, program, faculty, year, status, registration_date, created_at) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', CURDATE(), NOW())
                                ON DUPLICATE KEY UPDATE 
                                full_name = VALUES(full_name),
                                phone = VALUES(phone),
                                program = VALUES(program),
                                faculty = VALUES(faculty),
                                year = VALUES(year)";
                        
                        if (simpleQuery($sql, [$data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6]])) {
                            $imported_count++;
                        } else {
                            $error_count++;
                        }
                    }
                }
                fclose($handle);
            }
            
            // Clean up uploaded file
            unlink($upload_path);
            
            header("Location: admin_voters.php?success=imported&count=$imported_count&errors=$error_count");
            exit();
        } else {
            header("Location: admin_voters.php?error=upload_failed");
            exit();
        }
    } else {
        header("Location: admin_voters.php?error=invalid_file");
        exit();
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'activate':
            $sql = "UPDATE voters SET status = 'active' WHERE id = ?";
            if (simpleQuery($sql, [$_POST['voter_id']])) {
                header("Location: admin_voters.php?success=activated");
                exit();
            }
            break;
            
        case 'deactivate':
            $sql = "UPDATE voters SET status = 'inactive' WHERE id = ?";
            if (simpleQuery($sql, [$_POST['voter_id']])) {
                header("Location: admin_voters.php?success=deactivated");
                exit();
            }
            break;
            
        case 'delete':
            $sql = "DELETE FROM voters WHERE id = ?";
            if (simpleQuery($sql, [$_POST['voter_id']])) {
                header("Location: admin_voters.php?success=deleted");
                exit();
            }
            break;
            
        case 'add_voter':
            $sql = "INSERT INTO voters (full_name, email, student_id, phone, program, faculty, year, status, registration_date, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'active', CURDATE(), NOW())";
            if (simpleQuery($sql, [
                $_POST['full_name'],
                $_POST['email'],
                $_POST['student_id'],
                $_POST['phone'],
                $_POST['program'],
                $_POST['faculty'],
                $_POST['year']
            ])) {
                header("Location: admin_voters.php?success=added");
                exit();
            }
            break;
    }
}

// Get filters
$status_filter = $_GET['status'] ?? '';
$faculty_filter = $_GET['faculty'] ?? '';
$year_filter = $_GET['year'] ?? '';
$search_filter = $_GET['search'] ?? '';

// Build the main query
$sql = "SELECT * FROM voters WHERE 1=1";
$params = [];

if (!empty($status_filter)) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
}

if (!empty($faculty_filter)) {
    $sql .= " AND faculty = ?";
    $params[] = $faculty_filter;
}

if (!empty($year_filter)) {
    $sql .= " AND year = ?";
    $params[] = $year_filter;
}

if (!empty($search_filter)) {
    $sql .= " AND (full_name LIKE ? OR email LIKE ? OR student_id LIKE ?)";
    $search_term = '%' . $search_filter . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$sql .= " ORDER BY created_at DESC";

// Get all voters
$all_voters = getAll($sql, $params);
$total_voters = count($all_voters);

// Pagination
$items_per_page = 15;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $items_per_page;
$total_pages = max(1, ceil($total_voters / $items_per_page));

// Get paginated voters
$paginated_voters = array_slice($all_voters, $offset, $items_per_page);

// Get statistics
$stats = [
    'total_voters' => count(getAll("SELECT id FROM voters")),
    'active_voters' => count(getAll("SELECT id FROM voters WHERE status = 'active'")),
    'pending_voters' => count(getAll("SELECT id FROM voters WHERE status = 'pending'")),
    'inactive_voters' => count(getAll("SELECT id FROM voters WHERE status = 'inactive'")),
    'total_votes_cast' => count(getAll("SELECT id FROM votes")),
    'voters_voted_today' => count(getAll("SELECT DISTINCT voter_id FROM votes WHERE DATE(voted_at) = CURDATE()"))
];

// Get unique faculties and years for filters
$faculties = getAll("SELECT DISTINCT faculty FROM voters WHERE faculty IS NOT NULL ORDER BY faculty");
$years = getAll("SELECT DISTINCT year FROM voters WHERE year IS NOT NULL ORDER BY year");

// Debug information
echo "<!-- Debug Info:\n";
echo "Total voters in database: " . $stats['total_voters'] . "\n";
echo "Filtered voters: " . $total_voters . "\n";
echo "Paginated voters: " . count($paginated_voters) . "\n";
echo "Current page: " . $current_page . "\n";
echo "-->\n";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voters Management - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4338ca;
            --secondary: #8b5cf6;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --info: #3b82f6;
            --background: #f8fafc;
            --surface: #ffffff;
            --surface-hover: #f1f5f9;
            --border: #e2e8f0;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #94a3b8;
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
        }

        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 0;
            z-index: 1000;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            overflow-y: auto;
        }

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
            margin-bottom: 1rem;
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
            margin: 0 1rem 0.75rem 1rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 0.875rem 1rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            margin-bottom: 0.25rem;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }

        .nav-item.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .nav-item i {
            width: 20px;
            margin-right: 0.75rem;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
            width: calc(100vw - 280px);
        }

        .top-bar {
            background: var(--surface);
            padding: 1.5rem 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            margin-bottom: 2rem;
        }

        .top-bar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 1.875rem;
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
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.8rem;
            font-weight: 500;
        }

        /* Controls */
        .controls-section {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            margin-bottom: 1.5rem;
        }

        .filters-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
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

        /* Table */
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

        .voters-table {
            width: 100%;
            border-collapse: collapse;
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
        }

        .voters-table tbody tr:hover {
            background: var(--surface-hover);
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
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .voter-details h4 {
            font-weight: 600;
            margin-bottom: 0.125rem;
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .voter-details p {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .voter-status {
            padding: 0.25rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
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
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
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
            padding: 0.375rem 0.625rem;
            font-size: 0.7rem;
        }

        .voter-actions {
            display: flex;
            gap: 0.375rem;
        }

        /* Alerts */
        .alert {
            padding: 0.875rem 1.25rem;
            border-radius: var(--radius);
            margin-bottom: 1.25rem;
            font-weight: 500;
            font-size: 0.875rem;
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
        }

        .pagination-btn {
            padding: 0.375rem 0.75rem;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text-primary);
            text-decoration: none;
            border-radius: var(--radius);
            font-size: 0.8rem;
            text-align: center;
        }

        .pagination-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

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

        .debug-info {
            background: #f0f8ff;
            border: 1px solid #b0d4f1;
            border-radius: var(--radius);
            padding: 1rem;
            margin: 1rem 0;
            font-size: 0.8rem;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: var(--surface);
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
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
            font-weight: 700;
            color: var(--text-primary);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-muted);
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--surface);
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }

        /* Hidden file input */
        .file-input {
            display: none;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
                width: 100vw;
                padding: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .filters-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="#" class="logo">
                    <i class="fas fa-shield-alt"></i>
                    <span>VoteAdmin</span>
                </a>
                <div class="admin-info">
                    <div class="admin-name"><?php echo htmlspecialchars($admin_user['fullname']); ?></div>
                    <div class="admin-role"><?php echo htmlspecialchars($admin_user['role']); ?></div>
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
                <div class="top-bar-content">
                    <div>
                        <h1 class="page-title">Voters Management</h1>
                        <p class="page-subtitle">Manage voter registrations, statuses, and participation</p>
                    </div>
                    <div class="top-actions">
                        <button class="btn btn-secondary" onclick="openImportModal()">
                            <i class="fas fa-upload"></i>
                            Import
                        </button>
                        <a href="?export=csv<?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['export' => ''])) : ''; ?>" class="btn btn-secondary">
                            <i class="fas fa-download"></i>
                            Export
                        </a>
                        <button class="btn btn-primary" onclick="openAddVoterModal()">
                            <i class="fas fa-plus"></i>
                            Add Voter
                        </button>
                    </div>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    switch($_GET['success']) {
                        case 'activated': echo 'Voter activated successfully!'; break;
                        case 'deactivated': echo 'Voter deactivated successfully!'; break;
                        case 'deleted': echo 'Voter deleted successfully!'; break;
                        case 'added': echo 'Voter added successfully!'; break;
                        case 'imported': 
                            $count = $_GET['count'] ?? 0;
                            $errors = $_GET['errors'] ?? 0;
                            echo "Import completed! $count voters imported successfully.";
                            if ($errors > 0) echo " $errors records had errors.";
                            break;
                        default: echo 'Operation completed successfully!';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <?php 
                    switch($_GET['error']) {
                        case 'upload_failed': echo 'File upload failed. Please try again.'; break;
                        case 'invalid_file': echo 'Invalid file format. Please upload a CSV file.'; break;
                        default: echo 'Operation failed. Please try again.';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <!-- Debug Information (if requested) -->
            <?php if (isset($_GET['debug'])): ?>
                <div class="debug-info">
                    <h4>Debug Information:</h4>
                    <p><strong>Total voters in database:</strong> <?php echo $stats['total_voters']; ?></p>
                    <p><strong>Filtered voters:</strong> <?php echo $total_voters; ?></p>
                    <p><strong>Paginated voters:</strong> <?php echo count($paginated_voters); ?></p>
                    <p><strong>Database connection:</strong> Connected</p>
                    <p><strong>Applied filters:</strong> <?php echo empty(array_filter([$status_filter, $faculty_filter, $year_filter, $search_filter])) ? 'None' : implode(', ', array_filter([$status_filter, $faculty_filter, $year_filter, $search_filter])); ?></p>
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
                    <div class="stat-value"><?php echo $stats['total_voters']; ?></div>
                    <div class="stat-label">Total Voters</div>
                </div>

                <div class="stat-card success">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $stats['active_voters']; ?></div>
                    <div class="stat-label">Active Voters</div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-user-clock"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $stats['pending_voters']; ?></div>
                    <div class="stat-label">Pending Approval</div>
                </div>

                <div class="stat-card error">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-user-times"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $stats['inactive_voters']; ?></div>
                    <div class="stat-label">Inactive</div>
                </div>
            </div>

            <!-- Controls Section -->
            <div class="controls-section">
                <form method="GET" class="filters-row">
                    <div class="filter-group">
                        <label class="filter-label">Status Filter</label>
                        <select class="filter-select" name="status" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Faculty Filter</label>
                        <select class="filter-select" name="faculty" onchange="this.form.submit()">
                            <option value="">All Faculties</option>
                            <?php foreach ($faculties as $faculty): ?>
                            <option value="<?php echo htmlspecialchars($faculty['faculty']); ?>" <?php echo $faculty_filter === $faculty['faculty'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($faculty['faculty']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Year Filter</label>
                        <select class="filter-select" name="year" onchange="this.form.submit()">
                            <option value="">All Years</option>
                            <?php foreach ($years as $year): ?>
                            <option value="<?php echo htmlspecialchars($year['year']); ?>" <?php echo $year_filter === $year['year'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($year['year']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Search Voters</label>
                        <div class="search-group">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" class="search-input" name="search" 
                                   placeholder="Search by name, email, student ID..." 
                                   value="<?php echo htmlspecialchars($search_filter); ?>">
                        </div>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                            Search
                        </button>
                    </div>
                </form>
            </div>

            <!-- Voters Table -->
            <div class="table-container">
                <div class="table-header">
                    <h3 class="table-title">Registered Voters</h3>
                    <p class="table-info">
                        Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $items_per_page, $total_voters); ?> of <?php echo $total_voters; ?> voters
                        <?php if ($total_voters != $stats['total_voters']): ?>
                            (filtered from <?php echo $stats['total_voters']; ?> total)
                        <?php endif; ?>
                    </p>
                </div>
                
                <?php if (empty($paginated_voters)): ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <h3>No voters found</h3>
                        <?php if ($total_voters == 0 && $stats['total_voters'] > 0): ?>
                            <p>No voters match your current filters.</p>
                            <p><a href="admin_voters.php">Clear all filters</a> to see all voters.</p>
                        <?php elseif ($stats['total_voters'] == 0): ?>
                            <p>No voters are registered in the system yet.</p>
                            <p><a href="?debug=1">Enable debug mode</a> to troubleshoot.</p>
                        <?php else: ?>
                            <p>Try adjusting your search criteria.</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <table class="voters-table">
                        <thead>
                            <tr>
                                <th>Voter Information</th>
                                <th>Student Details</th>
                                <th>Academic Information</th>
                                <th>Status</th>
                                <th>Registration</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paginated_voters as $voter): ?>
                            <tr>
                                <td>
                                    <div class="voter-info">
                                        <div class="voter-avatar">
                                            <?php echo strtoupper(substr($voter['full_name'], 0, 2)); ?>
                                        </div>
                                        <div class="voter-details">
                                            <h4><?php echo htmlspecialchars($voter['full_name']); ?></h4>
                                            <p><?php echo htmlspecialchars($voter['email']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div style="font-weight: 600; font-size: 0.8rem; margin-bottom: 0.25rem;">
                                            <?php echo htmlspecialchars($voter['student_id']); ?>
                                        </div>
                                        <div style="font-size: 0.7rem; color: var(--text-secondary);">
                                            <?php echo htmlspecialchars($voter['phone'] ?? 'No phone'); ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div style="font-weight: 500; font-size: 0.8rem; margin-bottom: 0.25rem;">
                                            <?php echo htmlspecialchars($voter['program'] ?? 'N/A'); ?>
                                        </div>
                                        <div style="font-size: 0.7rem; color: var(--text-secondary); margin-bottom: 0.25rem;">
                                            <?php echo htmlspecialchars($voter['faculty'] ?? 'N/A'); ?>
                                        </div>
                                        <div style="font-size: 0.7rem; color: var(--text-muted);">
                                            <?php echo htmlspecialchars($voter['year'] ?? 'N/A'); ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="voter-status status-<?php echo $voter['status']; ?>">
                                        <?php echo ucfirst($voter['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-size: 0.75rem;">
                                        <div><?php echo date('M d, Y', strtotime($voter['registration_date'])); ?></div>
                                        <div style="color: var(--text-muted);">
                                            <?php echo $voter['last_login'] ? date('H:i', strtotime($voter['last_login'])) : 'Never logged in'; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="voter-actions">
                                        <button class="btn btn-secondary btn-sm" title="View Details" onclick="viewVoter(<?php echo $voter['id']; ?>)">
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
                                        
                                        <button class="btn btn-secondary btn-sm" title="Edit" onclick="editVoter(<?php echo $voter['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this voter?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="voter_id" value="<?php echo $voter['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <div class="pagination-info">
                        Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>
                    </div>
                    
                    <div class="pagination-controls">
                        <?php if ($current_page > 1): ?>
                            <a href="?page=<?php echo $current_page - 1; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>" class="pagination-btn">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>" 
                               class="pagination-btn <?php echo $i == $current_page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($current_page < $total_pages): ?>
                            <a href="?page=<?php echo $current_page + 1; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>" class="pagination-btn">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Debug Panel (hidden by default) -->
            <?php if (isset($_GET['debug'])): ?>
            <div style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: var(--radius); border: 1px solid #dee2e6;">
                <h4>Detailed Debug Information</h4>
                
                <h5>Database Statistics:</h5>
                <ul>
                    <li>Total Voters: <?php echo $stats['total_voters']; ?></li>
                    <li>Active Voters: <?php echo $stats['active_voters']; ?></li>
                    <li>Pending Voters: <?php echo $stats['pending_voters']; ?></li>
                    <li>Inactive Voters: <?php echo $stats['inactive_voters']; ?></li>
                </ul>
                
                <h5>Query Information:</h5>
                <ul>
                    <li>Filtered Results: <?php echo $total_voters; ?></li>
                    <li>Displayed Results: <?php echo count($paginated_voters); ?></li>
                    <li>Current Page: <?php echo $current_page; ?></li>
                    <li>Total Pages: <?php echo $total_pages; ?></li>
                </ul>
                
                <h5>Active Filters:</h5>
                <ul>
                    <li>Status: <?php echo $status_filter ?: 'None'; ?></li>
                    <li>Faculty: <?php echo $faculty_filter ?: 'None'; ?></li>
                    <li>Year: <?php echo $year_filter ?: 'None'; ?></li>
                    <li>Search: <?php echo $search_filter ?: 'None'; ?></li>
                </ul>
                
                <?php if (!empty($paginated_voters)): ?>
                <h5>Sample Voter Data:</h5>
                <pre style="background: white; padding: 1rem; border-radius: 4px; overflow-x: auto; font-size: 0.8rem;">
<?php print_r(array_slice($paginated_voters, 0, 2)); ?>
                </pre>
                <?php endif; ?>
                
                <p style="margin-top: 1rem;">
                    <a href="admin_voters.php">Remove debug mode</a>
                </p>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Add Voter Modal -->
    <div id="addVoterModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New Voter</h3>
                <button class="modal-close" onclick="closeModal('addVoterModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_voter">
                
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Student ID *</label>
                    <input type="text" name="student_id" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Program</label>
                    <input type="text" name="program" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Faculty</label>
                    <select name="faculty" class="form-input">
                        <option value="">Select Faculty</option>
                        <?php foreach ($faculties as $faculty): ?>
                        <option value="<?php echo htmlspecialchars($faculty['faculty']); ?>">
                            <?php echo htmlspecialchars($faculty['faculty']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Year</label>
                    <select name="year" class="form-input">
                        <option value="">Select Year</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                        <option value="5">5th Year</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addVoterModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Voter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Import Modal -->
    <div id="importModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Import Voters</h3>
                <button class="modal-close" onclick="closeModal('importModal')">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">CSV File *</label>
                    <input type="file" name="import_file" class="form-input" accept=".csv" required>
                    <small style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.5rem; display: block;">
                        CSV format: Full Name, Email, Student ID, Phone, Program, Faculty, Year
                    </small>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('importModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('show');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }
        
        function openAddVoterModal() {
            openModal('addVoterModal');
        }
        
        function openImportModal() {
            openModal('importModal');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }
        
        // Voter action functions
        function viewVoter(voterId) {
            alert('View voter details for ID: ' + voterId + '\n\nThis would open a detailed view modal.');
        }
        
        function editVoter(voterId) {
            alert('Edit voter for ID: ' + voterId + '\n\nThis would open an edit modal with pre-filled data.');
        }

        // Enhanced JavaScript for better UX
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-submit search form on Enter
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        this.form.submit();
                    }
                });
            }
            
            // Add loading states to form submissions
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn && !submitBtn.classList.contains('btn-danger')) {
                        submitBtn.disabled = true;
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                        
                        // Reset after 3 seconds if still on page
                        setTimeout(() => {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }, 3000);
                    }
                });
            });
            
            // Highlight current filters
            const filterSelects = document.querySelectorAll('.filter-select');
            filterSelects.forEach(select => {
                if (select.value) {
                    select.style.borderColor = 'var(--primary)';
                    select.style.background = 'rgba(99, 102, 241, 0.05)';
                }
            });
            
            // Enhanced button hover effects
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-1px)';
                });
                
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
        
        // Console log for debugging
        console.log('Admin Voters Page Loaded');
        console.log('Total voters in database:', <?php echo $stats['total_voters']; ?>);
        console.log('Filtered voters:', <?php echo $total_voters; ?>);
        console.log('Displayed voters:', <?php echo count($paginated_voters); ?>);
    </script>
</body>
</html>