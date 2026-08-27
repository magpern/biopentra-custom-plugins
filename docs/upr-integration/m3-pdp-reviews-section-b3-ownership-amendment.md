# M3 PDP reviews section — B3 ownership amendment

**Status:** Documentation amendment only.  
**Amends:** [`m3-pdp-reviews-section.md`](m3-pdp-reviews-section.md)  
**Original freeze tag (unchanged):** `m3-pdp-reviews-section-freeze`  
**Amendment freeze tag:** `m3-pdp-reviews-section-freeze-b3`

## Reason

Productization (UPR ADR-0002 / B1) moved availability-aligned native product-comment enforcement and the native-PDP display helper into generic UPR **`v0.2.2`**. The original M3 PDP freeze assigned logged-in native POST denial and display eligibility to `biopentra-upr-host` (open PR #10). That host-owned security boundary is obsolete and must not be reimplemented.

## Artifacts

| Item | Reference |
|------|-----------|
| UPR B1 release | annotated tag `v0.2.2` |
| UPR implementation merge | `43c9989291a4c7eab7f9fd57603c851486da287a` |
| UPR B1 closure | `universal-product-reviews` `docs/roadmap/b1-native-submission-enforcement-closure.md` |
| Host B2 PR | [#11](https://github.com/magpern/biopentra-custom-plugins/pull/11) |
| Host B2 merge | `8a14ba1ebed69492a7690249fe762c38bc453707` |
| Host PR #10 | [Closed as superseded](https://github.com/magpern/biopentra-custom-plugins/pull/10) (not merged) |

## Revised ownership

| Concern | Owner |
|---------|--------|
| `NativeSubmissionGuard` / logged-in native product-comment enforcement / availability-aligned security | UPR `v0.2.2` |
| `NativePdpForm::should_render()` | UPR `v0.2.2` |
| Branded unavailable messaging | `biopentra-upr-host` |
| UPR pin + preflight + DEV integration verification | `biopentra-upr-host` |
| `comments_open` as availability gate | **Forbidden** (neither host nor UPR) |
| Host competing `preprocess_comment` submission guard | **Forbidden** |
| PDP `#reviews` section / template fork / CSS / Playwright | Unchanged owners (storefront / child / acceptance) — **B4** |

## Unchanged by this amendment

- Standard Blocksy PDP-only Phase 1 scope
- Template-fork exception and load rules
- Sticky-buy-bar lock
- A1–A20 acceptance matrix intent (A20 pin updated to `v0.2.2`)
- M2 guest submission exclusively on `/upr-review/form/`
- No product UI or runtime behaviour change from this docs-only amendment alone

## B4 gate

B4 PDP section implementation may begin only after both:

1. UPR **`v0.2.2`**; and  
2. merged B2 host integration (PR #11 / `8a14ba1…`).

Both are satisfied on documentation `main` after B2 merge. Controlled DEV bind-mount / replay remains a separate operator step and is **not** performed by B3.
