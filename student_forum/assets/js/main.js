let currentPage = 1;
const limit = 10;

console.log('Variables initialized:', { currentPage, limit });

// Toast Notification System
window.showToast = function showToast(message, type = 'info', title = null) {
    // Create container if not exists
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    // Icon by type
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        info: 'fa-info-circle',
        warning: 'fa-exclamation-triangle'
    };
    
    // Default title by type
    const defaultTitles = {
        success: 'Success',
        error: 'Error',
        info: 'Notification',
        warning: 'Warning'
    };
    
    const displayTitle = title || defaultTitles[type] || 'Notification';
    const icon = icons[type] || icons.info;
    
    toast.innerHTML = `
        <i class="fas ${icon} toast-icon"></i>
        <div class="toast-content">
            <div class="toast-title">${escapeHtml(displayTitle)}</div>
            <div class="toast-message">${escapeHtml(message)}</div>
        </div>
        <button class="toast-close" onclick="this.closest('.toast').remove()">
            <i class="fas fa-times"></i>
        </button>
        <div class="toast-progress"></div>
    `;
    
    container.appendChild(toast);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        toast.classList.add('hiding');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 300);
    }, 5000);
    
    return toast;
};

// Helper function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Show confirmation modal
if (typeof window.showConfirm === 'undefined') {
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

            // Remove old event listeners by cloning and replacing
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
            const handleOutsideClick = (e) => {
                if (e.target === modal) {
                    modal.classList.remove('show');
                    modal.removeEventListener('click', handleOutsideClick);
                    if (window.confirmModalResolve) {
                        window.confirmModalResolve(false);
                        window.confirmModalResolve = null;
                    }
                }
            };
            modal.addEventListener('click', handleOutsideClick);

            modal.classList.add('show');
        });
    };
}

// Update statistics in sidebar
window.updateStats = async function updateStats() {
    try {
        const response = await fetch('api/stats/get.php');
        const data = await response.json();
        
        if (data.success && data.stats) {
            const questionsEl = document.getElementById('stat-questions');
            const answersEl = document.getElementById('stat-answers');
            const usersEl = document.getElementById('stat-users');
            
            if (questionsEl) {
                questionsEl.textContent = new Intl.NumberFormat('vi-VN').format(data.stats.total_questions);
            }
            if (answersEl) {
                answersEl.textContent = new Intl.NumberFormat('vi-VN').format(data.stats.total_answers);
            }
            if (usersEl) {
                usersEl.textContent = new Intl.NumberFormat('vi-VN').format(data.stats.total_users);
            }
        }
    } catch (error) {
        console.error('Error updating stats:', error);
    }
};

// Load question list
window.loadQuestions = async function loadQuestions(page = 1) {
    currentPage = page;
    const questionsList = document.getElementById('questions-list');
    if (!questionsList) {
        console.warn('Element questions-list does not exist on this page');
        return;
    }
    questionsList.innerHTML = '<div class="loading">Loading...</div>';

    try {
        const response = await fetch(`api/questions/list.php?page=${page}&limit=${limit}`);
        const data = await response.json();

        if (data.success) {
            displayQuestions(data.questions);
            displayPagination(data.pagination);
        } else {
            questionsList.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Unable to load question list</div>';
        }
    } catch (error) {
        questionsList.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> An error occurred while loading data</div>';
        console.error('Error:', error);
    }
}

// Display question list
function displayQuestions(questions) {
    const questionsList = document.getElementById('questions-list');
    
    if (questions.length === 0) {
        questionsList.innerHTML = '<div class="alert alert-info"> No questions yet. Be the first to post a question!</div>';
        return;
    }
 
    questionsList.innerHTML = questions.map(question => `
        <div class="question-card" id="question-card-${question.question_id}">
            <div class="question-header">
                <h3><a href="question.php?id=${question.question_id}"><i class="fas fa-question-circle"></i> ${escapeHtml(question.title)}</a></h3>
            </div>
            <div class="question-content">${escapeHtml(question.content.substring(0, 200))}${question.content.length > 200 ? '...' : ''}</div>
            ${question.images && question.images.trim() !== '' ? `
                <div class="question-image">
                    <img src="${escapeHtml(question.images)}" alt="Question image" onclick="window.open('${escapeHtml(question.images)}', '_blank')">
                </div>
            ` : ''}
            <div class="question-meta">
                <span class="meta-item"><i class="fas fa-user"></i> ${escapeHtml(question.full_name || question.username)}</span>
                ${question.module_name ? `<span class="meta-item"><i class="fas fa-book"></i> ${escapeHtml(question.module_name)}</span>` : ''}
                <span class="meta-item"><i class="fas fa-comments"></i> ${question.answer_count || 0} answers</span>
                <span class="meta-item" id="like-count-${question.question_id}"><i class="fas fa-thumbs-up"></i> ${question.like_count || 0} likes</span>
                <span class="meta-item"><i class="fas fa-eye"></i> ${question.views || 0} views</span>
                <span class="meta-item"><i class="fas fa-clock"></i> ${formatDate(question.created_at)}</span>
            </div>
            <div class="question-actions">
                ${typeof window.isLoggedIn !== 'undefined' && window.isLoggedIn ? `
                    <button class="btn ${question.is_liked ? 'btn-success' : 'btn-primary'} btn-sm" onclick="toggleLikeQuestion(${question.question_id})" id="like-btn-${question.question_id}">
                        <i class="fas fa-thumbs-up"></i> ${question.is_liked ? 'Liked' : 'Like'}
                    </button>
                ` : `
                    <a href="login.php" class="btn btn-primary btn-sm"><i class="fas fa-thumbs-up"></i> Like</a>
                `}
                <a href="question.php?id=${question.question_id}" class="btn btn-secondary btn-sm"><i class="fas fa-reply"></i> Answer</a>
                <a href="question.php?id=${question.question_id}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-right"></i> View Details</a>
            </div>
        </div>
    `).join('');
}

