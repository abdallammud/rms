<?php
/**
 * Remittance Controller
 * Handles remittance transaction CRUD operations
 */

// Check authentication and permission
auth_middleware('view_remittances');

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'save':
            save_remittance();
            break;
        case 'get':
            get_remittance();
            break;
        case 'delete':
            delete_remittance();
            break;
        case 'list':
            list_remittances();
            break;
        case 'calculate_commission':
            calculate_commission();
            break;
        case 'update_status':
            update_status();
            break;
        default:
            send_json(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}

/**
 * Save remittance (Create or Update)
 */
function save_remittance() {
    require_permission('create_remittance');
    
    $remittance_id = $_POST['remittance_id'] ?? '';
    
    // Sender Information
    $sender_name = sanitize($_POST['sender_name'] ?? '');
    $sender_phone = sanitize($_POST['sender_phone'] ?? '');
    $sender_email = sanitize($_POST['sender_email'] ?? '');
    $sender_address = sanitize($_POST['sender_address'] ?? '');
    $sender_id_type = sanitize($_POST['sender_id_type'] ?? '');
    $sender_id_number = sanitize($_POST['sender_id_number'] ?? '');
    $sender_relation = sanitize($_POST['sender_relation_to_receiver'] ?? '');
    
    // Receiver Information
    $receiver_name = sanitize($_POST['receiver_name'] ?? '');
    $receiver_phone = sanitize($_POST['receiver_phone'] ?? '');
    $receiver_email = sanitize($_POST['receiver_email'] ?? '');
    $receiver_address = sanitize($_POST['receiver_address'] ?? '');
    $receiver_id_type = sanitize($_POST['receiver_id_type'] ?? '');
    $receiver_id_number = sanitize($_POST['receiver_id_number'] ?? '');
    $receiver_bank_name = sanitize($_POST['receiver_bank_name'] ?? '');
    $receiver_account_number = sanitize($_POST['receiver_account_number'] ?? '');
    $receiver_account_holder = sanitize($_POST['receiver_account_holder'] ?? '');
    
    // Transaction Details
    $amount_sent = floatval($_POST['amount_sent'] ?? 0);
    $currency_sent = sanitize($_POST['currency_sent'] ?? 'USD');
    $exchange_rate = floatval($_POST['exchange_rate'] ?? 1.0);
    $bank_account_id = intval($_POST['bank_account_id'] ?? 0);
    
    // Validation
    if (empty($sender_name)) {
        send_json(['success' => false, 'message' => 'Sender name is required']);
    }
    
    if (empty($receiver_name)) {
        send_json(['success' => false, 'message' => 'Receiver name is required']);
    }
    
    if ($amount_sent <= 0) {
        send_json(['success' => false, 'message' => 'Amount must be greater than zero']);
    }
    
    if (empty($bank_account_id)) {
        send_json(['success' => false, 'message' => 'Bank account is required']);
    }
    
    // Get user info
    $user_id = get_user_id();
    $user = db_query_row("SELECT branch_id FROM users WHERE id = " . db_escape($user_id));
    
    if (!$user || !$user['branch_id']) {
        send_json(['success' => false, 'message' => 'User must be assigned to a branch']);
    }
    
    // Calculate commission
    $commission_data = get_commission_for_amount($amount_sent, $currency_sent);
    $customer_commission = $commission_data['customer_commission'];
    $agent_commission = $commission_data['agent_commission'];
    $total_commission = $customer_commission + $agent_commission;
    
    // Calculate amount received
    $amount_received = $amount_sent * $exchange_rate;
    
    // Total amount to deduct from bank account
    $total_deduction = $amount_sent + $customer_commission;
    
    // Verify bank account has sufficient balance
    $bank_account = db_query_row("SELECT * FROM bank_accounts WHERE id = " . db_escape($bank_account_id) . " AND account_holder_id = " . db_escape($user_id) . " AND is_active = 1");
    
    if (!$bank_account) {
        send_json(['success' => false, 'message' => 'Invalid or inactive bank account']);
    }
    
    if (floatval($bank_account['balance']) < $total_deduction) {
        send_json(['success' => false, 'message' => 'Insufficient balance in bank account']);
    }
    
    if (empty($remittance_id)) {
        // Create new remittance
        
        // Generate unique transaction ID
        $transaction_id = 'RMT' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Check if transaction ID exists
        while (db_query_row("SELECT id FROM remittances WHERE transaction_id = '" . db_escape($transaction_id) . "'")) {
            $transaction_id = 'RMT' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
        
        // Start transaction
        db_query("START TRANSACTION");
        
        try {
            // Insert remittance
            $sql = "INSERT INTO remittances (
                transaction_id, sender_name, sender_phone, sender_email, sender_address,
                sender_id_type, sender_id_number, sender_relation_to_receiver,
                receiver_name, receiver_phone, receiver_email, receiver_address,
                receiver_id_type, receiver_id_number, receiver_bank_name,
                receiver_account_number, receiver_account_holder,
                amount_sent, currency_sent, amount_received, currency_received,
                exchange_rate, customer_commission, agent_commission, total_commission,
                agent_id, bank_account_id, branch_id, status, created_by, created_at
            ) VALUES (
                '" . db_escape($transaction_id) . "',
                '" . db_escape($sender_name) . "',
                '" . db_escape($sender_phone) . "',
                '" . db_escape($sender_email) . "',
                '" . db_escape($sender_address) . "',
                '" . db_escape($sender_id_type) . "',
                '" . db_escape($sender_id_number) . "',
                '" . db_escape($sender_relation) . "',
                '" . db_escape($receiver_name) . "',
                '" . db_escape($receiver_phone) . "',
                '" . db_escape($receiver_email) . "',
                '" . db_escape($receiver_address) . "',
                '" . db_escape($receiver_id_type) . "',
                '" . db_escape($receiver_id_number) . "',
                '" . db_escape($receiver_bank_name) . "',
                '" . db_escape($receiver_account_number) . "',
                '" . db_escape($receiver_account_holder) . "',
                " . db_escape($amount_sent) . ",
                '" . db_escape($currency_sent) . "',
                " . db_escape($amount_received) . ",
                '" . db_escape($currency_sent) . "',
                " . db_escape($exchange_rate) . ",
                " . db_escape($customer_commission) . ",
                " . db_escape($agent_commission) . ",
                " . db_escape($total_commission) . ",
                " . db_escape($user_id) . ",
                " . db_escape($bank_account_id) . ",
                " . db_escape($user['branch_id']) . ",
                'completed',
                " . db_escape($user_id) . ",
                NOW()
            )";
            
            if (!db_query($sql)) {
                throw new Exception('Failed to create remittance');
            }
            
            $remittance_id = db_insert_id();
            
            // Update bank account balance
            $new_balance = floatval($bank_account['balance']) - $total_deduction;
            $update_sql = "UPDATE bank_accounts SET balance = " . db_escape($new_balance) . ", updated_at = NOW() WHERE id = " . db_escape($bank_account_id);
            
            if (!db_query($update_sql)) {
                throw new Exception('Failed to update bank account balance');
            }
            
            // Log bank account activity
            $activity_sql = "INSERT INTO bank_account_activity (
                bank_account_id, transaction_type, transaction_direction,
                amount, balance_before, balance_after,
                reference_type, reference_id, description, created_by, created_at
            ) VALUES (
                " . db_escape($bank_account_id) . ",
                'remittance',
                'debit',
                " . db_escape($total_deduction) . ",
                " . db_escape($bank_account['balance']) . ",
                " . db_escape($new_balance) . ",
                'remittance',
                " . db_escape($remittance_id) . ",
                'Remittance to " . db_escape($receiver_name) . " (TXN: " . db_escape($transaction_id) . ")',
                " . db_escape($user_id) . ",
                NOW()
            )";
            
            if (!db_query($activity_sql)) {
                throw new Exception('Failed to log bank account activity');
            }
            
            // Commit transaction
            db_query("COMMIT");
            
            log_activity($user_id, 'create_remittance', "Created remittance: $transaction_id to $receiver_name", 'remittances');
            send_json(['success' => true, 'message' => 'Remittance created successfully', 'transaction_id' => $transaction_id]);
            
        } catch (Exception $e) {
            db_query("ROLLBACK");
            send_json(['success' => false, 'message' => $e->getMessage()]);
        }
        
    } else {
        // Update existing remittance (without balance changes)
        $sql = "UPDATE remittances SET
            sender_name = '" . db_escape($sender_name) . "',
            sender_phone = '" . db_escape($sender_phone) . "',
            sender_email = '" . db_escape($sender_email) . "',
            sender_address = '" . db_escape($sender_address) . "',
            sender_id_type = '" . db_escape($sender_id_type) . "',
            sender_id_number = '" . db_escape($sender_id_number) . "',
            sender_relation_to_receiver = '" . db_escape($sender_relation) . "',
            receiver_name = '" . db_escape($receiver_name) . "',
            receiver_phone = '" . db_escape($receiver_phone) . "',
            receiver_email = '" . db_escape($receiver_email) . "',
            receiver_address = '" . db_escape($receiver_address) . "',
            receiver_id_type = '" . db_escape($receiver_id_type) . "',
            receiver_id_number = '" . db_escape($receiver_id_number) . "',
            receiver_bank_name = '" . db_escape($receiver_bank_name) . "',
            receiver_account_number = '" . db_escape($receiver_account_number) . "',
            receiver_account_holder = '" . db_escape($receiver_account_holder) . "',
            updated_at = NOW()
            WHERE id = " . db_escape($remittance_id);
        
        if (db_query($sql)) {
            log_activity($user_id, 'update_remittance', "Updated remittance ID: $remittance_id", 'remittances');
            send_json(['success' => true, 'message' => 'Remittance updated successfully']);
        } else {
            send_json(['success' => false, 'message' => 'Failed to update remittance']);
        }
    }
}

