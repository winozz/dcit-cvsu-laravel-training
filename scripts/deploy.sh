#!/bin/bash
################################################################################
# One-Shot Deployment Script
#
# This script pushes all GitHub Actions configuration to GitHub
# Run this on your LOCAL machine
#
# Prerequisites:
#   - Git repository initialized
#   - GitHub remote configured
#   - All configuration files created
#
# Usage:
#   bash scripts/deploy.sh
################################################################################

set -e

echo "════════════════════════════════════════════════════════════════════════"
echo "  GitHub Actions - One-Shot Deployment"
echo "════════════════════════════════════════════════════════════════════════"
echo ""

# ═══════════════════════════════════════════════════════════════════════════
# Step 1: Verify Git Setup
# ═══════════════════════════════════════════════════════════════════════════

echo "STEP 1: Verifying Git Setup"
echo "────────────────────────────────────────────────────────────────────────"

if ! git rev-parse --git-dir > /dev/null 2>&1; then
    echo "❌ Not in a git repository"
    echo "   Run: git init"
    exit 1
fi

echo "✅ Git repository found"

# Check for GitHub remote
if ! git remote get-url origin > /dev/null 2>&1; then
    echo "❌ GitHub remote not configured"
    echo "   Run: git remote add origin https://github.com/your-org/your-repo.git"
    exit 1
fi

REMOTE=$(git remote get-url origin)
echo "✅ GitHub remote: $REMOTE"
echo ""

# ═══════════════════════════════════════════════════════════════════════════
# Step 2: Check for Tokens
# ═══════════════════════════════════════════════════════════════════════════

echo "STEP 2: Checking for Hardcoded Secrets"
echo "────────────────────────────────────────────────────────────────────────"

if grep -r "ghp_" .github/ *.md 2>/dev/null | grep -v "ghp_XXXX"; then
    echo "❌ Found hardcoded GitHub tokens in documentation!"
    echo "   Please remove or redact any tokens before pushing"
    exit 1
else
    echo "✅ No hardcoded tokens found"
fi

echo ""

# ═══════════════════════════════════════════════════════════════════════════
# Step 3: Stage Changes
# ═══════════════════════════════════════════════════════════════════════════

echo "STEP 3: Staging Changes"
echo "────────────────────────────────────────────────────────────────────────"

echo "Adding GitHub Actions workflow..."
git add .github/workflows/deploy.yml

echo "Adding documentation..."
git add 00_START_HERE.md
git add CI_CD_INDEX.md
git add DEPLOYMENT.md
git add GITHUB_ACTIONS_README.md
git add IMPLEMENTATION_SUMMARY.md
git add JENKINS_TO_GITHUB_ACTIONS_COMPARISON.md
git add SETUP_CHECKLIST.md
git add SETUP_GITHUB_ACTIONS.md

echo "Adding environment configuration..."
git add .env.production

echo "Adding setup scripts..."
git add scripts/

echo ""
echo "✅ All files staged"
echo ""

# Show what will be committed
echo "Files to be committed:"
git diff --cached --name-only | sed 's/^/  ✓ /'
echo ""

# ═══════════════════════════════════════════════════════════════════════════
# Step 4: Commit
# ═══════════════════════════════════════════════════════════════════════════

echo "STEP 4: Creating Commit"
echo "────────────────────────────────────────────────────────────────────────"

COMMIT_MSG="Add GitHub Actions CI/CD workflow and documentation

- Add .github/workflows/deploy.yml with 3-stage pipeline (test, build, deploy)
- Add comprehensive setup guides and checklists
- Add troubleshooting and reference documentation
- Add Jenkins-to-GitHub Actions comparison guide
- Add environment configuration templates
- Add automated setup scripts for local and server setup

Features:
- Automatic testing on all pushes
- Docker image building and pushing to GHCR
- SSH-based deployment to production
- Cloudflare Tunnel integration
- Health checks with retry logic
- Database migrations
- Comprehensive logging

This enables zero-touch deployments: developers just git push, GitHub Actions
handles testing, building, and deployment automatically."

git commit -m "$COMMIT_MSG"

echo "✅ Commit created"
echo ""

# ═══════════════════════════════════════════════════════════════════════════
# Step 5: Push to GitHub
# ═══════════════════════════════════════════════════════════════════════════

echo "STEP 5: Pushing to GitHub"
echo "────────────────────────────────────────────────────────────────────────"

CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)

echo "Current branch: $CURRENT_BRANCH"
echo ""

read -p "Push to $CURRENT_BRANCH? (y/n) " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Push cancelled"
    exit 1
fi

echo "🚀 Pushing to GitHub..."
git push origin "$CURRENT_BRANCH"

echo "✅ Pushed successfully"
echo ""

# ═══════════════════════════════════════════════════════════════════════════
# Step 6: Verify on GitHub
# ═══════════════════════════════════════════════════════════════════════════

echo "STEP 6: Verification"
echo "────────────────────────────────────────────────────────────────────────"

REPO=$(git remote get-url origin | sed 's/.*github.com[:/]\(.*\)\.git/\1/')

echo ""
echo "✅ Files pushed to GitHub!"
echo ""
echo "📋 Next steps:"
echo "  1. Add GitHub Secrets:"
echo "     https://github.com/$REPO/settings/secrets/actions"
echo "     - DEPLOY_HOST"
echo "     - DEPLOY_USER"
echo "     - DEPLOY_SSH_KEY"
echo "     - DEPLOY_PATH"
echo ""
echo "  2. Setup production server:"
echo "     bash scripts/setup-production-server.sh"
echo ""
echo "  3. Test deployment:"
echo "     git push origin $CURRENT_BRANCH"
echo "     Monitor at: https://github.com/$REPO/actions"
echo ""
echo "════════════════════════════════════════════════════════════════════════"
echo "  🎉 GitHub Actions Deployment Ready!"
echo "════════════════════════════════════════════════════════════════════════"
echo ""
