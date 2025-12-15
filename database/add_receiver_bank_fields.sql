-- Add receiver bank account fields to remittances table
-- Run this migration script to update existing database

USE rms_db;

ALTER TABLE remittances 
ADD COLUMN receiver_bank_name VARCHAR(100) AFTER receiver_id_number,
ADD COLUMN receiver_account_number VARCHAR(50) AFTER receiver_bank_name,
ADD COLUMN receiver_account_holder VARCHAR(100) AFTER receiver_account_number;

SELECT '✅ Migration completed successfully!' as Status;
SELECT 'Receiver bank fields added to remittances table' as Details;
