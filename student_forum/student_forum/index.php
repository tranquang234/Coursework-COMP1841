<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Forum</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<style>
    .question-image img{
        width: auto !important;
        height: 100px !important;
    }
</style>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <h1><i class="fas fa-graduation-cap"></i> Student Forum</h1>
            </div>
            <div class="nav-menu">
                <a href="#" onclick="showContactModal(); return false;" class="btn btn-info" style="background-color: #17a2b8; color:white !important;"><i class="fas fa-envelope"></i> Contact</a>
                <?php if (isLoggedIn()): ?>
                    <?php $user = getCurrentUser(); ?>
                    <span class="user-info"><i class="fas fa-user-circle"></i> Hello, <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></span>
                    <?php if (isAdmin()): ?>
                        <a href="admin.php" class="btn btn-warning"><i class="fas fa-cog"></i> Admin</a>
                    <?php endif; ?>
                    <a href="profile.php" class="btn btn-secondary"><i class="fas fa-user"></i> Profile</a>
                    <a href="#" onclick="logout()" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="register.php" class="btn btn-secondary"><i class="fas fa-user-plus"></i> Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="content-wrapper">
            <div class="main-content">
                <div class="page-header">
                    <h2><i class="fas fa-question-circle"></i> All Questions</h2>
                    <?php if (isLoggedIn()): ?>
                        <button class="btn btn-primary" onclick="showCreateQuestionModal()"><i class="fas fa-plus-circle"></i> Post New Question</button>
                    <?php endif; ?>
                </div>

                <div id="questions-list" class="questions-list">
                    <!-- Question list will be loaded by JavaScript -->
                </div>

                <div id="pagination" class="pagination">
                    <!-- Pagination will be loaded by JavaScript -->
                </div>
            </div>

            <?php include 'includes/sidebar.php'; ?>
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

    <!-- Create Question Modal --> 
    <div id="createQuestionModal" class="modal"  style="overflow: auto;">
        <div class="modal-content">
            <span class="close" onclick="closeCreateQuestionModal()">&times;</span>
            <h2><i class="fas fa-edit"></i> Post New Question</h2>
            <form id="createQuestionForm" onsubmit="return false;">
                <div class="form-group">
                    <label for="questionTitle"><i class="fas fa-heading"></i> Title: <span style="color: red;">*</span></label>
                    <input type="text" id="questionTitle" name="title" placeholder="Enter question title...">
                </div>
                <div class="form-group">
                    <label for="questionContent"><i class="fas fa-align-left"></i> Content: <span style="color: red;">*</span></label>
                    <textarea id="questionContent" name="content" rows="6" placeholder="Enter your question content..."></textarea>
                </div>
                <div class="form-group">
                    <label for="questionModule"><i class="fas fa-book"></i> Module:</label>
                    <select id="questionModule" name="module_id">
                        <option value="">-- Select module (optional) --</option>
                        <?php
                        // Query module list from database
                        try {
                            $pdo = getDBConnection();
                            
                            // Check and create modules table if it doesn't exist
                            try {
                                $checkTable = $pdo->query("SHOW TABLES LIKE 'modules'");
                                if ($checkTable->rowCount() == 0) {
                                    $createTableSQL = "
                                        CREATE TABLE IF NOT EXISTS modules (
                                            module_id INT AUTO_INCREMENT PRIMARY KEY,
                                            module_name VARCHAR(100) NOT NULL UNIQUE,
                                            module_code VARCHAR(50),
                                            description TEXT,
                                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                            INDEX idx_module_name (module_name)
                                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                                    ";
                                    $pdo->exec($createTableSQL);
                                }
                            } catch (PDOException $e) {
                                // Ignore error if table already exists
                            }
                            
                            // Get module list
                            $query = "SELECT module_id, module_name FROM modules ORDER BY module_name ASC";
                            $stmt = $pdo->query($query);
                            
                            // Create options
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $module_id = (int)$row['module_id'];
                                $module_name = htmlspecialchars($row['module_name']);
                                echo "<option value=\"{$module_id}\">{$module_name}</option>";
                            }
                        } catch (PDOException $e) {
                            // If error, just log and don't show any options
                            error_log('Error loading modules: ' . $e->getMessage());
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="questionImage"><i class="fas fa-image"></i> Image (optional):</label>
                    <input type="file" id="questionImage" name="image" accept="image/*">
                    <small style="color: #666; display: block; margin-top: 5px;">Only image files accepted (JPG, PNG, GIF, ...)</small>
                    <div id="imagePreview" style="margin-top: 10px; display: none;">
                        <img id="previewImg" src="" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 5px; border: 1px solid #ddd;">
                        <button type="button" onclick="removeImage()" style="margin-left: 10px; padding: 5px 10px; background: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer;">
                            <i class="fas fa-times"></i> Remove Image
                        </button>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-primary" onclick="submitCreateQuestion()"><i class="fas fa-paper-plane"></i> Post Question</button>
                    <button type="button" class="btn btn-secondary" onclick="closeCreateQuestionModal()"><i class="fas fa-times"></i> Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Contact - Feedback Modal -->
    <div id="contactModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeContactModal()">&times;</span>
            <h2><i class="fas fa-envelope"></i> Contact - Feedback</h2>
            <form id="contactForm" onsubmit="return false;">
                <div class="form-group">
                    <label for="contactFullName"><i class="fas fa-user"></i> Full Name: <span style="color: red;">*</span></label>
                    <input type="text" id="contactFullName" name="full_name" placeholder="Enter your full name..." required>
                </div>
                <div class="form-group">
                    <label for="contactEmail"><i class="fas fa-envelope"></i> Email: <span style="color: red;">*</span></label>
                    <input type="email" id="contactEmail" name="email" placeholder="Enter your email..." required>
                </div>
                <div class="form-group">
                    <label for="contactContent"><i class="fas fa-comment-alt"></i> Feedback Content: <span style="color: red;">*</span></label>
                    <textarea id="contactContent" name="content" rows="6" placeholder="Enter your feedback content..." required></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-primary" onclick="submitContactForm()"><i class="fas fa-paper-plane"></i> Send Feedback</button>
                    <button type="button" class="btn btn-secondary" onclick="closeContactModal()"><i class="fas fa-times"></i> Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Debug: Check if script has loaded
        console.log('index.php script loaded');
        console.log('Loading main.js...');
        
        window.isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
        window.submitCreateQuestion = async function submitCreateQuestion() {
            const titleInput = document.getElementById('questionTitle');
            const contentInput = document.getElementById('questionContent');
            const imageInput = document.getElementById('questionImage');
            const createQuestionForm = document.getElementById('createQuestionForm');

            if (!titleInput || !contentInput || !createQuestionForm) {
                alert('An error occurred. Please reload the page.');
                return;
            }

            // Get values directly from input
            const title = titleInput.value ? titleInput.value.trim() : '';
            const content = contentInput.value ? contentInput.value.trim() : '';

            // Validation
            if (title.length === 0) {
                if (typeof showToast === 'function') {
                    showToast('Please enter question title', 'warning');
                } else {
                    alert('Please enter title');
                }
                titleInput.focus();
                return;
            }

            if (content.length === 0) {
                if (typeof showToast === 'function') {
                    showToast('Please enter question content', 'warning');
                } else {
                    alert('Please enter content');
                }
                contentInput.focus();
                return;
            }

            // Create new FormData and append each field clearly
            const formData = new FormData();
            formData.append('title', title);
            formData.append('content', content);

            // Add module_id if exists
            const moduleInput = document.getElementById('questionModule');
            if (moduleInput && moduleInput.value) {
                formData.append('module_id', moduleInput.value);
            }

            // Add image file if exists
            if (imageInput && imageInput.files && imageInput.files.length > 0) {
                formData.append('image', imageInput.files[0]);
            }

            // Debug: Check data in FormData
            console.log('=== FORMDATA DEBUG ===');
            console.log('Title:', formData.get('title'));
            console.log('Content:', formData.get('content'));
            console.log('Module ID:', formData.get('module_id') || 'None');
            const imageFile = formData.get('image');
            console.log('Image:', imageFile ? (imageFile.name + ' (' + imageFile.size + ' bytes)') : 'None');
            console.log('All FormData entries:');
            for (let pair of formData.entries()) {
                if (pair[1] instanceof File) {
                    console.log('  ' + pair[0] + ': [File] ' + pair[1].name + ' (' + pair[1].size + ' bytes, type: ' + pair[1].type + ')');
                } else {
                    const value = String(pair[1]);
                    console.log('  ' + pair[0] + ': [' + value.substring(0, 50) + (value.length > 50 ? '...' : '') + '] (length: ' + value.length + ')');
                }
            }
            console.log('=====================');

            // Disable submit button to prevent double submit
            const submitBtn = createQuestionForm.querySelector('button[onclick="submitCreateQuestion()"]');
            if (!submitBtn) {
                alert('Submit button not found');
                return;
            }

            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            try {
                console.log('Sending request to api/questions/create.php...');
                const response = await fetch('api/questions/create.php', {
                    method: 'POST',
                    body: formData
                    // Don't set Content-Type header, browser will automatically set with boundary for multipart/form-data
                });

                console.log('Response status:', response.status);
                console.log('Response headers:', Object.fromEntries(response.headers.entries()));

                // Read response text
                const responseText = await response.text();
                console.log('Response text:', responseText);

                // Parse JSON
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Response text:', responseText);
                    alert('Error: Server returned invalid data. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                    return;
                }

                if (data.success) {
                    if (typeof showToast === 'function') {
                        showToast('Your question has been posted successfully!', 'success');
                    } else {
                        alert('Question posted successfully!');
                    }
                    closeCreateQuestionModal();
                    loadQuestions(1);
                    // Update statistics if updateStats function exists
                    if (typeof updateStats === 'function') {
                        updateStats();
                    }
                } else {
                    if (typeof showToast === 'function') {
                        showToast(data.message || 'An error occurred while posting question', 'error');
                    } else {
                        alert('Error: ' + (data.message || 'An error occurred while posting question'));
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                if (typeof showToast === 'function') {
                    showToast('An error occurred while posting question: ' + error.message, 'error');
                } else {
                    alert('An error occurred while posting question: ' + error.message);
                }
            } finally {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        }

        // Function to open contact modal
        function showContactModal() {
            const modal = document.getElementById('contactModal');
            if (modal) {
                modal.style.display = 'block';
                // Auto-fill information if logged in
                <?php if (isLoggedIn()): ?>
                    <?php $user = getCurrentUser(); ?>
                    const fullNameInput = document.getElementById('contactFullName');
                    const emailInput = document.getElementById('contactEmail');
                    if (fullNameInput && emailInput) {
                        fullNameInput.value = '<?php echo htmlspecialchars($user['full_name'] ?: $user['username'], ENT_QUOTES); ?>';
                        emailInput.value = '<?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?>';
                    }
                <?php endif; ?>
            }
        }

        // Function to close contact modal
        function closeContactModal() {
            const modal = document.getElementById('contactModal');
            if (modal) {
                modal.style.display = 'none';
                // Reset form
                const form = document.getElementById('contactForm');
                if (form) {
                    form.reset();
                }
            }
        }

        // Function to submit contact form
        async function submitContactForm() {
            const fullNameInput = document.getElementById('contactFullName');
            const emailInput = document.getElementById('contactEmail');
            const contentInput = document.getElementById('contactContent');
            const contactForm = document.getElementById('contactForm');

            if (!fullNameInput || !emailInput || !contentInput || !contactForm) {
                alert('An error occurred. Please reload the page.');
                return;
            }

            // Get values
            const fullName = fullNameInput.value ? fullNameInput.value.trim() : '';
            const email = emailInput.value ? emailInput.value.trim() : '';
            const content = contentInput.value ? contentInput.value.trim() : '';

            // Validation
            if (fullName.length === 0) {
                if (typeof showToast === 'function') {
                    showToast('Please enter full name', 'warning');
                } else {
                    alert('Please enter full name');
                }
                fullNameInput.focus();
                return;
            }

            if (email.length === 0) {
                if (typeof showToast === 'function') {
                    showToast('Please enter email', 'warning');
                } else {
                    alert('Please enter email');
                }
                emailInput.focus();
                return;
            }

            // Validate email format
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                if (typeof showToast === 'function') {
                    showToast('Invalid email format', 'warning');
                } else {
                    alert('Invalid email format');
                }
                emailInput.focus();
                return;
            }

            if (content.length === 0) {
                if (typeof showToast === 'function') {
                    showToast('Please enter feedback content', 'warning');
                } else {
                    alert('Please enter feedback content');
                }
                contentInput.focus();
                return;
            }

            // Create FormData
            const formData = new FormData();
            formData.append('full_name', fullName);
            formData.append('email', email);
            formData.append('content', content);

            // Disable submit button
            const submitBtn = contactForm.querySelector('button[onclick="submitContactForm()"]');
            if (!submitBtn) {
                alert('Submit button not found');
                return;
            }

            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

            try {
                const response = await fetch('api/contact/send.php', {
                    method: 'POST',
                    body: formData
                });

                const responseText = await response.text();
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Response text:', responseText);
                    alert('Error: Server returned invalid data. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                    return;
                }

                if (data.success) {
                    if (typeof showToast === 'function') {
                        showToast(data.message || 'Feedback sent successfully!', 'success');
                    } else {
                        alert(data.message || 'Feedback sent successfully!');
                    }
                    closeContactModal();
                } else {
                    if (typeof showToast === 'function') {
                        showToast(data.message || 'An error occurred while sending feedback', 'error');
                    } else {
                        alert('Error: ' + (data.message || 'An error occurred while sending feedback'));
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                if (typeof showToast === 'function') {
                    showToast('An error occurred while sending feedback: ' + error.message, 'error');
                } else {
                    alert('An error occurred while sending feedback: ' + error.message);
                }
            } finally {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const contactModal = document.getElementById('contactModal');
            if (event.target == contactModal) {
                closeContactModal();
            }
        }
    </script>
 
    <script src="assets/js/main.js?v=<?php echo time(); ?>" onerror="console.error('Error loading main.js!')" onload="console.log('main.js loaded (onload event)')"></script>
   
</body>

</html>