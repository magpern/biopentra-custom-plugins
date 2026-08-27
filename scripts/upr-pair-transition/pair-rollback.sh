#!/usr/bin/env bash
#
# Fixed pair rollback: UPR previous, then host previous (reverse of deploy).
# Emails remain disabled; workers suspended throughout switches.
#
set -euo pipefail
ROOT="$(cd "$(dirname "$0")" && pwd)"
# shellcheck source=lib.sh
source "${ROOT}/lib.sh"

pair_require_config

pair_preflight_safety
pair_suspend_workers

if [[ -n "${MAINTENANCE_ON_CMD:-}" ]]; then
	echo "==> Maintenance on (optional)"
	pair_run "${MAINTENANCE_ON_CMD}"
fi

echo "==> Restore UPR previous (fixed order step 1)"
pair_restore_previous "${UPR_SLUG}"

echo "==> Restore host previous (fixed order step 2)"
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
