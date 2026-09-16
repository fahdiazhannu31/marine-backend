-- ============================================================================
-- Generate seats for LA BRISA (boat_id = 9)
-- 116 seats total: rows 1-14 have ABCD (left) + EFGH (right) = 8 seats each
-- Row 15 has only EFGH (right) = 4 seats
-- ============================================================================

-- First, delete any existing seats for boat_id = 9 (LA BRISA) to avoid duplicates
DELETE FROM seat WHERE boat_id = 9;

-- Generate seats for rows 1-14 (8 seats per row: A,B,C,D,E,F,G,H)
INSERT INTO seat (boat_id, seat_number, status)
SELECT 
    9 as boat_id,
    CONCAT(row_num, seat_letter) as seat_number,
    'available' as status
FROM (
    SELECT 1 as row_num UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL
    SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL
    SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12 UNION ALL
    SELECT 13 UNION ALL SELECT 14
) AS row_numbers
CROSS JOIN (
    SELECT 'A' as seat_letter UNION ALL SELECT 'B' UNION ALL SELECT 'C' UNION ALL SELECT 'D' UNION ALL
    SELECT 'E' UNION ALL SELECT 'F' UNION ALL SELECT 'G' UNION ALL SELECT 'H'
) AS letters
ORDER BY row_num, seat_letter;

-- Generate seats for row 15 (only 4 seats: E,F,G,H on the right side)
INSERT INTO seat (boat_id, seat_number, status)
VALUES
    (9, '15E', 'available'),
    (9, '15F', 'available'),
    (9, '15G', 'available'),
    (9, '15H', 'available');

-- Verify the count (should be 116 total)
SELECT 
    boat_id,
    COUNT(*) as total_seats,
    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_seats,
    SUM(CASE WHEN status = 'booked' THEN 1 ELSE 0 END) as booked_seats
FROM seat
WHERE boat_id = 9
GROUP BY boat_id;

-- Show sample seats to verify
SELECT * FROM seat WHERE boat_id = 9 ORDER BY CAST(SUBSTRING(seat_number, 1, LENGTH(seat_number) - 1) AS UNSIGNED), seat_number LIMIT 20;
