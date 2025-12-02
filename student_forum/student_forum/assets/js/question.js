// Global variables
let currentUserId = null;
let questionOwnerId = null;

// Show confirmation modal
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

// Load question details
async function loadQuestionDetail() {
    const questionDetail = document.getElementById('question-detail');
    questionDetail.innerHTML = '<div class="loading">Loading...</div>';

    try {
        const response = await fetch(`api/questions/get.php?id=${questionId}`);
        const data = await response.json();

        if (data.success) {
            questionOwnerId = data.question.author_id;
            displayQuestionDetail(data.question);
            displayAnswers(data.question.answers);
        } else {
            questionDetail.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Question not found</div>';
        }
    } catch (error) {
        questionDetail.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> An error occurred while loading data</div>';
        console.error('Error:', error);
    }
}

// Display question details
function displayQuestionDetail(question) {
    const questionDetail = document.getElementById('question-detail');
    
    const isOwner = question.author_id === currentUserId;
    
    // Create module display section
    let moduleSection = '';
    if (question.module_name) {
        const moduleImage = question.module_image && question.module_image.trim() !== '' 
            ? escapeHtml(question.module_image) 
            : null;
        const moduleCode = question.module_code ? escapeHtml(question.module_code) : '';
        
        moduleSection = `
            <div class="module-info-card">
                ${moduleImage ? `
                    <div class="module-image">
                        <img src="${moduleImage}" alt="${escapeHtml(question.module_name)}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="module-icon-fallback" style="display: none;">
                            <i class="fas fa-book"></i>
                        </div>
                    </div>
                ` : `
                    <div class="module-icon">
                        <i class="fas fa-book"></i>
                    </div>
                `}
                <div class="module-details">
                    <h3 class="module-name">${escapeHtml(question.module_name)}</h3>
                    ${moduleCode ? `<span class="module-code">${moduleCode}</span>` : ''}
                </div>
            </div>
        `;
    }
    
    questionDetail.innerHTML = `
        ${moduleSection}
        <div class="question-detail">
            <h2><i class="fas fa-question-circle"></i> ${escapeHtml(question.title)}</h2>
            <div class="content">${escapeHtml(question.content).replace(/\n/g, '<br>')}</div>
            ${question.images && question.images.trim() !== '' ? `
                <div class="question-image">
                    <img src="${escapeHtml(question.images)}" alt="Question image" onclick="window.open('${escapeHtml(question.images)}', '_blank')">
                </div>
            ` : ''}
            <div class="question-meta">
                <span class="meta-item"><i class="fas fa-user"></i> ${escapeHtml(question.full_name || question.username)}</span>
                <span class="meta-item"><i class="fas fa-comments"></i> ${question.answer_count} answers</span>
                <span class="meta-item"><i class="fas fa-thumbs-up"></i> ${question.like_count} likes</span>
                <span class="meta-item"><i class="fas fa-eye"></i> ${question.views} views</span>
                <span class="meta-item"><i class="fas fa-clock"></i> ${formatDate(question.created_at)}</span>
            </div>
            <div class="question-actions">
                <button id="like-btn-question-${question.question_id}" class="btn ${question.is_liked ? 'btn-success' : 'btn-primary'}" onclick="toggleLike(${question.question_id}, null)">
                    <i class="fas fa-thumbs-up"></i> ${question.is_liked ? 'Liked' : 'Like'} (${question.like_count})
                </button>
                ${isOwner ? `
                    <button class="btn btn-secondary" onclick="editQuestion(${question.question_id})"><i class="fas fa-edit"></i> Edit</button>
                    <button class="btn btn-danger" onclick="deleteQuestion(${question.question_id})"><i class="fas fa-trash"></i> Delete</button>
                ` : ''}
            </div>
        </div>
    `;
}

