# GitHub Actions CI/CD Setup — Quick Start

This project now uses **GitHub Actions** for automated testing, building, and deployment (replacing the Jenkins `.bat` script).

## 📋 What Was Created

| File | Purpose |
|---|---|
| **`.github/workflows/deploy.yml`** | The actual CI/CD workflow (3 jobs: test, build, deploy) |
| **`SETUP_GITHUB_ACTIONS.md`** | ⭐ **START HERE** — Complete setup guide with all steps |
| **`DEPLOYMENT.md`** | Deployment overview, secrets reference, troubleshooting |
| **`JENKINS_TO_GITHUB_ACTIONS_COMPARISON.md`** | Detailed comparison to the Jenkins script |
| **This file** | Quick reference |

---

## 🚀 Quick Start (5 Minutes)

### For Developers (Push Code)

```bash
# 1. Make changes and commit
git add .
git commit -m "Your feature"

# 2. Push to main
git push origin main

# 3. Watch the workflow run
# Go to: https://github.com/your-org/dcit-cvsu-laravel-training/actions
```

**That's it!** The workflow will:
- ✅ Run tests
- ✅ Build Docker image
- ✅ Deploy to production (if tests pass)

### For Ops/DevOps (First-Time Setup)

**Read in this order:**

1. **`SETUP_GITHUB_ACTIONS.md`** (10-15 min read)
   - Phase 1: Push code to GitHub
   - Phase 2: Configure GitHub Secrets (DEPLOY_HOST, DEPLOY_USER, DEPLOY_SSH_KEY, DEPLOY_PATH)
   - Phase 3: Prepare production server (Docker, Git, Cloudflare Tunnel)
   - Phase 4: Test the deployment
   - Phase 5: Monitor ongoing operations

2. **`DEPLOYMENT.md`** (reference)
   - Workflow overview
   - Required secrets and server setup
   - Troubleshooting guide

3. **`JENKINS_TO_GITHUB_ACTIONS_COMPARISON.md`** (if you know Jenkins)
   - Maps Jenkins steps to GitHub Actions jobs
   - Explains what changed and why

---

## 📝 The 4 Required GitHub Secrets

Before deploying, add these to GitHub repo settings:

| Secret | Example Value |
|---|---|
| `DEPLOY_HOST` | `192.168.1.100` or `app.example.com` |
| `DEPLOY_USER` | `deploy` or `ubuntu` |
| `DEPLOY_SSH_KEY` | Contents of `~/.ssh/github_deploy` (private key) |
| `DEPLOY_PATH` | `/home/deploy/dcit-cvsu-laravel-training` |

**How to add:**
1. Go to **GitHub repo** → **Settings** → **Secrets and variables** → **Actions**
2. Click **New repository secret**
3. Add each secret above

---

## 🏗️ How It Works

### Workflow Overview

```
Developer: git push origin main
    ↓
GitHub Actions triggers:
    ↓
[TEST JOB] (2-3 min)
  ├─ Setup PHP 8.4
  ├─ Install composer dependencies
  ├─ Create SQLite test database
  ├─ Run migrations
  └─ Run tests
    ↓ (if tests pass)
[BUILD JOB] (5-10 min)
  ├─ Build Docker image from Dockerfile
  ├─ Push to GitHub Container Registry (GHCR)
  └─ Tag as latest
    ↓ (if build passes)
[DEPLOY JOB] (3-5 min)
  ├─ SSH to production server
  ├─ Pull latest Docker image
  ├─ Update source code (git pull)
  ├─ Build frontend assets (npm run build)
  ├─ Restart containers
  ├─ Run migrations
  ├─ Clear cache
  ├─ Verify Cloudflare Tunnel
  └─ Display status
    ↓
DONE ✅ (or FAILED ❌)

View results: GitHub Actions tab
```

### Key Differences from Jenkins

| Aspect | Jenkins | GitHub Actions |
|---|---|---|
| **Trigger** | Manual button click in Jenkins UI | Automatic on `git push` |
| **Parameters** | Enter SITE_NAME, BENCH_PORT, etc. | Fixed (single Laravel app) |
| **Testing** | Not included | Runs before deployment |
| **Build** | Pulls pre-built image | Builds from Dockerfile |
| **Deployment** | Runs in container (docker exec) | SSH to server (real deployment) |

---

