# M3 production invitation approval ledger — TEMPLATE

**Status:** Restricted template only. **Do not** create a live ledger or store real order IDs / recipient details in Git.  
**Location / named operators:** Pending separate approval before any live ledger is opened.

Use one row per authorised synthetic or pilot send attempt. All times **UTC**.

| Field | Value |
|-------|--------|
| Named operator | _TBD — approved operator name_ |
| UTC timestamp | _YYYY-MM-DDTHH:MM:SSZ_ |
| Order ID | _store outside git / redacted reference only_ |
| Short reason | |
| Recipient-contact authorisation | _yes/no_ |
| Authorisation basis | _ticket / PO decision ref — no PII_ |
| Expiry (UTC) | |
| Rollback owner | |
| Final-send approval | _name + UTC — empty until explicit approve_ |

## Rules

1. Empty `Final-send approval` means **do not send**.
2. Ledger rows are not a substitute for UPR master enable, emergency pause, or host allowlist controls.
3. Synthetic `@example.invalid` fixture drills use the same columns; mark reason `synthetic-fixture`.
4. Never commit filled rows with real customer identifiers to this repository.
