#!/usr/bin/env bash
#
# MOTION-1 acceptance runner (biopentra-custom-plugins owns the spec).
#
# Copies the canonical Playwright spec into the sibling storefront-acceptance
# harness, runs it, and removes the copies. Also runs the WP-CLI enqueue
# output-capture proof (feature-branch plugin via extra read-only mount) and
# an M4 baseline-vs-injected comparison.
#
# Does NOT: push, tag, deploy, switch the served DEV checkout, or bump version.
#
# Usage:
#   scripts/run-motion-1-acceptance.sh
#   scripts/run-motion-1-acceptance.sh --enqueue-only
#   scripts/run-motion-1-acceptance.sh --playwright-only
#   scripts/run-motion-1-acceptance.sh --m4-only
#   scripts/run-motion-1-acceptance.sh --php-lint-only
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SERVED_REPO="${SERVED_REPO:-/opt/biopentra/dev/biopentra-custom-plugins}"
ACCEPTANCE="${ACCEPTANCE:-/opt/biopentra/dev/storefront-acceptance}"
WP_COMPOSE_DIR="${WP_COMPOSE_DIR:-/opt/biopentra/apps/wordpress}"
STOREFRONT="${ROOT}/plugins/biopentra-storefront"
CANONICAL_SPEC_DIR="${ROOT}/docs/storefront-redesign/validation/motion-1"
EXPECTED_SERVED_SHA="${EXPECTED_SERVED_SHA:-ac60df4574642dba983d1327dd5d756445719c7b}"
M4_PROJECTS=(
	--project=mobile-360
	--project=mobile-390
	--project=mobile-430
	--project=tablet-768
	--project=desktop-1440
)
MOTION_PROJECTS=( "${M4_PROJECTS[@]}" )

mode="all"
case "${1:-}" in
	--enqueue-only) mode="enqueue"; shift || true ;;
	--playwright-only) mode="playwright"; shift || true ;;
	--m4-only) mode="m4"; shift || true ;;
	--php-lint-only) mode="php-lint"; shift || true ;;
	-h|--help)
		sed -n '2,20p' "$0"
		exit 0
		;;
esac

assert_served_untouched() {
	local sha branch dirty
	sha="$(git -C "$SERVED_REPO" rev-parse HEAD)"
	branch="$(git -C "$SERVED_REPO" branch --show-current)"
	if [[ "$branch" != "main" ]]; then
		echo "REFUSING: served checkout branch is '${branch}', expected main" >&2
		exit 2
	fi
	if [[ "$sha" != "$EXPECTED_SERVED_SHA" ]]; then
		echo "REFUSING: served HEAD ${sha} != expected ${EXPECTED_SERVED_SHA}" >&2
		exit 2
	fi
	dirty="$(git -C "$SERVED_REPO" status --porcelain)"
	if [[ -n "$dirty" ]]; then
		echo "REFUSING: served checkout is dirty" >&2
		echo "$dirty" >&2
		exit 2
	fi
	echo "served checkout: ${branch} ${sha} (clean, untouched)"
}

php_lint() {
	echo "== PHP syntax =="
	docker run --rm -v "$STOREFRONT:/plugin:ro" php:8.3-cli \
		php -l /plugin/includes/motion-assets.php
	docker run --rm -v "$STOREFRONT:/plugin:ro" php:8.3-cli \
		php -l /plugin/includes/class-biopentra-storefront.php
	docker run --rm -v "$STOREFRONT:/plugin:ro" php:8.3-cli \
		php -l /plugin/scripts/verify-motion-1-enqueue-cli.php
}

run_enqueue_proof() {
	echo "== WP-CLI enqueue output capture (feature-branch plugin, extra ro mount) =="
	echo "mount: ${STOREFRONT} -> /motion-1-storefront"
	echo "served bind-mount remains ${SERVED_REPO}/plugins/biopentra-storefront"
	(
		cd "$WP_COMPOSE_DIR"
		docker compose --profile tools run --rm -T \
			-v "${STOREFRONT}:/motion-1-storefront:ro" \
			wpcli wp eval-file /motion-1-storefront/scripts/verify-motion-1-enqueue-cli.php
	)
}

STAGED=()
cleanup_acceptance() {
	local f
	for f in "${STAGED[@]+"${STAGED[@]}"}"; do
		if [[ -e "$f" ]]; then
			rm -rf "$f"
			echo "cleaned ${f}"
		fi
	done
}

