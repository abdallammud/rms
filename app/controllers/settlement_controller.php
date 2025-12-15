<?php
/**
 * Settlement Controller
 * Handles settlement requests and approvals
 */

// Check authentication
auth_middleware('view_settlements');

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'request':
            request_settlement();
            break;
        case 'approve':
            approve_settlement();
            break;
        case 'reject':
            reject_settlement();
            break;
        case 'get':
            get_settlement();
            break;
        case 'list':
            list_settlements();
            break;
        default:
            send_json(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}

/**
 * Request settlement
 */
function request_settlement() {
    require_permission('request_settlement');
    
    $agent_id = get_user_id();
    $bank_account_id = sanitize($_POST['bank_account_id'] ?? '');
    $requested_amount = floatval($_POST['requested_amount'] ?? 0);
    $currency_code = sanitize($_POST['currency_code'] ?? 'USD');
    $settlement_type = sanitize($_POST['settlement_type'] ?? 'full');
    $payment_method = sanitize($_POST['payment_method'] ?? 'bank_transfer');
    $notes = sanitize($_POST['notes'] ?? '');
    
    // Validation
    if (empty($bank_account_id) || $requested_amount <= 0) {
        send_json(['success' => false, 'message' => 'Bank account and valid amount are required']);
    }
    
    // Verify bank account belongs to user
    $account = db_query_row("SELECT * FROM bank_accounts WHERE id = '" . db_escape($bank_account_id) . "' AND account_holder_id = $agent_id");
    
    if (!$account) {
        send_json(['success' => false, 'message' => 'Invalid bank account']);
    }
    
    // Generate settlement code
    $settlement_code = 'SET-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Create settlement request
    $sql = "INSERT INTO settlements (
                settlement_code, agent_id, bank_account_id, requested_amount, currency_code,
                settlement_type, payment_method, notes, status, created_at
            ) VALUES (
                '" . db_escape($settlement_code) . "',
                $agent_id,
                '" . db_escape($bank_account_id) . "',
                $requested_amount,
                '" . db_escape($currency_code) . "',
                '" . db_escape($settlement_type) . "',
                '" . db_escape($payment_method) . "',
                '" . db_escape($notes) . "',
                'pending',
                NOW()
            )";
    
    if (db_query($sql)) {
        log_activity($agent_id, 'request_settlement', "Requested settlement: $settlement_code - Amount: $requested_amount", 'settlements');
        send_json(['success' => true, 'message' => 'Settlement request submitted successfully', 'settlement_code' => $settlement_code]);
    } else {
        send_json(['success' => false, 'message' => 'Failed to submit settlement request']);
    }
}

/**
 * Approve settlement
 */
function approve_settlement() {
    require_permission('approve_settlement');
    
    $settlement_id = $_POST['settlement_id'] ?? '';
    $reference_number = sanitize($_POST['reference_number'] ?? '');
    
    if (empty($settlement_id)) {
        send_json(['success' => false, 'message' => 'Settlement ID is required']);
    }
    
    // Get settlement details
    $settlement = db_query_row("SELECT * FROM settlements WHERE id = '" . db_escape($settlement_id) . "' AND status = 'pending'");
    
    if (!$settlement) {
        send_json(['success' => false, 'message' => 'Settlement not found or already processed']);
    }
    
    $approved_by = get_user_id();
    
    // Update settlement status
    $sql = "UPDATE settlements SET 
            status = 'approved',
            approved_by = $approved_by,
            approved_at = NOW(),
            reference_number = '" . db_escape($reference_number) . "',
            completed_by = $approved_by,
            completed_at = NOW(),
            updated_at = NOW()
            WHERE id = '" . db_escape($settlement_id) . "'";
    
    if (!db_query($sql)) {
        send_json(['success' => false, 'message' => 'Failed to approve settlement']);
    }
    
    // Update bank account balance (Increase balance for deposit flow)
    $success = _settlement_log_bank_activity(
        $settlement['bank_account_id'],
        'settlement',
        floatval($settlement['requested_amount']),
        'credit', // CREDIT = Increase Balance
        'settlement',
        $settlement_id,
        "Settlement approved: {$settlement['settlement_code']}"
    );
    
    if ($success) {
        log_activity($approved_by, 'approve_settlement', "Approved settlement: {$settlement['settlement_code']}", 'settlements');
        send_json(['success' => true, 'message' => 'Settlement approved and balance updated successfully']);
    } else {
        // Rollback settlement approval
        db_query("UPDATE settlements SET status = 'pending', approved_by = NULL, approved_at = NULL WHERE id = '" . db_escape($settlement_id) . "'");
        send_json(['success' => false, 'message' => 'Failed to update account balance']);
    }
}

/**
 * Reject settlement
 */
