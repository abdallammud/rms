<?php
/**
 * General Helper Functions
 */

/**
 * Sanitize input data
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Redirect to a page
 */
function redirect($page) {
    header("Location: ?page=$page");
    exit;
}

/**
 * Get current user ID
 */
function get_user_id() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

/**
 * Get current user data
 */
/**
 * Get current user data
 */
function get_current_user1() {
    return isset($_SESSION['user_data']) ? $_SESSION['user_data'] : null;
}

/**
 * Format currency
 */
function format_currency($amount, $currency = 'USD') {
    return $currency . ' ' . number_format($amount, 2);
}

/**
 * Format date
 */
function format_date($date) {
    return date('M d, Y', strtotime($date));
}

/**
 * Format datetime
 */
function format_datetime($datetime) {
    return date('M d, Y h:i A', strtotime($datetime));
}

/**
 * Generate random OTP
 */
function generate_otp($length = 6) {
    return str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

/**
 * Send JSON response
 */
function send_json($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Log activity
 */
function log_activity($user_id, $action, $description, $module = null) {
    $sql = "INSERT INTO activity_log (user_id, action, description, module, created_at) 
            VALUES ('$user_id', '" . db_escape($action) . "', '" . db_escape($description) . "', 
                    " . ($module ? "'".db_escape($module)."'" : "NULL") . ", NOW())";
    db_query($sql);
}
