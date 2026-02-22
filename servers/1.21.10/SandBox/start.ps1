param(
  [string]$Xms = "1G",
  [string]$Xmx = "2G"
)

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot

# Vanilla 1.21.x requires Java 21; use system java
$java = "java"

if (-not (Test-Path -LiteralPath ".\eula.txt")) {
  throw "eula.txt not found. Run setup-servers-vanilla.ps1 first."
}

$eula = Get-Content -LiteralPath ".\eula.txt" -ErrorAction Stop
if ($eula -notcontains "eula=true") {
  throw "You must accept the EULA: open .\eula.txt and set eula=true"
}

if (-not (Test-Path -LiteralPath ".\server.jar")) {
  throw "server.jar not found. Run setup-servers-vanilla.ps1 -Version 1.21.10 or 1.21.11."
}

Write-Host ("Starting Paper server (server.jar)  RAM: Xms={0} Xmx={1}" -f $Xms, $Xmx)
& $java ("-Xms{0}" -f $Xms) ("-Xmx{0}" -f $Xmx) -jar server.jar nogui