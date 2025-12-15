<?php
/**
 * Profile Controller
 * Handles user profile management
 */

// Ensure user is logged in
if (!is_logged_in()) {
    redirect('login');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_profile':
            update_profile();
            break;
        case 'change_password':
            change_password();
            break;
        default:
            send_json(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}

/**
 * Update Profile Information
 */
function update_profile() {
    $user_id = get_user_id();
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    
    // Basic Validation
    if (empty($full_name) || empty($email)) {
        send_json(['success' => false, 'message' => 'Name and Email are required']);
    }
    
    // Check if email exists for another user
    $existing = db_query_row("SELECT id FROM users WHERE email = '" . db_escape($email) . "' AND id != $user_id");
    if ($existing) {
        send_json(['success' => false, 'message' => 'Email already registered']);
    }
    
    // Update Database
    $sql = "UPDATE users SET 
            full_name = '" . db_escape($full_name) . "',
            email = '" . db_escape($email) . "',
            phone = '" . db_escape($phone) . "'
            WHERE id = $user_id";
            
    if (db_query($sql)) {
        // Update Session Data
        $_SESSION['user_data']['full_name'] = $full_name;
        $_SESSION['user_data']['email'] = $email;
        $_SESSION['user_data']['phone'] = $phone;
        
        log_activity($user_id, 'update_profile', 'User updated profile details', 'users');
        send_json(['success' => true, 'message' => 'Profile updated successfully']);
    } else {
        send_json(['success' => false, 'message' => 'Database error during update']);
    }
}

/**
 * Change Password
 */
function change_password() {
    $user_id = get_user_id();
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password)) {
        send_json(['success' => false, 'message' => 'All fields are required']);
    }
    
    if ($new_password !== $confirm_password) {
        send_json(['success' => false, 'message' => 'New passwords do not match']);
    }
    
    // Verify Current Password
    $user = db_query_row("SELECT password FROM users WHERE id = $user_id");
    if (!password_verify($current_password, $user['password'])) {
        send_json(['success' => false, 'message' => 'Incorrect current password']);
    }
    
    // Update Password
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET password = '$hashed' WHERE id = $user_id";
    
    if (db_query($sql)) {
        log_activity($user_id, 'change_password', 'User changed password', 'users');
        send_json(['success' => true, 'message' => 'Password changed successfully']);
    } else {
        send_json(['success' => false, 'message' => 'Database error']);
    }
}
