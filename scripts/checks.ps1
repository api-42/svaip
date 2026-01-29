# scripts/check.ps1
Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

function RepoRoot {
  try {
    return (git rev-parse --show-toplevel).Trim()
  } catch {
    # fallback: parent of this script folder
    return (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
  }
}

$root = RepoRoot
Set-Location $root

Write-Host "==> Repo: $root"
Write-Host "==> PHP:  $(php -r 'echo PHP_VERSION;')"

# ---- Backend (Laravel) ----

Write-Host "==> Composer: validate"
composer validate --no-interaction --strict

Write-Host "==> Composer: dump-autoload"
composer dump-autoload -o -q

Write-Host "==> Laravel: clear caches"
php artisan optimize:clear

# Pint (format check)
if (Test-Path "vendor\bin\pint") {
  Write-Host "==> Pint: format check"
  php vendor\bin\pint --test
} else {
  Write-Host "==> Pint: skipped (vendor\bin\pint not found)"
}

# PHPStan/Larastan (static analysis)
if (Test-Path "vendor\bin\phpstan") {
  Write-Host "==> PHPStan/Larastan"
  php vendor\bin\phpstan analyse
} else {
  Write-Host "==> PHPStan: skipped (vendor\bin\phpstan not found)"
}

# Tests using sqlite to avoid needing MySQL/Postgres locally/CI
Write-Host "==> Tests (sqlite)"
$env:APP_ENV = "testing"
$env:DB_CONNECTION = "sqlite"

$dbPath = $env:DB_DATABASE
if ([string]::IsNullOrWhiteSpace($dbPath)) {
  $dbPath = Join-Path $root "database\database.sqlite"
  $env:DB_DATABASE = $dbPath
}

if (!(Test-Path (Split-Path $dbPath))) {
  New-Item -ItemType Directory -Force -Path (Split-Path $dbPath) | Out-Null
}
if (!(Test-Path $dbPath)) {
  New-Item -ItemType File -Force -Path $dbPath | Out-Null
}

php artisan test

Write-Host "==> Backend OK"

# ---- Frontend (optional: Vite/React/Alpine) ----
if (Test-Path "package.json") {
  Write-Host "==> Frontend: checks"

  # Read package.json scripts to avoid failing on missing commands
  $pkg = Get-Content -Raw package.json | ConvertFrom-Json
  $scripts = $pkg.scripts

  function HasScript($name) {
    return ($scripts -ne $null) -and ($scripts.PSObject.Properties.Name -contains $name)
  }

  if (HasScript "format:check") { npm run -s format:check }
  if (HasScript "lint")         { npm run -s lint }
  if (HasScript "typecheck")    { npm run -s typecheck }
  if (HasScript "test")         { npm run -s test }
  if (HasScript "build")        { npm run -s build }

  Write-Host "==> Frontend OK"
}

Write-Host "==> All checks passed ✅"
