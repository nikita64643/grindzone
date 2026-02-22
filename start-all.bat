@echo off
cd /d "%~dp0"
node scripts\start-servers.js %*
pause
