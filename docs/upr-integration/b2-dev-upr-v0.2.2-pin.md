# DEV UPR v0.2.2 host pin (B2)

**Status:** Host code pin constants updated; **DEV bind-mount checkout is a separate operator step** (not performed by the B2 PR).  
**Authoritative B2 doc:** [`b2-upr-v0.2.2-host-integration.md`](b2-upr-v0.2.2-host-integration.md)

| Field | Value |
|-------|--------|
| Annotated tag | `v0.2.2` |
| Commit | `43c9989291a4c7eab7f9fd57603c851486da287a` |
| Version constant | `UPR_VERSION === '0.2.2'` |
| Host constants | `Biopentra_Upr_Host_Upr_Pin::REQUIRED_*` |

When preparing DEV (later controlled replay):

```bash
cd /opt/biopentra/dev/universal-product-reviews
git fetch --tags
git checkout 43c9989291a4c7eab7f9fd57603c851486da287a
# Verify: git describe --tags --exact-match  → v0.2.2
```

Then:

```bash
wp biopentra-upr-host verify-pilot-preflight
wp biopentra-upr-host verify-native-submit-dev
```

Preflight **fail-closes** until the bind-mount matches the pin.