// Display pagination
function displayPagination(pagination) {
    const paginationDiv = document.getElementById('pagination');
    
    if (pagination.total_pages <= 1) {
        paginationDiv.innerHTML = '';
        return;
    }

    let html = '';
    
    // Previous button
    html += `<button onclick="loadQuestions(${pagination.current_page - 1})" ${pagination.current_page === 1 ? 'disabled' : ''}>Previous</button>`;
    
    // Page numbers
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === 1 || i === pagination.total_pages || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
            html += `<button class="${i === pagination.current_page ? 'active' : ''}" onclick="loadQuestions(${i})">${i}</button>`;
        } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
            html += `<button disabled>...</button>`;
        }
    }
    
    // Next button
    html += `<button onclick="loadQuestions(${pagination.current_page + 1})" ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}>Next</button>`;
    
    paginationDiv.innerHTML = html;
}

// Load module list
// Load all modules from API
async function loadModules() {
   
}

// Create question modal
window.showCreateQuestionModal = function showCreateQuestionModal() {
    document.getElementById('createQuestionModal').style.display = 'block';
    // Load module list when opening modal
}

window.closeCreateQuestionModal = function closeCreateQuestionModal() {
    document.getElementById('createQuestionModal').style.display = 'none';
    document.getElementById('createQuestionForm').reset();
    // Reset image preview
    document.getElementById('imagePreview').style.display = 'none';
    document.getElementById('previewImg').src = '';
    document.getElementById('questionImage').value = '';
}

// Remove image preview
window.removeImage = function removeImage() {
    const imageInput = document.getElementById('questionImage');
    const imagePreview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    
    if (imageInput) imageInput.value = '';
    if (imagePreview) imagePreview.style.display = 'none';
    if (previewImg) previewImg.src = '';
}

// Handle create question form submit
// window.submitCreateQuestion = async function submitCreateQuestion() {
    
// }

// Handle create question form - register in DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    // Load question list (only if element exists)
    const questionsList = document.getElementById('questions-list');
    if (questionsList) {
        loadQuestions(1);
    }
    
    // Handle image preview
    const questionImageInput = document.getElementById('questionImage');
    if (questionImageInput) {
        questionImageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Check file type
                if (!file.type.startsWith('image/')) {
                    alert('Please select an image file');
                    e.target.value = '';
                    return;
                }
                
                // Check file size (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('Image file must not exceed 5MB');
                    e.target.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(event) {
                    const previewImg = document.getElementById('previewImg');
                    const imagePreview = document.getElementById('imagePreview');
                    if (previewImg) previewImg.src = event.target.result;
                    if (imagePreview) imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }
});

// Logout
window.logout = async function logout() {
    const confirmed = typeof showConfirm === 'function'
        ? await showConfirm('Are you sure you want to logout?', 'Confirm Logout')
        : confirm('Are you sure you want to logout?');
    
    if (!confirmed) {
        return;
    }
    
    try {
        const response = await fetch('api/auth/logout.php', {
            method: 'POST'
        });
        
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Response is not JSON:', text);
            if (typeof showToast === 'function') {
                showToast('An error occurred while logging out. Please try again.', 'error');
            } else {
                alert('An error occurred while logging out. Please try again.');
            }
            return;
        }
        
        const data = await response.json();
        if (data.success) {
            if (typeof showToast === 'function') {
                showToast('Logout successful!', 'success');
            }
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 500);
        } else {
            if (typeof showToast === 'function') {
                showToast(data.message || 'Logout failed', 'error');
            } else {
                alert(data.message || 'Logout failed');
            }
        }
    } catch (error) {
        console.error('Error:', error);
        if (typeof showToast === 'function') {
            showToast('An error occurred while logging out. Please try again.', 'error');
        } else {
            alert('An error occurred while logging out. Please try again.');
        }
    }
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
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('createQuestionModal');
    if (event.target === modal) {
        closeCreateQuestionModal();
    }
}

// Toggle like for question (from homepage)
window.toggleLikeQuestion = async function toggleLikeQuestion(questionId) {
    // Check login
    try {
        const response = await fetch('api/likes/toggle.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ question_id: questionId, answer_id: null })
        });
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            if (text.includes('login') || text.includes('Đăng nhập')) {
                alert('Please login to like the question');
                window.location.href = 'login.php';
            }
            return;
        }
        
        const data = await response.json();
        
        if (data.success) {
            // Update like count in meta
            const likeCountElement = document.getElementById(`like-count-${questionId}`);
            if (likeCountElement) {
                likeCountElement.innerHTML = `<i class="fas fa-thumbs-up"></i> ${data.like_count} likes`;
            }
            
            // Update like button
            const likeBtn = document.getElementById(`like-btn-${questionId}`);
            if (likeBtn) {
                if (data.action === 'liked') {
                    likeBtn.innerHTML = '<i class="fas fa-thumbs-up"></i> Liked';
                    likeBtn.classList.remove('btn-primary');
                    likeBtn.classList.add('btn-success');
                } else {
                    likeBtn.innerHTML = '<i class="fas fa-thumbs-up"></i> Like';
                    likeBtn.classList.remove('btn-success');
                    likeBtn.classList.add('btn-primary');
                }
            }
        } else {
            if (data.message && data.message.includes('đăng nhập')) {
                alert('Please login to like the question');
                window.location.href = 'login.php';
            } else {
                alert(data.message || 'An error occurred');
            }
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while liking the question');
    }
}


