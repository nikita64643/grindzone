param(
  [string]$Xms = "1G",
  [string]$Xmx = "2G",
  [string[]]$Versions = @("1.16.5", "1.21.10")
)

$ErrorActionPreference = "Stop"

$serverNames = @("SandBox")

# All versions: servers\<Version>\<Name>
function Get-ServerStartPath([string]$Version, [string]$Name) {
  Join-Path $PSScriptRoot ("servers\" + $Version + "\" + $Name + "\start.ps1")
}

$launched = 0
foreach ($ver in $Versions) {
  foreach ($name in $serverNames) {
    $start = Get-ServerStartPath -Version $ver -Name $name
    if (-not (Test-Path -LiteralPath $start)) { continue }

    $wd = Split-Path -Parent $start
    Start-Process -FilePath "powershell.exe" `
      -WorkingDirectory $wd `
      -ArgumentList @(
      "-NoExit",
      "-ExecutionPolicy", "Bypass",
      "-File", $start,
      "-Xms", $Xms,
      "-Xmx", $Xmx
    )
    $launched++
    Write-Host "Started: $ver / $name"
  }
}

if ($launched -eq 0) {
  Write-Host "No servers found. Run setup-servers.ps1 (1.16.5) and setup-servers-vanilla.ps1 (1.21.10)."
}
else {
  Write-Host "Launched $launched window(s). Set eula=true in each server eula.txt if needed."
}
