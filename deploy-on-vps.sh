#!/bin/bash

# SEPA Fullstack VPS Deployment Script
# Run this script ON the VPS after pushing code from local

set -e  # Exit on error

echo "============================================"
echo "VPS Deployment Script - SEPA Fullstack"
echo "============================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration (UPDATE THESE PATHS)
PROJECT_DIR="/var/www/sepafullstack"
BACKEND_DIR="$PROJECT_DIR/backend"
FRONTEND_DIR="$PROJECT_DIR/frontend"
LOG_DIR="$BACKEND_DIR/writable/logs"

echo -e "${YELLOW}[1/7]${NC} Checking current directory..."
if [ ! -d "$PROJECT_DIR" ]; then
    echo -e "${RED}ERROR: Project directory not found: $PROJECT_DIR${NC}"
    echo "Please update PROJECT_DIR variable in this script"
    exit 1
fi

cd "$PROJECT_DIR"
echo -e "${GREEN}✓${NC} Current directory: $(pwd)"
echo ""

echo -e "${YELLOW}[2/7]${NC} Pulling latest code from repository..."
git pull origin main

if [ $? -ne 0 ]; then
    echo -e "${RED}ERROR: Git pull failed${NC}"
    exit 1
fi
echo -e "${GREEN}✓${NC} Code updated"
echo ""

echo -e "${YELLOW}[3/7]${NC} Checking Node.js and NPM..."
if ! command -v node &> /dev/null; then
    echo -e "${RED}ERROR: Node.js not found. Please install Node.js first.${NC}"
    exit 1
fi

if ! command -v npm &> /dev/null; then
    echo -e "${RED}ERROR: NPM not found. Please install NPM first.${NC}"
    exit 1
fi

echo "Node version: $(node --version)"
echo "NPM version: $(npm --version)"
echo -e "${GREEN}✓${NC} Node.js and NPM available"
echo ""

echo -e "${YELLOW}[4/7]${NC} Installing frontend dependencies (if needed)..."
cd "$FRONTEND_DIR"

if [ ! -d "node_modules" ]; then
    echo "node_modules not found, running npm install..."
    npm install
else
    echo "node_modules exists, skipping npm install"
fi
echo -e "${GREEN}✓${NC} Dependencies ready"
echo ""

echo -e "${YELLOW}[5/7]${NC} Building frontend..."
npm run build

if [ $? -ne 0 ]; then
    echo -e "${RED}ERROR: Frontend build failed${NC}"
    exit 1
fi
echo -e "${GREEN}✓${NC} Frontend built successfully"
echo ""

echo -e "${YELLOW}[6/7]${NC} Setting permissions..."
cd "$BACKEND_DIR"

# Ensure writable directories have correct permissions
chmod -R 777 writable/
chmod -R 777 public/uploads/ 2>/dev/null || true

echo -e "${GREEN}✓${NC} Permissions set"
echo ""

echo -e "${YELLOW}[7/7]${NC} Checking services..."

# Check if Apache/Nginx is running
if systemctl is-active --quiet apache2; then
    echo "Apache is running"
elif systemctl is-active --quiet nginx; then
    echo "Nginx is running"
else
    echo -e "${YELLOW}Warning: Neither Apache nor Nginx seems to be running${NC}"
fi

echo ""
echo "============================================"
echo -e "${GREEN}✓ DEPLOYMENT COMPLETED SUCCESSFULLY${NC}"
echo "============================================"
echo ""
echo "VERIFICATION STEPS:"
echo "1. Test manifest upload (should see success toast only)"
echo "2. Test QR scan for today/tomorrow trips"
echo "3. Check logs for errors:"
echo "   tail -f $LOG_DIR/log-\$(date +%Y-%m-%d).log"
echo ""
echo "If issues occur, rollback with:"
echo "   git revert HEAD && git push origin main"
echo "   Then run this script again"
echo ""
