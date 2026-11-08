-- Marina Srikandi 2 — Insert boat + generate seats (no outer L/R seats)
-- Layout (BAWAH deck):
--   Row 1-2  : [A B]   [aisle] [E F]    = 4 seats/row (NO C, NO D!)
--   Row 3-14 : [A B C] [aisle] [D E F]  = 6 seats/row
--   Row 15   : [R. GENSET]    [TOILET]  = 0 seats
--   Total    : (2 × 4) + (12 × 6) = 8 + 72 = 80 seats
--
-- CORRECTED: Row 1-2 should be A,B,E,F (not D,E,A,B)
-- A = LEFT window, F = RIGHT window (standard convention)
-- ============================================================================

-- ── 1. Insert boat ──────────────────────────────────────────────────────────
INSERT INTO boat (boat_name, capacity, photo1, photo2, photo3, photo4, photo5)
VALUES ('MARINA SRIKANDI 2', 80, '', '', '', '', '');

-- Ambil boat_id yang baru dibuat
SET @boat_id = LAST_INSERT_ID();
SELECT @boat_id AS new_boat_id;

-- ── 2. Delete existing seats (safety) ──────────────────────────────────────
DELETE FROM seat WHERE boat_id = @boat_id;

-- ── 3. Row 1-2: A, B, E, F (4 seats/row) ──────────────────────────────────
-- Left side: A, B (window seats)
-- Right side: E, F (window seats)
-- NO C, NO D in rows 1-2!
INSERT INTO seat (boat_id, seat_number, status) VALUES
(@boat_id, '1A', 'available'),
(@boat_id, '1B', 'available'),
(@boat_id, '1E', 'available'),
(@boat_id, '1F', 'available');

INSERT INTO seat (boat_id, seat_number, status) VALUES
(@boat_id, '2A', 'available'),
(@boat_id, '2B', 'available'),
(@boat_id, '2E', 'available'),
(@boat_id, '2F', 'available');

-- ── 4. Row 3-14: A, B, C, D, E, F (6 seats/row) ───────────────────────────
-- Left side: A, B, C (window → aisle)
-- Right side: D, E, F (aisle → window)
INSERT INTO seat (boat_id, seat_number, status) VALUES
(@boat_id, '3A', 'available'), (@boat_id, '3B', 'available'), (@boat_id, '3C', 'available'),
(@boat_id, '3D', 'available'), (@boat_id, '3E', 'available'), (@boat_id, '3F', 'available');

INSERT INTO seat (boat_id, seat_number, status) VALUES
(@boat_id, '4A', 'available'), (@boat_id, '4B', 'available'), (@boat_id, '4C', 'available'),
(@boat_id, '4D', 'available'), (@boat_id, '4E', 'available'), (@boat_id, '4F', 'available');

INSERT INTO seat (boat_id, seat_number, status) VALUES
(@boat_id, '5A', 'available'), (@boat_id, '5B', 'available'), (@boat_id, '5C', 'available'),
(@boat_id, '5D', 'available'), (@boat_id, '5E', 'available'), (@boat_id, '5F', 'available');

INSERT INTO seat (boat_id, seat_number, status) VALUES
(@boat_id, '6A', 'available'), (@boat_id, '6B', 'available'), (@boat_id, '6C', 'available'),
(@boat_id, '6D', 'available'), (@boat_id, '6E', 'available'), (@boat_id, '6F', 'available');

INSERT INTO seat (boat_id, seat_number, status) VALUES
(@boat_id, '7A', 'available'), (@boat_id, '7B', 'available'), (@boat_id, '7C', 'available'),
(@boat_id, '7D', 'available'), (@boat_id, '7E', 'available'), (@boat_id, '7F', 'available');

INSERT INTO seat (boat_id, seat_number, status) VALUES
(@boat_id, '8A', 'available'), (@boat_id, '8B', 'available'), (@boat_id, '8C', 'available'),
(@boat_id, '8D', 'available'), (@boat_id, '8E', 'available'), (@boat_id, '8F', 'available');

INSERT INTO seat (boat_id, seat_number, status) VALUES
(@boat_id, '9A', 'available'), (@boat_id, '9B', 'available'), (@boat_id, '9C', 'available'),
(@boat_id, '9D', 'available'), (@boat_id, '9E', 'available'), (@boat_id, '9F', 'available');

INSERT INTO seat (boat_id, seat_number, status) VALUES
(@boat_id, '10A', 'available'), (@boat_id, '10B', 'available'), (@boat_id, '10C', 'available'),
(@boat_id, '10D', 'available'), (@boat_id, '10E', 'available'), (@boat_id, '10F', 'available');

INSERT INTO seat (boat_id, seat_number, status) VALUES
(@boat_id, '11A', 'available'), (@boat_id, '11B', 'available'), (@boat_id, '11C', 'available'),
(@boat_id, '11D', 'available'), (@boat_id, '11E', 'available'), (@boat_id, '11F', 'available');

INSERT INTO seat (boat_id, seat_number, status) VALUES
(@boat_id, '12A', 'available'), (@boat_id, '12B', 'available'), (@boat_id, '12C', 'available'),
(@boat_id, '12D', 'available'), (@boat_id, '12E', 'available'), (@boat_id, '12F', 'available');

INSERT INTO seat (boat_id, seat_number, status) VALUES
(@boat_id, '13A', 'available'), (@boat_id, '13B', 'available'), (@boat_id, '13C', 'available'),
(@boat_id, '13D', 'available'), (@boat_id, '13E', 'available'), (@boat_id, '13F', 'available');

INSERT INTO seat (boat_id, seat_number, status) VALUES
(@boat_id, '14A', 'available'), (@boat_id, '14B', 'available'), (@boat_id, '14C', 'available'),
(@boat_id, '14D', 'available'), (@boat_id, '14E', 'available'), (@boat_id, '14F', 'available');

-- ── 5. Verify ───────────────────────────────────────────────────────────────
SELECT b.id, b.boat_name, b.capacity, COUNT(s.id) AS total_seats
FROM boat b
LEFT JOIN seat s ON s.boat_id = b.id
WHERE b.boat_name = 'MARINA SRIKANDI 2'
GROUP BY b.id;
