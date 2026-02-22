# Download Eclipse Temurin 21 JRE to tools\java21\current for 1.21.x Minecraft servers.
# Run from project root: .\setup-java21.ps1

$ErrorActionPreference = "Stop"

function Ensure-Dir([string]$Path) {
  if (-not (Test-Path -LiteralPath $Path)) {
    New-Item -ItemType Directory -Path $Path | Out-Null
  }
}

$root = $PSScriptRoot
$javaRoot = Join-Path $root "tools\java21"
$javaCurrent = Join-Path $javaRoot "current"
$javaExe = Join-Path $javaCurrent "bin\java.exe"

if (Test-Path -LiteralPath $javaExe) {
  Write-Host "Java 21 already present: $javaExe"
  & $javaExe -version
  exit 0
}

Ensure-Dir $javaRoot
$zipPath = Join-Path $javaRoot "temurin21-jre.zip"
$extractDir = Join-Path $javaRoot "_extract"
$downloadUrl = "https://api.adoptium.net/v3/binary/latest/21/ga/windows/x64/jre/hotspot/normal/eclipse"

Write-Host "Downloading Eclipse Temurin 21 JRE for 1.21.x servers..."
Write-Host "URL: $downloadUrl"

if (Test-Path -LiteralPath $extractDir) { Remove-Item -Recurse -Force -LiteralPath $extractDir }
Ensure-Dir $extractDir

Invoke-WebRequest -Uri $downloadUrl -OutFile $zipPath -UseBasicParsing
Expand-Archive -LiteralPath $zipPath -DestinationPath $extractDir -Force

$top = Get-ChildItem -LiteralPath $extractDir -Directory | Select-Object -First 1
if (-not $top) { throw "Failed to extract Java archive (no top-level folder found)." }

if (Test-Path -LiteralPath $javaCurrent) { Remove-Item -Recurse -Force -LiteralPath $javaCurrent }
Move-Item -LiteralPath $top.FullName -Destination $javaCurrent
Remove-Item -Recurse -Force -LiteralPath $extractDir

if (-not (Test-Path -LiteralPath $javaExe)) {
  throw "Java 21 was downloaded, but java.exe was not found at: $javaExe"
}

Write-Host "Java 21 installed: $javaExe"
& $javaExe -version
