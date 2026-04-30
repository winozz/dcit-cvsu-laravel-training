# GitHub Actions Setup Checklist

Use this checklist to track your progress through the entire setup process.

**Estimated Time:** 30-60 minutes (depending on server preparation)

---

## Phase 1: GitHub Repository Setup (5 min)

- [ ] Code is committed and pushed to GitHub
- [ ] GitHub repository is public or accessible
- [ ] Main branch exists: `git branch | grep main`
- [ ] `.github/workflows/deploy.yml` file exists in repo
- [ ] Can see Actions tab in GitHub repo: `https://github.com/your-org/dcit-cvsu-laravel-training/actions`

**Verification:**
```bash
git log --oneline | head -5
git remote -v  # Should show origin pointing to GitHub
```

---

## Phase 2: GitHub Secrets Configuration (10 min)

**Location:** GitHub repo → Settings → Secrets and variables → Actions

### 2.1: Generate SSH Key Pair (Local Machine)

```bash
ssh-keygen -t ed25519 -C "github-deploy" -f ~/.ssh/github_deploy
# Press Enter when prompted for passphrase (leave empty)
```

- [ ] SSH private key generated at `~/.ssh/github_deploy`
- [ ] SSH public key generated at `~/.ssh/github_deploy.pub`
- [ ] Can read private key: `cat ~/.ssh/github_deploy | head -1` shows `-----BEGIN OPENSSH PRIVATE KEY-----`

### 2.2: Add GitHub Secrets

**Secret 1: DEPLOY_HOST**
- [ ] GitHub secret name: `DEPLOY_HOST`
- [ ] Value: Your production server IP or hostname (e.g., `192.168.1.100` or `app.example.com`)
- [ ] Verified by visiting Settings → Secrets → `DEPLOY_HOST` exists

**Secret 2: DEPLOY_USER**
- [ ] GitHub secret name: `DEPLOY_USER`
- [ ] Value: SSH username (e.g., `deploy` or `ubuntu`)
- [ ] Verified by visiting Settings → Secrets → `DEPLOY_USER` exists

**Secret 3: DEPLOY_SSH_KEY**
- [ ] GitHub secret name: `DEPLOY_SSH_KEY`
- [ ] Value: Full contents of `~/.ssh/github_deploy` (private key)
  - Starts with: `-----BEGIN OPENSSH PRIVATE KEY-----`
  - Ends with: `-----END OPENSSH PRIVATE KEY-----`
- [ ] Verified by visiting Settings → Secrets → `DEPLOY_SSH_KEY` exists

**Secret 4: DEPLOY_PATH**
- [ ] GitHub secret name: `DEPLOY_PATH`
- [ ] Value: Absolute path on server (e.g., `/home/deploy/dcit-cvsu-laravel-training`)
- [ ] Path starts with `/` (absolute, not relative)
- [ ] Verified by visiting Settings → Secrets → `DEPLOY_PATH` exists

**All 4 Secrets Verification:**
```bash
# On GitHub, go to Settings → Secrets and variables → Actions
# You should see 4 secrets listed:
# ✅ DEPLOY_HOST
# ✅ DEPLOY_USER
# ✅ DEPLOY_SSH_KEY
# ✅ DEPLOY_PATH
```

---

## Phase 3: Production Server Preparation (20-40 min)

### 3.1: Server Prerequisites

**Check for required tools:**
```bash
docker --version          # Should be v20+
docker compose version    # Should be v2+
git --version            # Should be v2+
curl --version           # Should exist
which cloudflared        # Should exist (or will install)
```

- [ ] Docker is installed and running
- [ ] Docker Compose v2+ is installed
- [ ] Git is installed
- [ ] curl is installed

**Install missing tools (Ubuntu/Debian):**
```bash
sudo apt update
sudo apt install -y docker.io docker-compose git curl
sudo systemctl start docker
```

- [ ] All tools installed
- [ ] Docker daemon is running: `sudo systemctl status docker`

### 3.2: Create Deployment User (if needed)

```bash
# Check if deploy user exists
id deploy
# If not, create it
sudo useradd -m -s /bin/bash deploy
sudo passwd deploy
```

- [ ] Deploy user exists: `id deploy`
- [ ] User has home directory: `ls -d /home/deploy`
- [ ] User can run docker (optional): `sudo usermod -aG docker deploy`

### 3.3: Set Up SSH Key Authentication

**On production server:**

