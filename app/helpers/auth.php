<?php
/**
 * Authentication Helper Functions
 */

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user has permission
 */
function has_permission($permission) {
    if (!is_logged_in()) {
        return false;
    }
    
    $user_permissions = isset($_SESSION['permissions']) ? $_SESSION['permissions'] : [];
    return in_array($permission, $user_permissions);
}

/**
 * Require login
 */
function require_login() {
    if (!is_logged_in()) {
        redirect('login');
    }
}

/**
 * Require permission
 */
function require_permission($permission) {
    if (!has_permission($permission)) {
        http_response_code(403);
        die('Access Denied: You do not have permission to access this resource.');
    }
}

/**
 * Get user role
 */
function get_user_role() {
    return isset($_SESSION['role_name']) ? $_SESSION['role_name'] : 'Guest';
}

/**
 * Check if user has role
 */
function has_role($role) {
    return get_user_role() === $role;
}

/**
 * Check if user is admin
 */
function is_admin() {
    return has_role('Admin');
}

/**
 * Check if user is manager
 */
function is_manager() {
    return has_role('Branch Manager');
}

/**
 * Check if user is agent
 */
function is_agent() {
    return has_role('Agent');
}
