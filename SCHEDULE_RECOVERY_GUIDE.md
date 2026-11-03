# 🔧 Schedule Recovery Guide

## Problem: Accidentally Deleted Schedule with Existing Manifest

Kalau schedule sudah dihapus tapi manifest passengers sudah checked in, data manifest masih ada tapi schedule-nya hilang. Ini menyebabkan:
- Halaman tickets tidak menampilkan trip yang dihapus
- Manifest uploads menjadi "orphan" (schedule_id tidak exist)
- Check-in data masih ada tapi tidak bisa diakses

---

## ✅ Solution 1: SQL Manual Recovery (Recommended)

### Step 1: Find Orphaned Manifests

```sql
SELECT 
    mu.id as upload_id,
    mu.schedule_id,
    mu.boat_name,
    mu.trip_date,
    mu.direction,
    mu.total_pax,
    COUNT(mt.id) as total_tickets,
    SUM(CASE WHEN mt.checked_in = 1 THEN 1 ELSE 0 END) as checked_in_count
FROM manifest_uploads mu
LEFT JOIN schedule s ON s.id = mu.schedule_id
LEFT JOIN manifest_tickets mt ON mt.upload_id = mu.id
WHERE s.id IS NULL  -- schedule doesn't exist
AND mu.trip_date = '2026-11-03'
AND mu.boat_name LIKE '%La Luna%'
GROUP BY mu.id;
```

**Expected Output:**
```
upload_id | schedule_id | boat_name | trip_date  | direction | total_pax | total_tickets | checked_in_count
----------|-------------|-----------|------------|-----------|-----------|---------------|------------------
123       | 456         | La Luna   | 2026-11-03 | DEPARTURE | 34        | 34            | 34
```

### Step 2: Get Boat ID

```sql
SELECT id, boat_name, capacity 
FROM boat 
WHERE boat_name LIKE '%La Luna%';
```

**Expected Output:**
```
id | boat_name | capacity
---|-----------|----------
10 | La Luna   | 34
```

### Step 3: Restore Schedule

```sql
-- Ganti {SCHEDULE_ID} dengan schedule_id dari Step 1 (456)
-- Ganti {BOAT_ID} dengan boat id dari Step 2 (10)

INSERT INTO schedule (id, boat_id, type, date, created_at, updated_at)
VALUES 
(456, 10, 'DEPARTURE', '2026-11-03 08:00:00', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    boat_id = 10,
    type = 'DEPARTURE',
    date = '2026-11-03 08:00:00',
    updated_at = NOW();
```

### Step 4: Verify Restoration

```sql
SELECT 
    s.id as schedule_id,
    s.boat_id,
    b.boat_name,
    s.type,
    s.date,
    mu.id as upload_id,
    mu.total_pax,
    COUNT(mt.id) as tickets,
    SUM(CASE WHEN mt.checked_in = 1 THEN 1 ELSE 0 END) as checked_in
FROM schedule s
JOIN boat b ON b.id = s.boat_id
LEFT JOIN manifest_uploads mu ON mu.schedule_id = s.id
LEFT JOIN manifest_tickets mt ON mt.upload_id = mu.id
WHERE s.date = '2026-11-03'
AND b.boat_name LIKE '%La Luna%'
GROUP BY s.id, mu.id;
```

✅ **Done!** Schedule sudah restored, manifest sekarang muncul di halaman tickets.

---

## ✅ Solution 2: API-Based Recovery

### Endpoint: Find Orphaned Manifests

```bash
GET /api/admin/schedule-recovery/orphaned
```

**Response:**
```json
{
  "success": true,
  "orphaned_manifests": [
    {
      "upload_id": 123,
      "schedule_id": 456,
      "boat_id": 10,
      "boat_name": "La Luna",
      "trip_date": "2026-11-03",
      "direction": "DEPARTURE",
      "total_pax": 34,
      "status": "active",
      "total_tickets": 34,
      "checked_in_count": 34,
      "last_checkin_at": "2026-11-03 07:45:00"
    }
  ],
  "count": 1
}
```

### Endpoint: Restore Single Schedule

```bash
POST /api/admin/schedule-recovery/restore
Content-Type: application/json

{
  "upload_id": 123
}
```

