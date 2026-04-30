# Jenkins Local Dev Deployment - Setup Guide

## Overview

This Jenkins job allows developers to:
1. ✅ **Pull Docker image** from GHCR (GitHub Container Registry)
2. ✅ **Run locally** for testing/development
3. ✅ **Tunnel via Cloudflare** for temporary public preview

Perfect for testing the Docker image before it goes to production.

---

## 📋 Prerequisites

### Local Machine (Jenkins Agent)
- Docker installed and running
- cloudflared installed (optional - for tunnel)
- Git (for credentials)

### Jenkins Server
- Jenkins 2.0+
- Docker plugin
- GitHub credentials configured

---

## 🚀 Setup Instructions

### Option 1: Freestyle Job (Easier)

#### Step 1: Create New Freestyle Job

1. Jenkins Dashboard → **New Item**
2. Enter name: `Deploy-Laravel-LocalDev`
3. Select: **Freestyle project**
4. Click **OK**

#### Step 2: Configure Parameters

1. Check: **This project is parameterized**
2. Add 4 String Parameters:

| Parameter | Default | Description |
|---|---|---|
| `IMAGE_TAG` | `latest` | Docker image tag (latest, main, git-sha) |
| `LOCAL_PORT` | `8080` | Port to expose app on |
| `CONTAINER_NAME` | `laravel-dev` | Container name |
| `TUNNEL_NAME` | `laravel-tunnel` | Cloudflare tunnel name |

**How to add parameters:**
1. Click **Add Parameter** → **String Parameter**
2. Fill in Name, Default Value, Description
3. Repeat for each parameter
4. Click **Save**

#### Step 3: Configure Build Steps

1. Click **Add build step** → **Execute Windows batch command**

2. Copy the entire script from: `jenkins-local-deploy.bat`

3. Paste into the command field

4. Click **Save**

#### Step 4: Set GitHub Token

1. Go to Jenkins → **Manage Jenkins** → **Manage Credentials**
2. Click **Jenkins** (under Stores)
3. Click **Global credentials**
4. Click **Add Credentials**
5. Kind: **Secret text**
6. Secret: (paste your GitHub token)
7. ID: `github-token`
8. Click **Create**

---

### Option 2: Pipeline Job (More Flexible)

#### Step 1: Create New Pipeline Job

1. Jenkins Dashboard → **New Item**
2. Enter name: `Deploy-Laravel-LocalDev-Pipeline`
3. Select: **Pipeline**
4. Click **OK**

#### Step 2: Configure Pipeline

1. In the **Pipeline** section, select: **Pipeline script from SCM**
2. SCM: **Git**
3. Repository URL: `https://github.com/winozz/dcit-cvsu-laravel-training.git`
4. Branch: `*/main`
5. Script Path: `Jenkinsfile.local-dev`
6. Click **Save**

#### Step 3: Set GitHub Token (Same as Option 1)

1. Go to Jenkins → **Manage Jenkins** → **Manage Credentials**
2. Add GitHub token with ID: `github-token`

---

## 🎯 Using the Job

### Run the Job

1. Jenkins Dashboard → Click your job name
2. Click **Build with Parameters** (or **Run** for Pipeline)
3. Enter parameters:
   - **IMAGE_TAG**: `latest` (or specific tag like `main` or `sha-abc123`)
   - **LOCAL_PORT**: `8080` (or different port)
   - **CONTAINER_NAME**: `laravel-dev` (or custom name)
   - **TUNNEL_NAME**: `laravel-tunnel` (or custom name)
4. Click **Build** (or **Run**)

### Monitor Build

1. Click the build number (e.g., `#5`)
2. Click **Console Output**
3. Watch logs in real-time

---

## 📊 What Happens

```
Step 1: Login to GHCR
  → Docker logs into GitHub Container Registry with token

Step 2: Pull Image
  → Downloads Docker image: ghcr.io/winozz/dcit-cvsu-laravel-training:TAG

Step 3: Cleanup
  → Stops and removes old container if running

Step 4: Run Container
  → Starts new container on LOCAL_PORT with health check

Step 5: Cloudflare Tunnel (Optional)
  → Creates temporary tunnel to localhost:LOCAL_PORT
  → Generates public URL (https://xxx.trycloudflare.com)
```

---

## 🌐 Access Your App

After build completes:

### Local Access
```
http://localhost:8080
```
(or whatever LOCAL_PORT you specified)

### Health Check
```
http://localhost:8080/up
```

### View Logs
```bash
docker logs -f laravel-dev
```

### Public Access (via Cloudflare Tunnel)
```
https://[tunnel-url].trycloudflare.com
```
(URL shown in Jenkins console output)

---

## 🛑 Stop & Remove

After testing, clean up:

```bash
# Stop container
docker stop laravel-dev

# Remove container
docker rm laravel-dev

# Stop Cloudflare tunnel
# (Press Ctrl+C in Jenkins console)
```

---

## 🔄 Different Image Tags

### Pull Latest Development Image
```
IMAGE_TAG: main
```

### Pull Specific Git Commit
```
IMAGE_TAG: sha-abc1234def5678
```

