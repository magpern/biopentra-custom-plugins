#!/usr/bin/env bash
# Verify custom Biopentra plugin folders on disk match expectations and WP/runtime checks pass.
# Read-only — does not modify production plugins.
# Run from custom-wordpress-plugins or set WOOCOMMERCE_ROOT / CUSTOM_PLUGINS_ROOT.
set -euo pipefail

WOOCOMMERCE_ROOT="${WOOCOMMERCE_ROOT:-/home/magpern/woocommerce}"
CUSTOM_PLUGINS_ROOT="${CUSTOM_PLUGINS_ROOT:-${WOOCOMMERCE_ROOT}/custom-wordpress-plugins}"
PLUGINS_DIR="${WOOCOMMERCE_ROOT}/wp-content/plugins"
SOURCE_DIR="${CUSTOM_PLUGINS_ROOT}/plugins"
SITE_URL="${SITE_URL:-https://www.biopentra.eu}"
CSS_DIR="${WOOCOMMERCE_ROOT}/wp-content/uploads/elementor/css"
SUPPORT_HEALTH_URL="${SITE_URL}/wp-json/biopentra-support/v1/health"

WP="${WOOCOMMERCE_ROOT}/wp"
if [[ ! -x "$WP" ]]; then
	echo "FAIL: missing ./wp at $WP" >&2
	exit 1
fi

fail=0
ok() { echo "OK   $*"; }
warn() { echo "WARN $*"; }
bad() { echo "FAIL $*"; fail=1; }

echo "=== Custom plugin integrity check ==="
echo "woocommerce: $WOOCOMMERCE_ROOT"
echo "source repo: $SOURCE_DIR"
echo "site:      $SITE_URL"
echo

# slug|main_php|dir1|dir2|...
PLUGIN_MANIFEST=(
	"biopentra-storefront|biopentra-storefront.php|includes|modules/header-auth|modules/footer-contact|modules/information-megamenu|modules/variation-stock-selector|assets"
	"biopentra-loop-card|biopentra-loop-card.php|includes|assets"
	"biopentra-contact-inbox|biopentra-contact-inbox.php|includes|assets"
	"wc-inventory-overview|wc-inventory-overview.php|includes|assets|cli"
)

check_plugin_tree() {
	local slug="$1"
	local main="$2"
	shift 2
	local -a required_dirs=( "$@" )
	local prod="${PLUGINS_DIR}/${slug}"
	local src="${SOURCE_DIR}/${slug}"

	echo "--- ${slug} ---"

	if [[ ! -d "$prod" ]]; then
		bad "${slug}: production folder missing ($prod)"
		return
	fi
	ok "${slug}: folder exists"

	local perms owner
	perms="$(stat -c '%a' "$prod" 2>/dev/null || echo '?')"
	owner="$(stat -c '%U:%G' "$prod" 2>/dev/null || echo '?')"
	if [[ ! -r "$prod" || ! -x "$prod" ]]; then
		bad "${slug}: directory not readable/executable ($perms $owner)"
	elif [[ "$owner" == "www-data:www-data" && "$perms" == "755" ]]; then
		warn "${slug}: owned www-data:www-data mode $perms (deploy user may not sync without sudo/chown)"
	else
		ok "${slug}: permissions $perms $owner"
	fi

	local main_path="${prod}/${main}"
	if [[ ! -f "$main_path" ]]; then
		bad "${slug}: MISSING main plugin file ${main}"
	else
		ok "${slug}: main file ${main}"
	fi

	local d
	for d in "${required_dirs[@]}"; do
		if [[ ! -d "${prod}/${d}" ]]; then
			bad "${slug}: missing directory ${d}/"
		else
			ok "${slug}: ${d}/ present"
		fi
	done

	local prod_count src_count
	prod_count="$(find "$prod" -type f 2>/dev/null | wc -l | tr -d ' ')"
	if [[ -d "$src" ]]; then
		src_count="$(find "$src" -type f 2>/dev/null | wc -l | tr -d ' ')"
		if [[ "$prod_count" -lt "$(( src_count - 2 ))" ]]; then
			bad "${slug}: file count prod=$prod_count src=$src_count (possible partial loss)"
		elif [[ "$prod_count" -ne "$src_count" ]]; then
			extra="$(diff -rq "$src" "$prod" 2>/dev/null | head -5 || true)"
			if [[ -n "$extra" ]]; then
				warn "${slug}: prod=$prod_count src=$src_count — diff: $(echo "$extra" | tr '\n' '; ' | sed 's/; $//')"
			else
				ok "${slug}: file count prod=$prod_count src=$src_count (minor diff acceptable)"
			fi
		else
			if diff -rq "$src" "$prod" >/dev/null 2>&1; then
				ok "${slug}: matches source tree ($prod_count files)"
			else
				warn "${slug}: same file count but content differs from source"
				diff -rq "$src" "$prod" 2>/dev/null | head -5 | while read -r line; do
					warn "  $line"
				done
			fi
		fi
	else
		warn "${slug}: no source at ${src} (skip tree compare)"
	fi

	if "$WP" plugin is-active "$slug" >/dev/null 2>&1; then
		local ver
		ver="$("$WP" plugin get "$slug" --field=version 2>/dev/null || echo '?')"
		ok "${slug}: active in WordPress (v${ver})"
	else
		bad "${slug}: NOT active (or not registered — check main PHP file)"
	fi
	echo
}

for entry in "${PLUGIN_MANIFEST[@]}"; do
	IFS='|' read -ra parts <<< "$entry"
	slug="${parts[0]}"
	main="${parts[1]}"
	check_plugin_tree "$slug" "$main" "${parts[@]:2}"
done

# Support Desk REST health (plugin must be active)
echo "--- runtime ---"
if "$WP" plugin is-active biopentra-contact-inbox >/dev/null 2>&1; then
	code="$(curl -sS -o /dev/null -w '%{http_code}' "$SUPPORT_HEALTH_URL" 2>/dev/null || echo '000')"
	if [[ "$code" == "200" ]]; then
		ok "Support Desk REST health HTTP 200"
	else
		bad "Support Desk REST health HTTP $code ($SUPPORT_HEALTH_URL)"
	fi
else
	bad "Support Desk REST skipped — biopentra-contact-inbox inactive"
fi

# Elementor CSS writable via wpcli
if [[ -d "$CSS_DIR" ]]; then
	perms="$(stat -c '%a %U:%G' "$CSS_DIR" 2>/dev/null || echo '?')"
	compose="${WOOCOMMERCE_ROOT}/docker-compose.yml"
	if [[ -f "$compose" ]] && docker compose -f "$compose" run --rm --no-deps -T wpcli \
		sh -c 'test -w /var/www/html/wp-content/uploads/elementor/css' >/dev/null 2>&1; then
		ok "Elementor CSS dir writable via wpcli ($perms)"
	else
		bad "Elementor CSS dir not writable via wpcli ($perms)"
	fi
else
	bad "missing Elementor CSS directory $CSS_DIR"
fi

echo
if [[ "$fail" -eq 0 ]]; then
	echo "Result: PASS"
	exit 0
fi
echo "Result: FAIL (see above)"
exit 1
