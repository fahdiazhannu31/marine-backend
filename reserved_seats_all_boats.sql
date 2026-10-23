-- ============================================================================
-- Insert reserved seats for all boats (manual-assign only, excluded from auto-assign)
--
-- MOLA-MOLA      : 9A-9J
-- LA VELA        : 9D, 9E, 9F
-- LA LUNA        : 9A, 9B
-- LA BRISA       : 1A-1H
-- MARINA SRIK. 2 : 1A, 1B, 1E, 1F
-- ============================================================================

-- ── MOLA-MOLA (boat_name LIKE '%mola%') ──────────────────────────────────────
SET @mola_id = (SELECT id FROM boat WHERE LOWER(boat_name) LIKE '%mola%' ORDER BY id LIMIT 1);

DELETE FROM seat WHERE boat_id = @mola_id
  AND seat_number IN ('9A','9B','9C','9D','9E','9F','9G','9H','9I','9J');

INSERT INTO seat (boat_id, seat_number, status) VALUES
(@mola_id,'9A','available'),(@mola_id,'9B','available'),(@mola_id,'9C','available'),
(@mola_id,'9D','available'),(@mola_id,'9E','available'),(@mola_id,'9F','available'),
(@mola_id,'9G','available'),(@mola_id,'9H','available'),(@mola_id,'9I','available'),
(@mola_id,'9J','available');

-- ── LA VELA (boat_name LIKE '%la vela%') ─────────────────────────────────────
SET @vela_id = (SELECT id FROM boat WHERE LOWER(boat_name) LIKE '%la vela%' ORDER BY id LIMIT 1);

DELETE FROM seat WHERE boat_id = @vela_id AND seat_number IN ('9D','9E','9F');

INSERT INTO seat (boat_id, seat_number, status) VALUES
(@vela_id,'9D','available'),
(@vela_id,'9E','available'),
(@vela_id,'9F','available');

-- ── LA LUNA (boat_name LIKE '%la luna%') ─────────────────────────────────────
SET @luna_id = (SELECT id FROM boat WHERE LOWER(boat_name) LIKE '%la luna%' ORDER BY id LIMIT 1);

DELETE FROM seat WHERE boat_id = @luna_id AND seat_number IN ('9A','9B');

INSERT INTO seat (boat_id, seat_number, status) VALUES
(@luna_id,'9A','available'),
(@luna_id,'9B','available');

-- ── LA BRISA (boat_name LIKE '%la brisa%' or '%labrisa%') ───────────────────
SET @brisa_id = (SELECT id FROM boat WHERE LOWER(boat_name) LIKE '%brisa%' ORDER BY id LIMIT 1);

DELETE FROM seat WHERE boat_id = @brisa_id
  AND seat_number IN ('1A','1B','1C','1D','1E','1F','1G','1H');

INSERT INTO seat (boat_id, seat_number, status) VALUES
(@brisa_id,'1A','available'),(@brisa_id,'1B','available'),(@brisa_id,'1C','available'),
(@brisa_id,'1D','available'),(@brisa_id,'1E','available'),(@brisa_id,'1F','available'),
(@brisa_id,'1G','available'),(@brisa_id,'1H','available');

-- ── MARINA SRIKANDI 2 (boat_name LIKE '%marina srikandi%') ───────────────────
SET @srikandi_id = (SELECT id FROM boat WHERE LOWER(boat_name) LIKE '%marina srikandi%' ORDER BY id LIMIT 1);

DELETE FROM seat WHERE boat_id = @srikandi_id
  AND seat_number IN ('1A','1B','1E','1F');

INSERT INTO seat (boat_id, seat_number, status) VALUES
(@srikandi_id,'1A','available'),
(@srikandi_id,'1B','available'),
(@srikandi_id,'1E','available'),
(@srikandi_id,'1F','available');

-- ── Verify ────────────────────────────────────────────────────────────────────
SELECT b.boat_name, s.seat_number, s.status
FROM seat s
JOIN boat b ON b.id = s.boat_id
WHERE (b.boat_name LIKE '%mola%'    AND s.seat_number IN ('9A','9B','9C','9D','9E','9F','9G','9H','9I','9J'))
   OR (b.boat_name LIKE '%la vela%' AND s.seat_number IN ('9D','9E','9F'))
   OR (b.boat_name LIKE '%la luna%' AND s.seat_number IN ('9A','9B'))
   OR (b.boat_name LIKE '%brisa%'   AND s.seat_number IN ('1A','1B','1C','1D','1E','1F','1G','1H'))
   OR (b.boat_name LIKE '%srikandi%' AND s.seat_number IN ('1A','1B','1E','1F'))
ORDER BY b.boat_name, s.seat_number;
