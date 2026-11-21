# 🛡️ NAMA Marine - Hardened VPS Migration Guide

**From:** Rumahweb (suspended - crypto miner attack)  
**To:** Domainesia VPS (production-grade security)

---

## 🎯 What's Different from Previous Migration?

### **Previous (Basic)**
- ❌ PHP 8.1 (outdated)
- ❌ No crypto miner protection
- ❌ No brute force protection
- ❌ No antivirus
- ❌ No system monitoring
- ❌ Basic Nginx config

### **New (HARDENED)** ✅
- ✅ **PHP 8.3** (latest stable - Nov 2024)
- ✅ **Crypto miner detection** (every 5 min)
- ✅ **Fail2ban** (auto-ban SSH/HTTP attacks)
- ✅ **ClamAV antivirus** (daily malware scans)
- ✅ **System monitoring** (auto-restart failed services)
- ✅ **Nginx rate limiting** (20 req/s API, 100 req/s general)
- ✅ **UFW firewall** (only SSH, HTTP, HTTPS allowed)
- ✅ **Security headers** (OWASP recommendations)
- ✅ **MySQL hardening** (localhost-only, no remote access)
- ✅ **Process monitoring** (kill suspicious high-CPU processes)

---

## 🚀 Quick Start (5 Steps)

### **Step 1: SSH to New VPS**

```bash
ssh root@YOUR_VPS_IP
# Enter password from Domainesia email
```

---

### **Step 2: Download & Run Hardened Setup Script**

**Option A: Direct from GitHub**
```bash
curl -sL https://raw.githubusercontent.com/fahdiazhannu31/marine-backend/security/auth-hardening/migrate-to-domainesia.sh -o /root/migrate.sh
chmod +x /root/migrate.sh
bash /root/migrate.sh
```

**Option B: Manual Copy-Paste**
```bash
# Copy script from Windows:
# 1. Open: C:\laragon\www\sepafullstack\migrate-to-domainesia.sh
# 2. Copy all content (Ctrl+A, Ctrl+C)
# 3. SSH to VPS, create file:
nano /root/migrate.sh

# 4. Paste content (Right-click)
# 5. Save: Ctrl+X, Y, Enter
# 6. Run:
chmod +x /root/migrate.sh
bash /root/migrate.sh
```

**Expected runtime:** 20-30 minutes (includes ClamAV virus database update)

---

### **Step 3: Import Database**

After script completes, import your database:

```bash
# Get MySQL password
cat /root/mysql_credentials.txt

# Upload database from Windows
# Method 1: Via SCP (from Windows CMD/PowerShell)
scp "C:\Users\User\Documents\Nama Marine\nama_mrn.sql" root@YOUR_VPS_IP:/tmp/

# Method 2: Via SFTP client (WinSCP, FileZilla)
# Upload to /tmp/nama_mrn.sql

# Then on VPS, import:
mysql -u nama_user -p nama_mrn < /tmp/nama_mrn.sql
# Enter password from /root/mysql_credentials.txt

# Verify import
mysql -u nama_user -p nama_mrn -e "SELECT COUNT(*) FROM boat;"
# Should show boat count (e.g., 19)

# Clean up
rm /tmp/nama_mrn.sql
```

---

### **Step 4: Update DNS**

Point domain `nama-marin.com` to new VPS IP:

1. **Get VPS IP:**
   ```bash
   curl ifconfig.me
   # Example output: 103.xxx.xxx.xxx
   ```

2. **Update DNS at Rumahweb:**
   - Login: https://clientzone.rumahweb.com
   - Menu: **Domain → Kelola Domain**
   - Select: `nama-marin.com`
   - Click: **DNS Management**
   - Update A records:
     ```
     Type: A
     Host: @
     Value: YOUR_NEW_VPS_IP
     TTL: 300 (5 minutes)
     
     Type: A
     Host: www
     Value: YOUR_NEW_VPS_IP
     TTL: 300
     ```
   - **Save Changes**

3. **Verify DNS Propagation (wait 5-15 min):**
   ```bash
   # From your Windows PC:
   nslookup nama-marin.com
   
   # Should return YOUR_NEW_VPS_IP
   ```

---

### **Step 5: Setup SSL Certificate**

After DNS points to new VPS (verify with `nslookup`):

