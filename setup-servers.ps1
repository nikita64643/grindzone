<#
.SYNOPSIS
  Установка Paper сервера 1.16.5 (SandBox) в servers\1.16.5\SandBox

.NOTES
  - Использует локальную Java 8 в tools\java8\current (скачает, если нет)
  - Скачивает Paper 1.16.5 build 794 как server.jar
  - Если найден servers\proxy\forwarding.secret — пропишет его в paper.yml (velocity-support)
#>
param(
  [int]$Port = 25566,
  [string]$PaperBuild = "794"
)

$ErrorActionPreference = "Stop"

function Ensure-Dir([string]$Path) {
  if (-not (Test-Path -LiteralPath $Path)) {
    New-Item -ItemType Directory -Path $Path | Out-Null
  }
}

function Write-TextFile([string]$Path, [string]$Content) {
  $dir = Split-Path -Parent $Path
  if ($dir) { Ensure-Dir $dir }
  # UTF-8 with BOM (Windows PowerShell friendly)
  [System.IO.File]::WriteAllText($Path, $Content, (New-Object System.Text.UTF8Encoding($true)))
}

function Ensure-LocalJava8([string]$RootDir) {
  $javaRoot = Join-Path $RootDir "tools\\java8"
  $javaCurrent = Join-Path $javaRoot "current"
  $javaExe = Join-Path $javaCurrent "bin\\java.exe"

  if (Test-Path -LiteralPath $javaExe) {
    return (Resolve-Path -LiteralPath $javaExe).Path
  }

  Ensure-Dir $javaRoot

  $zipPath = Join-Path $javaRoot "temurin8-jre.zip"
  $extractDir = Join-Path $javaRoot "_extract"
  $downloadUrl = "https://api.adoptium.net/v3/binary/latest/8/ga/windows/x64/jre/hotspot/normal/eclipse"

  Write-Host "Downloading local Java 8 JRE (Eclipse Temurin)..."
  Write-Host "URL: $downloadUrl"

  if (Test-Path -LiteralPath $extractDir) { Remove-Item -Recurse -Force -LiteralPath $extractDir }
  Ensure-Dir $extractDir

  Invoke-WebRequest -Uri $downloadUrl -OutFile $zipPath
  Expand-Archive -LiteralPath $zipPath -DestinationPath $extractDir -Force

  $top = Get-ChildItem -LiteralPath $extractDir -Directory | Select-Object -First 1
  if (-not $top) { throw "Failed to extract Java archive (no top-level folder found)." }

  if (Test-Path -LiteralPath $javaCurrent) { Remove-Item -Recurse -Force -LiteralPath $javaCurrent }
  Move-Item -LiteralPath $top.FullName -Destination $javaCurrent
  Remove-Item -Recurse -Force -LiteralPath $extractDir

  if (-not (Test-Path -LiteralPath $javaExe)) {
    throw "Local Java was downloaded, but java.exe was not found at: $javaExe"
  }

  return (Resolve-Path -LiteralPath $javaExe).Path
}

$root = $PSScriptRoot
$serverDir = Join-Path $root "servers\\1.16.5\\SandBox"
Ensure-Dir $serverDir

$paperUrl = "https://api.papermc.io/v2/projects/paper/versions/1.16.5/builds/$PaperBuild/downloads/paper-1.16.5-$PaperBuild.jar"
$paperJar = Join-Path $serverDir "server.jar"

if (-not (Test-Path -LiteralPath $paperJar)) {
  Write-Host "Downloading Paper 1.16.5 build $PaperBuild..."
  Write-Host "URL: $paperUrl"
  Invoke-WebRequest -Uri $paperUrl -OutFile $paperJar -UseBasicParsing
}
else {
  Write-Host "Paper jar already present: $paperJar"
}

$eulaPath = Join-Path $serverDir "eula.txt"
if (-not (Test-Path -LiteralPath $eulaPath)) {
  Write-TextFile $eulaPath @"
# By changing the setting below to TRUE you are indicating your agreement to the Minecraft EULA (https://aka.ms/MinecraftEULA).
eula=false
"@
}

$propsPath = Join-Path $serverDir "server.properties"
if (-not (Test-Path -LiteralPath $propsPath)) {
  Write-TextFile $propsPath @"
# Minecraft server properties
online-mode=false
server-port=$Port
motd=SandBox (1.16.5)
level-name=world_sandbox
gamemode=survival
difficulty=normal
max-players=100
enable-rcon=false
enable-query=false
"@
}

$secretPath = Join-Path $root "servers\\proxy\\forwarding.secret"
$secret = $null
if (Test-Path -LiteralPath $secretPath) {
  $secret = (Get-Content -LiteralPath $secretPath -Raw).Trim()
}

$paperYmlPath = Join-Path $serverDir "paper.yml"
if (-not (Test-Path -LiteralPath $paperYmlPath)) {
  $secretLine = if ($secret) { $secret } else { "CHANGE_ME" }
  Write-TextFile $paperYmlPath @"
settings:
  velocity-support:
    enabled: true
    online-mode: true
    secret: $secretLine
"@
}

$java = Ensure-LocalJava8 -RootDir $root
$startPath = Join-Path $serverDir "start.ps1"
if (-not (Test-Path -LiteralPath $startPath)) {
  Write-TextFile $startPath @'
param(
  [string]$Xms = "1G",
  [string]$Xmx = "2G"
)

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot

$localJava = Resolve-Path -LiteralPath (Join-Path $PSScriptRoot "..\..\..\tools\java8\current\bin\java.exe") -ErrorAction SilentlyContinue
$java = if ($localJava) { $localJava.Path } else { "java" }

if ((Get-Content ".\eula.txt") -notcontains "eula=true") { throw "Set eula=true in eula.txt" }
Write-Host "Starting SandBox 1.16.5 (25566)..."
& $java ("-Xms{0}" -f $Xms) ("-Xmx{0}" -f $Xmx) -jar server.jar nogui
'@
}

Write-Host ""
Write-Host "Done."
Write-Host "Next: set eula=true in servers\\1.16.5\\SandBox\\eula.txt (and lobby), then start via:"
Write-Host "  node scripts/start-servers.js lobby"

