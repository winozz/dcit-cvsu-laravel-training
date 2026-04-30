# GitHub Actions CI/CD Deployment Guide

This project uses GitHub Actions for automated testing, building, and deployment.

## Workflow Overview

The CI/CD pipeline has three stages:

### 1. **Test** (Runs on all pushes and PRs)
- Sets up PHP 8.4 with required extensions (bcmath, intl, pdo_sqlite)
- Installs Composer dependencies
- Creates SQLite in-memory test database
- Runs migrations and tests via `php artisan test`

### 2. **Build** (Runs on push to `main` only)
- Builds Docker image using `docker/app/Dockerfile`
- Pushes to GitHub Container Registry (GHCR) as `ghcr.io/[owner]/dcit-cvsu-laravel-training:latest`
- Caches layers for faster rebuilds

### 3. **Deploy** (Runs on push to `main` only)
- SSHes into production server
- Pulls latest Docker image from GHCR
- Updates source code via `git pull origin main`
- Builds frontend assets (`npm ci && npm run build`)
- Restarts application containers
- Runs database migrations
- Clears and caches configuration
- Verifies Cloudflare Tunnel on port 8087

## Required GitHub Secrets

Add these secrets to your GitHub repository settings (**Settings > Secrets and variables > Actions**):

| Secret Name | Description | Example |
|---|---|---|
| `DEPLOY_HOST` | Production server IP or hostname | `192.168.1.100` or `app.example.com` |
| `DEPLOY_USER` | SSH username for production server | `deploy` |
| `DEPLOY_SSH_KEY` | Private SSH key (PEM format) | See below for setup |
| `DEPLOY_PATH` | Absolute path to app directory on server | `/home/deploy/dcit-cvsu-laravel-training` |

### Setting Up `DEPLOY_SSH_KEY`

1. On your local machine, create a new SSH key pair (if you don't have one):
   ```bash
   ssh-keygen -t ed25519 -C "github-deploy" -f ~/.ssh/github_deploy
   ```

2. Copy the **private key** content:
   ```bash
   cat ~/.ssh/github_deploy
   ```

3. In GitHub, add this as a repository secret named `DEPLOY_SSH_KEY`

4. On the production server, add the **public key** to the deploy user's authorized keys:
   ```bash
   cat ~/.ssh/github_deploy.pub >> ~/.ssh/authorized_keys
   chmod 600 ~/.ssh/authorized_keys
   ```

## Pre-Deployment Server Setup

Your production server must have:

### Required Tools
- Docker and Docker Compose (v2+)
- `curl` (for health checks)
- `git` (for pulling source code)
- `node:20-alpine` Docker image (for asset building)
- `cloudflared` (for Cloudflare Tunnel)

### Cloudflare Tunnel Configuration

The deployment script expects `cloudflared` to be running as a systemd service.

#### Option A: Named Tunnel (Recommended)

1. Install cloudflared:
   ```bash
   curl -L https://pkg.cloudflare.com/cloudflare-release-key.gpg | sudo tee /etc/apt/trusted.gpg.d/cloudflare.gpg > /dev/null
   echo 'deb [signed-by=/etc/apt/trusted.gpg.d/cloudflare.gpg] https://pkg.cloudflare.com/linux focal main' | sudo tee /etc/apt/sources.list.d/cloudflare.list
   sudo apt update && sudo apt install -y cloudflared
   ```

2. Create a Cloudflare Tunnel:
   ```bash
   cloudflared tunnel create php-app
   ```

3. Create `/etc/cloudflared/config.yml`:
   ```yaml
   tunnel: <your-tunnel-id-from-step-2>
   credentials-file: /etc/cloudflared/<tunnel-id>.json
   
   ingress:
     - service: http://localhost:8087
   ```

4. Enable and start the service:
   ```bash
   sudo systemctl enable --now cloudflared
   sudo systemctl status cloudflared
   ```

#### Option B: Quick Tunnel (Alternative)

If you prefer not to set up a persistent named tunnel, the deployment script will still work if `cloudflared` is available but not configured. However, this requires a quick-tunnel authentication each time.

### Port Configuration

The app runs on **8010** locally and **8080** inside the container. For the Cloudflare Tunnel to work on port **8087**, you have two options:

#### Option A: `compose.override.yml` (Recommended)

Create `/home/deploy/dcit-cvsu-laravel-training/compose.override.yml` (NOT committed to git):

```yaml
services:
  app:
    ports:
      - "8087:8080"
```

#### Option B: Adjust Tunnel Configuration

Change the ingress in `/etc/cloudflared/config.yml` to:
```yaml
ingress:
  - service: http://localhost:8010
```

### Directory Permissions

Ensure the deploy user can write to the app directory:

```bash
sudo chown -R $DEPLOY_USER:$DEPLOY_USER /home/$DEPLOY_USER/dcit-cvsu-laravel-training
chmod 755 /home/$DEPLOY_USER/dcit-cvsu-laravel-training
```

### Initial Repository Clone

Clone the repository on the server:

```bash
cd /home/$DEPLOY_USER
git clone https://github.com/your-org/dcit-cvsu-laravel-training.git
cd dcit-cvsu-laravel-training

# Create the .env file from .env.example
cp .env.example .env
php artisan key:generate

# Create SQLite database file
touch database/database.sqlite

# Create initial volumes/directories
mkdir -p bootstrap/cache storage/framework/{cache,sessions,views} storage/logs
```

## Workflow Behavior

### On Pull Request
- **Test job** runs only (no build or deploy)
- Ensures code quality before merge

### On Push to Main
- **Test job** runs
- **Build job** runs (builds and pushes Docker image to GHCR)
- **Deploy job** runs (deploys to production if build succeeds)

## Monitoring Deployments

1. Check the workflow run: **Actions** tab in GitHub
2. View logs for each job
3. On the server, monitor the deployment:
   ```bash
   docker compose logs -f app
   docker compose ps
   systemctl status cloudflared
   ```

## Troubleshooting

### SSH Connection Failed
- Verify the SSH key is added to the deploy user's `authorized_keys`
- Check the server IP/hostname in the `DEPLOY_HOST` secret
- Ensure the deploy user has permission to access the application directory

### Docker Image Pull Failed
- Check that the image was successfully pushed to GHCR
- Verify Docker is logged in: `docker login ghcr.io`
- Check GitHub Packages permissions

### Migrations Failed
- Review `docker compose logs app` for database errors
- Ensure the SQLite database file exists and is writable
- Check that all previous migrations have been run

### Cloudflare Tunnel Not Working
- Verify `systemctl status cloudflared` shows active
- Check `/etc/cloudflared/config.yml` for correct tunnel ID
- Test locally: `curl http://localhost:8087/up`
- Review Cloudflare dashboard for tunnel status

## Security Considerations

- SSH keys are stored only as GitHub Secrets and never logged
- Docker images are pushed to private GitHub Container Registry
- Production `.env` file should be kept off the repository
- Regularly rotate SSH keys and GitHub Secrets
- Monitor GitHub Actions logs for failed deployments
