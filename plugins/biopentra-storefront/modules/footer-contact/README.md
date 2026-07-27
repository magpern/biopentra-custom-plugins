# Module: footer-contact

**Migrated from:** `plugins/biopentra-footer-contact/` (retired monorepo stub; runtime lives here).

## Implementation

- `class-footer-contact-module.php`
  - **`wp_enqueue_scripts` @ 5:** registers script handle `biopentra-footer-contact-email` (same handle as legacy), `assets/footer-contact/footer-contact-email.js`, version `1.1.1`, footer.
  - **`wp_robots`:** sets `noindex` when singular post has meta `_biopentra_placeholder_page` === `'1'`.
  - **Shortcode `[biopentra_footer_email]`:** same markup as legacy; mailto built in JS only.

## Duplicate plugin guard

If a standalone `biopentra-footer-contact` plugin were active, it would register the shortcode first and this module would skip registration. Legacy slug is retired — not deployed.

## Assets

- `assets/footer-contact/footer-contact-email.js` (required for script registration).
- `assets/footer-contact/bp-e1.png` (optional; if missing, the shortcode still outputs the button and JS mailto, without the `<img>` tag).
