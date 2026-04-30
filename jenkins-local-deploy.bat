@echo off
REM ================================================================
REM  Jenkins Local Dev Deploy Script - Laravel PHP Project
REM  ================================================================
REM  Purpose:
REM    - Fetch Docker image from GHCR
REM    - Run locally for development/testing
REM    - Tunnel via Cloudflare for temporary public access
REM
REM  Jenkins Setup:
REM    1. Create a "Freestyle project"
REM    2. Check "This project is parameterized"
REM    3. Add String Parameters:
REM       Name             Default Value          Description
REM       ----             -------------          -----------
REM       IMAGE_TAG        latest                 Docker image tag (latest, main, sha-xxx)
REM       LOCAL_PORT       8080                   Local port to run on
REM       CONTAINER_NAME   laravel-dev            Container name
REM       TUNNEL_NAME      laravel-tunnel         Cloudflare tunnel name
REM
REM  Usage:
REM    Developers trigger in Jenkins UI and select image tag to test
REM ================================================================

setlocal enabledelayedexpansion

REM --- Elapsed time helper ---
set "DEPLOY_START_RAW=%TIME%"
for /f "tokens=1-4 delims=:." %%a in ("%TIME%") do (
    set /a "START_S=(%%a * 3600) + (1%%b %% 100 * 60) + (1%%c %% 100)"
)
goto :after_functions

:log
    for /f "tokens=1-4 delims=:." %%a in ("%TIME%") do (
        set /a "NOW_S=(%%a * 3600) + (1%%b %% 100 * 60) + (1%%c %% 100)"
    )
    set /a "ELAPSED=!NOW_S! - !START_S!"
    echo [%TIME%] [+!ELAPSED!s] %~1
    goto :eof

:after_functions

REM === Fixed configuration ===
set REGISTRY=ghcr.io
set IMAGE_NAME=winozz/dcit-cvsu-laravel-training
set GITHUB_ACTOR=winozz

REM Use Jenkins WORKSPACE or fallback
if not defined WORKSPACE set WORKSPACE=C:\Users\user\Documents\laravel-training\dcit-cvsu-laravel-training

REM Use GitHub token (set via Jenkins credentials)
if not defined GITHUB_TOKEN set GITHUB_TOKEN=ghp_YOUR_TOKEN_HERE

REM === Parameterized configuration (set per developer) ===
if not defined IMAGE_TAG set IMAGE_TAG=latest
if not defined LOCAL_PORT set LOCAL_PORT=8080
if not defined CONTAINER_NAME set CONTAINER_NAME=laravel-dev
if not defined TUNNEL_NAME set TUNNEL_NAME=laravel-tunnel

echo ============================================================
echo  Jenkins Local Dev Deploy - Laravel PHP Project
echo ============================================================
call :log "Deploy started"
echo.
call :log "=== Parameters ==="
echo   IMAGE_TAG:      %IMAGE_TAG%
echo   LOCAL_PORT:     %LOCAL_PORT%
echo   CONTAINER_NAME: %CONTAINER_NAME%
echo   TUNNEL_NAME:    %TUNNEL_NAME%
echo.
call :log "=== Fixed Config ==="
echo   REGISTRY:       %REGISTRY%
echo   IMAGE:          %IMAGE_NAME%:%IMAGE_TAG%
echo.

REM ---------------------------------------------------------------
REM  STEP 1/4: Login to GHCR
REM ---------------------------------------------------------------
call :log "[1/4] Logging in to GHCR..."
echo %GITHUB_TOKEN% | docker login %REGISTRY% -u %GITHUB_ACTOR% --password-stdin
if errorlevel 1 (
    call :log "WARNING: Docker login failed - will try cached image"
) else (
    call :log "GHCR login OK."
)
echo.

REM ---------------------------------------------------------------
REM  STEP 2/4: Pull latest image
REM ---------------------------------------------------------------
call :log "[2/4] Pulling image %REGISTRY%/%IMAGE_NAME%:%IMAGE_TAG%..."

call :log "Pulling... (this can take 2-10 min on first pull, ~10s if up to date)"
docker pull --platform linux/amd64 %REGISTRY%/%IMAGE_NAME%:%IMAGE_TAG%
if errorlevel 1 (
    call :log "WARNING: Pull failed - checking for cached image..."
    docker image inspect %REGISTRY%/%IMAGE_NAME%:%IMAGE_TAG% >nul 2>&1
    if errorlevel 1 (
        call :log "ERROR: No cached image available and pull failed!"
        exit /b 1
    )
    call :log "Using cached image."
) else (
    call :log "Pull complete."
)

