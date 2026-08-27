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
HOST_ZIP="${HOST_ZIP:-${ROOT}/builds/zips/biopentra-upr-host-0.1.5.zip}"
UPR_SUMS="${UPR_SUMS:-${ROOT}/../universal-product-reviews/builds/universal-product-reviews-0.3.0.SHA256SUMS}"
HOST_SUMS="${HOST_SUMS:-${ROOT}/builds/zips/biopentra-upr-host-0.1.5.SHA256SUMS}"

# Fallback: locate relative to monorepo sibling
if [[ ! -f "${UPR_ZIP}" && -f /opt/biopentra/dev/universal-product-reviews/builds/universal-product-reviews-0.3.0.zip ]]; then
	UPR_ZIP=/opt/biopentra/dev/universal-product-reviews/builds/universal-product-reviews-0.3.0.zip
	UPR_SUMS=/opt/biopentra/dev/universal-product-reviews/builds/universal-product-reviews-0.3.0.SHA256SUMS
fi
if [[ ! -f "${HOST_ZIP}" && -f /opt/biopentra/dev/biopentra-custom-plugins/builds/zips/biopentra-upr-host-0.1.5.zip ]]; then
	HOST_ZIP=/opt/biopentra/dev/biopentra-custom-plugins/builds/zips/biopentra-upr-host-0.1.5.zip
	HOST_SUMS=/opt/biopentra/dev/biopentra-custom-plugins/builds/zips/biopentra-upr-host-0.1.5.SHA256SUMS
fi

[[ -f "${UPR_ZIP}" && -f "${HOST_ZIP}" ]] || {
	echo "ERROR: package zips missing; build WP-B packages first" >&2
	exit 1
}

# Seed "previous" compatible trees (fake older versions) so rollback has targets.
mkdir -p "${DISPOSABLE}/releases/biopentra-upr-host/0.1.4" \
	"${DISPOSABLE}/releases/universal-product-reviews/0.2.9"
echo "previous-host" > "${DISPOSABLE}/releases/biopentra-upr-host/0.1.4/marker.txt"
echo "previous-upr" > "${DISPOSABLE}/releases/universal-product-reviews/0.2.9/marker.txt"
# Manifests for previous trees
( cd "${DISPOSABLE}/releases/biopentra-upr-host/0.1.4" && sha256sum marker.txt > "../0.1.4.SHA256SUMS" )
( cd "${DISPOSABLE}/releases/universal-product-reviews/0.2.9" && sha256sum marker.txt > "../0.2.9.SHA256SUMS" )

# Point plugins at previous (starting state)
ln -sfn "${DISPOSABLE}/releases/biopentra-upr-host/0.1.4" "${DISPOSABLE}/plugins/biopentra-upr-host"
ln -sfn "${DISPOSABLE}/releases/universal-product-reviews/0.2.9" "${DISPOSABLE}/plugins/universal-product-reviews"
mkdir -p "${DISPOSABLE}/pointers/biopentra-upr-host" "${DISPOSABLE}/pointers/universal-product-reviews"
ln -sfn "${DISPOSABLE}/releases/biopentra-upr-host/0.1.4" "${DISPOSABLE}/pointers/biopentra-upr-host/current"
ln -sfn "${DISPOSABLE}/releases/universal-product-reviews/0.2.9" "${DISPOSABLE}/pointers/universal-product-reviews/current"

# Safety flags: emails disabled, pause recorded, pilot deny, empty allowlist
cat > "${DISPOSABLE}/safety/state.env" <<'EOF'
invitation_emails_enabled=false
emergency_pause_recorded=true
pilot_invitation_sending_authorised=false
pilot_order_id_allowlist_empty=true
EOF

export PAIR_ROOT="${DISPOSABLE}"
export PLUGINS_LINK_ROOT="${DISPOSABLE}/plugins"
export HOST_SLUG=biopentra-upr-host
export HOST_VERSION=0.1.5
export UPR_SLUG=universal-product-reviews
export UPR_VERSION=0.3.0

