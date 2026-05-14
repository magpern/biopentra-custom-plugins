# Proposal: GitHub Actions for `biopentra-storefront` release artifacts

**Status:** Proposal only — not implemented. **Goal:** On each release tag, build the storefront ZIP once and attach it to a GitHub Release so deployers never rely on a single laptop’s `builds/zips/` tree.

---

## Triggers

- **Push tags** matching `storefront-v*` (e.g. `storefront-v0.4.0`), or  
- **Manual** `workflow_dispatch` with an input `version` (optional fallback).

---

## Job outline

1. **Checkout** repository at the tag SHA.  
2. **Setup** `bash`, ensure `zip` or `python3` exists (Ubuntu runners have both).  
3. **Run** `./scripts/build-zips.sh` from repo root.  
4. **Upload artifact** (GitHub Actions artifact retention 30–90 days).  
5. **Create or update GitHub Release** (if using `softprops/action-gh-release` or `ncipollo/release-action`):
   - Attach `builds/zips/biopentra-storefront-{version}.zip`.  
   - Body: paste section from `CHANGELOG.md` for that version.  
   - Mark as pre-release if tag contains `rc` or `beta`.

---

## Permissions

- **`contents: write`** for creating releases (use a scoped `GITHUB_TOKEN` or OIDC).  
- Do **not** embed long-lived PATs in the workflow; prefer default `GITHUB_TOKEN` with least privilege.

---

## Optional extensions

- **`php -l`** matrix on `plugins/biopentra-storefront/**/*.php` on every PR touching that path.  
- **Checksum file** (`sha256sum`) uploaded next to the ZIP for integrity verification on production.  
- **Fail if** `Version:` in `biopentra-storefront.php` does not match the tag (parse tag `v0.4.0` → compare to header).

---

## Out of scope (initially)

- Signing artifacts (GPG) — add if org policy requires.  
- Deploying to WordPress hosts — remains a separate pipeline or manual runbook (`docs/release-process.md`).

---

## Acceptance criteria (when implemented)

- Tag `storefront-v0.4.0` produces a downloadable `biopentra-storefront-0.4.0.zip` on the Release page.  
- Workflow duration under ~2 minutes; no secrets in logs.
