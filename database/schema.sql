-- =====================================================
-- RMS (Remittance Management System) - Complete Database Schema
-- Version: 3.0 - Bank System Redesign
-- =====================================================

DROP DATABASE IF EXISTS rms_db;
CREATE DATABASE rms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rms_db;

-- =====================================================
-- 1. ROLES TABLE
-- =====================================================
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role_name (role_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 2. PERMISSIONS TABLE
-- =====================================================
CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    permission_name VARCHAR(100) NOT NULL,
    permission_code VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    module VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_module (module),
    INDEX idx_permission_code (permission_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 3. ROLE_PERMISSIONS TABLE (Junction)
-- =====================================================
CREATE TABLE role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_permission (role_id, permission_id),
    INDEX idx_role_id (role_id),
    INDEX idx_permission_id (permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 4. BRANCHES TABLE
-- =====================================================
CREATE TABLE branches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    branch_name VARCHAR(100) NOT NULL,
    branch_code VARCHAR(20) NOT NULL UNIQUE,
    location TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    manager_name VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_branch_code (branch_code),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 5. USERS TABLE
-- =====================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    role_id INT NOT NULL,
    branch_id INT,
    is_active TINYINT(1) DEFAULT 1,
    is_suspended TINYINT(1) DEFAULT 0,
    two_fa_enabled TINYINT(1) DEFAULT 0,
    two_fa_secret VARCHAR(100),
    last_login TIMESTAMP NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role_id (role_id),
    INDEX idx_branch_id (branch_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 6. BANKS TABLE (NEW)
-- =====================================================
CREATE TABLE banks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_name VARCHAR(100) NOT NULL UNIQUE,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_bank_name (bank_name),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 7. BANK ACCOUNTS TABLE (NEW)
-- =====================================================
CREATE TABLE bank_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_number VARCHAR(50) NOT NULL UNIQUE,
    bank_id INT NOT NULL,
    account_holder_id INT NOT NULL,
    balance DECIMAL(15, 2) DEFAULT 0.00,
    initial_balance DECIMAL(15, 2) DEFAULT 0.00,
    currency_code VARCHAR(3) DEFAULT 'USD',
    is_default TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bank_id) REFERENCES banks(id) ON DELETE RESTRICT,
    FOREIGN KEY (account_holder_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_account_number (account_number),
    INDEX idx_bank_id (bank_id),
    INDEX idx_account_holder (account_holder_id),
    INDEX idx_is_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 8. BANK ACCOUNT ACTIVITY TABLE (NEW)
-- =====================================================
CREATE TABLE bank_account_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_account_id INT NOT NULL,
    transaction_type ENUM('initial_balance', 'settlement', 'remittance') NOT NULL,
    transaction_direction ENUM('credit', 'debit') NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    balance_before DECIMAL(15, 2) NOT NULL,
    balance_after DECIMAL(15, 2) NOT NULL,
    reference_type VARCHAR(50),
    reference_id INT,
    description TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_bank_account (bank_account_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_created_at (created_at),
    INDEX idx_reference (reference_type, reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 9. COMMISSION_TIERS TABLE
-- =====================================================
CREATE TABLE commission_tiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tier_name VARCHAR(100) NOT NULL,
    tier_type ENUM('customer', 'agent') NOT NULL,
    min_amount DECIMAL(15, 2) NOT NULL,
    max_amount DECIMAL(15, 2),
    commission_type ENUM('percentage', 'fixed') NOT NULL,
    commission_value DECIMAL(10, 4) NOT NULL,
    currency_code VARCHAR(3) DEFAULT 'USD',
    is_active TINYINT(1) DEFAULT 1,
    created_by INT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_tier_type (tier_type),
    INDEX idx_currency (currency_code),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 10. COMMISSION_CHANGE_LOG TABLE
-- =====================================================
CREATE TABLE commission_change_log (
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
-- 11. REMITTANCES TABLE
-- =====================================================
CREATE TABLE remittances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(50) NOT NULL UNIQUE,
    sender_name VARCHAR(100) NOT NULL,
    sender_phone VARCHAR(20),
    sender_email VARCHAR(100),
    sender_address TEXT,
    sender_id_type VARCHAR(50),
    sender_id_number VARCHAR(50),
    receiver_name VARCHAR(100) NOT NULL,
    receiver_phone VARCHAR(20),
    receiver_email VARCHAR(100),
    receiver_address TEXT,
    receiver_id_type VARCHAR(50),
    receiver_id_number VARCHAR(50),
    receiver_bank_name VARCHAR(100),
    receiver_account_number VARCHAR(50),
    receiver_account_holder VARCHAR(100),
    amount_sent DECIMAL(15, 2) NOT NULL,
    currency_sent VARCHAR(3) DEFAULT 'USD',
    amount_received DECIMAL(15, 2) NOT NULL,
    currency_received VARCHAR(3) DEFAULT 'USD',
    exchange_rate DECIMAL(10, 6) NOT NULL DEFAULT 1.000000,
    customer_commission DECIMAL(10, 2) DEFAULT 0.00,
    agent_commission DECIMAL(10, 2) DEFAULT 0.00,
    total_commission DECIMAL(10, 2) DEFAULT 0.00,
    status ENUM('pending', 'approved', 'completed', 'rejected', 'cancelled') DEFAULT 'pending',
    is_flagged TINYINT(1) DEFAULT 0,
    flag_reason TEXT,
    requires_approval TINYINT(1) DEFAULT 0,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    approval_notes TEXT,
    agent_id INT NOT NULL,
    bank_account_id INT NULL,
    branch_id INT NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE RESTRICT,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_status (status),
    INDEX idx_agent_id (agent_id),
    INDEX idx_bank_account_id (bank_account_id),
    INDEX idx_branch_id (branch_id),
    INDEX idx_is_flagged (is_flagged),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 12. SETTLEMENTS TABLE (UPDATED)
-- =====================================================
CREATE TABLE settlements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    settlement_code VARCHAR(50) NOT NULL UNIQUE,
    agent_id INT NOT NULL,
    bank_account_id INT NOT NULL,
    requested_amount DECIMAL(15, 2) NOT NULL,
    currency_code VARCHAR(3) DEFAULT 'USD',
    settlement_type ENUM('full', 'partial') DEFAULT 'full',
    payment_method ENUM('bank_transfer', 'cash', 'cheque', 'mobile_money') NOT NULL,
    reference_number VARCHAR(100),
    status ENUM('pending', 'approved', 'completed', 'rejected') DEFAULT 'pending',
    approved_by INT,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT,
    completed_by INT,
    completed_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id) ON DELETE RESTRICT,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (completed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_settlement_code (settlement_code),
    INDEX idx_agent_id (agent_id),
    INDEX idx_bank_account (bank_account_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 13. NOTIFICATIONS_LOG TABLE
-- =====================================================
CREATE TABLE notifications_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    notification_type ENUM('sms', 'email', 'system') NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    recipient VARCHAR(100) NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    error_message TEXT,
    related_module VARCHAR(50),
    related_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_notification_type (notification_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 14. ACTIVITY_LOG TABLE (Enhanced Audit Trail)
-- =====================================================
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    module VARCHAR(50),
    ip_address VARCHAR(45),
    user_agent TEXT,
    request_method VARCHAR(10),
    request_url TEXT,
    session_id VARCHAR(100),
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low',
    old_values JSON,
    new_values JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_module (module),
    INDEX idx_created_at (created_at),
    INDEX idx_severity (severity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 15. OTP_CODES TABLE
-- =====================================================
CREATE TABLE otp_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    otp_code VARCHAR(10) NOT NULL,
    purpose ENUM('login', 'transaction', 'password_reset') NOT NULL,
    is_used TINYINT(1) DEFAULT 0,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_purpose (purpose),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 16. SYSTEM_SETTINGS TABLE
-- =====================================================
CREATE TABLE system_settings (
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

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Insert Roles
INSERT INTO roles (role_name, description) VALUES
('Admin', 'Full system access and control'),
('Branch Manager', 'Branch oversight and approval authority'),
('Agent', 'Remittance processing agent');

-- Insert Permissions
INSERT INTO permissions (permission_name, permission_code, description, module) VALUES
-- Dashboard
('View Dashboard', 'view_dashboard', 'Access to main dashboard', 'dashboard'),

-- Remittances
('View Remittances', 'view_remittances', 'View all remittances', 'remittances'),
('View Own Remittances', 'view_own_remittances', 'View own remittances only', 'remittances'),
('Create Remittance', 'create_remittance', 'Create new remittance transactions', 'remittances'),
('Edit Remittance', 'edit_remittance', 'Edit remittance details', 'remittances'),
('Delete Remittance', 'delete_remittance', 'Delete remittance transactions', 'remittances'),
('Approve Remittance', 'approve_remittance', 'Approve flagged remittances', 'remittances'),
('Reject Remittance', 'reject_remittance', 'Reject flagged remittances', 'remittances'),

-- Settlements
('View Settlements', 'view_settlements', 'View settlement requests', 'settlements'),
('Request Settlement', 'request_settlement', 'Request balance settlement', 'settlements'),
('Approve Settlement', 'approve_settlement', 'Approve settlement requests', 'settlements'),
('Complete Settlement', 'complete_settlement', 'Mark settlements as completed', 'settlements'),

-- Branches
('View Branches', 'view_branches', 'View branch information', 'branches'),
('Create Branch', 'create_branch', 'Create new branches', 'branches'),
('Edit Branch', 'edit_branch', 'Edit branch details', 'branches'),
('Delete Branch', 'delete_branch', 'Delete branches', 'branches'),

-- Users
('View Users', 'view_users', 'View user accounts', 'users'),
('Create User', 'create_user', 'Create new user accounts', 'users'),
('Edit User', 'edit_user', 'Edit user details', 'users'),
('Delete User', 'delete_user', 'Delete user accounts', 'users'),
('Suspend User', 'suspend_user', 'Suspend/unsuspend users', 'users'),

-- Roles & Permissions
('View Roles', 'view_roles', 'View system roles', 'roles'),
('Manage Roles', 'manage_roles', 'Create, edit, delete roles', 'roles'),
('Assign Permissions', 'assign_permissions', 'Assign permissions to roles', 'roles'),

-- Commission Management
('View Commission Tiers', 'view_commission_tiers', 'View commission tier settings', 'commission'),
('Manage Commission Tiers', 'manage_commission_tiers', 'Create, edit, delete commission tiers', 'commission'),

-- Banking
('View Banks', 'view_banks', 'View banks', 'banking'),
('Manage Banks', 'manage_banks', 'Create, edit, delete banks', 'banking'),
('View Bank Accounts', 'view_bank_accounts', 'View user bank accounts', 'banking'),
('Manage Bank Accounts', 'manage_bank_accounts', 'Create, edit, delete bank accounts', 'banking'),

-- Reports
('View Reports', 'view_reports', 'View system reports', 'reports'),
('Export Reports', 'export_reports', 'Export reports to file', 'reports'),

-- Audit
('View Activity Log', 'view_activity_log', 'View system activity log', 'audit'),
('View Audit Log', 'view_audit_log', 'View full system audit trail', 'audit'),
('Export Audit Log', 'export_audit_log', 'Export audit log reports', 'audit');

-- Assign permissions to Admin role
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions;

-- Assign permissions to Branch Manager role
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE permission_code IN (
    'view_dashboard', 'view_remittances', 'approve_remittance', 'reject_remittance',
    'view_settlements', 'approve_settlement', 'complete_settlement',
    'view_branches', 'view_users', 'view_roles', 'view_commission_tiers',
    'view_banks', 'view_bank_accounts', 'view_reports', 'view_activity_log', 'view_audit_log'
);

-- Assign permissions to Agent role
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE permission_code IN (
    'view_dashboard', 'view_own_remittances', 'create_remittance', 'request_settlement'
);

-- Insert Default Branch
INSERT INTO branches (branch_name, branch_code, location, manager_name) VALUES
('Main Branch', 'BR001', 'Head Office', 'System Administrator');

-- Insert Default Admin User (password: admin123)
INSERT INTO users (username, password, full_name, email, role_id, branch_id) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@rms.local', 1, 1);

-- Insert Default Commission Tiers
INSERT INTO commission_tiers (tier_name, tier_type, min_amount, max_amount, commission_type, commission_value, currency_code, created_by) VALUES
-- Customer Tiers
('Customer Tier 1', 'customer', 0.00, 1000.00, 'fixed', 5.00, 'USD', 1),
('Customer Tier 2', 'customer', 1000.01, 10000.00, 'percentage', 0.5, 'USD', 1),
('Customer Tier 3', 'customer', 10000.01, NULL, 'percentage', 0.3, 'USD', 1),

-- Agent Tiers
('Agent Tier 1', 'agent', 0.00, 1000.00, 'fixed', 2.00, 'USD', 1),
('Agent Tier 2', 'agent', 1000.01, 10000.00, 'percentage', 0.2, 'USD', 1),
('Agent Tier 3', 'agent', 10000.01, NULL, 'percentage', 0.15, 'USD', 1);

-- Insert System Settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('large_transfer_threshold', '50000', 'number', 'Amount threshold for flagging large transfers', 0),
('enable_2fa', '1', 'boolean', 'Enable two-factor authentication', 1),
('default_currency', 'USD', 'string', 'Default system currency', 1),
('session_timeout', '1800', 'number', 'Session timeout in seconds', 0),
('app_name', 'RMS', 'string', 'Application name', 1),
('company_name', 'Remittance Management System', 'string', 'Company name', 1);

-- Insert Sample Banks
INSERT INTO banks (bank_name, created_by) VALUES
('Premier Bank', 1),
('Amal Bank', 1),
('Salaam African Bank', 1);

-- =====================================================
-- END OF SCHEMA
-- =====================================================

SELECT '✅ Database created successfully!' as Status;
SELECT 'Default Login: admin / admin123' as Credentials;
SELECT 'Please change the default password after login!' as Warning;
