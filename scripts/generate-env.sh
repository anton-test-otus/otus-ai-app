#!/usr/bin/env bash
# Генерирует backend/.env и frontend/.env из корневого .env (или .env.example).
# backend — все переменные, кроме VITE_*; frontend — только VITE_*.
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

SOURCE="$ROOT_DIR/.env"
if [[ ! -f "$SOURCE" ]]; then
  SOURCE="$ROOT_DIR/.env.example"
  echo "⚠️  $ROOT_DIR/.env не найден — используется .env.example" >&2
fi

merge_env() {
  local skeleton="$1"
  local output="$2"
  local mode="$3" # backend | frontend

  local overrides_file
  overrides_file="$(mktemp)"

  while IFS= read -r line || [[ -n "$line" ]]; do
    [[ "$line" =~ ^[[:space:]]*# ]] && continue
    [[ "$line" =~ ^[[:space:]]*$ ]] && continue
    [[ "$line" =~ ^[A-Za-z_][A-Za-z0-9_]*= ]] || continue

    local key="${line%%=*}"
    local value="${line#*=}"

    if [[ "$mode" == "frontend" ]]; then
      [[ "$key" == VITE_* ]] || continue
    else
      [[ "$key" == VITE_* ]] && continue
    fi

    printf '%s=%s\n' "$key" "$value" >> "$overrides_file"
  done < "$SOURCE"

  if [[ "$mode" == "frontend" ]] && ! grep -q '^VITE_AUTH_ENABLED=' "$overrides_file" 2>/dev/null; then
    if grep -q '^APP_AUTH_ENABLED=' "$SOURCE"; then
      local app_auth
      app_auth="$(grep -E '^APP_AUTH_ENABLED=' "$SOURCE" | head -1 | cut -d= -f2-)"
      printf 'VITE_AUTH_ENABLED=%s\n' "$app_auth" >> "$overrides_file"
    fi
  fi

  awk -v overrides_file="$overrides_file" '
  BEGIN {
    while ((getline line < overrides_file) > 0) {
      eq = index(line, "=")
      if (eq == 0) continue
      key = substr(line, 1, eq - 1)
      val = substr(line, eq + 1)
      overrides[key] = val
    }
    close(overrides_file)
  }
  {
    if ($0 ~ /^[A-Za-z_][A-Za-z0-9_]*=/) {
      eq = index($0, "=")
      key = substr($0, 1, eq - 1)
      if (key in overrides) {
        print key "=" overrides[key]
        delete overrides[key]
        next
      }
    }
    print
  }
  END {
    for (k in overrides) {
      print k "=" overrides[k]
    }
  }
  ' "$skeleton" > "$output"

  rm -f "$overrides_file"
}

merge_env "$ROOT_DIR/backend/.env.example" "$ROOT_DIR/backend/.env" backend
merge_env "$ROOT_DIR/frontend/.env.example" "$ROOT_DIR/frontend/.env" frontend

echo "✅ backend/.env и frontend/.env сгенерированы из $(basename "$SOURCE")"
