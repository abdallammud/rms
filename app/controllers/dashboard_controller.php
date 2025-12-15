<?php
/**
 * Dashboard Controller
 * Handles dashboard statistics and charts
 */

auth_middleware('view_dashboard');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_stats':
            get_dashboard_stats();
            break;
        case 'get_charts':
            get_chart_data();
            break;
        case 'get_tables':
            get_dashboard_tables();
            break;
        default:
            send_json(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}

/**
 * Get core dashboard statistics based on filters and role
 */
function get_dashboard_stats() {
    $period = $_POST['period'] ?? 'monthly';
    $custom_start = $_POST['start_date'] ?? '';
    $custom_end = $_POST['end_date'] ?? '';
    
    $date_conditions = get_date_conditions($period, $custom_start, $custom_end);
    $role_conditions = get_role_conditions(); // Returns array with SQL WHERE clause parts
    
    $where_remittance = "WHERE 1=1 " . $date_conditions['sql'] . $role_conditions['remittance_sql'];
    $where_settlement = "WHERE 1=1 " . $role_conditions['settlement_sql']; // Settlements usually track pending, so maybe no date filter for "Pending Count"? Or date filter for "Settled Amount"?
    // For pending settlements, we usually want TOTAL pending regardless of date, but for "Settled Amount" we want date.
    // Let's split: Pending is current state. Processed is filtered.
    
    $stats = [];
    
    // 1. Total Remittance Volume (Filtered)
    $sql = "SELECT SUM(amount_sent) as total_amount, COUNT(*) as total_count FROM remittances $where_remittance AND status != 'cancelled'";
    $remittance_data = db_query_row($sql);
    $stats['remittance_volume'] = (float)($remittance_data['total_amount'] ?? 0);
    $stats['remittance_count'] = (int)($remittance_data['total_count'] ?? 0);
    
    // 2. Commission Earned (Filtered)
    $sql = "SELECT SUM(total_commission) as total_comm FROM remittances $where_remittance AND status != 'cancelled'";
    $comm_data = db_query_row($sql);
    $stats['total_commission'] = (float)($comm_data['total_comm'] ?? 0);
    
    // 3. Pending Settlements (Current State - No Date Filter)
    $sql = "SELECT COUNT(*) as count, SUM(requested_amount) as amount FROM settlements WHERE status = 'pending' " . $role_conditions['settlement_sql'];
    $pending_sett = db_query_row($sql);
    $stats['pending_settlements_count'] = (int)($pending_sett['count'] ?? 0);
    $stats['pending_settlements_amount'] = (float)($pending_sett['amount'] ?? 0);
    
    // 4. Failed/Rejected Transactions (Filtered)
    $sql = "SELECT COUNT(*) as count FROM remittances $where_remittance AND status = 'rejected'";
    $rejected = db_query_row($sql);
    $stats['rejected_count'] = (int)($rejected['count'] ?? 0);
    
    // 5. Avg Transaction Value
    $stats['avg_transaction'] = $stats['remittance_count'] > 0 ? $stats['remittance_volume'] / $stats['remittance_count'] : 0;
    
    // 6. Active Agents (Admin/Branch only)
    if (get_user_role() === 'admin' || get_user_role() === 'branch_manager') {
        $where_agent = "WHERE is_active = 1";
        if (get_user_role() === 'branch_manager') {
             // Assuming users table has branch_id or linked via agent/branch table. Using generic for now.
             // If structure is distinct, might need adjustment. reusing role conds if applicable.
             $where_agent .= $role_conditions['user_sql']; 
        }
        // Note: Simple count for now
        $agent_data = db_query_row("SELECT COUNT(*) as count FROM users $where_agent AND role_id = (SELECT id FROM roles WHERE role_name = 'Agent' LIMIT 1)");
        $stats['active_agents'] = (int)($agent_data['count'] ?? 0);
    } else {
        // For Agent: Maybe "My Balance"
        $user_id = get_user_id();
        $balance_data = db_query_row("SELECT SUM(balance) as total_balance FROM bank_accounts WHERE account_holder_id = $user_id AND is_active = 1");
        $stats['my_balance'] = (float)($balance_data['total_balance'] ?? 0);
    }

    // 7. Today's Volume vs Yesterday (Trend)
    $today_start = date('Y-m-d 00:00:00');
    $today_role_sql = $role_conditions['remittance_sql'];
    $today = db_query_row("SELECT SUM(amount_sent) as total FROM remittances WHERE created_at >= '$today_start' $today_role_sql");
    $stats['today_volume'] = (float)($today['total'] ?? 0);
    
    // 8. Success Rate
    $completed = db_query_row("SELECT COUNT(*) as count FROM remittances $where_remittance AND status = 'completed'");
    $stats['success_rate'] = $stats['remittance_count'] > 0 ? round(($completed['count'] / $stats['remittance_count']) * 100, 1) : 0;
    
    send_json(['success' => true, 'data' => $stats]);
}

/**
 * Get Chart Data
 */
