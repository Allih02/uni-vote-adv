<?php
session_start();
if (!isset($_SESSION['studentId'])) {
    header('Location: index.php');
    exit;
}

// Mock data - replace with actual database query
$votingHistory = [
    [
        'election' => 'Student Council Presidential Election 2025',
        'date' => '2025-03-15',
        'candidate' => 'Sarah Johnson',
        'status' => 'Active',
        'turnout' => '75%',
        'category' => 'Executive Board'
    ],
    [
        'election' => 'Class Representative Election 2024 - 2025',
        'date' => '2024-11-05',
        'candidate' => 'Class Representatives',
        'status' => 'Completed',
        'turnout' => '100%',
        'category' => 'Students Government'
    ],
    [
        'election' => 'Student Council Presidential Election 2024',
        'date' => '2024-06-10',
        'candidate' => 'Edwin A. Andrea',
        'status' => 'Completed',
        'turnout' => '82%',
        'category' => 'Executive Board'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting History - Student Portal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f6f9fc 0%, #edf2f7 100%);
            min-height: 100vh;
            padding: 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title {
            color: #2d3748;
        }

        .page-title h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .page-title p {
            color: #718096;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            color: #718096;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #f8fafc;
            color: #2d3748;
            transform: translateY(-2px);
        }

        .history-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .history-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1.5rem;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .history-stats {
            display: flex;
            gap: 2rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .history-list {
            padding: 1rem;
        }

        .history-item {
            display: grid;
            grid-template-columns: 1fr 2fr 1fr 1fr;
            gap: 1rem;
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            transition: transform 0.3s ease;
        }

        .history-item:hover {
            background: #f8fafc;
            transform: translateX(10px);
        }

        .history-item:last-child {
            border-bottom: none;
        }

        .date-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #718096;
        }

        .election-info h3 {
            color: #2d3748;
            margin-bottom: 0.25rem;
        }

        .election-info p {
            color: #718096;
            font-size: 0.9rem;
        }

        .category-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            background: #ebf8ff;
            color: #3182ce;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .status-active {
            background: #e6fffa;
            color: #319795;
        }

        .status-completed {
            background: #f0fff4;
            color: #38a169;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .history-item {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }

            .history-stats {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="page-title">
                <h1>Voting History</h1>
                <p>Track your participation in student elections and polls</p>
            </div>
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>

        <div class="history-container">
            <div class="history-header">
                <div class="history-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo count($votingHistory); ?></div>
                        <div class="stat-label">Total Votes</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">75%</div>
                        <div class="stat-label">Participation Rate</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">2024</div>
                        <div class="stat-label">Latest Election</div>
                    </div>
                </div>
            </div>

            <div class="history-list">
                <?php foreach ($votingHistory as $vote): ?>
                    <div class="history-item">
                        <div class="date-badge">
                            <i class="far fa-calendar"></i>
                            <?php echo date('M d, Y', strtotime($vote['date'])); ?>
                        </div>
                        <div class="election-info">
                            <h3><?php echo htmlspecialchars($vote['election']); ?></h3>
                            <p>Voted for: <?php echo htmlspecialchars($vote['candidate']); ?></p>
                        </div>
                        <div class="category-badge">
                            <?php echo htmlspecialchars($vote['category']); ?>
                        </div>
                        <div class="status-badge status-<?php echo strtolower($vote['status']); ?>">
                            <i class="fas fa-circle"></i>
                            <?php echo htmlspecialchars($vote['status']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>