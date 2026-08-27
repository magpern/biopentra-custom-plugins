#!/usr/bin/env bash
#
# Coordinated pair transition: host then UPR (fixed order).
# Never enables emails, allowlist, or reconciliation.
#
# Requires config env (see config.example.env). Fail-closed.
#
set -euo pipefail
ROOT="$(cd "$(dirname "$0")" && pwd)"
# shellcheck source=lib.sh
source "${ROOT}/lib.sh"

pair_require_config

HOST_TREE="$(pair_release_dir "${HOST_SLUG}" "${HOST_VERSION}")"
UPR_TREE="$(pair_release_dir "${UPR_SLUG}" "${UPR_VERSION}")"
[[ -d "${HOST_TREE}" ]] || pair_die "host not staged: ${HOST_TREE}"
[[ -d "${UPR_TREE}" ]] || pair_die "upr not staged: ${UPR_TREE}"

pair_verify_tree_manifest "${HOST_TREE}" "${HOST_TREE}.SHA256SUMS"
pair_verify_tree_manifest "${UPR_TREE}" "${UPR_TREE}.SHA256SUMS"

# UPR package meta must exist (host 0.1.5 pin)
[[ -f "${UPR_TREE}/release.meta.json" ]] || pair_die "staged UPR missing release.meta.json"

pair_preflight_safety
pair_suspend_workers

if [[ -n "${MAINTENANCE_ON_CMD:-}" ]]; then
	echo "==> Maintenance on (optional)"
	pair_run "${MAINTENANCE_ON_CMD}"
fi

echo "==> Switch host ${HOST_VERSION}"
pair_switch_slug "${HOST_SLUG}" "${HOST_VERSION}"

echo "==> Switch UPR ${UPR_VERSION}"
pair_switch_slug "${UPR_SLUG}" "${UPR_VERSION}"

echo "==> Post-switch verify"
[[ -n "${POST_VERIFY_CMD:-}" ]] || pair_die "POST_VERIFY_CMD unset"
pair_run "${POST_VERIFY_CMD}"

# Emails must still be disabled
[[ -n "${CONFIRM_EMAILS_DISABLED_CMD:-}" ]] || pair_die "CONFIRM_EMAILS_DISABLED_CMD unset"
pair_run "${CONFIRM_EMAILS_DISABLED_CMD}"

if [[ -n "${MAINTENANCE_OFF_CMD:-}" ]]; then
	echo "==> Maintenance off"
	pair_run "${MAINTENANCE_OFF_CMD}"
fi

pair_resume_workers
echo "==> Pair transition complete (emails remain disabled; pilot not authorised by this script)"
