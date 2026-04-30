#!/bin/bash
################################################################################
# Production Server Setup Script
#
# This script prepares a production server for GitHub Actions deployment
# Run this on the production server as root or with sudo
#
# Prerequisites:
#   - Ubuntu/Debian server with internet access
#   - Root or sudo access
#   - The repository cloned at /home/deploy/dcit-cvsu-laravel-training
#
# Usage:
#   sudo bash scripts/setup-production-server.sh
################################################################################

set -e

echo "════════════════════════════════════════════════════════════════════════"
echo "  Production Server Setup for GitHub Actions CI/CD"
echo "════════════════════════════════════════════════════════════════════════"
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "❌ This script must be run as root"
    echo "   Run: sudo bash $0"
    exit 1
fi

echo "✅ Running as root"
echo ""

# ═══════════════════════════════════════════════════════════════════════════
# Step 1: Install Required Tools
# ═══════════════════════════════════════════════════════════════════════════

echo "════════════════════════════════════════════════════════════════════════"
echo "  STEP 1: Installing Required Tools"
echo "════════════════════════════════════════════════════════════════════════"
echo ""

echo "📦 Updating package lists..."
apt-get update -qq

echo "📦 Installing Docker..."
if ! command -v docker &> /dev/null; then
    apt-get install -y docker.io > /dev/null
    systemctl start docker
    systemctl enable docker
    echo "✅ Docker installed"
else
    echo "✅ Docker already installed"
fi

echo ""
echo "📦 Installing Docker Compose..."
if ! command -v docker-compose &> /dev/null; then
    apt-get install -y docker-compose > /dev/null
    echo "✅ Docker Compose installed"
else
    echo "✅ Docker Compose already installed"
fi

echo ""
echo "📦 Installing Git..."
if ! command -v git &> /dev/null; then
    apt-get install -y git > /dev/null
    echo "✅ Git installed"
else
    echo "✅ Git already installed"
fi

echo ""
echo "📦 Installing curl..."
if ! command -v curl &> /dev/null; then
    apt-get install -y curl > /dev/null
    echo "✅ curl installed"
else
    echo "✅ curl already installed"
fi

echo ""
echo "📦 Installing Cloudflare Tunnel..."
if ! command -v cloudflared &> /dev/null; then
    curl -L https://pkg.cloudflare.com/cloudflare-release-key.gpg | tee /etc/apt/trusted.gpg.d/cloudflare.gpg > /dev/null
    echo 'deb [signed-by=/etc/apt/trusted.gpg.d/cloudflare.gpg] https://pkg.cloudflare.com/linux focal main' | tee /etc/apt/sources.list.d/cloudflare.list > /dev/null
    apt-get update -qq
    apt-get install -y cloudflared > /dev/null
    echo "✅ Cloudflared installed"
else
    echo "✅ Cloudflared already installed"
fi

echo ""
echo "✅ All tools installed"
echo ""

# ═══════════════════════════════════════════════════════════════════════════
# Step 2: Create Deploy User
# ═══════════════════════════════════════════════════════════════════════════

echo "════════════════════════════════════════════════════════════════════════"
echo "  STEP 2: Setting Up Deploy User"
echo "════════════════════════════════════════════════════════════════════════"
echo ""

if id "deploy" &>/dev/null; then
    echo "✅ Deploy user already exists"
else
    echo "👤 Creating deploy user..."
    useradd -m -s /bin/bash deploy
    echo "✅ Deploy user created"
fi

echo ""
echo "Setting Docker permissions..."
usermod -aG docker deploy > /dev/null
echo "✅ Deploy user can run docker"

# ═══════════════════════════════════════════════════════════════════════════
# Step 3: Prepare Application Directory
# ═══════════════════════════════════════════════════════════════════════════

echo ""
echo "════════════════════════════════════════════════════════════════════════"
echo "  STEP 3: Preparing Application Directory"
echo "════════════════════════════════════════════════════════════════════════"
echo ""

APP_DIR="/home/deploy/dcit-cvsu-laravel-training"

if [ -d "$APP_DIR" ]; then
    echo "✅ Application directory exists: $APP_DIR"
else
    echo "📁 Creating application directory..."
    mkdir -p "$APP_DIR"
    echo "✅ Application directory created"
fi

echo ""
echo "Setting directory ownership..."
chown -R deploy:deploy "$APP_DIR"
chmod 755 "$APP_DIR"
echo "✅ Directory ownership set to deploy user"

# ═══════════════════════════════════════════════════════════════════════════
# Step 4: Initialize Laravel Environment
# ═══════════════════════════════════════════════════════════════════════════

