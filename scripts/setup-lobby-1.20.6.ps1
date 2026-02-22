# Setup lobby: Paper 1.20.6 + Citizens 2.0.40 (for compatibility with ItemJoin)
# Run from project root. Stop lobby first!
$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot\..

$lobby = "servers\lobby"

# 1. Downgrade to Paper 1.20.6
Write-Host "Downloading Paper 1.20.6..." -ForegroundColor Yellow
Copy-Item "$lobby\server.jar" "$lobby\server-1.21.10.jar.bak" -Force -ErrorAction SilentlyContinue
$url = "https://api.papermc.io/v2/projects/paper/versions/1.20.6/builds/151/downloads/paper-1.20.6-151.jar"
Invoke-WebRequest -Uri $url -OutFile "$lobby\server.jar" -UseBasicParsing
Write-Host "Paper 1.20.6 installed." -ForegroundColor Green

# 2. Install Citizens 2.0.40 for 1.20.6
Write-Host "Installing Citizens 2.0.40..." -ForegroundColor Yellow
Get-ChildItem "$lobby\plugins" -Filter "Citizens*.jar" -ErrorAction SilentlyContinue | Remove-Item -Force
$url = "https://ci.citizensnpcs.co/job/Citizens2/4000/artifact/dist/target/Citizens-2.0.40-b4000.jar"
Invoke-WebRequest -Uri $url -OutFile "$lobby\plugins\Citizens-2.0.40-b4000.jar" -UseBasicParsing
Write-Host "Citizens 2.0.40 installed." -ForegroundColor Green

Write-Host ""
Write-Host "DONE. Restart lobby: node scripts/restart-server.js --port 25580 --dir servers/lobby" -ForegroundColor Yellow
Write-Host "Then run: node scripts/send-lobby-rcon.js `"citizens reload`" `"npc spawn 1`"" -ForegroundColor Gray
