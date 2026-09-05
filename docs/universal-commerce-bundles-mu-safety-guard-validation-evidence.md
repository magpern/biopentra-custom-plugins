# UCB host MU safety guard — validation evidence

This records how the guard at
`mu-plugins/universal-commerce-bundles-safety-guard.php` was validated before
this PR was opened. All validation ran in a **disposable, isolated Docker
stack** (its own project name, its own private network, no host ports
published, no bind-mount from any served/DEV path) built and torn down
solely for this change — nothing here was run against DEV or production.

## Stack shape

- `db` — MariaDB, same major version family as this host's real DB image.
- `wordpress` — stock `wordpress:php8.3-apache` image (no custom build),
  WooCommerce installed via `wp plugin install woocommerce --activate`
  from wordpress.org, on a Docker-named (not bind-mounted) volume.
- `wpcli` — a persistent WP-CLI container sharing the same database and the
  same WordPress files volume, used both as the test driver and as the
  stand-in "second/background runtime context" (distinct container/process
  from the `wordpress` web container).
- The guard file, plus two throwaway dummy MU-plugin files created only for
  this test (to prove sibling files in `mu-plugins/` are unaffected by the
  guard's single-file mount), were each mounted individually, read-only,
  directly into `wp-content/mu-plugins/` on both the `wordpress` and
  `wpcli` services — matching the single-file-mount shape this PR proposes
  for real deployment, never a directory mount.

## UCB stand-in choice

Real UCB was **not** pulled into the stack. Instead, `ucb_runtime_ready` was
fired directly via `wp eval` / `do_action(...)` with hand-built payloads,
exactly matching the payload shape UCB's own source documents
(`plugin_version` string, `contract_version` int, `snapshot_versions`
int[]) — this exercises the guard's own contract logic (which is entirely
independent of UCB's code) without requiring UCB's plugin files to be
present at all, which is itself part of what the guard must tolerate.

## Test products

- Product 11 — "Ordinary Product", no `_ucb_is_kit` meta (non-kit control).
- Product 12 — "Kit Product", `_ucb_is_kit` meta set to `1`.

## Results

| # | Case | Result |
|---|---|---|
| 1 | Non-kit product (11) purchasable, UCB entirely absent | `wc_get_product(11)->is_purchasable()` → **PURCHASABLE** |
| 2 | Kit product (12) blocked: no `ucb_runtime_ready` ever fired in the request (models UCB deactivated / files absent / bootstrap failing before emit — all three are, correctly, indistinguishable to the guard) | `is_purchasable()` → **BLOCKED** |
| 2 (stale option) | A stale/pre-existing persisted `ucb_contract_record` option (written directly via `wp option update`, describing a healthy `contract_version: 1`) does not by itself unlock a fresh request with no `ucb_runtime_ready` fired | `is_purchasable()` → **BLOCKED** (option confirmed present via `wp option get`) |
| 3 | Kit product becomes purchasable only after a **valid** `ucb_runtime_ready` payload (`plugin_version: "0.1.0-dev"`, `contract_version: 1`, `snapshot_versions: [1]`) fires in that same request | `is_purchasable()` → **PURCHASABLE**; a subsequent fresh request (new process) with nothing fired → **BLOCKED** again, confirming readiness is request-local, not persisted |
| 4 | Invalid payload shapes never unlock purchase — tried 3 distinct shapes: (a) wrong `contract_version` (`2`), (b) empty `snapshot_versions` (`[]`), (c) non-string `plugin_version` (`123`) | All three → **BLOCKED** |
| 5 | Classic add-to-cart (`WC()->cart->add_to_cart(12, 1)`) blocked with no readiness | Returned `false`; `wc_get_notices('error')` carried "Sorry, this product cannot be purchased." |
| 5 (Store API) | Genuine HTTP Store API add-to-cart (`GET /wp-json/wc/store/v1/cart` for a Cart-Token/Nonce, then `POST /wp-json/wc/store/v1/cart/add-item` for product 12) with no readiness, via real `curl` from inside the stack's network | `HTTP/1.1 400 Bad Request`, body `{"code":"woocommerce_rest_product_not_purchasable","message":"\"Kit Product\" is not available for purchase.","data":{"status":400}}` |
| 6 | A second runtime context (the dedicated `wpcli` container — a distinct container/process from the `wordpress` web container, confirmed via differing `hostname` output) also blocks a fresh, readiness-absent purchase attempt | `is_purchasable()` → **BLOCKED**; `WC()->cart->add_to_cart(12, 1)` → **REJECTED** |
| 7 | Two dummy placeholder MU-plugin files, mounted individually alongside the guard file in the same `mu-plugins/` directory, remain visible/loaded (not shadowed) | Each dummy file's distinct marker option (`dummy_mu_plugin_1_marker`, `dummy_mu_plugin_2_marker`) was present after every request, and a direct directory listing inside the container showed all three files (`dummy-existing-mu-plugin-1.php`, `dummy-existing-mu-plugin-2.php`, `universal-commerce-bundles-safety-guard.php`) coexisting as separate top-level files |
| 8 | Static grep of the final guard file for `UniversalCommerceBundles`, `use UCB`, `/opt/`, `/home/`, IP-address patterns, and secret-shaped strings | **No matches** — confirmed clean |

Additional sanity check (not one of the 8 required cases, done for
confidence): with WooCommerce itself fully deactivated, the guard produced
no fatal error and WordPress loaded normally, confirming the
`plugins_loaded` / `class_exists('WooCommerce')` registration gate works as
intended.

## Teardown

`docker compose down -v` removed all 3 containers, the private network, and
both named volumes. Confirmed via `docker ps -a`, `docker network ls`, and
`docker volume ls` (filtered on the disposable stack's project name) — zero
matches after teardown.
