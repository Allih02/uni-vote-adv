<?php
// reports.php - Reports Management
require_once 'includes/session_check.php';
require_once 'config/database.php';

// Handle report generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    $election_id = $_POST['election_id'];
    $report_type = $_POST['report_type'];
    
    try {
        $conn = getDBConnection();
        
        // Generate report file name
        $file_name = $report_type . '_' . date('YmdHis') . '.pdf';
        $file_path = 'uploads/reports/' . $file_name;
        
        // Create reports directory if it doesn't exist
        if (!file_exists('uploads/reports')) {
            mkdir('uploads/reports', 0777, true);
        }
        
        // In a real implementation, you would generate the actual PDF here
        // For now, we'll create a placeholder file
        file_put_contents($file_path, "Report placeholder content");
        
        // Save report record to database
        $stmt = $conn->prepare("INSERT INTO reports (election_id, report_type, file_path, generated_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$election_id, $report_type, $file_path, $_SESSION['admin_id']]);
        
        header("Location: reports.php?success=generated");
        exit();
    } catch (PDOException $e) {
        error_log($e->getMessage());
        header("Location: reports.php?error=generation_failed");
        exit();
    }
}

// Get all elections for dropdown
try {
    $conn = getDBConnection();
    $stmt = $conn->query("SELECT election_id, title FROM elections ORDER BY created_at DESC");
    $elections = $stmt->fetchAll();
    
    // Get all reports
    $stmt = $conn->query("
        SELECT r.*, e.title as election_title, a.full_name as generated_by_name
        FROM reports r
        LEFT JOIN elections e ON r.election_id = e.election_id
        LEFT JOIN admins a ON r.generated_by = a.admin_id
        ORDER BY r.created_at DESC
    ");
    $reports = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $elections = [];
    $reports = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?php echo getSessionScript($_SESSION['session_lifetime'], $_SESSION['login_time']); ?>
    <style>
        /* Include dashboard styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #8b5cf6;
            --accent: #f43f5e;
            --background: #f8fafc;
            --surface: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border: #e2e8f0;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --info: #3b82f6;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--background);
            color: var(--text-primary);
            line-height: 1.6;
        }

        /* Dashboard layout */
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
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            min-height: 100vh;
        }

        /* Reports specific styles */
        .reports-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }

        .report-generator {
            background: var(--surface);
            border-radius: 1rem;
            border: 1px solid var(--border);
            padding: 1.5rem;
            height: fit-content;
        }

        .reports-list {
            background: var(--surface);
            border-radius: 1rem;
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .report-item {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            transition: background 0.2s;
        }

        .report-item:last-child {
            border-bottom: none;
        }

        .report-item:hover {
            background: var(--background);
        }

        .report-icon {
            width: 40px;
            height: 40px;
            background: var(--primary);
            color: white;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }

        .report-info {
            flex: 1;
        }

        .report-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .report-meta {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .report-actions {
            display: flex;
            gap: 0.5rem;
        }

        /* Form styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            font-size: 1rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        /* Button styles */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-secondary {
            background: var(--background);
            color: var(--text-primary);
            border: 1px solid var(--border);
        }

        .btn-icon {
            padding: 0.5rem;
            border-radius: 0.375rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            background: transparent;
            color: var(--text-secondary);
        }

        .btn-icon:hover {
            background: var(--background);
            color: var(--text-primary);
        }

        /* Navigation styles */
        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 0.5rem;
            margin-bottom: 0.25rem;
            transition: all 0.2s;
        }

        .nav-item.active {
            background: var(--primary);
            color: white;
        }

        /* Alert styles */
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
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

        .report-type-card {
            border: 2px solid var(--border);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .report-type-card:hover {
            border-color: var(--primary);
        }

        .report-type-card.selected {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.05);
        }

        .report-type-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .report-type-description {
            font-size: 0.875rem;
            color: var(--text-secondary);
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
                <a href="voters.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Voters</span>
                </a>
                <a href="results.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Results</span>
                </a>
                <a href="reports.php" class="nav-item active">
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
                <h1>Reports</h1>
            </div>

            <div class="page-content">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        Report generated successfully!
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-error">
                        Failed to generate report. Please try again.
                    </div>
                <?php endif; ?>

                <div class="reports-grid">
                    <!-- Report Generator -->
                    <div class="report-generator">
                        <h2 style="margin-bottom: 1.5rem;">Generate New Report</h2>
                        
                        <form method="POST">
                            <input type="hidden" name="generate_report" value="1">
                            
                            <div class="form-group">
                                <label class="form-label">Election</label>
                                <select name="election_id" class="form-control" required>
                                    <option value="">Select Election</option>
                                    <?php foreach ($elections as $election): ?>
                                        <option value="<?php echo $election['election_id']; ?>">
                                            <?php echo htmlspecialchars($election['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Report Type</label>
                                
                                <div class="report-type-card" onclick="selectReportType(this, 'complete_results')">
                                    <input type="radio" name="report_type" value="complete_results" required style="display: none;">
                                    <div class="report-type-title">Complete Results Report</div>
                                    <div class="report-type-description">Detailed results for all positions including vote counts and percentages</div>
                                </div>
                                
                                <div class="report-type-card" onclick="selectReportType(this, 'voter_turnout')">
                                    <input type="radio" name="report_type" value="voter_turnout" required style="display: none;">
                                    <div class="report-type-title">Voter Turnout Report</div>
                                    <div class="report-type-description">Analysis of voter participation and demographics</div>
                                </div>
                                
                                <div class="report-type-card" onclick="selectReportType(this, 'candidate_summary')">
                                    <input type="radio" name="report_type" value="candidate_summary" required style="display: none;">
                                    <div class="report-type-title">Candidate Summary</div>
                                    <div class="report-type-description">Overview of all candidates and their campaign details</div>
                                </div>
                                
                                <div class="report-type-card" onclick="selectReportType(this, 'audit_trail')">
                                    <input type="radio" name="report_type" value="audit_trail" required style="display: none;">
                                    <div class="report-type-title">Audit Trail Report</div>
                                    <div class="report-type-description">Comprehensive log of all voting activities and system changes</div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-file-export"></i> Generate Report
                            </button>
                        </form>
                    </div>
                    
                    <!-- Reports List -->
                    <div class="reports-list">
                        <div style="padding: 1.5rem; border-bottom: 1px solid var(--border);">
                            <h2>Generated Reports</h2>
                        </div>
                        
                        <?php if (empty($reports)): ?>
                            <div style="padding: 3rem; text-align: center; color: var(--text-secondary);">
                                <i class="fas fa-file-alt" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                                <p>No reports generated yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($reports as $report): ?>
                                <div class="report-item">
                                    <div class="report-icon">
                                        <i class="fas fa-file-pdf"></i>
                                    </div>
                                    <div class="report-info">
                                        <div class="report-title">
                                            <?php echo ucwords(str_replace('_', ' ', $report['report_type'])); ?>
                                        </div>
                                        <div class="report-meta">
                                            <?php echo htmlspecialchars($report['election_title']); ?> • 
                                            Generated by <?php echo htmlspecialchars($report['generated_by_name']); ?> • 
                                            <?php echo date('M d, Y H:i', strtotime($report['created_at'])); ?>
                                        </div>
                                    </div>
                                    <div class="report-actions">
                                        <a href="<?php echo htmlspecialchars($report['file_path']); ?>" class="btn-icon" title="Download" download>
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <button class="btn-icon" title="View" onclick="viewReport('<?php echo htmlspecialchars($report['file_path']); ?>')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-icon" title="Delete" onclick="deleteReport(<?php echo $report['report_id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function selectReportType(card, type) {
            // Deselect all cards
            document.querySelectorAll('.report-type-card').forEach(c => {
                c.classList.remove('selected');
            });
            
            // Select clicked card
            card.classList.add('selected');
            card.querySelector('input[type="radio"]').checked = true;
        }
        
        function viewReport(path) {
            window.open(path, '_blank');
        }
        
        function deleteReport(reportId) {
            if (confirm('Are you sure you want to delete this report?')) {
                window.location.href = `delete_report.php?id=${reportId}`;
            }
        }
    </script>
</body>
</html>