<#
.SYNOPSIS
  Устанавливает Velocity proxy и лобби. Одна точка входа (25565) — игроки попадают в лобби,
  откуда можно перейти на серверы (/server sandbox165, /server sandbox121).
  Позволит потом добавить режимы.
#>
param(
  [switch]$SkipVelocity,
  [switch]$SkipLobby
)

$ErrorActionPreference = "Stop"

$velocityUrl = "https://api.papermc.io/v2/projects/velocity/versions/3.4.0-SNAPSHOT/builds/550/downloads/velocity-3.4.0-SNAPSHOT-550.jar"
$paperUrl = "https://api.papermc.io/v2/projects/paper/versions/1.21.10/builds/113/downloads/paper-1.21.10-113.jar"

$root = $PSScriptRoot
$proxyDir = Join-Path $root "servers\proxy"
$lobbyDir = Join-Path $root "servers\lobby"

function Ensure-Dir([string]$Path) {
  if (-not (Test-Path -LiteralPath $Path)) {
    New-Item -ItemType Directory -Path $Path | Out-Null
  }
}

function Write-TextFile([string]$Path, [string]$Content) {
  $dir = Split-Path -Parent $Path
  if ($dir) { Ensure-Dir $dir }
  [System.IO.File]::WriteAllText($Path, $Content, (New-Object System.Text.UTF8Encoding($false)))
}

