-- ============================================================================
-- RESTORE DELETED SCHEDULE - La Luna 03 Nov 2026 08:00
-- ============================================================================
-- Use this script to restore a deleted schedule that still has manifest data
-- ============================================================================

-- Step 1: Find orphaned manifest uploads (schedule not exists)
SELECT 
    mu.id as upload_id,
    mu.schedule_id,
    mu.boat_name,
    mu.trip_date,
    mu.direction,
    mu.total_pax,
    COUNT(mt.id) as total_tickets,
    SUM(CASE WHEN mt.checked_in = 1 THEN 1 ELSE 0 END) as checked_in_count
FROM manifest_uploads mu
LEFT JOIN schedule s ON s.id = mu.schedule_id
LEFT JOIN manifest_tickets mt ON mt.upload_id = mu.id
WHERE s.id IS NULL  -- schedule doesn't exist
AND mu.trip_date = '2026-11-03'
AND mu.boat_name LIKE '%La Luna%'
GROUP BY mu.id;

-- Step 2: Get boat_id for La Luna
SELECT id, boat_name, capacity 
FROM boat 
WHERE boat_name LIKE '%La Luna%';

-- Step 3: Re-create the deleted schedule
-- IMPORTANT: Use the EXACT schedule_id from manifest_uploads.schedule_id
-- Replace values below with actual data:
-- - {SCHEDULE_ID} = the deleted schedule ID (from manifest_uploads.schedule_id)
-- - {BOAT_ID} = La Luna boat ID (from query above, usually 10)

INSERT INTO schedule (id, boat_id, type, date, created_at, updated_at)
VALUES 
({SCHEDULE_ID}, {BOAT_ID}, 'DEPARTURE', '2026-11-03 08:00:00', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    boat_id = {BOAT_ID},
    type = 'DEPARTURE',
    date = '2026-11-03 08:00:00',
    updated_at = NOW();

-- Example (adjust values):
-- INSERT INTO schedule (id, boat_id, type, date, created_at, updated_at)
-- VALUES 
-- (456, 10, 'DEPARTURE', '2026-11-03 08:00:00', NOW(), NOW())
-- ON DUPLICATE KEY UPDATE
--     boat_id = 10,
--     type = 'DEPARTURE',
--     date = '2026-11-03 08:00:00',
--     updated_at = NOW();

-- Step 4: Verify restoration
SELECT 
    s.id as schedule_id,
    s.boat_id,
    b.boat_name,
    s.type,
    s.date,
    mu.id as upload_id,
    mu.total_pax,
    COUNT(mt.id) as tickets,
    SUM(CASE WHEN mt.checked_in = 1 THEN 1 ELSE 0 END) as checked_in
FROM schedule s
JOIN boat b ON b.id = s.boat_id
LEFT JOIN manifest_uploads mu ON mu.schedule_id = s.id
LEFT JOIN manifest_tickets mt ON mt.upload_id = mu.id
WHERE s.date = '2026-11-03'
AND b.boat_name LIKE '%La Luna%'
GROUP BY s.id, mu.id;

-- ============================================================================
-- PREVENTION: Add foreign key constraint with RESTRICT to prevent deletion
-- ============================================================================
-- This will prevent schedule deletion if manifest_uploads still references it
-- (Optional - run only if you want to prevent future accidental deletions)

-- Check existing constraint
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_NAME = 'manifest_uploads'
AND COLUMN_NAME = 'schedule_id';

-- Add constraint if not exists (uncomment to use)
-- ALTER TABLE manifest_uploads
-- ADD CONSTRAINT fk_manifest_schedule
-- FOREIGN KEY (schedule_id) REFERENCES schedule(id)
-- ON DELETE RESTRICT
-- ON UPDATE CASCADE;

-- ============================================================================
