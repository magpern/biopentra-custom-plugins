#!/usr/bin/env bash
# Verify custom Biopentra plugin folders on disk match expectations and WP/runtime checks pass.
# Read-only — does not modify production plugins.
# Run from custom-wordpress-plugins or set WOOCOMMERCE_ROOT / CUSTOM_PLUGINS_ROOT.
set -euo pipefail

WOOCOMMERCE_ROOT="${WOOCOMMERCE_ROOT:-/home/magpern/woocommerce}"
CUSTOM_PLUGINS_ROOT="${CUSTOM_PLUGINS_ROOT:-${WOOCOMMERCE_ROOT}/custom-wordpress-plugins}"
FISD_SOURCE_ROOT="${FISD_SOURCE_ROOT:-/home/magpern/fluent-imap-support-desk-repo}"
PLUGINS_DIR="${WOOCOMMERCE_ROOT}/wp-content/plugins"
SOURCE_DIR="${CUSTOM_PLUGINS_ROOT}/plugins"
SITE_URL="${SITE_URL:-https://www.biopentra.eu}"
CSS_DIR="${WOOCOMMERCE_ROOT}/wp-content/uploads/elementor/css"
SUPPORT_HEALTH_URL="${SITE_URL}/wp-json/biopentra-support/v1/health"
SUPPORT_DESK_SLUG="fluent-imap-support-desk"
LEGACY_INBOX_SLUG="biopentra-contact-inbox"

WP="${WOOCOMMERCE_ROOT}/wp"
if [[ ! -x "$WP" ]]; then
	echo "FAIL: missing ./wp at $WP" >&2
	exit 1
fi

fail=0
ok() { echo "OK   $*"; }
warn() { echo "WARN $*"; }
bad() { echo "FAIL $*"; fail=1; }

# Deployable plugin files only (exclude repo docs/scripts/builds/git metadata).
fisd_deployable_file_count() {
	local root="$1"
	find "$root" -type f \
		! -path '*/.git/*' \
		! -path '*/docs/*' \
		! -path '*/scripts/*' \
		! -path '*/builds/*' \
		! -name 'README.md' \
		! -name 'CHANGELOG.md' \
		! -name '.gitignore' \
		2>/dev/null | wc -l | tr -d ' '
}

fisd_prod_matches_source() {
	local src="$1" prod="$2"
	diff -q "${src}/fluent-imap-support-desk.php" "${prod}/fluent-imap-support-desk.php" >/dev/null 2>&1 \
		&& diff -q "${src}/uninstall.php" "${prod}/uninstall.php" >/dev/null 2>&1 \
		&& diff -rq "${src}/includes" "${prod}/includes" >/dev/null 2>&1 \
		&& diff -rq "${src}/assets" "${prod}/assets" >/dev/null 2>&1
}

echo "=== Custom plugin integrity check ==="
echo "woocommerce: $WOOCOMMERCE_ROOT"
echo "source repo: $SOURCE_DIR"
echo "fisd source: $FISD_SOURCE_ROOT"
echo "site:      $SITE_URL"
echo

# slug|main_php|dir1|dir2|...
PLUGIN_MANIFEST=(
	"biopentra-storefront|biopentra-storefront.php|includes|modules/header-auth|modules/footer-contact|modules/information-megamenu|modules/variation-stock-selector|assets"
	"biopentra-loop-card|biopentra-loop-card.php|includes|assets"
	"fluent-imap-support-desk|fluent-imap-support-desk.php|includes|assets"
	"wc-inventory-overview|wc-inventory-overview.php|includes|assets|cli"
)