```bash
# As deploy user (or as root setting up for deploy user)
sudo su - deploy

mkdir -p ~/.ssh
chmod 700 ~/.ssh

# Now paste the PUBLIC key from your local machine
# On local machine, run: cat ~/.ssh/github_deploy.pub
# Then add to server:
```

- [ ] `.ssh` directory exists: `ls -d ~/.ssh`
- [ ] `.ssh` directory permissions are 700: `ls -ld ~/.ssh`
- [ ] Public key added to authorized_keys: `cat ~/.ssh/authorized_keys | grep github-deploy`
- [ ] authorized_keys has correct permissions: `ls -l ~/.ssh/authorized_keys` shows `rw-------`

**Test SSH from local machine:**
```bash
ssh deploy@your-server-ip "echo 'SSH OK'"
# Should print: SSH OK
```

- [ ] SSH connection works without password prompt
- [ ] Can run commands via SSH: `ssh deploy@your-server-ip "docker ps"`

### 3.4: Clone Repository on Server

```bash
# As deploy user on production server
cd /home/deploy

git clone https://github.com/your-org/dcit-cvsu-laravel-training.git
cd dcit-cvsu-laravel-training

# Verify structure
ls -la | head -20
# Should see: compose.yml, docker/, .github/, routes/, etc.
```

- [ ] Repository cloned: `ls -d ~/dcit-cvsu-laravel-training`
- [ ] Branch is main: `git branch`
- [ ] Can pull updates: `git pull origin main`

### 3.5: Initialize Laravel Environment

```bash
# In repo directory
cp .env.example .env

# Generate app key
php artisan key:generate

# Create SQLite database
touch database/database.sqlite

# Create directories
mkdir -p bootstrap/cache storage/framework/{cache,sessions,views} storage/logs

# Verify
ls -la database/
# Should see: database.sqlite
```

- [ ] `.env` file exists: `ls -l .env`
- [ ] Application key is set: `grep APP_KEY .env | grep -v "^#"`
- [ ] SQLite database file exists: `ls -l database/database.sqlite`
- [ ] Required directories exist: `ls -d bootstrap/cache storage/framework storage/logs`

### 3.6: Set Up Directory Permissions

```bash
# Ensure deploy user owns the directory
sudo chown -R deploy:deploy /home/deploy/dcit-cvsu-laravel-training
chmod 755 /home/deploy/dcit-cvsu-laravel-training

# Verify
ls -ld /home/deploy/dcit-cvsu-laravel-training
# Should show: deploy deploy
```

- [ ] Directory owner is deploy user
- [ ] Directory is writable by deploy user
- [ ] SSH user can modify files: `ssh deploy@server "touch ~/dcit-cvsu-laravel-training/test.txt && rm ~/dcit-cvsu-laravel-training/test.txt"`

### 3.7: Install and Configure Cloudflare Tunnel

#### Option A: Named Tunnel (Recommended)

```bash
# Install cloudflared
curl -L https://pkg.cloudflare.com/cloudflare-release-key.gpg | sudo tee /etc/apt/trusted.gpg.d/cloudflare.gpg > /dev/null
echo 'deb [signed-by=/etc/apt/trusted.gpg.d/cloudflare.gpg] https://pkg.cloudflare.com/linux focal main' | sudo tee /etc/apt/sources.list.d/cloudflare.list
sudo apt update && sudo apt install -y cloudflared

# Authenticate (opens browser)
cloudflared tunnel login

# Create tunnel
cloudflared tunnel create php-app
# Note the tunnel ID from the output
```

- [ ] cloudflared is installed: `which cloudflared`
- [ ] Authenticated with Cloudflare: `ls ~/.cloudflared/cert.pem`
- [ ] Tunnel created: `cloudflared tunnel list` shows `php-app`
- [ ] Tunnel credentials exist: `ls ~/.cloudflared/*.json`

```bash
# Create config file
sudo tee /etc/cloudflared/config.yml > /dev/null << 'EOF'
tunnel: <your-tunnel-id>
credentials-file: /home/deploy/.cloudflared/<tunnel-id>.json

ingress:
  - service: http://localhost:8087
EOF

# Enable and start
sudo systemctl enable cloudflared
sudo systemctl start cloudflared

# Verify
sudo systemctl status cloudflared
# Should show: active (running)
```

- [ ] Config file exists: `cat /etc/cloudflared/config.yml | grep tunnel`
- [ ] cloudflared is enabled: `sudo systemctl is-enabled cloudflared` shows `enabled`
- [ ] cloudflared is running: `sudo systemctl status cloudflared` shows `active (running)`

