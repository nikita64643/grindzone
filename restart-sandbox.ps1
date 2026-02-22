# Restart SandBox 1.16.5 via Node.js
$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot
& node scripts/restart-server.js --port 25566 --version 1.16.5 --name SandBox
