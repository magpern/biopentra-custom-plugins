# Legacy plugin retirement plan (post–`biopentra-storefront` consolidation)

This document defines **when** the standalone plugin folders that were merged into **`biopentra-storefront`** may be removed from **deploy artifacts** (and optionally from the Git monorepo). It does **not** authorize deletion until the criteria below are met.

**Superseded plugins (folders retained today):**

- `biopentra-information-megamenu`
- `biopentra-footer-contact`
- `custom-variation-stock-selector`
- `biopentra-header-auth`

---

## 1. When folders may safely be removed

All of the following must be true:

1. **Production** has run **`biopentra-storefront` 0.4.0** (or newer) with all four legacy plugins **deactivated** for the full **monitoring period** (see §2).  
2. **No open P1/P2** defects attributed to the consolidation; checkout, account, cart, header, mega-menu, footer, and variation flows are stable.  
3. **Elementor:** No remaining references to legacy asset URLs in `_elementor_data` / caches (especially **megamenu** legacy `information-mega.js` — see `docs/elementor-information-mega-js-cleanup.md`).  
4. **Backups:** At least one **verified restorable** backup (DB + `wp-content/plugins/` slice) taken **after** the stable cutover exists off-site.  
5. **Rollback drill** completed once on staging after the monitoring period: reactivate legacies, confirm behaviour, return to storefront-only state without data loss.  
6. **Stakeholder sign-off** (product/ops) that rollback via legacy ZIP is no longer required for day-to-day operations.

**Remove from deploy first:** stop packaging legacy ZIPs in release pipelines; keep Git history until policy says otherwise.

---

## 2. Required monitoring period

| Tier | Minimum calendar days (production storefront-only) | Notes |
|------|------------------------------------------------------|--------|
| **Default** | **14 days** | No Sev-1/Sev-2 incidents tied to storefront; order flow and critical editor paths verified. |
| **High-traffic / regulated** | **30 days** | Extend if peak season, migration adjacent to other releases, or incomplete browser QA (see `docs/phase-4-header-auth-test-results.md`). |

The monitoring period starts **after** the last production change in the cutover window (including Elementor cleanup and final CDN purge), not from the first staging deploy.

---

## 3. Backup requirements

Before **any** folder removal from a production host:

- **Database:** Full dump retained per retention policy.  
- **Files:** Tar/ZIP of `wp-content/plugins/` including **both** `biopentra-storefront` and the four legacy trees (even if inactive).  
- **Artifacts:** Store matching `biopentra-storefront-{version}.zip` and guarded `biopentra-header-auth.php` in artifact storage with version labels.

Backups must be **tested** (restore to disposable instance) at least once before irreversible deletion.

---

## 4. Elementor considerations

- **Megamenu:** Hardcoded `<script src="…/biopentra-information-megamenu/...">` in post meta must be gone; otherwise removing the folder causes **404** and broken menus.  
- **Header auth:** Widget type `biopentra_header_auth` is stored by Elementor; removal of legacy **PHP** is fine **only if** storefront provides the class — do not delete storefront.  
- **Footer:** Shortcode `[biopentra_footer_email]` in templates must resolve via storefront; verify JSON exports before deleting legacy plugin.

Re-export or document Elementor templates after retirement for disaster recovery.

---

## 5. Rollback requirements until retirement

- Legacy plugin directories remain **readable** on disk and activatable.  
- **Guarded** `biopentra-header-auth/biopentra-header-auth.php` must remain on any host where rollback or odd load order could occur (see `CHANGELOG.md` 0.4.0).  
- Runbook: `docs/storefront-0.4.0-production-cutover.md` §6.

---

## 6. Repo vs production

- **Production:** Retire legacy folders from **deploy packages** first; keep a cold-storage ZIP for years if compliance requires.  
- **Git monorepo:** Removing folders is optional; many teams **keep** legacy sources read-only for archaeology and diff. If removed, use a tagged commit before deletion for bisection.

---

## Summary

| Gate | Requirement |
|------|-------------|
| Time | **≥ 14 days** storefront-only production ( **≥ 30 days** if high risk) |
| Quality | No unresolved critical regressions; Elementor clean |
| Backups | Verified DB + plugin archives |
| Process | Stakeholder approval + documented rollback last drill |
