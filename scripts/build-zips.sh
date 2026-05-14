#!/usr/bin/env bash
# Build deployable ZIPs for each plugin under plugins/.
# Output: builds/zips/{slug}-{version}.zip with correct WordPress root folder inside.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PLUGINS_DIR="$ROOT/plugins"
OUT_DIR="$ROOT/builds/zips"
STAGE_ROOT="$(mktemp -d "${TMPDIR:-/tmp}/biopentra-zip-build.XXXXXX")"

cleanup() {
	rm -rf "$STAGE_ROOT"
}
trap cleanup EXIT

mkdir -p "$OUT_DIR"

# Create a ZIP with top-level folder `slug` (WordPress expects plugin root inside archive).
write_plugin_zip() {
	local stage_parent="$1"
	local slug="$2"
	local zip_path="$3"
	if command -v zip >/dev/null 2>&1; then
		(
			cd "$stage_parent" || exit 1
			zip -qr "$zip_path" "$slug"
		)
		return
	fi
	if command -v python3 >/dev/null 2>&1; then
		PYTHONPATH= python3 - "$stage_parent" "$slug" "$zip_path" <<'PY'
import sys, zipfile
from pathlib import Path
stage_parent, slug, out = Path(sys.argv[1]), sys.argv[2], Path(sys.argv[3])
root = stage_parent / slug
with zipfile.ZipFile(out, "w", compression=zipfile.ZIP_DEFLATED) as zf:
    for path in root.rglob("*"):
        if path.is_file():
            zf.write(path, path.relative_to(stage_parent).as_posix())
PY
		return
	fi
	echo "error: install zip or python3 to build archives" >&2
	exit 1
}

extract_version() {
	local file="$1"
	local line
	line="$(grep -m1 -iE '^[[:space:]]*\*?[[:space:]]*Version:[[:space:]]*' "$file" 2>/dev/null || true)"
	if [[ -z "$line" ]]; then
		echo "0.0.0"
		return
	fi
	echo "$line" | sed -E 's/^[[:space:]]*\*?[[:space:]]*Version:[[:space:]]*//I' | tr -d '\r' | sed 's/[[:space:]]*$//'
}

main_php_for_slug() {
	local slug="$1"
	local dir="$2"
	local candidate="$dir/${slug}.php"
	if [[ -f "$candidate" ]]; then
		echo "$candidate"
		return
	fi
	# Fallback: any top-level PHP with a Plugin Name header
	local f
	while IFS= read -r -d '' f; do
		if grep -q "Plugin Name:" "$f" 2>/dev/null; then
			echo "$f"
			return
		fi
	done < <(find "$dir" -maxdepth 1 -name '*.php' -type f -print0 2>/dev/null)
	echo ""
}

build_one() {
	local slug="$1"
	local src="$PLUGINS_DIR/$slug"
	local main
	main="$(main_php_for_slug "$slug" "$src")"
	if [[ -z "$main" || ! -f "$main" ]]; then
		echo "skip: $slug (no main plugin file found)" >&2
		return
	fi
	local version
	version="$(extract_version "$main")"
	if [[ -z "$version" ]]; then
		version="0.0.0"
	fi

	local pack="$STAGE_ROOT/pack-$slug"
	rm -rf "$pack"
	mkdir -p "$pack/$slug"

	rsync -a \
		--exclude '.git/' \
		--exclude '.git' \
		--exclude 'node_modules/' \
		--exclude 'node_modules' \
		--exclude 'tests/' \
		--exclude 'tests' \
		--exclude '.DS_Store' \
		--exclude 'Thumbs.db' \
		--exclude '.env' \
		--exclude '.env.*' \
		--exclude '*.swp' \
		--exclude '.phpunit.result.cache' \
		--exclude 'package-lock.json' \
		--exclude 'yarn.lock' \
		--exclude 'pnpm-lock.yaml' \
		"${src}/" "$pack/$slug/"

	local zip_path="$OUT_DIR/${slug}-${version}.zip"
	rm -f "$zip_path"
	write_plugin_zip "$pack" "$slug" "$zip_path"
	echo "built: $zip_path"
}

if [[ ! -d "$PLUGINS_DIR" ]]; then
	echo "Missing plugins dir: $PLUGINS_DIR" >&2
	exit 1
fi

shopt -s nullglob
for dir in "$PLUGINS_DIR"/*/; do
	[[ -d "$dir" ]] || continue
	slug="$(basename "$dir")"
	build_one "$slug"
done

echo "Done. ZIPs in: $OUT_DIR"
