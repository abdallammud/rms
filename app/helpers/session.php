<?php
/**
 * Session Management Middleware
 * Auto-logout on inactivity and session validation
 */

// Session timeout (30 minutes)
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds

/**
 * Check session validity
 */
function check_session() {
    if (!is_logged_in()) {
        return false;
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];
        
        if ($inactive_time > SESSION_TIMEOUT) {
            // Session expired
            session_unset();
            session_destroy();
            return false;
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * Middleware to check authentication and permissions
 */
function auth_middleware($required_permission = null) {
    // Check if session is valid
    if (!check_session()) {
        if (is_ajax_request()) {
            send_json(['success' => false, 'message' => 'Session expired. Please login again.', 'redirect' => '?page=login']);
        } else {
            redirect('login');
        }
    }
    
    // Check permission if required
    if ($required_permission && !has_permission($required_permission)) {
        if (is_ajax_request()) {
            send_json(['success' => false, 'message' => 'You do not have permission to perform this action']);
        } else {
            http_response_code(403);
            die('Access Denied: You do not have permission to access this resource');
        }
    }
    
    return true;
}

/**
 * Check if request is AJAX
 */
function is_ajax_request() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * CSRF Token Generation and Validation
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}
