# Install EssentialsX on all 1.21.x servers and optionally restart them.
# Usage: .\install-essentialsx.ps1
#        .\install-essentialsx.ps1 -SkipDownload   (use existing jar in scripts\cache)
#        .\install-essentialsx.ps1 -NoRestart      (do not restart servers)
param(
    [switch]$SkipDownload,
    [switch]$NoRestart
)

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot

$cacheDir = "scripts\cache"
$jarName = "EssentialsX-2.22.0-dev+59-6ab56d2.jar"
$jarPath = Join-Path $cacheDir $jarName
$downloadUrl = "https://ci.ender.zone/job/EssentialsX/lastSuccessfulBuild/artifact/jars/EssentialsX-2.22.0-dev%2B59-6ab56d2.jar"

if (-not $SkipDownload) {
    if (-not (Test-Path $cacheDir)) { New-Item -ItemType Directory -Path $cacheDir -Force | Out-Null }
    Write-Host "Downloading EssentialsX from ci.ender.zone (may take 1-2 min)..."
    try {
        $ProgressPreference = "SilentlyContinue"
        Invoke-WebRequest -Uri $downloadUrl -OutFile $jarPath -UseBasicParsing -TimeoutSec 300
    }
    catch {
        Write-Host "Download failed. Download manually from:" -ForegroundColor Yellow
        Write-Host "  https://ci.ender.zone/job/EssentialsX/lastSuccessfulBuild/artifact/jars/"
        Write-Host "  Save 'EssentialsX-2.22.0-dev+59-6ab56d2.jar' to $((Resolve-Path $cacheDir).Path)" -ForegroundColor Yellow
        exit 1
    }
    $size = (Get-Item $jarPath).Length
    if ($size -lt 4MB) {
        Write-Host "Downloaded file too small ($size bytes), likely incomplete. Remove it and run again or download manually." -ForegroundColor Red
        exit 1
    }
    Write-Host "Downloaded OK ($([math]::Round($size/1MB, 2)) MB)"
}
else {
    $jars = Get-ChildItem -Path $cacheDir -Filter "EssentialsX*.jar" -ErrorAction SilentlyContinue | Where-Object { $_.Length -gt 10KB }
    if ($jars.Count -eq 0) {
        Write-Host "No valid EssentialsX jars in scripts\cache (need >10KB each)." -ForegroundColor Red
        Write-Host "Download from: https://ci.ender.zone/job/EssentialsX/lastSuccessfulBuild/artifact/jars/" -ForegroundColor Yellow
        exit 1
    }
    Write-Host "Using cached: $($jars.Name -join ', ')"
}

$servers = @("1.21.10\SandBox")

$jarsToInstall = if ($SkipDownload) {
    @(Get-ChildItem -Path $cacheDir -Filter "EssentialsX*.jar" -ErrorAction SilentlyContinue | Where-Object { $_.Length -gt 10KB })
}
else {
    @(Get-Item $jarPath)
}

$ports121 = @(25570)
if (-not $NoRestart) {
    Write-Host "`nStopping 1.21 servers to update plugins..."
    foreach ($port in $ports121) {
        $conn = Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue
        if ($conn) {
            $procId = $conn.OwningProcess
            if ($procId) { Stop-Process -Id $procId -Force -ErrorAction SilentlyContinue; Write-Host "  Stopped port $port (PID $procId)" }
        }
    }
    Start-Sleep -Seconds 3
}

foreach ($s in $servers) {
    $pluginsDir = "servers\$s\plugins"
    if (-not (Test-Path $pluginsDir)) { continue }
    Get-ChildItem -Path $pluginsDir -Filter "EssentialsX*.jar" -ErrorAction SilentlyContinue | Remove-Item -Force
    foreach ($j in $jarsToInstall) {
        Copy-Item -Path $j.FullName -Destination $pluginsDir -Force
    }
    Write-Host "Installed $($jarsToInstall.Count) jar(s): $pluginsDir"
}

if (-not $NoRestart) {
    Write-Host "`nStarting 1.21 servers..."
    $ports = @(
        @{ port = 25570; ver = "1.21.10"; name = "SandBox" }
    )
    foreach ($p in $ports) {
        node scripts\restart-server.js --port $p.port --version $p.ver --name $p.name
    }
    Write-Host "Done. Wait 15-30 sec for servers to start, then /kit, /nick, /home should work."
}
else {
    Write-Host "`nSkipped restart. Restart 1.21.10 server manually (e.g. from admin panel or restart-all-servers.js 1.21.10)."
}
