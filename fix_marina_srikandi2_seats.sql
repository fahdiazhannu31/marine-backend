-- ============================================================================
-- FIX Marina Srikandi 2 Seat Layout
-- ============================================================================
-- Problem: Database has wrong seats (1D, 1E, 2D, 2E instead of 1E, 1F, 2E, 2F)
-- Correct layout:
--   Row 1-2  : A, B, E, F (NOT D, E, A, B!)
--   Row 3-14 : A, B, C, D, E, F
-- ============================================================================

-- Get Marina Srikandi 2 boat_id
SET @boat_id = (SELECT id FROM boat WHERE boat_name LIKE '%Marina Srikandi 2%' LIMIT 1);
SELECT @boat_id AS marina_srikandi_2_boat_id;

-- ─── STEP 1: Backup current state ──────────────────────────────────────────

CREATE TABLE IF NOT EXISTS backup_marina_srikandi2_seats_before_fix AS
SELECT 
    s.*,
    b.boat_name
FROM seat s
JOIN boat b ON b.id = s.boat_id
WHERE b.id = @boat_id;

SELECT 'Backed up seats' AS status, COUNT(*) AS total_seats_backed_up
FROM backup_marina_srikandi2_seats_before_fix;

-- ─── STEP 2: Check for bookings on wrong seats ─────────────────────────────

-- Check manifest_tickets yang menggunakan seats yang akan dihapus (1D, 2D)
SELECT 
    mt.id,
    mt.upload_id,
    mt.passenger_name,
    mt.seat_number,
    mu.boat_name,
    mu.trip_date,
    mu.direction
FROM manifest_tickets mt
JOIN manifest_uploads mu ON mu.id = mt.upload_id
WHERE mu.boat_name LIKE '%Marina Srikandi 2%'
AND mt.seat_number IN ('1C', '1D', '2C', '2D')
ORDER BY mu.trip_date DESC;

-- If above query returns rows, you need to reassign those passengers first!

-- ─── STEP 3: Delete wrong seats from database ──────────────────────────────

-- IMPORTANT: Only run this after verifying no active bookings or after reassigning passengers

-- Delete seats that should not exist (1C, 1D, 2C, 2D)
DELETE FROM seat
WHERE boat_id = @boat_id
AND seat_number IN ('1C', '1D', '2C', '2D');

SELECT 'Deleted wrong seats (1C, 1D, 2C, 2D)' AS status;

-- ─── STEP 4: Add missing seats if they don't exist ─────────────────────────

-- Add 1F if missing
INSERT IGNORE INTO seat (boat_id, seat_number, status)
VALUES (@boat_id, '1F', 'available');

-- Add 2F if missing
INSERT IGNORE INTO seat (boat_id, seat_number, status)
VALUES (@boat_id, '2F', 'available');

SELECT 'Added missing seats (1F, 2F if not exist)' AS status;

-- ─── STEP 5: Verify final seat configuration ───────────────────────────────

SELECT 
    s.seat_number,
    s.status,
    CASE 
        WHEN s.seat_number LIKE '1%' THEN 1
        WHEN s.seat_number LIKE '2%' THEN 2
        ELSE CAST(SUBSTRING(s.seat_number, 1, 2) AS UNSIGNED)
    END AS row_num
FROM seat s
WHERE s.boat_id = @boat_id
AND (
    s.seat_number LIKE '1%' OR 
    s.seat_number LIKE '2%'
)
ORDER BY row_num, s.seat_number;

-- Expected result:
-- Row 1: 1A, 1B, 1E, 1F (4 seats)
-- Row 2: 2A, 2B, 2E, 2F (4 seats)

-- ─── STEP 6: Count total seats per row ─────────────────────────────────────

SELECT 
    CASE 
        WHEN seat_number LIKE '1%' THEN 1
        WHEN seat_number LIKE '2%' THEN 2
        WHEN seat_number LIKE '3%' THEN 3
        WHEN seat_number LIKE '4%' THEN 4
        WHEN seat_number LIKE '5%' THEN 5
        WHEN seat_number LIKE '6%' THEN 6
        WHEN seat_number LIKE '7%' THEN 7
        WHEN seat_number LIKE '8%' THEN 8
        WHEN seat_number LIKE '9%' THEN 9
        ELSE CAST(SUBSTRING(seat_number, 1, 2) AS UNSIGNED)
    END AS row_number,
    GROUP_CONCAT(seat_number ORDER BY seat_number SEPARATOR ', ') AS seats,
    COUNT(*) AS total_seats_in_row
FROM seat
WHERE boat_id = @boat_id
GROUP BY row_number
ORDER BY row_number;

-- Expected:
-- Row 1  : 1A, 1B, 1E, 1F (4 seats) ← NO 1C, NO 1D
-- Row 2  : 2A, 2B, 2E, 2F (4 seats) ← NO 2C, NO 2D
-- Row 3-14: XA, XB, XC, XD, XE, XF (6 seats per row)

-- ─── STEP 7: Final verification ────────────────────────────────────────────

SELECT 
    b.id,
    b.boat_name,
    b.capacity,
    COUNT(s.id) AS actual_seats,
    b.capacity - COUNT(s.id) AS diff
FROM boat b
LEFT JOIN seat s ON s.boat_id = b.id
WHERE b.id = @boat_id
GROUP BY b.id;

-- Expected: 80 seats total (2×4 + 12×6 = 8 + 72 = 80)

-- ============================================================================
-- OPTIONAL: Fix manifest_tickets if passengers were on wrong seats
-- ============================================================================

-- If passengers were assigned to 1D or 2D, you might want to reassign them
-- Example: Move 1D → 1F, 2D → 2F (only if target seat is available)

/*
-- Check available seats for each upload before reassigning
SELECT 
    mu.id AS upload_id,
    mu.boat_name,
    mu.trip_date,
    GROUP_CONCAT(DISTINCT mt.seat_number ORDER BY mt.seat_number) AS occupied_seats
FROM manifest_uploads mu
JOIN manifest_tickets mt ON mt.upload_id = mu.id
WHERE mu.boat_name LIKE '%Marina Srikandi 2%'
GROUP BY mu.id;

-- Manual reassignment (check availability first!)
UPDATE manifest_tickets
SET seat_number = '1F'
WHERE upload_id = {UPLOAD_ID}
AND seat_number = '1D'
AND NOT EXISTS (
    SELECT 1 FROM manifest_tickets mt2 
    WHERE mt2.upload_id = {UPLOAD_ID} AND mt2.seat_number = '1F'
);

UPDATE manifest_tickets
SET seat_number = '2F'
WHERE upload_id = {UPLOAD_ID}
AND seat_number = '2D'
AND NOT EXISTS (
    SELECT 1 FROM manifest_tickets mt2 
    WHERE mt2.upload_id = {UPLOAD_ID} AND mt2.seat_number = '2F'
);
*/

-- ============================================================================
-- ROLLBACK (if something goes wrong)
-- ============================================================================

-- To restore from backup:
/*
DELETE FROM seat WHERE boat_id = @boat_id;

INSERT INTO seat (boat_id, seat_number, status)
SELECT boat_id, seat_number, status
FROM backup_marina_srikandi2_seats_before_fix;
*/

-- ============================================================================
-- END OF FIX SCRIPT
-- ============================================================================
