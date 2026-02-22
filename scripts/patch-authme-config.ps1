# Patch AuthMe config.yml: MySQL (shared DB), sessions, Velocity/BungeeCord.
# Run after AuthMe has created config (first server start).
param(
    [switch]$AllServers  # Include lobby, 1.16.5, 1.21.10
)

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot\..

$servers = if ($AllServers) {
    @("lobby", "1.16.5\SandBox", "1.21.10\SandBox")
} else {
    @("1.16.5\SandBox", "1.21.10\SandBox")
}

# Read MySQL from site/.env
$envPath = "site\.env"
$dbHost = "127.0.0.1"
$dbPort = "3306"
$dbName = "authme"
$dbUser = "root"
$dbPass = ""
if (Test-Path $envPath) {
    Get-Content $envPath | ForEach-Object {
        if ($_ -match '^\s*DB_HOST=(.+)$') { $dbHost = $matches[1].Trim() }
        if ($_ -match '^\s*DB_PORT=(.+)$') { $dbPort = $matches[1].Trim() }
        if ($_ -match '^\s*DB_USERNAME=(.+)$') { $dbUser = $matches[1].Trim() }
        if ($_ -match '^\s*DB_PASSWORD=(.+)$') { $dbPass = $matches[1].Trim() }
        if ($_ -match '^\s*AUTHME_DB_HOST=(.+)$') { $dbHost = $matches[1].Trim() }
        if ($_ -match '^\s*AUTHME_DB_PORT=(.+)$') { $dbPort = $matches[1].Trim() }
        if ($_ -match '^\s*AUTHME_DB_DATABASE=(.+)$') { $dbName = $matches[1].Trim() }
        if ($_ -match '^\s*AUTHME_DB_USERNAME=(.+)$') { $dbUser = $matches[1].Trim() }
        if ($_ -match '^\s*AUTHME_DB_PASSWORD=(.+)$') { $dbPass = $matches[1].Trim() }
    }
}
if (-not $dbName -or $dbName -eq "servers") { $dbName = "authme" }

foreach ($s in $servers) {
    $configPath = "servers\$s\plugins\AuthMe\config.yml"
    if (-not (Test-Path $configPath)) {
        Write-Host "Config not found: $configPath" -ForegroundColor Yellow
        continue
    }

    $content = Get-Content $configPath -Raw
    $changed = $false

    # MySQL backend (shared DB across lobby + game servers)
    if ($content -match 'backend:\s*SQLITE') {
        $content = $content -replace '(backend:\s*)SQLITE', '${1}MYSQL'
        $content = $content -replace 'mySQLHost:\s*[\S]+', "mySQLHost: $dbHost"
        $content = $content -replace "mySQLPort:\s*'[^']*'", "mySQLPort: '$dbPort'"
        $content = $content -replace 'mySQLDatabase:\s*[\S]+', "mySQLDatabase: $dbName"
        $content = $content -replace 'mySQLUsername:\s*[\S]+', "mySQLUsername: $dbUser"
        $escPass = ($dbPass -replace "'", "''")
        $content = $content -replace "mySQLPassword:\s*'[^']*'", "mySQLPassword: '$escPass'"
        $content = $content -replace 'mySQLUseSSL:\s*true', 'mySQLUseSSL: false'
        $changed = $true
    }

    # sessions.enabled: false -> true
    if ($content -match 'sessions:' -and $content -match 'enabled:\s*false') {
        $content = $content -replace '(\s+enabled:\s*)false(\r?\n\s+# After how many minutes)', '${1}true${2}'
        $changed = $true
    }

    # sessionExpireOnIpChange: true -> false (save session when login from new IP)
    if ($content -match 'sessionExpireOnIpChange:\s*true') {
        $content = $content -replace '(sessionExpireOnIpChange:\s*)true', '${1}false'
        $changed = $true
    }

    # sessions timeout 10 -> 480 min
    if ($content -match 'timeout:\s*10\s*#' -or $content -match '(\s+timeout:\s*)10(\s*#)') {
        $content = $content -replace '(\s+timeout:\s*)10(\s*#.*session)', '${1}480${2}'
        $changed = $true
    }

    # velocitySupport / bungeecord
    if ($content -match 'velocitySupport:\s*false') {
        $content = $content -replace '(velocitySupport:\s*)false', '${1}true'
        $changed = $true
    }
    if ($content -match 'bungeecordServer:\s*false') {
        $content = $content -replace '(bungeecordServer:\s*)false', '${1}true'
        $changed = $true
    }
    if ($content -match 'bungeecord:\s*false') {
        $content = $content -replace '(bungeecord:\s*)false', '${1}true'
        $changed = $true
    }

    # restrictions.timeout (login/register) 30 -> 120 sec
    if ($content -match 'be kicked\? Set to 0 to disable' -and $content -match 'timeout:\s*30' -and $content -notmatch 'timeout:\s*120') {
        $content = $content -replace '(# be kicked\? Set to 0 to disable\.\r?\n\s+timeout:\s*)30', '${1}120'
        $changed = $true
    }

    # messagesLanguage: en -> ru (Russian)
    if ($content -match 'messagesLanguage:\s*en\b') {
        $content = $content -replace '(messagesLanguage:\s*)en\b', '${1}ru'
        $changed = $true
    }

    # serverName for welcome message
    if ($content -match "serverName:\s*Your Minecraft Server") {
        $content = $content -replace "serverName:\s*Your Minecraft Server", "serverName: GrindZone"
        $changed = $true
    }

    # applyBlindEffect: false -> true (dark screen for unauthenticated)
    if ($content -match 'applyBlindEffect:\s*false') {
        $content = $content -replace '(applyBlindEffect:\s*)false', '${1}true'
        $changed = $true
    }

    if ($changed) {
        [System.IO.File]::WriteAllText((Resolve-Path $configPath).Path, $content)
        Write-Host "Patched: $configPath" -ForegroundColor Green
    }
    else {
        Write-Host "No changes needed: $configPath" -ForegroundColor Gray
    }
}
