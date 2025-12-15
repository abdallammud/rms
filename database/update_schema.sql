-- =====================================================
-- RMS Database Update Script
-- Adds: Commission Management, User Bank Accounts, Enhanced Audit Trail
-- =====================================================

USE rms_db;

-- =====================================================
-- 1. ADD NEW PERMISSIONS
-- =====================================================

-- Commission Management Permissions
INSERT INTO permissions (permission_name, permission_code, description, module, created_at) 
VALUES 
('View Commission Tiers', 'view_commission_tiers', 'View commission tier settings', 'commission', NOW()),
('Manage Commission Tiers', 'manage_commission_tiers', 'Create, edit, delete commission tiers', 'commission', NOW()),
('View Bank Accounts', 'view_bank_accounts', 'View user bank accounts', 'banking', NOW()),
('Manage Bank Accounts', 'manage_bank_accounts', 'Create, edit, delete bank accounts', 'banking', NOW()),
('View Audit Log', 'view_audit_log', 'View full system audit trail', 'audit', NOW()),
('Export Audit Log', 'export_audit_log', 'Export audit log reports', 'audit', NOW());

-- =====================================================
-- 2. GRANT PERMISSIONS TO ADMIN ROLE
-- =====================================================

-- Get Admin role ID
SET @admin_role_id = (SELECT id FROM roles WHERE role_name = 'Admin' LIMIT 1);

-- Add new permissions to Admin role
INSERT INTO role_permissions (role_id, permission_id)
SELECT @admin_role_id, id FROM permissions 
WHERE permission_code IN (
    'view_commission_tiers',
    'manage_commission_tiers',
    'view_bank_accounts',
    'manage_bank_accounts',
    'view_audit_log',
    'export_audit_log'
);

-- Grant view permissions to Branch Manager
SET @manager_role_id = (SELECT id FROM roles WHERE role_name = 'Branch Manager' LIMIT 1);

INSERT INTO role_permissions (role_id, permission_id)
SELECT @manager_role_id, id FROM permissions 
WHERE permission_code IN (
    'view_commission_tiers',
    'view_bank_accounts',
    'view_audit_log'
);

