-- Add sender_relation_to_receiver field to remittances table
-- This field stores the relationship between sender and receiver

USE rms_db;

ALTER TABLE remittances 
ADD COLUMN sender_relation_to_receiver VARCHAR(50) AFTER sender_id_number;

SELECT 'Sender relation field added successfully!' as Status;
