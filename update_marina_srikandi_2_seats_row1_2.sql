-- Update Marina Srikandi 2 seat layout for rows 1-2
-- Change from 1D,1E to 1E,1F and 2D,2E to 2E,2F
-- Keep 1A, 1B, 2A, 2B as is

-- Get Marina Srikandi 2 boat ID
SET @boat_id = (SELECT id FROM boat WHERE LOWER(boat_name) LIKE '%marina%srikandi%2%' LIMIT 1);

-- Delete old seats 1C, 1D, 2C, 2D (if they exist)
DELETE FROM seat 
WHERE boat_id = @boat_id 
AND seat_number IN ('1C', '1D', '2C', '2D');

-- Update existing seats if they have old naming
UPDATE seat SET seat_number = '1E' WHERE boat_id = @boat_id AND seat_number = '1D';
UPDATE seat SET seat_number = '1F' WHERE boat_id = @boat_id AND seat_number = '1E';
UPDATE seat SET seat_number = '2E' WHERE boat_id = @boat_id AND seat_number = '2D';
UPDATE seat SET seat_number = '2F' WHERE boat_id = @boat_id AND seat_number = '2E';

-- Verify: show all seats for rows 1-2
SELECT seat_number, status 
FROM seat 
WHERE boat_id = @boat_id 
AND seat_number REGEXP '^[12][A-F]$'
ORDER BY 
  CAST(SUBSTRING(seat_number, 1, 1) AS UNSIGNED),
  SUBSTRING(seat_number, 2);

-- Expected result:
-- 1A, 1B, 1E, 1F
-- 2A, 2B, 2E, 2F
