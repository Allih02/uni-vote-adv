<?php
// Candidates Management - No authentication required for demo
// session_start();
// if (!isset($_SESSION["admin_id"]) || $_SESSION["role"] !== "admin") {
//     header("Location: admin_login.php");
//     exit();
// }

// Mock admin data
$admin_user = [
    "admin_id" => 1,
    "fullname" => "Dr. Sarah Johnson",
    "email" => "admin@university.edu",
    "role" => "Administrator"
];

// Handle form submissions (simplified without database)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                header("Location: admin_candidates.php?success=created");
                exit();
                break;
            case 'approve':
                header("Location: admin_candidates.php?success=approved");
                exit();
                break;
            case 'reject':
                header("Location: admin_candidates.php?success=rejected");
                exit();
                break;
            case 'delete':
                header("Location: admin_candidates.php?success=deleted");
                exit();
                break;
        }
    }
}

// Mock candidates data
$candidates = [
    [
        "id" => 1,
        "name" => "Allih A. Abubakar",
        "email" => "allih.abubakar@university.edu",
        "student_id" => "ST2024001",
        "position" => "Student Body President",
        "election_id" => 1,
        "election_title" => "Student Council Elections 2025",
        "platform" => "Campus Sustainability & Student Wellness - Focusing on mental health resources, environmental initiatives, and inclusive campus activities.",
        "status" => "approved",
        "image" => "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=400&fit=crop&crop=face",
        "created_at" => "2025-05-20 10:30:00",
        "approved_at" => "2025-05-21 14:15:00",
        "approved_by" => "Dr. Sarah Johnson",
        "vote_count" => 156,
        "qualifications" => "3rd Year Computer Science, Former Class Representative, Volunteer Coordinator",
        "contact_phone" => "+255 123 456 789"
    ],
    [
        "id" => 2,
        "name" => "Sarah Chen",
        "email" => "sarah.chen@university.edu",
        "student_id" => "ST2024002",
        "position" => "Student Body President",
        "election_id" => 1,
        "election_title" => "Student Council Elections 2025",
        "platform" => "Academic Excellence & Campus Unity - Promoting collaborative learning environments and bridging gaps between different student communities.",
        "status" => "approved",
        "image" => "https://images.unsplash.com/photo-1494790108755-2616b169b9c0?w=400&h=400&fit=crop&crop=face",
        "created_at" => "2025-05-18 09:45:00",
        "approved_at" => "2025-05-19 11:20:00",
        "approved_by" => "Prof. Michael Davis",
        "vote_count" => 142,
        "qualifications" => "4th Year Business Administration, Student Senate Member, Debate Club President",
        "contact_phone" => "+255 987 654 321"
    ],
    [
        "id" => 3,
        "name" => "Michael Thompson",
        "email" => "michael.thompson@university.edu",
        "student_id" => "ST2024003",
        "position" => "Vice President",
        "election_id" => 1,
        "election_title" => "Student Council Elections 2025",
        "platform" => "Student Support Services - Enhancing academic support, career counseling, and financial aid accessibility for all students.",
        "status" => "pending",
        "image" => "https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=400&h=400&fit=crop&crop=face",
        "created_at" => "2025-05-25 16:20:00",
        "approved_at" => null,
        "approved_by" => null,
        "vote_count" => 0,
        "qualifications" => "3rd Year Engineering, Peer Tutor, Community Service Leader",
        "contact_phone" => "+255 555 123 456"
    ],
    [
        "id" => 4,
        "name" => "Emily Rodriguez",
        "email" => "emily.rodriguez@university.edu",
        "student_id" => "ST2024004",
        "position" => "Secretary",
        "election_id" => 1,
        "election_title" => "Student Council Elections 2025",
        "platform" => "Communication & Transparency - Improving information flow between administration and students through better communication channels.",
        "status" => "pending",
        "image" => "https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=400&h=400&fit=crop&crop=face",
        "created_at" => "2025-05-26 11:30:00",
        "approved_at" => null,
        "approved_by" => null,
        "vote_count" => 0,
        "qualifications" => "2nd Year Communications, Newsletter Editor, Event Coordinator",
        "contact_phone" => "+255 777 888 999"
    ],
    [
        "id" => 5,
        "name" => "James Wilson",
        "email" => "james.wilson@university.edu",
        "student_id" => "ST2024005",
        "position" => "Sports Representative",
        "election_id" => 4,
        "election_title" => "Sports Committee Elections",
        "platform" => "Athletic Excellence & Inclusion - Promoting sports participation across all skill levels and improving athletic facilities.",
        "status" => "rejected",
        "image" => "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400&h=400&fit=crop&crop=face",
        "created_at" => "2025-05-22 14:45:00",
        "approved_at" => null,
        "approved_by" => null,
        "vote_count" => 0,
        "qualifications" => "4th Year Sports Science, Team Captain, Fitness Instructor",
        "contact_phone" => "+255 444 555 666",
        "rejection_reason" => "Incomplete documentation - missing academic transcripts"
    ],
    [
        "id" => 6,
        "name" => "Lisa Park",
        "email" => "lisa.park@university.edu",
        "student_id" => "ST2024006",
        "position" => "Cultural Representative",
        "election_id" => 1,
        "election_title" => "Student Council Elections 2025",
        "platform" => "Cultural Diversity & Arts - Celebrating multicultural heritage and expanding arts programs on campus.",
        "status" => "approved",
        "image" => "https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&h=400&fit=crop&crop=face",
        "created_at" => "2025-05-19 13:15:00",
        "approved_at" => "2025-05-20 09:30:00",
        "approved_by" => "Dr. Sarah Johnson",
        "vote_count" => 89,
        "qualifications" => "3rd Year Arts & Culture, International Student Ambassador, Event Organizer",
        "contact_phone" => "+255 333 222 111"
    ]
];