```bash
# SSH to VPS
ssh root@YOUR_VPS_IP

# Install Certbot
apt install -y certbot python3-certbot-nginx

# Get SSL certificate
certbot --nginx -d nama-marin.com -d www.nama-marin.com

# Follow prompts:
# Email: your-email@example.com
# Terms: A (Agree)
# Redirect HTTP to HTTPS: 2 (Yes - recommended)
```

**Done!** ✅

---

## 🧪 Verification Checklist

After setup, verify everything works:

### **1. Service Status**
```bash
systemctl status nginx
systemctl status mysql
systemctl status nama-marine-backend
systemctl status fail2ban
systemctl status clamav-freshclam
```

All should show: **active (running)** ✅

---

### **2. Test Application**

**Backend API:**
```bash
curl http://localhost/api/home
# Should return JSON with boats/routes

curl https://nama-marin.com/api/home
# Should return same JSON (after SSL setup)
```

**Frontend:**
Open browser: https://nama-marin.com
- ✅ Should load homepage
- ✅ Should show boats (Marina Srikandi 2, La Vela, etc.)
- ✅ HTTPS lock icon in address bar

---

### **3. Security Features**

**Check firewall:**
```bash
ufw status
# Should show: SSH (22), HTTP (80), HTTPS (443) ALLOW
```

**Check Fail2ban:**
```bash
fail2ban-client status sshd
# Should show: Currently banned IPs (if any attacks)
```

**Check crypto miner detection:**
```bash
cat /var/log/crypto-miner-detection.log
# Should exist (empty if no miners detected)
```

**Check ClamAV:**
```bash
systemctl status clamav-freshclam
# Should be active (updating virus definitions)
```

---

### **4. Monitor Logs (First 24 Hours)**

**Backend errors:**
```bash
journalctl -u nama-marine-backend -f
# Press Ctrl+C to exit
```

**Nginx errors:**
```bash
tail -f /var/log/nginx/error.log
```

**Security scan results:**
```bash
cat /var/log/security-scan.log
# Updated daily at 2 AM
```

**System monitoring:**
```bash
cat /var/log/system-monitor.log
# Updated every 5 minutes
```

---

## 🔒 Security Features Explained

### **1. Crypto Miner Detection**
- **Runs:** Every 5 minutes (cron job)
- **Detects:** Processes using >70% CPU
- **Action:** Auto-kills known miners (xmrig, minerd, kinsing, etc.)
- **Log:** `/var/log/crypto-miner-detection.log`

**Manual scan:**
```bash
/usr/local/bin/detect-crypto-miner.sh
cat /var/log/crypto-miner-detection.log
```

---

### **2. Fail2ban (Brute Force Protection)**
- **SSH:** Ban after 3 failed logins (1 hour)
- **Nginx:** Ban bots/scanners attacking web server
- **Log:** `/var/log/fail2ban.log`

**Check banned IPs:**
```bash
fail2ban-client status sshd
fail2ban-client status nginx-limit-req
```

**Manually ban IP:**
```bash
fail2ban-client set sshd banip 1.2.3.4
```

**Unban IP:**
```bash
fail2ban-client set sshd unbanip 1.2.3.4
```

---

### **3. ClamAV Antivirus**
- **Runs:** Daily at 2 AM (full `/var/www` scan)
- **Updates:** Virus definitions every 2 hours
- **Log:** `/var/log/clamav-scan.log`

**Manual scan:**
```bash
clamscan -r -i /var/www
# -r = recursive, -i = infected files only
```

---

### **4. System Monitoring**
- **Runs:** Every 5 minutes
- **Monitors:** CPU, memory, service status
- **Action:** Auto-restart failed services (Nginx, MySQL, backend)
- **Log:** `/var/log/system-monitor.log`

**Manual check:**
```bash
/usr/local/bin/monitor-system.sh
cat /var/log/system-monitor.log
```

---

### **5. Nginx Rate Limiting**
- **API endpoints:** 20 requests/second per IP
- **General pages:** 100 requests/second per IP
- **Burst:** Allows temporary spikes (10-50 requests)
- **Action:** Returns `503 Service Unavailable` if exceeded

**Test rate limit:**
```bash
# Rapid requests (will be rate-limited)
for i in {1..100}; do curl -s -o /dev/null -w "%{http_code}\n" http://localhost/api/home; done
# Should show mix of 200 and 503
```