echo ""
echo "════════════════════════════════════════════════════════════════════════"
echo "  STEP 4: Initializing Laravel Environment"
echo "════════════════════════════════════════════════════════════════════════"
echo ""

if [ -f "$APP_DIR/.env" ]; then
    echo "✅ .env file already exists"
else
    echo "📝 Creating .env file..."
    cp "$APP_DIR/.env.production" "$APP_DIR/.env"
    chown deploy:deploy "$APP_DIR/.env"
    chmod 600 "$APP_DIR/.env"
    echo "✅ .env file created"
fi

echo ""
echo "Creating required directories..."
sudo -u deploy mkdir -p "$APP_DIR/bootstrap/cache"
sudo -u deploy mkdir -p "$APP_DIR/storage/framework/cache"
sudo -u deploy mkdir -p "$APP_DIR/storage/framework/sessions"
sudo -u deploy mkdir -p "$APP_DIR/storage/framework/views"
sudo -u deploy mkdir -p "$APP_DIR/storage/logs"
sudo -u deploy mkdir -p "$APP_DIR/database"
echo "✅ All directories created"

echo ""
echo "Creating SQLite database..."
if [ ! -f "$APP_DIR/database/database.sqlite" ]; then
    sudo -u deploy touch "$APP_DIR/database/database.sqlite"
    chmod 666 "$APP_DIR/database/database.sqlite"
    echo "✅ SQLite database created"
else
    echo "✅ SQLite database already exists"
fi

# ═══════════════════════════════════════════════════════════════════════════
# Step 5: Set Up Cloudflare Tunnel
# ═══════════════════════════════════════════════════════════════════════════

echo ""
echo "════════════════════════════════════════════════════════════════════════"
echo "  STEP 5: Cloudflare Tunnel Setup (Optional)"
echo "════════════════════════════════════════════════════════════════════════"
echo ""

read -p "Do you want to set up a Cloudflare Tunnel? (y/n) " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "🌐 Setting up Cloudflare Tunnel..."
    echo ""
    echo "NOTE: You'll need to authenticate with Cloudflare"
    echo "      This will open your browser"
    echo ""
    echo "To authenticate, run:"
    echo "  sudo -u deploy cloudflared tunnel login"
    echo ""
    echo "Then create a tunnel with:"
    echo "  sudo -u deploy cloudflared tunnel create php-app"
    echo ""
    echo "Update /etc/cloudflared/config.yml with the tunnel ID"
    echo ""
    echo "Then start the service:"
    echo "  sudo systemctl enable cloudflared"
    echo "  sudo systemctl start cloudflared"
    echo ""
else
    echo "⏭️  Skipping Cloudflare Tunnel setup"
fi

# ═══════════════════════════════════════════════════════════════════════════
# Step 6: Set Up Port Mapping
# ═══════════════════════════════════════════════════════════════════════════

echo ""
echo "════════════════════════════════════════════════════════════════════════"
echo "  STEP 6: Port Mapping Setup"
echo "════════════════════════════════════════════════════════════════════════"
echo ""

if [ ! -f "$APP_DIR/compose.override.yml" ]; then
    echo "📝 Creating compose.override.yml (port 8087 mapping)..."
    cat > "$APP_DIR/compose.override.yml" << 'EOF'
services:
  app:
    ports:
      - "8087:8080"
EOF
    chown deploy:deploy "$APP_DIR/compose.override.yml"
    echo "✅ compose.override.yml created"
else
    echo "✅ compose.override.yml already exists"
fi

# ═══════════════════════════════════════════════════════════════════════════
# Step 7: Setup Summary
# ═══════════════════════════════════════════════════════════════════════════

echo ""
echo "════════════════════════════════════════════════════════════════════════"
echo "  ✅ Server Setup Complete!"
echo "════════════════════════════════════════════════════════════════════════"
echo ""
echo "Summary:"
echo "  ✅ Docker installed and running"
echo "  ✅ Docker Compose installed"
echo "  ✅ Git installed"
echo "  ✅ Cloudflared installed"
echo "  ✅ Deploy user created"
echo "  ✅ Application directory prepared"
echo "  ✅ .env file created"
echo "  ✅ Required directories created"
echo "  ✅ SQLite database created"
echo "  ✅ Port 8087 mapping configured"
echo ""
echo "Next steps:"
echo "  1. Set up SSH key authentication (see SETUP_GITHUB_ACTIONS.md)"
echo "  2. Configure Cloudflare Tunnel (see steps above)"
echo "  3. Update .env file if needed:"
echo "     nano $APP_DIR/.env"
echo "  4. Test deployment:"
echo "     cd $APP_DIR && docker compose up -d"
echo ""
echo "Server is ready for GitHub Actions deployment!"
echo ""
