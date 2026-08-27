#!/usr/bin/env bash
# Shared helpers for UPR+host coordinated pair transition (generic ops).
# shellcheck shell=bash
set -euo pipefail

pair_die() { echo "PAIR FATAL: $*" >&2; exit 1; }

pair_require_config() {
	[[ -n "${PAIR_ROOT:-}" ]] || pair_die "PAIR_ROOT unset"
	[[ -n "${PLUGINS_LINK_ROOT:-}" ]] || pair_die "PLUGINS_LINK_ROOT unset"
	[[ -n "${WORKER_SUSPEND_CMD:-}" ]] || pair_die "WORKER_SUSPEND_CMD unset (mandatory)"
	[[ -n "${WORKER_RESUME_CMD:-}" ]] || pair_die "WORKER_RESUME_CMD unset (mandatory)"
	[[ -n "${WORKER_PROOF_CMD:-}" ]] || pair_die "WORKER_PROOF_CMD unset (mandatory)"

	# Defaults only for the current production host identity. Superseded identity refused.
	[[ -n "${HOST_VERSION:-}" ]] || HOST_VERSION="0.1.1"
	[[ -n "${UPR_VERSION:-}" ]] || UPR_VERSION="0.3.0"
	[[ -n "${HOST_SLUG:-}" ]] || HOST_SLUG="upr-host-adapter"
	[[ -n "${UPR_SLUG:-}" ]] || UPR_SLUG="universal-product-reviews"

	if [[ "${HOST_SLUG}" == "biopentra-upr-host" || "${HOST_VERSION}" == "0.1.5" ]]; then
		pair_die "superseded host identity refused (HOST_SLUG=${HOST_SLUG} HOST_VERSION=${HOST_VERSION}); use upr-host-adapter / 0.1.1"
	fi
	if [[ "${HOST_SLUG}" != "upr-host-adapter" ]]; then
		pair_die "HOST_SLUG must be upr-host-adapter (got ${HOST_SLUG})"
	fi
	if [[ "${HOST_VERSION}" != "0.1.1" ]]; then
		pair_die "HOST_VERSION must be 0.1.1 (got ${HOST_VERSION})"
	fi
	if [[ "${UPR_SLUG}" != "universal-product-reviews" ]]; then
		pair_die "UPR_SLUG must be universal-product-reviews (got ${UPR_SLUG})"
	fi
	if [[ "${UPR_VERSION}" != "0.3.0" ]]; then
		pair_die "UPR_VERSION must be 0.3.0 (got ${UPR_VERSION})"
	fi

	# previous = restore prior tree; absent = remove live link (first-install rollback).
	[[ -n "${PAIR_PREVIOUS_MODE:-}" ]] || PAIR_PREVIOUS_MODE="previous"
	case "${PAIR_PREVIOUS_MODE}" in
		previous|absent) ;;
		*) pair_die "PAIR_PREVIOUS_MODE must be previous|absent (got ${PAIR_PREVIOUS_MODE})" ;;
	esac
}

pair_release_dir() {
	local slug="$1" version="$2"
	echo "${PAIR_ROOT}/releases/${slug}/${version}"
}

pair_pointer() {
	local slug="$1" name="$2"
	echo "${PAIR_ROOT}/pointers/${slug}/${name}"
}

pair_sha256_file() {
	local f="$1"
	if command -v sha256sum >/dev/null 2>&1; then
		sha256sum "${f}" | awk '{print $1}'
	else
		shasum -a 256 "${f}" | awk '{print $1}'
	fi
}

pair_verify_tree_manifest() {
	local tree="$1" manifest="$2"
	[[ -f "${manifest}" ]] || pair_die "missing manifest ${manifest}"
	[[ -d "${tree}" ]] || pair_die "missing tree ${tree}"
	while read -r sum rel; do
		[[ -n "${sum}" ]] || continue
		[[ "${rel}" == *"*"* ]] && continue
		local path="${tree}/${rel}"
		[[ -f "${path}" ]] || pair_die "manifest file missing: ${rel}"
		local got
		got="$(pair_sha256_file "${path}")"
		[[ "${got}" == "${sum}" ]] || pair_die "SHA mismatch ${rel}: got ${got} want ${sum}"
	done < <(awk 'NF>=2 {print $1, $2}' "${manifest}")
}

