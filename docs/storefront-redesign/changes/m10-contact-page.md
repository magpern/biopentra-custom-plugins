# M10 — Contact Page Experience Redesign — change record

**Status:** **PO-approved / frozen on DEV**
**Storefront:** `0.9.33` / tag **`storefront-v0.9.33`**
**Universal Telegram companion:** `0.8.1` (PHP `universal_telegram_chat_is_enabled()`; Open Chat JS API on main from `0.8.0` line)
**Plan:** [MILESTONE_M10_CONTACT_PAGE_REDESIGN.md](../plans/MILESTONE_M10_CONTACT_PAGE_REDESIGN.md)
**Plan-freeze commit:** `465e184`
**Backup:** [backups/contact-pre-M10.json](backups/contact-pre-M10.json)

## Architecture

| Layer | Decision |
|---|---|
| Hero | Frozen `ef97090` — no internal changes |
| Body | Subordinate editorial rail + dominant Fluent Form #5 (Reason = sole mode switch) |
| Chat visibility | Site-level `chat_widget_enabled` only — not per-user |
| Open Chat | `UniversalTelegramChat.open()` / `universal-telegram:open-chat` |
| Order field | Mu-plugin render-time options + Storefront submit-time ownership guard |
| Elementor | Idempotent `setup-m10-contact-page-cli.php` on page **3410** |

## Removed

- Contact / Request Product mode cards (`123d679`)
- Bottom Request Product band (`fff6430`)
- Self-referential `#contact-form` CTA
- Elementor prefill widget (`d3307cf`) → Storefront `contact-v2.js`

## Acceptance

`storefront-acceptance` `tests/m10-contact.spec.ts` — `tools/run-dev.sh --m10-only` (8 viewports; chat-enabled DEV run).

## Production replay (not performed)

1. Deploy Release ZIPs: `biopentra-storefront` `0.9.33`, `universal-telegram` `0.8.1` if not already present.
2. Run `setup-m10-contact-page-cli.php` against production Contact page ID (verify ID; DEV = 3410).
3. `dev-clear-cache.sh` equivalent on prod cache infrastructure.
4. Run `--m10-only` against production/staging after explicit PO GO.
