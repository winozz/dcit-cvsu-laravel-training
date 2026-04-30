# Complete Setup Guide for GitHub Actions CI/CD

This guide walks through all the steps needed to deploy the Laravel app using GitHub Actions, comparable to the Jenkins deploy system used for AIS.

**Jenkins Config Reference:** `C:\Users\user\Documents\Test Env\frappe_docker\development\frappe-bench\apps\accounting\accounting\accounting_information_system\doctype\project_accounting_entry\jenkins-deploy.bat`

---

## Phase 1: Prepare Your GitHub Repository

### Step 1.1: Push Code to GitHub

Ensure your repository is set up on GitHub and the code is pushed:

```bash
# If not already a git repo
git init
git remote add origin https://github.com/your-org/dcit-cvsu-laravel-training.git

# If using existing repo, verify main branch
git branch
# You should see: feat/add-component (current) and main

# Push to GitHub
git push -u origin main
git push -u origin feat/add-component
```

**Verify:** Go to `https://github.com/your-org/dcit-cvsu-laravel-training` and confirm:
- Repository exists and is accessible
- Main branch is present
- `.github/workflows/deploy.yml` is visible in the Actions tab

---

## Phase 2: Configure GitHub Secrets

### Step 2.1: Generate SSH Key Pair for Deployment

On your **local machine** (not the server):

```bash
# Generate a new SSH key (if you don't already have one)
ssh-keygen -t ed25519 -C "github-deploy" -f ~/.ssh/github_deploy

# When prompted for passphrase, press Enter (leave empty for CI/CD)
# Output should be:
#   Generating public/private ed25519 key pair...
#   Your public key has been saved in ~/.ssh/github_deploy.pub
#   Your private key has been saved in ~/.ssh/github_deploy
```

### Step 2.2: Add Secrets to GitHub

1. Go to your GitHub repository: `https://github.com/your-org/dcit-cvsu-laravel-training`
2. Click **Settings** (repo settings, not account)
3. In the left sidebar, click **Secrets and variables** → **Actions**
4. Click **New repository secret** for each:

#### Secret 1: `DEPLOY_HOST`
- **Name:** `DEPLOY_HOST`
- **Value:** Your production server IP or hostname
  - Example: `192.168.1.100` or `app.example.com` or `your-server.com`
- Click **Add secret**

#### Secret 2: `DEPLOY_USER`
- **Name:** `DEPLOY_USER`
- **Value:** The SSH username on your production server
  - Example: `deploy` or `ubuntu` or `root`
- Click **Add secret**

#### Secret 3: `DEPLOY_SSH_KEY`
- **Name:** `DEPLOY_SSH_KEY`
- **Value:** Contents of your **private key** file (NOT public key)
  - Run: `cat ~/.ssh/github_deploy` (on your local machine)
  - Copy the entire output (starts with `-----BEGIN OPENSSH PRIVATE KEY-----` and ends with `-----END OPENSSH PRIVATE KEY-----`)
  - Paste into the GitHub secret
- Click **Add secret**

#### Secret 4: `DEPLOY_PATH`
- **Name:** `DEPLOY_PATH`
- **Value:** Absolute path to the app directory on your production server
  - Example: `/home/deploy/dcit-cvsu-laravel-training` or `/opt/php-app` or `/var/www/app`
  - **Must be an absolute path** (starts with `/`)
- Click **Add secret**

**Verify Secrets:** In the **Secrets and variables > Actions** section, you should see:
- ✅ DEPLOY_HOST
- ✅ DEPLOY_USER
- ✅ DEPLOY_SSH_KEY
- ✅ DEPLOY_PATH

---

## Phase 3: Prepare Production Server

### Step 3.1: Server Prerequisites

Your production server must have:

```bash
# Check if installed
docker --version          # Docker Engine (v20+)
docker compose version    # Docker Compose (v2+)
git --version            # Git
curl --version           # curl
which cloudflared        # Cloudflare Tunnel
which php                # PHP 8.4+ (optional, only if not in container)
```

**Install missing tools:**

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install -y docker.io docker-compose git curl

# Verify Docker is running
sudo systemctl status docker

