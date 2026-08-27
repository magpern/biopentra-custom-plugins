#!/usr/bin/env bash
#
# Disposable non-WordPress rehearsal of pair transition + rollback.
# Does NOT modify the existing DEV WordPress site or production.
#
# Optional env:
#   UPR_ZIP / HOST_ZIP / UPR_SUMS / HOST_SUMS — package paths
#
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
PAIR_SCRIPTS="$(cd "$(dirname "$0")" && pwd)"
DISPOSABLE="$(mktemp -d "${TMPDIR:-/tmp}/m3-pair-rehearsal.XXXXXX")"
STATE="${DISPOSABLE}/worker-state"
mkdir -p "${STATE}" "${DISPOSABLE}/plugins" "${DISPOSABLE}/releases" "${DISPOSABLE}/pointers" "${DISPOSABLE}/safety"

cleanup() { rm -rf "${DISPOSABLE}"; }
trap cleanup EXIT

echo "==> Disposable rehearsal root: ${DISPOSABLE}"

UPR_ZIP="${UPR_ZIP:-${ROOT}/../universal-product-reviews/builds/universal-product-reviews-0.3.0.zip}"
HOST_ZIP="${HOST_ZIP:-${ROOT}/../upr-host-adapter/builds/upr-host-adapter-0.1.1.zip}"
UPR_SUMS="${UPR_SUMS:-${ROOT}/../universal-product-reviews/builds/universal-product-reviews-0.3.0.SHA256SUMS}"
HOST_SUMS="${HOST_SUMS:-${ROOT}/../upr-host-adapter/builds/upr-host-adapter-0.1.1.SHA256SUMS}"

if [[ ! -f "${UPR_ZIP}" && -f /opt/biopentra/dev/universal-product-reviews/builds/universal-product-reviews-0.3.0.zip ]]; then
	UPR_ZIP=/opt/biopentra/dev/universal-product-reviews/builds/universal-product-reviews-0.3.0.zip
	UPR_SUMS=/opt/biopentra/dev/universal-product-reviews/builds/universal-product-reviews-0.3.0.SHA256SUMS
fi
if [[ ! -f "${HOST_ZIP}" && -f /opt/biopentra/dev/upr-host-adapter/builds/upr-host-adapter-0.1.1.zip ]]; then
	HOST_ZIP=/opt/biopentra/dev/upr-host-adapter/builds/upr-host-adapter-0.1.1.zip
	HOST_SUMS=/opt/biopentra/dev/upr-host-adapter/builds/upr-host-adapter-0.1.1.SHA256SUMS
fi
# Also accept custom-plugins builds/zips mirror
if [[ ! -f "${HOST_ZIP}" && -f "${ROOT}/builds/zips/upr-host-adapter-0.1.1.zip" ]]; then
	HOST_ZIP="${ROOT}/builds/zips/upr-host-adapter-0.1.1.zip"
	HOST_SUMS="${ROOT}/builds/zips/upr-host-adapter-0.1.1.SHA256SUMS"
fi

[[ -f "${UPR_ZIP}" && -f "${HOST_ZIP}" ]] || {
	echo "ERROR: package zips missing; build UPR + upr-host-adapter packages first" >&2
	exit 1
}

# --- Path A: upgrade-style (previous trees exist) ---
mkdir -p "${DISPOSABLE}/releases/upr-host-adapter/0.0.0-prev" \
	"${DISPOSABLE}/releases/universal-product-reviews/0.0.0-prev"
echo "previous-host" > "${DISPOSABLE}/releases/upr-host-adapter/0.0.0-prev/marker.txt"
echo "previous-upr" > "${DISPOSABLE}/releases/universal-product-reviews/0.0.0-prev/marker.txt"
( cd "${DISPOSABLE}/releases/upr-host-adapter/0.0.0-prev" && sha256sum marker.txt > "../0.0.0-prev.SHA256SUMS" )
( cd "${DISPOSABLE}/releases/universal-product-reviews/0.0.0-prev" && sha256sum marker.txt > "../0.0.0-prev.SHA256SUMS" )

