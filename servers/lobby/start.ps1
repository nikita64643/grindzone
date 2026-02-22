param([string]$Xms = "1G", [string]$Xmx = "2G")
$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot
$java = if (Test-Path "..\..\tools\java21\current\bin\java.exe") { "..\..\tools\java21\current\bin\java.exe" } else { "java" }
if ((Get-Content ".\eula.txt") -notcontains "eula=true") { throw "Set eula=true in eula.txt" }
Write-Host "Starting Lobby (25580)..."
& $java "-Xms$Xms" "-Xmx$Xmx" -jar server.jar nogui