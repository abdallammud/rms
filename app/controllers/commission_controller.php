<?php
/**
 * Commission Tier Controller
 * Handles commission tier management with change logging
 */

// Check authentication and permission
auth_middleware('view_commission_tiers');

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'save':
            save_commission_tier();
            break;
        case 'get':
            get_commission_tier();
            break;
        case 'delete':
            delete_commission_tier();
            break;
        case 'toggle_status':
            toggle_tier_status();
            break;
        case 'list':
            list_commission_tiers();
            break;
        case 'get_change_log':
            get_change_log();
            break;
        default:
            send_json(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}

/**
 * Save commission tier (Create or Update)
 */
function save_commission_tier() {
    require_permission('manage_commission_tiers');
    
    $tier_id = $_POST['tier_id'] ?? '';
    $tier_name = sanitize($_POST['tier_name'] ?? '');
    $tier_type = sanitize($_POST['tier_type'] ?? '');
    $min_amount = floatval($_POST['min_amount'] ?? 0);
    $max_amount = !empty($_POST['max_amount']) ? floatval($_POST['max_amount']) : null;
    $commission_type = sanitize($_POST['commission_type'] ?? '');
    $commission_value = floatval($_POST['commission_value'] ?? 0);
    $currency_code = sanitize($_POST['currency_code'] ?? 'USD');
    
    // Validation
    if (empty($tier_name) || empty($tier_type) || empty($commission_type) || $commission_value <= 0) {
        send_json(['success' => false, 'message' => 'All fields are required']);
    }
    
    if ($min_amount < 0) {
        send_json(['success' => false, 'message' => 'Minimum amount cannot be negative']);
    }
    
    if ($max_amount !== null && $max_amount <= $min_amount) {
        send_json(['success' => false, 'message' => 'Maximum amount must be greater than minimum amount']);
    }
    
    $user_id = get_user_id();
    
    if (empty($tier_id)) {
        // Create new tier
        $sql = "INSERT INTO commission_tiers (tier_name, tier_type, min_amount, max_amount, 
                commission_type, commission_value, currency_code, created_by, created_at) 
                VALUES ('" . db_escape($tier_name) . "', 
                        '" . db_escape($tier_type) . "', 
                        $min_amount, 
                        " . ($max_amount !== null ? $max_amount : "NULL") . ", 
                        '" . db_escape($commission_type) . "', 
                        $commission_value, 
                        '" . db_escape($currency_code) . "', 
                        $user_id, 
                        NOW())";
        
        if (db_query($sql)) {
            log_activity($user_id, 'create_commission_tier', "Created commission tier: $tier_name", 'commission');
            send_json(['success' => true, 'message' => 'Commission tier created successfully']);
        } else {
            send_json(['success' => false, 'message' => 'Failed to create commission tier']);
        }
    } else {
        // Get old values for change log
        $old_tier = db_query_row("SELECT * FROM commission_tiers WHERE id = '" . db_escape($tier_id) . "'");
        
        if (!$old_tier) {
            send_json(['success' => false, 'message' => 'Commission tier not found']);
        }
        
        // Update tier
        $sql = "UPDATE commission_tiers SET 
                tier_name = '" . db_escape($tier_name) . "', 
                tier_type = '" . db_escape($tier_type) . "', 
                min_amount = $min_amount, 
                max_amount = " . ($max_amount !== null ? $max_amount : "NULL") . ", 
                commission_type = '" . db_escape($commission_type) . "', 
                commission_value = $commission_value, 
                currency_code = '" . db_escape($currency_code) . "', 
                updated_by = $user_id, 
                updated_at = NOW() 
                WHERE id = '" . db_escape($tier_id) . "'";
        
        if (db_query($sql)) {
            // Log changes
            $changes = [
                'tier_name' => [$old_tier['tier_name'], $tier_name],
                'tier_type' => [$old_tier['tier_type'], $tier_type],
                'min_amount' => [$old_tier['min_amount'], $min_amount],
                'max_amount' => [$old_tier['max_amount'], $max_amount],
                'commission_type' => [$old_tier['commission_type'], $commission_type],
                'commission_value' => [$old_tier['commission_value'], $commission_value],
                'currency_code' => [$old_tier['currency_code'], $currency_code]
            ];
            
            foreach ($changes as $field => $values) {
                if ($values[0] != $values[1]) {
                    $sql = "INSERT INTO commission_change_log (commission_tier_id, field_changed, old_value, new_value, changed_by, changed_at) 
                            VALUES ('" . db_escape($tier_id) . "', 
                                    '" . db_escape($field) . "', 
                                    '" . db_escape($values[0]) . "', 
                                    '" . db_escape($values[1]) . "', 
                                    $user_id, 
                                    NOW())";
                    db_query($sql);
                }
            }
            
            log_activity($user_id, 'update_commission_tier', "Updated commission tier: $tier_name", 'commission');
            send_json(['success' => true, 'message' => 'Commission tier updated successfully']);
        } else {
            send_json(['success' => false, 'message' => 'Failed to update commission tier']);
        }
    }
}

