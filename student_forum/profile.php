<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Student Forum</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <h1><i class="fas fa-graduation-cap"></i> Student Forum</h1>
            </div>
            <div class="nav-menu">
                <span class="user-info"><i class="fas fa-user-circle"></i> Hello, <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></span>
                <?php if (isAdmin()): ?>
                    <a href="admin.php" class="btn btn-warning"><i class="fas fa-cog"></i> Admin</a>
                <?php endif; ?>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-home"></i> Home</a>
                <a href="profile.php" class="btn btn-secondary"><i class="fas fa-user"></i> Profile</a>
                <a href="#" onclick="logout()" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="content-wrapper">
            <div class="main-content">
                <div class="profile-card">
                    <h2><i class="fas fa-user"></i> Account Information</h2>
                    <div class="profile-info">
                        <p><i class="fas fa-user-tag"></i> <strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                        <p><i class="fas fa-envelope"></i> <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><i class="fas fa-id-card"></i> <strong>Full Name:</strong> <?php echo htmlspecialchars($user['full_name'] ?: 'Not updated'); ?></p>
                        <p><i class="fas fa-user-shield"></i> <strong>Role:</strong> 
                            <?php 
                            $roles = ['student' => 'Student', 'teacher' => 'Teacher', 'admin' => 'Administrator'];
                            echo $roles[$user['role']] ?? $user['role'];
                            ?>
                        </p>
                    </div>
                </div>

                <div class="profile-card">
                    <h2><i class="fas fa-edit"></i> Update Information</h2>
                    <form id="updateProfileForm">
                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> Email: <span style="color: red;">*</span></label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="full_name"><i class="fas fa-id-card"></i> Full Name:</label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" placeholder="Enter your full name">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                        <div id="update-profile-message" style="margin-top: 15px;"></div>
                    </form>
                </div>

                <div class="profile-card">
                    <h2><i class="fas fa-key"></i> Change Password</h2>
                    <form id="changePasswordForm">
                        <div class="form-group">
                            <label for="current_password"><i class="fas fa-lock"></i> Current Password: <span style="color: red;">*</span></label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password"><i class="fas fa-key"></i> New Password: <span style="color: red;">*</span></label>
                            <input type="password" id="new_password" name="new_password" minlength="6" required>
                            <small style="color: #666; display: block; margin-top: 5px;">Password must be at least 6 characters</small>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password"><i class="fas fa-check-circle"></i> Confirm New Password: <span style="color: red;">*</span></label>
                            <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </div>
                        <div id="change-password-message" style="margin-top: 15px;"></div>
                    </form>
                </div>

                <div class="profile-stats">
                    <h2><i class="fas fa-chart-bar"></i> Statistics</h2>
                    <div id="user-stats">
                        <!-- Statistics will be loaded by JavaScript -->
                    </div>
                </div>
            </div>

            <?php include 'includes/sidebar.php'; ?>
        </div>
    </div>

    <!-- List Display Modal -->
    <div id="listModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 900px; max-height: 90vh; overflow-y: auto;">
            <span class="close" onclick="closeListModal()">&times;</span>
            <h2 id="listModalTitle"><i class="fas fa-list"></i> List</h2>
            <div id="listModalContent">
                <!-- Content will be loaded by JavaScript -->
            </div>
            <div id="listModalPagination" class="pagination" style="margin-top: 20px;"></div>
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

    <!-- Edit Question Modal -->
    <div id="editQuestionModal" class="modal" style="display: none; overflow:auto;">
        <div class="modal-content">
            <span class="close" onclick="closeEditQuestionModal()">&times;</span>
            <h2><i class="fas fa-edit"></i> Edit Question</h2>
            <form id="editQuestionForm" onsubmit="return false;">
                <input type="hidden" id="editQuestionId" name="question_id">
                <div class="form-group">
                    <label for="editQuestionTitle"><i class="fas fa-heading"></i> Title: <span style="color: red;">*</span></label>
                    <input type="text" id="editQuestionTitle" name="title" placeholder="Enter question title..." required>
                </div>
                <div class="form-group">
                    <label for="editQuestionContent"><i class="fas fa-align-left"></i> Content: <span style="color: red;">*</span></label>
                    <textarea id="editQuestionContent" name="content" rows="6" placeholder="Enter your question content..." required></textarea>
                </div>
                <div class="form-group">
                    <label for="editQuestionModule"><i class="fas fa-book"></i> Module:</label>
                    <select id="editQuestionModule" name="module_id">
                        <option value="">-- Select module (optional) --</option>
                        <?php
                        // Query module list from database
                        try {
                            $pdo = getDBConnection();
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
                    <label for="editQuestionImage"><i class="fas fa-image"></i> Image (optional):</label>
                    <input type="file" id="editQuestionImage" name="image" accept="image/*">
                    <small style="color: #666; display: block; margin-top: 5px;">Only image files accepted (JPG, PNG, GIF, ...). Leave empty if you don't want to change.</small>
                    <div id="editImagePreview" style="margin-top: 10px;">
                        <img id="editPreviewImg" src="" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 5px; border: 1px solid #ddd; display: none;">
                        <button type="button" id="removeEditImageBtn" onclick="removeEditImage()" style="margin-left: 10px; padding: 5px 10px; background: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer; display: none;">
                            <i class="fas fa-times"></i> Remove Image
                        </button>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-primary" onclick="submitEditQuestion()"><i class="fas fa-save"></i> Save Changes</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditQuestionModal()"><i class="fas fa-times"></i> Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script src="assets/js/auth.js?v=<?php echo time(); ?>"></script>
    <script src="assets/js/profile.js?v=<?php echo time(); ?>"></script>
</body>
</html>