-- =====================================================
-- 3. CREATE USER BANK ACCOUNTS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS user_bank_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bank_name VARCHAR(100) NOT NULL,
    account_number VARCHAR(50) NOT NULL,
    account_holder_name VARCHAR(100) NOT NULL,
    branch_name VARCHAR(100),
    swift_code VARCHAR(20),
    iban VARCHAR(50),
    currency_code VARCHAR(3) DEFAULT 'USD',
    account_type ENUM('savings', 'current', 'business') DEFAULT 'current',
    is_primary TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_is_primary (is_primary),
    UNIQUE KEY unique_account (user_id, account_number, bank_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 4. CREATE MONTHLY RECONCILIATION TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS monthly_reconciliations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bank_account_id INT NOT NULL,
    month_year DATE NOT NULL,
    system_balance DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    actual_balance DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    difference DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    status ENUM('pending', 'matched', 'discrepancy', 'resolved') DEFAULT 'pending',
    notes TEXT,
    reconciled_by INT,
    reconciled_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (bank_account_id) REFERENCES user_bank_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (reconciled_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_month (user_id, month_year),
    UNIQUE KEY unique_reconciliation (user_id, bank_account_id, month_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 5. ENHANCE ACTIVITY LOG TABLE FOR FULL AUDIT TRAIL
-- =====================================================

-- Add additional audit fields if they don't exist
ALTER TABLE activity_log 
ADD COLUMN IF NOT EXISTS request_method VARCHAR(10) AFTER user_agent,
ADD COLUMN IF NOT EXISTS request_url TEXT AFTER request_method,
ADD COLUMN IF NOT EXISTS session_id VARCHAR(100) AFTER request_url,
ADD COLUMN IF NOT EXISTS severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low' AFTER session_id;

-- Add indexes for better performance
ALTER TABLE activity_log 
ADD INDEX IF NOT EXISTS idx_user_id (user_id),
ADD INDEX IF NOT EXISTS idx_action (action),
ADD INDEX IF NOT EXISTS idx_module (module),
ADD INDEX IF NOT EXISTS idx_created_at (created_at),
ADD INDEX IF NOT EXISTS idx_severity (severity);

-- =====================================================
-- 6. UPDATE COMMISSION_TIERS TABLE
-- =====================================================

-- Add created_by and updated_by for audit trail
ALTER TABLE commission_tiers
ADD COLUMN IF NOT EXISTS created_by INT AFTER is_active,
ADD COLUMN IF NOT EXISTS updated_by INT AFTER created_by,
ADD CONSTRAINT IF NOT EXISTS fk_commission_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
ADD CONSTRAINT IF NOT EXISTS fk_commission_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL;

-- =====================================================
-- 7. ADD MISSING PERMISSIONS FOR EXISTING MODULES
-- =====================================================

-- Add missing remittance permissions
INSERT IGNORE INTO permissions (permission_name, permission_code, description, module, created_at) 
VALUES 
('View Own Remittances', 'view_own_remittances', 'View own remittances only', 'remittances', NOW()),
('Approve Remittance', 'approve_remittance', 'Approve flagged remittances', 'remittances', NOW()),
('Reject Remittance', 'reject_remittance', 'Reject flagged remittances', 'remittances', NOW());

-- Grant to appropriate roles
INSERT INTO role_permissions (role_id, permission_id)
SELECT @admin_role_id, id FROM permissions 
WHERE permission_code IN ('approve_remittance', 'reject_remittance')
AND id NOT IN (SELECT permission_id FROM role_permissions WHERE role_id = @admin_role_id);

INSERT INTO role_permissions (role_id, permission_id)
SELECT @manager_role_id, id FROM permissions 
WHERE permission_code IN ('approve_remittance', 'reject_remittance')
AND id NOT IN (SELECT permission_id FROM role_permissions WHERE role_id = @manager_role_id);

-- Grant view_own_remittances to Agent role
SET @agent_role_id = (SELECT id FROM roles WHERE role_name = 'Agent' LIMIT 1);

INSERT INTO role_permissions (role_id, permission_id)
SELECT @agent_role_id, id FROM permissions 
WHERE permission_code = 'view_own_remittances'
AND id NOT IN (SELECT permission_id FROM role_permissions WHERE role_id = @agent_role_id);

-- =====================================================
-- 8. CREATE COMMISSION CHANGE LOG TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS commission_change_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commission_tier_id INT NOT NULL,
    field_changed VARCHAR(50) NOT NULL,
    old_value VARCHAR(100),
    new_value VARCHAR(100),
    changed_by INT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (commission_tier_id) REFERENCES commission_tiers(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_commission_tier (commission_tier_id),
    INDEX idx_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 9. UPDATE USERS TABLE FOR BANK RECONCILIATION
-- =====================================================

-- Add reconciliation tracking
ALTER TABLE users
ADD COLUMN IF NOT EXISTS last_reconciliation_date DATE AFTER two_fa_secret,
ADD COLUMN IF NOT EXISTS reconciliation_status ENUM('up_to_date', 'pending', 'overdue') DEFAULT 'pending' AFTER last_reconciliation_date;

-- =====================================================
-- 10. CREATE BALANCE ADJUSTMENT TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS balance_adjustments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_account_id INT NOT NULL,
    adjustment_type ENUM('add', 'subtract', 'correction') NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    reason TEXT NOT NULL,
    previous_balance DECIMAL(15, 2) NOT NULL,
    new_balance DECIMAL(15, 2) NOT NULL,
    approved_by INT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_account_id) REFERENCES user_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_account (user_account_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 11. ADD SYSTEM SETTINGS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    is_public TINYINT(1) DEFAULT 0,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('large_transfer_threshold', '50000', 'number', 'Amount threshold for flagging large transfers', 0),
('reconciliation_day', '5', 'number', 'Day of month for mandatory reconciliation', 0),
('enable_2fa', '1', 'boolean', 'Enable two-factor authentication', 1),
('default_currency', 'USD', 'string', 'Default system currency', 1),
('session_timeout', '1800', 'number', 'Session timeout in seconds', 0)
ON DUPLICATE KEY UPDATE setting_value = setting_value;

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Check new permissions
SELECT 'New Permissions Added:' as Info;
SELECT permission_name, permission_code, module 
FROM permissions 
WHERE module IN ('commission', 'banking', 'audit') 
ORDER BY module, permission_name;

-- Check new tables
SELECT 'New Tables Created:' as Info;
SHOW TABLES LIKE '%bank%';
SHOW TABLES LIKE '%reconciliation%';
SHOW TABLES LIKE '%adjustment%';
SHOW TABLES LIKE '%commission_change%';

-- Check role permissions count
SELECT 'Role Permissions Count:' as Info;
SELECT r.role_name, COUNT(rp.permission_id) as permission_count
FROM roles r
LEFT JOIN role_permissions rp ON r.id = rp.role_id
GROUP BY r.id, r.role_name
ORDER BY r.role_name;

-- =====================================================
-- END OF UPDATE SCRIPT
-- =====================================================

SELECT '✅ Database update completed successfully!' as Status;
SELECT 'Please review the changes and test the application.' as Note;
