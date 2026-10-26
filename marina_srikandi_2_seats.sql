-- Marina Srikandi 2 — Insert boat + generate seats (no outer L/R seats)
-- Layout (BAWAH deck):
--   Row 1-2  : [D E]   [aisle] [A B]    = 4 seats/row
--   Row 3-14 : [D E F] [aisle] [A B C]  = 6 seats/row
--   Row 15   : [R. GENSET]    [TOILET]  = 0 seats
--   Total    : (2 × 4) + (12 × 6) = 8 + 72 = 80 seats
-- ============================================================================

-- ── 1. Insert boat ──────────────────────────────────────────────────────────
INSERT INTO boat (boat_name, capacity, photo1, photo2, photo3, photo4, photo5)
VALUES ('MARINA SRIKANDI 2', 80, '', '', '', '', '');

-- Ambil boat_id yang baru dibuat
SET @boat_id = LAST_INSERT_ID();
SELECT @boat_id AS new_boat_id;

-- ── 2. Delete existing seats (safety) ──────────────────────────────────────
DELETE FROM seat WHERE boat_id = @boat_id;

-- ── 3. Row 1-2: D, E, A, B (4 seats/row) ──────────────────────────────────
INSERT INTO seat (boat_id, seat_number, status) VALUES
(@boat_id, '1D', 'available'),
(@boat_id, '1E', 'available'),
(@boat_id, '1A', 'available'),
(@boat_id, '1B', 'available');

INSERT INTO seat (boat_id, seat_number, status) VALUES
(@boat_id, '2D', 'available'),
(@boat_id, '2E', 'available'),
(@boat_id, '2A', 'available'),
(@boat_id, '2B', 'available');

-- ── 4. Row 3-14: D, E, F, A, B, C (6 seats/row) ───────────────────────────
INSERT INTO seat (boat_id, seat_number, status) VALUES
(@boat_id, '3D', 'available'), (@boat_id, '3E', 'available'), (@boat_id, '3F', 'available'),
(@boat_id, '3A', 'available'), (@boat_id, '3B', 'available'), (@boat_id, '3C', 'available');

INSERT INTO seat (boat_id, seat_number, status) VALUES
(@boat_id, '4D', 'available'), (@boat_id, '4E', 'available'), (@boat_id, '4F', 'available'),
(@boat_id, '4A', 'available'), (@boat_id, '4B', 'available'), (@boat_id, '4C', 'available');

INSERT INTO seat (boat_id, seat_number, status) VALUES
(@boat_id, '5D', 'available'), (@boat_id, '5E', 'available'), (@boat_id, '5F', 'available'),
(@boat_id, '5A', 'available'), (@boat_id, '5B', 'available'), (@boat_id, '5C', 'available');

INSERT INTO seat (boat_id, seat_number, status) VALUES
(@boat_id, '6D', 'available'), (@boat_id, '6E', 'available'), (@boat_id, '6F', 'available'),
(@boat_id, '6A', 'available'), (@boat_id, '6B', 'available'), (@boat_id, '6C', 'available');

INSERT INTO seat (boat_id, seat_number, status) VALUES
(@boat_id, '7D', 'available'), (@boat_id, '7E', 'available'), (@boat_id, '7F', 'available'),
(@boat_id, '7A', 'available'), (@boat_id, '7B', 'available'), (@boat_id, '7C', 'available');

INSERT INTO seat (boat_id, seat_number, status) VALUES
(@boat_id, '8D', 'available'), (@boat_id, '8E', 'available'), (@boat_id, '8F', 'available'),
(@boat_id, '8A', 'available'), (@boat_id, '8B', 'available'), (@boat_id, '8C', 'available');

INSERT INTO seat (boat_id, seat_number, status) VALUES
(@boat_id, '9D', 'available'), (@boat_id, '9E', 'available'), (@boat_id, '9F', 'available'),
(@boat_id, '9A', 'available'), (@boat_id, '9B', 'available'), (@boat_id, '9C', 'available');

INSERT INTO seat (boat_id, seat_number, status) VALUES
(@boat_id, '10D', 'available'), (@boat_id, '10E', 'available'), (@boat_id, '10F', 'available'),
(@boat_id, '10A', 'available'), (@boat_id, '10B', 'available'), (@boat_id, '10C', 'available');

INSERT INTO seat (boat_id, seat_number, status) VALUES
(@boat_id, '11D', 'available'), (@boat_id, '11E', 'available'), (@boat_id, '11F', 'available'),
(@boat_id, '11A', 'available'), (@boat_id, '11B', 'available'), (@boat_id, '11C', 'available');

INSERT INTO seat (boat_id, seat_number, status) VALUES
(@boat_id, '12D', 'available'), (@boat_id, '12E', 'available'), (@boat_id, '12F', 'available'),
(@boat_id, '12A', 'available'), (@boat_id, '12B', 'available'), (@boat_id, '12C', 'available');

INSERT INTO seat (boat_id, seat_number, status) VALUES
(@boat_id, '13D', 'available'), (@boat_id, '13E', 'available'), (@boat_id, '13F', 'available'),
(@boat_id, '13A', 'available'), (@boat_id, '13B', 'available'), (@boat_id, '13C', 'available');

INSERT INTO seat (boat_id, seat_number, status) VALUES
(@boat_id, '14D', 'available'), (@boat_id, '14E', 'available'), (@boat_id, '14F', 'available'),
(@boat_id, '14A', 'available'), (@boat_id, '14B', 'available'), (@boat_id, '14C', 'available');

-- ── 5. Verify ───────────────────────────────────────────────────────────────
SELECT b.id, b.boat_name, b.capacity, COUNT(s.id) AS total_seats
FROM boat b
LEFT JOIN seat s ON s.boat_id = b.id
WHERE b.boat_name = 'MARINA SRIKANDI 2'
GROUP BY b.id;
