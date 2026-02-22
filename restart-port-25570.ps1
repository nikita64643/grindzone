# Restart server on port 25570 (1.21.10 SandBox) via Node.js
$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot
& node scripts/restart-server.js --port 25570 --version 1.21.10 --name SandBox
