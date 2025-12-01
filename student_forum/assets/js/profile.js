// Show confirmation modal (if not already defined from main.js or question.js)
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

// Load user statistics
async function loadUserStats() {
    const userStatsDiv = document.getElementById('user-stats');
    if (!userStatsDiv) {
        console.error('user-stats element not found');
        return;
    }
    
    userStatsDiv.innerHTML = '<div class="loading">Loading...</div>';

    try {
        const response = await fetch('api/users/stats.php');
        
        // Check status code
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Stats data:', data);

        if (data.success && data.stats) {
            displayUserStats(data.stats);
        } else {
            userStatsDiv.innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ${data.message || 'Unable to load statistics'}</div>`;
        }
    } catch (error) {
        console.error('Error loading stats:', error);
        userStatsDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> An error occurred while loading statistics. Please try again later.</div>';
    }
}

// Display user statistics
function displayUserStats(stats) {
    const userStatsDiv = document.getElementById('user-stats');
    
    userStatsDiv.innerHTML = `
        <div class="stats-grid">
            <div class="stat-item clickable-stat" onclick="showMyQuestions()" style="cursor: pointer;" title="Click to view question list">
                <div class="number"><i class="fas fa-question-circle"></i> ${stats.questions_count || 0}</div>
                <div class="label">Questions</div>
            </div>
            <div class="stat-item clickable-stat" onclick="showMyAnswers()" style="cursor: pointer;" title="Click to view answer list">
                <div class="number"><i class="fas fa-comments"></i> ${stats.answers_count || 0}</div>
                <div class="label">Answers</div>
            </div>
            <div class="stat-item">
                <div class="number"><i class="fas fa-comment"></i> ${stats.comments_count || 0}</div>
                <div class="label">Comments</div>
            </div>
            <div class="stat-item clickable-stat" onclick="showMyLikes()" style="cursor: pointer;" title="Click to view received likes">
                <div class="number"><i class="fas fa-thumbs-up"></i> ${stats.likes_received || 0}</div>
                <div class="label">Likes Received</div>
            </div>
            <div class="stat-item clickable-stat" onclick="showMyLikesGiven()" style="cursor: pointer;" title="Click to view and manage likes given">
                <div class="number"><i class="fas fa-heart"></i> ${stats.likes_given || 0}</div>
                <div class="label">Likes Given</div>
            </div>
        </div>
    `;
}

// Display my question list
let currentQuestionsPage = 1;
window.showMyQuestions = async function showMyQuestions(page = 1) {
    currentQuestionsPage = page;
    const modal = document.getElementById('listModal');
    const modalTitle = document.getElementById('listModalTitle');
    const modalContent = document.getElementById('listModalContent');
    const modalPagination = document.getElementById('listModalPagination');
    
    modalTitle.innerHTML = '<i class="fas fa-question-circle"></i> My Questions';
    modalContent.innerHTML = '<div class="loading">Loading...</div>';
    modal.style.display = 'block';
    
    try {
        const response = await fetch(`api/users/questions.php?page=${page}&limit=10`);
        const data = await response.json();
        
        if (data.success && data.questions) {
            if (data.questions.length === 0) {
                modalContent.innerHTML = '<div class="alert alert-info"> You have no questions yet.</div>';
                modalPagination.innerHTML = '';
            } else {
                let html = '<div class="questions-list">';
                data.questions.forEach(question => {
                    const createdDate = new Date(question.created_at).toLocaleString('en-US');
                    const hasImage = question.images && question.images.trim() !== '';
                    
                    html += `
                        <div class="question-item" style="padding: 15px; border-bottom: 1px solid #eee; margin-bottom: 15px; border-radius: 8px; background: #f9f9f9;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 15px;">
                                <div style="flex: 1;">
                                    <h3 style="margin: 0 0 10px 0;">
                                        <a href="question.php?id=${question.question_id}" style="color: #3498db; text-decoration: none; font-size: 18px;">
                                            ${escapeHtml(question.title)}
                                        </a>
                                    </h3>
                                    <div style="color: #666; font-size: 14px; margin-bottom: 10px; line-height: 1.6;">
                                        ${question.content ? escapeHtml(question.content.substring(0, 200)) + (question.content.length > 200 ? '...' : '') : ''}
                                    </div>
                                    ${hasImage ? `
                                        <div style="margin-bottom: 10px;">
                                            <img src="${escapeHtml(question.images)}" 
                                                 alt="Question image" 
                                                 style="max-width: 200px; max-height: 150px; border-radius: 6px; cursor: pointer; border: 1px solid #ddd;"
                                                 onclick="window.open('${escapeHtml(question.images)}', '_blank')"
                                                 onerror="this.style.display='none';">
                                        </div>
                                    ` : ''}
                                    <div style="display: flex; gap: 15px; font-size: 13px; color: #999; flex-wrap: wrap; margin-bottom: 10px;">
                                        ${question.module_name ? `<span><i class="fas fa-book"></i> ${escapeHtml(question.module_name)}</span>` : ''}
                                        <span><i class="fas fa-comments"></i> ${question.answer_count || 0} answers</span>
                                        <span><i class="fas fa-thumbs-up"></i> ${question.like_count || 0} likes</span>
                                        <span><i class="fas fa-eye"></i> ${question.views || 0} views</span>
                                        <span><i class="fas fa-clock"></i> ${createdDate}</span>
                                    </div>
                                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                                        <button class="btn btn-secondary btn-sm" onclick="editMyQuestion(${question.question_id})" style="padding: 6px 12px; font-size: 13px;">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="deleteMyQuestion(${question.question_id})" style="padding: 6px 12px; font-size: 13px;">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                modalContent.innerHTML = html;
                
                // Pagination
                if (data.pagination.total_pages > 1) {
                    let paginationHtml = '';
                    if (data.pagination.current_page > 1) {
                        paginationHtml += `<button onclick="showMyQuestions(${data.pagination.current_page - 1})" class="btn btn-secondary">Previous</button>`;
                    }
                    paginationHtml += `<span style="margin: 0 15px;">Page ${data.pagination.current_page} / ${data.pagination.total_pages}</span>`;
                    if (data.pagination.current_page < data.pagination.total_pages) {
                        paginationHtml += `<button onclick="showMyQuestions(${data.pagination.current_page + 1})" class="btn btn-secondary">Next</button>`;
                    }
                    modalPagination.innerHTML = paginationHtml;
                } else {
                    modalPagination.innerHTML = '';
                }
            }
        } else {
            modalContent.innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ${data.message || 'Unable to load question list'}</div>`;
        }
    } catch (error) {
        console.error('Error loading questions:', error);
        modalContent.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> An error occurred while loading question list.</div>';
    }
}

// Display my answer list
let currentAnswersPage = 1;
window.showMyAnswers = async function showMyAnswers(page = 1) {
    currentAnswersPage = page;
    const modal = document.getElementById('listModal');
    const modalTitle = document.getElementById('listModalTitle');
    const modalContent = document.getElementById('listModalContent');
    const modalPagination = document.getElementById('listModalPagination');
    
    modalTitle.innerHTML = '<i class="fas fa-comments"></i> My Answers';
    modalContent.innerHTML = '<div class="loading">Loading...</div>';
    modal.style.display = 'block';
    
    try {
        const response = await fetch(`api/users/answers.php?page=${page}&limit=10`);
        const data = await response.json();
        
        if (data.success && data.answers) {
            if (data.answers.length === 0) {
                modalContent.innerHTML = '<div class="alert alert-info">You have no answers yet.</div>';
                modalPagination.innerHTML = '';
            } else {
                let html = '<div class="answers-list">';
                data.answers.forEach(answer => {
                    const createdDate = new Date(answer.created_at).toLocaleString('en-US');
                    html += `
                        <div class="answer-item" style="padding: 15px; border-bottom: 1px solid #eee; margin-bottom: 10px;">
                            <h4 style="margin: 0 0 10px 0;">
                                <a href="question.php?id=${answer.question_id}" style="color: #3498db; text-decoration: none;">
                                    <i class="fas fa-question-circle"></i> ${escapeHtml(answer.question_title || 'Question #' + answer.question_id)}
                                </a>
                            </h4>
                            <div style="color: #666; font-size: 14px; margin-bottom: 8px;">
                                ${answer.content ? escapeHtml(answer.content.substring(0, 150)) + (answer.content.length > 150 ? '...' : '') : ''}
                            </div>
                            <div style="display: flex; gap: 15px; font-size: 13px; color: #999;">
                                <span><i class="fas fa-comment"></i> ${answer.comment_count || 0} comments</span>
                                <span><i class="fas fa-thumbs-up"></i> ${answer.like_count || 0} likes</span>
                                ${answer.is_accepted ? '<span style="color: #27ae60;"><i class="fas fa-check-circle"></i> Accepted</span>' : ''}
                                <span><i class="fas fa-clock"></i> ${createdDate}</span>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                modalContent.innerHTML = html;
                
                // Pagination
                if (data.pagination.total_pages > 1) {
                    let paginationHtml = '';
                    if (data.pagination.current_page > 1) {
                        paginationHtml += `<button onclick="showMyAnswers(${data.pagination.current_page - 1})" class="btn btn-secondary">Previous</button>`;
                    }
                    paginationHtml += `<span style="margin: 0 15px;">Page ${data.pagination.current_page} / ${data.pagination.total_pages}</span>`;
                    if (data.pagination.current_page < data.pagination.total_pages) {
                        paginationHtml += `<button onclick="showMyAnswers(${data.pagination.current_page + 1})" class="btn btn-secondary">Next</button>`;
                    }
                    modalPagination.innerHTML = paginationHtml;
                } else {
                    modalPagination.innerHTML = '';
                }
            }
        } else {
            modalContent.innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ${data.message || 'Unable to load answer list'}</div>`;
        }
    } catch (error) {
        console.error('Error loading answers:', error);
        modalContent.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> An error occurred while loading answer list.</div>';
    }
}

// Display received likes list
let currentLikesPage = 1;
window.showMyLikes = async function showMyLikes(page = 1) {
    currentLikesPage = page;
    const modal = document.getElementById('listModal');
    const modalTitle = document.getElementById('listModalTitle');
    const modalContent = document.getElementById('listModalContent');
    const modalPagination = document.getElementById('listModalPagination');
    
    modalTitle.innerHTML = '<i class="fas fa-thumbs-up"></i> Received Likes';
    modalContent.innerHTML = '<div class="loading">Loading...</div>';
    modal.style.display = 'block';
    
    try {
        const response = await fetch(`api/users/likes.php?page=${page}&limit=10`);
        const data = await response.json();
        
        if (data.success && data.likes) {
            if (data.likes.length === 0) {
                modalContent.innerHTML = '<div class="alert alert-info"> You have not received any likes yet.</div>';
                modalPagination.innerHTML = '';
            } else {
                let html = '<div class="likes-list">';
                data.likes.forEach(like => {
                    const createdDate = new Date(like.created_at).toLocaleString('en-US');
                    const likerName = like.liker_full_name || like.liker_username || 'User';
                    
                    if (like.like_type === 'question') {
                        html += `
                            <div class="like-item" style="padding: 15px; border-bottom: 1px solid #eee; margin-bottom: 10px;">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                    <i class="fas fa-question-circle" style="color: #3498db;"></i>
                                    <strong>${escapeHtml(likerName)}</strong>
                                    <span style="color: #999;">liked your question</span>
                                </div>
                                <h4 style="margin: 5px 0;">
                                    <a href="question.php?id=${like.question_id}" style="color: #3498db; text-decoration: none;">
                                        ${escapeHtml(like.question_title || 'Question #' + like.question_id)}
                                    </a>
                                </h4>
                                <div style="font-size: 13px; color: #999;">
                                    <i class="fas fa-clock"></i> ${createdDate}
                                </div>
                            </div>
                        `;
                    } else if (like.like_type === 'answer') {
                        html += `
                            <div class="like-item" style="padding: 15px; border-bottom: 1px solid #eee; margin-bottom: 10px;">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                    <i class="fas fa-comments" style="color: #27ae60;"></i>
                                    <strong>${escapeHtml(likerName)}</strong>
                                    <span style="color: #999;">liked your answer</span>
                                </div>
                                <div style="color: #666; font-size: 14px; margin-bottom: 5px;">
                                    ${like.answer_content ? escapeHtml(like.answer_content.substring(0, 150)) + (like.answer_content.length > 150 ? '...' : '') : ''}
                                </div>
                                <div style="font-size: 13px; color: #999;">
                                    <a href="question.php?id=${like.question_id}" style="color: #3498db; text-decoration: none;">
                                        <i class="fas fa-question-circle"></i> View Question
                                    </a>
                                    <span style="margin-left: 15px;"><i class="fas fa-clock"></i> ${createdDate}</span>
                                </div>
                            </div>
                        `;
                    }
                });
                html += '</div>';
                modalContent.innerHTML = html;
                
                // Pagination
                if (data.pagination.total_pages > 1) {
                    let paginationHtml = '';
                    if (data.pagination.current_page > 1) {
                        paginationHtml += `<button onclick="showMyLikes(${data.pagination.current_page - 1})" class="btn btn-secondary">Previous</button>`;
                    }
                    paginationHtml += `<span style="margin: 0 15px;">Page ${data.pagination.current_page} / ${data.pagination.total_pages}</span>`;
                    if (data.pagination.current_page < data.pagination.total_pages) {
                        paginationHtml += `<button onclick="showMyLikes(${data.pagination.current_page + 1})" class="btn btn-secondary">Next</button>`;
                    }
                    modalPagination.innerHTML = paginationHtml;
                } else {
                    modalPagination.innerHTML = '';
                }
            }
        } else {
            modalContent.innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ${data.message || 'Unable to load likes list'}</div>`;
        }
    } catch (error) {
        console.error('Error loading likes:', error);
        modalContent.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> An error occurred while loading likes list.</div>';
    }
}

