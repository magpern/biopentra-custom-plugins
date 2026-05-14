# GitHub backup plan

## Repository

Use this folder (`custom-wordpress-plugins/`) as its own Git repository **or** as a subdirectory in a larger monorepo (e.g. `custom-wordpress-plugins/` inside the WooCommerce project repo).

### Recommended: dedicated repo

- **Repo name:** `biopentra-custom-wordpress-plugins` (or similar).
- **Default branch:** `main`, protected with required PR review for `plugins/biopentra-contact-inbox/**` and `plugins/wc-inventory-overview/**`.

### What to commit

- `README.md`, `.gitignore`, `docs/**`, `scripts/**`
- Full `plugins/**` source trees (excluding nothing critical; ZIPs stay ignored).
- `builds/zips/.gitkeep` only — **do not** commit generated `*.zip` (binary churn; rebuild in CI).

### What not to commit

- `.env`, database dumps, IMAP/SMTP secrets, worker bearer tokens in plaintext.
- `node_modules/`, `vendor/` unless you introduce Composer/npm with lockfiles intentionally.
- Generated `builds/zips/*.zip` (see `.gitignore`).

## CI (optional next step)

- Job: `shellcheck scripts/build-zips.sh`, `php -l` on changed PHP files.
- Artifact: upload `builds/zips/*.zip` from `build-zips.sh` for tagged releases.

## Release tagging

- Tag format: `biopentra-contact-inbox/v2.0.0` **or** repo-wide `v2026.05.14` with release notes per plugin section.
- Prefer **per-plugin version** in tag message matching the plugin header used in ZIP names.

## Composer

Optional root `composer.json` **dev-only** for PHPStan/PHPCS; plugins currently need no runtime Composer.

## Submodule / subtree

If the WooCommerce repo owns this tree as a **subtree**, document update commands in the main project README so operators know how to pull plugin updates.
