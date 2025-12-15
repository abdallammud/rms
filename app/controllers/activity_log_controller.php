<?php
/**
 * Activity Log Controller
 * Handles viewing of system activity logs
 */

auth_middleware('view_activity_log');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_logs':
            get_activity_logs();
            break;
        case 'get_filters':
            get_log_filters();
            break;
        default:
            send_json(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}

/**
 * Get Activity Logs
 */
function get_activity_logs() {
    $module = $_POST['module'] ?? '';
    $user_id = $_POST['user_id'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    
    $where = "WHERE 1=1";
    
    if (!empty($module)) {
        $where .= " AND l.module = '" . db_escape($module) . "'";
    }
    if (!empty($user_id)) {
        $where .= " AND l.user_id = '" . db_escape($user_id) . "'";
    }
    if (!empty($start_date)) {
        $where .= " AND DATE(l.created_at) >= '" . db_escape($start_date) . "'";
    }
    if (!empty($end_date)) {
        $where .= " AND DATE(l.created_at) <= '" . db_escape($end_date) . "'";
    }
    
    // Limit to last 1000 records to prevent performance issues
    // Real implementation should use server-side pagination for DataTables
    $sql = "SELECT l.id, l.action, l.description, l.module, l.ip_address, l.created_at,
            u.username, u.full_name, r.role_name
            FROM activity_log l
            LEFT JOIN users u ON l.user_id = u.id
            LEFT JOIN roles r ON u.role_id = r.id
            $where
            ORDER BY l.created_at DESC
            LIMIT 1000";
            
    $logs = db_query_all($sql);
    
    send_json(['success' => true, 'data' => $logs]);
}

/**
 * Get Filter Options (Modules and Users)
 */
function get_log_filters() {
    // Get distinct modules
    $modules = db_query_all("SELECT DISTINCT module FROM activity_log WHERE module IS NOT NULL ORDER BY module");
    
    // Get users who have logs
    $users = db_query_all("SELECT DISTINCT u.id, u.username FROM users u JOIN activity_log l ON u.id = l.user_id ORDER BY u.username");
    
    send_json([
        'success' => true,
        'modules' => $modules,
        'users' => $users
    ]);
}
