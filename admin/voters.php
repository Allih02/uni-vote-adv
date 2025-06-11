<?php
// voters.php - Enhanced Voters Management
require_once 'includes/session_check.php';
require_once 'config/database.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $conn = getDBConnection();
        
        switch ($_POST['action']) {
            case 'add_voter':
                $student_id = trim($_POST['student_id']);
                $full_name = trim($_POST['full_name']);
                $email = trim($_POST['email']);
                $department = trim($_POST['department']);
                $year_of_study = $_POST['year_of_study'];
                $phone = trim($_POST['phone'] ?? '');
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                try {
                    $stmt = $conn->prepare("INSERT INTO voters (student_id, full_name, email, password, department, year_of_study, phone) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$student_id, $full_name, $email, $password, $department, $year_of_study, $phone]);
                    
                    // Log activity
                    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, description) VALUES (?, ?, ?)");
                    $stmt->execute([$_SESSION['admin_id'], 'voter_added', "Added voter: $full_name ($student_id)"]);
                    
                    header("Location: voters.php?success=added");
                } catch (PDOException $e) {
                    if ($e->getCode() == '23000') {
                        header("Location: voters.php?error=duplicate");
                    } else {
                        header("Location: voters.php?error=database");
                    }
                }
                exit();
                break;
                
            case 'update_status':
                $voter_id = $_POST['voter_id'];
                $status = $_POST['status'];
                
                try {
                    $stmt = $conn->prepare("UPDATE voters SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE voter_id = ?");
                    $stmt->execute([$status, $voter_id]);
                    
                    // Get voter name for logging
                    $stmt = $conn->prepare("SELECT full_name FROM voters WHERE voter_id = ?");
                    $stmt->execute([$voter_id]);
                    $voter = $stmt->fetch();
                    
                    // Log activity
                    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, description) VALUES (?, ?, ?)");
                    $stmt->execute([$_SESSION['admin_id'], 'voter_status_updated', "Changed status of {$voter['full_name']} to $status"]);
                    
                    header("Location: voters.php?success=updated");
                } catch (PDOException $e) {
                    header("Location: voters.php?error=update_failed");
                }
                exit();
                break;
                
            case 'reset_password':
                $voter_id = $_POST['voter_id'];
                $new_password = bin2hex(random_bytes(4)); // Generate 8-character password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                try {
                    $stmt = $conn->prepare("UPDATE voters SET password = ?, password_reset = 1, updated_at = CURRENT_TIMESTAMP WHERE voter_id = ?");
                    $stmt->execute([$hashed_password, $voter_id]);
                    
                    // Get voter info for logging
                    $stmt = $conn->prepare("SELECT full_name, email FROM voters WHERE voter_id = ?");
                    $stmt->execute([$voter_id]);
                    $voter = $stmt->fetch();
                    
                    // Log activity
                    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, description) VALUES (?, ?, ?)");
                    $stmt->execute([$_SESSION['admin_id'], 'password_reset', "Reset password for {$voter['full_name']}"]);
                    
                    // In production, send email to voter with new password
                    // sendPasswordResetEmail($voter['email'], $new_password);
                    
                    header("Location: voters.php?success=reset&password=" . $new_password);
                } catch (PDOException $e) {
                    header("Location: voters.php?error=reset_failed");
                }
                exit();
                break;
                
            case 'bulk_action':
                $action_type = $_POST['bulk_action'];
                $voter_ids = $_POST['voter_ids'] ?? [];
                
                if (!empty($voter_ids) && !empty($action_type)) {
                    try {
                        $placeholders = str_repeat('?,', count($voter_ids) - 1) . '?';
                        
                        switch ($action_type) {
                            case 'activate':
                                $stmt = $conn->prepare("UPDATE voters SET status = 'active', updated_at = CURRENT_TIMESTAMP WHERE voter_id IN ($placeholders)");
                                $stmt->execute($voter_ids);
                                break;
                            case 'suspend':
                                $stmt = $conn->prepare("UPDATE voters SET status = 'suspended', updated_at = CURRENT_TIMESTAMP WHERE voter_id IN ($placeholders)");
                                $stmt->execute($voter_ids);
                                break;
                            case 'delete':
                                $stmt = $conn->prepare("DELETE FROM voters WHERE voter_id IN ($placeholders)");
                                $stmt->execute($voter_ids);
                                break;
                        }
                        
                        // Log bulk action
                        $count = count($voter_ids);
                        $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, description) VALUES (?, ?, ?)");
                        $stmt->execute([$_SESSION['admin_id'], 'bulk_action', "Performed '$action_type' on $count voters"]);
                        
                        header("Location: voters.php?success=bulk_updated");
                    } catch (PDOException $e) {
                        header("Location: voters.php?error=bulk_failed");
                    }
                }
                exit();
                break;
        }
    }
}

