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
REM       LOCAL_PORT       9090                   Local port to run on
REM       CONTAINER_NAME   laravel-dev            Container name
REM       TUNNEL_NAME      laravel-tunnel         Cloudflare tunnel name
REM
REM    4. Under Environment > Use secret text(s) or file(s), add:
REM       Kind             Variable               Credential ID
REM       ----             --------               -------------
REM       Secret text      GITHUB_TOKEN           github-token
REM       Secret text      APP_KEY                laravel-app-key
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

REM Validate required credentials (set via Jenkins Environment > Use secret text)
if not defined GITHUB_TOKEN (
    echo ERROR: GITHUB_TOKEN is not defined. Add Secret text credential with ID "github-token" in Jenkins Environment.
    exit /b 1
)
call :log "GITHUB_TOKEN is defined"

if not defined APP_KEY (
    echo ERROR: APP_KEY is not defined. Add Secret text credential with ID "laravel-app-key" in Jenkins Environment.
    exit /b 1
)
call :log "APP_KEY is defined"

REM === Parameterized configuration (set per developer) ===
if not defined IMAGE_TAG set IMAGE_TAG=latest
if not defined LOCAL_PORT set LOCAL_PORT=9090
if not defined CONTAINER_NAME set CONTAINER_NAME=laravel-dev
if not defined TUNNEL_NAME set TUNNEL_NAME=laravel-tunnel

echo ============================================================
echo  Jenkins Local Dev Deploy - Laravel PHP Project
echo ============================================================
call :log "Deploy started"