ln -sfn "${DISPOSABLE}/releases/upr-host-adapter/0.0.0-prev" "${DISPOSABLE}/plugins/upr-host-adapter"
ln -sfn "${DISPOSABLE}/releases/universal-product-reviews/0.0.0-prev" "${DISPOSABLE}/plugins/universal-product-reviews"
mkdir -p "${DISPOSABLE}/pointers/upr-host-adapter" "${DISPOSABLE}/pointers/universal-product-reviews"
ln -sfn "${DISPOSABLE}/releases/upr-host-adapter/0.0.0-prev" "${DISPOSABLE}/pointers/upr-host-adapter/current"
ln -sfn "${DISPOSABLE}/releases/universal-product-reviews/0.0.0-prev" "${DISPOSABLE}/pointers/universal-product-reviews/current"

cat > "${DISPOSABLE}/safety/state.env" <<'EOF'
invitation_emails_enabled=false
pilot_invitation_sending_authorised=false
pilot_order_id_allowlist_empty=true
EOF
: > "${DISPOSABLE}/safety/activated-upr"
: > "${DISPOSABLE}/safety/activated-host"
# Start "activated" for previous trees so deactivate makes sense
echo 1 > "${DISPOSABLE}/safety/wp-upr-active"
echo 1 > "${DISPOSABLE}/safety/wp-host-active"

export PAIR_ROOT="${DISPOSABLE}"
export PLUGINS_LINK_ROOT="${DISPOSABLE}/plugins"
export HOST_SLUG=upr-host-adapter
export HOST_VERSION=0.1.1
export UPR_SLUG=universal-product-reviews
export UPR_VERSION=0.3.0
export PAIR_PREVIOUS_MODE=previous

export WORKER_SUSPEND_CMD="touch '${STATE}/suspended' && rm -f '${STATE}/running-send'"
export WORKER_RESUME_CMD="rm -f '${STATE}/suspended'"
export WORKER_PROOF_CMD="test -f '${STATE}/suspended' && test ! -f '${STATE}/running-send' && echo upr_send_active=0"

export PREFLIGHT_CMD="grep -qx 'invitation_emails_enabled=false' '${DISPOSABLE}/safety/state.env' && grep -qx 'pilot_invitation_sending_authorised=false' '${DISPOSABLE}/safety/state.env' && grep -qx 'pilot_order_id_allowlist_empty=true' '${DISPOSABLE}/safety/state.env' && echo PREFLIGHT_OK"
export CONFIRM_EMAILS_DISABLED_CMD="grep -qx 'invitation_emails_enabled=false' '${DISPOSABLE}/safety/state.env' && echo EMAILS_DISABLED_OK"
export ACTIVATE_UPR_CMD="echo 1 > '${DISPOSABLE}/safety/wp-upr-active' && echo ACTIVATE_UPR_OK"
export ACTIVATE_HOST_CMD="test -f '${DISPOSABLE}/safety/wp-upr-active' && echo 1 > '${DISPOSABLE}/safety/wp-host-active' && echo ACTIVATE_HOST_OK"
export DEACTIVATE_HOST_CMD="rm -f '${DISPOSABLE}/safety/wp-host-active' && echo DEACTIVATE_HOST_OK"
export DEACTIVATE_UPR_CMD="test ! -f '${DISPOSABLE}/safety/wp-host-active' && rm -f '${DISPOSABLE}/safety/wp-upr-active' && echo DEACTIVATE_UPR_OK"
export POST_VERIFY_CMD="test -f '${DISPOSABLE}/plugins/universal-product-reviews/release.meta.json' && test -f '${DISPOSABLE}/plugins/upr-host-adapter/upr-host-adapter.php' && grep -q \"Version: 0.1.1\" '${DISPOSABLE}/plugins/upr-host-adapter/upr-host-adapter.php' && test -f '${DISPOSABLE}/safety/wp-upr-active' && test -f '${DISPOSABLE}/safety/wp-host-active' && echo POST_VERIFY_OK"
export ROLLBACK_VERIFY_CMD="test -f '${DISPOSABLE}/plugins/upr-host-adapter/marker.txt' && test -f '${DISPOSABLE}/plugins/universal-product-reviews/marker.txt' && test ! -f '${DISPOSABLE}/safety/wp-host-active' && test ! -f '${DISPOSABLE}/safety/wp-upr-active' && echo ROLLBACK_VERIFY_OK"
export MAINTENANCE_ON_CMD="touch '${DISPOSABLE}/safety/maintenance'"
export MAINTENANCE_OFF_CMD="rm -f '${DISPOSABLE}/safety/maintenance'"

