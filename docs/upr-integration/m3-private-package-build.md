# M3 private package build (UPR v0.3.0 + host 0.1.5)

**Status:** Repository prerequisite (WP-B). **NO-GO** for production install.  
**Does not:** create a public GitHub Release, publish a public ZIP, or change DEV/production WordPress.

## Exact refs

| Package | Immutable ref | Peel commit |
|---------|---------------|-------------|
| UPR | annotated tag `v0.3.0` | `b2abc2defc30fc023601593aa1720cbfdd0a4f3c` |
| Host | annotated tag `0.1.5` | recorded at tag peel on `biopentra-custom-plugins` |

## Build

### UPR (canonical: `magpern/universal-product-reviews`)

```bash
cd /path/to/universal-product-reviews
git fetch --tags origin
bash scripts/build-release-package.sh v0.3.0
```

Outputs under `builds/` (gitignored):

- `universal-product-reviews-0.3.0.zip`
- `universal-product-reviews-0.3.0.SHA256SUMS`
- `universal-product-reviews-0.3.0.release.meta.json`

Embeds generic `release.meta.json` (`universal-product-reviews.package-meta/v1`). No `.git` in the ZIP. Does not alter or retag `v0.3.0`.

Private CI: workflow **Package release (private artifact)** (`workflow_dispatch`) uploads those files via `actions/upload-artifact` (no Release).

### Host (canonical: `magpern/biopentra-custom-plugins`)

```bash
cd /path/to/biopentra-custom-plugins
git fetch --tags origin
bash scripts/build-upr-host-package.sh 0.1.5
```

Outputs under `builds/zips/`:

- `biopentra-upr-host-0.1.5.zip`
- `biopentra-upr-host-0.1.5.SHA256SUMS`

## Verify (disposable tree)

```bash
export UPR_ZIP=/path/to/universal-product-reviews-0.3.0.zip
export UPR_SUMS=/path/to/universal-product-reviews-0.3.0.SHA256SUMS
export HOST_ZIP=/path/to/biopentra-upr-host-0.1.5.zip
export HOST_SUMS=/path/to/biopentra-upr-host-0.1.5.SHA256SUMS
bash scripts/validate-m3-pair-packages.sh
```

Asserts: checksums, extract layout, host pin accepts packaged UPR, tampered meta denied. **Does not** touch the existing DEV WordPress site.

## Retention

- Prefer private GitHub Actions artifacts (30-day retention on the UPR package workflow).
- Local `builds/` / `builds/zips/` are operator-controlled and gitignored.
- Do not attach these ZIPs to a public GitHub Release for this initiative.