/**
 * Get remittance details
 */
function get_remittance() {
    $remittance_id = $_POST['remittance_id'] ?? '';
    
    if (empty($remittance_id)) {
        send_json(['success' => false, 'message' => 'Remittance ID is required']);
    }
    
    $sql = "SELECT r.*, ba.account_number, b.bank_name 
            FROM remittances r
            LEFT JOIN bank_accounts ba ON r.bank_account_id = ba.id
            LEFT JOIN banks b ON ba.bank_id = b.id
            WHERE r.id = '" . db_escape($remittance_id) . "'";
    
    $remittance = db_query_row($sql);
    
    if ($remittance) {
        send_json(['success' => true, 'data' => $remittance]);
    } else {
        send_json(['success' => false, 'message' => 'Remittance not found']);
    }
}

/**
 * Delete remittance
 */
function delete_remittance() {
    require_permission('delete_remittance');
    
    $remittance_id = $_POST['remittance_id'] ?? '';
    
    if (empty($remittance_id)) {
        send_json(['success' => false, 'message' => 'Remittance ID is required']);
    }
    
    // Get remittance details
    $remittance = db_query_row("SELECT * FROM remittances WHERE id = '" . db_escape($remittance_id) . "'");
    
    if (!$remittance) {
        send_json(['success' => false, 'message' => 'Remittance not found']);
    }
    
    // Delete remittance
    $sql = "DELETE FROM remittances WHERE id = '" . db_escape($remittance_id) . "'";
    
    if (db_query($sql)) {
        log_activity(get_user_id(), 'delete_remittance', "Deleted remittance: {$remittance['transaction_id']}", 'remittances');
        send_json(['success' => true, 'message' => 'Remittance deleted successfully']);
    } else {
        send_json(['success' => false, 'message' => 'Failed to delete remittance']);
    }
}

