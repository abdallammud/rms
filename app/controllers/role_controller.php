<?php
/**
 * Role Controller
 * Handles roles and permissions management
 */

// Check authentication and permission
auth_middleware('view_roles');

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'save':
            save_role();
            break;
        case 'get':
            get_role();
            break;
        case 'delete':
            delete_role();
            break;
        case 'list':
            list_roles();
            break;
        case 'get_permissions':
            get_all_permissions();
            break;
        case 'get_role_permissions':
            get_role_permissions();
            break;
        case 'save_permissions':
            save_role_permissions();
            break;
        default:
            send_json(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}

/**
 * Save role (Create or Update)
 */
function save_role() {
    require_permission('manage_roles');
    
    $role_id = $_POST['role_id'] ?? '';
    $role_name = sanitize($_POST['role_name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    
    if (empty($role_name)) {
        send_json(['success' => false, 'message' => 'Role name is required']);
    }
    
    // Check if role already exists
    $sql = "SELECT id FROM roles WHERE role_name = '" . db_escape($role_name) . "'";
    if (!empty($role_id)) {
        $sql .= " AND id != '" . db_escape($role_id) . "'";
    }
    $existing = db_query_row($sql);
    
    if ($existing) {
        send_json(['success' => false, 'message' => 'Role name already exists']);
    }
    
    if (empty($role_id)) {
        // Create new role
        $sql = "INSERT INTO roles (role_name, description, created_at) 
                VALUES ('" . db_escape($role_name) . "', '" . db_escape($description) . "', NOW())";
        
        if (db_query($sql)) {
            log_activity(get_user_id(), 'create_role', "Created role: $role_name", 'roles');
            send_json(['success' => true, 'message' => 'Role created successfully', 'role_id' => db_insert_id()]);
        } else {
            send_json(['success' => false, 'message' => 'Failed to create role']);
        }
    } else {
        // Update existing role
        $sql = "UPDATE roles SET 
                role_name = '" . db_escape($role_name) . "', 
                description = '" . db_escape($description) . "', 
                updated_at = NOW() 
                WHERE id = '" . db_escape($role_id) . "'";
        
        if (db_query($sql)) {
            log_activity(get_user_id(), 'update_role', "Updated role: $role_name", 'roles');
            send_json(['success' => true, 'message' => 'Role updated successfully']);
        } else {
            send_json(['success' => false, 'message' => 'Failed to update role']);
        }
    }
}

/**
 * Get role details
 */
function get_role() {
    $role_id = $_POST['role_id'] ?? '';
    
    if (empty($role_id)) {
        send_json(['success' => false, 'message' => 'Role ID is required']);
    }
    
    $sql = "SELECT * FROM roles WHERE id = '" . db_escape($role_id) . "'";
    $role = db_query_row($sql);
    
    if ($role) {
        send_json(['success' => true, 'data' => $role]);
    } else {
        send_json(['success' => false, 'message' => 'Role not found']);
    }
}

/**
 * Delete role
 */
function delete_role() {
    require_permission('manage_roles');
    
    $role_id = $_POST['role_id'] ?? '';
    
    if (empty($role_id)) {
        send_json(['success' => false, 'message' => 'Role ID is required']);
    }
    
    // Check if role has users
    $user_count = db_query_row("SELECT COUNT(*) as count FROM users WHERE role_id = '" . db_escape($role_id) . "'");
    
    if ($user_count['count'] > 0) {
        send_json(['success' => false, 'message' => 'Cannot delete role. It has ' . $user_count['count'] . ' assigned users.']);
    }
    
    // Get role details for logging
    $role = db_query_row("SELECT role_name FROM roles WHERE id = '" . db_escape($role_id) . "'");
    
    // Delete role permissions first
    db_query("DELETE FROM role_permissions WHERE role_id = '" . db_escape($role_id) . "'");
    
    // Delete role
    $sql = "DELETE FROM roles WHERE id = '" . db_escape($role_id) . "'";
    
    if (db_query($sql)) {
        log_activity(get_user_id(), 'delete_role', "Deleted role: {$role['role_name']}", 'roles');
        send_json(['success' => true, 'message' => 'Role deleted successfully']);
    } else {
        send_json(['success' => false, 'message' => 'Failed to delete role']);
    }
}

/**
 * List all roles
 */
function list_roles() {
    $sql = "SELECT r.*, 
            (SELECT COUNT(*) FROM users WHERE role_id = r.id) as user_count,
            (SELECT COUNT(*) FROM role_permissions WHERE role_id = r.id) as permission_count
            FROM roles r 
            ORDER BY r.created_at DESC";
    
    $roles = db_query_all($sql);
    
    send_json(['success' => true, 'data' => $roles]);
}

/**
 * Get all permissions grouped by module
 */
function get_all_permissions() {
    $sql = "SELECT * FROM permissions ORDER BY module, permission_name";
    $permissions = db_query_all($sql);
    
    // Group by module
    $grouped = [];
    foreach ($permissions as $permission) {
        $module = $permission['module'] ?? 'Other';
        if (!isset($grouped[$module])) {
            $grouped[$module] = [];
        }
        $grouped[$module][] = $permission;
    }
    
    send_json(['success' => true, 'data' => $grouped]);
}

/**
 * Get role permissions
 */
function get_role_permissions() {
    $role_id = $_POST['role_id'] ?? '';
    
    if (empty($role_id)) {
        send_json(['success' => false, 'message' => 'Role ID is required']);
    }
    
    $sql = "SELECT permission_id FROM role_permissions WHERE role_id = '" . db_escape($role_id) . "'";
    $result = db_query($sql);
    
    $permissions = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $permissions[] = $row['permission_id'];
    }
    
    send_json(['success' => true, 'data' => $permissions]);
}

/**
 * Save role permissions
 */
function save_role_permissions() {
    require_permission('assign_permissions');
    
    $role_id = $_POST['role_id'] ?? '';
    $permissions = $_POST['permissions'] ?? [];
    
    if (empty($role_id)) {
        send_json(['success' => false, 'message' => 'Role ID is required']);
    }
    
    // Get role name for logging
    $role = db_query_row("SELECT role_name FROM roles WHERE id = '" . db_escape($role_id) . "'");
    
    // Delete existing permissions
    db_query("DELETE FROM role_permissions WHERE role_id = '" . db_escape($role_id) . "'");
    
    // Insert new permissions
    if (!empty($permissions) && is_array($permissions)) {
        foreach ($permissions as $permission_id) {
            $sql = "INSERT INTO role_permissions (role_id, permission_id) 
                    VALUES ('" . db_escape($role_id) . "', '" . db_escape($permission_id) . "')";
            db_query($sql);
        }
    }
    
    log_activity(get_user_id(), 'update_permissions', "Updated permissions for role: {$role['role_name']}", 'roles');
    send_json(['success' => true, 'message' => 'Permissions updated successfully']);
}
