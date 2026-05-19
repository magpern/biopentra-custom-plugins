#!/usr/bin/env bash
#
# Release audit for one monorepo plugin.
# Usage: release-audit-plugin.sh <plugin-slug> [expected-version]
#
set -euo pipefail

if [[ $# -lt 1 || -z "${1:-}" ]]; then
	echo "usage: $0 <plugin-slug> [expected-version]" >&2
	exit 1
fi

readonly PLUGIN_SLUG="$1"
readonly EXPECTED_VERSION="${2:-}"

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
VERIFY_ZIP="${SCRIPT_DIR}/lib/verify-release-zip.py"
BUILD_ONE="${SCRIPT_DIR}/build-one-plugin-zip.sh"
PLUGIN_DIR="${ROOT}/plugins/${PLUGIN_SLUG}"
OUT_DIR="${ROOT}/builds/zips"

fail() {
	echo "ERROR: $*" >&2
	exit 1
}

warn() {
	echo "    WARN: $*"
}

echo "==> Release audit: ${PLUGIN_SLUG}"
echo "    Root: ${ROOT}"

[[ -d "${PLUGIN_DIR}" ]] || fail "Missing plugin dir: ${PLUGIN_DIR}"

MAIN_FILE="${PLUGIN_DIR}/${PLUGIN_SLUG}.php"
[[ -f "${MAIN_FILE}" ]] || fail "Missing main file: ${MAIN_FILE}"

echo ""
echo "==> Repository checks"

HEADER_VERSION="$(
	grep -E '^\s*\*\s*Version:\s*' "${MAIN_FILE}" \
		| head -n 1 \
		| sed -E 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*//'
)"

# shellcheck source=lib/release-common.sh
source "${SCRIPT_DIR}/lib/release-common.sh"

if release_is_skipped_plugin "${PLUGIN_SLUG}"; then
	fail "${PLUGIN_SLUG} is deprecated or excluded from release builds"
fi

VERSION_CONST="$(release_read_version_constant "${PLUGIN_SLUG}" "${MAIN_FILE}")"

	case "${PLUGIN_SLUG}" in
	wc-inventory-overview)
		[[ -f "${PLUGIN_DIR}/CHANGELOG.md" ]] || fail "Missing plugins/${PLUGIN_SLUG}/CHANGELOG.md"
		[[ -f "${PLUGIN_DIR}/readme.txt" ]] || fail "Missing plugins/${PLUGIN_SLUG}/readme.txt"
		[[ -f "${PLUGIN_DIR}/LICENSE" ]] || fail "Missing plugins/${PLUGIN_SLUG}/LICENSE"
		[[ -f "${PLUGIN_DIR}/includes/class-github-updater.php" ]] || fail "Missing includes/class-github-updater.php"
		[[ -d "${PLUGIN_DIR}/cli" ]] && echo "    cli/: present in repo (excluded from production ZIP)"
		;;
	biopentra-storefront)
		[[ -f "${PLUGIN_DIR}/readme.txt" ]] || fail "Missing plugins/${PLUGIN_SLUG}/readme.txt"
		[[ -f "${PLUGIN_DIR}/LICENSE" ]] || fail "Missing plugins/${PLUGIN_SLUG}/LICENSE"
		[[ -f "${PLUGIN_DIR}/includes/class-github-updater.php" ]] || fail "Missing includes/class-github-updater.php"
		[[ -d "${PLUGIN_DIR}/scripts" ]] && echo "    scripts/: present in repo (excluded from production ZIP)"
		;;
esac

[[ -n "${VERSION_CONST}" ]] || fail "No version constant mapping for ${PLUGIN_SLUG}"
[[ "${HEADER_VERSION}" == "${VERSION_CONST}" ]] \
	|| fail "Version mismatch: header=${HEADER_VERSION} const=${VERSION_CONST}"

if [[ -n "${EXPECTED_VERSION}" && "${HEADER_VERSION}" != "${EXPECTED_VERSION}" ]]; then
	fail "Expected version ${EXPECTED_VERSION} but plugin has ${HEADER_VERSION}"
fi

echo "    Version: ${HEADER_VERSION}"

if command -v php >/dev/null 2>&1; then
	echo "==> PHP syntax lint (${PLUGIN_SLUG})"
	LINT_FAIL=0
	while IFS= read -r -d '' php_file; do
		if ! php -l "${php_file}" >/dev/null 2>&1; then
			echo "    FAIL: ${php_file}" >&2
			LINT_FAIL=1
		fi
	done < <(find "${PLUGIN_DIR}" -name '*.php' -print0)
	if [[ "${LINT_FAIL}" -ne 0 ]]; then
		fail "PHP syntax lint failed"
	fi
	echo "    PHP lint: OK"
else
	warn "php not in PATH; skipping local syntax lint"
fi

NOTES="${ROOT}/docs/GITHUB_RELEASE_NOTES_${PLUGIN_SLUG}_${HEADER_VERSION//./_}.md"
if [[ ! -f "${NOTES}" ]]; then
	ALT="${ROOT}/docs/GITHUB_RELEASE_NOTES_${PLUGIN_SLUG}.md"
	if [[ -f "${ALT}" ]]; then
		NOTES="${ALT}"
	else
		fail "Missing release notes: docs/GITHUB_RELEASE_NOTES_${PLUGIN_SLUG}_${HEADER_VERSION//./_}.md"
	fi
fi
echo "    Release notes: ${NOTES}"

[[ -f "${ROOT}/.github/workflows/release-${PLUGIN_SLUG}.yml" ]] \
	|| fail "Missing .github/workflows/release-${PLUGIN_SLUG}.yml"

echo "    Repository checks passed"

echo ""
echo "==> Release artifact checks"

ZIP_PATH="${OUT_DIR}/${PLUGIN_SLUG}-${HEADER_VERSION}.zip"
if [[ ! -f "${ZIP_PATH}" ]]; then
	echo "    Building zip (not found: ${ZIP_PATH})"
	bash "${BUILD_ONE}" "${PLUGIN_SLUG}"
fi

[[ -f "${ZIP_PATH}" ]] || fail "ZIP not found: ${ZIP_PATH}"
echo "    Zip: ${ZIP_PATH}"

[[ -f "${VERIFY_ZIP}" ]] || fail "Missing ${VERIFY_ZIP}"
python3 "${VERIFY_ZIP}" "${ZIP_PATH}" "${PLUGIN_SLUG}" "${HEADER_VERSION}"

python3 - "${ZIP_PATH}" "${PLUGIN_SLUG}" <<'PY'
import sys
import zipfile

zip_path, slug = sys.argv[1], sys.argv[2]
forbidden = [f"{slug}/cli/", f"{slug}/scripts/", f"{slug}/docs/", f"{slug}/.github/"]
with zipfile.ZipFile(zip_path) as zf:
    names = zf.namelist()
    hits = [n for n in names if any(n.startswith(p) for p in forbidden)]
    if hits:
        print("ERROR: zip contains forbidden dev paths:", hits[:5], file=sys.stderr)
        sys.exit(1)
print("    OK: cli/, scripts/, docs/, .github/ absent from zip")
updater = f"{slug}/includes/class-github-updater.php"
if updater not in names:
    print(f"ERROR: zip missing {updater}", file=sys.stderr)
    sys.exit(1)
print("    OK: includes/class-github-updater.php present in zip")
PY

echo ""
echo "==> Release audit passed (${PLUGIN_SLUG} ${HEADER_VERSION})"
