<?php
/**
 * User Controller
 * Handles user management
 */

// Check authentication and permission
auth_middleware('view_users');

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'save':
            save_user();
            break;
        case 'get':
            get_user();
            break;
        case 'delete':
            delete_user();
            break;
        case 'suspend':
            suspend_user();
            break;
        case 'list':
            list_users();
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
 * Save user (Create or Update)
 */
function save_user() {
    $user_id = $_POST['user_id'] ?? '';
    $required_permission = empty($user_id) ? 'create_user' : 'edit_user';
    require_permission($required_permission);
    
    // Validate input
    $username = sanitize($_POST['username'] ?? '');
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $role_id = sanitize($_POST['role_id'] ?? '');
    $branch_id = sanitize($_POST['branch_id'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($full_name) || empty($email) || empty($role_id)) {
        send_json(['success' => false, 'message' => 'Username, full name, email, and role are required']);
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        send_json(['success' => false, 'message' => 'Invalid email address']);
    }
    
    // Check if username already exists
    $sql = "SELECT id FROM users WHERE username = '" . db_escape($username) . "'";
    if (!empty($user_id)) {
        $sql .= " AND id != '" . db_escape($user_id) . "'";
    }
    $existing = db_query_row($sql);
    
    if ($existing) {
        send_json(['success' => false, 'message' => 'Username already exists']);
    }
    
    // Check if email already exists
    $sql = "SELECT id FROM users WHERE email = '" . db_escape($email) . "'";
    if (!empty($user_id)) {
        $sql .= " AND id != '" . db_escape($user_id) . "'";
    }
    $existing = db_query_row($sql);
    
    if ($existing) {
        send_json(['success' => false, 'message' => 'Email already exists']);
    }
    
    if (empty($user_id)) {
        // Create new user
        if (empty($password)) {
            send_json(['success' => false, 'message' => 'Password is required for new users']);
        }
        
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        
        $sql = "INSERT INTO users (username, password, full_name, email, phone, role_id, branch_id, created_by, created_at) 
                VALUES ('" . db_escape($username) . "', 
                        '" . db_escape($hashed_password) . "', 
                        '" . db_escape($full_name) . "', 
                        '" . db_escape($email) . "', 
                        '" . db_escape($phone) . "', 
                        '" . db_escape($role_id) . "', 
                        " . (empty($branch_id) ? "NULL" : "'" . db_escape($branch_id) . "'") . ", 
                        '" . get_user_id() . "', 
                        NOW())";
        
        if (db_query($sql)) {
            $new_user_id = db_insert_id();
            
            // Create default agent balance account if role is Agent
            $role = db_query_row("SELECT role_name FROM roles WHERE id = '" . db_escape($role_id) . "'");
            if ($role && strtolower($role['role_name']) === 'agent') {
                $sql = "INSERT INTO user_accounts (user_id, account_type, currency_code, balance) 
                        VALUES ('$new_user_id', 'agent_balance', 'USD', 0.00)";
                db_query($sql);
            }
            
            log_activity(get_user_id(), 'create_user', "Created user: $username", 'users');
            send_json(['success' => true, 'message' => 'User created successfully']);
        } else {
            send_json(['success' => false, 'message' => 'Failed to create user']);
        }
    } else {
        // Update existing user
        $sql = "UPDATE users SET 
                username = '" . db_escape($username) . "', 
                full_name = '" . db_escape($full_name) . "', 
                email = '" . db_escape($email) . "', 
                phone = '" . db_escape($phone) . "', 
                role_id = '" . db_escape($role_id) . "', 
                branch_id = " . (empty($branch_id) ? "NULL" : "'" . db_escape($branch_id) . "'") . ", 
                updated_at = NOW() 
                WHERE id = '" . db_escape($user_id) . "'";
        
        if (db_query($sql)) {
            log_activity(get_user_id(), 'update_user', "Updated user: $username", 'users');
            send_json(['success' => true, 'message' => 'User updated successfully']);
        } else {
            send_json(['success' => false, 'message' => 'Failed to update user']);
        }
    }
}

/**
 * Get user details
 */
function get_user() {
    $user_id = $_POST['user_id'] ?? '';
    
    if (empty($user_id)) {
        send_json(['success' => false, 'message' => 'User ID is required']);
    }
    
    $sql = "SELECT u.*, r.role_name, b.branch_name 
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.id 
            LEFT JOIN branches b ON u.branch_id = b.id 
            WHERE u.id = '" . db_escape($user_id) . "'";
    $user = db_query_row($sql);
    
    if ($user) {
        unset($user['password']); // Don't send password
        send_json(['success' => true, 'data' => $user]);
    } else {
        send_json(['success' => false, 'message' => 'User not found']);
    }
}

/**
 * Delete user
 */
function delete_user() {
    require_permission('delete_user');
    
    $user_id = $_POST['user_id'] ?? '';
    
    if (empty($user_id)) {
        send_json(['success' => false, 'message' => 'User ID is required']);
    }
    
    // Prevent deleting own account
    if ($user_id == get_user_id()) {
        send_json(['success' => false, 'message' => 'You cannot delete your own account']);
    }
    
    // Get user details for logging
    $user = db_query_row("SELECT username FROM users WHERE id = '" . db_escape($user_id) . "'");
    
    $sql = "DELETE FROM users WHERE id = '" . db_escape($user_id) . "'";
    
    if (db_query($sql)) {
        log_activity(get_user_id(), 'delete_user', "Deleted user: {$user['username']}", 'users');
        send_json(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        send_json(['success' => false, 'message' => 'Failed to delete user']);
    }
}

/**
 * Suspend/Unsuspend user
 */
function suspend_user() {
    require_permission('suspend_user');
    
    $user_id = $_POST['user_id'] ?? '';
    $status = $_POST['status'] ?? 1;
    
    if (empty($user_id)) {
        send_json(['success' => false, 'message' => 'User ID is required']);
    }
    
    // Prevent suspending own account
    if ($user_id == get_user_id()) {
        send_json(['success' => false, 'message' => 'You cannot suspend your own account']);
    }
    
    $sql = "UPDATE users SET is_suspended = '" . db_escape($status) . "', updated_at = NOW() WHERE id = '" . db_escape($user_id) . "'";
    
    if (db_query($sql)) {
        $status_text = $status == 1 ? 'suspended' : 'unsuspended';
        $user = db_query_row("SELECT username FROM users WHERE id = '" . db_escape($user_id) . "'");
        log_activity(get_user_id(), 'suspend_user', "User {$status_text}: {$user['username']}", 'users');
        send_json(['success' => true, 'message' => 'User status updated successfully']);
    } else {
        send_json(['success' => false, 'message' => 'Failed to update user status']);
    }
}

/**
 * Change user password
 */
function change_password() {
    require_permission('edit_user');
    
    $user_id = $_POST['user_id'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    
    if (empty($user_id) || empty($new_password)) {
        send_json(['success' => false, 'message' => 'User ID and new password are required']);
    }
    
    if (strlen($new_password) < 6) {
        send_json(['success' => false, 'message' => 'Password must be at least 6 characters']);
    }
    
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
    
    $sql = "UPDATE users SET password = '" . db_escape($hashed_password) . "', updated_at = NOW() WHERE id = '" . db_escape($user_id) . "'";
    
    if (db_query($sql)) {
        $user = db_query_row("SELECT username FROM users WHERE id = '" . db_escape($user_id) . "'");
        log_activity(get_user_id(), 'change_password', "Changed password for user: {$user['username']}", 'users');
        send_json(['success' => true, 'message' => 'Password changed successfully']);
    } else {
        send_json(['success' => false, 'message' => 'Failed to change password']);
    }
}

/**
 * List all users
 */
function list_users() {
    $sql = "SELECT u.id, u.username, u.full_name, u.email, u.phone, u.is_active, u.is_suspended, 
            u.last_login, u.created_at, r.role_name, b.branch_name 
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.id 
            LEFT JOIN branches b ON u.branch_id = b.id 
            ORDER BY u.created_at DESC";
    
    $users = db_query_all($sql);
    
    send_json(['success' => true, 'data' => $users]);
}