---

### **6. UFW Firewall**
- **Allowed:** SSH (22), HTTP (80), HTTPS (443)
- **Blocked:** Everything else (MySQL 3306, etc.)
- **Status:** Active on boot

**Check rules:**
```bash
ufw status numbered
```

**Add custom rule (if needed):**
```bash
# Example: Allow MySQL from specific IP only
ufw allow from 103.xxx.xxx.xxx to any port 3306
```

---

## 📊 Daily Operations

### **Morning Checks (5 min)**

```bash
# 1. Check service health
systemctl status nginx mysql nama-marine-backend

# 2. Check security logs
cat /var/log/security-scan.log | tail -50

# 3. Check banned IPs (attacks)
fail2ban-client status sshd

# 4. Check system resources
htop
# Press 'q' to exit
```

---

### **Weekly Maintenance (10 min)**

```bash
# 1. Update system packages
apt update && apt upgrade -y

# 2. Check disk usage
df -h
# Root should be < 80%

# 3. Review security logs
cat /var/log/crypto-miner-detection.log | grep "CRYPTO MINER DETECTED"
cat /var/log/clamav-scan.log | grep "Infected files"

# 4. Restart services (if needed)
systemctl restart nginx mysql nama-marine-backend
```

---

### **Monthly Tasks (30 min)**

```bash
# 1. Review fail2ban bans (unban false positives)
fail2ban-client status sshd
# If office IP banned by mistake:
fail2ban-client set sshd unbanip OFFICE_IP

# 2. Update ClamAV virus database
freshclam

# 3. Check backend logs for errors
journalctl -u nama-marine-backend --since "30 days ago" | grep -i error

# 4. Backup database
mysqldump -u nama_user -p nama_mrn > /root/backup-$(date +%Y%m%d).sql
# Download to Windows via SCP/SFTP
```

---

## 🚨 Troubleshooting

### **Backend Not Starting**

```bash
# Check logs
journalctl -u nama-marine-backend -xe

# Common issues:
# 1. Wrong database credentials
nano /var/www/marine-backend/.env
# Verify DB_PASSWORD matches /root/mysql_credentials.txt

# 2. Port 8080 in use
netstat -tulpn | grep 8080
# Kill process: kill -9 PID

# 3. PHP not found
which php  # Should be /usr/bin/php
php -v     # Should be 8.3.x

# Restart
systemctl restart nama-marine-backend
```

---

### **High CPU Usage**

```bash
# Check top processes
htop
# Press Shift+P to sort by CPU

# Check crypto miner log
cat /var/log/crypto-miner-detection.log

# Manually kill suspicious process
kill -9 PID
```

---

### **Site Unreachable**

```bash
# 1. Check Nginx
systemctl status nginx
systemctl restart nginx

# 2. Check DNS
nslookup nama-marin.com
# Should return VPS IP

# 3. Check firewall
ufw status
# Should allow 80, 443

# 4. Check SSL certificate
certbot certificates
# Should show valid until date (90 days)
```

---

### **Database Connection Errors**

```bash
# 1. Check MySQL running
systemctl status mysql

# 2. Test connection
mysql -u nama_user -p nama_mrn
# Enter password from /root/mysql_credentials.txt

# 3. Verify backend .env
cat /var/www/marine-backend/.env | grep database

# Should match:
# database.default.database = nama_mrn
# database.default.username = nama_user
# database.default.password = <from mysql_credentials.txt>
```

---

## 📁 Important File Locations

```
/var/www/marine-backend/         → Backend code
/var/www/marine-backend/.env     → Database credentials
/var/www/marine-frontend/dist/   → Frontend build

/etc/nginx/sites-available/nama-marine  → Nginx config
/etc/systemd/system/nama-marine-backend.service  → Backend service

/root/mysql_credentials.txt      → MySQL passwords

/var/log/security-scan.log       → Daily security scans
/var/log/system-monitor.log      → System health checks
/var/log/crypto-miner-detection.log  → Miner detection
/var/log/fail2ban.log           → Banned IPs
/var/log/clamav-scan.log        → Antivirus scans
/var/log/nginx/access.log       → HTTP requests
/var/log/nginx/error.log        → Nginx errors
```

---

## 🔧 Useful Commands Reference