## 🔍 Where to Find Things

### Jenkins Script (for reference)
```
C:\Users\user\Documents\Test Env\frappe_docker\development\frappe-bench\apps\accounting\accounting\accounting_information_system\doctype\project_accounting_entry\jenkins-deploy.bat
```

### GitHub Actions Workflow
```
c:\Users\user\Documents\laravel-training\dcit-cvsu-laravel-training\.github\workflows\deploy.yml
```

### GitHub Actions Logs
```
https://github.com/your-org/dcit-cvsu-laravel-training/actions
```

---

## ✅ Checklist Before First Deployment

- [ ] Code pushed to GitHub (main branch)
- [ ] 4 GitHub Secrets added (DEPLOY_HOST, DEPLOY_USER, DEPLOY_SSH_KEY, DEPLOY_PATH)
- [ ] Production server has Docker and Docker Compose installed
- [ ] SSH key pair generated and public key added to server's `~/.ssh/authorized_keys`
- [ ] Repository cloned on production server
- [ ] `.env` file created from `.env.example`
- [ ] SQLite database file created (`touch database/database.sqlite`)
- [ ] Cloudflare Tunnel installed and configured
- [ ] Port 8087 mapped (via `compose.override.yml` or tunnel config)
- [ ] SSH connectivity tested (`ssh deploy@your-server`)

---

## 🐛 Something Broken?

### Test Job Fails
```
→ Check: Do you have all dependencies in composer.json?
→ Run locally: composer install && php artisan test
```

### Build Job Fails
```
→ Check: Is Dockerfile correct? docker build -f docker/app/Dockerfile .
→ Check: Can Docker push to GHCR? docker login ghcr.io
```

### Deploy Job Fails
```
→ SSH issue? ssh -v deploy@your-server-ip
→ Docker issue? docker compose ps
→ Migration issue? docker compose logs app | tail -20
→ Tunnel issue? systemctl status cloudflared
```

**For detailed troubleshooting:** See `DEPLOYMENT.md` → Troubleshooting section

---

## 📚 Full Documentation

For complete step-by-step setup:

→ **[SETUP_GITHUB_ACTIONS.md](SETUP_GITHUB_ACTIONS.md)** — Read this first!

For reference and troubleshooting:

→ **[DEPLOYMENT.md](DEPLOYMENT.md)** — Reference guide

For understanding the changes:

→ **[JENKINS_TO_GITHUB_ACTIONS_COMPARISON.md](JENKINS_TO_GITHUB_ACTIONS_COMPARISON.md)** — Maps Jenkins to GitHub Actions

---

## 🆘 Need Help?

1. **Setup question?** → Read `SETUP_GITHUB_ACTIONS.md` Phase 1-3
2. **Deploy failed?** → Check `DEPLOYMENT.md` Troubleshooting section
3. **Want to understand it?** → See `JENKINS_TO_GITHUB_ACTIONS_COMPARISON.md`
4. **Want to know the workflow steps?** → See the YAML in `.github/workflows/deploy.yml`

---

## 🎯 What Happens When You Push

```
$ git push origin main

✅ Workflow triggered automatically
✅ Tests run (all must pass)
✅ Docker image builds and pushes to GHCR
✅ Deployment runs on production server
✅ Migrations execute
✅ Cloudflare Tunnel verified
✅ Status shown in GitHub Actions tab

View live: https://github.com/your-org/dcit-cvsu-laravel-training/actions
```

---

## 📞 Quick Reference

### Monitor Deployment
```bash
# Watch GitHub Actions logs
https://github.com/your-org/dcit-cvsu-laravel-training/actions

# SSH to server and check
ssh deploy@your-server-ip
docker compose ps
docker compose logs -f app
systemctl status cloudflared
```

### Manual Deployment (if needed)
```bash
ssh deploy@your-server-ip
cd /home/deploy/dcit-cvsu-laravel-training
git pull origin main
docker compose pull && docker compose up -d
docker compose exec app php artisan migrate --force
```

### Rollback
```bash
ssh deploy@your-server-ip
cd /home/deploy/dcit-cvsu-laravel-training
git reset --hard HEAD~1
docker compose up -d
```

---

**Next Step:** Follow the setup guide in [SETUP_GITHUB_ACTIONS.md](SETUP_GITHUB_ACTIONS.md)
