# Shared monorepo plugin release helpers (sourced by build scripts).
# shellcheck shell=bash

# Plugins never included in ./scripts/build-zips.sh default loop.
RELEASE_SKIP_PLUGINS=(
	biopentra-contact-inbox
)

release_is_skipped_plugin() {
	local slug="$1"
	local s
	for s in "${RELEASE_SKIP_PLUGINS[@]}"; do
		if [[ "${slug}" == "${s}" ]]; then
			return 0
		fi
	done
	if [[ -f "${PLUGINS_DIR:-}/${slug}/DEPRECATED.md" ]]; then
		return 0
	fi
	return 1
}

# Copy plugin tree to staging dir with production exclusions (tar stream).
release_copy_plugin_production() {
	local source_dir="$1"
	local dest_dir="$2"
	mkdir -p "${dest_dir}"
	tar -C "${source_dir}" \
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
		| tar -C "${dest_dir}" -xf -
}

release_write_zip() {
	local stage_parent="$1"
	local slug="$2"
	local zip_path="$3"
	if command -v zip >/dev/null 2>&1; then
		(
			cd "${stage_parent}" || exit 1
			zip -qr "${zip_path}" "${slug}"
		)
		return
	fi
	python3 - "${stage_parent}" "${zip_path}" "${slug}" <<'PY'
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
}

release_extract_version() {
	local file="$1"
	local line
	line="$(grep -m1 -iE '^[[:space:]]*\*?[[:space:]]*Version:[[:space:]]*' "${file}" 2>/dev/null || true)"
	if [[ -z "${line}" ]]; then
		echo "0.0.0"
		return
	fi
	echo "${line}" | sed -E 's/^[[:space:]]*\*?[[:space:]]*Version:[[:space:]]*//I' | tr -d '\r' | sed 's/[[:space:]]*$//'
}

release_main_php_for_slug() {
	local slug="$1"
	local dir="$2"
	local candidate="${dir}/${slug}.php"
	if [[ -f "${candidate}" ]]; then
		echo "${candidate}"
		return
	fi
	local f
	while IFS= read -r -d '' f; do
		if grep -q "Plugin Name:" "${f}" 2>/dev/null; then
			echo "${f}"
			return
		fi
	done < <(find "${dir}" -maxdepth 1 -name '*.php' -type f -print0 2>/dev/null)
	echo ""
}

release_read_version_constant() {
	local slug="$1"
	local main_file="$2"
	case "${slug}" in
		wc-inventory-overview)
			grep -E "define\s*\(\s*'WC_INVENTORY_OVERVIEW_VERSION'" "${main_file}" \
				| head -n 1 \
				| sed -E "s/.*'([^']+)'.*/\1/"
			;;
		biopentra-storefront)
			grep -E "define\s*\(\s*'BIOPENTRA_STOREFRONT_VERSION'" "${main_file}" \
				| head -n 1 \
				| sed -E "s/.*'([^']+)'.*/\1/"
			;;
		*)
			echo ""
			;;
	esac
}
