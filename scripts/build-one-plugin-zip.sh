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
readonly PLUGINS_DIR="${ROOT}/plugins"
readonly OUT_DIR="${ROOT}/builds/zips"
readonly SOURCE="${PLUGINS_DIR}/${PLUGIN_SLUG}"
readonly VERIFY_ZIP="$(cd "$(dirname "$0")" && pwd)/lib/verify-release-zip.py"

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
STAGE_ROOT="$(mktemp -d "${TMPDIR:-/tmp}/biopentra-one-zip.XXXXXX")"

cleanup() {
	rm -rf "${STAGE_ROOT}"
}
trap cleanup EXIT

echo "==> Build production ZIP: ${PLUGIN_SLUG}"
echo "    Source: ${SOURCE}"

[[ -d "${SOURCE}" ]] || {
	echo "ERROR: Plugin directory not found: ${SOURCE}" >&2
	exit 1
}

MAIN_FILE="${SOURCE}/${PLUGIN_SLUG}.php"
if [[ ! -f "${MAIN_FILE}" ]]; then
	MAIN_FILE="$(find "${SOURCE}" -maxdepth 1 -name '*.php' -exec grep -l 'Plugin Name:' {} \; 2>/dev/null | head -n1)"
fi
[[ -f "${MAIN_FILE}" ]] || {
	echo "ERROR: No main plugin file for ${PLUGIN_SLUG}" >&2
	exit 1
}

HEADER_VERSION="$(
	grep -E '^\s*\*\s*Version:\s*' "${MAIN_FILE}" \
		| head -n 1 \
		| sed -E 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*//'
)"

VERSION_CONST=""
case "${PLUGIN_SLUG}" in
	wc-inventory-overview)
		VERSION_CONST="$(
			grep -E "define\s*\(\s*'WC_INVENTORY_OVERVIEW_VERSION'" "${MAIN_FILE}" \
				| head -n 1 \
				| sed -E "s/.*'([^']+)'.*/\1/"
		)"
		;;
esac

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
tar -C "${SOURCE}" \
	--exclude='.git' \
	--exclude='.github' \
	--exclude='vendor' \
	--exclude='node_modules' \
	--exclude='scripts' \
	--exclude='tests' \
	--exclude='docs' \
	--exclude='build' \
	--exclude='builds' \
	--exclude='cli' \
	--exclude='.phpcs-cache' \
	--exclude='.phpunit.result.cache' \
	--exclude='.env' \
	--exclude='.env.*' \
	--exclude='*.log' \
	--exclude='*.sql' \
	--exclude='*.sql.gz' \
	--exclude='*.dump' \
	--exclude='*.sqlite' \
	--exclude='.write-test' \
	--exclude='.DS_Store' \
	--exclude='Thumbs.db' \
	-cf - . \
	| tar -C "${PACKAGE_DIR}" -xf -

echo "==> Creating zip archive"
rm -f "${ZIP_PATH}"
if command -v zip >/dev/null 2>&1; then
	(
		cd "${STAGE_ROOT}"
		zip -qr "${ZIP_PATH}" "${PLUGIN_SLUG}"
	)
else
	python3 - "${STAGE_ROOT}" "${ZIP_PATH}" "${PLUGIN_SLUG}" <<'PY'
import os
import sys
import zipfile
from pathlib import Path

staging_dir = Path(sys.argv[1])
zip_path = Path(sys.argv[2])
plugin_slug = sys.argv[3]
root = staging_dir / plugin_slug

with zipfile.ZipFile(zip_path, "w", compression=zipfile.ZIP_DEFLATED) as zf:
    for dirpath, _dirnames, filenames in os.walk(root):
        for name in filenames:
            full = Path(dirpath) / name
            zf.write(full, full.relative_to(staging_dir).as_posix())
PY
fi

if [[ -f "${VERIFY_ZIP}" ]]; then
	echo "==> Verifying production zip"
	python3 "${VERIFY_ZIP}" "${ZIP_PATH}" "${PLUGIN_SLUG}" "${VERSION}"
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
