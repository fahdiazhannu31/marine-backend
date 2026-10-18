-- Fix: Reset all seats that were incorrectly marked as 'booked' globally.
-- After this patch, seat availability is tracked per trip_date via manifest_tickets,
-- not via the global seat.status flag. 
-- 
-- 'available' = seat exists and can be used
-- 'disabled'  = seat permanently removed/not in use (do NOT reset these)
-- ============================================================================

-- Reset all 'booked' seats back to 'available'
UPDATE seat SET status = 'available' WHERE status = 'booked';

-- Verify
SELECT status, COUNT(*) as total FROM seat GROUP BY status;