# (Optional) Add your user to docker group to avoid sudo
sudo usermod -aG docker $USER
newgrp docker
```

### Step 3.2: Create Deployment User (if needed)

If using a dedicated deploy user:

```bash
# On production server
sudo useradd -m -s /bin/bash deploy
sudo passwd deploy  # Set a password

# Or add the user to sudoers for docker commands:
echo "deploy ALL=(ALL) NOPASSWD: /usr/bin/docker" | sudo tee /etc/sudoers.d/docker-deploy
```

### Step 3.3: Set Up SSH Key Authentication

On your **production server**, add the public key to the deploy user:

```bash
# As the deploy user (or root if setting up for deploy user)
mkdir -p /home/deploy/.ssh
chmod 700 /home/deploy/.ssh

# Paste the PUBLIC key (from ~/.ssh/github_deploy.pub on your local machine)
# Run this and paste the output of: cat ~/.ssh/github_deploy.pub
cat >> /home/deploy/.ssh/authorized_keys << 'EOF'
ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAI... (your-public-key-here) github-deploy
EOF

chmod 600 /home/deploy/.ssh/authorized_keys
chown -R deploy:deploy /home/deploy/.ssh
```

**Verify SSH access:**

```bash
# On your local machine
ssh deploy@your-server-ip
# Should connect without password prompt
```

### Step 3.4: Clone Repository on Server

```bash
# As the deploy user, on the production server
cd /home/deploy

# Clone the repository
git clone https://github.com/your-org/dcit-cvsu-laravel-training.git
cd dcit-cvsu-laravel-training

# Initialize Laravel environment
cp .env.example .env
php artisan key:generate

# Create SQLite database
touch database/database.sqlite

# Create required directories
mkdir -p bootstrap/cache storage/framework/{cache,sessions,views} storage/logs

# Verify structure
ls -la
# You should see: compose.yml, docker/, .github/, etc.
```

### Step 3.5: Install and Configure Cloudflare Tunnel

#### Option A: Named Tunnel (Recommended for Production)

```bash
# On production server
# 1. Install cloudflared
curl -L https://pkg.cloudflare.com/cloudflare-release-key.gpg | sudo tee /etc/apt/trusted.gpg.d/cloudflare.gpg > /dev/null
echo 'deb [signed-by=/etc/apt/trusted.gpg.d/cloudflare.gpg] https://pkg.cloudflare.com/linux focal main' | sudo tee /etc/apt/sources.list.d/cloudflare.list
sudo apt update && sudo apt install -y cloudflared

# 2. Authenticate with Cloudflare (opens browser)
cloudflared tunnel login
# This creates ~/.cloudflared/cert.pem and writes your account token

# 3. Create a tunnel
cloudflared tunnel create php-app
# Output:
#   Tunnel credentials written to /home/deploy/.cloudflared/<tunnel-id>.json
#   Tunnel php-app created with ID <tunnel-id>

# 4. Create config file at /etc/cloudflared/config.yml
sudo tee /etc/cloudflared/config.yml > /dev/null << 'EOF'
tunnel: <your-tunnel-id-from-step-3>
credentials-file: /home/deploy/.cloudflared/<tunnel-id>.json

ingress:
  - service: http://localhost:8087
EOF

# 5. Enable and start the service
sudo systemctl enable --now cloudflared
sudo systemctl status cloudflared
# Should show: active (running)

# 6. You'll get a public URL like: https://php-app-123abc.trycloudflare.com
# Note this URL for testing
```

#### Option B: Quick Tunnel (Simpler, No Persistent Config)

Skip the named tunnel setup above. The GitHub Actions deploy script will attempt to start cloudflared if not already running, and it will fall back gracefully.

### Step 3.6: Set Up Port 8087 Mapping

The app runs on port **8010** locally and **8080** inside the container. For the Cloudflare Tunnel on port **8087**:

**Option A: Use `compose.override.yml` (Recommended)**

Create `/home/deploy/dcit-cvsu-laravel-training/compose.override.yml`:

```yaml
services:
  app:
    ports:
      - "8087:8080"
```

**This file is NOT committed to git.** Verify it's in `.gitignore`:

```bash
cat .gitignore | grep compose.override
# Should show: compose.override.yml
```

**Option B: Change Tunnel Config**

If you don't want a separate port, update `/etc/cloudflared/config.yml`:

```yaml
ingress:
  - service: http://localhost:8010
