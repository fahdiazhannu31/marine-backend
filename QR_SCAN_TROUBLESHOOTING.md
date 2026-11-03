# QR Scan Troubleshooting Guide

## Masalah: "Ticket or group not found" saat scan QR

### Penyebab Umum:

1. **QR dari manifest yang belum di-upload**
   - Solusi: Pastikan manifest sudah di-upload di halaman "Manifest Upload"
   - Cek: Admin > Manifest Upload > Tickets, cari nama penumpang

2. **QR dari tanggal yang terlalu lama (> 1 hari dari sekarang)**
   - Sistem sekarang prioritas cari ticket dari kemarin, hari ini, dan besok
   - QR untuk trip minggu depan masih bisa di-scan, tapi mungkin lebih lambat

3. **Ticket sudah di-cancel**
   - Cek di halaman Manifest Upload > Tickets
   - Kolom "cancelled" harus unchecked agar bisa scan

4. **Format QR tidak standar**
   - Format yang didukung:
     * URL boarding pass: `https://domain/boarding-pass?t=<token>`
     * Ticket code: `TKT-{uploadId}-{id}`
     * Group name: nama grup langsung
     * NIK/Passport: nomor identitas penumpang

### Cara Debug:

1. **Cek Browser Console** (F12 > Console)
   - Akan muncul log: `[CheckIn] Ticket not found - Debug Info:`
   - Lihat `scanned_code` dan `tried_formats`

2. **Cek Backend Logs** (VPS)
   ```bash
   tail -f /path/to/writable/logs/log-$(date +%Y-%m-%d).log | grep "QR Scan"
   ```
   
3. **Cari Manual di Database**
   ```sql
   -- Cari by ticket code
   SELECT * FROM manifest_tickets WHERE ticket_code = 'TKT-xxx-xxxxx';
   
   -- Cari by NIK
   SELECT * FROM manifest_tickets WHERE id_passport = '1234567890123456';
   
   -- Cari by group name
   SELECT * FROM manifest_tickets WHERE group_name = 'NAMA GROUP';
   ```

### Improvement yang Sudah Dibuat:

✅ **Date window expanded** — cari dari kemarin sampai besok (3 hari)
✅ **Skip cancelled tickets** — ticket yang cancelled tidak muncul di hasil scan
✅ **Better error messages** — tampilkan suggestion untuk user
✅ **Debug logging** — log di backend + frontend console
✅ **Multiple format support** — URL token, ticket_code, NIK, group name

### Jika Masih Error:

1. Screenshot QR code yang bermasalah
2. Cek browser console log
3. Cek backend log di VPS
4. Kirim info ke developer:
   - Tanggal trip
   - Nama penumpang/group
   - Boat name
   - Screenshot error + console log
