#!/usr/bin/env bash
set -euo pipefail

# Raspadinha - Setup Script (Unix/macOS/Linux)
# Usage: chmod +x scripts/install.sh && ./scripts/install.sh

ROOT_DIR="$(cd "$(dirname "$0")"/.. && pwd)"
cd "$ROOT_DIR"

echo "==> Raspadinha setup starting (Unix)"

# 1) PHP dependencies via Composer
if command -v composer >/dev/null 2>&1; then
  echo "==> Installing PHP dependencies with Composer (prod)"
  composer install --no-interaction --prefer-dist --optimize-autoloader
else
  echo "[WARN] Composer not found. Install from https://getcomposer.org/"
fi

# 2) Frontend dependencies (if any)
if [ -f "frontend/package.json" ]; then
  if command -v npm >/dev/null 2>&1; then
    echo "==> Installing frontend dependencies (npm)"
    (cd frontend && npm install)
  else
    echo "[WARN] npm not found. Install Node.js from https://nodejs.org/"
  fi
fi

# 3) Apply Supabase schema (optional) if psql and DATABASE_URL are present
if command -v psql >/dev/null 2>&1; then
  if [ -n "${DATABASE_URL:-}" ]; then
    if [ -f "database/supabase_schema.sql" ]; then
      echo "==> Applying Supabase schema to DATABASE_URL"
      psql "$DATABASE_URL" -v ON_ERROR_STOP=1 -f database/supabase_schema.sql || {
        echo "[WARN] Failed to apply schema. Check your DATABASE_URL and connectivity.";
      }
    fi
  else
    echo "[INFO] DATABASE_URL not set in environment. Skipping schema apply."
  fi
else
  echo "[INFO] psql not found. Skipping schema apply."
fi

# 4) Summary
cat <<'EOF'

==> Setup finished.
Next steps:
- Copy config/.env.example to config/.env and fill values.
- Start local dev with: npx vercel dev
- Ensure Vercel project variables are set: SUPABASE_URL, SUPABASE_ANON_KEY, DATABASE_URL
EOF