**Response:**
```json
{
  "success": true,
  "message": "Schedule restored successfully",
  "schedule_id": 456,
  "boat_id": 10,
  "trip_date": "2026-11-03",
  "direction": "DEPARTURE"
}
```

### Endpoint: Restore All Orphaned Schedules

```bash
POST /api/admin/schedule-recovery/restore-all
```

**Response:**
```json
{
  "success": true,
  "message": "Restored 3 schedules",
  "restored_count": 3,
  "restored_ids": [456, 457, 458],
  "failed_count": 0,
  "failed": []
}
```

---

## 🛡️ Prevention: Add Foreign Key Constraint

Untuk mencegah masalah ini terulang, tambahkan FK constraint dengan `ON DELETE RESTRICT`:

```sql
-- Check existing constraint
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    DELETE_RULE
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_NAME = 'manifest_uploads'
AND COLUMN_NAME = 'schedule_id';

-- Add constraint (will prevent schedule deletion if manifest exists)
ALTER TABLE manifest_uploads
ADD CONSTRAINT fk_manifest_schedule
FOREIGN KEY (schedule_id) REFERENCES schedule(id)
ON DELETE RESTRICT
ON UPDATE CASCADE;
```

**Effect:**
- ✅ **Sebelum constraint:** Schedule bisa dihapus meskipun ada manifest → orphan data
- ✅ **Setelah constraint:** Database akan REJECT penghapusan schedule kalau masih ada manifest yang refer ke schedule tersebut

**Error saat coba hapus schedule dengan manifest:**
```
ERROR 1451 (23000): Cannot delete or update a parent row: 
a foreign key constraint fails (`nama_mrn_dev`.`manifest_uploads`, 
CONSTRAINT `fk_manifest_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `schedule` (`id`))
```

---

## 📋 Files Created

1. **`restore_deleted_schedule.sql`** - SQL script untuk manual recovery
2. **`app/Controllers/ScheduleRecoveryController.php`** - API controller untuk recovery via admin panel
3. **Routes added to `app/Config/Routes.php`:**
   - `GET /api/admin/schedule-recovery/orphaned`
   - `POST /api/admin/schedule-recovery/restore`
   - `POST /api/admin/schedule-recovery/restore-all`

---

## 🔍 How to Use

### Scenario: La Luna 03 Nov 2026 08:00 dihapus

1. **Check orphaned manifests:**
   ```bash
   curl -X GET http://localhost:8080/api/admin/schedule-recovery/orphaned \
     -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
   ```

2. **Restore via API:**
   ```bash
   curl -X POST http://localhost:8080/api/admin/schedule-recovery/restore \
     -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"upload_id": 123}'
   ```

3. **Verify di frontend:**
   - Buka halaman Manifest Upload
   - La Luna 03 Nov 2026 seharusnya muncul kembali
   - Check-in data tetap utuh

---

## ⚠️ Important Notes

1. **Schedule ID must match:** Saat restore, gunakan schedule_id yang sama dengan yang tercatat di `manifest_uploads.schedule_id`
2. **Boat ID must match:** Pastikan boat_id sesuai dengan boat yang benar (La Luna = 10)
3. **Date format:** Gunakan format `YYYY-MM-DD HH:MM:SS` untuk date
4. **Check orphans first:** Selalu cek dulu orphaned manifests sebelum restore
5. **FK constraint:** Strongly recommended untuk prevent future accidents

---

## 🚀 Deployment

```bash
# Backend
cd /var/www/marine-backend
git pull origin security/auth-hardening

# Check new files
ls -la app/Controllers/ScheduleRecoveryController.php
ls -la restore_deleted_schedule.sql

# No restart needed (CodeIgniter auto-loads new controllers)
```

---

## 📞 Support

Kalau ada masalah recovery:
1. Check log: `writable/logs/log-YYYY-MM-DD.log`
2. Verify database: Jalankan query Step 1 di atas
3. Manual restore: Gunakan SQL script `restore_deleted_schedule.sql`

---

**Created:** 2026-11-02  
**Last Updated:** 2026-11-02  
**Status:** ✅ Ready for Production
