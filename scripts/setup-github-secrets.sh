#!/bin/bash
################################################################################
# GitHub Secrets Setup Script
#
# This script helps you add the 4 required GitHub Secrets
# Run this script to automate the secret creation
#
# Prerequisites:
#   - GitHub CLI installed: https://cli.github.com/
#   - Logged in to GitHub CLI: gh auth login
#   - Have the 4 values ready (see below)
################################################################################

set -e

echo "════════════════════════════════════════════════════════════════════════"
echo "  GitHub Actions CI/CD - Secrets Setup"
echo "════════════════════════════════════════════════════════════════════════"
echo ""

# Check if GitHub CLI is installed
if ! command -v gh &> /dev/null; then
    echo "❌ GitHub CLI is not installed"
    echo "   Install from: https://cli.github.com/"
    exit 1
fi

echo "✅ GitHub CLI found"
echo ""

# Get repository info
REPO=$(gh repo view --json nameWithOwner -q)
if [ -z "$REPO" ]; then
    echo "❌ Not in a GitHub repository or not logged in"
    echo "   Run: gh auth login"
    exit 1
fi

echo "📦 Repository: $REPO"
echo ""

# Collect values from user
echo "════════════════════════════════════════════════════════════════════════"
echo "  Enter the 4 GitHub Secrets"
echo "════════════════════════════════════════════════════════════════════════"
echo ""

# Secret 1: DEPLOY_HOST
echo "1️⃣  DEPLOY_HOST"
echo "   What: Production server IP or hostname"
echo "   Example: 192.168.1.100 or app.example.com"
read -p "   Value: " DEPLOY_HOST

if [ -z "$DEPLOY_HOST" ]; then
    echo "❌ DEPLOY_HOST cannot be empty"
    exit 1
fi

# Secret 2: DEPLOY_USER
echo ""
echo "2️⃣  DEPLOY_USER"
echo "   What: SSH username on your server"
echo "   Example: deploy or ubuntu"
read -p "   Value: " DEPLOY_USER

if [ -z "$DEPLOY_USER" ]; then
    echo "❌ DEPLOY_USER cannot be empty"
    exit 1
fi

# Secret 3: DEPLOY_SSH_KEY
echo ""
echo "3️⃣  DEPLOY_SSH_KEY"
echo "   What: SSH private key content"
echo "   File: ~/.ssh/github_deploy"
echo "   To display: cat ~/.ssh/github_deploy"
echo ""
echo "   Paste the ENTIRE private key (from -----BEGIN to -----END)"
echo "   When done, press Enter twice:"
echo ""

read -r line
DEPLOY_SSH_KEY="$line"
while IFS= read -r line; do
    if [ -z "$line" ]; then
        break
    fi
    DEPLOY_SSH_KEY="$DEPLOY_SSH_KEY"$'\n'"$line"
done

if [ -z "$DEPLOY_SSH_KEY" ]; then
    echo "❌ DEPLOY_SSH_KEY cannot be empty"
    exit 1
fi

# Secret 4: DEPLOY_PATH
echo ""
echo "4️⃣  DEPLOY_PATH"
echo "   What: Absolute path to app directory on server"
echo "   Example: /home/deploy/dcit-cvsu-laravel-training"
echo "   Must start with /"
read -p "   Value: " DEPLOY_PATH

if [ -z "$DEPLOY_PATH" ]; then
    echo "❌ DEPLOY_PATH cannot be empty"
    exit 1
fi

if [[ ! "$DEPLOY_PATH" =~ ^/ ]]; then
    echo "❌ DEPLOY_PATH must be absolute (start with /)"
    exit 1
fi

echo ""
echo "════════════════════════════════════════════════════════════════════════"
echo "  Creating GitHub Secrets"
echo "════════════════════════════════════════════════════════════════════════"
echo ""

# Create secrets using GitHub CLI
echo "Creating DEPLOY_HOST..."
gh secret set DEPLOY_HOST --body "$DEPLOY_HOST" --repo "$REPO"
echo "✅ DEPLOY_HOST created"

echo ""
echo "Creating DEPLOY_USER..."
gh secret set DEPLOY_USER --body "$DEPLOY_USER" --repo "$REPO"
echo "✅ DEPLOY_USER created"

echo ""
echo "Creating DEPLOY_SSH_KEY..."
gh secret set DEPLOY_SSH_KEY --body "$DEPLOY_SSH_KEY" --repo "$REPO"
echo "✅ DEPLOY_SSH_KEY created"

echo ""
echo "Creating DEPLOY_PATH..."
gh secret set DEPLOY_PATH --body "$DEPLOY_PATH" --repo "$REPO"
echo "✅ DEPLOY_PATH created"

echo ""
echo "════════════════════════════════════════════════════════════════════════"
echo "  ✅ All Secrets Created Successfully!"
echo "════════════════════════════════════════════════════════════════════════"
echo ""
echo "Verify in GitHub:"
echo "  https://github.com/$REPO/settings/secrets/actions"
echo ""
echo "Next steps:"
echo "  1. Prepare production server (follow SETUP_GITHUB_ACTIONS.md)"
echo "  2. Push test commit to trigger workflow"
echo "  3. Monitor at: https://github.com/$REPO/actions"
echo ""
