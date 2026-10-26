-- Add KKM and GUARD columns to manifest_uploads table
-- Run this on VPS database

ALTER TABLE `manifest_uploads` 
ADD COLUMN `kkm_name` VARCHAR(200) NULL AFTER `gro_name`,
ADD COLUMN `guard_name` VARCHAR(200) NULL AFTER `kkm_name`;

-- Verify columns were added
DESCRIBE `manifest_uploads`;
