-- Add 'confirmed' column to manifest_uploads table
-- Run this on production database after pulling the latest code

ALTER TABLE `manifest_uploads` 
ADD COLUMN `confirmed` TINYINT(1) NOT NULL DEFAULT 0 
AFTER `vendor_count`;

-- Optional: Add index if needed for performance
-- CREATE INDEX idx_manifest_uploads_confirmed ON manifest_uploads(confirmed);
