# Milestone 0 — Production deployment

**Status:** Not applicable  
**Date:** 2026-08-01

Milestone 0 (Foundations + Storefront Design System) ships **no customer-visible changes** and requires **no production deployment**.

## What ships in git only

- Documentation scaffolds under `docs/storefront-redesign/`
- Storefront Design System spec (`design-system/`)
- Playwright harness viewport expansion (`storefront-acceptance`)

## Production replay

Begin at **Milestone A** with `deployment/milestone-A-home.md` (to be created).

## Validation on production

Optional sanity check (no baseline change expected):

```bash
cd /opt/biopentra/dev/storefront-acceptance
bash tools/run-prod.sh
```

This confirms production remains reachable; Milestone 0 does not assert new budgets on production.
