#!/bin/bash
# ============================================================================
# NAMA Marine - HARDENED Migration to Domainesia VPS
# From: Rumahweb (suspended - crypto miner attack)
# To: Domainesia (new VPS with security hardening)
# ============================================================================
# Features:
# - PHP 8.3 (latest stable)
# - VPS hardening (anti crypto miner)
# - Fail2ban (auto-ban attacks)
# - ClamAV (antivirus)
# - Nginx rate limiting & security headers
# - Process monitoring (CPU spike alerts)
# ============================================================================
# Run on NEW Domainesia VPS as root
# Usage: bash migrate-to-domainesia.sh
# ============================================================================

set -e  # Exit on any error

echo "=========================================="
echo "  �️  NAMA Marine - HARDENED Migration"
echo "=========================================="
echo ""
echo "📍 Target: Domainesia Ubuntu 22.04"
echo "� Security: Production-grade hardening"
echo "📦 PHP Version: 8.3 (latest)"
echo ""

# ─── System Update ───────────────────────────────────────────────────────────
echo "📦 Step 1/12: Updating system packages..."
apt update -qq
DEBIAN_FRONTEND=noninteractive apt upgrade -y -qq

# ─── Install PHP 8.3 Repository ──────────────────────────────────────────────
echo ""
echo "📦 Step 2/12: Adding PHP 8.3 repository..."
apt install -y -qq software-properties-common
add-apt-repository -y ppa:ondrej/php > /dev/null 2>&1
apt update -qq

# ─── Install LEMP Stack + Security Tools ─────────────────────────────────────
echo ""
echo "📦 Step 3/12: Installing LEMP + Security Tools..."
echo "  → Nginx, MySQL, PHP 8.3"
echo "  → Fail2ban, ClamAV, UFW"
DEBIAN_FRONTEND=noninteractive apt install -y -qq \
  nginx \
  mysql-server \
  php8.3-fpm \
  php8.3-mysql \
  php8.3-mbstring \
  php8.3-xml \
  php8.3-curl \
  php8.3-zip \
  php8.3-gd \
  php8.3-intl \
  php8.3-bcmath \
  git \
  unzip \
  curl \
  wget \
  fail2ban \
  clamav \
  clamav-daemon \
  ufw \
  htop \
  sysstat

# ─── Install Node.js 20 ──────────────────────────────────────────────────────
echo ""
echo "📦 Step 4/12: Installing Node.js 20 LTS..."
curl -fsSL https://deb.nodesource.com/setup_20.x | bash - > /dev/null 2>&1
apt install -y -qq nodejs

# ─── Install Composer ────────────────────────────────────────────────────────
echo ""
echo "📦 Step 5/12: Installing Composer..."
curl -sS https://getcomposer.org/installer | php > /dev/null 2>&1
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# ─── VPS HARDENING (Anti Crypto Miner) ───────────────────────────────────────
echo ""
echo "🛡️  Step 6/12: VPS Hardening (Anti Crypto Miner)..."

# Disable IPv6 (reduce attack surface)
echo "  → Disabling IPv6..."
cat >> /etc/sysctl.conf <<'SYSCTL'
# Disable IPv6
net.ipv6.conf.all.disable_ipv6 = 1
net.ipv6.conf.default.disable_ipv6 = 1
net.ipv6.conf.lo.disable_ipv6 = 1

# IP Spoofing protection
net.ipv4.conf.all.rp_filter = 1
net.ipv4.conf.default.rp_filter = 1

# Ignore ICMP redirects
net.ipv4.conf.all.accept_redirects = 0
net.ipv6.conf.all.accept_redirects = 0

# Ignore send redirects
net.ipv4.conf.all.send_redirects = 0

# Disable source packet routing
net.ipv4.conf.all.accept_source_route = 0
net.ipv6.conf.all.accept_source_route = 0

# Log Martians
net.ipv4.conf.all.log_martians = 1

# Kernel hardening
kernel.kptr_restrict = 2
kernel.dmesg_restrict = 1
SYSCTL

sysctl -p > /dev/null 2>&1

# Disable unnecessary services
echo "  → Disabling unnecessary services..."
systemctl disable bluetooth.service 2>/dev/null || true
systemctl disable avahi-daemon.service 2>/dev/null || true