// Display answer list
function displayAnswers(answers) {
    const answersSection = document.getElementById('answers-section');
    
    if (answers.length === 0) {
        answersSection.innerHTML = '<div class="alert alert-info"> No answers yet. Be the first to answer!</div>';
        return;
    }
    
    answersSection.innerHTML = `
        <h2><i class="fas fa-comments"></i> ${answers.length} Answers</h2>
        ${answers.map(answer => `
            <div class="answer-card ${answer.is_accepted ? 'accepted' : ''}" id="answer-${answer.answer_id}">
                ${answer.is_accepted ? '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Answer accepted</div>' : ''}
                <div class="content">${escapeHtml(answer.content).replace(/\n/g, '<br>')}</div>
                <div class="answer-meta">
                    <span class="meta-item"><i class="fas fa-user"></i> ${escapeHtml(answer.full_name || answer.username)}</span>
                    <span class="meta-item"><i class="fas fa-thumbs-up"></i> ${answer.like_count} likes</span>
                    <span class="meta-item"><i class="fas fa-comments"></i> ${answer.comment_count} comments</span>
                    <span class="meta-item"><i class="fas fa-clock"></i> ${formatDate(answer.created_at)}</span>
                </div>
                <div class="answer-actions">
                    <button id="like-btn-answer-${answer.answer_id}" class="btn ${answer.is_liked ? 'btn-success' : 'btn-primary'}" onclick="toggleLike(null, ${answer.answer_id})">
                        <i class="fas fa-thumbs-up"></i> ${answer.is_liked ? 'Liked' : 'Like'} (${answer.like_count})
                    </button>
                    ${answer.author_id === (window.currentUserId || null) ? `
                        <button class="btn btn-secondary" onclick="editAnswer(${answer.answer_id})"><i class="fas fa-edit"></i> Edit</button>
                        <button class="btn btn-danger" onclick="deleteAnswer(${answer.answer_id})"><i class="fas fa-trash"></i> Delete</button>
                    ` : ''}
                    ${window.currentUserId === window.questionOwnerId ? `
                        <button class="btn btn-success" onclick="acceptAnswer(${answer.answer_id})">
                            <i class="fas fa-check"></i> ${answer.is_accepted ? 'Accepted' : 'Accept'}
                        </button>
                    ` : ''}
                    <button class="btn btn-secondary" onclick="showComments(${answer.answer_id})">
                        <i class="fas fa-comments"></i> Comments (${answer.comment_count})
                    </button>
                </div>
                <div id="comments-${answer.answer_id}" class="comments-section" style="display: none;">
                    ${displayComments(answer.comments)}
                    <form onsubmit="addComment(event, ${answer.answer_id})">
                        <div class="form-group">
                            <textarea name="content" rows="2" placeholder="Write a comment..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send Comment</button>
                    </form>
                </div>
            </div>
        `).join('')}
    `;
}

// Display comments
function displayComments(comments) {
    if (comments.length === 0) {
        return '<p class="alert alert-info"> No comments yet</p>';
    }
    
    return comments.map(comment => `
        <div class="comment-card">
            <div class="comment-meta">
                <i class="fas fa-user"></i> <strong>${escapeHtml(comment.full_name || comment.username)}</strong> - <i class="fas fa-clock"></i> ${formatDate(comment.created_at)}
            </div>
            <div>${escapeHtml(comment.content)}</div>
        </div>
    `).join('');
}

// Handle answer form
document.getElementById('answerForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const content = document.getElementById('answerContent').value.trim();
    
    if (!content) {
        if (typeof showToast === 'function') {
            showToast('Please enter your answer', 'warning');
        } else {
            alert('Please enter your answer');
        }
        return;
    }
    
    try {
        const response = await fetch('api/answers/create.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ question_id: questionId, content })
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (typeof showToast === 'function') {
                showToast('Your answer has been submitted successfully!', 'success');
            } else {
                alert('Answer submitted successfully!');
            }
            document.getElementById('answerContent').value = '';
            loadQuestionDetail();
            updateStats(); // Update statistics
        } else {
            if (typeof showToast === 'function') {
                showToast(data.message || 'An error occurred while answering', 'error');
            } else {
                alert('Error: ' + data.message);
            }
        }
    } catch (error) {
        if (typeof showToast === 'function') {
            showToast('An error occurred while answering', 'error');
        } else {
            alert('An error occurred while answering');
        }
        console.error('Error:', error);
    }
});

