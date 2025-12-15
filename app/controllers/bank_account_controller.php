<?php
/**
 * Bank Account Controller
 * Handles bank account CRUD operations and activity tracking
 */

// Check authentication and permission
auth_middleware('view_bank_accounts');

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'save':
            save_account();
            break;
        case 'get':
            get_account();
            break;
        case 'delete':
            delete_account();
            break;
        case 'set_default':
            set_default_account();
            break;
        case 'toggle_status':
            toggle_account_status();
            break;
        case 'list':
            list_accounts();
            break;
        case 'get_user_accounts':
            get_user_accounts();
            break;
        case 'get_activity':
            get_account_activity();
            break;
        default:
            send_json(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}

/**
 * Save bank account (Create or Update)
 */
function save_account() {
    require_permission('manage_bank_accounts');
    
    $account_id = $_POST['account_id'] ?? '';
    $account_number = sanitize($_POST['account_number'] ?? '');
    $bank_id = sanitize($_POST['bank_id'] ?? '');
    $account_holder_id = sanitize($_POST['account_holder_id'] ?? '');
    $initial_balance = floatval($_POST['initial_balance'] ?? 0);
    $currency_code = sanitize($_POST['currency_code'] ?? 'USD');
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    $notes = sanitize($_POST['notes'] ?? '');
    
    // Validation
    if (empty($account_number) || empty($bank_id) || empty($account_holder_id)) {
        send_json(['success' => false, 'message' => 'Account number, bank, and account holder are required']);
    }
    
    // Check if account already exists
    $check_sql = "SELECT id FROM bank_accounts WHERE account_number = '" . db_escape($account_number) . "'";
    if (!empty($account_id)) {
        $check_sql .= " AND id != '" . db_escape($account_id) . "'";
    }
    
    if (db_query_row($check_sql)) {
        send_json(['success' => false, 'message' => 'This account number already exists']);
    }
    
    $created_by = get_user_id();
    
    // If setting as default, remove default flag from other accounts
    if ($is_default) {
        db_query("UPDATE bank_accounts SET is_default = 0 WHERE account_holder_id = '" . db_escape($account_holder_id) . "'");
    }
    
    if (empty($account_id)) {
        // Create new account
        $sql = "INSERT INTO bank_accounts (
                    account_number, bank_id, account_holder_id, balance, initial_balance,
                    currency_code, is_default, notes, created_by, created_at
                ) VALUES (
                    '" . db_escape($account_number) . "',
                    '" . db_escape($bank_id) . "',
                    '" . db_escape($account_holder_id) . "',
                    $initial_balance,
                    $initial_balance,
                    '" . db_escape($currency_code) . "',
                    $is_default,
                    '" . db_escape($notes) . "',
                    $created_by,
                    NOW()
                )";
        
        if (db_query($sql)) {
            $new_account_id = db_insert_id();
            
            // Log initial balance as activity if > 0
            if ($initial_balance > 0) {
                $activity_sql = "INSERT INTO bank_account_activity (
                                bank_account_id, transaction_type, transaction_direction, amount,
                                balance_before, balance_after, description, created_by, created_at
                            ) VALUES (
                                $new_account_id,
                                'initial_balance',
                                'credit',
                                $initial_balance,
                                0.00,
                                $initial_balance,
                                'Initial account balance',
                                $created_by,
                                NOW()
                            )";
                db_query($activity_sql);
            }
            
            log_activity($created_by, 'create_bank_account', "Created bank account: $account_number", 'banking');
            send_json(['success' => true, 'message' => 'Bank account created successfully']);
        } else {
            send_json(['success' => false, 'message' => 'Failed to create bank account']);
        }
    } else {
        // Update existing account
        $sql = "UPDATE bank_accounts SET 
                account_number = '" . db_escape($account_number) . "',
                bank_id = '" . db_escape($bank_id) . "',
                account_holder_id = '" . db_escape($account_holder_id) . "',
                currency_code = '" . db_escape($currency_code) . "',
                is_default = $is_default,
                notes = '" . db_escape($notes) . "',
                updated_at = NOW()
                WHERE id = '" . db_escape($account_id) . "'";
        
        if (db_query($sql)) {
            log_activity($created_by, 'update_bank_account', "Updated bank account: $account_number", 'banking');
            send_json(['success' => true, 'message' => 'Bank account updated successfully']);
        } else {
            send_json(['success' => false, 'message' => 'Failed to update bank account']);
        }
    }
}

/**
 * Get bank account details
 */
function get_account() {
    $account_id = $_POST['account_id'] ?? '';
    
    if (empty($account_id)) {
        send_json(['success' => false, 'message' => 'Account ID is required']);
    }
    
    $sql = "SELECT ba.*, b.bank_name, u.full_name as holder_name
            FROM bank_accounts ba
            LEFT JOIN banks b ON ba.bank_id = b.id
            LEFT JOIN users u ON ba.account_holder_id = u.id
            WHERE ba.id = '" . db_escape($account_id) . "'";
    $account = db_query_row($sql);
    
    if ($account) {
        send_json(['success' => true, 'data' => $account]);
    } else {
        send_json(['success' => false, 'message' => 'Bank account not found']);
    }
}

/**
 * Delete bank account
 */