### Pull Last Release
```
IMAGE_TAG: v1.0.0
```

---

## 🌐 Cloudflare Tunnel Details

### How It Works

1. **cloudflared** creates a secure tunnel to your local app
2. Generates temporary public URL (valid for 2-3 hours)
3. No configuration needed (quick tunnel mode)
4. Perfect for sharing with team temporarily

### Example Tunnel Output

```
Tunnel created! Access your app at:
https://example-abc-123.trycloudflare.com
```

Share this URL with your team to test!

### Disable Tunnel

If you don't want the tunnel:
- Modify the job to not run cloudflared step
- Or just ignore the tunnel URL

---

## 🆘 Troubleshooting

### Build Fails - Image Not Found

**Error:**
```
ERROR: No cached image available and pull failed!
```

**Fix:**
1. Check IMAGE_TAG is correct
2. Verify GitHub token is valid
3. Check network connectivity
4. Verify GHCR image exists: `docker search ghcr.io/winozz/dcit-cvsu-laravel-training`

---

### Port Already In Use

**Error:**
```
Error response from daemon: bind: address already in use
```

**Fix:**
1. Check what's using the port: `netstat -ano | findstr :8080`
2. Stop the process or use a different LOCAL_PORT
3. Or remove old container: `docker rm laravel-dev`

---

### cloudflared Not Installed

**Error:**
```
cloudflared: command not found
```

**Fix:**
1. Install cloudflared: https://developers.cloudflare.com/cloudflare-one/connections/connect-applications/install-and-setup/
2. Or disable tunnel in job parameters

---

### Health Check Fails

**Error:**
```
curl: (7) Failed to connect to localhost port 8080
```

**Fix:**
1. Wait longer - app might still be starting
2. Check docker logs: `docker logs laravel-dev`
3. Verify LOCAL_PORT mapping is correct
4. Check firewall allows port 8080

---

## 📝 Parameters Reference

### IMAGE_TAG
- **What:** Docker image tag to pull from GHCR
- **Examples:**
  - `latest` - Most recent image
  - `main` - Main branch image
  - `sha-abc1234` - Specific commit
  - `v1.0.0` - Release version
- **Default:** `latest`

### LOCAL_PORT
- **What:** Port to expose app on localhost
- **Examples:**
  - `8080` - Default
  - `8081` - If 8080 is in use
  - `3000` - Alternative
- **Default:** `8080`
- **Access at:** `http://localhost:8080`

### CONTAINER_NAME
- **What:** Docker container name
- **Examples:**
  - `laravel-dev` - General dev
  - `laravel-test-1` - Testing instance 1
  - `laravel-feature-branch` - Feature branch test
- **Default:** `laravel-dev`
- **Note:** Each job needs unique container name

### TUNNEL_NAME
- **What:** Cloudflare tunnel identifier
- **Examples:**
  - `laravel-tunnel` - Production-like tunnel
  - `laravel-dev-temp` - Temporary dev access
- **Default:** `laravel-tunnel`
- **Note:** For quick tunnel, this is not used

---

## 🎯 Common Workflows

### Test Latest Image
```
IMAGE_TAG: latest
LOCAL_PORT: 8080
CONTAINER_NAME: laravel-dev
USE_CLOUDFLARE_TUNNEL: ☑️ (checked)
```

### Test Specific Feature Branch
```
IMAGE_TAG: sha-[git-sha-of-feature]
LOCAL_PORT: 8081
CONTAINER_NAME: laravel-feature-test
USE_CLOUDFLARE_TUNNEL: ☑️ (checked)
```

### Test Multiple Versions in Parallel
```
Job 1:
  IMAGE_TAG: main
  LOCAL_PORT: 8080
  CONTAINER_NAME: laravel-main

Job 2:
  IMAGE_TAG: latest
  LOCAL_PORT: 8081
  CONTAINER_NAME: laravel-latest
```

---

## 🔒 Security Notes

- **GitHub Token:** Keep it secret - stored in Jenkins Credentials
- **Local Testing:** Container runs on localhost (not public until tunneled)
- **Tunnel URL:** Temporary (valid 2-3 hours) and random
- **Credentials:** Tunnel creates secure connection via Cloudflare

---

## 📚 Quick Reference

| Task | Command |
|---|---|
| View logs | `docker logs -f laravel-dev` |
| Stop container | `docker stop laravel-dev` |
| Remove container | `docker rm laravel-dev` |
| See running containers | `docker ps` |
| See all containers | `docker ps -a` |
| Health check | `curl http://localhost:8080/up` |
| Restart container | `docker restart laravel-dev` |

---

## ✨ Next Steps

1. **Setup Job** - Follow the setup instructions above
2. **Add GitHub Token** - In Jenkins Credentials
3. **Test Job** - Build with default parameters
4. **Share Tunnel URL** - Test with team via Cloudflare
5. **Adjust Port** - Use different port if needed

---

**Questions?** Check the troubleshooting section or review the script files:
- `jenkins-local-deploy.bat` - Batch version
- `Jenkinsfile.local-dev` - Pipeline version
