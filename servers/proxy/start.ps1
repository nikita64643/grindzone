param([string]$Xms = "512M", [string]$Xmx = "1G")
$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot
$java = if (Test-Path "..\..\tools\java21\current\bin\java.exe") { "..\..\tools\java21\current\bin\java.exe" } else { "java" }
Write-Host "Starting Velocity proxy (25565)..."
& $java "-Xms$Xms" "-Xmx$Xmx" -XX:+UseG1GC -jar velocity.jar nogui