<?php
/**
 * Bank Controller
 * Handles bank entity CRUD operations
 */

// Check authentication and permission
auth_middleware('view_banks');

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'save':
            save_bank();
            break;
        case 'get':
            get_bank();
            break;
        case 'delete':
            delete_bank();
            break;
        case 'list':
            list_banks();
            break;
        case 'toggle_status':
            toggle_bank_status();
            break;
        default:
            send_json(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}

/**
 * Save bank (Create or Update)
 */
function save_bank() {
    require_permission('manage_banks');
    
    $bank_id = $_POST['bank_id'] ?? '';
    $bank_name = sanitize($_POST['bank_name'] ?? '');
    
    // Validation
    if (empty($bank_name)) {
        send_json(['success' => false, 'message' => 'Bank name is required']);
    }
    
    // Check if bank already exists
    $check_sql = "SELECT id FROM banks WHERE bank_name = '" . db_escape($bank_name) . "'";
    if (!empty($bank_id)) {
        $check_sql .= " AND id != '" . db_escape($bank_id) . "'";
    }
    
    if (db_query_row($check_sql)) {
        send_json(['success' => false, 'message' => 'This bank name already exists']);
    }
    
    $created_by = get_user_id();
    
    if (empty($bank_id)) {
        // Create new bank
        $sql = "INSERT INTO banks (bank_name, created_by, created_at) 
                VALUES (
                    '" . db_escape($bank_name) . "',
                    $created_by,
                    NOW()
                )";
        
        if (db_query($sql)) {
            log_activity($created_by, 'create_bank', "Created bank: $bank_name", 'banking');
            send_json(['success' => true, 'message' => 'Bank created successfully']);
        } else {
            send_json(['success' => false, 'message' => 'Failed to create bank']);
        }
    } else {
        // Update existing bank
        $sql = "UPDATE banks SET 
                bank_name = '" . db_escape($bank_name) . "',
                updated_at = NOW()
                WHERE id = '" . db_escape($bank_id) . "'";
        
        if (db_query($sql)) {
            log_activity($created_by, 'update_bank', "Updated bank: $bank_name", 'banking');
            send_json(['success' => true, 'message' => 'Bank updated successfully']);
        } else {
            send_json(['success' => false, 'message' => 'Failed to update bank']);
        }
    }
}

/**
 * Get bank details
 */
function get_bank() {
    $bank_id = $_POST['bank_id'] ?? '';
    
    if (empty($bank_id)) {
        send_json(['success' => false, 'message' => 'Bank ID is required']);
    }
    
    $sql = "SELECT * FROM banks WHERE id = '" . db_escape($bank_id) . "'";
    $bank = db_query_row($sql);
    
    if ($bank) {
        send_json(['success' => true, 'data' => $bank]);
    } else {
        send_json(['success' => false, 'message' => 'Bank not found']);
    }
}

/**
 * Delete bank
 */
function delete_bank() {
    require_permission('manage_banks');
    
    $bank_id = $_POST['bank_id'] ?? '';
    
    if (empty($bank_id)) {
        send_json(['success' => false, 'message' => 'Bank ID is required']);
    }
    
    // Check if bank has any accounts
    $account_count = db_query_row("SELECT COUNT(*) as count FROM bank_accounts WHERE bank_id = '" . db_escape($bank_id) . "'");
    
    if ($account_count && $account_count['count'] > 0) {
        send_json(['success' => false, 'message' => 'Cannot delete bank with existing accounts. Please delete or reassign accounts first.']);
    }
    
    // Get bank details for logging
    $bank = db_query_row("SELECT * FROM banks WHERE id = '" . db_escape($bank_id) . "'");
    
    if (!$bank) {
        send_json(['success' => false, 'message' => 'Bank not found']);
    }
    
    $sql = "DELETE FROM banks WHERE id = '" . db_escape($bank_id) . "'";
    
    if (db_query($sql)) {
        log_activity(get_user_id(), 'delete_bank', "Deleted bank: {$bank['bank_name']}", 'banking');
        send_json(['success' => true, 'message' => 'Bank deleted successfully']);
    } else {
        send_json(['success' => false, 'message' => 'Failed to delete bank']);
    }
}

/**
 * List all banks
 */
function list_banks() {
    $sql = "SELECT b.*, u.full_name as created_by_name,
            (SELECT COUNT(*) FROM bank_accounts WHERE bank_id = b.id) as account_count
            FROM banks b
            LEFT JOIN users u ON b.created_by = u.id
            ORDER BY b.bank_name ASC";
    
    $banks = db_query_all($sql);
    
    send_json(['success' => true, 'data' => $banks]);
}

/**
 * Toggle bank status
 */
function toggle_bank_status() {
    require_permission('manage_banks');
    
    $bank_id = $_POST['bank_id'] ?? '';
    $status = $_POST['status'] ?? 1;
    
    if (empty($bank_id)) {
        send_json(['success' => false, 'message' => 'Bank ID is required']);
    }
    
    $sql = "UPDATE banks SET is_active = '" . db_escape($status) . "', updated_at = NOW() 
            WHERE id = '" . db_escape($bank_id) . "'";
    
    if (db_query($sql)) {
        $status_text = $status == 1 ? 'activated' : 'deactivated';
        log_activity(get_user_id(), 'toggle_bank_status', "Bank {$status_text}", 'banking');
        send_json(['success' => true, 'message' => 'Bank status updated successfully']);
    } else {
        send_json(['success' => false, 'message' => 'Failed to update bank status']);
    }
}