function reject_settlement() {
    require_permission('approve_settlement');
    
    $settlement_id = $_POST['settlement_id'] ?? '';
    $rejection_reason = sanitize($_POST['rejection_reason'] ?? '');
    
    if (empty($settlement_id) || empty($rejection_reason)) {
        send_json(['success' => false, 'message' => 'Settlement ID and rejection reason are required']);
    }
    
    // Get settlement details
    $settlement = db_query_row("SELECT * FROM settlements WHERE id = '" . db_escape($settlement_id) . "' AND status = 'pending'");
    
    if (!$settlement) {
        send_json(['success' => false, 'message' => 'Settlement not found or already processed']);
    }
    
    $approved_by = get_user_id();
    
    // Update settlement status
    $sql = "UPDATE settlements SET 
            status = 'rejected',
            approved_by = $approved_by,
            approved_at = NOW(),
            rejection_reason = '" . db_escape($rejection_reason) . "',
            updated_at = NOW()
            WHERE id = '" . db_escape($settlement_id) . "'";
    
    if (db_query($sql)) {
        log_activity($approved_by, 'reject_settlement', "Rejected settlement: {$settlement['settlement_code']}", 'settlements');
        send_json(['success' => true, 'message' => 'Settlement rejected']);
    } else {
        send_json(['success' => false, 'message' => 'Failed to reject settlement']);
    }
}

/**
 * Get settlement details
 */
function get_settlement() {
    $settlement_id = $_POST['settlement_id'] ?? '';
    
    if (empty($settlement_id)) {
        send_json(['success' => false, 'message' => 'Settlement ID is required']);
    }
    
    $sql = "SELECT s.*, 
            u.full_name as agent_name,
            ba.account_number,
            b.bank_name,
            approver.full_name as approved_by_name,
            completer.full_name as completed_by_name
            FROM settlements s
            LEFT JOIN users u ON s.agent_id = u.id
            LEFT JOIN bank_accounts ba ON s.bank_account_id = ba.id
            LEFT JOIN banks b ON ba.bank_id = b.id
            LEFT JOIN users approver ON s.approved_by = approver.id
            LEFT JOIN users completer ON s.completed_by = completer.id
            WHERE s.id = '" . db_escape($settlement_id) . "'";
    
    $settlement = db_query_row($sql);
    
    if ($settlement) {
        send_json(['success' => true, 'data' => $settlement]);
    } else {
        send_json(['success' => false, 'message' => 'Settlement not found']);
    }
}

/**
 * List settlements
 */
function list_settlements() {
    $status_filter = $_POST['status_filter'] ?? '';
    $user_id = $_POST['user_id'] ?? '';
    
    $sql = "SELECT s.id, s.settlement_code, s.agent_id, s.bank_account_id, 
            s.requested_amount, s.payment_method, s.status, s.created_at,
            s.approved_at, s.approved_by, s.completed_at, s.completed_by,
            s.reference_number, s.rejection_reason, s.notes,
            u.full_name as agent_name,
            ba.account_number,
            ba.currency_code,
            b.bank_name,
            approver.full_name as approved_by_name
            FROM settlements s
            LEFT JOIN users u ON s.agent_id = u.id
            LEFT JOIN bank_accounts ba ON s.bank_account_id = ba.id
            LEFT JOIN banks b ON ba.bank_id = b.id
            LEFT JOIN users approver ON s.approved_by = approver.id
            WHERE 1=1";
    
    if (!empty($status_filter)) {
        $sql .= " AND s.status = '" . db_escape($status_filter) . "'";
    }
    
    // Check permissions
    if (has_permission('view_settlements')) {
        // Admin views all or filtered by user
        if (!empty($user_id)) {
            $sql .= " AND s.agent_id = '" . db_escape($user_id) . "'";
        }
    } else {
        // Regular user only views their own
        $sql .= " AND s.agent_id = '" . get_user_id() . "'";
    }
    
    $sql .= " ORDER BY s.created_at DESC";
    
    $settlements = db_query_all($sql);
    
    send_json(['success' => true, 'data' => $settlements]);
}

/**
 * Helper function: Log bank account activity (Local Scope)
 * Independent implementation to avoid dependency on bank_account_controller
 */
function _settlement_log_bank_activity($bank_account_id, $transaction_type, $amount, $direction, $reference_type = null, $reference_id = null, $description = '') {
    // Get current balance
    $account = db_query_row("SELECT balance FROM bank_accounts WHERE id = '" . db_escape($bank_account_id) . "'");
    
    if (!$account) {
        return false;
    }
    
    $balance_before = floatval($account['balance']);
    // CREDIT = Increase, DEBIT = Decrease
    $balance_after = $direction === 'credit' ? ($balance_before + $amount) : ($balance_before - $amount);
    
    // Update account balance
    $update_sql = "UPDATE bank_accounts SET balance = $balance_after, updated_at = NOW() 
                   WHERE id = '" . db_escape($bank_account_id) . "'";
    
    if (!db_query($update_sql)) {
        return false;
    }
    
    // Log activity
    $activity_sql = "INSERT INTO bank_account_activity (
                        bank_account_id, transaction_type, transaction_direction, amount,
                        balance_before, balance_after, reference_type, reference_id,
                        description, created_by, created_at
                    ) VALUES (
                        '" . db_escape($bank_account_id) . "',
                        '" . db_escape($transaction_type) . "',
                        '" . db_escape($direction) . "',
                        $amount,
                        $balance_before,
                        $balance_after,
                        " . ($reference_type ? "'" . db_escape($reference_type) . "'" : "NULL") . ",
                        " . ($reference_id ? "'" . db_escape($reference_id) . "'" : "NULL") . ",
                        '" . db_escape($description) . "',
                        " . get_user_id() . ",
                        NOW()
                    )";
    
    return db_query($activity_sql);
}