export WORKER_SUSPEND_CMD="touch '${STATE}/suspended' && rm -f '${STATE}/running-send'"
export WORKER_RESUME_CMD="rm -f '${STATE}/suspended'"
export WORKER_PROOF_CMD="test -f '${STATE}/suspended' && test ! -f '${STATE}/running-send' && echo upr_send_active=0"

export PREFLIGHT_CMD="grep -qx 'invitation_emails_enabled=false' '${DISPOSABLE}/safety/state.env' && grep -qx 'pilot_invitation_sending_authorised=false' '${DISPOSABLE}/safety/state.env' && grep -qx 'pilot_order_id_allowlist_empty=true' '${DISPOSABLE}/safety/state.env' && grep -qx 'emergency_pause_recorded=true' '${DISPOSABLE}/safety/state.env' && echo PREFLIGHT_OK"
export CONFIRM_EMAILS_DISABLED_CMD="grep -qx 'invitation_emails_enabled=false' '${DISPOSABLE}/safety/state.env' && echo EMAILS_DISABLED_OK"
export POST_VERIFY_CMD="test -f '${DISPOSABLE}/plugins/universal-product-reviews/release.meta.json' && test -f '${DISPOSABLE}/plugins/biopentra-upr-host/biopentra-upr-host.php' && grep -q \"Version: 0.1.5\" '${DISPOSABLE}/plugins/biopentra-upr-host/biopentra-upr-host.php' && echo POST_VERIFY_OK"
export ROLLBACK_VERIFY_CMD="test -f '${DISPOSABLE}/plugins/biopentra-upr-host/marker.txt' && test -f '${DISPOSABLE}/plugins/universal-product-reviews/marker.txt' && echo ROLLBACK_VERIFY_OK"
export MAINTENANCE_ON_CMD="touch '${DISPOSABLE}/safety/maintenance'"
export MAINTENANCE_OFF_CMD="rm -f '${DISPOSABLE}/safety/maintenance'"

echo "==> Stage packages"
bash "${PAIR_SCRIPTS}/stage-release.sh" biopentra-upr-host 0.1.5 "${HOST_ZIP}" "${HOST_SUMS}"
bash "${PAIR_SCRIPTS}/stage-release.sh" universal-product-reviews 0.3.0 "${UPR_ZIP}" "${UPR_SUMS}"

echo "==> Transition"
bash "${PAIR_SCRIPTS}/pair-transition.sh"

# Assert pointers
readlink -f "${DISPOSABLE}/plugins/biopentra-upr-host" | grep -q '/0.1.5$'
readlink -f "${DISPOSABLE}/plugins/universal-product-reviews" | grep -q '/0.3.0$'
test ! -f "${STATE}/suspended"  # resumed
test -f "${DISPOSABLE}/plugins/universal-product-reviews/release.meta.json"
echo "OK transition"

echo "==> Rollback"
bash "${PAIR_SCRIPTS}/pair-rollback.sh"
readlink -f "${DISPOSABLE}/plugins/biopentra-upr-host" | grep -q '/0.1.4$'
readlink -f "${DISPOSABLE}/plugins/universal-product-reviews" | grep -q '/0.2.9$'
test ! -f "${STATE}/suspended"
grep -qx 'invitation_emails_enabled=false' "${DISPOSABLE}/safety/state.env"
echo "OK rollback"

echo "==> Fail-closed: missing suspend aborts before switch"
export WORKER_SUSPEND_CMD='echo SUSPEND_FAIL; false'
# Re-stage target state as previous for a fresh attempt
ln -sfn "${DISPOSABLE}/releases/biopentra-upr-host/0.1.4" "${DISPOSABLE}/plugins/biopentra-upr-host"
if bash "${PAIR_SCRIPTS}/pair-transition.sh"; then
	echo "ERROR: expected abort on suspend failure" >&2
	exit 1
fi
echo "OK suspend failure aborts"

echo "==> Disposable rehearsal passed (DEV WordPress untouched)"
