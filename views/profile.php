<?php 
require VIEW_PATH . '/partials/header.php'; 
?>
<style>
    /* Force inactive tabs to be black */
    .nav-pills .nav-link:not(.active) {
        color: #000 !important;
        background-color: #f8f9fa !important; /* Ensure light bg */
    }
    /* Force active tabs to be blue with white text */
    .nav-pills .nav-link.active {
        background-color: #0d6efd !important;
        color: #fff !important;
    }
</style>
<?php
// Always fetch fresh data from DB for profile page to ensure completeness
$user_id = get_user_id();
$user = db_query_row("SELECT u.*, r.role_name, b.branch_name 
                      FROM users u 
                      LEFT JOIN roles r ON u.role_id = r.id
                      LEFT JOIN branches b ON u.branch_id = b.id 
                      WHERE u.id = $user_id");

if (!$user) {
    // Should not happen if logged in, but handle gracefully
    echo '<div class="alert alert-danger m-4">User not found.</div>';
    require VIEW_PATH . '/partials/footer.php';
    exit;
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Profile Sidebar -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm text-center p-4">
                <div class="mb-3 position-relative d-inline-block">
                    <div class="user-avatar-lg rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto" style="width: 120px; height: 120px; font-size: 3rem;">
                        <?php 
                        $initials = strtoupper(substr($user['username'], 0, 1));
                        if (!empty($user['full_name'])) {
                            $parts = explode(' ', $user['full_name']);
                            $initials = strtoupper(substr($parts[0], 0, 1));
                            if (isset($parts[1])) $initials .= strtoupper(substr($parts[1], 0, 1));
                        }
                        echo $initials;
                        ?>
                    </div>
                </div>
                <h4 class="mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h4>
                <p class="text-muted mb-2">@<?php echo htmlspecialchars($user['username']); ?></p>
                <div class="d-flex justify-content-center gap-2 mb-4">
                    <span class="badge bg-info bg-opacity-10 text-info border border-info"><?php echo htmlspecialchars($user['role_name'] ?? 'User'); ?></span>
                    <?php if(!empty($user['branch_name'])): ?>
                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning"><?php echo htmlspecialchars($user['branch_name']); ?></span>
                    <?php endif; ?>
                </div>
                
                <hr>
                
                <div class="text-start">
                    <div class="mb-3">
                        <small class="text-muted d-block uppercase fst-italic">Member Since</small>
                        <span><?php echo date('F d, Y', strtotime($user['created_at'])); ?></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block uppercase fst-italic">Last Login</small>
                        <span><?php echo $user['last_login'] ? date('M d, Y h:i A', strtotime($user['last_login'])) : 'Never'; ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Profile Settings -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <ul class="nav nav-pills card-header-pills" id="profileTabs" role="tablist">
                        <li class="nav-item me-2">
                            <a class="nav-link active bg-primary text-black fw-bold" id="general-tab" data-bs-toggle="tab" href="#general" role="tab">
                                <i class="bi bi-person-lines-fill me-1"></i> General Info
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-dark fw-bold" id="security-tab" data-bs-toggle="tab" href="#security" role="tab">
                                <i class="bi bi-shield-lock me-1"></i> Security
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content pt-3">
                        <!-- General Info Tab -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel">
                            <form id="profileForm">
                                <input type="hidden" name="action" value="update_profile">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Username</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled readonly>
                                        <div class="form-text">Username cannot be changed.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone Number</label>
                                        <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                    </div>
                                    <div class="col-12 mt-4">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-save me-1"></i> Save Changes
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Security Tab -->
                        <div class="tab-pane fade" id="security" role="tabpanel">
                            <form id="passwordForm">
                                <input type="hidden" name="action" value="change_password">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="alert alert-info border-0 d-flex align-items-center">
                                            <i class="bi bi-shield-lock fs-4 me-3"></i>
                                            <div>
                                                <strong>Password Requirements</strong><br>
                                                <small>Minimum 8 characters. Keep your password secure.</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Current Password</label>
                                        <input type="password" class="form-control" name="current_password" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">New Password</label>
                                        <input type="password" class="form-control" name="new_password" required minlength="8">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" name="confirm_password" required minlength="8">
                                    </div>
                                    <div class="col-12 mt-4">
                                        <button type="submit" class="btn btn-warning text-dark">
                                            <i class="bi bi-key me-1"></i> Update Password
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/profile.js"></script>

<?php require VIEW_PATH . '/partials/footer.php'; ?>
