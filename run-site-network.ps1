# Start site for network access
# From another PC open: http://<IP>:<PORT>

$ErrorActionPreference = "Stop"
$siteDir = Join-Path $PSScriptRoot "site"

# Get local IP
try {
    $ip = (Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue | Where-Object {
            $_.InterfaceAlias -notlike "*Loopback*" -and
            $_.IPAddress -notlike "127.*" -and
            $_.IPAddress -notlike "169.*"
        } | Select-Object -First 1).IPAddress
}
catch { $ip = $null }

if (-not $ip -or $ip -notmatch "^\d+\.\d+\.\d+\.\d+$") {
    $ipLine = ipconfig | Select-String "IPv4" | Select-Object -First 1
    if ($ipLine -match "(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})" -and $matches[1] -ne "127.0.0.1") {
        $ip = $matches[1]
    }
    else {
        Write-Host "ERROR: Could not get LAN IP. Run ipconfig and check." -ForegroundColor Red
        pause
        exit 1
    }
}

# Find free port (49200-49300)
$port = $null
foreach ($p in 49200..49300) {
    $inUse = netstat -an 2>$null | Select-String ":$p\s"
    if (-not $inUse) { $port = $p; break }
}
if (-not $port) { $port = 49200 }

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Minecruft - network access" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "This PC:     " -NoNewline
Write-Host ("http://" + $ip + ":" + $port) -ForegroundColor Green
Write-Host ""
Write-Host "Other PC - open:" -ForegroundColor Yellow
Write-Host ("  http://" + $ip + ":" + $port) -ForegroundColor Green
Write-Host ""
Write-Host "If blocked: Run as Administrator!" -ForegroundColor DarkYellow
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

Set-Location $siteDir
php artisan serve --host=0.0.0.0 --port=$port
