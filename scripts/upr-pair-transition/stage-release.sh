#!/usr/bin/env bash
# Stage an immutable release tree from a verified ZIP + SHA256SUMS into PAIR_ROOT.
# Usage: stage-release.sh <slug> <version> <zip> <sha256sums>
set -euo pipefail
ROOT="$(cd "$(dirname "$0")" && pwd)"
# shellcheck source=lib.sh
source "${ROOT}/lib.sh"

[[ $# -eq 4 ]] || pair_die "usage: $0 <slug> <version> <zip> <sha256sums>"
pair_require_config

SLUG="$1"
VERSION="$2"
ZIP="$3"
SUMS="$4"

[[ -f "${ZIP}" ]] || pair_die "missing zip ${ZIP}"
[[ -f "${SUMS}" ]] || pair_die "missing sums ${SUMS}"

echo "==> Verify ZIP checksum"
ZIP_BASE="$(basename "${ZIP}")"
DIGEST_LINE="$(grep -E "[[:space:]]${ZIP_BASE}\$" "${SUMS}" | head -n1 || true)"
[[ -n "${DIGEST_LINE}" ]] || pair_die "${ZIP_BASE} not in ${SUMS}"
echo "${DIGEST_LINE}" | (
	cd "$(dirname "${ZIP}")"
	if command -v sha256sum >/dev/null 2>&1; then sha256sum -c -; else shasum -a 256 -c -; fi
)

DEST="$(pair_release_dir "${SLUG}" "${VERSION}")"
mkdir -p "$(dirname "${DEST}")"
rm -rf "${DEST}.tmp"
mkdir -p "${DEST}.tmp"
python3 - "${ZIP}" "${DEST}.tmp" <<'PY'
import sys, zipfile
from pathlib import Path
zf = zipfile.ZipFile(sys.argv[1])
dest = Path(sys.argv[2])
zf.extractall(dest)
PY

# Expect single top-level slug dir
INNER="$(find "${DEST}.tmp" -maxdepth 1 -mindepth 1 -type d | head -n1)"
[[ -n "${INNER}" ]] || pair_die "zip has no top-level directory"
[[ "$(basename "${INNER}")" == "${SLUG}" ]] || pair_die "zip root $(basename "${INNER}") != ${SLUG}"

rm -rf "${DEST}"
mv "${INNER}" "${DEST}"
rm -rf "${DEST}.tmp"

# Write per-file manifest for staged tree
MANIFEST="${DEST}.SHA256SUMS"
(
	cd "${DEST}"
	if command -v sha256sum >/dev/null 2>&1; then
		find . -type f -print0 | sort -z | xargs -0 sha256sum
	else
		find . -type f -print0 | sort -z | xargs -0 shasum -a 256
	fi
) > "${MANIFEST}"

echo "staged ${DEST}"
echo "manifest ${MANIFEST}"
