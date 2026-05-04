@echo off
for /f "tokens=1 delims==" %%V in ('set TUNNEL_ 2^>nul') do set "%%V="
set "USERPROFILE=C:\Users\user\Documents\laravel-training\dcit-cvsu-laravel-training\.cf-home-loop-validate"
set "HOME=C:\Users\user\Documents\laravel-training\dcit-cvsu-laravel-training\.cf-home-loop-validate"
cloudflared tunnel --url http://127.0.0.1:9090 --no-autoupdate
