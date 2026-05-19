#!/usr/bin/env bash
#
# Build a production release ZIP for one plugin under plugins/.
# Usage: build-one-plugin-zip.sh <plugin-slug>
#
set -euo pipefail

if [[ $# -lt 1 || -z "${1:-}" ]]; then
	echo "usage: $0 <plugin-slug>" >&2
	exit 1
fi

readonly PLUGIN_SLUG="$1"
readonly ROOT="$(cd "$(dirname "$0")/.." && pwd)"
# shellcheck source=lib/release-common.sh
source "${ROOT}/scripts/lib/release-common.sh"

readonly PLUGINS_DIR="${ROOT}/plugins"
readonly OUT_DIR="${ROOT}/builds/zips"
readonly SOURCE="${PLUGINS_DIR}/${PLUGIN_SLUG}"
readonly VERIFY_ZIP="$(cd "$(dirname "$0")" && pwd)/lib/verify-release-zip.py"

STAGE_ROOT="$(mktemp -d "${TMPDIR:-/tmp}/biopentra-one-zip.XXXXXX")"

cleanup() {
	rm -rf "${STAGE_ROOT}"
}
trap cleanup EXIT

echo "==> Build production ZIP: ${PLUGIN_SLUG}"
echo "    Source: ${SOURCE}"

if release_is_skipped_plugin "${PLUGIN_SLUG}"; then
	echo "ERROR: ${PLUGIN_SLUG} is deprecated or excluded from release builds (see plugins/${PLUGIN_SLUG}/DEPRECATED.md)" >&2
	exit 1
fi

[[ -d "${SOURCE}" ]] || {
	echo "ERROR: Plugin directory not found: ${SOURCE}" >&2
	exit 1
}

MAIN_FILE="${SOURCE}/${PLUGIN_SLUG}.php"
if [[ ! -f "${MAIN_FILE}" ]]; then
	MAIN_FILE="$(release_main_php_for_slug "${PLUGIN_SLUG}" "${SOURCE}")"
fi
[[ -f "${MAIN_FILE}" ]] || {
	echo "ERROR: No main plugin file for ${PLUGIN_SLUG}" >&2
	exit 1
}

HEADER_VERSION="$(release_extract_version "${MAIN_FILE}")"
VERSION_CONST="$(release_read_version_constant "${PLUGIN_SLUG}" "${MAIN_FILE}")"

if [[ -n "${VERSION_CONST}" && "${HEADER_VERSION}" != "${VERSION_CONST}" ]]; then
	echo "ERROR: header Version (${HEADER_VERSION}) != constant (${VERSION_CONST})" >&2
	exit 1
fi

readonly VERSION="${HEADER_VERSION}"
readonly ZIP_PATH="${OUT_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"
readonly PACKAGE_DIR="${STAGE_ROOT}/${PLUGIN_SLUG}"

mkdir -p "${OUT_DIR}" "${PACKAGE_DIR}"

echo "    Version: ${VERSION}"
echo "    Output:  ${ZIP_PATH}"

echo "==> Copying production files"
release_copy_plugin_production "${SOURCE}" "${PACKAGE_DIR}"

echo "==> Creating zip archive"
rm -f "${ZIP_PATH}"
release_write_zip "${STAGE_ROOT}" "${PLUGIN_SLUG}" "${ZIP_PATH}"

if [[ -f "${VERIFY_ZIP}" ]]; then
	echo "==> Verifying production zip"
	set +e
	verify_out="$(python3 "${VERIFY_ZIP}" "${ZIP_PATH}" "${PLUGIN_SLUG}" "${VERSION}" 2>&1)"
	verify_code=$?
	set -e
	if [[ "${verify_code}" -eq 0 ]]; then
		echo "${verify_out}"
	elif grep -q "no verify profile" <<<"${verify_out}"; then
		echo "    WARN: no verify profile for ${PLUGIN_SLUG}; zip created without verification"
	else
		echo "${verify_out}" >&2
		exit 1
	fi
fi

echo "==> Zip summary"
python3 - "${ZIP_PATH}" "${PLUGIN_SLUG}" <<'PY'
import sys
import zipfile
from collections import Counter

zip_path, slug = sys.argv[1], sys.argv[2]
prefix = f"{slug}/"
with zipfile.ZipFile(zip_path) as zf:
    names = sorted(n for n in zf.namelist() if n.startswith(prefix))
    tops = Counter(n[len(prefix) :].split("/")[0] for n in names if n != prefix)
    print(f"    Total entries: {len(names)}")
    for key in sorted(tops):
        print(f"      {key}/  ({tops[key]} paths)")
PY

echo "==> ${ZIP_PATH}"
echo "==> Build complete."
