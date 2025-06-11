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

        /* Election Selector */
        .election-selector {
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

        .selector-label {
            font-weight: 600;
            color: var(--text-primary);
        }

        .election-select {
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            background: var(--surface);
            color: var(--text-primary);
            font-size: 1rem;
            min-width: 300px;
        }

        .election-status {
            padding: 0.375rem 0.75rem;
            border-radius: var(--radius-md);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-completed {
            background: #f3e8ff;
            color: #6b21a8;
        }

        /* Overview Cards */
        .overview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .overview-card {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: var(--radius-xl);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .overview-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary);
        }

        .overview-card.success::before { background: var(--success); }
        .overview-card.warning::before { background: var(--warning); }
        .overview-card.info::before { background: var(--info); }

        .overview-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .overview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .overview-icon {
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

        .overview-card.success .overview-icon { background: var(--success); }
        .overview-card.warning .overview-icon { background: var(--warning); }
        .overview-card.info .overview-icon { background: var(--info); }

        .overview-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .overview-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Results Grid */
        .results-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .results-card {
            background: var(--surface);
            border-radius: var(--radius-xl);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .results-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .results-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .results-body {
            padding: 2rem;
        }

        /* Candidate Results */
        .candidate-result {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border);
        }

        .candidate-result:last-child {
            border-bottom: none;
        }

        .candidate-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid var(--border);
            flex-shrink: 0;
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
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--text-primary);
        }

        .candidate-position {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .vote-bar {
            background: var(--border);
            height: 6px;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 0.25rem;
        }

        .vote-progress {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 3px;
            transition: width 0.5s ease;
        }

        .vote-stats {
            display: flex;
            justify-content: space-between;
            font-size: 0.875rem;
        }

        .vote-count {
            font-weight: 600;
            color: var(--text-primary);
        }

        .vote-percentage {
            color: var(--primary);
            font-weight: 600;
        }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 1rem;
        }

        .chart-small {
            height: 200px;
        }

        /* Analytics Grid */
        .analytics-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        /* Demographic Table */
        .demographic-table {
            width: 100%;
            border-collapse: collapse;
        }

        .demographic-table th,
        .demographic-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .demographic-table th {
            background: var(--surface-hover);
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .demographic-table tr:hover {
            background: var(--surface-hover);
        }

        .turnout-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .turnout-circle {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .turnout-high { background: var(--success); }
        .turnout-medium { background: var(--warning); }
        .turnout-low { background: var(--error); }

        /* Buttons */
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

        /* Live Updates */
        .live-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--success);
        }

        .live-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--success);
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .results-grid {
                grid-template-columns: 1fr;
            }
            
            .analytics-grid {
                grid-template-columns: 1fr;
            }
        }

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

            .election-selector {
                flex-direction: column;
                align-items: stretch;
            }

            .election-select {
                min-width: auto;
            }

            .overview-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .chart-container {
                height: 250px;
            }
        }

        @media (max-width: 480px) {
            .overview-grid {
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
                    <a href="admin_voters.php" class="nav-item">
                        <i class="fas fa-users"></i>
                        <span>Voters</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Reports & Analytics</div>
                    <a href="admin_results.php" class="nav-item active">
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
                <h1>Election Results & Analytics</h1>
                <div class="top-bar-actions">
                    <?php if ($selected_election['status'] === 'active'): ?>
                    <div class="live-indicator">
                        <div class="live-dot"></div>
                        <span>Live Results</span>
                    </div>
                    <?php endif; ?>
                    <button class="btn btn-secondary" onclick="exportResults()">
                        <i class="fas fa-download"></i>
                        Export Report
                    </button>
                    <button class="btn btn-primary" onclick="refreshResults()">
                        <i class="fas fa-sync-alt"></i>
                        Refresh
                    </button>
                </div>
            </div>

            <!-- Page Content -->
            <div class="page-content">
                <!-- Election Selector -->
                <div class="election-selector">
                    <span class="selector-label">Select Election:</span>
                    <select class="election-select" onchange="changeElection(this.value)">
                        <?php foreach ($elections as $election): ?>
                        <option value="<?php echo $election['id']; ?>" <?php echo $election['id'] == $selected_election['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($election['title']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="election-status status-<?php echo $selected_election['status']; ?>">
                        <?php echo ucfirst($selected_election['status']); ?>
                    </span>
                </div>

                <!-- Overview Cards -->
                <div class="overview-grid">
                    <div class="overview-card">
                        <div class="overview-header">
                            <div class="overview-icon">
                                <i class="fas fa-vote-yea"></i>
                            </div>
                        </div>
                        <div class="overview-value"><?php echo number_format($selected_election['total_votes']); ?></div>
                        <div class="overview-label">Total Votes Cast</div>
                    </div>

                    <div class="overview-card success">
                        <div class="overview-header">
                            <div class="overview-icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="overview-value"><?php echo number_format($selected_election['eligible_voters']); ?></div>
                        <div class="overview-label">Eligible Voters</div>
                    </div>

                    <div class="overview-card warning">
                        <div class="overview-header">
                            <div class="overview-icon">
                                <i class="fas fa-percentage"></i>
                            </div>
                        </div>
                        <div class="overview-value"><?php echo number_format($selected_election['turnout_percentage'], 1); ?>%</div>
                        <div class="overview-label">Voter Turnout</div>
                    </div>

                    <div class="overview-card info">
                        <div class="overview-header">
                            <div class="overview-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                        </div>
                        <div class="overview-value"><?php echo count($selected_election['candidates']); ?></div>
                        <div class="overview-label">Candidates</div>
                    </div>
                </div>

                <!-- Results Grid -->
                <div class="results-grid">
                    <!-- Candidate Results -->
                    <div class="results-card">
                        <div class="results-header">
                            <h3 class="results-title">Candidate Results</h3>
                            <span style="font-size: 0.875rem; color: var(--text-muted);">
                                Last updated: <?php echo date('H:i'); ?>
                            </span>
                        </div>
                        <div class="results-body">
                            <?php foreach ($selected_election['candidates'] as $candidate): ?>
                            <div class="candidate-result">
                                <div class="candidate-avatar">
                                    <img src="<?php echo htmlspecialchars($candidate['image']); ?>" alt="<?php echo htmlspecialchars($candidate['name']); ?>">
                                </div>
                                <div class="candidate-info">
                                    <div class="candidate-name"><?php echo htmlspecialchars($candidate['name']); ?></div>
                                    <div class="candidate-position"><?php echo htmlspecialchars($candidate['position']); ?></div>
                                    <div class="vote-bar">
                                        <div class="vote-progress" style="width: <?php echo $candidate['percentage']; ?>%"></div>
                                    </div>
                                    <div class="vote-stats">
                                        <span class="vote-count"><?php echo number_format($candidate['votes']); ?> votes</span>
                                        <span class="vote-percentage"><?php echo number_format($candidate['percentage'], 1); ?>%</span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Vote Distribution Chart -->
                    <div class="results-card">
                        <div class="results-header">
                            <h3 class="results-title">Vote Distribution</h3>
                        </div>
                        <div class="results-body">
                            <div class="chart-container chart-small">
                                <canvas id="distributionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Analytics Grid -->
                <div class="analytics-grid">
                    <!-- Voting Trends -->
                    <div class="results-card">
                        <div class="results-header">
                            <h3 class="results-title">Voting Trends</h3>
                        </div>
                        <div class="results-body">
                            <div class="chart-container">
                                <canvas id="trendsChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Demographic Analysis -->
                    <div class="results-card">
                        <div class="results-header">
                            <h3 class="results-title">Demographic Analysis</h3>
                        </div>
                        <div class="results-body">
                            <table class="demographic-table">
                                <thead>
                                    <tr>
                                        <th>Faculty</th>
                                        <th>Votes</th>
                                        <th>Share</th>
                                        <th>Turnout</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($demographic_data as $demo): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($demo['faculty']); ?></td>
                                        <td><?php echo number_format($demo['votes']); ?></td>
                                        <td><?php echo number_format($demo['percentage'], 1); ?>%</td>
                                        <td>
                                            <div class="turnout-indicator">
                                                <div class="turnout-circle <?php echo $demo['turnout'] >= 70 ? 'turnout-high' : ($demo['turnout'] >= 50 ? 'turnout-medium' : 'turnout-low'); ?>"></div>
                                                <?php echo number_format($demo['turnout'], 1); ?>%
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            initializeMobile();
            initializeCharts();
            
            // Auto-refresh for active elections
            <?php if ($selected_election['status'] === 'active'): ?>
            setInterval(refreshResults, 30000); // Refresh every 30 seconds
            <?php endif; ?>
        });

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

        function initializeCharts() {
            // Vote Distribution Pie Chart
            const distributionCtx = document.getElementById('distributionChart').getContext('2d');
            const distributionChart = new Chart(distributionCtx, {
                type: 'doughnut',
                data: {
                    labels: [
                        <?php foreach ($selected_election['candidates'] as $candidate): ?>
                        '<?php echo addslashes($candidate['name']); ?>',
                        <?php endforeach; ?>
                    ],
                    datasets: [{
                        data: [
                            <?php foreach ($selected_election['candidates'] as $candidate): ?>
                            <?php echo $candidate['votes']; ?>,
                            <?php endforeach; ?>
                        ],
                        backgroundColor: [
                            '#6366f1',
                            '#8b5cf6',
                            '#f43f5e',
                            '#10b981',
                            '#f59e0b'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: {
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });

            // Voting Trends Line Chart
            const trendsCtx = document.getElementById('trendsChart').getContext('2d');
            const trendsChart = new Chart(trendsCtx, {
                type: 'line',
                data: {
                    labels: [
                        <?php foreach ($voting_trends as $trend): ?>
                        '<?php echo $trend['hour']; ?>',
                        <?php endforeach; ?>
                    ],
                    datasets: [{
                        label: 'Votes per Hour',
                        data: [
                            <?php foreach ($voting_trends as $trend): ?>
                            <?php echo $trend['votes']; ?>,
                            <?php endforeach; ?>
                        ],
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 10
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        function changeElection(electionId) {
            window.location.href = `admin_results.php?election=${electionId}`;
        }

        function refreshResults() {
            // In a real application, this would fetch updated data
            showNotification('Results refreshed successfully!', 'success');
            
            // Animate refresh button
            const refreshBtn = document.querySelector('.fa-sync-alt');
            refreshBtn.style.animation = 'spin 1s linear';
            setTimeout(() => {
                refreshBtn.style.animation = '';
            }, 1000);
        }

        function exportResults() {
            showNotification('Export feature coming soon!', 'info');
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-${type === 'info' ? 'info-circle' : type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
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
                border-left: 4px solid var(--${type === 'info' ? 'info' : type === 'success' ? 'success' : 'warning'});
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
            }, 4000);
        }

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html><?php
// Election Results & Analytics - No authentication required for demo
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

// Mock election results data
$elections = [
    [
        "id" => 1,
        "title" => "Student Council Elections 2025",
        "status" => "active",
        "start_date" => "2025-06-01",
        "end_date" => "2025-06-30",
        "total_votes" => 324,
        "eligible_voters" => 1250,
        "turnout_percentage" => 25.9,
        "candidates" => [
            [
                "id" => 1,
                "name" => "Allih A. Abubakar",
                "position" => "Student Body President",
                "votes" => 156,
                "percentage" => 48.1,
                "image" => "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&h=100&fit=crop&crop=face"
            ],
            [
                "id" => 2,
                "name" => "Sarah Chen",
                "position" => "Student Body President",
                "votes" => 142,
                "percentage" => 43.8,
                "image" => "https://images.unsplash.com/photo-1494790108755-2616b169b9c0?w=100&h=100&fit=crop&crop=face"
            ],
            [
                "id" => 6,
                "name" => "Lisa Park",
                "position" => "Cultural Representative",
                "votes" => 26,
                "percentage" => 8.1,
                "image" => "https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&h=100&fit=crop&crop=face"
            ]
        ]
    ],
    [
        "id" => 3,
        "title" => "Graduation Committee Elections",
        "status" => "completed",
        "start_date" => "2025-04-01",
        "end_date" => "2025-04-15",
        "total_votes" => 156,
        "eligible_voters" => 200,
        "turnout_percentage" => 78.0,
        "candidates" => [
            [
                "id" => 7,
                "name" => "Alex Johnson",
                "position" => "Committee Chair",
                "votes" => 89,
                "percentage" => 57.1,
                "image" => "https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100&fit=crop&crop=face"
            ],
            [
                "id" => 8,
                "name" => "Maria Garcia",
                "position" => "Committee Chair",
                "votes" => 67,
                "percentage" => 42.9,
                "image" => "https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=100&h=100&fit=crop&crop=face"
            ]
        ]
    ]
];

// Mock voting trends data (hourly for current election)
$voting_trends = [
    ["hour" => "08:00", "votes" => 12],
    ["hour" => "09:00", "votes" => 28],
    ["hour" => "10:00", "votes" => 45],
    ["hour" => "11:00", "votes" => 67],
    ["hour" => "12:00", "votes" => 52],
    ["hour" => "13:00", "votes" => 38],
    ["hour" => "14:00", "votes" => 56],
    ["hour" => "15:00", "votes" => 42],
    ["hour" => "16:00", "votes" => 34],
    ["hour" => "17:00", "votes" => 18]
];

// Mock demographic data
$demographic_data = [
    [
        "faculty" => "Science & Technology",
        "votes" => 98,
        "percentage" => 30.2,
        "turnout" => 65.3
    ],
    [
        "faculty" => "Business & Economics", 
        "votes" => 87,
        "percentage" => 26.9,
        "turnout" => 58.4
    ],
    [
        "faculty" => "Engineering",
        "votes" => 76,
        "percentage" => 23.5,
        "turnout" => 72.4
    ],
    [
        "faculty" => "Arts & Humanities",
        "votes" => 45,
        "percentage" => 13.9,
        "turnout" => 48.9
    ],
    [
        "faculty" => "Health Sciences",
        "votes" => 18,
        "percentage" => 5.5,
        "turnout" => 75.0
    ]
];

// Get selected election for detailed view
$selected_election_id = isset($_GET['election']) ? intval($_GET['election']) : 1;
$selected_election = array_filter($elections, function($e) use ($selected_election_id) {
    return $e['id'] == $selected_election_id;
});
$selected_election = !empty($selected_election) ? reset($selected_election) : $elections[0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results & Analytics - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            --