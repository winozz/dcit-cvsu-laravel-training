@echo off
REM ================================================================
REM  Local CI/CD Pipeline - Mirrors GitHub Actions
REM  ================================================================
REM  Purpose:
REM    - Run the same tests as GitHub Actions CI
REM    - Build Docker image locally
REM    - Run and verify the container
REM    - Test Cloudflare tunnel
REM
REM  Usage:
REM    local-ci.bat
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

REM === Configuration ===
set REGISTRY=ghcr.io
set IMAGE_NAME=dcit-cvsu-laravel-training
set IMAGE_TAG=local-test
set CONTAINER_NAME=laravel-local-ci
set LOCAL_PORT=8080

echo ============================================================
echo  Local CI/CD Pipeline - Laravel PHP Project
echo ============================================================
call :log "Pipeline started"
echo.

REM ---------------------------------------------------------------
REM  STEP 1: Install PHP Dependencies
REM ---------------------------------------------------------------
call :log "[1/4] Installing PHP Dependencies..."
call :log "Running: composer install"

composer install --no-interaction --prefer-dist --no-progress
if errorlevel 1 (
    call :log "ERROR: Composer install failed!"
    exit /b 1
)

call :log "✅ Dependencies installed"
echo.

REM ---------------------------------------------------------------
REM  STEP 2: Setup Test Environment
REM ---------------------------------------------------------------
call :log "[2/4] Setting up test environment..."

call :log "Copying .env.example to .env"
copy .env.example .env >nul

call :log "Generating app key"
php artisan key:generate

call :log "Creating SQLite test database"
if not exist database mkdir database
type nul > database/database.sqlite

call :log "✅ Test environment ready"
echo.

REM ---------------------------------------------------------------
REM  STEP 3: Run Tests
REM ---------------------------------------------------------------
call :log "[3/4] Running Tests (PHP/Laravel)..."
call :log "Running: php artisan test"
echo.

php artisan test
if errorlevel 1 (
    call :log "❌ Tests FAILED!"
    exit /b 1
)

call :log "✅ All tests passed"
echo.

REM ---------------------------------------------------------------
REM  STEP 4: Build Docker Image
REM ---------------------------------------------------------------
call :log "[4/4] Building Docker Image..."
call :log "Building: %IMAGE_NAME%:%IMAGE_TAG%"
echo.

docker build -f docker/app/Dockerfile -t %IMAGE_NAME%:%IMAGE_TAG% .
if errorlevel 1 (
    call :log "❌ Docker build failed!"
    exit /b 1
)

call :log "✅ Docker image built successfully"
echo.

call :log "Image details:"
for /f "tokens=*" %%d in ('docker inspect --format "{{.Id}}" %IMAGE_NAME%:%IMAGE_TAG% 2^>nul') do (
    echo   Image ID: %%d
)
for /f "tokens=*" %%d in ('docker inspect --format "{{.Size}}" %IMAGE_NAME%:%IMAGE_TAG% 2^>nul') do (
    echo   Size:     %%d bytes
)
echo.

REM ---------------------------------------------------------------
REM  STEP 5: Test Docker Container
REM ---------------------------------------------------------------
call :log "[BONUS] Testing Docker Container Locally..."
echo.

call :log "Cleaning up old container..."
docker stop %CONTAINER_NAME% 2>nul || true
docker rm %CONTAINER_NAME% 2>nul || true

call :log "Starting container: %CONTAINER_NAME%"
docker run -d --name %CONTAINER_NAME% ^
    -p %LOCAL_PORT%:8080 ^
    -e APP_URL=http://localhost:%LOCAL_PORT% ^
    -e HEALTHCHECK_PATH=/up ^
    %IMAGE_NAME%:%IMAGE_TAG%

if errorlevel 1 (
    call :log "❌ Failed to start container!"
    exit /b 1
)

call :log "Container started. Waiting for app to be ready..."
timeout /t 10 /nobreak

call :log "Running health check..."
set HTTP_CODE=000
for /f "tokens=*" %%h in ('docker exec %CONTAINER_NAME% curl -s -o /dev/null -w "%%{http_code}" http://localhost:8080/up 2^>nul') do set HTTP_CODE=%%h

if "%HTTP_CODE%"=="200" (
    call :log "✅ Health check PASSED (HTTP %HTTP_CODE%)"
) else (
    call :log "❌ Health check FAILED (HTTP %HTTP_CODE%)"
    call :log "Container logs:"
    docker logs %CONTAINER_NAME%
    docker stop %CONTAINER_NAME%
    docker rm %CONTAINER_NAME%
    exit /b 1
)
echo.

call :log "Container status:"
docker inspect --format "  Name: {{.Name}} | Status: {{.State.Status}}" %CONTAINER_NAME%
docker stats %CONTAINER_NAME% --no-stream --format "  CPU: {{.CPUPerc}} | Mem: {{.MemUsage}}"
echo.

REM ---------------------------------------------------------------
REM  Final Summary
REM ---------------------------------------------------------------
for /f "tokens=1-4 delims=:." %%a in ("%TIME%") do (
    set /a "END_S=(%%a * 3600) + (1%%b %% 100 * 60) + (1%%c %% 100)"
)
set /a "TOTAL_S=!END_S! - !START_S!"

echo ============================================================
call :log "✅ LOCAL CI/CD PIPELINE COMPLETED SUCCESSFULLY!"
echo ============================================================
echo.
echo Summary:
echo   ✅ Dependencies installed
echo   ✅ Tests passed
echo   ✅ Docker image built
echo   ✅ Container running
echo   ✅ Health check passed
echo.
echo Local Testing URLs:
echo   App:    http://localhost:%LOCAL_PORT%
echo   Health: http://localhost:%LOCAL_PORT%/up
echo.
echo Useful Commands:
echo   View logs:      docker logs -f %CONTAINER_NAME%
echo   Stop container: docker stop %CONTAINER_NAME%
echo   Remove image:   docker rmi %IMAGE_NAME%:%IMAGE_TAG%
echo   Remove all:     docker rm %CONTAINER_NAME% ^& docker rmi %IMAGE_NAME%:%IMAGE_TAG%
echo.
echo Total time: !TOTAL_S! seconds
echo ============================================================
echo.
call :log "Ready to push! All local tests passed."
echo.
echo Next: git push origin main
echo Then monitor: https://github.com/winozz/dcit-cvsu-laravel-training/actions
echo.
