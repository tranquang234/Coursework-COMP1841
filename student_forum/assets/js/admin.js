// Admin Dashboard JavaScript

// Global variable to store charts
let charts = {};

// Show confirmation modal (if not already defined from main.js)
if (typeof showConfirm === 'undefined') {
    window.showConfirm = function showConfirm(message, title = 'Confirm') {
        return new Promise((resolve) => {
            const modal = document.getElementById('confirmModal');
            const modalTitle = document.getElementById('confirmModalTitle');
            const modalMessage = document.getElementById('confirmModalMessage');
            const confirmBtn = document.getElementById('confirmModalConfirm');
            const cancelBtn = document.getElementById('confirmModalCancel');

            if (!modal || !modalTitle || !modalMessage || !confirmBtn || !cancelBtn) {
                // Fallback to confirm if modal not found
                resolve(confirm(message));
                return;
            }

            modalTitle.textContent = title;
            modalMessage.textContent = message;
            window.confirmModalResolve = resolve;

            // Remove old event listeners
            const newConfirmBtn = confirmBtn.cloneNode(true);
            const newCancelBtn = cancelBtn.cloneNode(true);
            confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
            cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);

            // Add new event listeners
            newConfirmBtn.addEventListener('click', () => {
                modal.classList.remove('show');
                if (window.confirmModalResolve) {
                    window.confirmModalResolve(true);
                    window.confirmModalResolve = null;
                }
            });

            newCancelBtn.addEventListener('click', () => {
                modal.classList.remove('show');
                if (window.confirmModalResolve) {
                    window.confirmModalResolve(false);
                    window.confirmModalResolve = null;
                }
            });

            // Close when clicking outside modal
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.remove('show');
                    if (window.confirmModalResolve) {
                        window.confirmModalResolve(false);
                        window.confirmModalResolve = null;
                    }
                }
            });

            modal.classList.add('show');
        });
    };
}

// Tab navigation
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.admin-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all menu items
    document.querySelectorAll('.admin-menu a').forEach(link => {
        link.classList.remove('active');
    });
    
    // Show selected tab
    const tab = document.getElementById(`tab-${tabName}`);
    if (tab) {
        tab.classList.add('active');
    }
    
    // Add active class to menu item
    const menuItem = document.getElementById(`menu-${tabName}`);
    if (menuItem) {
        menuItem.classList.add('active');
    }
    
    // Load data for tab
    if (tabName === 'dashboard') {
        loadAdminStats();
        loadCharts();
    } else if (tabName === 'users') {
        loadUsers();
    } else if (tabName === 'questions') {
        loadQuestions(1);
    } else if (tabName === 'modules') {
        loadModules();
    }
}

// Load overall statistics
async function loadAdminStats() {
    try {
        const response = await fetch('api/admin/stats.php');
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('total-users').textContent = data.stats.total_users || 0;
            document.getElementById('total-questions').textContent = data.stats.total_questions || 0;
            document.getElementById('total-answers').textContent = data.stats.total_answers || 0;
            document.getElementById('total-comments').textContent = data.stats.total_comments || 0;
        }
    } catch (error) {
        console.error('Error loading admin stats:', error);
    }
}

// Load and draw charts
async function loadCharts() {
    try {
        const response = await fetch('api/admin/charts.php');
        const data = await response.json();
        
        if (data.success) {
            drawUsersRoleChart(data.data.users_by_role);
            drawUsersMonthChart(data.data.users_by_month);
            drawQuestionsMonthChart(data.data.questions_by_month);
            drawTopQuestionsChart(data.data.top_questions);
            drawTopUsersChart(data.data.top_users);
        }
    } catch (error) {
        console.error('Error loading charts:', error);
    }
}