REM Check if docker is available
docker --version >nul 2>&1
if errorlevel 1 (
    call :log "ERROR: Docker is not installed or not in PATH"
    exit /b 1
)
call :log "Docker is available"
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
    call :log "WARNING: Pull failed with error code %errorlevel% - checking for cached image..."
    docker image inspect %REGISTRY%/%IMAGE_NAME%:%IMAGE_TAG% >nul 2>&1
    if errorlevel 1 (
        call :log "ERROR: No cached image available and pull failed!"
        call :log "Docker error details - ensure docker daemon is running and GITHUB_TOKEN has read:packages permission"
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
docker stop %CONTAINER_NAME% >nul 2>&1
docker rm %CONTAINER_NAME% >nul 2>&1
call :log "Old container removed."
echo.

REM ---------------------------------------------------------------
REM  STEP 4/4: Run container locally with port mapping
REM ---------------------------------------------------------------
call :log "[4/4] Running container on port %LOCAL_PORT%..."

call :log "Starting container..."
docker run -d --name %CONTAINER_NAME% ^
    -p %LOCAL_PORT%:8080 ^
    -e APP_KEY=%APP_KEY% ^
    -e APP_URL=http://127.0.0.1:%LOCAL_PORT% ^
    -e APP_ENV=staging ^
    -e APP_DEBUG=false ^
    -e LOG_CHANNEL=stderr ^
    -e HEALTHCHECK_PATH=/up ^
    %REGISTRY%/%IMAGE_NAME%:%IMAGE_TAG%

if errorlevel 1 (
    call :log "ERROR: Failed to start container"
    exit /b 1
)

call :log "Container started: %CONTAINER_NAME%"
echo.

REM --- Wait for container to be ready ---
call :log "Waiting for Laravel application to start (up to 60 seconds)..."
set HTTP_CODE=000
set RETRY_COUNT=0
:health_check_loop
set /a RETRY_COUNT+=1
ping -n 4 127.0.0.1 >nul 2>&1
docker exec %CONTAINER_NAME% curl -s -o /dev/null -w "%%{http_code}" http://127.0.0.1:8080/up >temp_http.txt 2>nul
if exist temp_http.txt (
    set /p HTTP_CODE=<temp_http.txt
    del temp_http.txt
)
if "%HTTP_CODE%"=="200" (
    call :log "Health check PASSED on attempt %RETRY_COUNT% (HTTP %HTTP_CODE%)"
    goto :health_done
)
if %RETRY_COUNT% geq 20 (
    call :log "WARNING: Health check failed after 20 attempts (HTTP %HTTP_CODE%)"
    call :log "Container logs (last 20 lines):"
    docker logs --tail 20 %CONTAINER_NAME%
    goto :health_done
)
call :log "  Attempt %RETRY_COUNT%: HTTP %HTTP_CODE% - retrying..."
goto :health_check_loop
:health_done
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

REM Resolve cloudflared — ALWAYS use a portable download, ignoring any system install.
REM Reason: a system-wide cloudflared.exe on this Jenkins host has a broken/stale build
REM that routes `--url` to named-tunnel auth (verified via env-debug: even with a clean
REM env, isolated USERPROFILE, and an explicit --config pointing at an empty yaml, the
REM binary still demanded an origin cert). Downloading the latest portable into the
REM workspace bypasses whatever's wrong with the system install.
set "CLOUDFLARED_EXE=%WORKSPACE%\cloudflared.exe"
if not exist "!CLOUDFLARED_EXE!" (
    call :log "Downloading portable cloudflared..."
    powershell -NoProfile -Command "[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -Uri 'https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe' -OutFile '!CLOUDFLARED_EXE!' -UseBasicParsing"
    if errorlevel 1 (
        call :log "WARNING: Failed to download cloudflared - skipping tunnel"
        goto :skip_tunnel
    )
    call :log "Portable cloudflared downloaded"
)
set "CLOUDFLARED_CMD=!CLOUDFLARED_EXE!"

REM Print version so we can confirm which binary is actually running
call :log "cloudflared version:"
"!CLOUDFLARED_CMD!" --version 2>&1

REM Kill any existing tunnel so we get a fresh URL
taskkill /F /IM cloudflared.exe >nul 2>&1

call :log "Starting Cloudflare quick tunnel (trycloudflare.com - no account needed)..."
call :log "  Tunnel URL will appear in: %WORKSPACE%\cloudflared-tunnel.log"

REM Delete old logs so we don't read stale URLs
if exist "%WORKSPACE%\cloudflared-tunnel.log" del "%WORKSPACE%\cloudflared-tunnel.log"
if exist "%WORKSPACE%\cloudflared-tunnel-err.log" del "%WORKSPACE%\cloudflared-tunnel-err.log"
if exist "%WORKSPACE%\cloudflared-launcher.log" del "%WORKSPACE%\cloudflared-launcher.log"

REM Create an isolated home directory for cloudflared so the Jenkins SYSTEM user's
REM ~/.cloudflared/config.yml (which may contain a 'tunnel:' key for a named tunnel)
REM is never read. cloudflared resolves ~ via the USERPROFILE env var in Go.
REM
REM Quick tunnels are not supported when config.yml/config.yaml exists under
REM .cloudflared, so keep this isolated home clean and remove any stale files
REM from previous runs before launching cloudflared.
set "CF_HOME=%WORKSPACE%\.cf-home"
set "CF_CONFIG_DIR=!CF_HOME!\.cloudflared"
if exist "!CF_HOME!" rmdir /s /q "!CF_HOME!"
mkdir "!CF_HOME!" 2>nul

REM NOTE: cloudflared writes ALL output (log lines, tunnel URL) to STDERR, not stdout.
REM      We generate a one-shot launcher .ps1 that scrubs the env and starts
REM      cloudflared directly via Start-Process with stdout/stderr redirected
REM      to files. This avoids Jenkins keeping the build open on inherited
REM      handles and avoids ProcessTreeKiller killing the tunnel at the end
REM      of the build step.
REM
REM      Env scrub: Jenkins SYSTEM may have TUNNEL_ORIGIN_CERT / TUNNEL_CONFIG set
REM      machine-wide from prior debugging commits — those force named-tunnel mode and
REM      break --url quick tunnels. We also point USERPROFILE/HOME at an isolated dir.
REM
REM      Critical: do NOT create or pass a config.yml for quick tunnels. Cloudflare's
REM      docs explicitly state TryCloudflare is unsupported when a config file is present
REM      in .cloudflared. The isolated home above is enough to avoid any SYSTEM-level
REM      named-tunnel config without forcing cloudflared into locally-managed mode.
set "TUNNEL_LAUNCHER=%WORKSPACE%\cloudflared-launch.ps1"
> "%TUNNEL_LAUNCHER%" echo Get-ChildItem Env:TUNNEL_* -ErrorAction SilentlyContinue ^| Remove-Item -Force -ErrorAction SilentlyContinue
>> "%TUNNEL_LAUNCHER%" echo $env:BUILD_ID = 'dontKillMe'
>> "%TUNNEL_LAUNCHER%" echo $env:JENKINS_NODE_COOKIE = 'dontKillMe'
>> "%TUNNEL_LAUNCHER%" echo $env:USERPROFILE = '!CF_HOME!'
>> "%TUNNEL_LAUNCHER%" echo $env:HOME = '!CF_HOME!'
>> "%TUNNEL_LAUNCHER%" echo $logPath = '%WORKSPACE%\cloudflared-tunnel.log'
>> "%TUNNEL_LAUNCHER%" echo $outPath = '%WORKSPACE%\cloudflared-tunnel-err.log'
>> "%TUNNEL_LAUNCHER%" echo $debugPath = '%WORKSPACE%\cloudflared-launcher.log'
>> "%TUNNEL_LAUNCHER%" echo Set-Content -Path $debugPath -Value '=== LAUNCHER ENV DEBUG ==='
>> "%TUNNEL_LAUNCHER%" echo Add-Content -Path $debugPath -Value ('CLOUDFLARED_CMD=!CLOUDFLARED_CMD!')
>> "%TUNNEL_LAUNCHER%" echo Add-Content -Path $debugPath -Value ('BUILD_ID=' + $env:BUILD_ID)
>> "%TUNNEL_LAUNCHER%" echo Add-Content -Path $debugPath -Value ('JENKINS_NODE_COOKIE=' + $env:JENKINS_NODE_COOKIE)
>> "%TUNNEL_LAUNCHER%" echo Add-Content -Path $debugPath -Value ('USERPROFILE=' + $env:USERPROFILE)
>> "%TUNNEL_LAUNCHER%" echo $tunnelVars = Get-ChildItem Env:TUNNEL_* -ErrorAction SilentlyContinue
>> "%TUNNEL_LAUNCHER%" echo if ($tunnelVars) { $tunnelVars ^| Sort-Object Name ^| ForEach-Object { Add-Content -Path $debugPath -Value ($_.Name + '=' + $_.Value) } } else { Add-Content -Path $debugPath -Value 'TUNNEL_VARS=[]' }
>> "%TUNNEL_LAUNCHER%" echo Add-Content -Path $debugPath -Value ('CONFIG_DIR=!CF_CONFIG_DIR!')
>> "%TUNNEL_LAUNCHER%" echo Add-Content -Path $debugPath -Value '=== END DEBUG ==='
>> "%TUNNEL_LAUNCHER%" echo Start-Process -FilePath '!CLOUDFLARED_CMD!' -ArgumentList @('tunnel','--url','http://127.0.0.1:%LOCAL_PORT%','--no-autoupdate') -WorkingDirectory '%WORKSPACE%' -RedirectStandardError $logPath -RedirectStandardOutput $outPath -WindowStyle Hidden ^| Out-Null

powershell -NoProfile -ExecutionPolicy Bypass -File "%TUNNEL_LAUNCHER%"
if errorlevel 1 (
    call :log "WARNING: Failed to start detached cloudflared launcher - skipping tunnel"
    goto :skip_tunnel
)

REM Poll log file for tunnel URL (up to 30 seconds)
call :log "Waiting for tunnel URL..."
set TUNNEL_URL=
set TUNNEL_WAIT=0
:tunnel_wait_loop
ping -n 3 127.0.0.1 >nul 2>&1
set /a TUNNEL_WAIT+=3
for /f "usebackq delims=" %%X in (`powershell -NoProfile -Command "$match = Select-String -Path '%WORKSPACE%\cloudflared-tunnel.log' -Pattern 'https://\S+trycloudflare\.com' | Select-Object -Last 1; if ($match) { [regex]::Match($match.Line, 'https://\S+trycloudflare\.com').Value }"`) do set "TUNNEL_URL=%%X"
if defined TUNNEL_URL (
    goto :tunnel_found
)
if !TUNNEL_WAIT! geq 30 (
    call :log "WARNING: Tunnel URL not found after 30 seconds"
    if exist "%WORKSPACE%\cloudflared-launcher.log" (
        call :log "launcher debug log:"
        type "%WORKSPACE%\cloudflared-launcher.log"
    )
    if exist "%WORKSPACE%\cloudflared-tunnel.log" (
        call :log "cloudflared log:"
        type "%WORKSPACE%\cloudflared-tunnel.log"
    )
    goto :skip_tunnel
)
goto :tunnel_wait_loop

:tunnel_found
echo.
echo ============================================================
call :log "Cloudflare Tunnel is LIVE!"
call :log "  Public URL: !TUNNEL_URL!"
echo ============================================================
echo.

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
echo   Local URL:  http://127.0.0.1:%LOCAL_PORT%
echo   Status:     http://127.0.0.1:%LOCAL_PORT%/up
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
exit /b 0
