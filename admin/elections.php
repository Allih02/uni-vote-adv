<?php
// elections.php - Elections Management
require_once 'includes/session_check.php';
require_once 'config/database.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $conn = getDBConnection();
        
        switch ($_POST['action']) {
            case 'create':
                $title = $_POST['title'];
                $description = $_POST['description'];
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
                
                $stmt = $conn->prepare("INSERT INTO elections (title, description, start_date, end_date, created_by) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$title, $description, $start_date, $end_date, $_SESSION['admin_id']]);
                
                header("Location: elections.php?success=created");
                exit();
                break;
                
            case 'update_status':
                $election_id = $_POST['election_id'];
                $status = $_POST['status'];
                
                $stmt = $conn->prepare("UPDATE elections SET status = ? WHERE election_id = ?");
                $stmt->execute([$status, $election_id]);
                
                header("Location: elections.php?success=updated");
                exit();
                break;
        }
    }
}

// Get all elections
try {
    $conn = getDBConnection();
    $stmt = $conn->query("SELECT e.*, a.full_name as created_by_name 
                          FROM elections e 
                          LEFT JOIN admins a ON e.created_by = a.admin_id 
                          ORDER BY e.created_at DESC");
    $elections = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $elections = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elections Management - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php echo getSessionScript($_SESSION['session_lifetime'], $_SESSION['login_time']); ?>
    <style>
        /* ===== CSS VARIABLES ===== */
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

        /* ===== RESET & BASE STYLES ===== */
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
        }

        /* ===== LAYOUT STRUCTURE ===== */
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
            display: flex;
            flex-direction: column;
        }

        /* ===== SIDEBAR STYLES ===== */
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
            font-size: 1.25rem;
            font-weight: 700;
        }

        .logo i {
            font-size: 1.5rem;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }

        .nav-item:hover {
            background: var(--surface-hover);
            color: var(--text-primary);
        }

        .nav-item.active {
            background: var(--primary-light);
            color: var(--primary-dark);
            border-left-color: var(--primary);
            font-weight: 500;
        }

        .nav-item i {
            width: 1.25rem;
            text-align: center;
        }

        /* ===== HEADER STYLES ===== */
        .top-bar {
            background: var(--surface);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-sm);
        }

        .top-bar h1 {
            font-size: 1.875rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        /* ===== MAIN CONTENT STYLES ===== */
        .page-content {
            flex: 1;
            padding: 2rem;
        }

        /* ===== ALERTS ===== */
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

        /* ===== ELECTION CARDS ===== */
        .elections-list {
            display: grid;
            gap: 1.5rem;
        }

        .election-card {
            background: var(--surface);
            border-radius: var(--radius-xl);
            padding: 2rem;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
        }

        .election-card:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--border-dark);
        }

        .election-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            gap: 1rem;
        }

        .election-info {
            flex: 1;
        }

        .election-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }

        .election-meta {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .election-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .election-meta-item i {
            width: 1rem;
            text-align: center;
            color: var(--text-muted);
        }

        .election-status {
            padding: 0.5rem 1rem;
            border-radius: var(--radius-lg);
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            white-space: nowrap;
        }

        .status-draft {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        .status-upcoming {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .status-completed {
            background: #f3e8ff;
            color: #6b21a8;
            border: 1px solid #c4b5fd;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .election-description {
            color: var(--text-secondary);
            margin-bottom: 2rem;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .election-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
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

        .btn:active {
            transform: translateY(0);
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
            border-color: var(--border-dark);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
            box-shadow: var(--shadow-md);
        }

        .btn-danger {
            background: var(--error);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
            box-shadow: var(--shadow-md);
        }

        /* ===== MODAL STYLES ===== */
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
            padding: 2.5rem;
            border-radius: var(--radius-xl);
            width: 100%;
            max-width: 550px;
            margin: 1rem;
            box-shadow: var(--shadow-lg);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-content h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 2rem;
            color: var(--text-primary);
        }

        /* ===== FORM STYLES ===== */
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
            padding: 0.75rem 1rem;
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
            min-height: 120px;
            font-family: inherit;
        }

        .form-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
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

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .main-content {
                margin-left: 0;
            }

            .top-bar {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .page-content {
                padding: 1rem;
            }

            .election-header {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }

            .election-actions {
                justify-content: center;
            }

            .modal-content {
                margin: 0.5rem;
                padding: 1.5rem;
            }

            .form-actions {
                flex-direction: column;
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
                <a href="elections.php" class="nav-item active">
                    <i class="fas fa-poll"></i>
                    <span>Elections</span>
                </a>
                <a href="candidates.php" class="nav-item">
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
            <!-- Top Bar -->
            <div class="top-bar">
                <h1>Elections Management</h1>
                <button class="btn btn-primary" onclick="showCreateModal()">
                    <i class="fas fa-plus"></i>
                    Create Election
                </button>
            </div>

            <!-- Page Content -->
            <div class="page-content">
                <!-- Success Messages -->
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        switch($_GET['success']) {
                            case 'created':
                                echo 'Election created successfully!';
                                break;
                            case 'updated':
                                echo 'Election updated successfully!';
                                break;
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <!-- Elections List -->
                <div class="elections-list">
                    <?php if (empty($elections)): ?>
                        <div class="empty-state">
                            <i class="fas fa-poll"></i>
                            <h3>No Elections Found</h3>
                            <p>Get started by creating your first election.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($elections as $election): ?>
                            <div class="election-card">
                                <div class="election-header">
                                    <div class="election-info">
                                        <h3 class="election-title"><?php echo htmlspecialchars($election['title']); ?></h3>
                                        <div class="election-meta">
                                            <div class="election-meta-item">
                                                <i class="fas fa-user"></i>
                                                <span>Created by: <?php echo htmlspecialchars($election['created_by_name']); ?></span>
                                            </div>
                                            <div class="election-meta-item">
                                                <i class="fas fa-calendar-alt"></i>
                                                <span>Start: <?php echo date('M d, Y \a\t H:i', strtotime($election['start_date'])); ?></span>
                                            </div>
                                            <div class="election-meta-item">
                                                <i class="fas fa-calendar-check"></i>
                                                <span>End: <?php echo date('M d, Y \a\t H:i', strtotime($election['end_date'])); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="election-status status-<?php echo $election['status']; ?>">
                                        <?php echo ucfirst($election['status']); ?>
                                    </span>
                                </div>
                                
                                <p class="election-description">
                                    <?php echo htmlspecialchars($election['description']); ?>
                                </p>
                                
                                <div class="election-actions">
                                    <a href="election-details.php?id=<?php echo $election['election_id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-eye"></i>
                                        View Details
                                    </a>
                                    <button class="btn btn-secondary" onclick="editElection(<?php echo $election['election_id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                        Edit
                                    </button>
                                    <?php if ($election['status'] === 'draft'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="election_id" value="<?php echo $election['election_id']; ?>">
                                            <input type="hidden" name="status" value="upcoming">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-check"></i>
                                                Publish
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Create Election Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <h2>Create New Election</h2>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label class="form-label">Election Title</label>
                    <input type="text" name="title" class="form-control" placeholder="Enter election title..." required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" placeholder="Describe the purpose and details of this election..." required></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Start Date & Time</label>
                    <input type="datetime-local" name="start_date" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">End Date & Time</label>
                    <input type="datetime-local" name="end_date" class="form-control" required>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Election</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showCreateModal() {
            document.getElementById('createModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('createModal').classList.remove('active');
        }
        
        function editElection(id) {
            window.location.href = `edit-election.php?id=${id}`;
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('createModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
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
    </script>
</body>
</html>