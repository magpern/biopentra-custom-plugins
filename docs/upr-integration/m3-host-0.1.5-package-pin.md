> **SUPERSEDED (2026-08-27):** Host identity moved to `upr-host-adapter` `v0.1.0`. See [`upr-host-adapter-ownership-addendum.md`](upr-host-adapter-ownership-addendum.md).

# Host UPR package pin (biopentra-upr-host 0.1.5)

**Status:** Repository prerequisite for production packaging (WP-C).  
**Production target:** host **0.1.5** + UPR **v0.3.0** / `b2abc2defc30fc023601593aa1720cbfdd0a4f3c`.  
**Not authorised:** production deploy, email enablement, or customer contact.

## Mechanism

`Biopentra_Upr_Host_Upr_Pin` reads only:

| Field | Value |
|-------|--------|
| File | `release.meta.json` in the UPR plugin directory |
| Schema | `universal-product-reviews.package-meta/v1` |
| Required | `version`=`0.3.0`, `tag`=`v0.3.0`, `commit`=`b2abc2defc30fc023601593aa1720cbfdd0a4f3c` |
| APIs | `NativePdpForm`, `NativeSubmissionGuard`, `InvitationAuthorisation` |

Fail-closed on missing, empty, non-JSON, wrong schema, wrong fields, or missing APIs. A decoy `.git` directory is ignored.

The metadata format is **generic** (no host/brand names). Integrity of the package tree is additionally enforced by SHA-256 manifests from the WP-B build process.

## DEV

Write the same `release.meta.json` beside a verified UPR checkout (same values as above). Do not relax the pin to read `.git` at runtime.

## Checks

```bash
bash plugins/biopentra-upr-host/scripts/b2-policy-check.sh
bash plugins/biopentra-upr-host/scripts/m3-pilot-policy-check.sh
bash plugins/biopentra-upr-host/scripts/m3-package-pin-check.sh
```
