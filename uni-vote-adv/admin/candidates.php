<?php
// candidates.php - Enhanced Candidates Management
require_once 'includes/session_check.php';
require_once 'config/database.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $conn = getDBConnection();
        
        switch ($_POST['action']) {
            case 'add_candidate':
                $position_id = $_POST['position_id'];
                $full_name = $_POST['full_name'];
                $student_id = $_POST['student_id'];
                $manifesto = $_POST['manifesto'];
                $status = 'pending';
                
                // Handle file upload
                $photo = '';
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
                    $upload_dir = 'uploads/candidates/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($file_extension, $allowed_extensions)) {
                        $photo = uniqid() . '.' . $file_extension;
                        move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $photo);
                    }
                }
                
                $stmt = $conn->prepare("INSERT INTO candidates (position_id, full_name, student_id, photo, manifesto, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$position_id, $full_name, $student_id, $photo, $manifesto, $status]);
                
                header("Location: candidates.php?success=added");
                exit();
                break;
                
            case 'update_status':
                $candidate_id = $_POST['candidate_id'];
                $status = $_POST['status'];
                
                $stmt = $conn->prepare("UPDATE candidates SET status = ? WHERE candidate_id = ?");
                $stmt->execute([$status, $candidate_id]);
                
                header("Location: candidates.php?success=updated");
                exit();
                break;
        }
    }
}

