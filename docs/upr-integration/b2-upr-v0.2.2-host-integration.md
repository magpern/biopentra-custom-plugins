# B2 — Host integration for UPR v0.2.2

**Status:** Implementation PR (do not claim M3 pilot accepted).  
**Host plugin:** `biopentra-upr-host` **0.1.3**  
**UPR pin:** annotated tag `v0.2.2` / commit `43c9989291a4c7eab7f9fd57603c851486da287a`

## Ownership boundary

| Concern | Owner |
|---------|--------|
| Availability-aligned native product-comment enforcement | UPR core (`NativeSubmissionGuard`) |
| Native-PDP form eligibility | UPR core (`NativePdpForm::should_render()`) |
| Guest M2 invitation submit | UPR core (`/upr-review/form/` only) |
| Branded unavailable messaging + form title chrome | Host (`Biopentra_Upr_Host_Review_Availability_Ux`) |
| UPR dependency pin + DEV verification CLIs | Host |
| Delivery / support adapters | Host (unchanged) |

The host **must not** register `comments_open` availability gating or a competing `preprocess_comment` guard. Display decisions fail closed when the UPR `NativePdpForm` API / pin is unavailable.

## Pin mechanism

Single mechanism: `Biopentra_Upr_Host_Upr_Pin` constants + `verify-pilot-preflight` / `verify-native-submit-dev`.

| Constant | Value |
|----------|--------|
| `REQUIRED_VERSION` | `0.2.2` |
| `REQUIRED_TAG` | `v0.2.2` |
| `REQUIRED_COMMIT` | `43c9989291a4c7eab7f9fd57603c851486da287a` |

Also requires `NativePdpForm` and `NativeSubmissionGuard` classes to exist.

**DEV bind-mount / checkout is not performed by this PR** — operators update the UPR bind-mount separately before running DEV CLIs.

## Host UX

- `can_submit_for_product()` is a **thin fail-closed wrapper** around `NativePdpForm::should_render()` (no second rule engine).
- Branded copy includes `product_not_reviewable` (catalogue-hidden / discontinued).
- Guests (including M2 form sessions) never get a native PDP form via the helper.

## Verification CLIs

```bash
wp biopentra-upr-host verify-pilot-preflight
wp biopentra-upr-host verify-native-submit-dev
```

`verify-native-submit-dev` proves: pin, no host `comments_open` / host `preprocess_comment`, UPR guards registered, display helper behaviour, and UPR native deny/allow matrix. Requires development environment + UPR pin present on the running site.

Static policy (no WordPress):

```bash
bash plugins/biopentra-upr-host/scripts/b2-policy-check.sh
```

## Explicit non-goals (this PR)

- No M3 PDP section / template / CSS / Playwright
- No M3 freeze ownership-table amend (B3)
- No DEV bind-mount or production deploy
- Does not merge or close host PR #10 (supersede after this PR merges)
- Does not claim M3 pilot accepted

## Next after merge

1. Close host PR #10 as superseded.  
2. B3: amend M3 PDP freeze ownership docs to match this boundary.  
3. Controlled DEV replay (bind-mount UPR `v0.2.2`) before B4 PDP section work.