# Create crypto miner detection script
echo "  → Creating crypto miner detection script..."
cat > /usr/local/bin/detect-crypto-miner.sh <<'DETECT'
#!/bin/bash
# Detect suspicious high CPU processes (potential crypto miners)

HIGH_CPU_THRESHOLD=70
LOG_FILE="/var/log/crypto-miner-detection.log"

# Get top CPU processes
SUSPICIOUS=$(ps aux --sort=-%cpu | awk 'NR>1 && $3>'$HIGH_CPU_THRESHOLD' {print $2":"$11":"$3}')

if [ ! -z "$SUSPICIOUS" ]; then
    echo "[$(date)] ⚠️  HIGH CPU DETECTED:" >> $LOG_FILE
    echo "$SUSPICIOUS" >> $LOG_FILE
    
    # Known crypto miner process names
    KNOWN_MINERS="xmrig|minerd|cpuminer|cryptonight|kinsing|kdevtmpfsi|xmr-stak"
    
    for PROC in $SUSPICIOUS; do
        PID=$(echo $PROC | cut -d: -f1)
        CMD=$(echo $PROC | cut -d: -f2)
        CPU=$(echo $PROC | cut -d: -f3)
        
        if echo "$CMD" | grep -Eqi "$KNOWN_MINERS"; then
            echo "[$(date)] 🚨 CRYPTO MINER DETECTED: PID=$PID CMD=$CMD CPU=$CPU%" >> $LOG_FILE
            kill -9 $PID
            echo "[$(date)] ✅ Killed process: $PID" >> $LOG_FILE
        fi
    done
fi
DETECT

chmod +x /usr/local/bin/detect-crypto-miner.sh

# Setup cron job to run every 5 minutes
echo "  → Scheduling crypto miner detection (every 5 min)..."
(crontab -l 2>/dev/null; echo "*/5 * * * * /usr/local/bin/detect-crypto-miner.sh") | crontab -

# ─── Setup Firewall (UFW) ────────────────────────────────────────────────────
echo ""
echo "🔒 Step 7/12: Configuring firewall (UFW)..."
ufw --force reset
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp   # SSH
ufw allow 80/tcp   # HTTP
ufw allow 443/tcp  # HTTPS
ufw --force enable
echo "  ✅ Firewall active (SSH, HTTP, HTTPS allowed)"

# ─── Configure Fail2ban (Brute Force Protection) ─────────────────────────────
echo ""
echo "🔒 Step 8/12: Configuring Fail2ban (SSH + Nginx protection)..."

cat > /etc/fail2ban/jail.local <<'F2B'
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3
destemail = root@localhost
sendername = Fail2Ban
action = %(action_mwl)s

[sshd]
enabled = true
port = 22
filter = sshd
logpath = /var/log/auth.log
maxretry = 3

[nginx-http-auth]
enabled = true
filter = nginx-http-auth
port = http,https
logpath = /var/log/nginx/error.log

[nginx-limit-req]
enabled = true
filter = nginx-limit-req
port = http,https
logpath = /var/log/nginx/error.log

[nginx-botsearch]
enabled = true
filter = nginx-botsearch
port = http,https
logpath = /var/log/nginx/access.log
maxretry = 2
F2B

systemctl enable fail2ban
systemctl restart fail2ban
echo "  ✅ Fail2ban active (SSH + Nginx protected)"

# ─── Clone Repositories ──────────────────────────────────────────────────────
echo ""
echo "📥 Step 9/12: Cloning application repositories from GitHub..."
cd /var/www

# Backend
if [ ! -d "marine-backend" ]; then
  echo "  → Cloning backend..."
  git clone -q https://github.com/fahdiazhannu31/marine-backend.git
fi

cd marine-backend
git checkout -q security/auth-hardening
git pull -q origin security/auth-hardening

# Create .env if not exists
if [ ! -f .env ]; then
  echo "  → Creating .env from template..."
  cp .env.production.example .env
fi

echo "  → Installing PHP dependencies (Composer)..."
composer install --no-dev --optimize-autoloader --quiet

# Create writable directories if not exist (CodeIgniter 4)
mkdir -p writable/{cache,logs,session,uploads,debugbar}

# Set permissions
chown -R www-data:www-data /var/www/marine-backend
chmod -R 755 writable/

# Frontend
cd /var/www
if [ ! -d "marine-frontend" ]; then
  echo "  → Cloning frontend..."
  git clone -q https://github.com/fahdiazhannu31/marine-frontend.git
