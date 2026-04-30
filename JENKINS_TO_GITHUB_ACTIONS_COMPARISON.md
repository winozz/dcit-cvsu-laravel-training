# Jenkins to GitHub Actions Migration Guide

**Jenkins Config Location:**
```
C:\Users\user\Documents\Test Env\frappe_docker\development\frappe-bench\apps\accounting\accounting\accounting_information_system\doctype\project_accounting_entry\jenkins-deploy.bat
```

**GitHub Actions Equivalent:**
```
c:\Users\user\Documents\laravel-training\dcit-cvsu-laravel-training\.github\workflows\deploy.yml
```

---

## Step-by-Step Comparison

### Jenkins: VALIDATION Phase

Jenkins performs pre-deployment validation:

| Jenkins Step | Purpose | GitHub Actions Equivalent |
|---|---|---|
| Check SITE_NAME is not empty | Validates required parameter | N/A (no parameterization, always main) |
| Check site exists in container | Validates target | N/A (uses docker compose from repo) |
| Check port availability | Prevents port conflicts | N/A (fixed ports: 8010, 8087) |

### Jenkins: Step 1/5 - GHCR Login

```batch
echo %GITHUB_TOKEN% | docker login %REGISTRY% -u %GITHUB_ACTOR% --password-stdin
```

**GitHub Actions Equivalent:**

In `build` job:
```yaml
- name: Log in to Container Registry
  uses: docker/login-action@v3
  with:
    registry: ${{ env.REGISTRY }}
    username: ${{ github.actor }}
    password: ${{ secrets.GITHUB_TOKEN }}
```

**Difference:** GitHub Actions uses the built-in `GITHUB_TOKEN` secret. Jenkins requires manual `GITHUB_TOKEN` as parameter.

---

### Jenkins: Step 2/5 - Pull Docker Image

```batch
docker pull --platform linux/amd64 %REGISTRY%/%IMAGE_NAME%:%IMAGE_TAG%
```

Jenkins parameters:
- `IMAGE_TAG` = developer-selected (main, sha-xxx, etc.)
- `REGISTRY` = ghcr.io
- `IMAGE_NAME` = winozz/cvsu-ais

**GitHub Actions Equivalent:**

In `build` job:
```yaml
- name: Build and push Docker image
  uses: docker/build-push-action@v6
  with:
    context: .
    file: ./docker/app/Dockerfile
    push: true
    tags: ${{ steps.meta.outputs.tags }}
    labels: ${{ steps.meta.outputs.labels }}
```

