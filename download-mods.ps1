param(
  [Parameter(Mandatory = $true)]
  [ValidateSet("SandBox", "NanoTech-Legacy", "TechnoMagic", "TechnoMagicSky", "Magic")]
  [string]$Server,

  [string]$ListPath,

  [int]$Retries = 4
)

$ErrorActionPreference = "Stop"

function Ensure-Dir([string]$Path) {
  if (-not (Test-Path -LiteralPath $Path)) {
    New-Item -ItemType Directory -Path $Path | Out-Null
  }
}

function Download-File([string]$Url, [string]$OutFile, [int]$Retries) {
  for ($i = 1; $i -le $Retries; $i++) {
    try {
      Write-Host ("Downloading ({0}/{1}): {2}" -f $i, $Retries, $Url)
      Invoke-WebRequest -Uri $Url -OutFile $OutFile -UseBasicParsing
      return
    }
    catch {
      if ($i -eq $Retries) { throw }
      Start-Sleep -Seconds ([Math]::Min(20, 2 * $i))
    }
  }
}

function Resolve-DownloadUrl([string]$Url) {
  # Accept:
  # - Direct .jar/.zip URLs
  # - CurseForge download links like:
  #   https://www.curseforge.com/minecraft/mc-mods/<slug>/download/<fileId>
  #
  # We resolve redirects to edge.forgecdn.net so we can get the real filename.
  $u = $Url.Trim()
  if (-not $u) { throw "Empty URL" }

  $lower = $u.ToLowerInvariant()
  if ($lower.EndsWith(".jar") -or $lower.EndsWith(".zip")) {
    return $u
  }

  try {
    $resp = Invoke-WebRequest -Uri $u -Method Head -MaximumRedirection 10 -UseBasicParsing
    $final = $resp.BaseResponse.ResponseUri.AbsoluteUri
    if (-not $final) { throw "Could not resolve redirects for: $u" }

    $finalLower = $final.ToLowerInvariant()
    if ($finalLower.EndsWith(".jar") -or $finalLower.EndsWith(".zip")) {
      return $final
    }

    throw "Resolved URL does not look like a file (.jar/.zip): $final"
  }
  catch {
    throw "Failed to resolve URL (need direct file or CurseForge /download/<id> link): $u`n$($_.Exception.Message)"
  }
}

function Parse-List([string]$Path) {
  if (-not (Test-Path -LiteralPath $Path)) {
    throw "List file not found: $Path"
  }

  $items = @()
  $lines = Get-Content -LiteralPath $Path -ErrorAction Stop
  foreach ($lineRaw in $lines) {
    $line = $lineRaw.Trim()
    if (-not $line) { continue }
    if ($line.StartsWith("#")) { continue }

    $parts = $line -split "\s*\|\s*", 2
    if ($parts.Count -ne 2) {
      throw "Bad line (expected 'Name | URL'): $lineRaw"
    }

    $name = $parts[0].Trim()
    $url = $parts[1].Trim()
    if (-not $name) { throw "Missing name in line: $lineRaw" }
    if (-not $url) { throw "Missing URL in line: $lineRaw" }

    $items += [pscustomobject]@{ Name = $name; Url = $url }
  }
  return $items
}

$root = $PSScriptRoot
$serverDir = Join-Path $root ("servers\" + $Server)
$modsDir = Join-Path $serverDir "mods"
if (-not (Test-Path -LiteralPath $modsDir)) {
  throw "Mods directory not found: $modsDir (did you run setup-servers.ps1?)"
}

if (-not $ListPath) {
  $ListPath = Join-Path $serverDir "mods-urls.txt"
}
if (-not (Test-Path -LiteralPath $ListPath)) {
  throw "mods-urls.txt not found. Fill it with direct links first: $ListPath"
}

$downloadDir = Join-Path $modsDir "_downloads"
$extractDir = Join-Path $modsDir "_extract"
Ensure-Dir $downloadDir

$items = Parse-List -Path $ListPath
if ($items.Count -eq 0) {
  throw "No items in list: $ListPath"
}

foreach ($it in $items) {
  $url = Resolve-DownloadUrl -Url $it.Url
  $name = $it.Name

  $uri = [Uri]$url
  $fileName = [IO.Path]::GetFileName($uri.AbsolutePath)
  if (-not $fileName) {
    throw "URL must end with a filename (.jar/.zip): $url"
  }

  $out = Join-Path $downloadDir $fileName
  Download-File -Url $url -OutFile $out -Retries $Retries

  $ext = [IO.Path]::GetExtension($out).ToLowerInvariant()
  if ($ext -eq ".jar") {
    Copy-Item -LiteralPath $out -Destination (Join-Path $modsDir $fileName) -Force
    Write-Host ("Installed: {0}" -f $name)
  }
  elseif ($ext -eq ".zip") {
    if (Test-Path -LiteralPath $extractDir) { Remove-Item -Recurse -Force -LiteralPath $extractDir }
    Ensure-Dir $extractDir
    Expand-Archive -LiteralPath $out -DestinationPath $extractDir -Force
    $jars = Get-ChildItem -LiteralPath $extractDir -Recurse -File -Filter "*.jar"
    if (-not $jars) {
      throw "ZIP has no .jar files: $fileName"
    }
    foreach ($j in $jars) {
      Copy-Item -LiteralPath $j.FullName -Destination (Join-Path $modsDir $j.Name) -Force
    }
    Remove-Item -Recurse -Force -LiteralPath $extractDir
    Write-Host ("Installed from zip: {0}" -f $name)
  }
  else {
    throw "Unsupported file type '$ext' for: $fileName (URL: $url)"
  }
}

Write-Host ""
Write-Host "Done. Mods are in: $modsDir"
Write-Host "Next: run the server start script, e.g.: .\\servers\\$Server\\start.ps1"

