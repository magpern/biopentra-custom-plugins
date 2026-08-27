> **Host packaging target:** `magpern/upr-host-adapter` annotated **`v0.1.1`**. Embedded `biopentra-upr-host` **0.1.5** is **SUPERSEDED**.

# M3 private package build (UPR v0.3.0 + upr-host-adapter v0.1.1)

**Status:** Repository prerequisite (WP-B). **NO-GO** for production install.  
**Does not:** create a public GitHub Release, publish a public ZIP, or change DEV/production WordPress.

## Exact refs

| Package | Immutable ref | Peel commit |
|---------|---------------|-------------|
| UPR | annotated tag `v0.3.0` | `b2abc2defc30fc023601593aa1720cbfdd0a4f3c` |
| Host | annotated tag `v0.1.1` | `5bc67c7dd7946178b75e1b7d91d32d1e2074296e` |

## Offline-validated package SHAs (2026-08-27 rebuild from immutable tags)

| Artifact | SHA-256 |
|----------|---------|
| `upr-host-adapter-0.1.1.zip` | `654328c1f8e5605e14b00c4f973bd22ef1a31c86fdbda63bb1a19111b94b16d9` |
| `universal-product-reviews-0.3.0.zip` | `43beb998579743caf0b2b32dc5fe26470804cd21131ed678b983d19d06e9b8c6` |
| `universal-product-reviews-0.3.0.release.meta.json` | `0af6348642c30648377204e7eb423b8364ecd7898870d282c8b329631be3adc2` |

Prior closure recorded UPR zip `e7f02bc5…` for an earlier 0.1.5-era build. This rebuild from the same `v0.3.0` tag **supersedes** that artifact SHA for the operational-prerequisites freeze (**meta SHA unchanged**).

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

### Host (canonical: `magpern/upr-host-adapter`)

```bash
cd /path/to/upr-host-adapter
git fetch --tags origin
bash scripts/build-release-package.sh v0.1.1
```

Outputs under `builds/`:

- `upr-host-adapter-0.1.1.zip`
- `upr-host-adapter-0.1.1.SHA256SUMS`

Top-level directory must be `upr-host-adapter/`. Plugin header `Requires Plugins: universal-product-reviews`.

## Verify (disposable tree)

```bash
export UPR_ZIP=/path/to/universal-product-reviews-0.3.0.zip
export UPR_SUMS=/path/to/universal-product-reviews-0.3.0.SHA256SUMS
export HOST_ZIP=/path/to/upr-host-adapter-0.1.1.zip
export HOST_SUMS=/path/to/upr-host-adapter-0.1.1.SHA256SUMS
bash scripts/validate-m3-pair-packages.sh
```

Asserts: checksums, extract layout, host Version `0.1.1`, Requires Plugins, host pin accepts packaged UPR, tampered meta denied. **Does not** touch the existing DEV WordPress site.

P2 for this pair is **COMPLETE** after offline validate against the SHAs above.

## Retention

- Prefer private GitHub Actions artifacts (retention per workflow).
- Local `builds/` paths are operator-controlled and gitignored.
- Do not attach these ZIPs to a public GitHub Release for this initiative.
