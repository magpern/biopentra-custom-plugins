# Module: footer-contact

**Migrated from:** `plugins/biopentra-footer-contact/` (legacy folder retained; **deactivate** legacy when storefront owns this behavior).

## Implementation

- `class-footer-contact-module.php`
  - **`wp_enqueue_scripts` @ 5:** registers script handle `biopentra-footer-contact-email` (same handle as legacy), `assets/footer-contact/footer-contact-email.js`, version `1.1.1`, footer.
  - **`wp_robots`:** sets `noindex` when singular post has meta `_biopentra_placeholder_page` === `'1'`.
  - **Shortcode `[biopentra_footer_email]`:** same markup as legacy; mailto built in JS only.

## Duplicate plugin guard

If `biopentra-footer-contact` is **active**, it registers the shortcode first and this module **does nothing** (avoids double shortcode registration). For cutover, **deactivate** the legacy plugin after QA.

## Assets

- `assets/footer-contact/footer-contact-email.js` (required for script registration).
- `assets/footer-contact/bp-e1.png` (optional; if missing, the shortcode still outputs the button and JS mailto, without the `<img>` tag).
