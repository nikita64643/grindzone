# Fix lobby NPC and menu - restart lobby, wait, run citizens reload + npc spawn 1
# Run from project root

param([switch]$Restart)

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot\..

# Ensure spawn-protection=0
$props = "servers\lobby\server.properties"
$content = Get-Content $props -Raw
if ($content -match "spawn-protection=\d+") {
  $content = $content -replace "spawn-protection=\d+", "spawn-protection=0"
  [System.IO.File]::WriteAllText((Resolve-Path $props).Path, $content)
  Write-Host "spawn-protection=0" -ForegroundColor Green
}

if ($Restart) {
  Write-Host "Restarting lobby..." -ForegroundColor Yellow
  node scripts\restart-server.js --port 25580 --dir servers\lobby
  Write-Host "Waiting 75s for server to be ready..." -ForegroundColor Gray
  Start-Sleep -Seconds 75
  Write-Host "Sending citizens reload, npc spawn 1 via RCON..." -ForegroundColor Yellow
  node scripts\send-lobby-rcon.js "citizens reload" "npc spawn 1"
  Write-Host "Done." -ForegroundColor Green
} else {
  Write-Host "Run with -Restart to restart lobby and spawn NPC." -ForegroundColor Gray
  Write-Host "Or run: node scripts\send-lobby-rcon.js" -ForegroundColor Gray
}
