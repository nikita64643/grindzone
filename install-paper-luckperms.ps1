# Install Paper + LuckPerms for 1.21.x servers (replaces Vanilla server.jar with Paper)
# Usage: .\install-paper-luckperms.ps1
#        .\install-paper-luckperms.ps1 -Version "1.21.10"
#        .\install-paper-luckperms.ps1 -Version "1.21.10","1.21.11"
param(
  [string[]]$Version = @("1.21.10")
)

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot

$scriptPath = Join-Path $PSScriptRoot "scripts\install-paper-luckperms.js"
if (-not (Test-Path -LiteralPath $scriptPath)) {
  Write-Error "install-paper-luckperms.js not found at $scriptPath"
  exit 1
}

& node $scriptPath @Version
