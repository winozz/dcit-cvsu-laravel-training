@echo off
set "TUNNEL_ORIGIN_CERT="
set "TUNNEL_CONFIG="
set "TUNNEL_TOKEN="
set "TUNNEL_ID="
set "USERPROFILE=C:\Users\user\Documents\laravel-training\dcit-cvsu-laravel-training\.cf-home-launcher-check"
set "HOME=C:\Users\user\Documents\laravel-training\dcit-cvsu-laravel-training\.cf-home-launcher-check"
cloudflared tunnel --url http://127.0.0.1:9090 --no-autoupdate
