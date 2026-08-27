# Changelog — biopentra-upr-host

## [0.1.5] — 2026-08-27

### Changed

- UPR dependency pin no longer reads a `.git` checkout. Packaged installs must ship generic `release.meta.json` (`universal-product-reviews.package-meta/v1`) with exact `version` / `tag` / `commit`. Missing, malformed, wrong-schema, or mismatched metadata fails closed.
- Production package target is **0.1.5 only**. Host **0.1.4** remains DEV bind-mount history and must not be used as a production ZIP pin.

### Added

- Offline package-pin checks: `scripts/m3-package-pin-check.sh`.

## [0.1.4] — prior

- Pilot invitation send policy + `.git`-based UPR pin (DEV bind-mount).