stage_motion_spec() {
	mkdir -p "${ACCEPTANCE}/tmp-motion"
	cp "${STOREFRONT}/assets/css/bp-motion.css" "${ACCEPTANCE}/tmp-motion/"
	cp "${STOREFRONT}/assets/js/bp-motion.js" "${ACCEPTANCE}/tmp-motion/"
	cp "${CANONICAL_SPEC_DIR}/motion-1.spec.ts" "${ACCEPTANCE}/tests/motion-1.spec.ts"
	cp "${CANONICAL_SPEC_DIR}/motion-1-apply.ts" "${ACCEPTANCE}/tests/motion-1-apply.ts"
	STAGED+=(
		"${ACCEPTANCE}/tmp-motion"
		"${ACCEPTANCE}/tests/motion-1.spec.ts"
		"${ACCEPTANCE}/tests/motion-1-apply.ts"
	)
}

stage_m4_injected_spec() {
	python3 - "${ACCEPTANCE}/tests/m4-cards.spec.ts" "${ACCEPTANCE}/tests/m4-cards-motion-1.spec.ts" <<'PY'
from pathlib import Path
import sys
src = Path(sys.argv[1]).read_text()
src = src.replace(
    "import { gotoWithComingSoonBypass } from '../helpers/coming-soon';",
    "import { gotoWithComingSoonBypass } from '../helpers/coming-soon';\nimport { applyMotion } from './motion-1-apply';",
)
src = src.replace(
    "await dismissOverlays(page);",
    "await dismissOverlays(page);\n    await applyMotion(page);",
)
src = src.replace("M4 — Premium product cards", "M4 + MOTION-1 injected assets — Premium product cards")
Path(sys.argv[2]).write_text(src)
PY
	STAGED+=( "${ACCEPTANCE}/tests/m4-cards-motion-1.spec.ts" )
}

run_playwright() {
	local -a args=( "$@" )
	echo "== Playwright: ${args[*]} =="
	bash "${ACCEPTANCE}/tools/run-dev-playwright.sh" "${args[@]}"
}

copy_report() {
	local dest="$1"
	mkdir -p "$(dirname "$dest")"
	cp "${ACCEPTANCE}/artifacts/playwright-report.json" "$dest"
	echo "saved report ${dest}"
}

DIGEST="${ROOT}/scripts/lib/playwright-failure-digest.py"
OUT_DIR="${CANONICAL_SPEC_DIR}/runs"
mkdir -p "$OUT_DIR"

assert_served_untouched

trap cleanup_acceptance EXIT

case "$mode" in
	php-lint)
		php_lint
		;;
	enqueue)
		php_lint
		run_enqueue_proof
		;;
	playwright)
		stage_motion_spec
		run_playwright tests/motion-1.spec.ts "${MOTION_PROJECTS[@]}"
		copy_report "${OUT_DIR}/motion-1-playwright.json"
		;;
	m4)
		stage_motion_spec
		echo "== M4 baseline (served ${EXPECTED_SERVED_SHA}, no MOTION-1 inject) =="
		run_playwright tests/m4-cards.spec.ts "${M4_PROJECTS[@]}" || true
		copy_report "${OUT_DIR}/m4-baseline.json"
		stage_m4_injected_spec
		echo "== M4 feature (same live DOM + injected MOTION-1 CSS/JS from ${ROOT}) =="
		run_playwright tests/m4-cards-motion-1.spec.ts "${M4_PROJECTS[@]}" || true
		copy_report "${OUT_DIR}/m4-feature-injected.json"
		echo "== M4 parity digest =="
		python3 "$DIGEST" "${OUT_DIR}/m4-baseline.json" "${OUT_DIR}/m4-feature-injected.json" | tee "${OUT_DIR}/m4-parity.txt"
		;;
	all)
		php_lint
		run_enqueue_proof
		stage_motion_spec
		run_playwright tests/motion-1.spec.ts "${MOTION_PROJECTS[@]}"
		copy_report "${OUT_DIR}/motion-1-playwright.json"
		echo "== M4 baseline (served ${EXPECTED_SERVED_SHA}, no MOTION-1 inject) =="
		run_playwright tests/m4-cards.spec.ts "${M4_PROJECTS[@]}" || true
		copy_report "${OUT_DIR}/m4-baseline.json"
		stage_m4_injected_spec
		echo "== M4 feature (same live DOM + injected MOTION-1 CSS/JS from ${ROOT}) =="
		run_playwright tests/m4-cards-motion-1.spec.ts "${M4_PROJECTS[@]}" || true
		copy_report "${OUT_DIR}/m4-feature-injected.json"
		echo "== M4 parity digest =="
		python3 "$DIGEST" "${OUT_DIR}/m4-baseline.json" "${OUT_DIR}/m4-feature-injected.json" | tee "${OUT_DIR}/m4-parity.txt"
		assert_served_untouched
		echo "== MOTION-1 acceptance runner complete =="
		;;
esac
