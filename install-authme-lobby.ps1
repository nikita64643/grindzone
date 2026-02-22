# Install AuthMe + AuthMeVelocity for lobby-first auth flow.
# Player connects -> Velocity -> Lobby. Must /register or /login in lobby.
# Auth state is shared via MySQL. After login, /server sandbox165 or sandbox121 works.
#
# Prereq: CREATE DATABASE authme; (or use existing DB from site/.env)
#         AuthMe reads DB from site/.env: DB_HOST, DB_DATABASE (default authme), DB_USERNAME, DB_PASSWORD
#
# Usage: .\install-authme-lobby.ps1
param(
    [switch]$SkipDownload,
    [switch]$NoRestart
)

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot

$cacheDir = "scripts\cache"
$authMeVersion = "5.6.0"
$authMeVelocityVersion = "4.2.0"

$urls = @{
    AuthMeModern   = "https://github.com/AuthMe/AuthMeReloaded/releases/download/$authMeVersion/AuthMe-$authMeVersion.jar"
    AuthMeLegacy   = "https://github.com/AuthMe/AuthMeReloaded/releases/download/$authMeVersion/AuthMe-$authMeVersion-legacy.jar"
    AuthMeVelocity = "https://cdn.modrinth.com/data/sG6SrXta/versions/GLCUo91X/AuthMeVelocity-Velocity-$authMeVelocityVersion.jar"
    AuthMeVelocityPaper = "https://cdn.modrinth.com/data/sG6SrXta/versions/JbQXH7IC/AuthMeVelocity-Paper-$authMeVelocityVersion.jar"
}

$allServers = @(
    @{ path = "lobby"; jar = "AuthMe-$authMeVersion.jar"; url = $urls.AuthMeModern }
    @{ path = "1.16.5\SandBox"; jar = "AuthMe-$authMeVersion-legacy.jar"; url = $urls.AuthMeLegacy }
    @{ path = "1.21.10\SandBox"; jar = "AuthMe-$authMeVersion.jar"; url = $urls.AuthMeModern }
)

if (-not $SkipDownload) {
    if (-not (Test-Path $cacheDir)) { New-Item -ItemType Directory -Path $cacheDir -Force | Out-Null }
    $toDownload = @()
    foreach ($s in $allServers) {
        $jarPath = Join-Path $cacheDir $s.jar
        if (-not (Test-Path $jarPath) -or (Get-Item $jarPath).Length -lt 1MB) { $toDownload += $s }
    }
    $toDownload = $toDownload | Select-Object -Unique -Property jar, url
    foreach ($s in $toDownload) {
        $jarPath = Join-Path $cacheDir $s.jar
        Write-Host "Downloading $($s.jar)..." -ForegroundColor Cyan
        try {
            $ProgressPreference = "SilentlyContinue"
            Invoke-WebRequest -Uri $s.url -OutFile $jarPath -UseBasicParsing -TimeoutSec 120
            Write-Host "  OK" -ForegroundColor Green
        }
        catch { Write-Host "  Failed. Download manually: $($s.url)" -ForegroundColor Yellow; exit 1 }
    }
    # AuthMeVelocity
    $avProxy = Join-Path $cacheDir "AuthMeVelocity-Velocity-$authMeVelocityVersion.jar"
    $avPaper = Join-Path $cacheDir "AuthMeVelocity-Paper-$authMeVelocityVersion.jar"
    if (-not (Test-Path $avProxy) -or (Get-Item $avProxy).Length -lt 50KB) {
        Write-Host "Downloading AuthMeVelocity Velocity..." -ForegroundColor Cyan
        Invoke-WebRequest -Uri $urls.AuthMeVelocity -OutFile $avProxy -UseBasicParsing -TimeoutSec 60
    }
    if (-not (Test-Path $avPaper) -or (Get-Item $avPaper).Length -lt 50KB) {
        Write-Host "Downloading AuthMeVelocity Paper..." -ForegroundColor Cyan
        Invoke-WebRequest -Uri $urls.AuthMeVelocityPaper -OutFile $avPaper -UseBasicParsing -TimeoutSec 60
    }
}
else {
    Write-Host "Using cached jars" -ForegroundColor Gray
}