#### Option B: Quick Tunnel (Alternative)

Skip the above. Cloudflared doesn't need to be pre-configured; the deploy script will start it.

- [ ] cloudflared is installed: `which cloudflared`

### 3.8: Set Up Port Mapping

Choose one option:

#### Option A: compose.override.yml

```bash
# Create (but don't commit to git)
cat > /home/deploy/dcit-cvsu-laravel-training/compose.override.yml << 'EOF'
services:
  app:
    ports:
      - "8087:8080"
EOF

# Verify it's in .gitignore
grep compose.override /home/deploy/dcit-cvsu-laravel-training/.gitignore
```

- [ ] `compose.override.yml` exists in deploy directory
- [ ] File is NOT in git: `git status | grep compose.override` shows nothing
- [ ] File is in .gitignore: `cat .gitignore | grep override`

#### Option B: Update Tunnel Config

Update `/etc/cloudflared/config.yml` to point to `http://localhost:8010` instead of `8087`.

- [ ] Tunnel config updated: `grep "service:" /etc/cloudflared/config.yml`

### 3.9: Test Manual Deployment

```bash
# SSH to server
ssh deploy@your-server-ip
cd /home/deploy/dcit-cvsu-laravel-training

# Pull latest code
git pull origin main

# Start containers
docker compose up -d

# Wait 15 seconds for startup
sleep 15

# Check status
docker compose ps
# Should show: app and queue in "Up" status

# Check health
curl http://localhost:8080/up
# Should return: {"status":"up"}

# Check tunnel port
curl http://localhost:8087/up
# Should return: {"status":"up"}

# Stop (so GitHub Actions can start fresh)
docker compose down
```

- [ ] Containers started successfully: `docker compose ps` shows "Up"
- [ ] Health check passes: `curl http://localhost:8080/up` returns 200
- [ ] Port 8087 accessible: `curl http://localhost:8087/up` returns 200
- [ ] Containers stopped: `docker compose ps` shows nothing running

---

## Phase 4: Test the GitHub Actions Workflow (10 min)

### 4.1: Trigger a Test Deployment

```bash
# On your local machine
cd dcit-cvsu-laravel-training

# Make a test commit
echo "# Test deployment" >> README.md
git add README.md
git commit -m "Test GitHub Actions workflow"
git push origin main
```

- [ ] Commit pushed to GitHub: `git log --oneline | head -1` shows your commit
- [ ] Commit is on main branch: `git branch -a | grep "main"` shows remote tracking branch updated

### 4.2: Monitor Workflow in GitHub

```bash
# Go to: https://github.com/your-org/dcit-cvsu-laravel-training/actions
# Or run:
gh run list --branch main --limit 1
```

- [ ] Workflow run appears in GitHub Actions tab
- [ ] Test job is running/completed
- [ ] Build job is running/completed (after test passes)
- [ ] Deploy job is running/completed (after build passes)
- [ ] All jobs show green checkmarks ✅

### 4.3: Verify on Server

```bash
# SSH to server
ssh deploy@your-server-ip
cd /home/deploy/dcit-cvsu-laravel-training

# Check containers
docker compose ps
# Should show: app and queue UP and HEALTHY

# Check code was updated
git log --oneline | head -1
# Should show your test commit

# Check migrations ran
docker compose exec app php artisan migrate:status

# Check health
curl http://localhost:8087/up
# Should return: {"status":"up"}

# Check tunnel
systemctl status cloudflared
# Should show: active (running)
```

- [ ] Containers are running: `docker compose ps` shows "Up"
- [ ] Containers are healthy: `docker compose ps` shows "healthy"
- [ ] Code was updated: Latest commit is visible in git log
- [ ] Migrations completed: `migrate:status` shows all migrations with "Yes"
- [ ] Health check passes: `curl http://localhost:8087/up` returns 200
- [ ] Tunnel is active: `systemctl status cloudflared` shows running

### 4.4: Check Workflow Logs

If any step failed:

1. Go to GitHub Actions tab
2. Click the failed run
3. Click the failed job name
4. Expand the failed step and read the error
5. Common issues:
   - SSH connection failed → Check DEPLOY_HOST, DEPLOY_USER, DEPLOY_SSH_KEY
   - Docker pull failed → Check GHCR access
   - Migration failed → Check database permissions
   - Tunnel failed → Check cloudflared config

