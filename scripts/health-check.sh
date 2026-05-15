#!/usr/bin/env bash
# Biopentra WooCommerce stack health checks (read-only).
# Run from /home/magpern/woocommerce or set WOOCOMMERCE_ROOT.
set -euo pipefail

WOOCOMMERCE_ROOT="${WOOCOMMERCE_ROOT:-/home/magpern/woocommerce}"
SITE_URL="${SITE_URL:-https://www.biopentra.eu}"
CSS_DIR="${WOOCOMMERCE_ROOT}/wp-content/uploads/elementor/css"

WP="${WOOCOMMERCE_ROOT}/wp"
if [[ ! -x "$WP" ]]; then
	echo "FAIL: missing ./wp at $WP" >&2
	exit 1
fi

fail=0
ok() { echo "OK   $*"; }
warn() { echo "WARN $*"; }
bad() { echo "FAIL $*"; fail=1; }

echo "=== Biopentra health check ==="
echo "root: $WOOCOMMERCE_ROOT"
echo "site: $SITE_URL"
echo

# Storefront active
if "$WP" plugin is-active biopentra-storefront >/dev/null 2>&1; then
	ver="$("$WP" plugin get biopentra-storefront --field=version 2>/dev/null || echo '?')"
	ok "biopentra-storefront active (version $ver)"
else
	bad "biopentra-storefront not active"
fi

# Coming soon off
cs="$("$WP" option get woocommerce_coming_soon 2>/dev/null || echo '?')"
if [[ "$cs" == "no" ]]; then
	ok "woocommerce_coming_soon=no"
else
	bad "woocommerce_coming_soon=$cs (expected no)"
fi

# Elementor CSS directory writable by wpcli (uid 33) — host user may not match www-data
if [[ ! -d "$CSS_DIR" ]]; then
	bad "missing $CSS_DIR"
else
	perms="$(stat -c '%a %U:%G' "$CSS_DIR" 2>/dev/null || echo '?')"
	compose="${WOOCOMMERCE_ROOT}/docker-compose.yml"
	if [[ -f "$compose" ]] && docker compose -f "$compose" run --rm --no-deps -T wpcli \
		sh -c 'test -w /var/www/html/wp-content/uploads/elementor/css' >/dev/null 2>&1; then
		ok "Elementor CSS dir writable via wpcli ($perms)"
	else
		bad "Elementor CSS dir not writable via wpcli ($perms) — see docs/elementor-css-generation-hardening.md"
	fi
fi

# Spot-check critical Elementor post CSS (page IDs)
declare -A PAGES=(
	[3483]="home"
	[3410]="contact"
	[3825]="about"
	[3826]="faq"
	[3755]="shop"
	[82]="cart"
)
for id in "${!PAGES[@]}"; do
	name="${PAGES[$id]}"
	file="post-${id}.css"
	path="${CSS_DIR}/${file}"
	url="${SITE_URL}/wp-content/uploads/elementor/css/${file}"
	if [[ ! -f "$path" ]]; then
		bad "missing on disk: $file ($name)"
		continue
	fi
	code="$(curl -sS -o /dev/null -w '%{http_code}' "$url" 2>/dev/null || echo '000')"
	if [[ "$code" == "200" ]]; then
		ok "HTTP 200 $file ($name)"
	else
		bad "HTTP $code $url ($name)"
	fi
done

# Legacy plugins should not be active
for leg in biopentra-information-megamenu biopentra-footer-contact custom-variation-stock-selector biopentra-header-auth; do
	if "$WP" plugin is-active "$leg" >/dev/null 2>&1; then
		bad "legacy plugin still active: $leg"
	else
		ok "legacy inactive: $leg"
	fi
done

echo
if [[ "$fail" -eq 0 ]]; then
	echo "Result: PASS"
	exit 0
fi
echo "Result: FAIL (see above)"
exit 1
