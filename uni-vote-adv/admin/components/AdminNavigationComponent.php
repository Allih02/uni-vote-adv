<?php
/**
 * Unified Admin Navigation Component
 * 
 * This component provides a consistent navigation structure across all admin pages.
 * It includes the sidebar navigation, top bar, and breadcrumb functionality.
 * 
 * Usage: Include this file in any admin page and call renderAdminNavigation()
 */

class AdminNavigationComponent {
    private $currentPage;
    private $userInfo;
    private $stats;
    
    public function __construct($currentPage = '', $userInfo = [], $stats = []) {
        $this->currentPage = $currentPage;
        $this->userInfo = $userInfo;
        $this->stats = $stats;
    }
    
    /**
     * Main function to render the complete navigation structure
     */
    public function render() {
        echo $this->getNavigationCSS();
        echo $this->getSidebar();
        echo $this->getTopBar();
        echo $this->getBreadcrumbs();
    }
    
    /**
     * Get the CSS styles for the navigation
     */
    private function getNavigationCSS() {
        return '
        <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #a5b4fc;
            --secondary: #10b981;
            --secondary-light: #34d399;
            --surface: #ffffff;
            --surface-hover: #f8fafc;
            --background: #f1f5f9;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --border: #e2e8f0;
            --error: #ef4444;
            --warning: #f59e0b;
            --success: #10b981;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .admin-layout {
            display: flex;
            min-height: 100vh;
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--background);
            color: var(--text-primary);
        }
        
        /* Sidebar Styles */
        .admin-sidebar {
            width: 280px;
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
            color: white;
        }
        
        .admin-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: white;
            font-size: 1.25rem;
            font-weight: 700;
        }
        
        .admin-logo i {
            font-size: 1.5rem;
        }
        
        .admin-profile {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .admin-profile h4 {
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .admin-profile p {
            font-size: 0.75rem;
            opacity: 0.8;
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
            letter-spacing: 0.05em;
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
            position: relative;
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
            font-size: 1rem;
        }
        
        .nav-badge {
            position: absolute;
            right: 1rem;
            background: var(--error);
            color: white;
            font-size: 0.75rem;
            padding: 0.125rem 0.5rem;
            border-radius: 1rem;
            font-weight: 600;
            min-width: 1.25rem;
            text-align: center;
        }
        
        /* Main Content Area */
        .admin-main {
            flex: 1;
            margin-left: 280px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Top Bar */
        .admin-top-bar {
            background: var(--surface);
            padding: 1rem 2rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 50;
        }
        
        .top-bar-left h1 {
            font-size: 1.875rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }
        
        .top-bar-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .notification-icon {
            position: relative;
            padding: 0.5rem;
            color: var(--text-secondary);
            background: transparent;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .notification-icon:hover {
            background: var(--surface-hover);
            color: var(--text-primary);
        }
        
        .notification-badge {
            position: absolute;
            top: 0.25rem;
            right: 0.25rem;
            background: var(--error);
            color: white;
            font-size: 0.625rem;
            padding: 0.125rem 0.25rem;
            border-radius: 0.5rem;
            min-width: 1rem;
            text-align: center;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            background: var(--surface-hover);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .user-menu:hover {
            box-shadow: var(--shadow-md);
        }
        
        .user-avatar {
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .user-info h4 {
            font-size: 0.875rem;
            font-weight: 600;
            margin: 0;
        }
        
        .user-info p {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin: 0;
        }
        
        /* Breadcrumbs */
        .breadcrumbs {
            background: var(--surface);
            padding: 0.75rem 2rem;
            border-bottom: 1px solid var(--border);
        }
        
        .breadcrumb-list {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .breadcrumb-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .breadcrumb-item a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .breadcrumb-item a:hover {
            color: var(--primary);
        }
        
        .breadcrumb-item.active {
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .breadcrumb-separator {
            color: var(--text-muted);
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            
            .admin-sidebar.mobile-open {
                transform: translateX(0);
            }
            
            .admin-main {
                margin-left: 0;
            }
            
            .admin-top-bar {
                padding: 1rem;
            }
            
            .mobile-menu-toggle {
                display: block;
                background: none;
                border: none;
                color: var(--text-primary);
                font-size: 1.25rem;
                cursor: pointer;
            }
        }
        
        @media (min-width: 769px) {
            .mobile-menu-toggle {
                display: none;
            }
        }
        </style>';
    }
    
    /**
     * Get the sidebar navigation
     */
    private function getSidebar() {
        $currentPage = $this->currentPage;
        $userInfo = $this->userInfo;
        $stats = $this->stats;
        
        return '
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-header">
                <a href="admin_dashboard.php" class="admin-logo">
                    <i class="fas fa-vote-yea"></i>
                    <span>VoteAdmin</span>
                </a>
                <div class="admin-profile">
                    <h4>' . htmlspecialchars($userInfo['name'] ?? 'Admin User') . '</h4>
                    <p>' . htmlspecialchars($userInfo['role'] ?? 'Administrator') . '</p>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="admin_dashboard.php" class="nav-item ' . ($currentPage === 'dashboard' ? 'active' : '') . '">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="admin_elections.php" class="nav-item ' . ($currentPage === 'elections' ? 'active' : '') . '">
                        <i class="fas fa-poll"></i>
                        <span>Elections</span>
                    </a>
                    <a href="admin_candidates.php" class="nav-item ' . ($currentPage === 'candidates' ? 'active' : '') . '">
                        <i class="fas fa-user-tie"></i>
                        <span>Candidates</span>
                    </a>
                    <a href="admin_voters.php" class="nav-item ' . ($currentPage === 'voters' ? 'active' : '') . '">
                        <i class="fas fa-users"></i>
                        <span>Voters</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Reports & Analytics</div>
                    <a href="admin_results.php" class="nav-item ' . ($currentPage === 'results' ? 'active' : '') . '">
                        <i class="fas fa-chart-bar"></i>
                        <span>Results</span>
                    </a>
                    <a href="admin_analytics.php" class="nav-item ' . ($currentPage === 'analytics' ? 'active' : '') . '">
                        <i class="fas fa-chart-line"></i>
                        <span>Analytics</span>
                    </a>
                    <a href="admin_reports.php" class="nav-item ' . ($currentPage === 'reports' ? 'active' : '') . '">
                        <i class="fas fa-file-alt"></i>
                        <span>Reports</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">System</div>
                    <a href="admin_settings.php" class="nav-item ' . ($currentPage === 'settings' ? 'active' : '') . '">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                    <a href="admin_users.php" class="nav-item ' . ($currentPage === 'admin_users' ? 'active' : '') . '">
                        <i class="fas fa-user-shield"></i>
                        <span>Admin Users</span>
                    </a>
                    <a href="admin_logs.php" class="nav-item ' . ($currentPage === 'logs' ? 'active' : '') . '">
                        <i class="fas fa-list-alt"></i>
                        <span>System Logs</span>
                        ' . (isset($stats['system_alerts']) && $stats['system_alerts'] > 0 ? 
                        '<span class="nav-badge">' . $stats['system_alerts'] . '</span>' : '') . '
                    </a>
                    <a href="logout.php" class="nav-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </nav>
        </aside>';
    }
    
    /**
     * Get the top bar
     */
    private function getTopBar() {
        $pageTitle = $this->getPageTitle();
        $userInfo = $this->userInfo;
        
        return '
        <div class="admin-top-bar">
            <div class="top-bar-left">
                <button class="mobile-menu-toggle" onclick="toggleMobileSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>' . $pageTitle . '</h1>
            </div>
            <div class="top-bar-actions">
                <button class="notification-icon" title="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </button>
                <div class="user-menu" onclick="toggleUserMenu()">
                    <div class="user-avatar">
                        ' . strtoupper(substr($userInfo['name'] ?? 'AU', 0, 2)) . '
                    </div>
                    <div class="user-info">
                        <h4>' . htmlspecialchars($userInfo['name'] ?? 'Admin User') . '</h4>
                        <p>' . htmlspecialchars($userInfo['role'] ?? 'Administrator') . '</p>
                    </div>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
        </div>';
    }
    
    /**
     * Get breadcrumbs navigation
     */
    private function getBreadcrumbs() {
        $breadcrumbs = $this->getBreadcrumbItems();
        
        $breadcrumbHtml = '<div class="breadcrumbs"><ol class="breadcrumb-list">';
        
        foreach ($breadcrumbs as $index => $item) {
            $isLast = $index === count($breadcrumbs) - 1;
            
            if ($isLast) {
                $breadcrumbHtml .= '<li class="breadcrumb-item active">' . $item['title'] . '</li>';
            } else {
                $breadcrumbHtml .= '<li class="breadcrumb-item">
                    <a href="' . $item['url'] . '">' . $item['title'] . '</a>
                    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
                </li>';
            }
        }
        
        $breadcrumbHtml .= '</ol></div>';
        
        return $breadcrumbHtml;
    }
    
    /**
     * Get page title based on current page
     */
    private function getPageTitle() {
        $titles = [
            'dashboard' => 'Dashboard Overview',
            'elections' => 'Elections Management',
            'candidates' => 'Candidates Management',
            'voters' => 'Voters Management',
            'results' => 'Election Results',
            'analytics' => 'Analytics Dashboard',
            'reports' => 'Reports Center',
            'settings' => 'System Settings',
            'admin_users' => 'Admin Users',
            'logs' => 'System Logs'
        ];
        
        return $titles[$this->currentPage] ?? 'Admin Panel';
    }
    
    /**
     * Get breadcrumb items based on current page
     */
    private function getBreadcrumbItems() {
        $breadcrumbs = [
            ['title' => 'Home', 'url' => 'admin_dashboard.php']
        ];
        
        switch ($this->currentPage) {
            case 'dashboard':
                $breadcrumbs[] = ['title' => 'Dashboard', 'url' => ''];
                break;
            case 'elections':
                $breadcrumbs[] = ['title' => 'Elections', 'url' => ''];
                break;
            case 'candidates':
                $breadcrumbs[] = ['title' => 'Candidates', 'url' => ''];
                break;
            case 'voters':
                $breadcrumbs[] = ['title' => 'Voters', 'url' => ''];
                break;
            case 'results':
                $breadcrumbs[] = ['title' => 'Reports & Analytics', 'url' => '#'];
                $breadcrumbs[] = ['title' => 'Results', 'url' => ''];
                break;
            case 'analytics':
                $breadcrumbs[] = ['title' => 'Reports & Analytics', 'url' => '#'];
                $breadcrumbs[] = ['title' => 'Analytics', 'url' => ''];
                break;
            case 'reports':
                $breadcrumbs[] = ['title' => 'Reports & Analytics', 'url' => '#'];
                $breadcrumbs[] = ['title' => 'Reports', 'url' => ''];
                break;
            case 'settings':
                $breadcrumbs[] = ['title' => 'System', 'url' => '#'];
                $breadcrumbs[] = ['title' => 'Settings', 'url' => ''];
                break;
            case 'admin_users':
                $breadcrumbs[] = ['title' => 'System', 'url' => '#'];
                $breadcrumbs[] = ['title' => 'Admin Users', 'url' => ''];
                break;
            case 'logs':
                $breadcrumbs[] = ['title' => 'System', 'url' => '#'];
                $breadcrumbs[] = ['title' => 'System Logs', 'url' => ''];
                break;
        }
        
        return $breadcrumbs;
    }
    
    /**
     * Get JavaScript for mobile functionality
     */
    public function getJavaScript() {
        return '
        <script>
        function toggleMobileSidebar() {
            const sidebar = document.getElementById("adminSidebar");
            sidebar.classList.toggle("mobile-open");
        }
        
        function toggleUserMenu() {
            // Add user menu dropdown functionality here
            console.log("User menu toggled");
        }
        
        // Close mobile sidebar when clicking outside
        document.addEventListener("click", function(event) {
            const sidebar = document.getElementById("adminSidebar");
            const toggleButton = document.querySelector(".mobile-menu-toggle");
            
            if (!sidebar.contains(event.target) && !toggleButton.contains(event.target)) {
                sidebar.classList.remove("mobile-open");
            }
        });
        
        // Handle active states for navigation
        document.addEventListener("DOMContentLoaded", function() {
            const currentPage = "' . $this->currentPage . '";
            const navItems = document.querySelectorAll(".nav-item");
            
            navItems.forEach(item => {
                if (item.getAttribute("href").includes(currentPage)) {
                    item.classList.add("active");
                }
            });
        });
        </script>';
    }
}

/**
 * Helper function to render navigation
 */
function renderAdminNavigation($currentPage = '', $userInfo = [], $stats = []) {
    $nav = new AdminNavigationComponent($currentPage, $userInfo, $stats);
    $nav->render();
    return $nav->getJavaScript();
}

/**
 * Helper function to start admin layout
 */
function startAdminLayout() {
    echo '<div class="admin-layout">';
}

/**
 * Helper function to start main content area
 */
function startAdminMain() {
    echo '<main class="admin-main">';
}

/**
 * Helper function to end admin layout
 */
function endAdminLayout() {
    echo '</main></div>';
}
?>