fi

cd marine-frontend
git checkout -q security/auth-hardening
git pull -q origin security/auth-hardening

echo "  → Installing Node.js dependencies..."
npm install --production --silent

echo "  → Building production bundle..."
npm run build

# ─── Setup Database ──────────────────────────────────────────────────────────
echo ""
echo "🗄️  Step 10/12: Setting up MySQL database..."

# Secure MySQL installation
MYSQL_ROOT_PASS=$(openssl rand -base64 24)
mysql <<EOF
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${MYSQL_ROOT_PASS}';
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';
FLUSH PRIVILEGES;
EOF

# Create database and user
DB_PASS=$(openssl rand -base64 24)
mysql -u root -p"${MYSQL_ROOT_PASS}" <<EOF
CREATE DATABASE nama_mrn CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'nama_user'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON nama_mrn.* TO 'nama_user'@'localhost';
FLUSH PRIVILEGES;
EOF

# Harden MySQL config
cat >> /etc/mysql/mysql.conf.d/mysqld.cnf <<'MYSQLCONF'

# Security hardening
bind-address = 127.0.0.1
local-infile = 0
skip-name-resolve
MYSQLCONF

systemctl restart mysql

# Update .env with database credentials
cd /var/www/marine-backend
sed -i "s/database.default.database = .*/database.default.database = nama_mrn/" .env
sed -i "s/database.default.username = .*/database.default.username = nama_user/" .env
sed -i "s/database.default.password = .*/database.default.password = ${DB_PASS}/" .env

echo "  ✅ Database created: nama_mrn"
echo "  ✅ User created: nama_user"
echo "  ✅ Password saved to .env"

# Save credentials to file for reference
cat > /root/mysql_credentials.txt <<EOF
MySQL Database Credentials
==========================
ROOT Password: ${MYSQL_ROOT_PASS}
Database: nama_mrn
User: nama_user
Password: ${DB_PASS}

Backend .env has been updated automatically.

To import database backup, run:
  mysql -u nama_user -p nama_mrn < /path/to/backup.sql
  
Or from Windows (remote):
  mysql -h VPS_IP -u nama_user -p nama_mrn < "C:\path\to\backup.sql"
EOF

chmod 600 /root/mysql_credentials.txt

# ─── Configure Nginx (HARDENED) ──────────────────────────────────────────────
echo ""
echo "⚙️  Step 11/12: Configuring Nginx (HARDENED with rate limiting)..."

# Remove default Nginx config
rm -f /etc/nginx/sites-enabled/default

# Create rate limit zones in nginx.conf
cat > /etc/nginx/conf.d/rate-limit.conf <<'RATE'
# Rate limiting zones (anti DDoS)
limit_req_zone $binary_remote_addr zone=api_limit:10m rate=20r/s;
limit_req_zone $binary_remote_addr zone=general_limit:10m rate=100r/s;
limit_conn_zone $binary_remote_addr zone=conn_limit:10m;

# Hide Nginx version
server_tokens off;
RATE

