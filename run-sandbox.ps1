param(
  [string]$Xms = "2G",
  [string]$Xmx = "4G"
)

$ErrorActionPreference = "Stop"

$root = $PSScriptRoot
$server = "SandBox"
$serverDir = Join-Path $root ("servers\" + $server)

Write-Host "1) Downloading mods (if mods-urls.txt is filled)..."
& (Join-Path $root "download-mods.ps1") -Server $server

Write-Host ""
Write-Host "2) Starting server..."
& (Join-Path $serverDir "start.ps1") -Xms $Xms -Xmx $Xmx