check_plugin_tree() {
	local slug="$1"
	local main="$2"
	shift 2
	local -a required_dirs=( "$@" )
	local prod="${PLUGINS_DIR}/${slug}"
	local src="${SOURCE_DIR}/${slug}"

	if [[ "$slug" == "$SUPPORT_DESK_SLUG" ]]; then
		src="${FISD_SOURCE_ROOT}"
	fi

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
		if [[ "$slug" == "$SUPPORT_DESK_SLUG" ]]; then
			src_count="$(fisd_deployable_file_count "$src")"
		else
			src_count="$(find "$src" -type f 2>/dev/null | wc -l | tr -d ' ')"
		fi
		if [[ "$prod_count" -lt "$(( src_count - 3 ))" ]]; then
			bad "${slug}: file count prod=$prod_count src=$src_count (possible partial loss)"
		elif [[ "$prod_count" -ne "$src_count" ]]; then
			extra="$( { diff -rq "$src" "$prod" 2>/dev/null || true; } | head -5 )"
			if [[ -n "$extra" ]]; then
				warn "${slug}: prod=$prod_count src=$src_count — diff: $(echo "$extra" | tr '\n' '; ' | sed 's/; $//')"
			else
				ok "${slug}: file count prod=$prod_count src=$src_count (minor diff acceptable)"
			fi
		else
			if [[ "$slug" == "$SUPPORT_DESK_SLUG" ]]; then
				if fisd_prod_matches_source "$src" "$prod"; then
					ok "${slug}: matches deployable source ($prod_count files)"
				else
					warn "${slug}: deployable tree differs from source"
					{ diff -rq "${src}/includes" "${prod}/includes" 2>/dev/null || true
					  diff -rq "${src}/assets" "${prod}/assets" 2>/dev/null || true
					} | head -5 | while read -r line; do warn "  $line"; done || true
				fi
			elif diff -rq "$src" "$prod" >/dev/null 2>&1; then
				ok "${slug}: matches source tree ($prod_count files)"
			else
				warn "${slug}: same file count but content differs from source"
				{ diff -rq "$src" "$prod" 2>/dev/null || true; } | head -5 | while read -r line; do
					warn "  $line"
				done || true
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

# Legacy inbox slug must not be active alongside FISD
echo "--- support desk compatibility ---"
if "$WP" plugin is-active "$LEGACY_INBOX_SLUG" >/dev/null 2>&1; then
	bad "legacy plugin $LEGACY_INBOX_SLUG is still active (deactivate — only $SUPPORT_DESK_SLUG should run)"
else
	ok "legacy plugin $LEGACY_INBOX_SLUG inactive"
fi

if [[ -d "${PLUGINS_DIR}/${LEGACY_INBOX_SLUG}" ]]; then
	warn "legacy folder ${LEGACY_INBOX_SLUG}/ still present (remove after cutover stable period if not backup)"
elif ls -d "${PLUGINS_DIR}/${LEGACY_INBOX_SLUG}".backup-* >/dev/null 2>&1; then
	ok "legacy folder moved aside ($(ls -d "${PLUGINS_DIR}/${LEGACY_INBOX_SLUG}".backup-* 2>/dev/null | head -1 | xargs basename))"
else
	ok "no live legacy ${LEGACY_INBOX_SLUG}/ folder"
fi

if "$WP" eval 'echo current_user_can("manage_biopentra_inbox") ? "yes" : "no";' 2>/dev/null | grep -q yes; then
	ok "capability manage_biopentra_inbox (admin check via WP-CLI)"
else
	warn "manage_biopentra_inbox not true in WP-CLI context (verify logged-in admin in browser)"
fi

# Support Desk REST health (fluent-imap-support-desk must be active)
echo "--- runtime ---"
if "$WP" plugin is-active "$SUPPORT_DESK_SLUG" >/dev/null 2>&1; then
	code="$(curl -sS -o /dev/null -w '%{http_code}' "$SUPPORT_HEALTH_URL" 2>/dev/null || echo '000')"
	if [[ "$code" == "200" ]]; then
		ok "Support Desk REST health HTTP 200"
		body="$(curl -sS "$SUPPORT_HEALTH_URL" 2>/dev/null || echo '')"
		if echo "$body" | grep -q '"worker_token_configured":true'; then
			ok "REST health worker_token_configured=true"
		fi
		if echo "$body" | grep -q '"plugin":"biopentra-contact-inbox"'; then
			ok "REST health plugin id biopentra-contact-inbox (compatibility metadata)"
		elif echo "$body" | grep -q '"plugin_active":true'; then
			ok "REST health plugin_active=true"
		else
			warn "REST health body unexpected: ${body:0:120}"
		fi
	else
		bad "Support Desk REST health HTTP $code ($SUPPORT_HEALTH_URL)"
	fi

	import_code="$(curl -sS -o /dev/null -w '%{http_code}' -X POST "${SITE_URL}/wp-json/biopentra-support/v1/messages/import" 2>/dev/null || echo '000')"
	if [[ "$import_code" == "401" || "$import_code" == "403" ]]; then
		ok "REST import route reachable (HTTP $import_code without token — expected)"
	elif [[ "$import_code" == "404" ]]; then
		bad "REST import route HTTP 404 (plugin REST not registered)"
	else
		ok "REST import route HTTP $import_code"
	fi
else
	bad "Support Desk REST skipped — $SUPPORT_DESK_SLUG inactive"
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