# Create hardened Nginx site config
cat > /etc/nginx/sites-available/nama-marine <<'NGINX'
server {
    listen 80;
    server_name _;
    root /var/www/marine-frontend/dist;
    index index.html;

    # Connection limits
    limit_conn conn_limit 10;
    limit_req zone=general_limit burst=50 nodelay;

    # Security headers (OWASP recommendations)
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;
    
    # Prevent clickjacking
    add_header Content-Security-Policy "frame-ancestors 'self'" always;

    # Frontend - React Router
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Backend API - Proxy to CodeIgniter (with stricter rate limit)
    location /api {
        limit_req zone=api_limit burst=10 nodelay;
        
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # Timeout settings
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
        
        # Buffer settings
        proxy_buffering on;
        proxy_buffer_size 4k;
        proxy_buffers 8 4k;
        
        # WebSocket support (for future features)
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }

    # Static assets caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot|pdf)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Block common exploit attempts
    location ~* (\.git|\.env|\.htaccess|web\.config|\.sql|\.zip|\.tar\.gz)$ {
        deny all;
        return 404;
    }

    # Deny access to sensitive files
    location ~ /\.(?!well-known).* {
        deny all;
        return 404;
    }
    
    # Block user agents (common bots/scanners)
    if ($http_user_agent ~* (nmap|nikto|wikto|sf|sqlmap|bsqlbf|w3af|acunetix|havij|appscan)) {
        return 403;
    }

    # Block common attack patterns
    location ~* (eval\(|base64_decode|php://input|mosConfig_|passthru|proc_open|execute) {
        deny all;
        return 403;
    }
}
NGINX

# Enable site
ln -sf /etc/nginx/sites-available/nama-marine /etc/nginx/sites-enabled/

# Test nginx config
nginx -t

# Restart nginx
systemctl restart nginx
systemctl enable nginx

echo "  ✅ Nginx hardened with rate limiting & security headers"

# ─── Create Systemd Service for Backend ─────────────────────────────────────
echo ""
echo "🔧 Creating systemd service for CodeIgniter backend..."

cat > /etc/systemd/system/nama-marine-backend.service <<'EOF'
[Unit]
Description=NAMA Marine Backend (CodeIgniter)
After=network.target mysql.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/marine-backend
ExecStart=/usr/bin/php spark serve --host=0.0.0.0 --port=8080
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable nama-marine-backend
systemctl start nama-marine-backend

# Wait for backend to start
sleep 3

# ─── Final Checks ────────────────────────────────────────────────────────────
echo ""
echo "=========================================="
echo "  ✅ Migration Complete!"
echo "=========================================="
echo ""

# Get public IP
PUBLIC_IP=$(curl -s ifconfig.me)

echo "📋 Server Information:"
echo "  🌐 Public IP: ${PUBLIC_IP}"
echo "  💾 Database: nama_mrn"
echo "  👤 DB User: nama_user"
echo "  🔑 DB Password: ${DB_PASS}"
echo "     (Saved to: /root/mysql_credentials.txt)"
echo ""

echo "🔍 Service Status:"
systemctl is-active --quiet nginx && echo "  ✅ Nginx: Running" || echo "  ❌ Nginx: Stopped"
systemctl is-active --quiet mysql && echo "  ✅ MySQL: Running" || echo "  ❌ MySQL: Stopped"
systemctl is-active --quiet nama-marine-backend && echo "  ✅ Backend: Running" || echo "  ❌ Backend: Stopped"

echo ""
echo "🌐 Access URLs:"
echo "  Frontend: http://${PUBLIC_IP}"
echo "  Backend API: http://${PUBLIC_IP}/api/home"
echo ""

echo "📝 Next Steps:"
echo "  1️⃣  Import database backup:"
echo "     mysql -u nama_user -p nama_mrn < /path/to/backup.sql"
echo ""
echo "  2️⃣  Test application:"
echo "     curl http://localhost/api/home"
echo ""
echo "  3️⃣  Update domain DNS (at Domainesia):"
echo "     Login: https://panel.domainesia.com"
echo "     Menu: Domain → DNS Management"
echo "     Update A record to: ${PUBLIC_IP}"
echo ""
echo "  4️⃣  Setup SSL certificate (after domain points here):"
echo "     apt install certbot python3-certbot-nginx -y"
echo "     certbot --nginx -d nama-marin.com -d www.nama-marin.com"
echo ""

echo "📁 Important Files:"
echo "  Backend config: /var/www/marine-backend/.env"
echo "  Nginx config: /etc/nginx/sites-available/nama-marine"
echo "  MySQL credentials: /root/mysql_credentials.txt"
echo "  Backend logs: journalctl -u nama-marine-backend -f"
echo ""

echo "=========================================="
echo "  🎉 Ready for Database Import!"
echo "=========================================="

# ─── Create Systemd Service for Backend ─────────────────────────────────────
echo ""
echo "🔧 Step 12/12: Creating systemd service for CodeIgniter backend..."

cat > /etc/systemd/system/nama-marine-backend.service <<'SERVICE'
[Unit]
Description=NAMA Marine Backend (CodeIgniter)
After=network.target mysql.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/marine-backend
ExecStart=/usr/bin/php spark serve --host=127.0.0.1 --port=8080
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal

# Security hardening
NoNewPrivileges=true
PrivateTmp=true
ProtectSystem=strict
ProtectHome=true
ReadWritePaths=/var/www/marine-backend/writable

[Install]
WantedBy=multi-user.target
SERVICE

systemctl daemon-reload
systemctl enable nama-marine-backend
systemctl start nama-marine-backend

# Wait for backend to start
sleep 3

# ─── Setup ClamAV Antivirus ──────────────────────────────────────────────────
echo ""
echo "🦠 Setting up ClamAV antivirus..."
echo "  → Updating virus definitions (this may take 5-10 min)..."
systemctl stop clamav-freshclam 2>/dev/null || true
freshclam --quiet &
FRESHCLAM_PID=$!

# Continue with other tasks while freshclam updates in background

# ─── Create Daily Security Scan Script ──────────────────────────────────────
echo ""
echo "🔍 Creating daily security scan script..."

cat > /usr/local/bin/daily-security-scan.sh <<'SCAN'
#!/bin/bash
# Daily security scan script

LOG_FILE="/var/log/security-scan.log"
echo "[$(date)] ═══ Daily Security Scan ═══" >> $LOG_FILE

# 1. Scan for crypto miners
echo "→ Scanning for crypto miners..." >> $LOG_FILE
/usr/local/bin/detect-crypto-miner.sh

# 2. Scan web directories for malware
echo "→ Scanning /var/www for malware..." >> $LOG_FILE
clamscan -r -i /var/www --log=/var/log/clamav-scan.log 2>&1 | grep -i "infected" >> $LOG_FILE || echo "  ✓ No threats found" >> $LOG_FILE

# 3. Check for failed login attempts
FAILED_LOGINS=$(grep "Failed password" /var/log/auth.log | wc -l)
echo "→ Failed SSH logins today: $FAILED_LOGINS" >> $LOG_FILE

# 4. Check for suspicious network connections
SUSPICIOUS_CONNS=$(netstat -tnp 2>/dev/null | grep -v "127.0.0.1\|0.0.0.0" | wc -l)
echo "→ Active external connections: $SUSPICIOUS_CONNS" >> $LOG_FILE

# 5. Disk usage check
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
echo "→ Root disk usage: ${DISK_USAGE}%" >> $LOG_FILE
if [ $DISK_USAGE -gt 80 ]; then
    echo "  ⚠️  WARNING: Disk usage over 80%!" >> $LOG_FILE
fi

echo "[$(date)] ═══ Scan Complete ═══" >> $LOG_FILE
echo "" >> $LOG_FILE
SCAN

chmod +x /usr/local/bin/daily-security-scan.sh

# Schedule daily scan at 2 AM
(crontab -l 2>/dev/null; echo "0 2 * * * /usr/local/bin/daily-security-scan.sh") | crontab -

# ─── Create System Monitoring Script ─────────────────────────────────────────
echo ""
echo "📊 Creating system monitoring script..."

cat > /usr/local/bin/monitor-system.sh <<'MONITOR'
#!/bin/bash
# Monitor system resources and alert on anomalies

LOG_FILE="/var/log/system-monitor.log"

# CPU usage
CPU_USAGE=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1 | cut -d'.' -f1)
if [ $CPU_USAGE -gt 80 ]; then
    echo "[$(date)] ⚠️  HIGH CPU: ${CPU_USAGE}%" >> $LOG_FILE
    ps aux --sort=-%cpu | head -10 >> $LOG_FILE
