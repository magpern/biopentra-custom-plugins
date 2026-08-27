# UPR Host Adapter extraction (ownership correction)

**Status:** Authoritative freeze for extracting the host adapter into a standalone neutral repository.  
**Date:** 2026-08-27  
**Production:** Unchanged by this freeze and by the extraction work it authorises for **repository + DEV only**.

## Verified prior state (read-only inspection)

| Fact | Actual |
|------|--------|
| Source location | Only `/opt/biopentra/dev/biopentra-custom-plugins/plugins/biopentra-upr-host` |
| Other trees | No second checkout named `biopentra-upr-host` or `upr-host-adapter` under `/opt/biopentra/dev` |
| DEV bind-mount | `apps/wordpress/compose.yml` mounts that path → `…/plugins/biopentra-upr-host` (wordpress + wpcli) |
| DEV active plugins | `biopentra-upr-host` **active** `0.1.5`; UPR **`0.3.0`** |
| UPR pin | Annotated `v0.3.0` → `b2abc2defc30fc023601593aa1720cbfdd0a4f3c` (unchanged) |
| Former prod candidate | Embedded host **`0.1.5`** — **superseded**; must not be deployed |

## Goal

| Item | Value |
|------|--------|
| New repository | `magpern/upr-host-adapter` (temporary **public** for CI) |
| Plugin slug | `upr-host-adapter` |
| Naming | Neutral `UprHostAdapter` / `upr-host-adapter` / `UPR_HOST_ADAPTER_*` |
| DEV source | `/opt/biopentra/dev/upr-host-adapter` |
| Initial version | **`0.1.0`** (new identity; supersedes embedded `0.1.5`) |

## Boundaries

1. The adapter is **host-side integration**, not UPR generic core. UPR remains `magpern/universal-product-reviews`.
2. Neutral public identity: repository, plugin slug, headers, classes, options, CLI, hooks, docs, CI labels.
3. **Must not contain:** `biopentra` branding, domain names, hostnames, server paths, IPs, credentials, customer data, or production configuration.
4. **May retain** generic contracts: WooCommerce, UPR filters/APIs, MPCF `mpcf_fulfillment_state_changed`, structured support-adapter signals (order ID + tags + ticket status; no free-text).
5. Public visibility is **temporary for CI**. **No GitHub Actions secrets** may be added.
6. DEV uses **source bind-mounts** only (established Docker workflow).
7. Production is **unchanged**. Former **`biopentra-upr-host` `0.1.5`** is **not** a production package candidate after extraction.
8. Extraction uses a **fresh public Git history** (no import of branded commit history).
9. **No** branded legacy-option migration code — production has no installed UPR/host adapter baseline.
10. Existing DEV options may be reset/reconfigured through normal DEV tooling only.

## Security behaviour to preserve

- UPR core denials non-overridable; host policy restrictive-only.
- Empty/malformed allowlist fails closed.
- Packaged UPR `release.meta.json` must match `v0.3.0` / `b2abc2defc30fc023601593aa1720cbfdd0a4f3c`.
- Invitation-email master enable remains UPR-owned and off by default; emergency pause remains effective.
- No historical invitation backfill.

## Non-goals

- Production deploy, package install on production, or customer contact.
- Moving UPR core invitation/security logic into the adapter.
- GitHub Release for `v0.1.0` (annotated tag only).

## Next steps (this initiative)

1. Create `magpern/upr-host-adapter`, implement neutral `0.1.0`, merge, tag `v0.1.0`.
2. Bind-mount DEV to `/opt/biopentra/dev/upr-host-adapter`; deactivate/remove embedded plugin after health checks.
3. Remove embedded source from `biopentra-custom-plugins`; addendum docs superseding `0.1.5`.