$randomBytes = New-Object byte[] 32
[System.Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($randomBytes)
$velocitySecret = [Convert]::ToBase64String($randomBytes)

# --- Velocity ---
if (-not $SkipVelocity) {
  Ensure-Dir $proxyDir

  $velocityJar = Join-Path $proxyDir "velocity.jar"
  if (-not (Test-Path -LiteralPath $velocityJar)) {
    Write-Host "Downloading Velocity..."
    Invoke-WebRequest -Uri $velocityUrl -OutFile $velocityJar -UseBasicParsing
  }

  $velocityToml = Join-Path $proxyDir "velocity.toml"
  $tomlContent = @"
bind = "0.0.0.0:25565"
motd = "§6Minecruft §7— §fЛобби"
show-max-players = 500

player-info-forwarding-mode = "modern"

[servers]
lobby = "127.0.0.1:25580"
sandbox165 = "127.0.0.1:25566"
sandbox121 = "127.0.0.1:25570"
try = [ "lobby" ]

[forced-hosts]

[advanced]
compression-threshold = 256
compression-level = -1
login-ratelimit = 3000
connection-timeout = 5000
read-timeout = 30000
haproxy-protocol = false
tcp-fast-open = false
bungeecord-plugin-message-channel = true
show-ping-requests = false

[query]
enabled = false
port = 25565
map = "Minecruft"
show-plugins = false
"@

  if (-not (Test-Path -LiteralPath $velocityToml)) {
    Write-TextFile $velocityToml $tomlContent
    Write-Host "Created velocity.toml"
  }
  else {
    $existing = Get-Content $velocityToml -Raw
    if ($existing -notmatch "\[servers\]") {
      Add-Content -Path $velocityToml -Value @"

[servers]
lobby = "127.0.0.1:25580"
sandbox165 = "127.0.0.1:25566"
sandbox121 = "127.0.0.1:25570"
try = [ "lobby" ]
"@
    }
  }

  $forwardSecretPath = Join-Path $proxyDir "forwarding.secret"
  Write-TextFile $forwardSecretPath $velocitySecret

  $proxyStart = Join-Path $proxyDir "start.ps1"
  if (-not (Test-Path -LiteralPath $proxyStart)) {
    Write-TextFile $proxyStart @'
param([string]$Xms = "512M", [string]$Xmx = "1G")
$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot
$java = if (Test-Path "..\..\tools\java21\current\bin\java.exe") { "..\..\tools\java21\current\bin\java.exe" } else { "java" }
Write-Host "Starting Velocity proxy (25565)..."
& $java "-Xms$Xms" "-Xmx$Xmx" -XX:+UseG1GC -jar velocity.jar nogui
'@
  }
  Write-Host "Velocity ready: $proxyDir"
}

# --- Lobby ---
if (-not $SkipLobby) {
  Ensure-Dir $lobbyDir

  $paperJar = Join-Path $lobbyDir "server.jar"
  if (-not (Test-Path -LiteralPath $paperJar)) {
    Write-Host "Downloading Paper for lobby..."
    Invoke-WebRequest -Uri $paperUrl -OutFile $paperJar -UseBasicParsing
  }

  $eulaPath = Join-Path $lobbyDir "eula.txt"
  if (-not (Test-Path -LiteralPath $eulaPath)) {
    Write-TextFile $eulaPath "eula=false`n"
  }

  $propsPath = Join-Path $lobbyDir "server.properties"
  if (-not (Test-Path -LiteralPath $propsPath)) {
    Write-TextFile $propsPath @"
server-port=25580
level-name=world_lobby
level-type=minecraft\:flat
generator-settings={"layers":[{"height":1,"block":"grass_block"},{"height":2,"block":"dirt"},{"height":1,"block":"bedrock"}],"biome":"plains","structures":{"structures":{}}}
gamemode=adventure
difficulty=peaceful
spawn-monsters=false
spawn-animals=true
pvp=false
max-players=100
motd=Lobby (Minecruft)
online-mode=false
view-distance=8
simulation-distance=6
enable-rcon=false
force-gamemode=true
"@
  }

  $paperConfigDir = Join-Path $lobbyDir "config"
  Ensure-Dir $paperConfigDir
  $paperGlobalPath = Join-Path $paperConfigDir "paper-global.yml"
  if (-not (Test-Path -LiteralPath $paperGlobalPath)) {
    Write-TextFile $paperGlobalPath @"
proxies:
  proxy-protocol: false
  velocity:
    enabled: true
    online-mode: true
    secret: "$velocitySecret"
"@
  }

  $lobbyStart = Join-Path $lobbyDir "start.ps1"
  if (-not (Test-Path -LiteralPath $lobbyStart)) {
    Write-TextFile $lobbyStart @'
param([string]$Xms = "1G", [string]$Xmx = "2G")
$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot
$java = if (Test-Path "..\..\tools\java21\current\bin\java.exe") { "..\..\tools\java21\current\bin\java.exe" } else { "java" }
if ((Get-Content ".\eula.txt") -notcontains "eula=true") { throw "Set eula=true in eula.txt" }
Write-Host "Starting Lobby (25580)..."
& $java "-Xms$Xms" "-Xmx$Xmx" -jar server.jar nogui
'@
  }
  Write-Host "Lobby ready: $lobbyDir"
}

# --- Настройка бэкендов ---
# 1.16.5: порт 25566, Paper velocity-support
$backend165Dir = Join-Path $root "servers\1.16.5\SandBox"
if (Test-Path $backend165Dir) {
  $props165 = Join-Path $backend165Dir "server.properties"
  if (Test-Path $props165) {
    $c = Get-Content $props165 -Raw
    $c = $c -replace "server-port=\d+", "server-port=25566"
    $c = $c -replace "online-mode=true", "online-mode=false"
    Write-TextFile $props165 $c
  }
  $paperYml165 = Join-Path $backend165Dir "paper.yml"
  if (Test-Path $paperYml165) {
    $y = Get-Content $paperYml165 -Raw
    if ($y -match "velocity-support:") {
      $y = $y -replace "secret:\s*.*", ("secret: " + $velocitySecret)
      Write-TextFile $paperYml165 $y
    }
  }
  Write-Host "Configured 1.16.5 SandBox for Velocity (port 25566)"
}

# 1.21.10: Paper Velocity forwarding
$backendDir = Join-Path $root "servers\1.21.10\SandBox"
$paperConfigDir = Join-Path $backendDir "config"
$paperConfig = Join-Path $paperConfigDir "paper-global.yml"
if (Test-Path $backendDir) {
  $props121 = Join-Path $backendDir "server.properties"
  if (Test-Path $props121) {
    $c = Get-Content $props121 -Raw
    $c = $c -replace "online-mode=true", "online-mode=false"
    Write-TextFile $props121 $c
  }
  if (-not (Test-Path $paperConfig)) {
    Ensure-Dir $paperConfigDir
    Write-TextFile $paperConfig @"
proxies:
  proxy-protocol: false
  velocity:
    enabled: true
    online-mode: true
    secret: "$velocitySecret"
"@
    Write-Host "Configured 1.21.10 SandBox for Velocity"
  }
}

Write-Host ""
Write-Host "Done. Next:"
Write-Host "1. Set eula=true in servers\lobby\eula.txt"
Write-Host "2. Run: node scripts/start-servers.js lobby"
Write-Host "3. Connect: localhost:25565"
Write-Host ""
Write-Host "In lobby: /server sandbox165 (1.16.5) or /server sandbox121 (1.21.10)"
Write-Host "1.21.10 needs Paper. Run install-paper-luckperms.ps1 if using Vanilla."
