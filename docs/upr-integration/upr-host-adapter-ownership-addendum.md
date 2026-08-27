# Addendum — UPR host adapter ownership correction

**Date:** 2026-08-27  
**Does not rewrite** historical M3 closure / rehearsal / prerequisites documents.  
**Production:** Untouched by this ownership correction.

## Correction

| Former | Current |
|--------|---------|
| Embedded `biopentra-custom-plugins/plugins/biopentra-upr-host` | Standalone https://github.com/magpern/upr-host-adapter |
| Production package candidate host **0.1.5** | **Superseded** — not a production candidate |
| Future production host target | **`upr-host-adapter` `v0.1.1`** / merge `5bc67c7dd7946178b75e1b7d91d32d1e2074296e` |
| UPR core | Unchanged: `v0.3.0` / `b2abc2defc30fc023601593aa1720cbfdd0a4f3c` |

## Status

- Repository prerequisites for the **embedded** `0.1.5` path are obsolete for production packaging.
- Production invitation rollout remains **NO-GO**.
- No production package/deployment of either host identity has occurred.
- Next: revise the frozen production prerequisite plan to use `upr-host-adapter v0.1.1`, then continue only with separately approved production operational prerequisites.

Authority for extraction: freeze tag `upr-host-adapter-extraction-freeze` → `a19cb41e92966e0d387ae0c33d2fb4d260815df6`.

## License corrective release

Public adapter **`v0.1.1`** (`5bc67c7dd7946178b75e1b7d91d32d1e2074296e`) aligns `LICENSE` / plugin header / README to **GPL-2.0-or-later**. Annotated **`v0.1.0`** remains at `687d33c1fbac5323e7b2319350ccdbe86e322375` and is not moved.
