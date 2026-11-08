-- ============================================================================
-- Check Marina Srikandi 2 Seat Data Issue
-- ============================================================================
-- Problem: Boarding pass shows passenger on seat 2D, but layout only has 2A, 2B, 2E, 2F

-- 1. Find Marina Srikandi 2 boat_id
SELECT id, boat_name, capacity 
FROM boat 
WHERE boat_name LIKE '%Marina Srikandi 2%' OR boat_name LIKE '%MARINA SRIKANDI 2%';

-- Expected result: boat_id (let's assume it's 15 or similar)

-- 2. Check all seats for Marina Srikandi 2 row 2
-- Replace {BOAT_ID} with actual boat_id from query above
SELECT 
    bs.id,
    bs.boat_id,
    b.boat_name,
    bs.seat_number,
    bs.seat_letter,
    bs.seat_row
FROM boat_seats bs
JOIN boat b ON b.id = bs.boat_id
WHERE b.boat_name LIKE '%Marina Srikandi 2%'
AND bs.seat_row = 2
ORDER BY bs.seat_number;

-- Expected: Should only show 2A, 2B, 2E, 2F
-- Issue: Will show 2C, 2D also (wrong!)

-- 3. Check if any bookings/manifest tickets use seats 2C or 2D
SELECT 
    mt.id,
    mt.upload_id,
    mt.seat_number,
    mt.passenger_name,
    mu.boat_name,
    mu.trip_date
FROM manifest_tickets mt
JOIN manifest_uploads mu ON mu.id = mt.upload_id
WHERE mu.boat_name LIKE '%Marina Srikandi 2%'
AND mt.seat_number IN ('2C', '2D')
ORDER BY mu.trip_date DESC;

-- 4. Count all seats per row for Marina Srikandi 2
SELECT 
    bs.seat_row,
    GROUP_CONCAT(bs.seat_number ORDER BY bs.seat_number SEPARATOR ', ') as seats,
    COUNT(*) as total_seats
FROM boat_seats bs
JOIN boat b ON b.id = bs.boat_id
WHERE b.boat_name LIKE '%Marina Srikandi 2%'
GROUP BY bs.seat_row
ORDER BY bs.seat_row;

-- Expected layout:
-- Row 1: 1A, 1B, 1E, 1F (4 seats)
-- Row 2: 2A, 2B, 2E, 2F (4 seats) ← should NOT have 2C, 2D
-- Row 3-14: XA, XB, XC, XD, XE, XF (6 seats per row)

-- ============================================================================
-- FIX: Delete wrong seats (2C, 2D) if they exist
-- ============================================================================

-- CAUTION: Only run this AFTER checking no bookings use these seats!

-- Step 1: Backup affected bookings (if any)
CREATE TABLE IF NOT EXISTS backup_wrong_seats_marina_srikandi2 AS
SELECT 
    mt.*,
    mu.boat_name,
    mu.trip_date
FROM manifest_tickets mt
JOIN manifest_uploads mu ON mu.id = mt.upload_id
WHERE mu.boat_name LIKE '%Marina Srikandi 2%'
AND mt.seat_number IN ('2C', '2D');

-- Step 2: Check if backup has data
SELECT COUNT(*) as affected_bookings FROM backup_wrong_seats_marina_srikandi2;
-- If > 0, you need to reassign these passengers to correct seats first!

-- Step 3: Delete wrong seats from boat_seats table
-- ONLY run this if affected_bookings = 0 OR after reassigning passengers
/*
DELETE bs
FROM boat_seats bs
JOIN boat b ON b.id = bs.boat_id
WHERE b.boat_name LIKE '%Marina Srikandi 2%'
AND bs.seat_number IN ('2C', '2D');
*/

-- Step 4: Verify deletion
SELECT 
    bs.seat_row,
    GROUP_CONCAT(bs.seat_number ORDER BY bs.seat_number SEPARATOR ', ') as seats,
    COUNT(*) as total_seats
FROM boat_seats bs
JOIN boat b ON b.id = bs.boat_id
WHERE b.boat_name LIKE '%Marina Srikandi 2%'
GROUP BY bs.seat_row
ORDER BY bs.seat_row;

-- Row 2 should now show: 2A, 2B, 2E, 2F (4 seats only)

-- ============================================================================
-- ALTERNATIVE: If passengers already booked on 2C/2D, reassign them
-- ============================================================================

-- Option A: Move 2C → 2E, 2D → 2F (if 2E/2F available)
/*
UPDATE manifest_tickets mt
JOIN manifest_uploads mu ON mu.id = mt.upload_id
SET mt.seat_number = '2E'
WHERE mu.boat_name LIKE '%Marina Srikandi 2%'
AND mt.seat_number = '2C'
AND NOT EXISTS (
    SELECT 1 FROM manifest_tickets mt2 
    WHERE mt2.upload_id = mt.upload_id AND mt2.seat_number = '2E'
);

UPDATE manifest_tickets mt
JOIN manifest_uploads mu ON mu.id = mt.upload_id
SET mt.seat_number = '2F'
WHERE mu.boat_name LIKE '%Marina Srikandi 2%'
AND mt.seat_number = '2D'
AND NOT EXISTS (
    SELECT 1 FROM manifest_tickets mt2 
    WHERE mt2.upload_id = mt.upload_id AND mt2.seat_number = '2F'
);
*/

-- Option B: Find any available seat in same upload and reassign
-- (Manual process, check available seats first)

-- ============================================================================
-- PREVENTION: Add constraint to prevent future wrong seat assignments
-- ============================================================================

-- This would require a more complex schema with seat validation logic
-- For now, the layout in React component will prevent UI selection of wrong seats
-- But manifest upload from Excel could still have wrong data

-- Recommendation: Add validation in ManifestUploadController.php
-- to reject seats that don't exist in boat_seats table

-- ============================================================================
-- END OF DIAGNOSTIC SCRIPT
-- ============================================================================
