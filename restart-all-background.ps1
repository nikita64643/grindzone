# Restart all Minecraft servers via Node.js (no PowerShell for server processes)
param(
  [string[]]$Versions = @("1.16.5", "1.21.10")
)

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot

$scriptPath = Join-Path $PSScriptRoot "scripts\restart-all-servers.js"
if (-not (Test-Path -LiteralPath $scriptPath)) {
  Write-Error "restart-all-servers.js not found at $scriptPath"
  exit 1
}

$versionArgs = $Versions -join " "
Write-Host "Running: node scripts/restart-all-servers.js $versionArgs"
& node $scriptPath @Versions