fi

# Memory usage
MEM_USAGE=$(free | grep Mem | awk '{print int($3/$2 * 100)}')
if [ $MEM_USAGE -gt 90 ]; then
    echo "[$(date)] ⚠️  HIGH MEMORY: ${MEM_USAGE}%" >> $LOG_FILE
    ps aux --sort=-%mem | head -10 >> $LOG_FILE
fi

# Check if backend service is running
if ! systemctl is-active --quiet nama-marine-backend; then
    echo "[$(date)] 🚨 Backend service DOWN! Restarting..." >> $LOG_FILE
    systemctl restart nama-marine-backend
fi

# Check if Nginx is running
if ! systemctl is-active --quiet nginx; then
    echo "[$(date)] 🚨 Nginx DOWN! Restarting..." >> $LOG_FILE
    systemctl restart nginx
fi

# Check if MySQL is running
if ! systemctl is-active --quiet mysql; then
    echo "[$(date)] 🚨 MySQL DOWN! Restarting..." >> $LOG_FILE
    systemctl restart mysql
fi
MONITOR

chmod +x /usr/local/bin/monitor-system.sh

# Run monitoring every 5 minutes
(crontab -l 2>/dev/null; echo "*/5 * * * * /usr/local/bin/monitor-system.sh") | crontab -

