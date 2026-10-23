-- ============================================================================
-- Insert row 9 seats (9A-9J) for MOLA-MOLA / MARINA SRIKANDI boat
-- Row 9 is used for manual assignment via Add Passenger (special/overflow guests)
-- ============================================================================

SET @mola_boat_id = (
    SELECT id FROM boat
    WHERE LOWER(boat_name) LIKE '%mola%'
    ORDER BY id
    LIMIT 1
);

SELECT @mola_boat_id AS mola_mola_boat_id;

-- Remove existing row 9 seats to avoid duplicates
DELETE FROM seat
WHERE boat_id = @mola_boat_id
  AND seat_number IN ('9A','9B','9C','9D','9E','9F','9G','9H','9I','9J');

-- Insert 10 seats: 9A through 9J
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
  AND seat_number IN ('9A','9B','9C','9D','9E','9F','9G','9H','9I','9J')
ORDER BY seat_number;
