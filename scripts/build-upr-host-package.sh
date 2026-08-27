#!/usr/bin/env bash
#
# Build a deterministic biopentra-upr-host package from an immutable Git ref
# (default: annotated tag 0.1.5). Produces ZIP + SHA-256 manifest under builds/zips/.
#
# Usage: scripts/build-upr-host-package.sh [ref]
#
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
REF="${1:-0.1.5}"
OUT_DIR="${ROOT}/builds/zips"
STAGE="$(mktemp -d "${TMPDIR:-/tmp}/host-pkg.XXXXXX")"
cleanup() { rm -rf "${STAGE}"; }
trap cleanup EXIT

cd "${ROOT}"

if ! git rev-parse --verify "${REF}^{commit}" >/dev/null 2>&1; then
	echo "ERROR: unknown ref ${REF}" >&2
	exit 1
fi

COMMIT="$(git rev-parse "${REF}^{commit}")"
SLUG="biopentra-upr-host"
SOURCE_TREE="${STAGE}/src"
mkdir -p "${SOURCE_TREE}" "${OUT_DIR}"

echo "==> Exporting ${SLUG} from ${REF} (${COMMIT})"
git archive --format=tar "${COMMIT}" "plugins/${SLUG}" | tar -C "${SOURCE_TREE}" -xf -

# Flatten to plugins/<slug> layout expected by build-one helpers, or zip directly.
PLUGIN_SRC="${SOURCE_TREE}/plugins/${SLUG}"
[[ -d "${PLUGIN_SRC}" ]] || { echo "ERROR: missing plugins/${SLUG} in ${REF}" >&2; exit 1; }

HEADER_VERSION="$(
	sed -nE 's/^ \* Version:[[:space:]]*([0-9A-Za-z.-]+).*/\1/p' "${PLUGIN_SRC}/${SLUG}.php" | head -n1
)"
CONST_VERSION="$(
	sed -nE "s/.*BIOPENTRA_UPR_HOST_VERSION', '([^']+)'.*/\1/p" "${PLUGIN_SRC}/${SLUG}.php" | head -n1
)"
if [[ -z "${HEADER_VERSION}" || "${HEADER_VERSION}" != "${CONST_VERSION}" ]]; then
	echo "ERROR: version mismatch header=${HEADER_VERSION} const=${CONST_VERSION}" >&2
	exit 1
fi

# When building the production pin tag, require tag name == version.
if [[ "${REF}" == "${HEADER_VERSION}" || "${REF}" == "v${HEADER_VERSION}" ]]; then
	:
elif git rev-parse -q --verify "refs/tags/${HEADER_VERSION}" >/dev/null 2>&1; then
	TAG_PEEL="$(git rev-parse "${HEADER_VERSION}^{commit}")"
	if [[ "${TAG_PEEL}" != "${COMMIT}" ]]; then
		echo "ERROR: ref ${REF} is not the peel of tag ${HEADER_VERSION}" >&2
		exit 1
	fi
fi

ZIP_NAME="${SLUG}-${HEADER_VERSION}.zip"
ZIP_PATH="${OUT_DIR}/${ZIP_NAME}"
SUMS_PATH="${OUT_DIR}/${SLUG}-${HEADER_VERSION}.SHA256SUMS"
PKG_DIR="${STAGE}/pkg/${SLUG}"
mkdir -p "${PKG_DIR}"

# shellcheck source=lib/release-common.sh
source "${ROOT}/scripts/lib/release-common.sh"
release_copy_plugin_production "${PLUGIN_SRC}" "${PKG_DIR}"

rm -f "${ZIP_PATH}"
release_write_zip "${STAGE}/pkg" "${SLUG}" "${ZIP_PATH}"

(
	cd "${OUT_DIR}"
	if command -v sha256sum >/dev/null 2>&1; then
		sha256sum "${ZIP_NAME}" > "${SUMS_PATH}"
	else
		shasum -a 256 "${ZIP_NAME}" > "${SUMS_PATH}"
	fi
	cat "${SUMS_PATH}"
)

python3 - "${ZIP_PATH}" <<'PY'
import sys, zipfile
names = zipfile.ZipFile(sys.argv[1]).namelist()
gitty = [n for n in names if ".git" in n.split("/")]
if gitty:
    raise SystemExit("ERROR: host package contains .git paths")
print("OK: host package has", len(names), "entries")
PY

echo "==> Done: ${ZIP_PATH}"