# Wait for freshclam to finish
wait $FRESHCLAM_PID 2>/dev/null
systemctl start clamav-freshclam

echo "  ✅ ClamAV configured (daily scans scheduled)"
echo "  ✅ System monitoring active (every 5 min)"

# ─── Final Checks ────────────────────────────────────────────────────────────
echo ""
echo "=========================================="
echo "  ✅ HARDENED Migration Complete!"
echo "=========================================="
echo ""

# Get public IP
PUBLIC_IP=$(curl -s ifconfig.me)

echo "📋 Server Information:"
echo "  🌐 Public IP: ${PUBLIC_IP}"
echo "  🐘 PHP Version: $(php -v | head -1 | awk '{print $2}')"
echo "  💾 Database: nama_mrn"
echo "  👤 DB User: nama_user"
echo "  🔑 Credentials: /root/mysql_credentials.txt"
echo ""

echo "🔍 Service Status:"
systemctl is-active --quiet nginx && echo "  ✅ Nginx: Running" || echo "  ❌ Nginx: Stopped"
systemctl is-active --quiet mysql && echo "  ✅ MySQL: Running" || echo "  ❌ MySQL: Stopped"
systemctl is-active --quiet nama-marine-backend && echo "  ✅ Backend: Running" || echo "  ❌ Backend: Stopped"
systemctl is-active --quiet fail2ban && echo "  ✅ Fail2ban: Running" || echo "  ❌ Fail2ban: Stopped"
systemctl is-active --quiet clamav-freshclam && echo "  ✅ ClamAV: Running" || echo "  ❌ ClamAV: Stopped"

echo ""
echo "🛡️  Security Features:"
echo "  ✅ PHP 8.3 (latest stable)"
echo "  ✅ Fail2ban (SSH + Nginx protection)"
echo "  ✅ UFW Firewall (SSH, HTTP, HTTPS only)"
echo "  ✅ Nginx rate limiting (20 req/s API, 100 req/s general)"
echo "  ✅ ClamAV antivirus (daily scans)"
echo "  ✅ Crypto miner detection (every 5 min)"
echo "  ✅ System monitoring (every 5 min)"
echo "  ✅ Security headers (OWASP)"
echo "  ✅ MySQL hardening (local-only, no remote access)"
echo ""

echo "🌐 Access URLs:"
echo "  Frontend: http://${PUBLIC_IP}"
echo "  Backend API: http://${PUBLIC_IP}/api/home"
echo ""

echo "📝 Next Steps:"
echo "  1️⃣  Import database backup:"
echo "     mysql -u nama_user -p nama_mrn < /path/to/backup.sql"
echo "     (Password in /root/mysql_credentials.txt)"
echo ""
echo "  2️⃣  Test application:"
echo "     curl http://localhost/api/home"
echo "     curl http://${PUBLIC_IP}"
echo ""
echo "  3️⃣  Update domain DNS A record:"
echo "     nama-marin.com → ${PUBLIC_IP}"
echo "     www.nama-marin.com → ${PUBLIC_IP}"
echo "     (Update at Rumahweb DNS panel: https://clientzone.rumahweb.com)"
echo ""
echo "  4️⃣  Setup SSL certificate (after DNS propagation):"
echo "     apt install certbot python3-certbot-nginx -y"
echo "     certbot --nginx -d nama-marin.com -d www.nama-marin.com"
echo ""

echo "📁 Important Files:"
echo "  Backend config: /var/www/marine-backend/.env"
echo "  Nginx config: /etc/nginx/sites-available/nama-marine"
echo "  MySQL credentials: /root/mysql_credentials.txt"
echo "  Backend logs: journalctl -u nama-marine-backend -f"
echo "  Security scan log: /var/log/security-scan.log"
echo "  System monitor log: /var/log/system-monitor.log"
echo "  Crypto miner log: /var/log/crypto-miner-detection.log"
echo ""

echo "🔧 Useful Commands:"
echo "  Check security: cat /var/log/security-scan.log"
echo "  View banned IPs: fail2ban-client status sshd"
echo "  Monitor CPU: htop"
echo "  Check firewall: ufw status"
echo "  Scan for viruses: clamscan -r -i /var/www"
echo ""

echo "=========================================="
echo "  🎉 Server Hardened & Ready!"
echo "=========================================="