```

### Step 3.7: Verify Directory Permissions

```bash
# On production server
sudo chown -R deploy:deploy /home/deploy/dcit-cvsu-laravel-training
chmod 755 /home/deploy/dcit-cvsu-laravel-training

# Verify git can pull
cd /home/deploy/dcit-cvsu-laravel-training
git pull origin main  # Should succeed
```

---

## Phase 4: Test the Deployment Pipeline

### Step 4.1: Trigger a Workflow Run

Push a test commit to main:

```bash
# On your local machine
cd dcit-cvsu-laravel-training
echo "# Test commit" >> README.md
git add README.md
git commit -m "Test GitHub Actions workflow"
git push origin main
```

### Step 4.2: Monitor the Workflow

1. Go to **GitHub** → **Actions** tab
2. You should see a new run for your commit
3. Watch the jobs execute:
   - **test** job (runs first, ~2-3 min)
   - **build** job (runs after test, ~5-10 min)
   - **deploy** job (runs after build, ~3-5 min)

### Step 4.3: Check Logs

If any job fails:

1. Click the failed job name
2. Expand each step to see the logs
3. Common failures:
   - **SSH connection failed** → Check DEPLOY_HOST and SSH key
   - **Docker pull failed** → Check GHCR permissions
   - **Migration failed** → Check database file permissions on server
   - **Tunnel verification failed** → Check cloudflared is running

### Step 4.4: Verify on Server

```bash
# SSH into production server
ssh deploy@your-server-ip
cd /home/deploy/dcit-cvsu-laravel-training

# Check Docker containers
docker compose ps
# Expected output:
#   NAME      IMAGE                 STATUS        PORTS
#   app       serversideup/php:8.4  Up (healthy)  0.0.0.0:8010->8080/tcp, 0.0.0.0:8087->8080/tcp
#   queue     serversideup/php:8.4  Up (healthy)  
#   mailpit   axllent/mailpit:...   Up            0.0.0.0:1025->1025/tcp, 0.0.0.0:8025->8025/tcp

# Check if app is responding
curl http://localhost:8080/up
# Should return: {"status":"up"}

# Check migrations
docker compose exec app php artisan migrate:status

# Check Cloudflare Tunnel status
systemctl status cloudflared
# Should show: active (running)

# Test tunnel health
curl http://localhost:8087/up
# Should return: {"status":"up"}

# Or via tunnel
curl https://php-app-123abc.trycloudflare.com/up
# Should return: {"status":"up"}
```

---

## Phase 5: Ongoing Operations

### Deploying New Code

**Developers:**
```bash
git push origin main
```

**The GitHub Actions workflow automatically:**
1. Runs tests on the pushed code
2. Builds a Docker image
3. Pushes to GHCR
4. SSHes to production and deploys

### Manual Deployment (if needed)

```bash
# SSH into server
ssh deploy@your-server-ip
cd /home/deploy/dcit-cvsu-laravel-training

# Pull latest code
git pull origin main

# Build frontend assets
docker run --rm \
  -v $(pwd):/var/www/html \
  -w /var/www/html \
  node:20-alpine \
  sh -c "npm ci && npm run build"

# Restart containers
docker compose up -d --no-deps --force-recreate app queue

# Run migrations
docker compose exec app php artisan migrate --force

# Clear cache
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

### Monitoring

```bash
# Check app logs
ssh deploy@your-server-ip
docker compose logs -f app

# Check queue worker
docker compose logs -f queue

# Check container health
docker compose ps

# Check Cloudflare Tunnel status
systemctl status cloudflared

# View tunnel URL and access info
cloudflared tunnel info php-app
```

### Rollback (if needed)

```bash
# SSH into server
ssh deploy@your-server-ip
cd /home/deploy/dcit-cvsu-laravel-training

# Get git log
git log --oneline | head -10

# Revert to previous commit
git reset --hard HEAD~1

# Restart containers
docker compose up -d --no-deps --force-recreate app queue

# Verify
docker compose ps
curl http://localhost:8087/up
```

---

## Troubleshooting Reference

### Comparison: Jenkins vs GitHub Actions

