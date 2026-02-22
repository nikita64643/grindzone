# Install Citizens 2.0.38 for Paper 1.20.4 (lobby)
# Run from project root
$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot\..

$pluginsDir = "servers\lobby\plugins"
$dest = Join-Path $pluginsDir "Citizens-2.0.38-b3800.jar"

# Remove any existing Citizens
Get-ChildItem $pluginsDir -Filter "Citizens*.jar" -ErrorAction SilentlyContinue | Remove-Item -Force

$url = "https://ci.citizensnpcs.co/job/Citizens2/3800/artifact/dist/target/Citizens-2.0.38-b3800.jar"
Invoke-WebRequest -Uri $url -OutFile $dest -UseBasicParsing
Write-Host "Installed Citizens 2.0.38 for 1.20.4. Restart lobby." -ForegroundColor Green
