#!/bin/bash
################################################################################
# SSH Key Setup Script
#
# This script generates SSH keys and helps set them up for GitHub Actions
# Run this on your LOCAL machine (not the server)
#
# Usage:
#   bash scripts/setup-ssh-keys.sh
################################################################################

set -e

echo "════════════════════════════════════════════════════════════════════════"
echo "  SSH Key Setup for GitHub Actions"
echo "════════════════════════════════════════════════════════════════════════"
echo ""

# ═══════════════════════════════════════════════════════════════════════════
# Step 1: Check for Existing Key
# ═══════════════════════════════════════════════════════════════════════════

echo "Checking for existing SSH key..."
echo ""

if [ -f ~/.ssh/github_deploy ]; then
    echo "✅ SSH key already exists: ~/.ssh/github_deploy"
    echo ""
    read -p "Do you want to use this key? (y/n) " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        USE_EXISTING=true
    else
        echo ""
        echo "⚠️  Existing key will be overwritten"
        echo ""
        USE_EXISTING=false
    fi
else
    echo "No existing SSH key found"
    echo ""
    USE_EXISTING=false
fi

# ═══════════════════════════════════════════════════════════════════════════
# Step 2: Generate New Key (if needed)
# ═══════════════════════════════════════════════════════════════════════════

if [ "$USE_EXISTING" = false ]; then
    echo "════════════════════════════════════════════════════════════════════════"
    echo "  Generating New SSH Key"
    echo "════════════════════════════════════════════════════════════════════════"
    echo ""

    # Create .ssh directory if it doesn't exist
    mkdir -p ~/.ssh
    chmod 700 ~/.ssh

    echo "🔑 Generating Ed25519 SSH key pair..."
    ssh-keygen -t ed25519 -C "github-deploy" -f ~/.ssh/github_deploy -N ""

    echo ""
    echo "✅ SSH key pair generated:"
    echo "   Private key: ~/.ssh/github_deploy"
    echo "   Public key:  ~/.ssh/github_deploy.pub"
    echo ""
fi

# ═══════════════════════════════════════════════════════════════════════════
# Step 3: Display Keys
# ═══════════════════════════════════════════════════════════════════════════

echo "════════════════════════════════════════════════════════════════════════"
echo "  SSH Key Information"
echo "════════════════════════════════════════════════════════════════════════"
echo ""

# Private key (for GitHub Secret)
echo "📄 PRIVATE KEY (for GitHub Secret DEPLOY_SSH_KEY):"
echo "────────────────────────────────────────────────────────────────────────"
cat ~/.ssh/github_deploy
echo ""
echo "────────────────────────────────────────────────────────────────────────"

# Public key (for server authorized_keys)
echo ""
echo "🔓 PUBLIC KEY (for server ~/.ssh/authorized_keys):"
echo "────────────────────────────────────────────────────────────────────────"
cat ~/.ssh/github_deploy.pub
echo ""
echo "────────────────────────────────────────────────────────────────────────"

# ═══════════════════════════════════════════════════════════════════════════
# Step 4: Instructions
# ═══════════════════════════════════════════════════════════════════════════

echo ""
echo "════════════════════════════════════════════════════════════════════════"
echo "  Next Steps"
echo "════════════════════════════════════════════════════════════════════════"
echo ""

echo "1️⃣  Add PRIVATE key to GitHub Secret DEPLOY_SSH_KEY:"
echo "   - Go to: GitHub Repo → Settings → Secrets and variables → Actions"
echo "   - Click: New repository secret"
echo "   - Name: DEPLOY_SSH_KEY"
echo "   - Value: (paste the PRIVATE key from above - from -----BEGIN to -----END)"
echo ""

echo "2️⃣  Add PUBLIC key to Production Server:"
echo "   - SSH to server: ssh your-user@your-server"
echo "   - Run these commands:"
echo "     mkdir -p ~/.ssh"
echo "     chmod 700 ~/.ssh"
echo "     cat >> ~/.ssh/authorized_keys << 'EOF'"
echo "     (paste the PUBLIC key from above)"
echo "     EOF"
echo "     chmod 600 ~/.ssh/authorized_keys"
echo ""

echo "3️⃣  Test SSH Connection (should not ask for password):"
echo "   ssh deploy@your-server-ip"
echo ""

echo "✅ SSH key setup complete!"
echo ""
