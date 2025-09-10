# Raspadinha - Setup Script (Windows PowerShell)
# Usage: powershell -ExecutionPolicy Bypass -File .\scripts\install.ps1

$ErrorActionPreference = 'Stop'

# Go to repo root
$PSScriptRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location (Join-Path $PSScriptRoot '..')

Write-Host '==> Raspadinha setup starting (Windows)' -ForegroundColor Green

# 1) PHP dependencies via Composer
if (Get-Command composer -ErrorAction SilentlyContinue) {
  Write-Host '==> Installing PHP dependencies with Composer (prod)' -ForegroundColor Cyan
  composer install --no-interaction --prefer-dist --optimize-autoloader
} else {
  Write-Warning 'Composer not found. Install from https://getcomposer.org/'
}

# 2) Frontend dependencies (if any)
if (Test-Path 'frontend/package.json') {
  if (Get-Command npm -ErrorAction SilentlyContinue) {
    Write-Host '==> Installing frontend dependencies (npm)' -ForegroundColor Cyan
    Push-Location 'frontend'
    npm install
    Pop-Location
  } else {
    Write-Warning 'npm not found. Install Node.js from https://nodejs.org/'
  }
}

# 3) Apply Supabase schema (optional) if psql and DATABASE_URL are present
$databaseUrl = $env:DATABASE_URL
if (Get-Command psql -ErrorAction SilentlyContinue) {
  if ($databaseUrl) {
    if (Test-Path 'database/supabase_schema.sql') {
      Write-Host '==> Applying Supabase schema to DATABASE_URL' -ForegroundColor Cyan
      try {
        & psql $databaseUrl -v ON_ERROR_STOP=1 -f 'database/supabase_schema.sql'
      } catch {
        Write-Warning 'Failed to apply schema. Check your DATABASE_URL and connectivity.'
      }
    }
  } else {
    Write-Host '[INFO] DATABASE_URL not set in environment. Skipping schema apply.'
  }
} else {
  Write-Host '[INFO] psql not found. Skipping schema apply.'
}

Write-Host "`n==> Setup finished." -ForegroundColor Green
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "- Copy config/.env.example to config/.env and fill values."
Write-Host "- Start local dev with: npx vercel dev"
Write-Host "- Ensure Vercel project variables are set: SUPABASE_URL, SUPABASE_ANON_KEY, DATABASE_URL"