// Get all candidates with their positions and elections
try {
    $conn = getDBConnection();
    $stmt = $conn->query("
        SELECT c.*, p.title as position_title, e.title as election_title, e.status as election_status
        FROM candidates c
        JOIN positions p ON c.position_id = p.position_id
        JOIN elections e ON p.election_id = e.election_id
        ORDER BY e.election_id DESC, p.sort_order ASC, c.full_name ASC
    ");
    $candidates = $stmt->fetchAll();
    
    // Get elections for dropdown
    $stmt = $conn->query("SELECT election_id, title FROM elections WHERE status != 'completed' ORDER BY created_at DESC");
    $elections = $stmt->fetchAll();
    
    // Get candidate statistics
    $stats = [
        'total' => count($candidates),
        'pending' => count(array_filter($candidates, fn($c) => $c['status'] === 'pending')),
        'approved' => count(array_filter($candidates, fn($c) => $c['status'] === 'approved')),
        'rejected' => count(array_filter($candidates, fn($c) => $c['status'] === 'rejected'))
    ];
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    $candidates = [];
    $elections = [];
    $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidates Management - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php echo getSessionScript($_SESSION['session_lifetime'], $_SESSION['login_time']); ?>
    <style>
        /* Enhanced CSS Variables */
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #a5b4fc;
            --secondary: #8b5cf6;
            --accent: #f43f5e;
            --background: #f8fafc;
            --surface: #ffffff;
            --surface-2: #f1f5f9;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-light: #94a3b8;
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
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --radius: 0.5rem;
            --radius-lg: 1rem;
            --radius-xl: 1.5rem;
        }

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

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: var(--primary);
            font-weight: 700;
            font-size: 1.25rem;
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
            border-radius: var(--radius);
            margin-bottom: 0.25rem;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .nav-item:hover {
            background: var(--surface-2);
            color: var(--text-primary);
        }

        .nav-item.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: var(--shadow);
        }

        .nav-item i {
            width: 18px;
            text-align: center;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            min-height: 100vh;
        }

        /* Top Bar */
        .top-bar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 1.25rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        .top-bar-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        /* Page Content */
        .page-content {
            padding: 2rem;
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .stat-icon {
            width: 3rem;
            height: 3rem;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
        }

        .stat-icon.total { background: linear-gradient(135deg, var(--info), #2563eb); }
        .stat-icon.pending { background: linear-gradient(135deg, var(--warning), #d97706); }
        .stat-icon.approved { background: linear-gradient(135deg, var(--success), #059669); }
        .stat-icon.rejected { background: linear-gradient(135deg, var(--error), #dc2626); }

        .stat-content {
            flex: 1;
            margin-left: 1rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
            margin-top: 0.25rem;
        }

        /* Content Header */
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .content-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        /* Filter Controls */
        .filter-controls {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        .filter-row {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-secondary);
        }

        /* Candidates Grid */
        .candidates-container {
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem;
        }

        /* Enhanced Candidate Cards */
        .candidate-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }

        .candidate-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-light);
        }

        .candidate-photo-container {
            position: relative;
            height: 200px;
            overflow: hidden;
        }

        .candidate-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .candidate-card:hover .candidate-photo {
            transform: scale(1.05);
        }

        .photo-placeholder {
            height: 100%;
            background: linear-gradient(135deg, var(--surface-2), var(--border));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
        }

        .status-badge {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            padding: 0.375rem 0.875rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            backdrop-filter: blur(10px);
        }

        .status-pending {
            background: rgba(251, 191, 36, 0.9);
            color: #92400e;
        }

        .status-approved {
            background: rgba(34, 197, 94, 0.9);
            color: #14532d;
        }

        .status-rejected {
            background: rgba(239, 68, 68, 0.9);
            color: #7f1d1d;
        }

        .candidate-info {
            padding: 1.5rem;
        }

        .candidate-name {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .candidate-id {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .candidate-details {
            margin-bottom: 1rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .detail-icon {
            width: 16px;
            color: var(--text-light);
        }

        .detail-text {
            color: var(--text-secondary);
        }

        .detail-value {
            font-weight: 500;
            color: var(--text-primary);
        }

        .candidate-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: var(--radius);
            font-weight: 500;
            font-size: 0.875rem;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .btn:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover:before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--error), #dc2626);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary {
            background: var(--surface);
            color: var(--text-primary);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--surface-2);
            border-color: var(--primary-light);
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8125rem;
        }

        /* Form Controls */
        .form-control {
            width: 100%;
            padding: 0.625rem 0.875rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-size: 0.875rem;
            transition: all 0.2s ease;
            background: var(--surface);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-control:hover {
            border-color: var(--primary-light);
        }

        /* Search Box */
        .search-box {
            position: relative;
            max-width: 300px;
        }

        .search-input {
            padding-left: 2.5rem;
        }

        .search-icon {
            position: absolute;
            left: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
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
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }

        .modal.active .modal-content {
            transform: scale(1);
        }

        .modal-header {
            margin-bottom: 1.5rem;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }

        /* Alert Styles */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: var(--radius-lg);
            margin-bottom: 1.5rem;
            border: 1px solid;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: var(--success-light);
            color: #065f46;
            border-color: #a7f3d0;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
            color: var(--text-secondary);
        }

        .empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--text-light);
        }

        .empty-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .empty-text {
            margin-bottom: 1.5rem;
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

            .candidates-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 1rem;
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1rem;
            }
        }

        @media (max-width: 768px) {
            .page-content {
                padding: 1rem;
            }

            .top-bar {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .filter-row {
                flex-direction: column;
                align-items: stretch;
            }

            .candidates-grid {
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
                <a href="candidates.php" class="nav-item active">
                    <i class="fas fa-user-tie"></i>
                    <span>Candidates</span>
                </a>
                <a href="voters.php" class="nav-item">
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
            <div class="top-bar">
                <h1 class="page-title">Candidates Management</h1>
                <div class="top-bar-actions">
                    <div class="search-box">
                        <input type="text" id="searchInput" class="form-control search-input" placeholder="Search candidates...">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                    <button class="btn btn-primary" onclick="showAddModal()">
                        <i class="fas fa-plus"></i> Add Candidate
                    </button>
                </div>
            </div>

            <div class="page-content">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php 
                        switch($_GET['success']) {
                            case 'added':
                                echo 'Candidate added successfully!';
                                break;
                            case 'updated':
                                echo 'Candidate status updated successfully!';
                                break;
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon total">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo $stats['total']; ?></div>
                                <div class="stat-label">Total Candidates</div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon pending">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo $stats['pending']; ?></div>
                                <div class="stat-label">Pending Review</div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon approved">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo $stats['approved']; ?></div>
                                <div class="stat-label">Approved</div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon rejected">
                                <i class="fas fa-times"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo $stats['rejected']; ?></div>
                                <div class="stat-label">Rejected</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Controls -->
                <div class="filter-controls">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label class="filter-label">Filter by Status</label>
                            <select id="statusFilter" class="form-control">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Filter by Election</label>
                            <select id="electionFilter" class="form-control">
                                <option value="">All Elections</option>
                                <?php foreach ($elections as $election): ?>
                                    <option value="<?php echo htmlspecialchars($election['title']); ?>">
                                        <?php echo htmlspecialchars($election['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">&nbsp;</label>
                            <button class="btn btn-secondary" onclick="clearFilters()">
                                <i class="fas fa-times"></i> Clear Filters
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Candidates Container -->
                <div class="candidates-container">
                    <?php if (empty($candidates)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <h3 class="empty-title">No Candidates Found</h3>
                            <p class="empty-text">There are no candidates registered yet. Add the first candidate to get started.</p>
                            <button class="btn btn-primary" onclick="showAddModal()">
                                <i class="fas fa-plus"></i> Add First Candidate
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="candidates-grid" id="candidatesGrid">
                            <?php foreach ($candidates as $candidate): ?>
                                <div class="candidate-card" 
                                     data-status="<?php echo $candidate['status']; ?>"
                                     data-election="<?php echo htmlspecialchars($candidate['election_title']); ?>"
                                     data-name="<?php echo htmlspecialchars(strtolower($candidate['full_name'])); ?>"
                                     data-id="<?php echo htmlspecialchars(strtolower($candidate['student_id'])); ?>">
                                    
                                    <div class="candidate-photo-container">
                                        <?php if ($candidate['photo']): ?>
                                            <img src="uploads/candidates/<?php echo htmlspecialchars($candidate['photo']); ?>" 
                                                 alt="<?php echo htmlspecialchars($candidate['full_name']); ?>" 
                                                 class="candidate-photo">
                                        <?php else: ?>
                                            <div class="photo-placeholder">
                                                <i class="fas fa-user" style="font-size: 4rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="status-badge status-<?php echo $candidate['status']; ?>">
                                            <?php echo ucfirst($candidate['status']); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="candidate-info">
                                        <h3 class="candidate-name"><?php echo htmlspecialchars($candidate['full_name']); ?></h3>
                                        <div class="candidate-id">ID: <?php echo htmlspecialchars($candidate['student_id']); ?></div>
                                        
                                        <div class="candidate-details">
                                            <div class="detail-item">
                                                <i class="fas fa-briefcase detail-icon"></i>
                                                <span class="detail-text">Position:</span>
                                                <span class="detail-value"><?php echo htmlspecialchars($candidate['position_title']); ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <i class="fas fa-poll detail-icon"></i>
                                                <span class="detail-text">Election:</span>
                                                <span class="detail-value"><?php echo htmlspecialchars($candidate['election_title']); ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <i class="fas fa-info-circle detail-icon"></i>
                                                <span class="detail-text">Status:</span>
                                                <span class="detail-value"><?php echo ucfirst($candidate['status']); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="candidate-actions">
                                            <?php if ($candidate['status'] === 'pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>">
                                                    <input type="hidden" name="status" value="approved">
                                                    <button type="submit" class="btn btn-success btn-sm">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>">
                                                    <input type="hidden" name="status" value="rejected">
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <button class="btn btn-secondary btn-sm" 
                                                    onclick="viewManifesto('<?php echo htmlspecialchars($candidate['full_name']); ?>', '<?php echo htmlspecialchars(addslashes($candidate['manifesto'])); ?>')">
                                                <i class="fas fa-file-alt"></i> Manifesto
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Candidate Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add New Candidate</h2>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_candidate">
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-poll"></i> Election
                    </label>
                    <select id="election_select" class="form-control" onchange="loadPositions()" required>
                        <option value="">Select Election</option>
                        <?php foreach ($elections as $election): ?>
                            <option value="<?php echo $election['election_id']; ?>">
                                <?php echo htmlspecialchars($election['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-briefcase"></i> Position
                    </label>
                    <select name="position_id" id="position_select" class="form-control" required>
                        <option value="">Select Position</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-user"></i> Full Name
                    </label>
                    <input type="text" name="full_name" class="form-control" placeholder="Enter candidate's full name" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-id-card"></i> Student ID
                    </label>
                    <input type="text" name="student_id" class="form-control" placeholder="Enter student ID" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-camera"></i> Photo
                    </label>
                    <input type="file" name="photo" class="form-control" accept="image/*">
                    <small style="color: var(--text-secondary); font-size: 0.8125rem; margin-top: 0.25rem; display: block;">
                        Upload a clear photo of the candidate (JPG, PNG, GIF supported)
                    </small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-file-alt"></i> Manifesto
                    </label>
                    <textarea name="manifesto" class="form-control" rows="4" 
                              placeholder="Enter candidate's manifesto and campaign promises..." required></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Candidate
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Manifesto Modal -->
    <div id="manifestoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="manifestoTitle">Candidate Manifesto</h2>
            </div>
            <div id="manifestoContent" style="max-height: 400px; overflow-y: auto; padding: 1rem 0; line-height: 1.8; color: var(--text-primary);">
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeManifestoModal()">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            filterCandidates();
        });

        // Filter functionality
        document.getElementById('statusFilter').addEventListener('change', function() {
            filterCandidates();
        });

        document.getElementById('electionFilter').addEventListener('change', function() {
            filterCandidates();
        });

        function filterCandidates() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const electionFilter = document.getElementById('electionFilter').value;
            const candidateCards = document.querySelectorAll('.candidate-card');
            
            let visibleCount = 0;
            
            candidateCards.forEach(card => {
                const name = card.dataset.name;
                const id = card.dataset.id;
                const status = card.dataset.status;
                const election = card.dataset.election;
                
                const matchesSearch = !searchTerm || name.includes(searchTerm) || id.includes(searchTerm);
                const matchesStatus = !statusFilter || status === statusFilter;
                const matchesElection = !electionFilter || election === electionFilter;
                
                if (matchesSearch && matchesStatus && matchesElection) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Show/hide empty state
            const grid = document.getElementById('candidatesGrid');
            if (visibleCount === 0 && candidateCards.length > 0) {
                if (!document.getElementById('noResults')) {
                    const noResults = document.createElement('div');
                    noResults.id = 'noResults';
                    noResults.className = 'empty-state';
                    noResults.innerHTML = `
                        <div class="empty-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3 class="empty-title">No Results Found</h3>
                        <p class="empty-text">Try adjusting your search criteria or filters.</p>
                        <button class="btn btn-secondary" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Clear Filters
                        </button>
                    `;
                    grid.parentNode.appendChild(noResults);
                }
                document.getElementById('noResults').style.display = 'block';
                grid.style.display = 'none';
            } else {
                const noResults = document.getElementById('noResults');
                if (noResults) {
                    noResults.style.display = 'none';
                }
                grid.style.display = 'grid';
            }
        }

        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('electionFilter').value = '';
            filterCandidates();
        }

        // Modal functions
        function showAddModal() {
            document.getElementById('addModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal() {
            document.getElementById('addModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        function viewManifesto(candidateName, manifesto) {
            document.getElementById('manifestoTitle').textContent = candidateName + "'s Manifesto";
            document.getElementById('manifestoContent').innerHTML = '<p>' + manifesto.replace(/\n/g, '</p><p>') + '</p>';
            document.getElementById('manifestoModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeManifestoModal() {
            document.getElementById('manifestoModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }
        
        function loadPositions() {
            const electionId = document.getElementById('election_select').value;
            const positionSelect = document.getElementById('position_select');
            
            if (electionId) {
                // AJAX call to get positions for selected election
                fetch(`get_positions.php?election_id=${electionId}`)
                    .then(response => response.json())
                    .then(data => {
                        positionSelect.innerHTML = '<option value="">Select Position</option>';
                        data.forEach(position => {
                            positionSelect.innerHTML += `<option value="${position.position_id}">${position.title}</option>`;
                        });
                    })
                    .catch(error => {
                        console.error('Error loading positions:', error);
                        positionSelect.innerHTML = '<option value="">Unable to load positions</option>';
                    });
            } else {
                positionSelect.innerHTML = '<option value="">Select Position</option>';
            }
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addModal');
            const manifestoModal = document.getElementById('manifestoModal');
            
            if (event.target === addModal) {
                closeModal();
            }
            if (event.target === manifestoModal) {
                closeManifestoModal();
            }
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
                closeManifestoModal();
            }
            if (event.ctrlKey && event.key === 'k') {
                event.preventDefault();
                document.getElementById('searchInput').focus();
            }
        });

        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', function() {
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

        // Add loading states to buttons
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const button = form.querySelector('button[type="submit"]');
                if (button) {
                    button.disabled = true;
                    const originalContent = button.innerHTML;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    
                    setTimeout(() => {
                        button.disabled = false;
                        button.innerHTML = originalContent;
                    }, 3000);
                }
            });
        });
    </script>
</body>
</html>