- [ ] Workflow completed successfully (all 3 jobs green)
- [ ] No errors in logs
- [ ] If errors exist, refer to DEPLOYMENT.md Troubleshooting section

---

## Phase 5: Cleanup and Verification (5 min)

### 5.1: Clean Up Test Commit

```bash
# If you made a test commit, you can revert it
git reset --hard HEAD~1
git push -f origin main
```

- [ ] Test commit removed (optional)

### 5.2: Final Verification Checklist

```bash
# Local machine
git log --oneline | head -3
git status
# Should be clean

# GitHub
# https://github.com/your-org/dcit-cvsu-laravel-training/actions
# Last run should be green

# Production server
ssh deploy@your-server-ip
docker compose ps
curl http://localhost:8087/up
systemctl status cloudflared
```

- [ ] Local git is clean
- [ ] GitHub Actions shows successful deployment
- [ ] Containers running on server
- [ ] Health check passes
- [ ] Tunnel is active

---

## Phase 6: Ongoing Operations

### 6.1: Making Changes

```bash
# Developers just push to main
git commit -am "Your change"
git push origin main

# GitHub Actions automatically:
# 1. Runs tests
# 2. Builds Docker image
# 3. Deploys to production
```

- [ ] Team understands: just `git push origin main`
- [ ] Team can monitor: https://github.com/your-org/dcit-cvsu-laravel-training/actions

### 6.2: Monitoring Deployments

```bash
# Watch GitHub Actions
# Watch server logs
ssh deploy@your-server-ip
docker compose logs -f app

# Check uptime
curl https://php-app-xyz.trycloudflare.com/up
```

- [ ] Have a way to monitor deployments (GitHub Actions, email alerts, etc.)
- [ ] Team knows how to check logs on server
- [ ] Team knows how to access via Cloudflare Tunnel URL

### 6.3: Troubleshooting Knowledge

- [ ] Team has read `DEPLOYMENT.md` Troubleshooting section
- [ ] Team knows to check GitHub Actions logs first
- [ ] Team knows to SSH to server and check `docker compose logs`
- [ ] Team knows to check `systemctl status cloudflared`

---

## ✅ Final Sign-Off

When all checkboxes are complete:

- [ ] **Code Review**: CI/CD workflow file reviewed
- [ ] **Testing**: First deployment tested successfully
- [ ] **Documentation**: Team has read `GITHUB_ACTIONS_README.md`
- [ ] **Secrets**: All 4 GitHub Secrets configured
- [ ] **Server**: Production server fully prepared
- [ ] **Monitoring**: Team knows how to monitor deployments
- [ ] **Troubleshooting**: Team knows first steps for debugging

### Deployment Sign-Off

By checking this box, you confirm:

- [ ] GitHub Actions CI/CD is fully operational
- [ ] Deployments are automated on push to main
- [ ] Production server is correctly configured
- [ ] Team has necessary documentation
- [ ] Team knows how to troubleshoot issues
- [ ] **Deployment is READY FOR PRODUCTION**

**Date Completed:** _______________

**Signed Off By:** _______________

---

## Quick Reference During Setup

### Useful Commands

```bash
# Check GitHub Secrets (from local machine)
gh secret list --repo your-org/dcit-cvsu-laravel-training

# Check workflow status
gh run list --repo your-org/dcit-cvsu-laravel-training --branch main

# View latest workflow run logs
gh run view [RUN_ID] --repo your-org/dcit-cvsu-laravel-training --log

# SSH to server and check
ssh deploy@your-server-ip
docker compose ps
docker compose logs app
systemctl status cloudflared
curl http://localhost:8087/up

# Manual deployment (if needed)
ssh deploy@your-server-ip
cd /home/deploy/dcit-cvsu-laravel-training
git pull origin main
docker compose up -d --force-recreate
docker compose exec app php artisan migrate --force
```

### Support Documents

| Document | Use Case |
|---|---|
| `GITHUB_ACTIONS_README.md` | Quick overview and quick start |
| `SETUP_GITHUB_ACTIONS.md` | Detailed step-by-step setup (use during setup) |
| `DEPLOYMENT.md` | Reference guide and troubleshooting |
| `JENKINS_TO_GITHUB_ACTIONS_COMPARISON.md` | Understanding differences from Jenkins |
| `.github/workflows/deploy.yml` | The actual workflow code |

---

**When stuck:** Check `DEPLOYMENT.md` Troubleshooting section or review the relevant document above.