### **Service Management**
```bash
systemctl status SERVICE_NAME      # Check status
systemctl restart SERVICE_NAME     # Restart
systemctl stop SERVICE_NAME        # Stop
systemctl start SERVICE_NAME       # Start
systemctl enable SERVICE_NAME      # Auto-start on boot
journalctl -u SERVICE_NAME -f      # View logs (live)
```

### **Nginx**
```bash
nginx -t                     # Test config syntax
systemctl reload nginx       # Reload config (no downtime)
tail -f /var/log/nginx/access.log  # Watch requests
```

### **MySQL**
```bash
mysql -u root -p             # Connect as root
mysql -u nama_user -p nama_mrn  # Connect to app DB
mysqldump -u nama_user -p nama_mrn > backup.sql  # Backup
```

### **Security**
```bash
fail2ban-client status       # Show all jails
ufw status numbered          # Show firewall rules
clamscan -r -i /var/www      # Scan for viruses
htop                         # Monitor processes
```

---

## 📞 Emergency Contacts

**Domainesia Support:**
- 📧 Email: cs@domainesia.com
- 💬 Live Chat: https://www.domainesia.com
- 📱 WhatsApp: 0274-5305505

**If Server Completely Down:**
1. Login to Domainesia panel: https://my.domainesia.com
2. VPS Management → Restart VPS
3. Wait 2-3 minutes for boot
4. SSH and check logs:
   ```bash
   journalctl -xe
   systemctl status nginx mysql nama-marine-backend
   ```

---

## ✅ Post-Migration Checklist

After completing all steps, verify:

```
☐ VPS accessible via SSH
☐ All services running (nginx, mysql, backend, fail2ban, clamav)
☐ Database imported successfully
☐ Frontend loads at https://nama-marin.com
☐ Backend API responds at https://nama-marin.com/api/home
☐ Admin login works
☐ Booking system functional
☐ Check-in process works
☐ PDF boarding pass generates
☐ SSL certificate valid (HTTPS lock icon)
☐ Firewall active (ufw status)
☐ Fail2ban protecting SSH (fail2ban-client status sshd)
☐ Crypto miner detection running (cron job exists)
☐ ClamAV updating (systemctl status clamav-freshclam)
☐ System monitoring active (check /var/log/system-monitor.log)
☐ DNS pointing to new VPS (nslookup nama-marin.com)
```

---

## 🎉 Migration Complete!

**Timeline Recap:**
- ⏱️ VPS setup script: 20-30 min
- ⏱️ Database import: 5-10 min
- ⏱️ DNS propagation: 5-15 min
- ⏱️ SSL certificate: 2 min
- **Total: ~1 hour**

**You now have:**
- ✅ PHP 8.3 (latest)
- ✅ Hardened VPS (anti crypto miner)
- ✅ Auto-protection (Fail2ban, ClamAV)
- ✅ Rate limiting (anti DDoS)
- ✅ System monitoring (auto-restart)
- ✅ HTTPS enabled (SSL)

**Rumahweb VPS dapat di-cancel setelah:**
- Verify aplikasi stabil 24-48 jam di Domainesia
- Backup final database dari Rumahweb (jika masih accessible)
- Download semua files penting (logs, configs)

---

## 📚 Additional Resources

**Domainesia Docs:**
- VPS Management: https://www.domainesia.com/panduan/category/vps
- SSH Access: https://www.domainesia.com/panduan/cara-akses-vps-melalui-ssh

**Security Best Practices:**
- OWASP Top 10: https://owasp.org/www-project-top-ten/
- PHP Security Guide: https://www.php.net/manual/en/security.php
- Nginx Hardening: https://www.nginx.com/blog/tuning-nginx/

**Monitoring Tools:**
- Fail2ban Wiki: https://www.fail2ban.org/wiki/
- ClamAV Docs: https://docs.clamav.net/

---

**Need Help?** Check logs first:
```bash
# Quick diagnostics
systemctl status nama-marine-backend
journalctl -u nama-marine-backend -n 50
cat /var/log/security-scan.log | tail -20
```

**Still stuck?** Provide error messages + logs for troubleshooting.

---

**Last updated:** January 21, 2027  
**Script version:** v2.0 (Hardened Production)  
**Author:** NAMA Marine Dev Team
