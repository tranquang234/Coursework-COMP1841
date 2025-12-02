<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Only admin can access
requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Student Forum</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        .admin-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        .admin-sidebar {
            width: 250px;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        .admin-sidebar h3 {
            margin: 0 0 20px 0;
            color: #2c3e50;
            font-size: 18px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        .admin-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .admin-menu li {
            margin-bottom: 10px;
        }
        .admin-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            color: #333;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s;
        }
        .admin-menu a:hover {
            background: #f8f9fa;
        }
        .admin-menu a.active {
            background: #3498db;
            color: white;
        }
        .admin-menu a i {
            width: 20px;
        }
        .admin-content {
            flex: 1;
        }
        .admin-tab {
            display: none;
        }
        .admin-tab.active {
            display: block;
        }
        .admin-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .admin-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .admin-card h3 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 18px;
        }
        .admin-card .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #007bff;
            margin: 10px 0;
        }
        .admin-card .stat-label {
            color: #666;
            font-size: 14px;
        }
        .admin-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .admin-section h2 {
            margin-top: 0;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .chart-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .chart-container h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #333;
        }
        .chart-wrapper {
            position: relative;
            height: 300px;
        }
        .users-table, .questions-table {
            width: 100%;
            border-collapse: collapse;
        }
        .users-table th, .users-table td,
        .questions-table th, .questions-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .users-table th, .questions-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .users-table tr:hover, .questions-table tr:hover {
            background: #f8f9fa;
        }
        .select-role {
            padding: 4px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
            margin: 2px;
        }
        .btn-danger-sm {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-danger-sm:hover {
            background: #c82333;
        }
        .btn-secondary-sm {
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            padding: 6px 12px;
            font-size: 14px;
            margin: 2px;
        }
        .btn-secondary-sm:hover {
            background: #5a6268;
        }
        .pagination {
            display: flex;
            gap: 5px;
            margin-top: 20px;
            justify-content: center;
        }
        .pagination button {
            padding: 8px 12px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 4px;
        }
        .pagination button:hover {
            background: #f8f9fa;
        }
        .pagination button.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <h1><i class="fas fa-graduation-cap"></i> Student Forum</h1>
            </div>
            <div class="nav-menu">
                <?php $user = getCurrentUser(); ?>
                <span class="user-info"><i class="fas fa-user-circle"></i> Hello, <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></span>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-home"></i> Home</a>
                <a href="profile.php" class="btn btn-secondary"><i class="fas fa-user"></i> Profile</a>
                <a href="#" onclick="logout()" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-cog"></i> Admin Panel</h1>
            <p>Manage users and forum content</p>
        </div>

        <div class="admin-container">
            <!-- Sidebar -->
            <aside class="admin-sidebar">
                <h3><i class="fas fa-bars"></i> Menu</h3>
                <ul class="admin-menu">
                    <li>
                        <a href="#" onclick="showTab('dashboard'); return false;" class="active" id="menu-dashboard">
                            <i class="fas fa-chart-line"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" onclick="showTab('users'); return false;" id="menu-users">
                            <i class="fas fa-users-cog"></i>
                            <span>User Management</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" onclick="showTab('questions'); return false;" id="menu-questions">
                            <i class="fas fa-question-circle"></i>
                            <span>Question Management</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" onclick="showTab('modules'); return false;" id="menu-modules">
                            <i class="fas fa-book"></i>
                            <span>Module Management</span>
                        </a>
                    </li>
                </ul>
            </aside>

            <!-- Content -->
            <div class="admin-content">
                <!-- Dashboard Tab -->
                <div id="tab-dashboard" class="admin-tab active">
                    <!-- Overall Statistics -->
                    <div class="admin-dashboard" id="admin-stats">
                        <div class="admin-card">
                            <h3><i class="fas fa-users"></i> Total Users</h3>
                            <div class="stat-value" id="total-users">-</div>
                            <div class="stat-label">Members</div>
                        </div>
                        <div class="admin-card">
                            <h3><i class="fas fa-question-circle"></i> Total Questions</h3>
                            <div class="stat-value" id="total-questions">-</div>
                            <div class="stat-label">Questions</div>
                        </div>
                        <div class="admin-card">
                            <h3><i class="fas fa-comments"></i> Total Answers</h3>
                            <div class="stat-value" id="total-answers">-</div>
                            <div class="stat-label">Answers</div>
                        </div>
                        <div class="admin-card">
                            <h3><i class="fas fa-comment-dots"></i> Total Comments</h3>
                            <div class="stat-value" id="total-comments">-</div>
                            <div class="stat-label">Comments</div>
                        </div>
                    </div>

                    <!-- Statistics Charts -->
                    <div class="chart-container">
                        <h3><i class="fas fa-chart-pie"></i> User Statistics by Role</h3>
                        <div class="chart-wrapper">
                            <canvas id="usersRoleChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-container">
                        <h3><i class="fas fa-chart-line"></i> User Registrations by Month</h3>
                        <div class="chart-wrapper">
                            <canvas id="usersMonthChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-container">
                        <h3><i class="fas fa-chart-line"></i> Questions Posted by Month</h3>
                        <div class="chart-wrapper">
                            <canvas id="questionsMonthChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-container">
                        <h3><i class="fas fa-chart-bar"></i> Top 5 Questions with Most Answers</h3>
                        <div class="chart-wrapper">
                            <canvas id="topQuestionsChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-container">
                        <h3><i class="fas fa-chart-bar"></i> Top 5 Most Active Users</h3>
                        <div class="chart-wrapper">
                            <canvas id="topUsersChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- User Management Tab -->
                <div id="tab-users" class="admin-tab">
                    <div class="admin-section">
                        <h2><i class="fas fa-users-cog"></i> User Management</h2>
                        <div id="users-list">
                            <p>Loading user list...</p>
                        </div>
                    </div>
                </div>

                <!-- Question Management Tab -->
                <div id="tab-questions" class="admin-tab">
                    <div class="admin-section">
                        <h2><i class="fas fa-question-circle"></i> Question Management</h2>
                        <div id="questions-list">
                            <p>Loading question list...</p>
                        </div>
                        <div id="questions-pagination" class="pagination"></div>
                    </div>
                </div>

                <!-- Module Management Tab -->
                <div id="tab-modules" class="admin-tab">
                    <div class="admin-section">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h2 style="margin: 0;"><i class="fas fa-book"></i> Module Management</h2>
                            <button class="btn btn-primary" onclick="showCreateModuleModal()">
                                <i class="fas fa-plus-circle"></i> Add New Module
                            </button>
                        </div>
                        <div id="modules-list">
                            <p>Loading module list...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Module Modal -->
    <div id="moduleModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <span class="close" onclick="closeModuleModal()">&times;</span>
            <h2 id="moduleModalTitle"><i class="fas fa-book"></i> Add New Module</h2>
            <form id="moduleForm">
                <input type="hidden" id="moduleId" name="module_id">
                <div class="form-group">
                    <label for="moduleName"><i class="fas fa-heading"></i> Module Name: <span style="color: red;">*</span></label>
                    <input type="text" id="moduleName" name="module_name" placeholder="Enter module name..." required>
                </div>
                <div class="form-group">
                    <label for="moduleCode"><i class="fas fa-code"></i> Module Code:</label>
                    <input type="text" id="moduleCode" name="module_code" placeholder="Enter module code (e.g., COMP-1801)">
                </div>
                <div class="form-group">
                    <label for="moduleDescription"><i class="fas fa-align-left"></i> Description:</label>
                    <textarea id="moduleDescription" name="description" rows="4" placeholder="Enter module description..."></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModuleModal()"><i class="fas fa-times"></i> Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="confirm-modal">
        <div class="confirm-modal-content">
            <div class="confirm-modal-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="confirm-modal-title" id="confirmModalTitle">Confirm</h3>
            <p class="confirm-modal-message" id="confirmModalMessage"></p>
            <div class="confirm-modal-actions">
                <button class="confirm-modal-btn confirm-modal-btn-cancel" id="confirmModalCancel">Cancel</button>
                <button class="confirm-modal-btn confirm-modal-btn-confirm" id="confirmModalConfirm">Confirm</button>
            </div>
        </div>
    </div>

    <script>
        // Pass login status to JavaScript
        window.isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
        
        // Ensure logout function is defined early to avoid errors
        if (typeof window.logout === 'undefined') {
            window.logout = async function() {
                const confirmed = typeof showConfirm === 'function' 
                    ? await showConfirm('Are you sure you want to logout?', 'Confirm Logout')
                    : confirm('Are you sure you want to logout?');
                
                if (!confirmed) {
                    return;
                }
                
                try {
                    const response = await fetch('api/auth/logout.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        window.location.href = 'login.php';
                    } else {
                        if (typeof showToast === 'function') {
                            showToast(data.message || 'An error occurred while logging out', 'error');
                        } else {
                            alert(data.message || 'An error occurred while logging out');
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                    window.location.href = 'login.php';
                }
            };
        }
    </script>
    <script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script src="assets/js/auth.js?v=<?php echo time(); ?>"></script>
    <script src="assets/js/admin.js?v=<?php echo time(); ?>"></script>
</body>
</html>