function get_chart_data() {
    $period = $_POST['period'] ?? 'monthly';
    $custom_start = $_POST['start_date'] ?? '';
    $custom_end = $_POST['end_date'] ?? '';
    
    $date_conditions = get_date_conditions($period, $custom_start, $custom_end);
    $role_conditions = get_role_conditions();
    
    // Chart 1: Volume Trends (Line Chart)
    // Group by Date
    $date_format = "%Y-%m-%d"; // Daily by default
    if ($period === 'yearly') $date_format = "%Y-%m";
    
    $sql = "SELECT DATE_FORMAT(created_at, '$date_format') as label, SUM(amount_sent) as value 
            FROM remittances 
            WHERE 1=1 " . $date_conditions['sql'] . $role_conditions['remittance_sql'] . "
            GROUP BY label 
            ORDER BY label ASC";
    $volume_trend = db_query_all($sql);
    
    // Chart 2: Status Distribution (Pie Chart)
    $sql = "SELECT status as label, COUNT(*) as value 
            FROM remittances 
            WHERE 1=1 " . $date_conditions['sql'] . $role_conditions['remittance_sql'] . "
            AND status != 'cancelled'
            GROUP BY status";
    $status_dist = db_query_all($sql);
    
    // Chart 3: Top Agents (Bar Chart) - Only for Admin/Branch
    $top_agents = [];
    if (get_user_role() !== 'agent') {
        $sql = "SELECT u.username as label, SUM(remittances.amount_sent) as value 
                FROM remittances
                JOIN users u ON remittances.agent_id = u.id
                WHERE 1=1 " . $date_conditions['sql'] . $role_conditions['remittance_sql'] . "
                GROUP BY u.id 
                ORDER BY value DESC 
                LIMIT 5";
        $top_agents = db_query_all($sql);
    }
    
    send_json([
        'success' => true, 
        'volume_trend' => $volume_trend,
        'status_dist' => $status_dist,
        'top_agents' => $top_agents
    ]);
}

/**
 * Get Dashboard Tables
 */
function get_dashboard_tables() {
    $role_conditions = get_role_conditions();
    
    // 1. Recent Remittances (Limit 10)
    $sql = "SELECT r.transaction_id, r.sender_name, r.receiver_name, r.amount_sent, r.currency_sent, r.status, r.created_at
            FROM remittances r
            WHERE 1=1 " . $role_conditions['remittance_sql'] . "
            ORDER BY r.created_at DESC LIMIT 10";
    $recent_remittances = db_query_all($sql);
    
    // 2. Settlement Requests (Pending)
    // Only Admin can see all, Agent sees theirs, Branch??
    // Assuming Dashboard approval is for Admin
    $pending_settlements = [];
    if (has_permission('approve_settlement')) {
        $sql = "SELECT s.id, s.settlement_code, u.full_name as agent_name, s.requested_amount, s.currency_code, s.created_at
                FROM settlements s
                JOIN users u ON s.agent_id = u.id
                WHERE s.status = 'pending' " . $role_conditions['settlement_sql'] . "
                ORDER BY s.created_at DESC LIMIT 10";
        $pending_settlements = db_query_all($sql);
    }
    
    send_json([
        'success' => true,
        'recent_remittances' => $recent_remittances,
        'pending_settlements' => $pending_settlements
    ]);
}

/**
 * Helper: Date Conditions
 */
function get_date_conditions($period, $start, $end) {
    // Default to last 30 days if not specified
    $sql = "";
    $today = date('Y-m-d');
    
    switch ($period) {
        case 'daily':
            $sql = " AND DATE(remittances.created_at) = '$today'";
            break;
        case 'weekly':
            $week_start = date('Y-m-d', strtotime('-7 days'));
            $sql = " AND DATE(remittances.created_at) BETWEEN '$week_start' AND '$today'";
            break;
        case 'monthly':
            $month_start = date('Y-m-d', strtotime('-30 days'));
            $sql = " AND DATE(remittances.created_at) BETWEEN '$month_start' AND '$today'";
            break;
        case 'custom':
            if (!empty($start) && !empty($end)) {
                $sql = " AND DATE(remittances.created_at) BETWEEN '" . db_escape($start) . "' AND '" . db_escape($end) . "'";
            }
            break;
    }
    
    return ['sql' => $sql];
}

/**
 * Helper: Role Conditions
 */
function get_role_conditions() {
    $user_id = get_user_id();
    $role = get_user_role(); // Need to implement this helper or fetch from session
    
    // Mocking role fetching if not available in session helper, assuming session structure
    // In real app, check 'role' in session
    if (!function_exists('get_user_role')) {
        // Fallback or local implementation
        // For now, assuming standard permission checks handle access, 
        // but for data filtering we need explicit SQL
    }
    
    // Quick role check based on permissions
    $is_admin = has_permission('view_all_remittances'); // or similar
    // $is_branch_manager = has_permission('view_branch_remittances'); 
    
    // Re-deriving based on standard session/permissions to be safe
    // Assuming: Admin sees all, Agent sees own.
    
    $remittance_sql = "";
    $settlement_sql = "";
    $user_sql = ""; 
    
    if (has_permission('view_remittances')) {
         // Admin/Manager: See All (or filtered by Branch if we had branch_id in session)
         // For now treating as "See All"
    } else {
         // Agent: See Own
         $remittance_sql = " AND agent_id = $user_id";
         $settlement_sql = " AND agent_id = $user_id";
         $user_sql = " AND id = $user_id";
    }
    
    return [
        'remittance_sql' => $remittance_sql,
        'settlement_sql' => $settlement_sql,
        'user_sql' => $user_sql
    ];
}

// Add local helper if missing
if (!function_exists('get_user_role')) {
    function get_user_role() {
        // Check session for role name
        return $_SESSION['user']['role_name'] ?? 'agent';
    }
}
