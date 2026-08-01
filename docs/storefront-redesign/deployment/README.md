# Production deployment records

One markdown file per production replay. Milestone 0 has no production deployment — records begin at Milestone A.

## Naming

| Pattern | Example |
|---|---|
| `milestone-{letter}-{slug}.md` | `milestone-A-home.md` |

## Required steps (every milestone)

1. Install Release ZIP(s) for the milestone (tag names in change record)
2. Run idempotent WP-CLI script(s) on production — **slug/option lookups only, never dev post IDs**
3. Apply documented admin settings with before/after screenshots
4. **Flush trio:**
   ```bash
   wp elementor flush-css
   wp cache flush
   # Cloudflare path purge for affected URLs
   ```
5. Run acceptance harness against production:
   ```bash
   cd /opt/biopentra/dev/storefront-acceptance
   bash tools/run-prod.sh
   ```
6. Complete QA table in this record
7. Link commit hash(es) and change record path

## Rollback

Document in each record:

- **Code:** reinstall previous Release ZIP tag
- **DB:** restore `_elementor_data` from `changes/backups/` via `wp post meta update`
- **Settings:** revert option values listed in change record

## Never

- Copy the development database to production
- Hand-edit production `data/` files on the host