/**
 * List all remittances
 */
function list_remittances() {
    $user_id = get_user_id();
    $user = db_query_row("SELECT role_id FROM users WHERE id = " . db_escape($user_id));
    
    // Build query based on permissions
    $sql = "SELECT r.*, 
            u.full_name as agent_name,
            ba.account_number,
            b.bank_name
            FROM remittances r
            LEFT JOIN users u ON r.agent_id = u.id
            LEFT JOIN bank_accounts ba ON r.bank_account_id = ba.id
            LEFT JOIN banks b ON ba.bank_id = b.id";
    
    // If user only has view_own_remittances permission, filter by agent
    if (has_permission('view_own_remittances') && !has_permission('view_remittances')) {
        $sql .= " WHERE r.agent_id = " . db_escape($user_id);
    }
    
    $sql .= " ORDER BY r.created_at DESC";
    
    $remittances = db_query_all($sql);
    
    send_json(['success' => true, 'data' => $remittances]);
}

/**
 * Calculate commission for given amount
 */
function calculate_commission() {
    $amount = floatval($_POST['amount'] ?? 0);
    $currency = sanitize($_POST['currency'] ?? 'USD');
    
    if ($amount <= 0) {
        send_json(['success' => false, 'message' => 'Invalid amount']);
    }
    
    $commission_data = get_commission_for_amount($amount, $currency);
    
    send_json([
        'success' => true,
        'customer_commission' => $commission_data['customer_commission'],
        'agent_commission' => $commission_data['agent_commission'],
        'total_commission' => $commission_data['customer_commission'] + $commission_data['agent_commission']
    ]);
}