// Calculate statistics
$total_candidates = count($candidates);
$approved_candidates = count(array_filter($candidates, function($c) { return $c['status'] === 'approved'; }));
$pending_candidates = count(array_filter($candidates, function($c) { return $c['status'] === 'pending'; }));
$rejected_candidates = count(array_filter($candidates, function($c) { return $c['status'] === 'rejected'; }));
$total_votes = array_sum(array_column($candidates, 'vote_count'));

// Get unique elections for filter
$elections = array_unique(array_map(function($c) {
    return ['id' => $c['election_id'], 'title' => $c['election_title']];
}, $candidates), SORT_REGULAR);

// Get unique positions for filter
$positions = array_unique(array_column($candidates, 'position'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidates Management - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
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

        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: var(--surface);
            border-right: 1px solid var(--border);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
            box-shadow: var(--shadow-sm);
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: white;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-section {
            margin-bottom: 1.5rem;
        }

        .nav-section-title {
            padding: 0.5rem 1.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
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

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

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

        .top-bar-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .page-content {
            flex: 1;
            padding: 2rem;
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
        .candidate-card.status-approved::before { background: var(--success); }
        .candidate-card.status-rejected::before { background: var(--error); }

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

        .status-approved {
            background: #dcfce7;
            color: #166534;
        }

        .status-rejected {
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
            background: var(--surface-hover);
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
            background: var(--surface-hover);
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

        /* Responsive Design */
        @media (max-width: 768px) {
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

            .top-bar {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .page-content {
                padding: 1rem;
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
            .stats-grid {
                grid-template-columns: 1fr;
            }
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
                        <div class="stat-label">Approved</div>
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
                        <div class="stat-label">Rejected</div>
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
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
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
                                 data-name="<?php echo strtolower($candidate['name']); ?>">
                                
                                <div class="candidate-status status-<?php echo $candidate['status']; ?>">
                                    <?php echo ucfirst($candidate['status']); ?>
                                </div>
                                
                                <div class="candidate-header">
                                    <div class="candidate-avatar">
                                        <img src="<?php echo htmlspecialchars($candidate['image']); ?>" alt="<?php echo htmlspecialchars($candidate['name']); ?>">
                                    </div>
                                    <div class="candidate-info">
                                        <h3 class="candidate-name"><?php echo htmlspecialchars($candidate['name']); ?></h3>
                                        <div class="candidate-position"><?php echo htmlspecialchars($candidate['position']); ?></div>
                                        <div class="candidate-meta">
                                            <div><i class="fas fa-id-card"></i> <?php echo htmlspecialchars($candidate['student_id']); ?></div>
                                            <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($candidate['email']); ?></div>
                                            <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($candidate['contact_phone']); ?></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="candidate-body">
                                    <div class="candidate-platform">
                                        <?php echo htmlspecialchars($candidate['platform']); ?>
                                    </div>

                                    <div class="candidate-qualifications">
                                        <h4>Qualifications</h4>
                                        <p><?php echo htmlspecialchars($candidate['qualifications']); ?></p>
                                    </div>

                                    <?php if ($candidate['status'] === 'approved'): ?>
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
                                            <div class="candidate-stat-value"><?php echo date('M d', strtotime($candidate['approved_at'])); ?></div>
                                            <div class="candidate-stat-label">Approved</div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($candidate['status'] === 'rejected' && isset($candidate['rejection_reason'])): ?>
                                    <div style="background: #fee2e2; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
                                        <h4 style="color: #991b1b; font-size: 0.875rem; margin-bottom: 0.5rem;">Rejection Reason</h4>
                                        <p style="color: #991b1b; font-size: 0.8rem;"><?php echo htmlspecialchars($candidate['rejection_reason']); ?></p>
                                    </div>
                                    <?php endif; ?>

                                    <div class="candidate-actions">
                                        <button class="btn btn-secondary btn-sm" onclick="viewCandidate(<?php echo $candidate['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                            View Details
                                        </button>
                                        
                                        <?php if ($candidate['status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="candidate_id" value="<?php echo $candidate['id']; ?>">
                                                <button type="submit" class="btn btn-success btn-sm">
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
            
            <form method="POST" class="form-grid">
                <input type="hidden" name="action" value="create">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="Enter full name..." required>
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
                            <option value="Student Body President">Student Body President</option>
                            <option value="Vice President">Vice President</option>
                            <option value="Secretary">Secretary</option>
                            <option value="Treasurer">Treasurer</option>
                            <option value="Sports Representative">Sports Representative</option>
                            <option value="Cultural Representative">Cultural Representative</option>
                            <option value="Academic Representative">Academic Representative</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Platform Statement *</label>
                    <textarea name="platform" class="form-control" placeholder="Describe the candidate's platform and goals..." required></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Qualifications</label>
                    <textarea name="qualifications" class="form-control" placeholder="List relevant qualifications, experience, and achievements..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Candidate</button>
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
                <p>Are you sure you want to delete this candidate? This action cannot be undone.</p>
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
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            initializeFilters();
            initializeMobile();
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

        function initializeMobile() {
            if (window.innerWidth <= 768) {
                const sidebarToggle = document.createElement('button');
                sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
                sidebarToggle.className = 'btn btn-secondary';
                sidebarToggle.style.marginRight = '1rem';
                
                document.querySelector('.top-bar h1').parentNode.insertBefore(sidebarToggle, document.querySelector('.top-bar h1'));
                
                sidebarToggle.addEventListener('click', function() {
                    document.getElementById('sidebar').classList.toggle('open');
                });
            }
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
            window.location.href = `admin_candidate_details.php?id=${id}`;
        }

        function editCandidate(id) {
            window.location.href = `admin_edit_candidate.php?id=${id}`;
        }

        function deleteCandidate(id) {
            document.getElementById('deleteCandidateId').value = id;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }

        function exportCandidates() {
            showNotification('Export feature coming soon!', 'info');
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-${type === 'info' ? 'info-circle' : 'check-circle'}"></i>
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
                border-left: 4px solid var(--${type === 'info' ? 'info' : 'success'});
                z-index: 1000;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease;
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
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>