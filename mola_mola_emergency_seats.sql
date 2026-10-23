-- ============================================================================
-- Insert emergency row 9 seats for MOLA-MOLA / MARINA SRIKANDI boat
-- These seats (9A-9J) are reserved for emergency / special guest use.
-- They are hidden in the seat map until all regular seats are full.
-- ============================================================================

-- Get the boat_id dynamically by name
SET @mola_boat_id = (
    SELECT id FROM boat
    WHERE LOWER(boat_name) LIKE '%mola%'
    ORDER BY id
    LIMIT 1
);

SELECT @mola_boat_id AS mola_mola_boat_id;

-- Safety: only insert if boat found
-- Delete existing row 9 seats first to avoid duplicates
DELETE FROM seat
WHERE boat_id = @mola_boat_id
  AND seat_number REGEXP '^9[A-J]$';

-- Insert 10 emergency seats: 9A through 9J
INSERT INTO seat (boat_id, seat_number, status) VALUES
(@mola_boat_id, '9A', 'available'),
(@mola_boat_id, '9B', 'available'),
(@mola_boat_id, '9C', 'available'),
(@mola_boat_id, '9D', 'available'),
(@mola_boat_id, '9E', 'available'),
(@mola_boat_id, '9F', 'available'),
(@mola_boat_id, '9G', 'available'),
(@mola_boat_id, '9H', 'available'),
(@mola_boat_id, '9I', 'available'),
(@mola_boat_id, '9J', 'available');

-- Verify
SELECT id, boat_id, seat_number, status
FROM seat
WHERE boat_id = @mola_boat_id
  AND seat_number REGEXP '^9[A-J]$'
ORDER BY seat_number;
