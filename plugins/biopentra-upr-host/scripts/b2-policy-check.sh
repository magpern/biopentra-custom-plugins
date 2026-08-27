#!/usr/bin/env bash
# B2 static policy checks for biopentra-upr-host (no DEV deploy).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
fail() { echo "B2 POLICY FAILED: $*" >&2; exit 1; }

echo "==> Host version / pin constants"
grep -q "Version: 0.1.5" "$ROOT/biopentra-upr-host.php" || fail "plugin header version"
grep -q "BIOPENTRA_UPR_HOST_VERSION', '0.1.5'" "$ROOT/biopentra-upr-host.php" || fail "host version constant"
grep -q "REQUIRED_VERSION = '0.3.0'" "$ROOT/includes/class-upr-pin.php" || fail "UPR pin version"
grep -q "REQUIRED_COMMIT = 'b2abc2defc30fc023601593aa1720cbfdd0a4f3c'" "$ROOT/includes/class-upr-pin.php" || fail "UPR pin commit"
grep -q "REQUIRED_TAG = 'v0.3.0'" "$ROOT/includes/class-upr-pin.php" || fail "UPR pin tag"
grep -q 'InvitationAuthorisation' "$ROOT/includes/class-upr-pin.php" || fail "UPR InvitationAuthorisation pin check"
grep -q "PACKAGE_META_BASENAME = 'release.meta.json'" "$ROOT/includes/class-upr-pin.php" || fail "package meta basename"
grep -q 'universal-product-reviews.package-meta/v1' "$ROOT/includes/class-upr-pin.php" || fail "package meta schema"
if grep -q '\.git' "$ROOT/includes/class-upr-pin.php"; then
  # Allow documentation comments that mention .git; forbid runtime .git path use.
  if grep -nE "['\"]/.git|/\.git'|/\.git\"|\.git/HEAD" "$ROOT/includes/class-upr-pin.php"; then
    fail "pin must not resolve commit via .git"
  fi
fi

echo "==> No host comments_open availability gate"
if grep -RIn --include='*.php' -E "add_filter\s*\(\s*['\"]comments_open" "$ROOT/includes" "$ROOT/biopentra-upr-host.php"; then
  fail "host comments_open filter found"
fi
if grep -RIn --include='*.php' 'function maybe_close_product_reviews' "$ROOT/includes" "$ROOT/biopentra-upr-host.php"; then
  fail "maybe_close_product_reviews must be removed from runtime includes"
fi

echo "==> No host preprocess_comment enforcement"
if grep -RIn --include='*.php' -E "add_filter\s*\(\s*['\"]preprocess_comment" "$ROOT/includes" "$ROOT/biopentra-upr-host.php"; then
  fail "host preprocess_comment registration found"
fi
if grep -RIn --include='*.php' 'function reject_unavailable_native_product_review' "$ROOT/includes"; then
  fail "host native preprocess reject must not remain in includes/"
fi

echo "==> Display helper delegates to UPR NativePdpForm"
grep -q 'NativePdpForm::should_render' "$ROOT/includes/class-review-availability-ux.php" || fail "missing NativePdpForm::should_render delegation"
grep -q 'product_not_reviewable' "$ROOT/includes/class-review-availability-ux.php" || fail "missing product_not_reviewable copy"

echo "==> No WooCommerce Internal APIs"
if grep -RIn --include='*.php' -E 'Internal\\OrderReviews|Automattic\\WooCommerce\\Internal\\' "$ROOT"; then
  fail "Internal WooCommerce API reference found"
fi

echo "==> PHP syntax (when php available)"
if command -v php >/dev/null 2>&1; then
  find "$ROOT" -name '*.php' -print0 | while IFS= read -r -d '' f; do
    php -l "$f" >/dev/null
  done
else
  echo "(php not on PATH — syntax lint deferred to CI/container)"
fi

echo "==> All B2 host policy checks passed"
