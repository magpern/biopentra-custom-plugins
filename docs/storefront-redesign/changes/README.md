# Change records

One markdown file per shipped milestone or discrete change. Required before a milestone is considered complete.

## Naming

| Pattern | Example |
|---|---|
| `milestone-{letter}-{slug}.md` | `milestone-A-home.md` |
| `milestone-0-foundations.md` | Foundations + SDS (no customer-visible change) |

## Required fields

Every record must include:

1. **Summary** — what changed and why (commercial intent)
2. **URLs affected** — full paths on dev and production
3. **Page / template IDs** — dev reference only; production looked up by slug/option
4. **Component owner** — repo + version tag
5. **Previous state** — brief + link to backup JSON if DB-backed
6. **New state** — DOM order, widgets, templates
7. **Files changed** — paths in version control
8. **Selectors / CSS** — classes, custom properties touched
9. **Settings** — admin paths, before/after values, screenshots
10. **DB changes** — `_elementor_data`, postmeta, options
11. **Export artifacts** — Elementor JSON, kit paths
12. **WP-CLI commands** — exact replay commands (slug-based)
13. **Cache steps** — flush trio + Cloudflare notes
14. **Screenshots** — before/after paths under `validation/`
15. **Acceptance results** — Playwright spec + exit code
16. **Production replay** — link to matching `deployment/` record
17. **Rollback** — restore backup JSON or previous Release ZIP
18. **Commit hash(es)** — git SHAs for every repo touched

## Backups

Elementor `_elementor_data` backups live in `backups/`:

```
backups/{page-slug}-pre-{milestone}.json
```

Capture before any CLI script mutates page meta:

```bash
cd /opt/biopentra/apps/wordpress
docker compose run --rm -T wpcli wp post meta get <page_id> _elementor_data \
  > /opt/biopentra/docs/storefront-redesign/changes/backups/<page-slug>-pre-<milestone>.json
```