call :log "Image info:"
for /f "tokens=*" %%d in ('docker inspect --format "{{.Id}}" %REGISTRY%/%IMAGE_NAME%:%IMAGE_TAG% 2^>nul') do (
    echo   Image ID: %%d
)
for /f "tokens=*" %%d in ('docker inspect --format "{{.Created}}" %REGISTRY%/%IMAGE_NAME%:%IMAGE_TAG% 2^>nul') do (
    echo   Created:  %%d
)
echo.

REM ---------------------------------------------------------------
REM  STEP 3/4: Stop and remove existing container
REM ---------------------------------------------------------------
call :log "[3/4] Cleaning up existing container..."

call :log "Stopping container %CONTAINER_NAME% if running..."
docker stop %CONTAINER_NAME% 2>nul || true
docker rm %CONTAINER_NAME% 2>nul || true
call :log "Old container removed."
echo.

REM ---------------------------------------------------------------
REM  STEP 4/4: Run container locally with port mapping
REM ---------------------------------------------------------------
call :log "[4/4] Running container on port %LOCAL_PORT%..."

call :log "Starting container..."
docker run -d --name %CONTAINER_NAME% ^
    -p %LOCAL_PORT%:8080 ^
    -e APP_URL=http://localhost:%LOCAL_PORT% ^
    -e HEALTHCHECK_PATH=/up ^
    %REGISTRY%/%IMAGE_NAME%:%IMAGE_TAG%

if errorlevel 1 (
    call :log "ERROR: Failed to start container"
    exit /b 1
)

call :log "Container started: %CONTAINER_NAME%"
echo.

REM --- Wait for container to be ready ---
call :log "Waiting 10 seconds for application to start..."
timeout /t 10 /nobreak

REM --- Health check ---
call :log "Running health check on port %LOCAL_PORT%..."
set HTTP_CODE=000
for /f "tokens=*" %%h in ('docker exec %CONTAINER_NAME% curl -s -o /dev/null -w "%%{http_code}" http://localhost:8080/up 2^>nul') do set HTTP_CODE=%%h
if "%HTTP_CODE%"=="200" (
    call :log "Health check PASSED (HTTP %HTTP_CODE%)"
) else (
    call :log "WARNING: Health check returned HTTP %HTTP_CODE% - application may not be ready"
)
echo.

REM --- Show container info ---
call :log "Container Status:"
docker inspect --format "  Name: {{.Name}} | Status: {{.State.Status}}" %CONTAINER_NAME%
docker stats %CONTAINER_NAME% --no-stream --format "  CPU: {{.CPUPerc}} | Mem: {{.MemUsage}}"
echo.

REM ---------------------------------------------------------------
REM  BONUS: Cloudflare Tunnel (Temporary Public Access)
REM ---------------------------------------------------------------
call :log "[BONUS] Setting up Cloudflare Tunnel for temporary public access..."
echo.

REM Check if cloudflared is installed
where cloudflared >nul 2>&1
if errorlevel 1 (
    call :log "WARNING: cloudflared not installed"
    call :log "  Install from: https://developers.cloudflare.com/cloudflare-one/connections/connect-applications/install-and-setup/installation/"
    goto :skip_tunnel
)

call :log "Starting Cloudflare quick tunnel..."
call :log "  Tunnel URL will be displayed below (watch for temporary URL)"
echo.

REM Start cloudflared tunnel (this will run indefinitely)
REM Note: This creates a temporary quick tunnel - no account needed
call :log "Cloudflare tunnel starting..."
call :log "  Access at: https://[tunnel-id].trycloudflare.com"
echo.

cloudflared tunnel --url http://localhost:%LOCAL_PORT%

:skip_tunnel

REM --- Final elapsed time ---
for /f "tokens=1-4 delims=:." %%a in ("%TIME%") do (
    set /a "END_S=(%%a * 3600) + (1%%b %% 100 * 60) + (1%%c %% 100)"
)
set /a "TOTAL_S=!END_S! - !START_S!"

echo ============================================================
call :log "Local Dev Deployment Complete!"
echo   Container:  %CONTAINER_NAME%
echo   Image:      %REGISTRY%/%IMAGE_NAME%:%IMAGE_TAG%
echo   Local URL:  http://localhost:%LOCAL_PORT%
echo   Status:     http://localhost:%LOCAL_PORT%/up
echo   Docker:     docker logs -f %CONTAINER_NAME%
echo   Total time: !TOTAL_S! seconds
echo ============================================================
echo.
call :log "To stop the container:"
echo   docker stop %CONTAINER_NAME%
echo.
call :log "To view logs:"
echo   docker logs -f %CONTAINER_NAME%
echo.
call :log "To remove container:"
echo   docker rm %CONTAINER_NAME%
echo.
call :log "✅ Ready for testing!"
echo ============================================================
