# UPR Host Adapter (private host pointer)

The UPR host adapter **no longer lives in this monorepo**.

| Item | Value |
|------|--------|
| Public repository | https://github.com/magpern/upr-host-adapter |
| Plugin slug | `upr-host-adapter` |
| Current DEV pin | annotated tag **`v0.1.0`** |
| Extraction freeze | [`upr-host-adapter-extraction.md`](upr-host-adapter-extraction.md) |

Embedded `plugins/biopentra-upr-host` (including former candidate **0.1.5**) has been **removed** from this repository and is **superseded**. Do not package or deploy `biopentra-upr-host` `0.1.5`.

DEV bind-mount source: `/opt/biopentra/dev/upr-host-adapter` (see VPS `apps/wordpress/compose.yml`).
