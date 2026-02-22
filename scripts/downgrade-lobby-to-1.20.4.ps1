# Downgrade lobby to Paper 1.20.4 for Citizens + ItemJoin compatibility
# WARNING: Backup world_lobby first! Downgrade may corrupt world if it was created on 1.21+
$ErrorActionPreference = "Stop"
$lobby = "C:\Users\nikita\Desktop\minecruft\servers\lobby"
$backup = "$lobby\server-1.21.10.jar.bak"

if (-not (Test-Path "$lobby\server.jar")) { Write-Host "server.jar not found"; exit 1 }

# Backup current server jar (stop server first!)
Copy-Item "$lobby\server.jar" $backup -Force
Write-Host "Backed up to server-1.21.10.jar.bak" -ForegroundColor Green

# Download Paper 1.20.4 (build 499)
$url = "https://api.papermc.io/v2/projects/paper/versions/1.20.4/builds/499/downloads/paper-1.20.4-499.jar"
$dest = "$lobby\server.jar"
Invoke-WebRequest -Uri $url -OutFile $dest -UseBasicParsing
Write-Host "Downloaded Paper 1.20.4" -ForegroundColor Green

# restart-server.js already updated for 1.20.4

Write-Host ""
Write-Host "DONE. Restart lobby: node scripts/restart-server.js --port 25580 --dir servers/lobby" -ForegroundColor Yellow
Write-Host "If world has issues, restore server-1.21.10.jar.bak and world_lobby backup." -ForegroundColor Gray
