# Install Spigot + LuckPerms for 1.21.x servers (replaces Vanilla/Paper server.jar with Spigot)
# Requires Java 21 and BuildTools.jar in scripts\spigot-build\
# Usage: .\install-spigot-luckperms.ps1
#        .\install-spigot-luckperms.ps1 -Version "1.21.10"
param(
  [string[]]$Version = @("1.21.10")
)

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot

$scriptPath = Join-Path $PSScriptRoot "scripts\install-spigot-luckperms.js"
if (-not (Test-Path -LiteralPath $scriptPath)) {
  Write-Error "install-spigot-luckperms.js not found at $scriptPath"
  exit 1
}

& node $scriptPath @Version
