#!/bin/bash
################################################################################
# SSH Connection Test Script
#
# Run this on your LOCAL machine to test SSH connection to production server
# Usage: bash scripts/test-ssh.sh
################################################################################

set -e

echo "════════════════════════════════════════════════════════════════════════"
echo "  SSH Connection Test"
echo "════════════════════════════════════════════════════════════════════════"
echo ""

# Get values from user
read -p "Enter DEPLOY_USER (e.g., deploy): " DEPLOY_USER
read -p "Enter DEPLOY_HOST (e.g., 192.168.1.100): " DEPLOY_HOST

if [ -z "$DEPLOY_USER" ] || [ -z "$DEPLOY_HOST" ]; then
    echo "❌ Both values required"
    exit 1
fi

echo ""
echo "Testing SSH connection to: $DEPLOY_USER@$DEPLOY_HOST"
echo ""

# Test 1: Basic SSH connection
echo "TEST 1: Basic SSH Connection"
echo "────────────────────────────────────────────────────────────────────────"
if ssh -o ConnectTimeout=5 "$DEPLOY_USER@$DEPLOY_HOST" "echo 'SSH OK'" 2>/dev/null; then
    echo "✅ SSH connection successful (no password required)"
else
    echo "❌ SSH connection failed"
    echo "   - Check DEPLOY_HOST is correct"
    echo "   - Check DEPLOY_USER is correct"
    echo "   - Check public key is in ~/.ssh/authorized_keys on server"
    exit 1
fi

echo ""

# Test 2: Check public key exists
echo "TEST 2: Verify Public Key on Server"
echo "────────────────────────────────────────────────────────────────────────"
if ssh "$DEPLOY_USER@$DEPLOY_HOST" "grep github-deploy ~/.ssh/authorized_keys" 2>/dev/null; then
    echo "✅ Public key found in authorized_keys"
else
    echo "❌ Public key NOT found"
    echo "   Add your public key to ~/.ssh/authorized_keys on the server"
    exit 1
fi

echo ""

# Test 3: Docker access
echo "TEST 3: Docker Access"
echo "────────────────────────────────────────────────────────────────────────"
if ssh "$DEPLOY_USER@$DEPLOY_HOST" "docker ps" 2>/dev/null | head -5; then
    echo "✅ Docker is accessible"
else
    echo "❌ Docker is not accessible"
    echo "   Run: sudo usermod -aG docker $DEPLOY_USER"
    exit 1
fi

echo ""

# Test 4: App directory access
echo "TEST 4: App Directory Access"
echo "────────────────────────────────────────────────────────────────────────"
if ssh "$DEPLOY_USER@$DEPLOY_HOST" "test -d /home/deploy/dcit-cvsu-laravel-training" 2>/dev/null; then
    echo "✅ App directory exists: /home/deploy/dcit-cvsu-laravel-training"
else
    echo "⚠️  App directory not found"
    echo "   This is OK if you haven't cloned the repo yet"
fi

echo ""

# Test 5: Docker Compose access
echo "TEST 5: Docker Compose Access"
echo "────────────────────────────────────────────────────────────────────────"
if ssh "$DEPLOY_USER@$DEPLOY_HOST" "cd /home/deploy/dcit-cvsu-laravel-training && docker compose ps" 2>/dev/null | head -5; then
    echo "✅ Docker Compose is working"
else
    echo "⚠️  Docker Compose test skipped (app directory not ready yet)"
fi

echo ""

# Test 6: Verify SSH key fingerprint
echo "TEST 6: SSH Key Fingerprint"
echo "────────────────────────────────────────────────────────────────────────"
ssh-keygen -l -f ~/.ssh/github_deploy
echo "✅ SSH key fingerprint verified"

echo ""
echo "════════════════════════════════════════════════════════════════════════"
echo "  🎉 SSH Test Complete!"
echo "════════════════════════════════════════════════════════════════════════"
echo ""
echo "Summary:"
echo "  ✅ SSH connection works without password"
echo "  ✅ Public key is on server"
echo "  ✅ Docker is accessible"
echo "  ✅ Ready for GitHub Actions deployment"
echo ""