pair_run() {
	echo "+ $*"
	# shellcheck disable=SC2086
	eval "$@"
}

pair_suspend_workers() {
	echo "==> Suspend workers (mandatory)"
	pair_run "${WORKER_SUSPEND_CMD}"
	pair_proof_no_upr_send
}

pair_resume_workers() {
	echo "==> Resume workers (mandatory)"
	pair_run "${WORKER_RESUME_CMD}"
}

pair_proof_no_upr_send() {
	echo "==> Proof no UPR send work active (mandatory gate)"
	local out
	# shellcheck disable=SC2086
	out="$(eval "${WORKER_PROOF_CMD}")" || pair_die "WORKER_PROOF_CMD failed (cannot prove idle send work)"
	echo "${out}"
	echo "${out}" | grep -Eq 'upr_send_active=0\b' || pair_die "proof did not report upr_send_active=0"
}

pair_preflight_safety() {
	echo "==> Preflight safety (emails off, pilot deny, manifests)"
	[[ -n "${PREFLIGHT_CMD:-}" ]] || pair_die "PREFLIGHT_CMD unset"
	# shellcheck disable=SC2086
	pair_run "${PREFLIGHT_CMD}"
}

pair_switch_slug() {
	local slug="$1" version="$2"
	local tree pointer_current pointer_previous link
	tree="$(pair_release_dir "${slug}" "${version}")"
	[[ -d "${tree}" ]] || pair_die "staged tree missing: ${tree}"
	pointer_current="$(pair_pointer "${slug}" current)"
	pointer_previous="$(pair_pointer "${slug}" previous)"
	link="${PLUGINS_LINK_ROOT}/${slug}"
	mkdir -p "$(dirname "${pointer_current}")"

	if [[ -e "${link}" || -L "${link}" ]]; then
		local old
		old="$(readlink -f "${link}" 2>/dev/null || true)"
		if [[ -n "${old}" ]]; then
			ln -sfn "${old}" "${pointer_previous}"
		fi
	else
		# First install: mark previous as absent so rollback can remove the link.
		rm -f "${pointer_previous}"
		: > "${pointer_previous}.absent"
	fi
	ln -sfn "${tree}" "${pointer_current}"
	local tmp="${link}.pair-new.$$"
	ln -sfn "${tree}" "${tmp}"
	mv -Tf "${tmp}" "${link}"
	echo "placed ${slug} -> ${tree}"
}

pair_restore_previous() {
	local slug="$1"
	local pointer_previous pointer_current link prev
	pointer_previous="$(pair_pointer "${slug}" previous)"
	pointer_current="$(pair_pointer "${slug}" current)"
	link="${PLUGINS_LINK_ROOT}/${slug}"

	if [[ "${PAIR_PREVIOUS_MODE}" == "absent" || -f "${pointer_previous}.absent" ]]; then
		echo "removing ${slug} to absent (first-install rollback)"
		rm -f "${link}" "${pointer_current}" "${pointer_previous}" "${pointer_previous}.absent"
		return 0
	fi

	[[ -L "${pointer_previous}" || -e "${pointer_previous}" ]] || pair_die "no previous pointer for ${slug}"
	prev="$(readlink -f "${pointer_previous}")"
	[[ -d "${prev}" ]] || pair_die "previous tree missing for ${slug}: ${prev}"
	local tmp="${link}.pair-rb.$$"
	ln -sfn "${prev}" "${tmp}"
	mv -Tf "${tmp}" "${link}"
	ln -sfn "${prev}" "${pointer_current}"
	echo "restored ${slug} previous -> ${prev}"
}