| Aspect | Jenkins (AIS) | GitHub Actions (PHP) |
|---|---|---|
| **Config File** | `jenkins-deploy.bat` | `.github/workflows/deploy.yml` |
| **Trigger** | Manual (developers click "Build with Parameters") | Automatic (on git push to main) |
| **GHCR Login** | Step 1/5 | Build job login step |
| **Image Pull** | Step 2/5 | Build job build-push step |
| **Container Check** | Step 3/5 | Deploy job docker compose ps |
| **Backup/Migrate** | Step 4/5 | Deploy job artisan migrate |
| **Clear Cache** | Step 4/5 | Deploy job artisan cache:clear etc. |
| **Start App** | Step 5/5 (bench serve) | Deploy job docker compose up |
| **Port Assignment** | BENCH_PORT parameterized (9001-9003) | Fixed at 8010, tunnel at 8087 |
| **Tunnel** | Not in Jenkins | NEW in GitHub Actions (systemctl cloudflared) |

### Common Issues and Fixes

#### Issue: SSH connection fails
```
ERROR: Cannot SSH to deploy@your-server-ip
```

**Fix:**
```bash
# 1. Check SSH key is in GitHub secret
# 2. Verify public key is on server
ssh deploy@your-server-ip "cat ~/.ssh/authorized_keys"

# 3. Test SSH from local machine
ssh -v deploy@your-server-ip "echo OK"
```

#### Issue: Docker image pull fails
```
ERROR: pull access denied for ghcr.io/org/repo
```

**Fix:**
```bash
# 1. Verify image was built and pushed in build job logs
# 2. Check GitHub Packages visibility (should be public or auth configured)
docker login ghcr.io -u username --password [GITHUB_TOKEN]
docker pull ghcr.io/your-org/dcit-cvsu-laravel-training:latest
```

#### Issue: Migration fails
```
ERROR: SQLSTATE[HY000]: General error
```

**Fix:**
```bash
# 1. Check SQLite file permissions
sudo chown deploy:deploy /home/deploy/dcit-cvsu-laravel-training/database/database.sqlite
chmod 666 /home/deploy/dcit-cvsu-laravel-training/database/database.sqlite

# 2. Check previous migrations
docker compose exec app php artisan migrate:status

# 3. View app logs
docker compose logs app | tail -50
```

#### Issue: Cloudflare Tunnel not working
```
Warning: Tunnel health check inconclusive
```

**Fix:**
```bash
# 1. Check cloudflared is running
systemctl status cloudflared

# 2. Check config file
cat /etc/cloudflared/config.yml

# 3. Restart tunnel
systemctl restart cloudflared

# 4. Check port 8087 is accessible
curl http://localhost:8087/up

# 5. Check tunnel credentials
ls -la ~/.cloudflared/
```

---

## Summary Checklist

### Before First Deployment

- [ ] Code pushed to GitHub main branch
- [ ] `.github/workflows/deploy.yml` created
- [ ] GitHub Secrets configured (DEPLOY_HOST, DEPLOY_USER, DEPLOY_SSH_KEY, DEPLOY_PATH)
- [ ] SSH key pair generated and public key added to server
- [ ] Production server has Docker, Git, curl, cloudflared
- [ ] Repository cloned on production server
- [ ] `.env` file created on server from `.env.example`
- [ ] SQLite database file created
- [ ] Required directories created (bootstrap/cache, storage/*, etc.)
- [ ] Cloudflare Tunnel configured and running
- [ ] Port 8087 accessible (via compose.override.yml or tunnel config)
- [ ] SSH connectivity tested from local machine

### After First Deployment

- [ ] Workflow run completed successfully (all 3 jobs green)
- [ ] Containers are running and healthy (`docker compose ps`)
- [ ] App responds to health check (`curl http://localhost:8087/up`)
- [ ] Migrations completed (`docker compose exec app php artisan migrate:status`)
- [ ] Cloudflare Tunnel shows online status
- [ ] Public tunnel URL is accessible

---

## Questions?

Refer to [DEPLOYMENT.md](DEPLOYMENT.md) for additional details or run:
```bash
# Check workflow status
curl -H "Authorization: token $GITHUB_TOKEN" \
  https://api.github.com/repos/your-org/dcit-cvsu-laravel-training/actions/runs
```