// Toggle like
async function toggleLike(questionId, answerId) {
    try {
        const response = await fetch('api/likes/toggle.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ question_id: questionId, answer_id: answerId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update UI immediately
            const buttonId = questionId ? `like-btn-question-${questionId}` : `like-btn-answer-${answerId}`;
            const likeBtn = document.getElementById(buttonId);
            
            if (likeBtn) {
                if (data.action === 'liked') {
                    likeBtn.innerHTML = `<i class="fas fa-thumbs-up"></i> Liked (${data.like_count})`;
                    likeBtn.classList.remove('btn-primary');
                    likeBtn.classList.add('btn-success');
                } else {
                    likeBtn.innerHTML = `<i class="fas fa-thumbs-up"></i> Like (${data.like_count})`;
                    likeBtn.classList.remove('btn-success');
                    likeBtn.classList.add('btn-primary');
                }
            }
            
            // Update like count in meta
            if (questionId) {
                // Find meta item containing likes by searching for thumbs-up icon
                const metaItems = document.querySelectorAll('.question-meta .meta-item');
                metaItems.forEach(item => {
                    if (item.innerHTML.includes('fa-thumbs-up') && item.innerHTML.includes('likes')) {
                        item.innerHTML = `<i class="fas fa-thumbs-up"></i> ${data.like_count} likes`;
                    }
                });
            } else {
                // Update for answer
                const answerCard = document.getElementById(`answer-${answerId}`);
                if (answerCard) {
                    const answerMetaItems = answerCard.querySelectorAll('.answer-meta .meta-item');
                    answerMetaItems.forEach(item => {
                        if (item.innerHTML.includes('fa-thumbs-up') && item.innerHTML.includes('likes')) {
                            item.innerHTML = `<i class="fas fa-thumbs-up"></i> ${data.like_count} likes`;
                        }
                    });
                }
            }
        } else {
            if (typeof showToast === 'function') {
                showToast(data.message || 'An error occurred', 'error');
            } else {
                alert('Error: ' + data.message);
            }
        }
    } catch (error) {
        console.error('Error:', error);
        if (typeof showToast === 'function') {
            showToast('An error occurred while liking', 'error');
        } else {
            alert('An error occurred while liking');
        }
    }
}