// Display likes given list
let currentLikesGivenPage = 1;
window.showMyLikesGiven = async function showMyLikesGiven(page = 1) {
    currentLikesGivenPage = page;
    const modal = document.getElementById('listModal');
    const modalTitle = document.getElementById('listModalTitle');
    const modalContent = document.getElementById('listModalContent');
    const modalPagination = document.getElementById('listModalPagination');
    modalTitle.innerHTML = '<i class="fas fa-heart"></i> Likes Given';
    modalContent.innerHTML = '<div class="loading">Loading...</div>';
    modal.style.display = 'block';
    try {
        const response = await fetch(`api/users/likes-given.php?page=${page}&limit=10`);
        const data = await response.json();
        if (data.success && data.likes) {
            if (data.likes.length === 0) {
                modalContent.innerHTML = '<div class="alert alert-info"> You have not liked any content yet.</div>';
                modalPagination.innerHTML = '';
            } else {
                let html = '<div class="likes-given-list">';
                data.likes.forEach(like => {
                    const createdDate = new Date(like.created_at).toLocaleString('en-US');
                    
                    if (like.like_type === 'question') {
                        const questionOwner = like.question_full_name || like.question_username || 'User';
                        html += `
                            <div class="like-given-item" style="padding: 15px; border-bottom: 1px solid #eee; margin-bottom: 10px; border-radius: 8px; background: #f9f9f9;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 15px;">
                                    <div style="flex: 1;">
                                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                            <i class="fas fa-question-circle" style="color: #3498db;"></i>
                                            <span style="color: #666;">Question by <strong>${escapeHtml(questionOwner)}</strong></span>
                                        </div>
                                        <h4 style="margin: 5px 0;">
                                            <a href="question.php?id=${like.question_id}" style="color: #3498db; text-decoration: none;">
                                                ${escapeHtml(like.question_title || 'Question #' + like.question_id)}
                                            </a>
                                        </h4>
                                        ${like.question_content ? `
                                            <div style="color: #666; font-size: 14px; margin-bottom: 8px;">
                                                ${escapeHtml(like.question_content.substring(0, 150))}${like.question_content.length > 150 ? '...' : ''}
                                            </div>
                                        ` : ''}
                                        <div style="font-size: 13px; color: #999;">
                                            <i class="fas fa-clock"></i> ${createdDate}
                                        </div>
                                    </div>
                                    <div>
                                        <button class="btn btn-danger btn-sm" onclick="removeLike(${like.like_id}, 'question', ${like.question_id})" style="padding: 6px 12px; font-size: 13px;">
                                            <i class="fas fa-heart-broken"></i> Unlike
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else if (like.like_type === 'answer') {
                        const answerOwner = like.answer_full_name || like.answer_username || 'User';
                        html += `
                            <div class="like-given-item" style="padding: 15px; border-bottom: 1px solid #eee; margin-bottom: 10px; border-radius: 8px; background: #f9f9f9;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 15px;">
                                    <div style="flex: 1;">
                                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                            <i class="fas fa-comments" style="color: #27ae60;"></i>
                                            <span style="color: #666;">Answer by <strong>${escapeHtml(answerOwner)}</strong></span>
                                        </div>
                                        <h4 style="margin: 5px 0;">
                                            <a href="question.php?id=${like.question_id}" style="color: #3498db; text-decoration: none;">
                                                <i class="fas fa-question-circle"></i> ${escapeHtml(like.question_title || 'Question #' + like.question_id)}
                                            </a>
                                        </h4>
                                        <div style="color: #666; font-size: 14px; margin-bottom: 8px;">
                                            ${like.answer_content ? escapeHtml(like.answer_content.substring(0, 150)) + (like.answer_content.length > 150 ? '...' : '') : ''}
                                        </div>
                                        <div style="font-size: 13px; color: #999;">
                                            <i class="fas fa-clock"></i> ${createdDate}
                                        </div>
                                    </div>
                                    <div>
                                        <button class="btn btn-danger btn-sm" onclick="removeLike(${like.like_id}, 'answer', ${like.answer_id})" style="padding: 6px 12px; font-size: 13px;">
                                            <i class="fas fa-heart-broken"></i> Unlike
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                });
                html += '</div>';
                modalContent.innerHTML = html;
                // Pagination
                if (data.pagination.total_pages > 1) {
                    let paginationHtml = '';
                    if (data.pagination.current_page > 1) {
                        paginationHtml += `<button onclick="showMyLikesGiven(${data.pagination.current_page - 1})" class="btn btn-secondary">Previous</button>`;
                    }
                    paginationHtml += `<span style="margin: 0 15px;">Page ${data.pagination.current_page} / ${data.pagination.total_pages}</span>`;
                    if (data.pagination.current_page < data.pagination.total_pages) {
                        paginationHtml += `<button onclick="showMyLikesGiven(${data.pagination.current_page + 1})" class="btn btn-secondary">Next</button>`;
                    }
                    modalPagination.innerHTML = paginationHtml;
                } else {
                    modalPagination.innerHTML = '';
                }
            }
        } else {
            modalContent.innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ${data.message || 'Unable to load likes given list'}</div>`;
        }
    } catch (error) {
        console.error('Error loading likes given:', error);
        modalContent.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> An error occurred while loading likes given list.</div>';
    }
}

// Remove like
window.removeLike = async function removeLike(likeId, type, itemId) {
    const confirmed = typeof showConfirm === 'function'
        ? await showConfirm('Are you sure you want to unlike this content?', 'Confirm Unlike')
        : confirm('Are you sure you want to unlike this content?');
    
    if (!confirmed) {
        return;
    }
    
    try {
        // Determine question_id or answer_id
        const data = {};
        if (type === 'question') {
            data.question_id = itemId;
            data.answer_id = null;
        } else {
            data.question_id = null;
            data.answer_id = itemId;
        }
        
        const response = await fetch('api/likes/toggle.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            if (typeof showToast === 'function') {
                showToast('Unlike successful!', 'success');
            } else {
                alert('Unlike successful!');
            }
            // Reload list
            showMyLikesGiven(currentLikesGivenPage);
            // Update statistics
            loadUserStats();
        } else {
            if (typeof showToast === 'function') {
                showToast(result.message || 'An error occurred', 'error');
            } else {
                alert('Error: ' + (result.message || 'An error occurred'));
            }
        }
    } catch (error) {
        console.error('Error:', error);
        if (typeof showToast === 'function') {
            showToast('An error occurred while unliking', 'error');
        } else {
            alert('An error occurred while unliking');
        }
    }
}

// Close modal
window.closeListModal = function closeListModal() {
    document.getElementById('listModal').style.display = 'none';
}

// Show edit question modal
window.editMyQuestion = async function editMyQuestion(questionId) {
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
        const modal = document.getElementById('editQuestionModal');
        
        // Fill form data
        document.getElementById('editQuestionId').value = questionId;
        document.getElementById('editQuestionTitle').value = question.title || '';
        document.getElementById('editQuestionContent').value = question.content || '';
        
        // Set module if exists
        const moduleSelect = document.getElementById('editQuestionModule');
        if (question.module_id && moduleSelect) {
            moduleSelect.value = question.module_id;
        }
        
        // Display current image if exists
        const previewImg = document.getElementById('editPreviewImg');
        const removeBtn = document.getElementById('removeEditImageBtn');
        if (question.images && question.images.trim() !== '') {
            previewImg.src = question.images;
            previewImg.style.display = 'block';
            removeBtn.style.display = 'inline-block';
        } else {
            previewImg.style.display = 'none';
            removeBtn.style.display = 'none';
        }
        
        // Handle preview when selecting new file
        const imageInput = document.getElementById('editQuestionImage');
        imageInput.onchange = function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewImg.style.display = 'block';
                    removeBtn.style.display = 'inline-block';
                };
                reader.readAsDataURL(file);
            }
        };
        
        // Show modal
        modal.style.display = 'block';
    } catch (error) {
        console.error('Error:', error);
        if (typeof showToast === 'function') {
            showToast('An error occurred while loading question information', 'error');
        } else {
            alert('An error occurred while loading question information');
        }
    }
}

// Close edit modal
window.closeEditQuestionModal = function closeEditQuestionModal() {
    document.getElementById('editQuestionModal').style.display = 'none';
    // Reset form
    document.getElementById('editQuestionForm').reset();
    document.getElementById('editPreviewImg').style.display = 'none';
    document.getElementById('removeEditImageBtn').style.display = 'none';
}

// Remove image preview
window.removeEditImage = function removeEditImage() {
    document.getElementById('editQuestionImage').value = '';
    document.getElementById('editPreviewImg').src = '';
    document.getElementById('editPreviewImg').style.display = 'none';
    document.getElementById('removeEditImageBtn').style.display = 'none';
}

// Submit edit question
window.submitEditQuestion = async function submitEditQuestion() {
    const questionId = document.getElementById('editQuestionId').value;
    const titleInput = document.getElementById('editQuestionTitle');
    const contentInput = document.getElementById('editQuestionContent');
    const moduleInput = document.getElementById('editQuestionModule');
    const imageInput = document.getElementById('editQuestionImage');
    
    const title = titleInput.value.trim();
    const content = contentInput.value.trim();
    
    // Validation
    if (!title) {
        if (typeof showToast === 'function') {
            showToast('Please enter question title', 'warning');
        } else {
            alert('Please enter title');
        }
        titleInput.focus();
        return;
    }
    
    if (!content) {
        if (typeof showToast === 'function') {
            showToast('Please enter question content', 'warning');
        } else {
            alert('Please enter content');
        }
        contentInput.focus();
        return;
    }
    
    // Create FormData
    const formData = new FormData();
    formData.append('question_id', questionId);
    formData.append('title', title);
    formData.append('content', content);
    
    if (moduleInput && moduleInput.value) {
        formData.append('module_id', moduleInput.value);
    }
    
    // Add image file if exists
    if (imageInput && imageInput.files && imageInput.files.length > 0) {
        formData.append('image', imageInput.files[0]);
    }
    
    // Disable submit button
    const submitBtn = document.querySelector('#editQuestionForm button[onclick="submitEditQuestion()"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    try {
        // Use POST for FormData because PUT doesn't automatically parse FormData in PHP
        const response = await fetch('api/questions/update.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (typeof showToast === 'function') {
                showToast('Question updated successfully!', 'success');
            } else {
                alert('Question updated successfully!');
            }
            closeEditQuestionModal();
            // Reload question list
            showMyQuestions(currentQuestionsPage);
            // Update statistics
            if (typeof updateStats === 'function') {
                updateStats();
            }
            loadUserStats();
        } else {
            if (typeof showToast === 'function') {
                showToast(data.message || 'An error occurred', 'error');
            } else {
                alert('Error: ' + (data.message || 'An error occurred'));
            }
        }
    } catch (error) {
        console.error('Error:', error);
        if (typeof showToast === 'function') {
            showToast('An error occurred while updating question: ' + error.message, 'error');
        } else {
            alert('An error occurred while updating question: ' + error.message);
        }
    } finally {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    }
}

// Close modal when clicking outside
const originalOnClick = window.onclick;
window.onclick = function(event) {
    // Call old onclick function if exists
    if (originalOnClick) {
        originalOnClick(event);
    }
    
    const listModal = document.getElementById('listModal');
    const editModal = document.getElementById('editQuestionModal');
    if (event.target === listModal) {
        listModal.style.display = 'none';
    }
    if (event.target === editModal) {
        closeEditQuestionModal();
    }
}

// Delete my question
window.deleteMyQuestion = async function deleteMyQuestion(questionId) {
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
        
        const data = await response.json();
        
        if (data.success) {
            if (typeof showToast === 'function') {
                showToast('Question deleted successfully!', 'success');
            } else {
                alert('Question deleted successfully!');
            }
            // Reload question list
            showMyQuestions(currentQuestionsPage);
            // Update statistics
            if (typeof updateStats === 'function') {
                updateStats();
            }
            loadUserStats();
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
            showToast('An error occurred while deleting question', 'error');
        } else {
            alert('An error occurred while deleting question');
        }
    }
}

// Utility function
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Logout
async function logout() {
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
        
        const data = await response.json();
        
        if (data.success) {
            window.location.href = 'index.php';
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Update profile information
document.addEventListener('DOMContentLoaded', () => {
    loadUserStats();
    
    // Handle update information form
    const updateProfileForm = document.getElementById('updateProfileForm');
    if (updateProfileForm) {
        updateProfileForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const messageDiv = document.getElementById('update-profile-message');
            messageDiv.innerHTML = '';
            
            const email = document.getElementById('email').value.trim();
            const full_name = document.getElementById('full_name').value.trim();
            
            if (!email) {
                showMessage(messageDiv, 'Please enter email', 'error');
                return;
            }
            
            try {
                const response = await fetch('api/users/update.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        email: email,
                        full_name: full_name
                    })
                });
                
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Response error:', errorText);
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Update response:', data);
                
                if (data.success) {
                    showMessage(messageDiv, data.message || 'Information updated successfully', 'success');
                    // Reload page information after 1.5 seconds
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showMessage(messageDiv, data.message || 'An error occurred', 'error');
                }
            } catch (error) {
                console.error('Error updating profile:', error);
                let errorMessage = 'An error occurred while updating information.';
                if (error.message) {
                    errorMessage += ' Details: ' + error.message;
                }
                showMessage(messageDiv, errorMessage, 'error');
            }
        });
    }
    
    // Handle change password form
    const changePasswordForm = document.getElementById('changePasswordForm');
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const messageDiv = document.getElementById('change-password-message');
            messageDiv.innerHTML = '';
            
            const current_password = document.getElementById('current_password').value;
            const new_password = document.getElementById('new_password').value;
            const confirm_password = document.getElementById('confirm_password').value;
            
            // Validation
            if (!current_password || !new_password || !confirm_password) {
                showMessage(messageDiv, 'Please fill in all required information', 'error');
                return;
            }
            
            if (new_password.length < 6) {
                showMessage(messageDiv, 'New password must be at least 6 characters', 'error');
                return;
            }
            
            if (new_password !== confirm_password) {
                showMessage(messageDiv, 'New password and confirm password do not match', 'error');
                return;
            }
            
            try {
                const response = await fetch('api/users/change-password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        current_password: current_password,
                        new_password: new_password,
                        confirm_password: confirm_password
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage(messageDiv, data.message || 'Password changed successfully', 'success');
                    // Reset form
                    changePasswordForm.reset();
                } else {
                    showMessage(messageDiv, data.message || 'An error occurred', 'error');
                }
            } catch (error) {
                console.error('Error changing password:', error);
                showMessage(messageDiv, 'An error occurred while changing password', 'error');
            }
        });
    }
});

// Show message
function showMessage(container, message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    container.innerHTML = `<div class="alert ${alertClass}"><i class="fas ${icon}"></i> ${message}</div>`;
    
    // Auto hide message after 5 seconds (except for errors)
    if (type === 'success') {
        setTimeout(() => {
            container.innerHTML = '';
        }, 5000);
    }
}

