#!/usr/bin/env bash
# Генерирует JWT keys на хосте (fallback; в Docker — при старте php).
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

JWT_DIR="$ROOT_DIR/backend/config/jwt"
PRIVATE_KEY="$JWT_DIR/private.pem"
PUBLIC_KEY="$JWT_DIR/public.pem"

mkdir -p "$JWT_DIR"

if [[ -f "$PRIVATE_KEY" ]]; then
  echo "✅ JWT keys уже существуют в backend/config/jwt/"
  exit 0
fi

echo "Generating JWT keys on host (fallback for dev without Docker rebuild)..."
openssl genpkey -algorithm RSA -out "$PRIVATE_KEY" -pkeyopt rsa_keygen_bits:4096
openssl rsa -pubout -in "$PRIVATE_KEY" -out "$PUBLIC_KEY"
chmod 644 "$PRIVATE_KEY" "$PUBLIC_KEY"
echo "✅ JWT keys generated in backend/config/jwt/"