// Accept answer
async function acceptAnswer(answerId) {
    try {
        const response = await fetch('api/answers/accept.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ answer_id: answerId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (typeof showToast === 'function') {
                showToast('Answer has been accepted!', 'success');
            }
            loadQuestionDetail();
        } else {
            if (typeof showToast === 'function') {
                showToast(data.message || 'An error occurred', 'error');
            } else {
                alert('Error: ' + data.message);
            }
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Delete question
async function deleteQuestion(questionId) {
    const confirmed = await showConfirm('Are you sure you want to delete this question?', 'Confirm Delete Question');
    if (!confirmed) {
        return;
    }
    
    try {
        const response = await fetch(`api/questions/delete.php?id=${questionId}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (typeof showToast === 'function') {
                showToast('Question deleted successfully!', 'success');
            } else {
                alert('Question deleted successfully!');
            }
            // Update statistics before redirecting
            updateStats();
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 1000);
        } else {
            if (typeof showToast === 'function') {
                showToast(data.message || 'An error occurred', 'error');
            } else {
                alert('Error: ' + data.message);
            }
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Delete answer
async function deleteAnswer(answerId) {
    const confirmed = await showConfirm('Are you sure you want to delete this answer?', 'Confirm Delete Answer');
    if (!confirmed) {
        return;
    }
    
    try {
        const response = await fetch(`api/answers/delete.php?id=${answerId}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (typeof showToast === 'function') {
                showToast('Answer deleted successfully!', 'success');
            } else {
                alert('Answer deleted successfully!');
            }
            loadQuestionDetail();
            updateStats(); // Update statistics
        } else {
            if (typeof showToast === 'function') {
                showToast(data.message || 'An error occurred', 'error');
            } else {
                alert('Error: ' + data.message);
            }
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Edit question
window.editQuestion = async function editQuestion(questionId) {
    // Get current question information
    try {
        const response = await fetch(`api/questions/get.php?id=${questionId}`);
        const data = await response.json();
        
        if (!data.success || !data.question) {
            if (typeof showToast === 'function') {
                showToast('Unable to load question information', 'error');
            } else {
                alert('Unable to load question information');
            }
            return;
        }
        
        const question = data.question;
        
        // Create edit form
        const title = prompt('Enter new title:', question.title);
        if (title === null) return;
        
        if (!title.trim()) {
            if (typeof showToast === 'function') {
                showToast('Title cannot be empty', 'warning');
            } else {
                alert('Title cannot be empty');
            }
            return;
        }
        
        // Use textarea for content
        const content = prompt('Enter new content (can be multiple lines):', question.content);
        if (content === null) return;
        
        if (!content.trim()) {
            if (typeof showToast === 'function') {
                showToast('Content cannot be empty', 'warning');
            } else {
                alert('Content cannot be empty');
            }
            return;
        }
        
        // Send update request
        const updateResponse = await fetch('api/questions/update.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                question_id: questionId,
                title: title.trim(),
                content: content.trim()
            })
        });
        
        const updateData = await updateResponse.json();
        
        if (updateData.success) {
            if (typeof showToast === 'function') {
                showToast('Question updated successfully!', 'success');
            } else {
                alert('Question updated successfully!');
            }
            loadQuestionDetail();
        } else {
            if (typeof showToast === 'function') {
                showToast(updateData.message || 'An error occurred', 'error');
            } else {
                alert('Error: ' + updateData.message);
            }
        }
    } catch (error) {
        console.error('Error:', error);
        if (typeof showToast === 'function') {
            showToast('An error occurred while editing question', 'error');
        } else {
            alert('An error occurred while editing question');
        }
    }
}

// Variable to store answerId being edited
let currentEditAnswerId = null;

// Open edit answer modal
window.editAnswer = function editAnswer(answerId) {
    // Find answer in DOM to get current content
    const answerCard = document.getElementById(`answer-${answerId}`);
    if (!answerCard) {
        if (typeof showToast === 'function') {
            showToast('Answer not found', 'error');
        } else {
            alert('Answer not found');
        }
        return;
    }
    
    const contentDiv = answerCard.querySelector('.content');
    if (!contentDiv) {
        if (typeof showToast === 'function') {
            showToast('Answer content not found', 'error');
        } else {
            alert('Answer content not found');
        }
        return;
    }
    
    // Get current content (remove <br> tags and convert to text)
    const currentContent = contentDiv.textContent || contentDiv.innerText || '';
    
    // Save answerId being edited
    currentEditAnswerId = answerId;
    
    // Show modal
    const modal = document.getElementById('editAnswerModal');
    const contentTextarea = document.getElementById('editAnswerContent');
    
    if (modal && contentTextarea) {
        contentTextarea.value = currentContent;
        modal.style.display = 'block';
        // Focus on textarea
        setTimeout(() => {
            contentTextarea.focus();
        }, 100);
    }
}

// Close edit answer modal
window.closeEditAnswerModal = function closeEditAnswerModal() {
    const modal = document.getElementById('editAnswerModal');
    const form = document.getElementById('editAnswerForm');
    
    if (modal) {
        modal.style.display = 'none';
    }
    
    if (form) {
        form.reset();
    }
    
    currentEditAnswerId = null;
}

// Submit answer update
window.submitEditAnswer = async function submitEditAnswer() {
    if (!currentEditAnswerId) {
        if (typeof showToast === 'function') {
            showToast('Answer to edit not found', 'error');
        } else {
            alert('Answer to edit not found');
        }
        return;
    }
    
    const contentTextarea = document.getElementById('editAnswerContent');
    if (!contentTextarea) {
        if (typeof showToast === 'function') {
            showToast('Content input field not found', 'error');
        } else {
            alert('Content input field not found');
        }
        return;
    }
    
    const newContent = contentTextarea.value.trim();
    
    if (!newContent) {
        if (typeof showToast === 'function') {
            showToast('Content cannot be empty', 'warning');
        } else {
            alert('Content cannot be empty');
        }
        contentTextarea.focus();
        return;
    }
    
    // Disable submit button
    const submitBtn = document.querySelector('#editAnswerForm button[onclick="submitEditAnswer()"]');
    const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    }
    
    try {
        // Send update request
        const response = await fetch('api/answers/update.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                answer_id: currentEditAnswerId,
                content: newContent
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (typeof showToast === 'function') {
                showToast('Answer updated successfully!', 'success');
            } else {
                alert('Answer updated successfully!');
            }
            closeEditAnswerModal();
            loadQuestionDetail();
        } else {
            if (typeof showToast === 'function') {
                showToast(data.message || 'An error occurred', 'error');
            } else {
                alert('Error: ' + data.message);
            }
        }
    } catch (error) {
        console.error('Error:', error);
        if (typeof showToast === 'function') {
            showToast('An error occurred while editing answer', 'error');
        } else {
            alert('An error occurred while editing answer');
        }
    } finally {
        // Re-enable submit button
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    }
}

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', () => {
    const editAnswerModal = document.getElementById('editAnswerModal');
    if (editAnswerModal) {
        editAnswerModal.addEventListener('click', (e) => {
            if (e.target === editAnswerModal) {
                closeEditAnswerModal();
        }
        });
}

    // Close modal when pressing ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const modal = document.getElementById('editAnswerModal');
            if (modal && modal.style.display === 'block') {
                closeEditAnswerModal();
            }
        }
    });
});

