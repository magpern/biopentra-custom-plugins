#!/usr/bin/env bash
# M3 packaged UPR pin checks for biopentra-upr-host 0.1.5+ (no site deploy).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
fail() { echo "M3 PACKAGE PIN FAILED: $*" >&2; exit 1; }

echo "==> Host 0.1.5 + package meta pin"
grep -q "Version: 0.1.5" "$ROOT/biopentra-upr-host.php" || fail "plugin header version"
grep -q "PACKAGE_META_BASENAME = 'release.meta.json'" "$ROOT/includes/class-upr-pin.php" || fail "meta basename"
grep -q 'universal-product-reviews.package-meta/v1' "$ROOT/includes/class-upr-pin.php" || fail "meta schema"
if grep -nE "['\"]/\.git|/\.git'|/\.git\"|\.git/HEAD" "$ROOT/includes/class-upr-pin.php"; then
  fail "pin must not resolve commit via .git"
fi

echo "==> Package pin decision matrix (php)"
if command -v php >/dev/null 2>&1; then
  php "$ROOT/scripts/m3-package-pin-check.php"
else
  docker run --rm -v "$ROOT":/src -w /src php:8.4-cli php scripts/m3-package-pin-check.php
fi

echo "==> All M3 package-pin checks passed"