# Stop all servers (Velocity, lobby, game)
$allPorts = @(25565, 25580, 25566, 25570)
if (-not $NoRestart) {
    Write-Host "`nStopping Velocity, lobby, game servers..."
    foreach ($port in $allPorts) {
        $conn = Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue
        if ($conn) {
            $procId = $conn.OwningProcess
            if ($procId) { Stop-Process -Id $procId -Force -ErrorAction SilentlyContinue; Write-Host "  Stopped port $port" }
        }
    }
    Start-Sleep -Seconds 4
}

# Install AuthMe on lobby, 1.16.5, 1.21.10
foreach ($s in $allServers) {
    $pluginsDir = "servers\$($s.path)\plugins"
    if (-not (Test-Path $pluginsDir)) { New-Item -ItemType Directory -Path $pluginsDir -Force | Out-Null }
    Get-ChildItem -Path $pluginsDir -Filter "AuthMe*.jar" -ErrorAction SilentlyContinue | Where-Object { $_.Name -notmatch "AuthMeVelocity" } | Remove-Item -Force
    $src = Join-Path $cacheDir $s.jar
    Copy-Item -Path $src -Destination $pluginsDir -Force
    Write-Host "Installed AuthMe -> servers\$($s.path)\plugins"
}

# Install AuthMeVelocity-Paper on lobby, 1.16.5, 1.21.10
$avPaper = Join-Path $cacheDir "AuthMeVelocity-Paper-$authMeVelocityVersion.jar"
foreach ($s in @("lobby", "1.16.5\SandBox", "1.21.10\SandBox")) {
    $pluginsDir = "servers\$s\plugins"
    if (Test-Path $pluginsDir) {
        Get-ChildItem -Path $pluginsDir -Filter "AuthMeVelocity*.jar" -ErrorAction SilentlyContinue | Remove-Item -Force
        Copy-Item -Path $avPaper -Destination $pluginsDir -Force
        Write-Host "Installed AuthMeVelocity-Paper -> servers\$s\plugins"
    }
}

# Install AuthMeVelocity on Velocity proxy
$proxyPlugins = "servers\proxy\plugins"
if (-not (Test-Path $proxyPlugins)) { New-Item -ItemType Directory -Path $proxyPlugins -Force | Out-Null }
$avProxy = Join-Path $cacheDir "AuthMeVelocity-Velocity-$authMeVelocityVersion.jar"
Get-ChildItem -Path $proxyPlugins -Filter "AuthMeVelocity*.jar" -ErrorAction SilentlyContinue | Remove-Item -Force
Copy-Item -Path $avProxy -Destination $proxyPlugins -Force
Write-Host "Installed AuthMeVelocity-Velocity -> servers\proxy\plugins"

if (-not $NoRestart) {
    Write-Host "`nStarting Velocity, lobby, game servers..."
    node scripts\start-servers.js lobby
    Write-Host "`nWaiting 30 sec for AuthMe to create configs..."
    Start-Sleep -Seconds 30
    Write-Host "Patching AuthMe configs (MySQL, sessions, Velocity)..."
    & "$PSScriptRoot\scripts\patch-authme-config.ps1" -AllServers
    Write-Host "Creating AuthMeVelocity config on proxy..."
    & "$PSScriptRoot\scripts\setup-authmevelocity-config.ps1"
    Write-Host "`nRestarting all servers..."
    Start-Sleep -Seconds 3
    foreach ($port in $allPorts) {
        $conn = Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue
        if ($conn) { $procId = $conn.OwningProcess; if ($procId) { Stop-Process -Id $procId -Force -ErrorAction SilentlyContinue } }
    }
    Start-Sleep -Seconds 4
    node scripts\start-servers.js lobby
    Write-Host "`nDone. Auth flow: connect -> lobby -> /login or /register -> /server sandbox165|sandbox121"
}
else {
    Write-Host "`nSkipped restart. Run: node scripts\start-servers.js lobby"
    Write-Host "Then: .\scripts\patch-authme-config.ps1 -AllServers"
    Write-Host "      .\scripts\setup-authmevelocity-config.ps1"
}
