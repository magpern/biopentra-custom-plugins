#!/usr/bin/env bash
#
# Fixed pair rollback: deactivate host then UPR, then restore previous/absent pointers.
# Emails remain disabled; workers suspended throughout.
#
set -euo pipefail
ROOT="$(cd "$(dirname "$0")" && pwd)"
# shellcheck source=lib.sh
source "${ROOT}/lib.sh"

pair_require_config

[[ -n "${DEACTIVATE_HOST_CMD:-}" ]] || pair_die "DEACTIVATE_HOST_CMD unset"
[[ -n "${DEACTIVATE_UPR_CMD:-}" ]] || pair_die "DEACTIVATE_UPR_CMD unset"

pair_preflight_safety
pair_suspend_workers

if [[ -n "${MAINTENANCE_ON_CMD:-}" ]]; then
	echo "==> Maintenance on (optional)"
	pair_run "${MAINTENANCE_ON_CMD}"
fi

echo "==> Deactivate host first"
pair_run "${DEACTIVATE_HOST_CMD}"

echo "==> Deactivate UPR second"
pair_run "${DEACTIVATE_UPR_CMD}"

echo "==> Restore UPR previous/absent (filesystem)"
pair_restore_previous "${UPR_SLUG}"

echo "==> Restore host previous/absent (filesystem)"
pair_restore_previous "${HOST_SLUG}"

echo "==> Post-rollback verify"
[[ -n "${ROLLBACK_VERIFY_CMD:-}" ]] || pair_die "ROLLBACK_VERIFY_CMD unset"
pair_run "${ROLLBACK_VERIFY_CMD}"

[[ -n "${CONFIRM_EMAILS_DISABLED_CMD:-}" ]] || pair_die "CONFIRM_EMAILS_DISABLED_CMD unset"
pair_run "${CONFIRM_EMAILS_DISABLED_CMD}"

if [[ -n "${MAINTENANCE_OFF_CMD:-}" ]]; then
	echo "==> Maintenance off"
	pair_run "${MAINTENANCE_OFF_CMD}"
fi

pair_resume_workers
echo "==> Pair rollback complete (emails remain disabled)"
