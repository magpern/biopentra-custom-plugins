# Universal Commerce Bundles — host MU safety guard

Status: **validated in a disposable test stack only — NOT deployed to any
served environment.** This document and the guard file are a reviewable
proposal; applying the compose-file change below to a served host is a
separate, deliberate follow-up action by a human operator, out of scope for
this change.

## What this is

Universal Commerce Bundles (UCB — a separately maintained, public generic
plugin) marks a product as a "kit" with the persisted post meta
`_ucb_is_kit`. A kit product must never be purchasable unless UCB has fully
initialised in the *current* request and emitted its documented readiness
handshake, the `ucb_runtime_ready` action (UCB's own published capability
contract — see UCB's `docs/adr/0006-*` and `docs/ARCHITECTURE.md`).

The guard that enforces this lives in this repository, not in UCB, and is
implemented as a single file:

```
mu-plugins/universal-commerce-bundles-safety-guard.php
```

It has **zero code dependency on UCB** — no UCB class, constant, autoloader,
or file is referenced or required anywhere in the guard. It works whether
UCB is installed, active, deactivated, or entirely absent from disk.

## Why a must-use (MU) plugin, not an ordinary plugin

An ordinary plugin — including a "storefront" plugin, and including UCB
itself — can be deactivated from wp-admin, or can fail to load correctly
(a fatal error, a missing dependency, a bad update). A guard implemented as
an ordinary plugin would therefore share the exact failure mode it exists to
catch: it would not be independent of the thing it is guarding against.

A **must-use** plugin (a PHP file placed directly in `wp-content/mu-plugins/`,
not a subdirectory) loads unconditionally on every request, before ordinary
plugins, and cannot be deactivated from wp-admin at all. That is the only
property that makes a "kits are never purchasable without a live readiness
signal" guarantee actually hold when the very thing supplying that signal
has failed.

## Request-local readiness — not persisted, not authoritative from a stored option

The guard tracks readiness as a **request-local flag** (a PHP static
variable), which is `false` at the start of every single request/process by
construction — there is nothing to initialise, because nothing persists it.
Readiness becomes `true` only if, later in that same request, the guard
receives a `ucb_runtime_ready` action whose payload validates (see below).

The guard may optionally read a **persisted option** written by UCB (if one
exists) for informational purposes — e.g. to know which contract version UCB
last recorded. That persisted state is deliberately **never sufficient by
itself** to set readiness true. The reason is the exact failure this whole
guard exists to catch: UCB initialises successfully once, writes a "healthy"
option, and its files are later removed, corrupted, or fail to load in a
subsequent request. If the guard trusted the stored option, it would
wrongly keep allowing kit purchases forever after — a stale record with no
relationship to current health. Request-local-only readiness makes a
partially-initialised (or now-absent) UCB indistinguishable from an absent
one, on every single request, which is the desired behaviour.

## Contract/snapshot version constants

Defined as PHP constants at the top of the guard file itself
(`mu-plugins/universal-commerce-bundles-safety-guard.php`):

| Constant | Value | Meaning |
|---|---|---|
| `CONTRACT_VERSION` | `1` | The capability-contract version this guard's payload validation supports. Must equal the `contract_version` field UCB sends. |
| `SUPPORTED_SNAPSHOT_VERSIONS` | `[1]` | Kit/child-line snapshot schema version(s) this deployment understands. UCB's `snapshot_versions` payload array must contain at least one of these. |

A payload is accepted only if **all** of the following hold:

- `plugin_version` is a non-empty string;
- `contract_version` exactly equals `CONTRACT_VERSION`;
- `snapshot_versions` is a non-empty array intersecting
  `SUPPORTED_SNAPSHOT_VERSIONS`.

Any other payload shape (wrong type, wrong contract version, empty or
non-overlapping snapshot list) leaves readiness `false` — identical to no
signal having arrived at all.

## Enforcement points

Both are required — a display-only guard is not sufficient:

- `woocommerce_is_purchasable` (late priority) — governs what the storefront
  shows as purchasable.
- `woocommerce_add_to_cart_validation` (late priority) — governs the actual
  add-to-cart operation itself (classic cart form and the Store API both
  route through WooCommerce core's add-to-cart validation).

In both cases the guard only ever **adds** a veto: a non-kit product is
returned completely untouched, and if an earlier plugin already vetoed
(`false`), the guard never turns that back into `true`.

## Single-file, read-only mount — never a directory mount

`wp-content/mu-plugins/` is not autoloaded as a package; WordPress core
includes every top-level `.php` file it finds directly inside that
directory. A **directory mount** onto `wp-content/mu-plugins/` would
therefore replace (shadow) whatever the host already has sitting in that
directory — including any unrelated MU-plugins already present outside this
repository, with no relationship to UCB.

The only safe mount shape is a **single-file, read-only** mount of exactly
one file:

```
mu-plugins/universal-commerce-bundles-safety-guard.php  (this repo)
  →  wp-content/mu-plugins/universal-commerce-bundles-safety-guard.php  (read-only)
```

Every other file already present in the target `mu-plugins/` directory is
left completely alone.

## Which runtime contexts need the mount

Enumerated from the real DEV WordPress compose file (not reproduced here —
see that file directly; described generically below since it is host
deployment configuration, not something this repository defines). It
defines one environment/volume anchor reused by every WooCommerce-capable
service, plus one service (the primary web/PHP-FPM-equivalent process) that
inlines the same volume list rather than pulling in the anchor. In this
repository's compose vocabulary that maps to:

1. The primary web service that serves the storefront and executes
   WooCommerce code on every request.
2. The shared WP-CLI volumes anchor, reused by every WP-CLI-based
   container — both the one-shot/mutating profile and the persistent
   DEV-inspection profile — since WP-CLI can invoke WooCommerce
   programmatically (`wp wc`, `wp eval` calling `wc_get_product()`, cron
   tasks, etc.).

No other service in that compose file executes WordPress/WooCommerce PHP
code, so no other service needs the mount. (A mail-relay worker and a
bridge service present in that file only speak HTTP to WordPress's REST API
from outside the PHP process — they do not need the mount.)

### Proposed compose snippet (documentation only — not applied)

Illustrative, generic form of the one line that needs to be added to each
WooCommerce-capable volume list already present in the real compose file
(paths generalised; adjust to match the actual host checkout path used for
this repository, which the real compose file already shows for
`biopentra-storefront`):

```yaml
    volumes:
      # ... existing entries unchanged ...
      - <path-to-this-repo>/mu-plugins/universal-commerce-bundles-safety-guard.php:/var/www/html/wp-content/mu-plugins/universal-commerce-bundles-safety-guard.php:ro
```

This single line is added to:

- the primary web service's own `volumes:` list, and
- the shared WP-CLI volumes anchor (which every WP-CLI-based service reuses),

using the **same repository checkout path convention** already used for
this repo's other single-directory plugin mount in that file, but as a
**single-file** mount with the trailing `:ro` flag (that flag is already
used elsewhere in the real compose file, so this follows existing
convention rather than introducing a new one).

Applying this snippet, and any resulting container recreate, is explicitly
**out of scope for this change** and must be a separate, deliberate action
taken by a human directly against the real compose file.

## Rollback

Rolling back is symmetrical and minimal:

- To remove the guard from a runtime context: delete only the one mount
  line added for that service (above). Never remove or otherwise touch the
  `mu-plugins/` directory as a whole — other, unrelated MU-plugins live
  there and must be left running.
- To remove the guard from this repository entirely: delete only
  `mu-plugins/universal-commerce-bundles-safety-guard.php` and this doc
  file. No other file in this repository references either.

## Explicitly not part of this change

- `<the real compose file>` itself was not edited. The snippet above is a
  proposal for a human to apply as a separate, deliberate change.
- No container was restarted, recreated, or otherwise touched.
- No served (DEV or production) environment was touched in any way.
- UCB's own repository/code was not modified.
- Fulfillment and promotions functionality are separate milestones and are
  not addressed here.
- Kit catalogue/product data was not created or modified.

Validation of the guard's logic was performed entirely inside a disposable,
isolated Docker stack created and torn down solely for this change, with no
bind-mount from any served host path — see the PR description for the
validation evidence.
