# Deployment Instructions - VPS

## Files Modified (Need to Deploy):

### Backend:
1. `backend/app/Controllers/ManifestUploadController.php`
   - Improved QR scan lookup logic
   - Expanded date window (yesterday + today + tomorrow)
   - Added cancelled ticket filtering
   - Enhanced error logging

### Frontend:
1. `frontend/src/features/admin/pages/ManifestUpload.jsx`
   - Fixed upload success handler with delay
   - Suppressed duplicate error toast

2. `frontend/src/features/admin/pages/CheckIn.jsx`
   - Improved 404 error handling
   - Show debug info in console
   - Better error messages

3. `frontend/src/features/admin/services/manifestUploadService.js`
   - Added response logging for debugging

## Manual Deployment Steps:

### Option 1: Using Git (Recommended)

```bash
# 1. Commit changes locally
cd c:\laragon\www\sepafullstack
git add .
git commit -m "Fix upload toast & improve QR scan robustness"
git push origin main

# 2. SSH to VPS
ssh user@your-vps-ip

# 3. Pull on VPS
cd /path/to/sepafullstack
git pull origin main

# 4. Build frontend on VPS
cd frontend
npm run build

# 5. Restart services (if needed)
# For Apache/Nginx - no restart needed usually
# For PM2 (if using Node.js proxy):
pm2 restart all
```

### Option 2: Using SCP/SFTP

```bash
# Backend files
scp backend/app/Controllers/ManifestUploadController.php user@vps:/path/to/backend/app/Controllers/

# Frontend - build locally then upload
cd frontend
npm run build

# Upload dist folder
scp -r dist/* user@vps:/path/to/frontend/dist/
```

### Option 3: Using FTP/File Manager

1. **Backend:**
   - Upload `backend/app/Controllers/ManifestUploadController.php`
   - Replace existing file

2. **Frontend:**
   - Build locally: `cd frontend && npm run build`
   - Upload entire `dist/` folder content
   - Replace existing files in production

## Verification After Deployment:

### 1. Check Backend Logs
```bash
# SSH to VPS
tail -f /path/to/writable/logs/log-$(date +%Y-%m-%d).log
```

### 2. Test Upload Manifest
- Login as admin
- Go to Manifest Upload
- Upload Excel file
- **Should see:** Success toast only (no error toast)
- **Should auto-load:** Tickets tab within 1 second

### 3. Test QR Scan
- Go to Check-In Scanner
- Scan QR for today's trip → should work
- Scan QR for tomorrow's trip → should work (NEW)
- Scan cancelled ticket → should skip
- Scan invalid QR → should show error with suggestion

### 4. Check Console Logs
- Open browser DevTools (F12)
- Check Console tab for:
  - `[uploadManifestFile] Upload successful:` (after upload)
  - `[CheckIn] Ticket not found - Debug Info:` (if scan fails)

## Rollback Plan (If Issues):

```bash
# Git rollback
git revert HEAD
git push origin main

# On VPS
git pull origin main
cd frontend && npm run build
```

## Database Changes:
**None required** — all changes are in application logic only.

## Environment Variables:
**No changes** — .env remains the same.

## Notes:
- No database migration needed
- No new dependencies
- No breaking changes
- Safe to deploy during business hours