**Difference:** 
- GitHub Actions **builds** the image (from Dockerfile), then pushes to GHCR
- Jenkins **pulls** a pre-built image (assumes it's already on GHCR)
- In GitHub Actions, the build job replaces both Jenkins' Step 1 (login) and Step 2 (pull)
- Image is automatically tagged as `ghcr.io/your-org/dcit-cvsu-laravel-training:latest`

---

### Jenkins: Step 3/5 - Container Checks

```batch
docker inspect --format "{{.Name}} | Status: {{.State.Status}}" %CONTAINER_NAME%
docker stats %CONTAINER_NAME% --no-stream
```

Purpose: Verify container is running and check resource usage.

**GitHub Actions Equivalent:**

In `deploy` job SSH script:
```bash
# No explicit check step, but happens implicitly when running docker compose commands
# If container is not healthy, the health check loop will fail (Step 5)

# Near the end of deploy, we show stats:
docker stats --no-stream
docker compose ps
```

**Difference:**
- Jenkins checks a **single container** by name (cvsu_ais_frappe)
- GitHub Actions relies on **docker compose health checks** (built into compose.yml)
- GitHub Actions explicitly displays stats at the end for verification

---

### Jenkins: Step 4/5 - Backup, Migrate, Clear Cache

```batch
# Database connectivity test
bench --site %SITE_NAME% mariadb -e 'SELECT DATABASE()'

# Backup
bench --site %SITE_NAME% backup

# Migrate (with retry)
bench --site %SITE_NAME% migrate
# [if fails, retry after 15s]

# Clear cache
bench --site %SITE_NAME% clear-cache
```

Jenkins parameters:
- `SITE_NAME` = developer-selected (e.g., accounting.localhost)
- Uses Frappe/ERPNext `bench` CLI

**GitHub Actions Equivalent:**

In `deploy` job SSH script:
```bash
# No backup (SQLite not backed up)

# Migrate (forced, no retry built-in)
docker compose exec app php artisan migrate --force

# Clear cache (multiple commands)
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

**Differences:**
- No backup step (SQLite file can be backed up separately if needed)
- No explicit DB connectivity test (assumed healthy via healthcheck)
- Uses Laravel `php artisan` commands instead of Frappe `bench` CLI
- Adds `route:cache` and `view:cache` for performance (not in Jenkins)
- No automatic retry (manual retry via re-running the workflow)

---

### Jenkins: Step 5/5 - Start Site on Port

```batch
# Kill existing processes on the port
fuser -k %BENCH_PORT%/tcp
pkill -f 'bench serve.*--port %BENCH_PORT%'

# Start the web server
bench serve --site %SITE_NAME% --port %BENCH_PORT%

# Wait 15 seconds
ping -n 16 127.0.0.1

# Health check
curl -s -o /dev/null -w "%{http_code}" http://localhost:%BENCH_PORT%/api/method/ping
```

Jenkins parameters:
- `BENCH_PORT` = developer-selected per site (9001, 9002, 9003)
- Auto-maps SITE_NAME → BENCH_PORT

**GitHub Actions Equivalent:**

In `deploy` job SSH script:
```bash
# Restart containers (replaces kill + start)
docker compose up -d --no-deps --force-recreate app queue

# Health check loop (more robust than single check)
for i in $(seq 1 12); do
  if docker compose exec app curl -sf http://localhost:8080/up > /dev/null 2>&1; then
    echo "Application is healthy"
    break
  fi
  if [ $i -eq 12 ]; then
    echo "Healthcheck failed after 60 seconds"
    exit 1
  fi
  sleep 5
done
```

**Differences:**
- No explicit port selection (fixed ports from compose.yml)
- Uses `docker compose up` (idempotent) instead of kill + start
- Health check retries up to 12 times over 60 seconds (vs. Jenkins' single attempt after 15s)
- Health check endpoint is `/up` (Laravel) instead of `/api/method/ping` (Frappe)

---

## Full Workflow Comparison

### Jenkins Workflow

```
Developer triggers in Jenkins UI:
  ↓
[Enter parameters: SITE_NAME, BENCH_PORT, IMAGE_TAG]
  ↓
Script runs 5 sequential steps:
  [1/5] GHCR Login
  [2/5] Pull Image
  [3/5] Check Container
  [4/5] Backup + Migrate + Clear Cache
  [5/5] Kill + Start + Health Check
  ↓
Shows elapsed time and final URL
```

**Key Points:**
- Manual trigger (developer clicks "Build with Parameters")
- Parameterized (different sites, ports, image tags)
- Single linear script (no job dependencies)
- Runs inside container (via `docker exec`)

### GitHub Actions Workflow

```
Developer pushes to main:
  ↓
GitHub automatically triggers workflow:
  ↓
[Test Job] (all triggers)
  → Setup PHP
  → Install dependencies
  → Run migrations (in-memory)
  → Run tests
  ↓
[Build Job] (depends on Test, main branch only)
  → Check out code
  → Build Docker image
  → Push to GHCR
  ↓
[Deploy Job] (depends on Build, main branch only)
  → SSH into server
  → Pull image
  → Git pull
  → Build frontend assets
  → Restart containers
  → Run migrations
  → Clear cache
  → Verify tunnel
  ↓
Shows completion status
```

**Key Points:**
- Automatic trigger (on push to main)
- Three separate jobs with dependencies
- Runs tests before build (quality gate)
- Builds image (not just pulls)
- Deploys only if build succeeds
- Verifies Cloudflare Tunnel (NEW feature)

---

## Feature Comparison

| Feature | Jenkins | GitHub Actions |
|---|---|---|
| **Trigger** | Manual (UI button) | Automatic (git push) |
| **Parameterization** | Yes (SITE_NAME, BENCH_PORT) | No (fixed config) |
| **Multi-site Support** | Yes (port mapping) | N/A (single Laravel app) |
| **Testing** | Not included | Yes (test job runs first) |
| **Image Building** | No (pulls pre-built) | Yes (builds on every push) |
| **Image Registry** | GHCR | GHCR |
| **Deployment Target** | Single container (cvsu_ais_frappe) | Multiple containers (docker compose) |
| **Backup** | Yes (bench backup) | No |
| **Migrate** | Yes (bench migrate with retry) | Yes (artisan migrate with loop healthcheck) |
| **Cache Clear** | Yes (bench clear-cache) | Yes (artisan cache:clear + config:clear + route:cache + view:cache) |
| **App Start** | Yes (bench serve on port) | Yes (docker compose up) |
| **Health Check** | Single attempt (curl /api/method/ping) | 12 retries (curl /up every 5s) |
| **Tunnel Support** | No | Yes (Cloudflare Tunnel on 8087) |
| **Logs** | On server or Jenkins logs | GitHub Actions logs + docker logs |
| **Rollback** | N/A (git-based, manual restart) | git reset --hard + docker compose up |
| **Metrics** | Docker stats at end | Docker stats + container status |

---

## Configuration Reference

### Jenkins Variables (from jenkins-deploy.bat)

```batch
Fixed Configuration:
  REGISTRY                = ghcr.io
  IMAGE_NAME              = winozz/cvsu-ais
  GITHUB_ACTOR            = winozz
  GITHUB_TOKEN            = <REDACTED_GITHUB_TOKEN> (removed - SECURITY RISK)
  CONTAINER_NAME          = cvsu_ais_frappe
  COMPOSE_DIR             = C:\Users\user\Documents\Test Env\frappe_docker\.devcontainer
  COMPOSE_FILE            = docker-compose.cvsu-ais.yml
  DEPLOY_USER             = user

Parameterized Configuration (per site):
  SITE_NAME               = accounting.localhost (default)
  BENCH_PORT              = 9001 (default)
  IMAGE_TAG               = main (default)
  DEPLOY_HOST             = localhost (default)
```

### GitHub Actions Variables (from deploy.yml)

```yaml
Fixed Configuration:
  REGISTRY                = ghcr.io
  IMAGE_NAME              = ${{ github.repository }} (auto from repo)
  GITHUB_ACTOR            = ${{ github.actor }} (current user)
  GITHUB_TOKEN            = ${{ secrets.GITHUB_TOKEN }} (built-in, rotated each run)

Parameterized Configuration (from Secrets):
  DEPLOY_HOST             = ${{ secrets.DEPLOY_HOST }}
  DEPLOY_USER             = ${{ secrets.DEPLOY_USER }}
  DEPLOY_SSH_KEY          = ${{ secrets.DEPLOY_SSH_KEY }}
  DEPLOY_PATH             = ${{ secrets.DEPLOY_PATH }}

Fixed Paths (from docker-compose):
  APP_PORT_HOST           = 8010
  APP_PORT_CONTAINER      = 8080
  TUNNEL_PORT             = 8087
  QUEUE_SERVICE           = queue (from compose.yml)
```

---

## Jenkins-to-Actions Porting Lessons

### What to Port (Good Ideas from Jenkins)

✅ **GHCR Login** — Both use GitHub token to authenticate
✅ **Image Management** — Both pull/push from/to GHCR
✅ **Database Migration** — Both run migrations before starting app
✅ **Cache Clearing** — Both clear cache after migration
✅ **Health Checks** — Both verify app is responding
✅ **Logging & Status** — Both show elapsed time, resource usage, final status

### What to Improve (Changes Made)

✅ **Add Testing** — GitHub Actions runs tests before deployment (Jenkins has no tests)
✅ **Image Building** — Build image once, push to registry, deploy pre-built (more reliable)
✅ **Job Dependencies** — Clear stages (test → build → deploy) with dependency gates
✅ **Health Check Robustness** — Retry up to 12 times over 60 seconds (Jenkins is single attempt)
✅ **Security** — Use GitHub Secrets for credentials (Jenkins has hardcoded token)
✅ **Tunnel Support** — Add Cloudflare Tunnel for public access (Jenkins doesn't have)
✅ **Automation** — Trigger automatically on push (Jenkins requires manual trigger)

### What Won't Work (Framework Differences)

❌ **Parameterization** — GitHub Actions doesn't have per-developer parameters like Jenkins (by design: all pushes to main are same config)
❌ **Multi-site** — GitHub Actions deploys one app, not multiple Frappe sites
❌ **Bench CLI** — GitHub Actions uses Laravel artisan, not Frappe bench
❌ **Backup** — SQLite backups aren't automated (can be added with separate scripts)
❌ **Manual Trigger** — GitHub Actions is event-driven, not manual (by design: CI/CD best practice)

---

## Quick Reference: What Changed from Jenkins to GitHub Actions

| Aspect | Jenkins | → | GitHub Actions |
|---|---|---|---|
| **Config File** | windows `.bat` script | → | YAML workflow |
| **Trigger** | `curl http://jenkins:8080/job/...` | → | `git push origin main` |
| **SITE_NAME param** | `SITE_NAME=accounting.localhost` | → | Fixed (single app) |
| **BENCH_PORT param** | `BENCH_PORT=9001` | → | Fixed (8010 + 8087 for tunnel) |
| **IMAGE_TAG param** | `IMAGE_TAG=main` | → | Auto-tagged `main:latest` |
| **Image action** | Pull (Step 2/5) | → | Build + Push (build job) |
| **Container name** | `cvsu_ais_frappe` | → | Named services in compose |
| **Start app** | `bench serve --port X` | → | `docker compose up -d` |
| **Health check** | Single `curl` (Step 5/5) | → | Loop retry 12x |
| **Tunnel** | Not supported | → | Cloudflare Tunnel verified |
| **Logs** | Server logs + Jenkins output | → | GitHub Actions logs + docker logs |