echo "==> Stage packages"
bash "${PAIR_SCRIPTS}/stage-release.sh" upr-host-adapter 0.1.1 "${HOST_ZIP}" "${HOST_SUMS}"
bash "${PAIR_SCRIPTS}/stage-release.sh" universal-product-reviews 0.3.0 "${UPR_ZIP}" "${UPR_SUMS}"

echo "==> Transition (upgrade-style)"
bash "${PAIR_SCRIPTS}/pair-transition.sh"
readlink -f "${DISPOSABLE}/plugins/upr-host-adapter" | grep -q '/0.1.1$'
readlink -f "${DISPOSABLE}/plugins/universal-product-reviews" | grep -q '/0.3.0$'
test ! -f "${STATE}/suspended"
test -f "${DISPOSABLE}/safety/wp-upr-active"
test -f "${DISPOSABLE}/safety/wp-host-active"
echo "OK transition"

echo "==> Rollback to previous"
bash "${PAIR_SCRIPTS}/pair-rollback.sh"
readlink -f "${DISPOSABLE}/plugins/upr-host-adapter" | grep -q '/0.0.0-prev$'
readlink -f "${DISPOSABLE}/plugins/universal-product-reviews" | grep -q '/0.0.0-prev$'
test ! -f "${STATE}/suspended"
echo "OK rollback previous"

echo "==> Fail-closed: missing suspend aborts before placement"
export WORKER_SUSPEND_CMD='echo SUSPEND_FAIL; false'
ln -sfn "${DISPOSABLE}/releases/upr-host-adapter/0.0.0-prev" "${DISPOSABLE}/plugins/upr-host-adapter"
ln -sfn "${DISPOSABLE}/releases/universal-product-reviews/0.0.0-prev" "${DISPOSABLE}/plugins/universal-product-reviews"
echo 1 > "${DISPOSABLE}/safety/wp-upr-active"
echo 1 > "${DISPOSABLE}/safety/wp-host-active"
if bash "${PAIR_SCRIPTS}/pair-transition.sh"; then
	echo "ERROR: expected abort on suspend failure" >&2
	exit 1
fi
echo "OK suspend failure aborts"

echo "==> Fail-closed: superseded host identity refused"
export WORKER_SUSPEND_CMD="touch '${STATE}/suspended' && rm -f '${STATE}/running-send'"
export HOST_SLUG=biopentra-upr-host
export HOST_VERSION=0.1.5
if bash "${PAIR_SCRIPTS}/pair-transition.sh" 2>/dev/null; then
	echo "ERROR: expected refuse superseded host" >&2
	exit 1
fi
export HOST_SLUG=upr-host-adapter
export HOST_VERSION=0.1.1
echo "OK superseded identity refused"

# --- Path B: first-install → rollback-to-absent ---
echo "==> First-install absent path"
rm -f "${DISPOSABLE}/plugins/upr-host-adapter" "${DISPOSABLE}/plugins/universal-product-reviews"
rm -rf "${DISPOSABLE}/pointers"
mkdir -p "${DISPOSABLE}/pointers"
rm -f "${DISPOSABLE}/safety/wp-upr-active" "${DISPOSABLE}/safety/wp-host-active"
export PAIR_PREVIOUS_MODE=absent
export ROLLBACK_VERIFY_CMD="test ! -e '${DISPOSABLE}/plugins/upr-host-adapter' && test ! -e '${DISPOSABLE}/plugins/universal-product-reviews' && test ! -f '${DISPOSABLE}/safety/wp-host-active' && test ! -f '${DISPOSABLE}/safety/wp-upr-active' && echo ROLLBACK_ABSENT_OK"

bash "${PAIR_SCRIPTS}/pair-transition.sh"
test -f "${DISPOSABLE}/safety/wp-upr-active"
test -f "${DISPOSABLE}/safety/wp-host-active"
bash "${PAIR_SCRIPTS}/pair-rollback.sh"
test ! -e "${DISPOSABLE}/plugins/upr-host-adapter"
test ! -e "${DISPOSABLE}/plugins/universal-product-reviews"
echo "OK first-install rollback-to-absent"

echo "==> Disposable rehearsal passed (DEV WordPress untouched)"
