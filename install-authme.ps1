# Install AuthMe Reloaded on 1.16.5 and 1.21.10 SandBox servers.
# AuthMe provides /register and /login on the server. Passwords are independent from the website.
# Sessions: when a player logs in correctly from a new IP, the session is saved (no re-login needed next time).
#
# Usage: .\install-authme.ps1
#        .\install-authme.ps1 -SkipDownload   (use existing jars in scripts\cache)
#        .\install-authme.ps1 -NoRestart      (do not restart servers)
param(
    [switch]$SkipDownload,
    [switch]$NoRestart
)

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot

$cacheDir = "scripts\cache"
$authMeVersion = "5.6.0"
$urlModern = "https://github.com/AuthMe/AuthMeReloaded/releases/download/$authMeVersion/AuthMe-$authMeVersion.jar"
$urlLegacy = "https://github.com/AuthMe/AuthMeReloaded/releases/download/$authMeVersion/AuthMe-$authMeVersion-legacy.jar"

$servers = @(
    @{ path = "1.16.5\SandBox"; jar = "AuthMe-$authMeVersion-legacy.jar"; url = $urlLegacy }
    @{ path = "1.21.10\SandBox"; jar = "AuthMe-$authMeVersion.jar"; url = $urlModern }
)

if (-not $SkipDownload) {
    if (-not (Test-Path $cacheDir)) { New-Item -ItemType Directory -Path $cacheDir -Force | Out-Null }
    foreach ($s in $servers) {
        $jarPath = Join-Path $cacheDir $s.jar
        if (-not (Test-Path $jarPath) -or (Get-Item $jarPath).Length -lt 1MB) {
            Write-Host "Downloading $($s.jar)..." -ForegroundColor Cyan
            try {
                $ProgressPreference = "SilentlyContinue"
                Invoke-WebRequest -Uri $s.url -OutFile $jarPath -UseBasicParsing -TimeoutSec 120
            }
            catch {
                Write-Host "Download failed. Download manually:" -ForegroundColor Yellow
                Write-Host "  $($s.url)" -ForegroundColor Yellow
                Write-Host "  Save to $((Resolve-Path $cacheDir).Path)" -ForegroundColor Yellow
                exit 1
            }
            $size = (Get-Item $jarPath).Length
            Write-Host "  OK ($([math]::Round($size/1MB, 2)) MB)"
        }
    }
}
else {
    foreach ($s in $servers) {
        $jarPath = Join-Path $cacheDir $s.jar
        if (-not (Test-Path $jarPath) -or (Get-Item $jarPath).Length -lt 1MB) {
            Write-Host "Missing $($s.jar) in scripts\cache. Run without -SkipDownload or download manually." -ForegroundColor Red
            exit 1
        }
    }
    Write-Host "Using cached AuthMe jars"
}

$ports = @(
    @{ port = 25566; ver = "1.16.5"; name = "SandBox" }
    @{ port = 25570; ver = "1.21.10"; name = "SandBox" }
)

if (-not $NoRestart) {
    Write-Host "`nStopping game servers..."
    foreach ($p in $ports) {
        $conn = Get-NetTCPConnection -LocalPort $p.port -State Listen -ErrorAction SilentlyContinue
        if ($conn) {
            $procId = $conn.OwningProcess
            if ($procId) { Stop-Process -Id $procId -Force -ErrorAction SilentlyContinue; Write-Host "  Stopped port $($p.port)" }
        }
    }
    Start-Sleep -Seconds 3
}

foreach ($s in $servers) {
    $pluginsDir = "servers\$($s.path)\plugins"
    if (-not (Test-Path $pluginsDir)) {
        New-Item -ItemType Directory -Path $pluginsDir -Force | Out-Null
    }
    Get-ChildItem -Path $pluginsDir -Filter "AuthMe*.jar" -ErrorAction SilentlyContinue | Remove-Item -Force
    $src = Join-Path $cacheDir $s.jar
    Copy-Item -Path $src -Destination $pluginsDir -Force
    Write-Host "Installed $($s.jar) -> $pluginsDir"
}

if (-not $NoRestart) {
    Write-Host "`nStarting game servers..."
    foreach ($p in $ports) {
        node scripts\restart-server.js --port $p.port --version $p.ver --name $p.name
    }
    Write-Host "`nWaiting 20 sec for AuthMe to create config..."
    Start-Sleep -Seconds 20
    Write-Host "Patching AuthMe config (sessions, Velocity)..."
    & "$PSScriptRoot\scripts\patch-authme-config.ps1"
    Write-Host "`nRestarting servers to apply config..."
    Start-Sleep -Seconds 2
    foreach ($p in $ports) {
        $conn = Get-NetTCPConnection -LocalPort $p.port -State Listen -ErrorAction SilentlyContinue
        if ($conn) { $procId = $conn.OwningProcess; if ($procId) { Stop-Process -Id $procId -Force -ErrorAction SilentlyContinue } }
    }
    Start-Sleep -Seconds 3
    foreach ($p in $ports) {
        node scripts\restart-server.js --port $p.port --version $p.ver --name $p.name
    }
    Write-Host "`nDone. AuthMe: /register, /login; sessions saved on new IP; Velocity enabled."
}
else {
    Write-Host "`nSkipped restart. Restart game servers manually (e.g. node scripts\restart-game-servers.js)."
}