// Get filters from URL
$status_filter = $_GET['status'] ?? '';
$department_filter = $_GET['department'] ?? '';
$year_filter = $_GET['year'] ?? '';
$search = $_GET['search'] ?? '';

// Build query with filters
$where_conditions = [];
$params = [];

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if (!empty($department_filter)) {
    $where_conditions[] = "department = ?";
    $params[] = $department_filter;
}

if (!empty($year_filter)) {
    $where_conditions[] = "year_of_study = ?";
    $params[] = $year_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(full_name LIKE ? OR student_id LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get voters with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 25;
$offset = ($page - 1) * $per_page;

try {
    $conn = getDBConnection();
    
    // Get total count
    $count_query = "SELECT COUNT(*) FROM voters $where_clause";
    $stmt = $conn->prepare($count_query);
    $stmt->execute($params);
    $total_voters = $stmt->fetchColumn();
    
    // Get voters for current page
    $query = "SELECT * FROM voters $where_clause ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $voters = $stmt->fetchAll();
    
    // Get departments for filter
    $dept_stmt = $conn->query("SELECT DISTINCT department FROM voters ORDER BY department");
    $departments = $dept_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get statistics
    $stats_query = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
            SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended,
            SUM(CASE WHEN has_voted = 1 THEN 1 ELSE 0 END) as voted
        FROM voters
    ";
    $stats = $conn->query($stats_query)->fetch();
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    $voters = [];
    $departments = [];
    $total_voters = 0;
    $stats = ['total' => 0, 'active' => 0, 'inactive' => 0, 'suspended' => 0, 'voted' => 0];
}

$total_pages = ceil($total_voters / $per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voters Management - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php echo getSessionScript($_SESSION['session_lifetime'], $_SESSION['login_time']); ?>
    <style>
        /* Enhanced CSS Variables */
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --secondary: #8b5cf6;
            --accent: #f43f5e;
            --background: #f8fafc;
            --surface: #ffffff;
            --surface-hover: #f1f5f9;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --border: #e2e8f0;
            --border-light: #f1f5f9;
            --success: #10b981;
            --success-light: #dcfce7;
            --warning: #f59e0b;
            --warning-light: #fef3c7;
            --error: #ef4444;
            --error-light: #fee2e2;
            --info: #3b82f6;
            --info-light: #dbeafe;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
        }

        /* Base Styles */
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
            font-size: 14px;
        }

        /* Dashboard Layout */
        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: var(--surface);
            border-right: 1px solid var(--border);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            min-height: 100vh;
        }

        /* Enhanced Top Bar */
        .top-bar {
            background: var(--surface);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 90;
        }

        .top-bar h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .top-bar-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
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
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
        }

        .stat-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-1px);
        }

        .stat-card .stat-icon {
            width: 3rem;
            height: 3rem;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }

        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1;
        }

        .stat-card .stat-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .stat-total .stat-icon { background: var(--info-light); color: var(--info); }
        .stat-active .stat-icon { background: var(--success-light); color: var(--success); }
        .stat-inactive .stat-icon { background: var(--warning-light); color: var(--warning); }
        .stat-suspended .stat-icon { background: var(--error-light); color: var(--error); }

        /* Enhanced Toolbar */
        .toolbar {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        .toolbar-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .search-and-filters {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-box {
            position: relative;
            min-width: 300px;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            background: var(--surface);
            transition: all 0.2s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        .filter-group {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .filter-select {
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            background: var(--surface);
            min-width: 150px;
        }

        /* Bulk Actions */
        .bulk-actions {
            display: none;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--info-light);
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
        }

        .bulk-actions.active {
            display: flex;
        }

        .bulk-counter {
            font-weight: 500;
            color: var(--info);
        }

        /* Enhanced Table */
        .table-container {
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: var(--surface-hover);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-light);
            vertical-align: middle;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tbody tr:hover {
            background: var(--surface-hover);
        }

        .data-table tbody tr.selected {
            background: rgba(99, 102, 241, 0.05);
        }

        /* Checkbox Styles */
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .custom-checkbox {
            position: relative;
            display: inline-block;
            width: 18px;
            height: 18px;
        }

        .custom-checkbox input[type="checkbox"] {
            opacity: 0;
            position: absolute;
            width: 100%;
            height: 100%;
            margin: 0;
            cursor: pointer;
        }

        .custom-checkbox .checkmark {
            position: absolute;
            top: 0;
            left: 0;
            width: 18px;
            height: 18px;
            border: 2px solid var(--border);
            border-radius: 3px;
            background: var(--surface);
            transition: all 0.2s ease;
        }

        .custom-checkbox input[type="checkbox"]:checked + .checkmark {
            background: var(--primary);
            border-color: var(--primary);
        }

        .custom-checkbox .checkmark:after {
            content: "";
            position: absolute;
            display: none;
            left: 5px;
            top: 2px;
            width: 6px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        .custom-checkbox input[type="checkbox"]:checked + .checkmark:after {
            display: block;
        }

        /* Enhanced Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: capitalize;
        }

        .status-active {
            background: var(--success-light);
            color: var(--success);
        }

        .status-inactive {
            background: var(--warning-light);
            color: var(--warning);
        }

        .status-suspended {
            background: var(--error-light);
            color: var(--error);
        }

        .status-badge i {
            font-size: 0.625rem;
        }

        /* Enhanced Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.25rem;
        }

        .btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: var(--radius-sm);
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            background: transparent;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .btn-icon:hover {
            background: var(--surface-hover);
            color: var(--text-primary);
            transform: scale(1.05);
        }

        .btn-icon.edit:hover { background: var(--info-light); color: var(--info); }
        .btn-icon.suspend:hover { background: var(--error-light); color: var(--error); }
        .btn-icon.activate:hover { background: var(--success-light); color: var(--success); }
        .btn-icon.reset:hover { background: var(--warning-light); color: var(--warning); }

        /* Enhanced Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            border-radius: var(--radius-md);
            font-weight: 500;
            font-size: 0.875rem;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            text-align: center;
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary {
            background: var(--surface);
            color: var(--text-primary);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--surface-hover);
            border-color: var(--primary);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        .btn-sm {
            padding: 0.5rem 0.75rem;
            font-size: 0.75rem;
        }

        /* Enhanced Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal.active {
            display: flex;
            opacity: 1;
        }

        .modal-content {
            background: var(--surface);
            padding: 2rem;
            border-radius: var(--radius-xl);
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            transform: scale(0.95);
            transition: transform 0.3s ease;
        }

        .modal.active .modal-content {
            transform: scale(1);
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
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0.25rem;
            border-radius: var(--radius-sm);
            transition: all 0.2s ease;
        }

        .modal-close:hover {
            background: var(--surface-hover);
            color: var(--text-primary);
        }

        /* Enhanced Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            background: var(--surface);
            transition: all 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-text {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }

        /* Enhanced Alert Styles */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            border: 1px solid;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
        }

        .alert-success {
            background: var(--success-light);
            color: var(--success);
            border-color: rgba(16, 185, 129, 0.2);
        }

        .alert-error {
            background: var(--error-light);
            color: var(--error);
            border-color: rgba(239, 68, 68, 0.2);
        }

        .alert-info {
            background: var(--info-light);
            color: var(--info);
            border-color: rgba(59, 130, 246, 0.2);
        }

        .alert i {
            font-size: 1rem;
        }

        /* Pagination */
        .pagination-wrapper {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-top: 1.5rem;
            padding: 1rem;
            background: var(--surface);
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
        }

        .pagination-info {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .pagination {
            display: flex;
            gap: 0.25rem;
        }

        .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: var(--radius-sm);
            background: var(--surface);
            color: var(--text-secondary);
            text-decoration: none;
            border: 1px solid var(--border);
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .page-link:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .page-link.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .page-link.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .page-link.disabled:hover {
            background: var(--surface);
            color: var(--text-secondary);
            border-color: var(--border);
        }

        /* Sidebar Navigation */
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }

        .logo i {
            font-size: 1.5rem;
        }

        .sidebar-nav {
            padding: 1rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: var(--radius-md);
            margin-bottom: 0.25rem;
            transition: all 0.2s ease;
            font-size: 0.875rem;
        }

        .nav-item:hover {
            background: var(--surface-hover);
            color: var(--text-primary);
        }

        .nav-item.active {
            background: var(--primary);
            color: white;
        }

        .nav-item i {
            width: 1.25rem;
            text-align: center;
        }

        /* Page Content */
        .page-content {
            padding: 2rem;
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
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .search-and-filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                min-width: auto;
            }
        }

        @media (max-width: 768px) {
            .top-bar {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
            }
            
            .top-bar-actions {
                width: 100%;
                justify-content: center;
            }
            
            .page-content {
                padding: 1rem;
            }
            
            .modal-content {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .data-table {
                font-size: 0.75rem;
            }
            
            .data-table th,
            .data-table td {
                padding: 0.75rem 0.5rem;
            }
        }

        /* Loading States */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid var(--border);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Export/Import Styles */
        .import-zone {
            border: 2px dashed var(--border);
            border-radius: var(--radius-lg);
            padding: 2rem;
            text-align: center;
            transition: all 0.2s ease;
        }

        .import-zone:hover,
        .import-zone.dragover {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.05);
        }

        .import-zone i {
            font-size: 2rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }

        /* Advanced Filters */
        .advanced-filters {
            display: none;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
            margin-top: 1rem;
        }

        .advanced-filters.show {
            display: block;
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        /* Voter Details Panel */
        .voter-details {
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            height: 100vh;
            background: var(--surface);
            border-left: 1px solid var(--border);
            box-shadow: var(--shadow-lg);
            z-index: 200;
            transition: right 0.3s ease;
            overflow-y: auto;
        }

        .voter-details.open {
            right: 0;
        }

        .voter-details-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .voter-details-content {
            padding: 1.5rem;
        }

        .detail-group {
            margin-bottom: 1.5rem;
        }

        .detail-label {
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }

        .detail-value {
            font-size: 0.875rem;
            color: var(--text-primary);
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="dashboard.php" class="logo">
                    <i class="fas fa-vote-yea"></i>
                    <span>VoteAdmin</span>
                </a>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="elections.php" class="nav-item">
                    <i class="fas fa-poll"></i>
                    <span>Elections</span>
                </a>
                <a href="candidates.php" class="nav-item">
                    <i class="fas fa-user-tie"></i>
                    <span>Candidates</span>
                </a>
                <a href="voters.php" class="nav-item active">
                    <i class="fas fa-users"></i>
                    <span>Voters</span>
                </a>
                <a href="results.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Results</span>
                </a>
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-file-alt"></i>
                    <span>Reports</span>
                </a>
                <a href="settings.php" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <div>
                    <h1>Voters Management</h1>
                    <div class="breadcrumb">
                        <span>Dashboard</span> / <span>Voters</span>
                    </div>
                </div>
                <div class="top-bar-actions">
                    <button class="btn btn-secondary" onclick="exportVoters()">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <button class="btn btn-secondary" onclick="showImportModal()">
                        <i class="fas fa-file-import"></i> Import
                    </button>
                    <button class="btn btn-primary" onclick="showAddModal()">
                        <i class="fas fa-plus"></i> Add Voter
                    </button>
                </div>
            </div>

            <div class="page-content">
                <!-- Success/Error Messages -->
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php 
                        switch($_GET['success']) {
                            case 'added':
                                echo 'Voter added successfully!';
                                break;
                            case 'updated':
                                echo 'Voter status updated successfully!';
                                break;
                            case 'reset':
                                echo 'Password reset successfully! New password: <strong>' . htmlspecialchars($_GET['password'] ?? '') . '</strong>';
                                break;
                            case 'bulk_updated':
                                echo 'Bulk action completed successfully!';
                                break;
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php 
                        switch($_GET['error']) {
                            case 'duplicate':
                                echo 'A voter with this Student ID or Email already exists!';
                                break;
                            case 'database':
                                echo 'Database error occurred. Please try again.';
                                break;
                            case 'update_failed':
                                echo 'Failed to update voter status.';
                                break;
                            case 'reset_failed':
                                echo 'Failed to reset password.';
                                break;
                            case 'bulk_failed':
                                echo 'Bulk action failed. Please try again.';
                                break;
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card stat-total">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
                        <div class="stat-label">Total Voters</div>
                    </div>
                    <div class="stat-card stat-active">
                        <div class="stat-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['active']); ?></div>
                        <div class="stat-label">Active Voters</div>
                    </div>
                    <div class="stat-card stat-inactive">
                        <div class="stat-icon">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['inactive']); ?></div>
                        <div class="stat-label">Inactive Voters</div>
                    </div>
                    <div class="stat-card stat-suspended">
                        <div class="stat-icon">
                            <i class="fas fa-user-slash"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['suspended']); ?></div>
                        <div class="stat-label">Suspended</div>
                    </div>
                </div>

                <!-- Bulk Actions Bar -->
                <div class="bulk-actions" id="bulkActions">
                    <div class="bulk-counter">
                        <span id="selectedCount">0</span> voters selected
                    </div>
                    <form method="POST" id="bulkForm">
                        <input type="hidden" name="action" value="bulk_action">
                        <input type="hidden" name="voter_ids" id="selectedVoters">
                        <select name="bulk_action" class="filter-select" required>
                            <option value="">Choose Action</option>
                            <option value="activate">Activate</option>
                            <option value="suspend">Suspend</option>
                            <option value="delete">Delete</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="clearSelection()">Cancel</button>
                    </form>
                </div>

                <!-- Enhanced Toolbar -->
                <div class="toolbar">
                    <div class="toolbar-top">
                        <div class="search-and-filters">
                            <div class="search-box">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" class="search-input" placeholder="Search by name, student ID, or email..." 
                                       value="<?php echo htmlspecialchars($search); ?>" id="searchInput">
                            </div>
                            <div class="filter-group">
                                <select class="filter-select" id="statusFilter">
                                    <option value="">All Statuses</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                </select>
                                <select class="filter-select" id="departmentFilter">
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept); ?>" 
                                                <?php echo $department_filter === $dept ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <select class="filter-select" id="yearFilter">
                                    <option value="">All Years</option>
                                    <option value="1" <?php echo $year_filter === '1' ? 'selected' : ''; ?>>1st Year</option>
                                    <option value="2" <?php echo $year_filter === '2' ? 'selected' : ''; ?>>2nd Year</option>
                                    <option value="3" <?php echo $year_filter === '3' ? 'selected' : ''; ?>>3rd Year</option>
                                    <option value="4" <?php echo $year_filter === '4' ? 'selected' : ''; ?>>4th Year</option>
                                    <option value="5" <?php echo $year_filter === '5' ? 'selected' : ''; ?>>5th Year</option>
                                </select>
                            </div>
                        </div>
                        <div class="toolbar-actions">
                            <button class="btn btn-outline btn-sm" onclick="toggleAdvancedFilters()">
                                <i class="fas fa-filter"></i> Advanced
                            </button>
                            <button class="btn btn-outline btn-sm" onclick="clearFilters()">
                                <i class="fas fa-times"></i> Clear
                            </button>
                        </div>
                    </div>

                    <!-- Advanced Filters -->
                    <div class="advanced-filters" id="advancedFilters">
                        <div class="filter-row">
                            <div class="form-group">
                                <label class="form-label">Registration Date From</label>
                                <input type="date" class="form-control" id="dateFrom">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Registration Date To</label>
                                <input type="date" class="form-control" id="dateTo">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Has Voted</label>
                                <select class="form-control" id="votedFilter">
                                    <option value="">All</option>
                                    <option value="yes">Has Voted</option>
                                    <option value="no">Not Voted</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Data Table -->
                <div class="table-container">
                    <table class="data-table" id="votersTable">
                        <thead>
                            <tr>
                                <th>
                                    <div class="checkbox-wrapper">
                                        <label class="custom-checkbox">
                                            <input type="checkbox" id="selectAll">
                                            <span class="checkmark"></span>
                                        </label>
                                    </div>
                                </th>
                                <th>Student ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Year</th>
                                <th>Status</th>
                                <th>Voted</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($voters)): ?>
                                <tr>
                                    <td colspan="10" style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                                        <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                                        <div>No voters found</div>
                                        <div style="font-size: 0.875rem; margin-top: 0.5rem;">
                                            Try adjusting your search or filters
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($voters as $voter): ?>
                                    <tr data-voter-id="<?php echo $voter['voter_id']; ?>">
                                        <td>
                                            <div class="checkbox-wrapper">
                                                <label class="custom-checkbox">
                                                    <input type="checkbox" class="voter-checkbox" value="<?php echo $voter['voter_id']; ?>">
                                                    <span class="checkmark"></span>
                                                </label>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($voter['student_id']); ?></strong>
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                <div class="voter-avatar">
                                                    <i class="fas fa-user-circle" style="font-size: 2rem; color: var(--primary);"></i>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($voter['full_name']); ?></div>
                                                    <?php if (!empty($voter['phone'])): ?>
                                                        <div style="font-size: 0.75rem; color: var(--text-secondary);">
                                                            <?php echo htmlspecialchars($voter['phone']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="mailto:<?php echo htmlspecialchars($voter['email']); ?>" 
                                               style="color: var(--primary); text-decoration: none;">
                                                <?php echo htmlspecialchars($voter['email']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($voter['department']); ?></td>
                                        <td>
                                            <span style="font-weight: 500;">Year <?php echo $voter['year_of_study']; ?></span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $voter['status']; ?>">
                                                <?php 
                                                $status_icons = [
                                                    'active' => 'fa-check',
                                                    'inactive' => 'fa-clock',
                                                    'suspended' => 'fa-ban'
                                                ];
                                                ?>
                                                <i class="fas <?php echo $status_icons[$voter['status']] ?? 'fa-question'; ?>"></i>
                                                <?php echo ucfirst($voter['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (isset($voter['has_voted']) && $voter['has_voted']): ?>
                                                <span style="color: var(--success); font-weight: 500;">
                                                    <i class="fas fa-check-circle"></i> Yes
                                                </span>
                                            <?php else: ?>
                                                <span style="color: var(--text-secondary);">
                                                    <i class="fas fa-times-circle"></i> No
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="font-size: 0.875rem;">
                                                <?php echo date('M d, Y', strtotime($voter['created_at'])); ?>
                                            </div>
                                            <div style="font-size: 0.75rem; color: var(--text-secondary);">
                                                <?php echo date('g:i A', strtotime($voter['created_at'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-icon edit" title="View Details" 
                                                        onclick="showVoterDetails(<?php echo $voter['voter_id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn-icon edit" title="Edit" 
                                                        onclick="editVoter(<?php echo $voter['voter_id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                
                                                <?php if ($voter['status'] === 'active'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="voter_id" value="<?php echo $voter['voter_id']; ?>">
                                                        <input type="hidden" name="status" value="suspended">
                                                        <button type="submit" class="btn-icon suspend" title="Suspend" 
                                                                onclick="return confirm('Are you sure you want to suspend this voter?')">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="voter_id" value="<?php echo $voter['voter_id']; ?>">
                                                        <input type="hidden" name="status" value="active">
                                                        <button type="submit" class="btn-icon activate" title="Activate">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="reset_password">
                                                    <input type="hidden" name="voter_id" value="<?php echo $voter['voter_id']; ?>">
                                                    <button type="submit" class="btn-icon reset" title="Reset Password"
                                                            onclick="return confirm('Are you sure you want to reset this voter\'s password?')">
                                                        <i class="fas fa-key"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Enhanced Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination-wrapper">
                        <div class="pagination-info">
                            Showing <?php echo (($page - 1) * $per_page) + 1; ?> to 
                            <?php echo min($page * $per_page, $total_voters); ?> of 
                            <?php echo number_format($total_voters); ?> voters
                        </div>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" 
                                   class="page-link" title="First">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                   class="page-link" title="Previous">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            <?php else: ?>
                                <span class="page-link disabled">
                                    <i class="fas fa-angle-double-left"></i>
                                </span>
                                <span class="page-link disabled">
                                    <i class="fas fa-angle-left"></i>
                                </span>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $page - 2);
                            $end = min($total_pages, $page + 2);
                            
                            for ($i = $start; $i <= $end; $i++):
                            ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                   class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                   class="page-link" title="Next">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" 
                                   class="page-link" title="Last">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            <?php else: ?>
                                <span class="page-link disabled">
                                    <i class="fas fa-angle-right"></i>
                                </span>
                                <span class="page-link disabled">
                                    <i class="fas fa-angle-double-right"></i>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Enhanced Add Voter Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add New Voter</h2>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" id="addVoterForm">
                <input type="hidden" name="action" value="add_voter">
                
                <div class="form-group">
                    <label class="form-label">Student ID *</label>
                    <input type="text" name="student_id" class="form-control" required 
                           pattern="[A-Za-z0-9]+" title="Student ID should contain only letters and numbers">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" required minlength="2">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email Address *</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" class="form-control" 
                           pattern="[0-9+\-\(\)\s]+" title="Please enter a valid phone number">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Department *</label>
                    <input type="text" name="department" class="form-control" required 
                           list="departmentsList">
                    <datalist id="departmentsList">
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Year of Study *</label>
                    <select name="year_of_study" class="form-control" required>
                        <option value="">Select Year</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                        <option value="5">5th Year</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password *</label>
                    <div style="position: relative;">
                        <input type="password" name="password" class="form-control" required minlength="6"
                               id="passwordInput">
                        <button type="button" class="btn-icon" 
                                style="position: absolute; right: 0.5rem; top: 50%; transform: translateY(-50%);"
                                onclick="togglePassword('passwordInput')">
                            <i class="fas fa-eye" id="passwordToggle"></i>
                        </button>
                    </div>
                    <div class="form-text">Minimum 6 characters required</div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Voter
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Enhanced Import Voters Modal -->
    <div id="importModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Import Voters</h2>
                <button class="modal-close" onclick="closeImportModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" enctype="multipart/form-data" action="import_voters.php" id="importForm">
                <div class="form-group">
                    <label class="form-label">Upload CSV File</label>
                    <div class="import-zone" id="importZone">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <div style="margin-bottom: 1rem;">
                            <strong>Drop your CSV file here</strong> or click to browse
                        </div>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" 
                               required id="csvFile" style="display: none;">
                        <button type="button" class="btn btn-outline" onclick="document.getElementById('csvFile').click()">
                            Choose File
                        </button>
                    </div>
                    <div class="form-text">
                        <strong>CSV Format:</strong> student_id, full_name, email, department, year_of_study, phone (optional)
                        <br>
                        <a href="sample_voters.csv" download class="link-primary">
                            <i class="fas fa-download"></i> Download Sample CSV
                        </a>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="custom-checkbox">
                        <input type="checkbox" name="skip_duplicates" value="1" checked>
                        <span class="checkmark"></span>
                        Skip duplicate entries
                    </label>
                </div>
                
                <div class="form-group">
                    <label class="custom-checkbox">
                        <input type="checkbox" name="send_passwords" value="1">
                        <span class="checkmark"></span>
                        Send auto-generated passwords via email
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Import Voters
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeImportModal()">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Voter Details Panel -->
    <div id="voterDetails" class="voter-details">
        <div class="voter-details-header">
            <h3>Voter Details</h3>
            <button class="btn-icon" onclick="closeVoterDetails()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="voter-details-content" id="voterDetailsContent">
            <!-- Content will be loaded dynamically -->
        </div>
    </div>

    <script>
        // Global variables
        let selectedVoters = new Set();

        // Modal functions
        function showAddModal() {
            document.getElementById('addModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('addModal').classList.remove('active');
            document.getElementById('addVoterForm').reset();
        }
        
        function showImportModal() {
            document.getElementById('importModal').classList.add('active');
        }
        
        function closeImportModal() {
            document.getElementById('importModal').classList.remove('active');
            document.getElementById('importForm').reset();
        }

        // Password toggle
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const toggle = document.getElementById('passwordToggle');
            
            if (input.type === 'password') {
                input.type = 'text';
                toggle.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                toggle.className = 'fas fa-eye';
            }
        }

        // Voter management functions
        function editVoter(voterId) {
            window.location.href = `edit_voter.php?id=${voterId}`;
        }

        function showVoterDetails(voterId) {
            const panel = document.getElementById('voterDetails');
            const content = document.getElementById('voterDetailsContent');
            
            // Show loading state
            content.innerHTML = `
                <div style="text-align: center; padding: 2rem;">
                    <div class="spinner"></div>
                    <div style="margin-top: 1rem;">Loading voter details...</div>
                </div>
            `;
            
            panel.classList.add('open');
            
            // Fetch voter details via AJAX
            fetch(`get_voter_details.php?id=${voterId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        content.innerHTML = generateVoterDetailsHTML(data.voter);
                    } else {
                        content.innerHTML = `
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-triangle"></i>
                                Failed to load voter details
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    content.innerHTML = `
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-triangle"></i>
                            Error loading voter details
                        </div>
                    `;
                });
        }

        function closeVoterDetails() {
            document.getElementById('voterDetails').classList.remove('open');
        }

        function generateVoterDetailsHTML(voter) {
            return `
                <div class="detail-group">
                    <div class="detail-label">Student ID</div>
                    <div class="detail-value">${voter.student_id}</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Full Name</div>
                    <div class="detail-value">${voter.full_name}</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Email</div>
                    <div class="detail-value">
                        <a href="mailto:${voter.email}" style="color: var(--primary);">${voter.email}</a>
                    </div>
                </div>
                ${voter.phone ? `
                <div class="detail-group">
                    <div class="detail-label">Phone</div>
                    <div class="detail-value">${voter.phone}</div>
                </div>
                ` : ''}
                <div class="detail-group">
                    <div class="detail-label">Department</div>
                    <div class="detail-value">${voter.department}</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Year of Study</div>
                    <div class="detail-value">Year ${voter.year_of_study}</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">
                        <span class="status-badge status-${voter.status}">
                            ${voter.status.charAt(0).toUpperCase() + voter.status.slice(1)}
                        </span>
                    </div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Registration Date</div>
                    <div class="detail-value">${new Date(voter.created_at).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    })}</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Last Updated</div>
                    <div class="detail-value">${voter.updated_at ? new Date(voter.updated_at).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    }) : 'Never'}</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Voting Status</div>
                    <div class="detail-value">
                        ${voter.has_voted ? 
                            '<span style="color: var(--success);"><i class="fas fa-check-circle"></i> Has Voted</span>' : 
                            '<span style="color: var(--text-secondary);"><i class="fas fa-times-circle"></i> Not Voted</span>'
                        }
                    </div>
                </div>
            `;
        }

        // Bulk selection functions
        function updateBulkActions() {
            const bulkActions = document.getElementById('bulkActions');
            const selectedCount = document.getElementById('selectedCount');
            const selectedVotersInput = document.getElementById('selectedVoters');
            
            selectedCount.textContent = selectedVoters.size;
            selectedVotersInput.value = JSON.stringify(Array.from(selectedVoters));
            
            if (selectedVoters.size > 0) {
                bulkActions.classList.add('active');
            } else {
                bulkActions.classList.remove('active');
            }
        }

        function clearSelection() {
            selectedVoters.clear();
            document.querySelectorAll('.voter-checkbox').forEach(cb => {
                cb.checked = false;
                cb.closest('tr').classList.remove('selected');
            });
            document.getElementById('selectAll').checked = false;
            updateBulkActions();
        }

        // Search and filter functions
        function applyFilters() {
            const search = document.getElementById('searchInput').value;
            const status = document.getElementById('statusFilter').value;
            const department = document.getElementById('departmentFilter').value;
            const year = document.getElementById('yearFilter').value;
            
            const params = new URLSearchParams(window.location.search);
            
            if (search) params.set('search', search);
            else params.delete('search');
            
            if (status) params.set('status', status);
            else params.delete('status');
            
            if (department) params.set('department', department);
            else params.delete('department');
            
            if (year) params.set('year', year);
            else params.delete('year');
            
            params.delete('page'); // Reset to first page
            
            window.location.search = params.toString();
        }

        function clearFilters() {
            window.location.href = 'voters.php';
        }

        function toggleAdvancedFilters() {
            const filters = document.getElementById('advancedFilters');
            filters.classList.toggle('show');
        }

        // Export function
        function exportVoters() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', '1');
            window.location.href = 'export_voters.php?' + params.toString();
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Search input with debounce
            let searchTimeout;
            document.getElementById('searchInput').addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(applyFilters, 500);
            });

            // Filter selects
            document.getElementById('statusFilter').addEventListener('change', applyFilters);
            document.getElementById('departmentFilter').addEventListener('change', applyFilters);
            document.getElementById('yearFilter').addEventListener('change', applyFilters);

            // Select all checkbox
            document.getElementById('selectAll').addEventListener('change', function() {
                const isChecked = this.checked;
                document.querySelectorAll('.voter-checkbox').forEach(cb => {
                    cb.checked = isChecked;
                    const voterId = parseInt(cb.value);
                    
                    if (isChecked) {
                        selectedVoters.add(voterId);
                        cb.closest('tr').classList.add('selected');
                    } else {
                        selectedVoters.delete(voterId);
                        cb.closest('tr').classList.remove('selected');
                    }
                });
                updateBulkActions();
            });

            // Individual checkboxes
            document.querySelectorAll('.voter-checkbox').forEach(cb => {
                cb.addEventListener('change', function() {
                    const voterId = parseInt(this.value);
                    
                    if (this.checked) {
                        selectedVoters.add(voterId);
                        this.closest('tr').classList.add('selected');
                    } else {
                        selectedVoters.delete(voterId);
                        this.closest('tr').classList.remove('selected');
                    }
                    
                    // Update select all checkbox
                    const allCheckboxes = document.querySelectorAll('.voter-checkbox');
                    const checkedCheckboxes = document.querySelectorAll('.voter-checkbox:checked');
                    document.getElementById('selectAll').checked = allCheckboxes.length === checkedCheckboxes.length;
                    
                    updateBulkActions();
                });
            });

            // Import zone drag and drop
            const importZone = document.getElementById('importZone');
            const csvFile = document.getElementById('csvFile');

            importZone.addEventListener('click', () => csvFile.click());

            importZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                importZone.classList.add('dragover');
            });

            importZone.addEventListener('dragleave', () => {
                importZone.classList.remove('dragover');
            });

            importZone.addEventListener('drop', (e) => {
                e.preventDefault();
                importZone.classList.remove('dragover');
                
                if (e.dataTransfer.files.length > 0) {
                    csvFile.files = e.dataTransfer.files;
                    updateFileDisplay();
                }
            });

            csvFile.addEventListener('change', updateFileDisplay);

            function updateFileDisplay() {
                if (csvFile.files.length > 0) {
                    const fileName = csvFile.files[0].name;
                    importZone.innerHTML = `
                        <i class="fas fa-file-csv" style="color: var(--success);"></i>
                        <div style="margin-bottom: 1rem;">
                            <strong>${fileName}</strong> selected
                        </div>
                        <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('csvFile').value=''; updateFileDisplay();">
                            Change File
                        </button>
                    `;
                }
            }

            // Form validation
            document.getElementById('addVoterForm').addEventListener('submit', function(e) {
                const requiredFields = this.querySelectorAll('[required]');
                let isValid = true;

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.style.borderColor = 'var(--error)';
                        isValid = false;
                    } else {
                        field.style.borderColor = 'var(--border)';
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                }
            });

            // Bulk form validation
            document.getElementById('bulkForm').addEventListener('submit', function(e) {
                const action = this.querySelector('[name="bulk_action"]').value;
                if (!action) {
                    e.preventDefault();
                    alert('Please select an action.');
                    return;
                }

                if (action === 'delete') {
                    if (!confirm(`Are you sure you want to delete ${selectedVoters.size} voters? This action cannot be undone.`)) {
                        e.preventDefault();
                    }
                } else {
                    if (!confirm(`Are you sure you want to ${action} ${selectedVoters.size} voters?`)) {
                        e.preventDefault();
                    }
                }
            });
        });

        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            const addModal = document.getElementById('addModal');
            const importModal = document.getElementById('importModal');
            
            if (event.target === addModal) {
                closeModal();
            }
            if (event.target === importModal) {
                closeImportModal();
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Escape key to close modals
            if (e.key === 'Escape') {
                closeModal();
                closeImportModal();
                closeVoterDetails();
            }
            
            // Ctrl/Cmd + A to select all (when not in input)
            if ((e.ctrlKey || e.metaKey) && e.key === 'a' && !e.target.matches('input, textarea')) {
                e.preventDefault();
                document.getElementById('selectAll').click();
            }
        });

        // Auto-refresh functionality (optional)
        let autoRefreshInterval;
        function toggleAutoRefresh() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
                autoRefreshInterval = null;
            } else {
                autoRefreshInterval = setInterval(() => {
                    if (!document.querySelector('.modal.active')) {
                        window.location.reload();
                    }
                }, 30000); // Refresh every 30 seconds
            }
        }

        // Show loading state for form submissions
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="spinner"></i> Processing...';
                }
            });
        });
    </script>
</body>
</html>