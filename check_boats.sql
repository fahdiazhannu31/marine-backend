-- Check all boats and their seat counts
SELECT 
    b.id,
    b.boat_name,
    b.capacity,
    COUNT(s.id) as seat_count,
    SUM(CASE WHEN s.status = 'available' THEN 1 ELSE 0 END) as available_seats,
    SUM(CASE WHEN s.status = 'booked' THEN 1 ELSE 0 END) as booked_seats
FROM boat b
LEFT JOIN seat s ON b.id = s.boat_id
GROUP BY b.id, b.boat_name, b.capacity
ORDER BY b.id;
