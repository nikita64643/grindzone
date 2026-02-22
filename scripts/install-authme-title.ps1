# Install AuthMeTitle - shows Title/Subtitle for unauthenticated players (auth splash)
# Run from project root. Requires lobby server.

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot\..

$jarUrl = "https://github.com/Zjaun/AuthMeTitle/releases/download/1.0.0/AuthMeTitle-1.0.0.jar"
$destDir = "servers\lobby\plugins"
$destPath = "$destDir\AuthMeTitle-1.0.0.jar"

if (Test-Path $destPath) {
    Write-Host "AuthMeTitle already installed." -ForegroundColor Gray
    exit 0
}

Write-Host "Downloading AuthMeTitle..." -ForegroundColor Cyan
Invoke-WebRequest -Uri $jarUrl -OutFile $destPath -UseBasicParsing

Write-Host "Installed: $destPath" -ForegroundColor Green
Write-Host "Restart lobby. Edit plugins/AuthMeTitle/config.yml for Russian titles." -ForegroundColor Yellow
