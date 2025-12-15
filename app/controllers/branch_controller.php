<?php
/**
 * Branch Controller
 * Handles CRUD operations for branches
 */

// Check authentication and permission
auth_middleware('view_branches');

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'save':
            save_branch();
            break;
        case 'get':
            get_branch();
            break;
        case 'delete':
            delete_branch();
            break;
        case 'toggle_status':
            toggle_branch_status();
            break;
        case 'list':
            list_branches();
            break;
        default:
            send_json(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}

/**
 * Save branch (Create or Update)
 */
function save_branch() {
    // Check permission
    $branch_id = $_POST['branch_id'] ?? '';
    $required_permission = empty($branch_id) ? 'create_branch' : 'edit_branch';
    require_permission($required_permission);
    
    // Validate input
    $branch_name = sanitize($_POST['branch_name'] ?? '');
    $branch_code = sanitize($_POST['branch_code'] ?? '');
    $location = sanitize($_POST['location'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $manager_name = sanitize($_POST['manager_name'] ?? '');
    
    if (empty($branch_name) || empty($branch_code)) {
        send_json(['success' => false, 'message' => 'Branch name and code are required']);
    }
    
    // Check if branch code already exists (for new branches or different branch)
    $sql = "SELECT id FROM branches WHERE branch_code = '" . db_escape($branch_code) . "'";
    if (!empty($branch_id)) {
        $sql .= " AND id != '" . db_escape($branch_id) . "'";
    }
    $existing = db_query_row($sql);
    
    if ($existing) {
        send_json(['success' => false, 'message' => 'Branch code already exists']);
    }
    
    if (empty($branch_id)) {
        // Create new branch
        $sql = "INSERT INTO branches (branch_name, branch_code, location, phone, email, manager_name, created_at) 
                VALUES ('" . db_escape($branch_name) . "', 
                        '" . db_escape($branch_code) . "', 
                        '" . db_escape($location) . "', 
                        '" . db_escape($phone) . "', 
                        '" . db_escape($email) . "', 
                        '" . db_escape($manager_name) . "', 
                        NOW())";
        
        if (db_query($sql)) {
            log_activity(get_user_id(), 'create_branch', "Created branch: $branch_name ($branch_code)", 'branches');
            send_json(['success' => true, 'message' => 'Branch created successfully']);
        } else {
            send_json(['success' => false, 'message' => 'Failed to create branch']);
        }
    } else {
        // Update existing branch
        $sql = "UPDATE branches SET 
                branch_name = '" . db_escape($branch_name) . "', 
                branch_code = '" . db_escape($branch_code) . "', 
                location = '" . db_escape($location) . "', 
                phone = '" . db_escape($phone) . "', 
                email = '" . db_escape($email) . "', 
                manager_name = '" . db_escape($manager_name) . "', 
                updated_at = NOW() 
                WHERE id = '" . db_escape($branch_id) . "'";
        
        if (db_query($sql)) {
            log_activity(get_user_id(), 'update_branch', "Updated branch: $branch_name ($branch_code)", 'branches');
            send_json(['success' => true, 'message' => 'Branch updated successfully']);
        } else {
            send_json(['success' => false, 'message' => 'Failed to update branch']);
        }
    }
}

/**
 * Get branch details
 */
function get_branch() {
    $branch_id = $_POST['branch_id'] ?? '';
    
    if (empty($branch_id)) {
        send_json(['success' => false, 'message' => 'Branch ID is required']);
    }
    
    $sql = "SELECT * FROM branches WHERE id = '" . db_escape($branch_id) . "'";
    $branch = db_query_row($sql);
    
    if ($branch) {
        send_json(['success' => true, 'data' => $branch]);
    } else {
        send_json(['success' => false, 'message' => 'Branch not found']);
    }
}

/**
 * Delete branch
 */
function delete_branch() {
    require_permission('delete_branch');
    
    $branch_id = $_POST['branch_id'] ?? '';
    
    if (empty($branch_id)) {
        send_json(['success' => false, 'message' => 'Branch ID is required']);
    }
    
    // Check if branch has users
    $user_count = db_query_row("SELECT COUNT(*) as count FROM users WHERE branch_id = '" . db_escape($branch_id) . "'");
    
    if ($user_count['count'] > 0) {
        send_json(['success' => false, 'message' => 'Cannot delete branch. It has ' . $user_count['count'] . ' assigned users.']);
    }
    
    // Get branch details for logging
    $branch = db_query_row("SELECT branch_name, branch_code FROM branches WHERE id = '" . db_escape($branch_id) . "'");
    
    $sql = "DELETE FROM branches WHERE id = '" . db_escape($branch_id) . "'";
    
    if (db_query($sql)) {
        log_activity(get_user_id(), 'delete_branch', "Deleted branch: {$branch['branch_name']} ({$branch['branch_code']})", 'branches');
        send_json(['success' => true, 'message' => 'Branch deleted successfully']);
    } else {
        send_json(['success' => false, 'message' => 'Failed to delete branch']);
    }
}

/**
 * Toggle branch status
 */
function toggle_branch_status() {
    require_permission('edit_branch');
    
    $branch_id = $_POST['branch_id'] ?? '';
    $status = $_POST['status'] ?? 1;
    
    if (empty($branch_id)) {
        send_json(['success' => false, 'message' => 'Branch ID is required']);
    }
    
    $sql = "UPDATE branches SET is_active = '" . db_escape($status) . "', updated_at = NOW() WHERE id = '" . db_escape($branch_id) . "'";
    
    if (db_query($sql)) {
        $status_text = $status == 1 ? 'activated' : 'deactivated';
        $branch = db_query_row("SELECT branch_name FROM branches WHERE id = '" . db_escape($branch_id) . "'");
        log_activity(get_user_id(), 'toggle_branch_status', "Branch {$status_text}: {$branch['branch_name']}", 'branches');
        send_json(['success' => true, 'message' => 'Branch status updated successfully']);
    } else {
        send_json(['success' => false, 'message' => 'Failed to update branch status']);
    }
}

/**
 * List all branches (for DataTables)
 */
function list_branches() {
    $sql = "SELECT id, branch_name, branch_code, location, phone, email, manager_name, is_active, created_at 
            FROM branches 
            ORDER BY created_at DESC";
    
    $branches = db_query_all($sql);
    
    send_json(['success' => true, 'data' => $branches]);
}
