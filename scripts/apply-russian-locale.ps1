# Apply Russian locale to all server plugins. Run from project root.

param([switch]$Restart)

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot\..

$sourceRu = "servers\lobby\plugins\AuthMe\messages\messages_ru.yml"
$authmeServers = @("lobby", "1.16.5\SandBox", "1.21.10\SandBox")

foreach ($s in $authmeServers) {
    $dst = "servers\$s\plugins\AuthMe\messages\messages_ru.yml"
    $cfg = "servers\$s\plugins\AuthMe\config.yml"
    if (Test-Path $sourceRu) {
        $dir = Split-Path $dst
        if (-not (Test-Path $dir)) { New-Item -ItemType Directory -Path $dir -Force | Out-Null }
        try {
            Copy-Item $sourceRu $dst -Force -ErrorAction Stop
            Write-Host "OK: $dst" -ForegroundColor Green
        } catch {
            Write-Host "Skip (busy): $dst" -ForegroundColor Yellow
        }
    }
    if (Test-Path $cfg) {
        $content = Get-Content $cfg -Raw
        if ($content -match 'messagesLanguage:\s*en\b') {
            $content = $content -replace '(messagesLanguage:\s*)en\b', '${1}ru'
            [System.IO.File]::WriteAllText((Resolve-Path $cfg).Path, $content)
            Write-Host "AuthMe ru: $cfg" -ForegroundColor Green
        }
    }
}

Write-Host ""
Write-Host "Patching AuthMe..." -ForegroundColor Cyan
& "$PSScriptRoot\patch-authme-config.ps1" -AllServers

$essPaths = @("servers\lobby\plugins\Essentials\config.yml", "servers\1.21.10\SandBox\plugins\Essentials\config.yml")
foreach ($p in $essPaths) {
    if (Test-Path $p) {
        $c = Get-Content $p -Raw
        if ($c -match '#locale:\s*en' -or $c -match 'locale:\s*en\b') {
            $c = $c -replace '#locale:\s*en', 'locale: ru' -replace 'locale:\s*en\b', 'locale: ru'
            [System.IO.File]::WriteAllText((Resolve-Path $p).Path, $c)
            Write-Host "Essentials ru: $p" -ForegroundColor Green
        }
    }
}

$citPath = "servers\lobby\plugins\Citizens\config.yml"
if (Test-Path $citPath) {
    $c = Get-Content $citPath -Raw
    if ($c -match "locale:\s*''" -or $c -match "locale:\s*en") {
        $c = $c -replace "locale:\s*''", "locale: 'ru'" -replace "locale:\s*en", "locale: 'ru'"
        [System.IO.File]::WriteAllText((Resolve-Path $citPath).Path, $c)
        Write-Host "Citizens ru: $citPath" -ForegroundColor Green
    }
}

$ctPath = "servers\lobby\plugins\CommandTimer\config.yml"
if (Test-Path $ctPath) {
    $c = Get-Content $ctPath -Raw
    if ($c -match 'language:\s*en') {
        $c = $c -replace 'language:\s*en', 'language: ru'
        [System.IO.File]::WriteAllText((Resolve-Path $ctPath).Path, $c)
        Write-Host "CommandTimer ru: $ctPath" -ForegroundColor Green
    }
}

Write-Host ""
Write-Host "Done. Restart lobby: node scripts\restart-server.js --port 25580 --dir servers\lobby" -ForegroundColor Yellow

if ($Restart) {
    node scripts\restart-server.js --port 25580 --dir servers\lobby
}
