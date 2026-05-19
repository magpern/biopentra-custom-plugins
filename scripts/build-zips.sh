#!/usr/bin/env bash
# Build production ZIPs for each non-deprecated plugin under plugins/.
# Output: builds/zips/{slug}-{version}.zip
# Skips plugins in RELEASE_SKIP_PLUGINS or with plugins/{slug}/DEPRECATED.md
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
# shellcheck source=lib/release-common.sh
source "${ROOT}/scripts/lib/release-common.sh"

PLUGINS_DIR="${ROOT}/plugins"
OUT_DIR="${ROOT}/builds/zips"
STAGE_ROOT="$(mktemp -d "${TMPDIR:-/tmp}/biopentra-zip-build.XXXXXX")"

cleanup() {
	rm -rf "${STAGE_ROOT}"
}
trap cleanup EXIT

mkdir -p "${OUT_DIR}"

build_one() {
	local slug="$1"
	local src="${PLUGINS_DIR}/${slug}"

	if release_is_skipped_plugin "${slug}"; then
		echo "skip: ${slug} (deprecated / excluded from release builds)"
		return
	fi

	local main
	main="$(release_main_php_for_slug "${slug}" "${src}")"
	if [[ -z "${main}" || ! -f "${main}" ]]; then
		echo "skip: ${slug} (no main plugin file found)" >&2
		return
	fi

	local version
	version="$(release_extract_version "${main}")"
	local version_const
	version_const="$(release_read_version_constant "${slug}" "${main}")"
	if [[ -n "${version_const}" && "${version}" != "${version_const}" ]]; then
		echo "ERROR: ${slug} header Version (${version}) != constant (${version_const})" >&2
		return 1
	fi

	local pack="${STAGE_ROOT}/pack-${slug}"
	rm -rf "${pack}"
	mkdir -p "${pack}/${slug}"

	release_copy_plugin_production "${src}" "${pack}/${slug}"

	local zip_path="${OUT_DIR}/${slug}-${version}.zip"
	rm -f "${zip_path}"
	release_write_zip "${pack}" "${slug}" "${zip_path}"
	echo "built: ${zip_path}"
}

if [[ ! -d "${PLUGINS_DIR}" ]]; then
	echo "Missing plugins dir: ${PLUGINS_DIR}" >&2
	exit 1
fi

shopt -s nullglob
for dir in "${PLUGINS_DIR}"/*/; do
	[[ -d "${dir}" ]] || continue
	slug="$(basename "${dir}")"
	build_one "${slug}"
done

echo "Done. ZIPs in: ${OUT_DIR}"
