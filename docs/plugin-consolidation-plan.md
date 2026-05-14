# Plugin consolidation plan

## Target architecture (approved)

### Standalone (no merge)

1. **biopentra-contact-inbox** — Own security boundary, DB schema, cron, REST worker.
2. **wc-inventory-overview** — Operational/financial domain, large codebase.
3. **biopentra-loop-card** — Remains separate **for now** (Elementor loop complexity).

### New consolidated plugin: biopentra-storefront

**Eventually** contains modules migrated from:

1. biopentra-header-auth  
2. biopentra-footer-contact  
3. biopentra-information-megamenu  
4. custom-variation-stock-selector  

**Current state:** `plugins/biopentra-storefront/` is a **scaffold only** (bootstrap file + empty `init()`). Legacy plugins remain the source of truth until migration phases complete.

## Module layout (future)

```
biopentra-storefront/
├── biopentra-storefront.php
├── includes/class-biopentra-storefront.php
├── modules/header-auth/
├── modules/footer-contact/
├── modules/information-megamenu/
├── modules/variation-stock-selector/
└── assets/
```

Each module should expose a small `register()` (or similar) called from the main plugin so hooks can be toggled or feature-flagged during rollout.

## Consolidation options

| Approach | Recommendation |
|----------|----------------|
| Keep all legacy plugins forever | Not ideal; duplicate enqueue and version drift. |
| Monolithic single file | Rejected; use modules per subdirectory. |
| **Modular single plugin (`biopentra-storefront`)** | **Chosen** for the four storefront-adjacent plugins. |

## biopentra-loop-card (later)

Optional future merge into storefront as a **separate module** (`modules/loop-card/`) once storefront stabilizes; not in scope for the first migration wave.

## Rollout principle

**One module at a time:** implement module in storefront, test with **both** old and new inactive for same concern (avoid double hooks), then deactivate the legacy plugin for that concern only.

See `migration-plan-biopentra-storefront.md` for detailed hook and asset mapping.
