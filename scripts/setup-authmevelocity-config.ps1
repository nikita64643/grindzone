# Create/patch AuthMeVelocity config on Velocity proxy.
# auth-servers = lobby (where AuthMe runs, players must login there first)
param()

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot\..

$possiblePaths = @(
    "servers\proxy\plugins\authmevelocity\config.toml",
    "servers\proxy\plugins\AuthMeVelocity\config.toml",
    "servers\proxy\plugins\authmevelocity\config.conf"
)
$configPath = $null
foreach ($p in $possiblePaths) {
    if (Test-Path $p) { $configPath = $p; break }
}
if (-not $configPath) {
    $dir = "servers\proxy\plugins\authmevelocity"
    if (-not (Test-Path $dir)) { New-Item -ItemType Directory -Path $dir -Force | Out-Null }
    $configPath = Join-Path $dir "config.toml"
}

$content = @"
# AuthMeVelocity - auth servers (where AuthMe runs). Players must /login in lobby first.
auth-servers = ["lobby"]

[SendOnLogin]
  send-to-server-on-login = false
"@

if (Test-Path $configPath) {
    $existing = Get-Content $configPath -Raw
    if ($existing -notmatch 'auth-servers\s*=\s*\[\s*"lobby"') {
        $existing = $existing -replace 'auth-servers\s*=\s*\[[^\]]*\]', 'auth-servers = ["lobby"]'
        [System.IO.File]::WriteAllText((Resolve-Path $configPath).Path, $existing)
        Write-Host "Patched AuthMeVelocity: auth-servers = [lobby]" -ForegroundColor Green
    }
    else { Write-Host "AuthMeVelocity config OK" -ForegroundColor Gray }
}
else {
    $dir = Split-Path $configPath -Parent
    $outPath = Join-Path $dir "config.toml"
    [System.IO.File]::WriteAllText($outPath, $content)
    Write-Host "Created AuthMeVelocity config at $outPath" -ForegroundColor Green
}