// Draw user role chart (Pie Chart)
function drawUsersRoleChart(usersByRole) {
    const ctx = document.getElementById('usersRoleChart');
    if (!ctx) return;
    
    // Destroy chart if it already exists
    if (charts.usersRoleChart) {
        charts.usersRoleChart.destroy();
    }
    
    const labels = [];
    const data = [];
    const colors = [];
    
    const roleNames = {
        'student': 'Student',
        'teacher': 'Teacher',
        'admin': 'Administrator'
    };
    
    const roleColors = {
        'student': '#3498db',
        'teacher': '#f39c12',
        'admin': '#e74c3c'
    };
    
    Object.keys(usersByRole).forEach(role => {
        labels.push(roleNames[role] || role);
        data.push(usersByRole[role]);
        colors.push(roleColors[role] || '#95a5a6');
    });
    
    charts.usersRoleChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors,
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// Draw user registration by month chart (Line Chart)
function drawUsersMonthChart(usersByMonth) {
    const ctx = document.getElementById('usersMonthChart');
    if (!ctx) return;
    
    if (charts.usersMonthChart) {
        charts.usersMonthChart.destroy();
    }
    
    const labels = usersByMonth.map(item => {
        const [year, month] = item.month.split('-');
        return `${month}/${year}`;
    });
    const data = usersByMonth.map(item => item.count);
    
    charts.usersMonthChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Number of registered users',
                data: data,
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// Draw questions by month chart (Line Chart)
function drawQuestionsMonthChart(questionsByMonth) {
    const ctx = document.getElementById('questionsMonthChart');
    if (!ctx) return;
    
    if (charts.questionsMonthChart) {
        charts.questionsMonthChart.destroy();
    }
    
    const labels = questionsByMonth.map(item => {
        const [year, month] = item.month.split('-');
        return `${month}/${year}`;
    });
    const data = questionsByMonth.map(item => item.count);
    
    charts.questionsMonthChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Number of questions',
                data: data,
                borderColor: '#27ae60',
                backgroundColor: 'rgba(39, 174, 96, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// Draw top 5 questions chart (Bar Chart)
function drawTopQuestionsChart(topQuestions) {
    const ctx = document.getElementById('topQuestionsChart');
    if (!ctx) return;
    
    if (charts.topQuestionsChart) {
        charts.topQuestionsChart.destroy();
    }
    
    const labels = topQuestions.map(q => {
        const title = q.title.length > 30 ? q.title.substring(0, 30) + '...' : q.title;
        return title;
    });
    const data = topQuestions.map(q => q.answer_count);
    
    charts.topQuestionsChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Number of answers',
                data: data,
                backgroundColor: '#9b59b6',
                borderColor: '#8e44ad',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// Draw top 5 users chart (Bar Chart)
function drawTopUsersChart(topUsers) {
    const ctx = document.getElementById('topUsersChart');
    if (!ctx) return;
    
    if (charts.topUsersChart) {
        charts.topUsersChart.destroy();
    }
    
    const labels = topUsers.map(u => u.full_name || u.username);
    const data = topUsers.map(u => u.total);
    
    charts.topUsersChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total (Questions + Answers)',
                data: data,
                backgroundColor: '#e67e22',
                borderColor: '#d35400',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// Load user list
async function loadUsers() {
    const usersListDiv = document.getElementById('users-list');
    if (!usersListDiv) return;
    
    usersListDiv.innerHTML = '<p>Loading user list...</p>';
    
    try {
        const response = await fetch('api/admin/users/list.php');
        const data = await response.json();
        
        if (data.success && data.users) {
            displayUsers(data.users);
        } else {
            usersListDiv.innerHTML = '<div class="alert alert-danger">Unable to load user list</div>';
        }
    } catch (error) {
        console.error('Error loading users:', error);
        usersListDiv.innerHTML = '<div class="alert alert-danger">An error occurred while loading user list</div>';
    }
}

// Display user list
function displayUsers(users) {
    const usersListDiv = document.getElementById('users-list');
    if (!usersListDiv) return;
    
    if (users.length === 0) {
        usersListDiv.innerHTML = '<div class="alert alert-info">No users yet</div>';
        return;
    }
    
    let html = `
        <table class="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Full Name</th>
                    <th>Role</th>
                    <th>Statistics</th>
                    <th>Created Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    users.forEach(user => {
        html += `
            <tr id="user-row-${user.user_id}">
                <td>${user.user_id}</td>
                <td>${escapeHtml(user.username)}</td>
                <td>${escapeHtml(user.email)}</td>
                <td>${escapeHtml(user.full_name || 'Not set')}</td>
                <td>
                    <select class="select-role" id="role-${user.user_id}" onchange="updateRole(${user.user_id}, this.value)">
                        <option value="student" ${user.role === 'student' ? 'selected' : ''}>Student</option>
                        <option value="teacher" ${user.role === 'teacher' ? 'selected' : ''}>Teacher</option>
                        <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Administrator</option>
                    </select>
                </td>
                <td>
                    <small>
                        <i class="fas fa-question-circle"></i> ${user.questions_count} |
                        <i class="fas fa-comments"></i> ${user.answers_count} |
                        <i class="fas fa-comment-dots"></i> ${user.comments_count}
                    </small>
                </td>
                <td>${formatDate(user.created_at)}</td>
                <td>
                    <button class="btn btn-danger-sm btn-sm" onclick="deleteUser(${user.user_id})" title="Delete user">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += `
            </tbody>
        </table>
    `;
    
    usersListDiv.innerHTML = html;
}

// Load question list
let currentQuestionsPage = 1;
async function loadQuestions(page = 1) {
    currentQuestionsPage = page;
    const questionsListDiv = document.getElementById('questions-list');
    if (!questionsListDiv) return;
    
    questionsListDiv.innerHTML = '<p>Loading question list...</p>';
    
    try {
        const response = await fetch(`api/admin/questions/list.php?page=${page}&limit=20`);
        const data = await response.json();
        
        if (data.success && data.questions) {
            displayQuestions(data.questions, data.pagination);
        } else {
            questionsListDiv.innerHTML = '<div class="alert alert-danger">Unable to load question list</div>';
        }
    } catch (error) {
        console.error('Error loading questions:', error);
        questionsListDiv.innerHTML = '<div class="alert alert-danger">An error occurred while loading question list</div>';
    }
}

// Display question list
function displayQuestions(questions, pagination) {
    const questionsListDiv = document.getElementById('questions-list');
    if (!questionsListDiv) return;
    
    if (questions.length === 0) {
        questionsListDiv.innerHTML = '<div class="alert alert-info">No questions yet</div>';
        return;
    }
    
    let html = `
        <table class="questions-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Views</th>
                    <th>Answers</th>
                    <th>Likes</th>
                    <th>Created Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    questions.forEach(question => {
        const title = question.title.length > 50 ? question.title.substring(0, 50) + '...' : question.title;
        html += `
            <tr id="question-row-${question.question_id}">
                <td>${question.question_id}</td>
                <td>
                    <a href="question.php?id=${question.question_id}" target="_blank">
                        ${escapeHtml(title)}
                    </a>
                </td>
                <td>${escapeHtml(question.full_name || question.username)}</td>
                <td>${question.views}</td>
                <td>${question.answer_count}</td>
                <td>${question.like_count}</td>
                <td>${formatDate(question.created_at)}</td>
                <td>
                    <button class="btn btn-danger-sm btn-sm" onclick="deleteQuestion(${question.question_id})" title="Delete question">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += `
            </tbody>
        </table>
    `;
    
    questionsListDiv.innerHTML = html;
    
    // Display pagination
    displayQuestionsPagination(pagination);
}

// Display pagination for questions
function displayQuestionsPagination(pagination) {
    const paginationDiv = document.getElementById('questions-pagination');
    if (!paginationDiv) return;
    
    if (pagination.total_pages <= 1) {
        paginationDiv.innerHTML = '';
        return;
    }
    
    let html = '';
    
    // Previous button
    html += `<button onclick="loadQuestions(${currentQuestionsPage - 1})" ${currentQuestionsPage === 1 ? 'disabled' : ''}>
        <i class="fas fa-chevron-left"></i> Previous
    </button>`;
    
    // Page numbers
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === 1 || i === pagination.total_pages || (i >= currentQuestionsPage - 2 && i <= currentQuestionsPage + 2)) {
            html += `<button onclick="loadQuestions(${i})" class="${i === currentQuestionsPage ? 'active' : ''}">${i}</button>`;
        } else if (i === currentQuestionsPage - 3 || i === currentQuestionsPage + 3) {
            html += `<button disabled>...</button>`;
        }
    }
    
    // Next button
    html += `<button onclick="loadQuestions(${currentQuestionsPage + 1})" ${currentQuestionsPage === pagination.total_pages ? 'disabled' : ''}>
        Next <i class="fas fa-chevron-right"></i>
    </button>`;
    
    paginationDiv.innerHTML = html;
}

// Delete question
window.deleteQuestion = async function deleteQuestion(questionId) {
    const confirmed = typeof showConfirm === 'function'
        ? await showConfirm('Are you sure you want to delete this question? All related answers and comments will be permanently deleted!', 'Confirm Delete Question')
        : confirm('Are you sure you want to delete this question? All related answers and comments will be permanently deleted!');
    
    if (!confirmed) {
        return;
    }
    
    try {
        const response = await fetch(`api/questions/delete.php?id=${questionId}`, {
            method: 'DELETE'
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            let errorData;
            try {
                errorData = JSON.parse(errorText);
            } catch (e) {
                errorData = { message: errorText || `HTTP error! status: ${response.status}` };
            }
            throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Question deleted successfully', 'success');
            loadQuestions(currentQuestionsPage);
            loadAdminStats();
            loadCharts();
        } else {
            showNotification(data.message || 'An error occurred', 'error');
        }
    } catch (error) {
        console.error('Error deleting question:', error);
        showNotification('An error occurred while deleting question: ' + error.message, 'error');
    }
}

// Update user role
async function updateRole(userId, newRole) {
    const confirmed = typeof showConfirm === 'function'
        ? await showConfirm(`Are you sure you want to change this user's role to "${newRole}"?`, 'Confirm Change Role')
        : confirm(`Are you sure you want to change this user's role to "${newRole}"?`);
    
    if (!confirmed) {
        loadUsers();
        return;
    }
    
    try {
        const response = await fetch('api/admin/users/update-role.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                user_id: userId,
                role: newRole
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Role updated successfully', 'success');
            loadUsers();
            loadAdminStats();
            loadCharts();
        } else {
            showNotification(data.message || 'An error occurred', 'error');
            loadUsers();
        }
    } catch (error) {
        console.error('Error updating role:', error);
        showNotification('An error occurred while updating role', 'error');
        loadUsers();
    }
}

// Delete user
async function deleteUser(userId) {
    const confirmed = typeof showConfirm === 'function'
        ? await showConfirm('Are you sure you want to delete this user? All related data (questions, answers, comments) will be permanently deleted!', 'Confirm Delete User')
        : confirm('Are you sure you want to delete this user? All related data (questions, answers, comments) will be permanently deleted!');
    
    if (!confirmed) {
        return;
    }
    
    try {
        const response = await fetch('api/admin/users/delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                user_id: userId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('User deleted successfully', 'success');
            loadUsers();
            loadAdminStats();
            loadCharts();
        } else {
            showNotification(data.message || 'An error occurred', 'error');
        }
    } catch (error) {
        console.error('Error deleting user:', error);
        showNotification('An error occurred while deleting user', 'error');
    }
}

// Show notification (fallback if showToast not available)
function showNotification(message, type = 'info') {
    if (typeof showToast === 'function') {
        showToast(message, type);
        return;
    }
    
    // Fallback to old notification if showToast not available
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'}`;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.minWidth = '300px';
    notification.style.padding = '15px';
    notification.style.borderRadius = '4px';
    notification.style.boxShadow = '0 2px 8px rgba(0,0,0,0.2)';
    notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i> ${message}`;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// ==================== MODULES MANAGEMENT ====================

// Load module list
async function loadModules() {
    const modulesListDiv = document.getElementById('modules-list');
    if (!modulesListDiv) {
        console.error('modules-list element not found');
        return;
    }
    
    modulesListDiv.innerHTML = '<p style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Loading module list...</p>';
    
    try {
        const response = await fetch('api/admin/modules/list.php', {
            method: 'GET',
            cache: 'no-cache',
            headers: {
                'Cache-Control': 'no-cache'
            }
        });
        
        console.log('Response status:', response.status, response.statusText);
        
        if (!response.ok) {
            // Server returned invalid data
            const errorText = await response.text();
            console.error('Error response text:', errorText);
            
            let errorData;
            try {
                errorData = JSON.parse(errorText);
            } catch (e) {
                errorData = { message: errorText || `HTTP error! status: ${response.status}` };
            }
            
            throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
        }
        
        const responseText = await response.text();
        console.log('Response text:', responseText);
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text:', responseText);
            throw new Error('Server returned invalid data: ' + responseText.substring(0, 200));
        }
        
        console.log('Modules API response:', data);
        
        if (data.success) {
            const modules = data.modules || [];
            displayModules(modules);
            
            // Show a notification if there is a message from the server (e.g. a new table is created)
            if (data.message) {
                showNotification(data.message, 'info');
            }
        } else {
            const errorMsg = data.message || 'Unable to load module list';
            modulesListDiv.innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ${escapeHtml(errorMsg)}</div>`;
            console.error('Error loading modules:', data);
        }
    } catch (error) {
        console.error('Error loading modules:', error);
        modulesListDiv.innerHTML = `<div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> An error occurred while loading module list: ${escapeHtml(error.message)}. 
            <br><small>Please check console for more details.</small>
        </div>`;
        showNotification('Unable to load module list', 'error');
    }
}

// Display module list
function displayModules(modules) {
    const modulesListDiv = document.getElementById('modules-list');
    if (!modulesListDiv) return;
    
    if (modules.length === 0) {
        modulesListDiv.innerHTML = '<div class="alert alert-info">No modules yet. Please add a new module!</div>';
        return;
    }
    
    let html = `
        <div style="overflow-x: auto;">
            <table class="users-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th style="min-width: 200px;">Module Name</th>
                        <th style="min-width: 120px;">Module Code</th>
                        <th>Description</th>
                        <th style="width: 150px;">Created Date</th>
                        <th style="width: 150px;">Last Updated</th>
                        <th style="width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    modules.forEach(module => {
        // Limit description display length
        const descriptionDisplay = module.description 
            ? (module.description.length > 100 
                ? module.description.substring(0, 100) + '...' 
                : module.description)
            : 'No description';
        
        // Escape data for display
        const moduleNameDisplay = escapeHtml(module.module_name || '');
        const moduleCodeDisplay = escapeHtml(module.module_code || '');
        const descriptionDisplayEscaped = escapeHtml(descriptionDisplay);
        const descriptionFullEscaped = escapeHtml(module.description || '');
        
        // Store original data in data attributes (escaped for HTML attribute)
        const moduleNameAttr = (module.module_name || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        const moduleCodeAttr = (module.module_code || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        const descriptionAttr = (module.description || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        
        html += `
            <tr id="module-row-${module.module_id}">
                <td>${module.module_id}</td>
                <td><strong>${moduleNameDisplay}</strong></td>
                <td><code style="background: #f8f9fa; padding: 4px 8px; border-radius: 4px;">${moduleCodeDisplay || 'Not set'}</code></td>
                <td title="${descriptionFullEscaped}">${descriptionDisplayEscaped}</td>
                <td><small>${formatDate(module.created_at)}</small></td>
                <td><small>${formatDate(module.updated_at || module.created_at)}</small></td>
                <td>
                    <button class="btn btn-secondary-sm btn-sm" 
                            onclick="editModuleFromRow(${module.module_id})" 
                            data-module-id="${module.module_id}"
                            data-module-name="${moduleNameAttr}"
                            data-module-code="${moduleCodeAttr}"
                            data-module-description="${descriptionAttr}"
                            title="Edit module">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-danger-sm btn-sm" onclick="deleteModule(${module.module_id})" title="Delete module">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
        <div style="margin-top: 15px; color: #666; font-size: 14px;">
            Total: <strong>${modules.length}</strong> modules
        </div>
    `;
    
    modulesListDiv.innerHTML = html;
}

// Show create module modal
window.showCreateModuleModal = function showCreateModuleModal() {
    document.getElementById('moduleModalTitle').innerHTML = '<i class="fas fa-book"></i> Add New Module';
    document.getElementById('moduleForm').reset();
    document.getElementById('moduleId').value = '';
    document.getElementById('moduleModal').style.display = 'block';
}

// Show edit module modal from data attributes (safer)
window.editModuleFromRow = function editModuleFromRow(moduleId) {
    const button = document.querySelector(`button[data-module-id="${moduleId}"]`);
    if (!button) {
        console.error('Button with module_id not found:', moduleId);
        return;
    }
    
    const moduleName = button.getAttribute('data-module-name') || '';
    const moduleCode = button.getAttribute('data-module-code') || '';
    const description = button.getAttribute('data-module-description') || '';
    
    editModule(moduleId, moduleName, moduleCode, description);
}

// Show edit module modal
window.editModule = function editModule(moduleId, moduleName, moduleCode, description) {
    const modal = document.getElementById('moduleModal');
    const modalTitle = document.getElementById('moduleModalTitle');
    const form = document.getElementById('moduleForm');
    
    if (!modal || !modalTitle || !form) {
        console.error('Modal elements not found');
        return;
    }
    
    modalTitle.innerHTML = '<i class="fas fa-edit"></i> Edit Module';
    document.getElementById('moduleId').value = moduleId;
    document.getElementById('moduleName').value = moduleName || '';
    document.getElementById('moduleCode').value = moduleCode || '';
    document.getElementById('moduleDescription').value = description || '';
    modal.style.display = 'block';
}

// Close module modal
window.closeModuleModal = function closeModuleModal() {
    document.getElementById('moduleModal').style.display = 'none';
    document.getElementById('moduleForm').reset();
    document.getElementById('moduleId').value = '';
}

// Handle module form submit
document.addEventListener('DOMContentLoaded', function() {
    const moduleForm = document.getElementById('moduleForm');
    if (moduleForm) {
        moduleForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const moduleId = document.getElementById('moduleId').value;
            const moduleName = document.getElementById('moduleName').value.trim();
            const moduleCode = document.getElementById('moduleCode').value.trim();
            const description = document.getElementById('moduleDescription').value.trim();
            
            if (!moduleName) {
                showNotification('Please enter module name', 'error');
                return;
            }
            
            try {
                let response;
                if (moduleId) {
                    // Update
                    response = await fetch('api/admin/modules/update.php', {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            module_id: parseInt(moduleId),
                            module_name: moduleName,
                            module_code: moduleCode,
                            description: description
                        })
                    });
                } else {
                    // Create new
                    response = await fetch('api/admin/modules/create.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            module_name: moduleName,
                            module_code: moduleCode,
                            description: description
                        })
                    });
                }
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification(data.message || (moduleId ? 'Module updated successfully' : 'Module added successfully'), 'success');
                    closeModuleModal();
                    loadModules();
                } else {
                    showNotification(data.message || 'An error occurred', 'error');
                }
            } catch (error) {
                console.error('Error saving module:', error);
                showNotification('An error occurred while saving module', 'error');
            }
        });
    }
    
    // Close modal when clicking outside
    const moduleModal = document.getElementById('moduleModal');
    if (moduleModal) {
        window.onclick = function(event) {
            if (event.target === moduleModal) {
                closeModuleModal();
            }
        }
    }
});

// Delete module
window.deleteModule = async function deleteModule(moduleId) {
    const confirmed = typeof showConfirm === 'function'
        ? await showConfirm('Are you sure you want to delete this module? This action cannot be undone!', 'Confirm Delete Module')
        : confirm('Are you sure you want to delete this module? This action cannot be undone!');
    
    if (!confirmed) {
        return;
    }
    
    try {
        const response = await fetch('api/admin/modules/delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                module_id: moduleId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Module deleted successfully', 'success');
            loadModules();
        } else {
            showNotification(data.message || 'An error occurred', 'error');
        }
    } catch (error) {
        console.error('Error deleting module:', error);
        showNotification('An error occurred while deleting module', 'error');
    }
}

// Load data when page is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Load dashboard mặc định
    loadAdminStats();
    loadCharts();
});
