#!/usr/bin/env bash
#
# Disposable (non-WordPress) validation of the M3 UPR + upr-host-adapter package pair.
# Does NOT modify the DEV WordPress installation or any production system.
#
# Expects:
#   UPR_ZIP  — path to universal-product-reviews-0.3.0.zip
#   HOST_ZIP — path to upr-host-adapter-0.1.1.zip
# Optional:
#   UPR_SUMS / HOST_SUMS — SHA256SUMS files
#
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
UPR_ZIP="${UPR_ZIP:?set UPR_ZIP}"
HOST_ZIP="${HOST_ZIP:?set HOST_ZIP}"
DISPOSABLE="$(mktemp -d "${TMPDIR:-/tmp}/m3-pair-validate.XXXXXX")"
cleanup() { rm -rf "${DISPOSABLE}"; }
trap cleanup EXIT

echo "==> Disposable tree: ${DISPOSABLE}"

verify_sum() {
	local zip="$1"
	local sums="${2:-}"
	[[ -f "${zip}" ]] || { echo "ERROR: missing ${zip}" >&2; exit 1; }
	if [[ -n "${sums}" && -f "${sums}" ]]; then
		local zip_base digest
		zip_base="$(basename "${zip}")"
		digest="$(grep -E "[[:space:]]${zip_base}\$" "${sums}" | head -n1 || true)"
		[[ -n "${digest}" ]] || { echo "ERROR: ${zip_base} not listed in ${sums}" >&2; exit 1; }
		echo "${digest}" | (
			cd "$(dirname "${zip}")"
			if command -v sha256sum >/dev/null 2>&1; then
				sha256sum -c -
			else
				shasum -a 256 -c -
			fi
		)
	else
		echo "(no sums file for $(basename "${zip}"); computing digest only)"
		if command -v sha256sum >/dev/null 2>&1; then
			sha256sum "${zip}"
		else
			shasum -a 256 "${zip}"
		fi
	fi
}

echo "==> Verifying ZIP checksums (if sums provided)"
verify_sum "${UPR_ZIP}" "${UPR_SUMS:-}"
verify_sum "${HOST_ZIP}" "${HOST_SUMS:-}"

mkdir -p "${DISPOSABLE}/extract"
python3 - "${UPR_ZIP}" "${HOST_ZIP}" "${DISPOSABLE}/extract" <<'PY'
import sys, zipfile
from pathlib import Path
upr, host, dest = sys.argv[1:4]
dest = Path(dest)
for zpath, label in ((upr, "upr"), (host, "host")):
    with zipfile.ZipFile(zpath) as zf:
        zf.extractall(dest / label)
print("extracted")
PY

UPR_DIR="$(find "${DISPOSABLE}/extract/upr" -maxdepth 2 -type d -name 'universal-product-reviews' | head -n1)"
HOST_DIR="$(find "${DISPOSABLE}/extract/host" -maxdepth 2 -type d -name 'upr-host-adapter' | head -n1)"
[[ -d "${UPR_DIR}" && -d "${HOST_DIR}" ]] || { echo "ERROR: plugin dirs missing after extract" >&2; exit 1; }

test -f "${UPR_DIR}/release.meta.json" || { echo "ERROR: UPR package missing release.meta.json" >&2; exit 1; }
test ! -e "${UPR_DIR}/.git" || { echo "ERROR: UPR package contains .git" >&2; exit 1; }
test -f "${HOST_DIR}/upr-host-adapter.php" || { echo "ERROR: host bootstrap missing" >&2; exit 1; }
grep -q 'Version: 0.1.1' "${HOST_DIR}/upr-host-adapter.php" || { echo "ERROR: host Version header not 0.1.1" >&2; exit 1; }
grep -q 'Requires Plugins:.*universal-product-reviews' "${HOST_DIR}/upr-host-adapter.php" || { echo "ERROR: host must Require Plugins universal-product-reviews" >&2; exit 1; }

# Refuse superseded branded slug inside host package
if grep -Rqi 'biopentra-upr-host\|BIOPENTRA_UPR_HOST' "${HOST_DIR}" --include='*.php' 2>/dev/null; then
	echo "ERROR: host package contains superseded branded identity" >&2
	exit 1
fi

echo "==> Host pin accepts packaged UPR"
docker run --rm \
	-e UPR_PLUGIN_DIR=/upr \
	-e HOST_INCLUDES=/host/includes \
	-e EXPECT_OK=1 \
	-v "${HOST_DIR}:/host:ro" \
	-v "${UPR_DIR}:/upr:ro" \
	-v "${ROOT}/scripts/lib/verify-upr-package-pin.php:/verify.php:ro" \
	php:8.4-cli php /verify.php

echo "==> Tampered meta rejected"
cp -a "${UPR_DIR}" "${DISPOSABLE}/tampered-upr"
python3 - "${DISPOSABLE}/tampered-upr/release.meta.json" <<'PY'
import json, pathlib, sys
p = pathlib.Path(sys.argv[1])
data = json.loads(p.read_text())
data["commit"] = "ffffffffffffffffffffffffffffffffffffffff"
p.write_text(json.dumps(data, indent=2) + "\n")
PY

docker run --rm \
	-e UPR_PLUGIN_DIR=/upr \
	-e HOST_INCLUDES=/host/includes \
	-e EXPECT_OK=0 \
	-v "${HOST_DIR}:/host:ro" \
	-v "${DISPOSABLE}/tampered-upr:/upr:ro" \
	-v "${ROOT}/scripts/lib/verify-upr-package-pin.php:/verify.php:ro" \
	php:8.4-cli php /verify.php

echo "==> All disposable pair validations passed (DEV WordPress untouched)"