/**
 * Get commission tier details
 */
function get_commission_tier() {
    $tier_id = $_POST['tier_id'] ?? '';
    
    if (empty($tier_id)) {
        send_json(['success' => false, 'message' => 'Tier ID is required']);
    }
    
    $sql = "SELECT * FROM commission_tiers WHERE id = '" . db_escape($tier_id) . "'";
    $tier = db_query_row($sql);
    
    if ($tier) {
        send_json(['success' => true, 'data' => $tier]);
    } else {
        send_json(['success' => false, 'message' => 'Commission tier not found']);
    }
}

/**
 * Delete commission tier
 */
function delete_commission_tier() {
    require_permission('manage_commission_tiers');
    
    $tier_id = $_POST['tier_id'] ?? '';
    
    if (empty($tier_id)) {
        send_json(['success' => false, 'message' => 'Tier ID is required']);
    }
    
    // Get tier details for logging
    $tier = db_query_row("SELECT tier_name FROM commission_tiers WHERE id = '" . db_escape($tier_id) . "'");
    
    // Delete change log first
    db_query("DELETE FROM commission_change_log WHERE commission_tier_id = '" . db_escape($tier_id) . "'");
    
    // Delete tier
    $sql = "DELETE FROM commission_tiers WHERE id = '" . db_escape($tier_id) . "'";
    
    if (db_query($sql)) {
        log_activity(get_user_id(), 'delete_commission_tier', "Deleted commission tier: {$tier['tier_name']}", 'commission');
        send_json(['success' => true, 'message' => 'Commission tier deleted successfully']);
    } else {
        send_json(['success' => false, 'message' => 'Failed to delete commission tier']);
    }
}

/**
 * Toggle tier status
 */
function toggle_tier_status() {
    require_permission('manage_commission_tiers');
    
    $tier_id = $_POST['tier_id'] ?? '';
    $status = $_POST['status'] ?? 1;
    
    if (empty($tier_id)) {
        send_json(['success' => false, 'message' => 'Tier ID is required']);
    }
    
    $sql = "UPDATE commission_tiers SET is_active = '" . db_escape($status) . "', updated_at = NOW() WHERE id = '" . db_escape($tier_id) . "'";
    
    if (db_query($sql)) {
        $status_text = $status == 1 ? 'activated' : 'deactivated';
        $tier = db_query_row("SELECT tier_name FROM commission_tiers WHERE id = '" . db_escape($tier_id) . "'");
        log_activity(get_user_id(), 'toggle_commission_tier_status', "Commission tier {$status_text}: {$tier['tier_name']}", 'commission');
        send_json(['success' => true, 'message' => 'Commission tier status updated successfully']);
    } else {
        send_json(['success' => false, 'message' => 'Failed to update tier status']);
    }
}

/**
 * List all commission tiers
 */
function list_commission_tiers() {
    $sql = "SELECT ct.*, u1.full_name as created_by_name, u2.full_name as updated_by_name 
            FROM commission_tiers ct 
            LEFT JOIN users u1 ON ct.created_by = u1.id 
            LEFT JOIN users u2 ON ct.updated_by = u2.id 
            ORDER BY ct.tier_type, ct.min_amount";
    
    $tiers = db_query_all($sql);
    
    send_json(['success' => true, 'data' => $tiers]);
}

/**
 * Get change log for a tier
 */
function get_change_log() {
    $tier_id = $_POST['tier_id'] ?? '';
    
    if (empty($tier_id)) {
        send_json(['success' => false, 'message' => 'Tier ID is required']);
    }
    
    $sql = "SELECT ccl.*, u.full_name as changed_by_name 
            FROM commission_change_log ccl 
            LEFT JOIN users u ON ccl.changed_by = u.id 
            WHERE ccl.commission_tier_id = '" . db_escape($tier_id) . "' 
            ORDER BY ccl.changed_at DESC";
    
    $logs = db_query_all($sql);
    
    send_json(['success' => true, 'data' => $logs]);
}