/**
 * Helper function to get commission for amount
 */
function get_commission_for_amount($amount, $currency = 'USD') {
    // Get customer commission tier
    $customer_tier = db_query_row("SELECT * FROM commission_tiers 
        WHERE tier_type = 'customer' 
        AND currency_code = '" . db_escape($currency) . "'
        AND min_amount <= " . db_escape($amount) . "
        AND (max_amount >= " . db_escape($amount) . " OR max_amount IS NULL)
        AND is_active = 1
        ORDER BY min_amount DESC
        LIMIT 1");
    
    // Get agent commission tier
    $agent_tier = db_query_row("SELECT * FROM commission_tiers 
        WHERE tier_type = 'agent' 
        AND currency_code = '" . db_escape($currency) . "'
        AND min_amount <= " . db_escape($amount) . "
        AND (max_amount >= " . db_escape($amount) . " OR max_amount IS NULL)
        AND is_active = 1
        ORDER BY min_amount DESC
        LIMIT 1");
    
    $customer_commission = 0;
    $agent_commission = 0;
    
    if ($customer_tier) {
        if ($customer_tier['commission_type'] === 'fixed') {
            $customer_commission = floatval($customer_tier['commission_value']);
        } else {
            $customer_commission = $amount * (floatval($customer_tier['commission_value']) / 100);
        }
    }
    
    if ($agent_tier) {
        if ($agent_tier['commission_type'] === 'fixed') {
            $agent_commission = floatval($agent_tier['commission_value']);
        } else {
            $agent_commission = $amount * (floatval($agent_tier['commission_value']) / 100);
        }
    }
    
    return [
        'customer_commission' => round($customer_commission, 2),
        'agent_commission' => round($agent_commission, 2)
    ];
}

/**
 * Update remittance status
 */
function update_status() {
    require_permission('approve_remittance');
    
    $remittance_id = $_POST['remittance_id'] ?? '';
    $status = sanitize($_POST['status'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    
    if (empty($remittance_id) || empty($status)) {
        send_json(['success' => false, 'message' => 'Remittance ID and status are required']);
    }
    
    $user_id = get_user_id();
    
    $sql = "UPDATE remittances SET 
            status = '" . db_escape($status) . "',
            approved_by = " . db_escape($user_id) . ",
            approved_at = NOW(),
            approval_notes = '" . db_escape($notes) . "',
            updated_at = NOW()
            WHERE id = " . db_escape($remittance_id);
    
    if (db_query($sql)) {
        log_activity($user_id, 'update_remittance_status', "Updated remittance status to: $status", 'remittances');
        send_json(['success' => true, 'message' => 'Remittance status updated successfully']);
    } else {
        send_json(['success' => false, 'message' => 'Failed to update remittance status']);
    }
}