// Update statistics in sidebar (if not already defined from main.js)
if (typeof updateStats === 'undefined') {
    window.updateStats = async function updateStats() {
        try {
            const response = await fetch('api/stats/get.php');
            const data = await response.json();
            
            if (data.success && data.stats) {
                const questionsEl = document.getElementById('stat-questions');
                const answersEl = document.getElementById('stat-answers');
                const usersEl = document.getElementById('stat-users');
                
                if (questionsEl) {
                    questionsEl.textContent = new Intl.NumberFormat('en-US').format(data.stats.total_questions);
                }
                if (answersEl) {
                    answersEl.textContent = new Intl.NumberFormat('en-US').format(data.stats.total_answers);
                }
                if (usersEl) {
                    usersEl.textContent = new Intl.NumberFormat('en-US').format(data.stats.total_users);
                }
            }
        } catch (error) {
            console.error('Error updating stats:', error);
        }
    };
}

// Add comment
async function addComment(event, answerId) {
    event.preventDefault();
    
    const content = event.target.content.value.trim();
    
    if (!content) {
        if (typeof showToast === 'function') {
            showToast('Please enter a comment', 'warning');
        } else {
            alert('Please enter a comment');
        }
        return;
    }
    
    try {
        const response = await fetch('api/comments/create.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ answer_id: answerId, content })
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (typeof showToast === 'function') {
                showToast('Comment added successfully!', 'success');
            }
            event.target.reset();
            loadQuestionDetail();
            updateStats(); // Update statistics
        } else {
            if (typeof showToast === 'function') {
                showToast(data.message || 'An error occurred', 'error');
            } else {
                alert('Error: ' + data.message);
            }
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Show/hide comments
function showComments(answerId) {
    const commentsDiv = document.getElementById(`comments-${answerId}`);
    if (commentsDiv.style.display === 'none') {
        commentsDiv.style.display = 'block';
    } else {
        commentsDiv.style.display = 'none';
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

// Logout
async function logout() {
    const confirmed = await showConfirm('Are you sure you want to logout?', 'Confirm Logout');
    if (!confirmed) {
        return;
    }
    
    try {
        const response = await fetch('api/auth/logout.php', {
            method: 'POST'
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.location.href = 'index.php';
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Load question details when page is loaded
document.addEventListener('DOMContentLoaded', () => {
    loadQuestionDetail();
});

