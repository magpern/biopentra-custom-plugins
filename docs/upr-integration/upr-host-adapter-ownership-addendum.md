# Addendum — UPR host adapter ownership correction

**Date:** 2026-08-27  
**Does not rewrite** historical M3 closure / rehearsal / prerequisites documents in place; see corrected freeze + operational addendum for current production packaging truth.  
**Production:** Untouched by this ownership correction. **PRODUCTION STILL NO-GO.**

## Correction

| Former | Current |
|--------|---------|
| Embedded `biopentra-custom-plugins/plugins/biopentra-upr-host` | Standalone https://github.com/magpern/upr-host-adapter |
| Production package candidate host **0.1.5** | **Superseded** — not a production candidate |
| Production host target | **`upr-host-adapter` `v0.1.1`** / merge `5bc67c7dd7946178b75e1b7d91d32d1e2074296e` |
| UPR core | Unchanged: `v0.3.0` / `b2abc2defc30fc023601593aa1720cbfdd0a4f3c` |

## Status

- Repository prerequisites for the **embedded** `0.1.5` path are obsolete for production packaging.
- Corrected freeze + package SHAs: [`m3-production-invitation-rollout.md`](m3-production-invitation-rollout.md), [`m3-production-operational-prerequisites-closure.md`](m3-production-operational-prerequisites-closure.md).
- P2 **COMPLETE** for the `v0.1.1` pair (offline validate); P1 and P4–P8 remain open.
- **P8 note:** Use ordinary post-boundary synthetic mail to an approved test mailbox (ledger-only). **Do not** add a UPR mint-without-email API. P8 remains **OPEN** until that operational synthetic-mail decision exists.
- No production package/deployment of either host identity has occurred.

## Next step

**Park production work** and return to product development. When production gates resume: record the P8 synthetic-mail/test-mailbox decision; do not enable real customer contact without P7.

Authority for extraction: freeze tag `upr-host-adapter-extraction-freeze` → `a19cb41e92966e0d387ae0c33d2fb4d260815df6`.

## License corrective release

Public adapter **`v0.1.1`** (`5bc67c7dd7946178b75e1b7d91d32d1e2074296e`) aligns `LICENSE` / plugin header / README to **GPL-2.0-or-later**. Annotated **`v0.1.0`** remains at `687d33c1fbac5323e7b2319350ccdbe86e322375` and is not moved.