function delete_account() {
    require_permission('manage_bank_accounts');
    
    $account_id = $_POST['account_id'] ?? '';
    
    if (empty($account_id)) {
        send_json(['success' => false, 'message' => 'Account ID is required']);
    }
    
    // Get account details
    $account = db_query_row("SELECT * FROM bank_accounts WHERE id = '" . db_escape($account_id) . "'");
    
    if (!$account) {
        send_json(['success' => false, 'message' => 'Bank account not found']);
    }
    
    // Check if account has transactions
    $activity_count = db_query_row("SELECT COUNT(*) as count FROM bank_account_activity WHERE bank_account_id = '" . db_escape($account_id) . "'");
    
    if ($activity_count && $activity_count['count'] > 0) {
        send_json(['success' => false, 'message' => 'Cannot delete account with transaction history. Deactivate it instead.']);
    }
    
    // Check if this is default account
    if ($account['is_default'] == 1) {
        send_json(['success' => false, 'message' => 'Cannot delete default account. Set another account as default first.']);
    }
    
    $sql = "DELETE FROM bank_accounts WHERE id = '" . db_escape($account_id) . "'";
    
    if (db_query($sql)) {
        log_activity(get_user_id(), 'delete_bank_account', "Deleted bank account: {$account['account_number']}", 'banking');
        send_json(['success' => true, 'message' => 'Bank account deleted successfully']);
    } else {
        send_json(['success' => false, 'message' => 'Failed to delete bank account']);
    }
}

/**
 * Set account as default
 */
function set_default_account() {
    require_permission('manage_bank_accounts');
    
    $account_id = $_POST['account_id'] ?? '';
    
    if (empty($account_id)) {
        send_json(['success' => false, 'message' => 'Account ID is required']);
    }
    
    $account = db_query_row("SELECT account_holder_id, account_number FROM bank_accounts WHERE id = '" . db_escape($account_id) . "'");
    
    if (!$account) {
        send_json(['success' => false, 'message' => 'Bank account not found']);
    }
    
    // Remove default from all user's accounts
    db_query("UPDATE bank_accounts SET is_default = 0 WHERE account_holder_id = '" . db_escape($account['account_holder_id']) . "'");
    
    // Set this as default
    $sql = "UPDATE bank_accounts SET is_default = 1, updated_at = NOW() WHERE id = '" . db_escape($account_id) . "'";
    
    if (db_query($sql)) {
        log_activity(get_user_id(), 'set_default_account', "Set default account: {$account['account_number']}", 'banking');
        send_json(['success' => true, 'message' => 'Default account updated successfully']);
    } else {
        send_json(['success' => false, 'message' => 'Failed to update default account']);
    }
}

/**
 * Toggle account status
 */
function toggle_account_status() {
    require_permission('manage_bank_accounts');
    
    $account_id = $_POST['account_id'] ?? '';
    $status = $_POST['status'] ?? 1;
    
    if (empty($account_id)) {
        send_json(['success' => false, 'message' => 'Account ID is required']);
    }
    
    $sql = "UPDATE bank_accounts SET is_active = '" . db_escape($status) . "', updated_at = NOW() 
            WHERE id = '" . db_escape($account_id) . "'";
    
    if (db_query($sql)) {
        $status_text = $status == 1 ? 'activated' : 'deactivated';
        log_activity(get_user_id(), 'toggle_account_status', "Bank account {$status_text}", 'banking');
        send_json(['success' => true, 'message' => 'Account status updated successfully']);
    } else {
        send_json(['success' => false, 'message' => 'Failed to update account status']);
    }
}

/**
 * List all bank accounts
 */
function list_accounts() {
    $sql = "SELECT ba.*, b.bank_name, u.username, u.full_name as holder_name, c.full_name as created_by_name
            FROM bank_accounts ba
            LEFT JOIN banks b ON ba.bank_id = b.id
            LEFT JOIN users u ON ba.account_holder_id = u.id
            LEFT JOIN users c ON ba.created_by = c.id
            ORDER BY u.full_name, ba.is_default DESC, ba.created_at DESC";
    
    $accounts = db_query_all($sql);
    
    send_json(['success' => true, 'data' => $accounts]);
}

/**
 * Get bank accounts for a specific user
 */
function get_user_accounts() {
    $user_id = $_POST['user_id'] ?? '';
    
    if (empty($user_id)) {
        send_json(['success' => false, 'message' => 'User ID is required']);
    }
    
    $sql = "SELECT ba.*, b.bank_name
            FROM bank_accounts ba
            LEFT JOIN banks b ON ba.bank_id = b.id
            WHERE ba.account_holder_id = '" . db_escape($user_id) . "' 
            AND ba.is_active = 1
            ORDER BY ba.is_default DESC, ba.created_at DESC";
    
    $accounts = db_query_all($sql);
    
    send_json(['success' => true, 'data' => $accounts]);
}

/**
 * Get account activity log
 */
function get_account_activity() {
    $account_id = $_POST['account_id'] ?? '';
    
    if (empty($account_id)) {
        send_json(['success' => false, 'message' => 'Account ID is required']);
    }
    
    $sql = "SELECT baa.*, u.full_name as created_by_name
            FROM bank_account_activity baa
            LEFT JOIN users u ON baa.created_by = u.id
            WHERE baa.bank_account_id = '" . db_escape($account_id) . "'
            ORDER BY baa.created_at DESC";
    
    $activities = db_query_all($sql);
    
    send_json(['success' => true, 'data' => $activities]);
}

/**
 * Helper function: Log bank account activity
 * Used by settlement controller and remittance controller
 */
function log_bank_activity($bank_account_id, $transaction_type, $amount, $direction, $reference_type = null, $reference_id = null, $description = '') {
    // Get current balance
    $account = db_query_row("SELECT balance FROM bank_accounts WHERE id = '" . db_escape($bank_account_id) . "'");
    
    if (!$account) {
        return false;
    }
    
    $balance_before = floatval($account['balance']);
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
