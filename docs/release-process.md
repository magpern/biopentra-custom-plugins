# Release process — `biopentra-storefront`

This document standardizes **versioning**, **artifacts**, **checklists**, and **rollback** for `biopentra-storefront` after consolidation. It complements `docs/storefront-0.4.0-production-cutover.md` and `docs/storefront-migration-checklist.md`.

---

## 1. Version bump process

1. **Choose the release type** (SemVer for the plugin package):
   - **PATCH** (0.4.x → 0.4.1): bugfix-only, no new modules, no intentional hook surface change.
   - **MINOR** (0.4.0 → 0.5.0): new module, new public hook/filter, or new asset handle (coordinate cache busting).
   - **MAJOR** (1.0.0): breaking change to shortcodes, Elementor widget types, or required deactivation order — avoid without a migration note.

2. **Edit** `plugins/biopentra-storefront/biopentra-storefront.php`:
   - `Version:` header line  
   - `BIOPENTRA_STOREFRONT_VERSION` constant (must match header).

3. **Update** `CHANGELOG.md` under `[Unreleased]` or the new version section (see that file’s format).

4. **Run** `./scripts/build-zips.sh` and confirm `builds/zips/biopentra-storefront-{version}.zip`.

5. **Tag in Git** (after merge to the release branch / `main`):

   ```bash
   git tag -a storefront-v0.4.0 -m "biopentra-storefront 0.4.0"
   git push origin storefront-v0.4.0
   ```

   **Recommended historical tags** (if not already present; annotate once per shipped line):

   | Tag | Meaning |
   |-----|---------|
   | `storefront-v0.1.0` | Information megamenu in storefront |
   | `storefront-v0.2.0` | Footer contact module |
   | `storefront-v0.3.0` | Variation stock selector (CVSS) |
   | `storefront-v0.4.0` | Header auth module + guarded legacy coexistence |

   Use the same prefix for future releases (`storefront-v0.5.0`, …) so release notes and deploy scripts can grep predictably.

6. **Attach the ZIP** to GitHub Releases (or your artifact store) with checksums if policy requires.

---

## 2. ZIP build process

From repository root:

```bash
./scripts/build-zips.sh
```

- **Output:** `builds/zips/biopentra-storefront-{version}.zip` (and other plugins under `plugins/`).  
- **Contents:** Top-level folder `biopentra-storefront/` inside the archive (WordPress upload expectation).  
- **Verify:** Unzip to a temp directory; confirm `biopentra-storefront.php` version string, `modules/*`, and `assets/*` paths.  
- **Note:** `builds/zips/*.zip` may be gitignored; retain artifacts in CI or release storage, not only the working tree.

---

## 3. Pre-release checklist (maintainer)

- [ ] `CHANGELOG.md` updated; no stray `[Unreleased]` items left ambiguous.  
- [ ] Version header + constant aligned.  
- [ ] `php -l` (or CI) clean on changed PHP files.  
- [ ] No accidental edits to unrelated plugins (`loop-card`, inbox, inventory) unless in scope.  
- [ ] `./scripts/build-zips.sh` succeeds; smoke-open the storefront ZIP.  
- [ ] Docs: cutover or migration notes updated if behaviour or deploy order changed.  
- [ ] Legacy coexistence guards still correct (e.g. header-auth `function_exists` early return) if touching shared symbols.

---

## 4. Staging checklist

- [ ] Deploy same ZIP (or rsync same tree) as production will use.  
- [ ] Plugin list: storefront **on**, all superseded legacy plugins **off** (per phase).  
- [ ] **Header auth:** logged out / logged in, dropdown, My Account, checkout, cart with **non-empty** cart, Elementor editor.  
- [ ] **CVSS:** variable products, URL params, OOS, add-to-cart (see Phase 3 staging docs).  
- [ ] **Footer + megamenu:** shortcode, email JS, mega-menu after any Elementor cleanup.  
- [ ] **Network:** storefront asset URLs only; no 404s; no duplicate legacy megamenu JS after cleanup.  
- [ ] **Console:** no new errors on critical flows.  
- [ ] **HPOS:** no compatibility warning for `biopentra-storefront`.  
- [ ] **Rollback drill:** reactivate legacy set once; confirm guarded header-auth does not fatal; return to cutover posture.

---

## 5. Production deployment checklist

Align with `docs/storefront-0.4.0-production-cutover.md` for the full 0.4.0 line. In general:

- [ ] DB backup + plugin folder backup.  
- [ ] Saved active plugin list.  
- [ ] Guarded `biopentra-header-auth.php` deployed if rollback safety depends on it.  
- [ ] Storefront ZIP deployed and activated.  
- [ ] Elementor megamenu legacy script removed from DB/widget if still present.  
- [ ] Legacy plugins deactivated in the documented order.  
- [ ] Caches/CDN purged (see §7).  
- [ ] §4 staging checks repeated on production URLs (smoke subset acceptable if timeboxed).

---

## 6. Rollback process

1. Reactivate the relevant **legacy** plugins from disk (folders never deleted pre-soak).  
2. Deactivate **`biopentra-storefront`** only if the whole consolidated stack must revert; partial rollback may reactivate one legacy plugin while storefront still serves other phases — document the exact combination before doing it.  
3. Restore **DB** only if Elementor meta or other data was changed and must be reverted.  
4. Purge caches/CDN again.  
5. Confirm **no fatal redeclare**: production must run the **guarded** `biopentra-header-auth.php` if both codepaths can load in edge requests.

---

## 7. Cache / CDN purge checklist

After **every** plugin file deploy or rollback:

- [ ] Full page cache (host plugin, Varnish, etc.).  
- [ ] Object cache flush (if safe; avoid thundering herd during peak).  
- [ ] Opcode cache reset if PHP OPcache serves stale plugin files.  
- [ ] **Elementor:** regenerate CSS/files cache if your runbook requires it.  
- [ ] **CDN:** purge HTML for key templates and purge **path** or **tag** for:
  - `biopentra-storefront/modules/**`
  - `biopentra-storefront/assets/**`
- [ ] Browser hard-refresh for spot checks; verify **new** `ver=` query strings on enqueued assets.

---

## 8. Post-release monitoring checklist (first days)

- [ ] **Day 0–1:** Error logs (`debug.log`, host APM), 404 logs for plugin paths, checkout completion rate.  
- [ ] **Day 2–7:** User-reported header/auth, cart, variation selection, mega-menu regressions; Elementor editor complaints.  
- [ ] **Orders / HPOS:** no new WooCommerce compatibility notices.  
- [ ] **Performance:** TTFB and main-thread errors (RUM or Lighthouse sample).  
- [ ] **Rollback readiness:** backups retained; legacy folders still present and activatable.

When monitoring is clean for the agreed period, see **`docs/legacy-plugin-retirement-plan.md`** before removing legacy deploy artifacts.

---

## 9. Optional automation

See **`docs/github-actions-release-proposal.md`** for a future GitHub Actions workflow (build ZIP on tag, upload release artifact).
