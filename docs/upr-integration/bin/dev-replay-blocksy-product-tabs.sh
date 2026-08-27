#!/usr/bin/env bash
#
# DEV-only Blocksy theme-mod replay: woo_has_product_tabs=no
#
# Restores the frozen B4 baseline (no Description / Additional / Reviews tab
# chrome on standard Blocksy PDPs). Does not touch production.
#
# Usage:
#   docs/upr-integration/bin/dev-replay-blocksy-product-tabs.sh status
#   docs/upr-integration/bin/dev-replay-blocksy-product-tabs.sh apply
#   docs/upr-integration/bin/dev-replay-blocksy-product-tabs.sh verify
#   docs/upr-integration/bin/dev-replay-blocksy-product-tabs.sh rollback
#
# Requires: /opt/biopentra/scripts/dev-wp (persistent DEV WP-CLI).
# Storage: WordPress theme_mod on the active stylesheet (blocksy-child),
# NOT a top-level wp_options key named woo_has_product_tabs.
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
STATE_DIR="${ROOT}/docs/upr-integration/.dev-replay-state"
STATE_FILE="${STATE_DIR}/woo_has_product_tabs.prev"
MOD_KEY="woo_has_product_tabs"
TARGET_VALUE="no"
DEV_WP="${DEV_WP:-/opt/biopentra/scripts/dev-wp}"

usage() {
	cat <<'EOF'
Usage: dev-replay-blocksy-product-tabs.sh <status|apply|verify|rollback>

  status    Print current theme_mod value and recorded rollback value
  apply     Record previous value (once), set woo_has_product_tabs=no (idempotent)
  verify    Exit 0 only when current value is exactly "no"
  rollback  Restore the recorded pre-apply value (or remove theme_mod if unset)
EOF
}

require_dev() {
	if [[ ! -x "$DEV_WP" ]]; then
		echo "ERROR: missing DEV WP-CLI helper: $DEV_WP" >&2
		exit 1
	fi
	local env
	env="$("$DEV_WP" wp eval 'echo wp_get_environment_type();' 2>/dev/null | tail -1 | tr -d '\r')"
	if [[ "$env" != "development" ]]; then
		echo "ERROR: refusing to mutate theme_mod; environment=$env (need development)" >&2
		exit 1
	fi
}

# Prints raw theme_mod value (may be empty when unset).
current_value() {
	"$DEV_WP" wp eval "
		\$v = get_theme_mod( '${MOD_KEY}', null );
		if ( null === \$v || false === \$v ) {
			echo '';
		} else {
			echo (string) \$v;
		}
	" 2>/dev/null | tail -1 | tr -d '\r'
}

normalize_recorded() {
	# Empty file / empty string means "was unset" → rollback via remove_theme_mod.
	local v="${1:-}"
	printf '%s' "$v"
}

cmd_status() {
	require_dev
	local cur
	cur="$(current_value)"
	echo "environment=development"
	echo "stylesheet=$("$DEV_WP" wp eval 'echo get_stylesheet();' 2>/dev/null | tail -1 | tr -d '\r')"
	if [[ -z "$cur" ]]; then
		echo "current=${MOD_KEY}=<unset> (Blocksy default=yes)"
	else
		echo "current=${MOD_KEY}=${cur}"
	fi
	if [[ -f "$STATE_FILE" ]]; then
		local prev
		prev="$(normalize_recorded "$(cat "$STATE_FILE")")"
		if [[ -z "$prev" ]]; then
			echo "recorded_rollback=<unset>"
		else
			echo "recorded_rollback=${prev}"
		fi
	else
		echo "recorded_rollback=<none>"
	fi
}

cmd_apply() {
	require_dev
	mkdir -p "$STATE_DIR"
	local cur
	cur="$(current_value)"

	if [[ ! -f "$STATE_FILE" ]]; then
		printf '%s' "$cur" >"$STATE_FILE"
		echo "recorded_previous=$( [[ -z "$cur" ]] && echo '<unset>' || echo "$cur" )"
	else
		echo "recorded_previous_kept=$( [[ -z "$(cat "$STATE_FILE")" ]] && echo '<unset>' || cat "$STATE_FILE" )"
	fi

	if [[ "$cur" == "$TARGET_VALUE" ]]; then
		echo "already=${MOD_KEY}=${TARGET_VALUE} (idempotent no-op)"
	else
		"$DEV_WP" wp theme mod set "$MOD_KEY" "$TARGET_VALUE" >/dev/null
		echo "set=${MOD_KEY}=${TARGET_VALUE}"
	fi

	cmd_verify
}

cmd_verify() {
	require_dev
	local cur
	cur="$(current_value)"
	if [[ "$cur" != "$TARGET_VALUE" ]]; then
		echo "VERIFY_FAIL current=$( [[ -z "$cur" ]] && echo '<unset>' || echo "$cur" ) expected=${TARGET_VALUE}" >&2
		exit 2
	fi
	echo "VERIFY_OK ${MOD_KEY}=${TARGET_VALUE}"
	# Clarify storage: not a top-level option.
	echo "note=stored as theme_mod on active stylesheet; not wp_options.${MOD_KEY}"
}

cmd_rollback() {
	require_dev
	if [[ ! -f "$STATE_FILE" ]]; then
		echo "ERROR: no recorded previous value at $STATE_FILE" >&2
		exit 1
	fi
	local prev
	prev="$(normalize_recorded "$(cat "$STATE_FILE")")"
	if [[ -z "$prev" ]]; then
		"$DEV_WP" wp theme mod remove "$MOD_KEY" >/dev/null || true
		echo "restored=${MOD_KEY}=<unset>"
	else
		"$DEV_WP" wp theme mod set "$MOD_KEY" "$prev" >/dev/null
		echo "restored=${MOD_KEY}=${prev}"
	fi
	rm -f "$STATE_FILE"
	cmd_status
}

ACTION="${1:-}"
case "$ACTION" in
status) cmd_status ;;
apply) cmd_apply ;;
verify) cmd_verify ;;
rollback) cmd_rollback ;;
-h|--help|help|'') usage; exit 0 ;;
*) usage >&2; exit 2 ;;
esac
