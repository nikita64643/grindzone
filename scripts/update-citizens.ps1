# Update Citizens to latest build (Paper 1.21 fix)
# Run from project root
$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot\..

$pluginsDir = "servers\lobby\plugins"
$oldJar = Get-ChildItem $pluginsDir -Filter "Citizens*.jar" -ErrorAction SilentlyContinue | Select-Object -First 1
$dest = Join-Path $pluginsDir "Citizens-2.0.41-b4126.jar"

$url = "https://ci.citizensnpcs.co/job/Citizens2/lastSuccessfulBuild/artifact/dist/target/Citizens-2.0.41-b4126.jar"
Invoke-WebRequest -Uri $url -OutFile $dest -UseBasicParsing

if ($oldJar -and $oldJar.FullName -ne (Resolve-Path $dest).Path) {
  Remove-Item $oldJar.FullName -Force
}
Write-Host "Updated Citizens. Restart lobby." -ForegroundColor Green
