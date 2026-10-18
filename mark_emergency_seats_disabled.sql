-- Mark emergency exit row seats as 'disabled' so they won't be auto-assigned.
-- These seats should remain empty by default for safety regulations.
-- ============================================================================

-- Mola-mola (boat_id will vary — use boat_name lookup)
UPDATE seat s
JOIN boat b ON b.id = s.boat_id
SET s.status = 'disabled'
WHERE b.boat_name LIKE '%MOLA%MOLA%'
  AND s.seat_number IN ('9A', '9B', '9C', '9D', '9E', '9F', '9G', '9H', '9I', '9J');

-- La Vela
UPDATE seat s
JOIN boat b ON b.id = s.boat_id
SET s.status = 'disabled'
WHERE b.boat_name LIKE '%LA VELA%'
  AND s.seat_number IN ('9D', '9E', '9F');

-- La Luna
UPDATE seat s
JOIN boat b ON b.id = s.boat_id
SET s.status = 'disabled'
WHERE b.boat_name LIKE '%LA LUNA%'
  AND s.seat_number IN ('9A', '9B');

-- La Brisa
UPDATE seat s
JOIN boat b ON b.id = s.boat_id
SET s.status = 'disabled'
WHERE b.boat_name LIKE '%LA BRISA%'
  AND s.seat_number IN ('1A', '1B', '1C', '1D', '1E', '1F', '1G', '1H');

-- Marina Srikandi 2
UPDATE seat s
JOIN boat b ON b.id = s.boat_id
SET s.status = 'disabled'
WHERE b.boat_name LIKE '%MARINA SRIKANDI 2%'
  AND s.seat_number IN ('1A', '1B', '1E', '1F');

-- Verify: show disabled seats per boat
SELECT 
    b.boat_name,
    GROUP_CONCAT(s.seat_number ORDER BY s.seat_number SEPARATOR ', ') as disabled_seats,
    COUNT(*) as total_disabled
FROM seat s
JOIN boat b ON b.id = s.boat_id
WHERE s.status = 'disabled'
GROUP BY b.boat_name
ORDER BY b.boat_name;
