<?php
/**
 * Plugin Name: Universal Commerce Bundles — Safety Guard (MU)
 * Description: Blocks purchase of any product carrying the Universal Commerce
 *              Bundles (UCB) kit marker unless UCB has fully initialised in
 *              the *current* request and emitted its documented readiness
 *              handshake, `ucb_runtime_ready`. Independent of UCB's presence,
 *              activation state, or bootstrap success.
 * Author:      Biopentra host ops
 *
 * This is a MUST-USE plugin: it loads unconditionally on every request and
 * cannot be deactivated from wp-admin, unlike an ordinary plugin (including
 * UCB itself, or any storefront plugin). That independence is the entire
 * point — a guard that could fail or be disabled the same way the thing it
 * guards against fails would not guard against anything.
 *
 * Contract implemented here (host side of UCB's published ADR-0006
 * capability contract — see UCB's own docs/adr/0006-*.md and
 * docs/ARCHITECTURE.md "Capability contract" table):
 *
 *   1. UCB emits `ucb_runtime_ready` exactly once, as the final successful
 *      step of its own bootstrap, with payload:
 *        plugin_version:    non-empty string
 *        contract_version:  int (see CONTRACT_VERSION below)
 *        snapshot_versions: int[] (see SUPPORTED_SNAPSHOT_VERSIONS below)
 *   2. This guard starts EVERY request with readiness = false. Readiness is
 *      request-local (a static var), never persisted, and is set true only
 *      after the payload above validates against the constants below.
 *   3. A persisted option (if one exists at all) is read here only for
 *      informational / contract-version-checking purposes. It can NEVER by
 *      itself set readiness true — a stale "healthy" option surviving a
 *      plugin's later removal or corruption is exactly the failure mode this
 *      guard exists to catch.
 *   4. At a late priority on `woocommerce_is_purchasable` and on
 *      `woocommerce_add_to_cart_validation`, any product whose
 *      `_ucb_is_kit` post meta is truthy is blocked unless readiness is
 *      true. Non-kit products are returned untouched, and an earlier
 *      plugin's veto (a `false`/false-y value already on the filter) is
 *      never overridden back to true — this guard only ever adds a veto.
 *
 * Deliberately has ZERO code dependency on UCB: no UCB class, constant,
 * autoloader, or `require`/`include` of any UCB file appears anywhere below.
 * `_ucb_is_kit` is read as plain post meta, which persists in the database
 * regardless of whether UCB's plugin files exist on disk at all.
 *
 * Safe to drop into any WordPress install: if WooCommerce itself is not
 * present, registration is skipped entirely (see the `plugins_loaded` guard
 * at the bottom) — no fatal, no attempt to load anything UCB-related.
 */

namespace Biopentra\MuGuards\KitPurchasabilityGuard;

// No direct access.
defined( 'ABSPATH' ) || exit;

/**
 * The capability-contract version this guard supports. Must equal the
 * `contract_version` UCB sends in its `ucb_runtime_ready` payload (ADR-0006).
 * Bump this only in lockstep with a deliberate, reviewed change to what this
 * guard accepts — it is a compatibility gate, not a version label.
 */
const CONTRACT_VERSION = 1;

/**
 * Kit/child-line snapshot schema version(s) this guard's host deployment
 * currently supports (ADR-0003 on the UCB side). UCB's payload must include
 * at least one version from this list in its `snapshot_versions` array.
 */
const SUPPORTED_SNAPSHOT_VERSIONS = array( 1 );

/**
 * Post meta key UCB persists on a product to mark it as a kit. Read as a
 * plain string key — no UCB constant referenced.
 */
const KIT_MARKER_META_KEY = '_ucb_is_kit';

/**
 * Option name of UCB's persisted contract record, if one exists. Read here
 * only for informational purposes (e.g. future diagnostics); never used to
 * set readiness. Guessed conservatively — if the option is absent, the
 * `get_option()` call below simply returns false and is ignored.
 */
const PERSISTED_CONTRACT_OPTION = 'ucb_contract_record';

/**
 * Request-local readiness signal. Plain static function-local state, reset
 * to false on every fresh PHP request/process by definition (this is not
 * persisted anywhere) — exactly the semantics the contract requires.
 *
 * @param bool|null $set  Pass true to mark the runtime ready (only ever
 *                        called from the validated `ucb_runtime_ready`
 *                        listener below). Omit to just read the flag.
 * @return bool Current readiness state.
 */
function runtime_ready( $set = null ) {
	static $ready = false;

	if ( true === $set ) {
		$ready = true;
	}

	return $ready;
}

/**
 * Validates a `ucb_runtime_ready` payload against this guard's locally
 * defined contract/snapshot expectations. Returns true only if every field
 * is present, correctly typed, and matches what this deployment supports.
 *
 * @param mixed $payload The value passed to the `ucb_runtime_ready` action.
 * @return bool
 */
function payload_is_valid( $payload ) {
	if ( ! is_array( $payload ) ) {
		return false;
	}

	if ( empty( $payload['plugin_version'] ) || ! is_string( $payload['plugin_version'] ) ) {
		return false;
	}

	if ( ! isset( $payload['contract_version'] ) || CONTRACT_VERSION !== $payload['contract_version'] ) {
		return false;
	}

	if ( empty( $payload['snapshot_versions'] ) || ! is_array( $payload['snapshot_versions'] ) ) {
		return false;
	}

	$supported = array_intersect( $payload['snapshot_versions'], SUPPORTED_SNAPSHOT_VERSIONS );

	if ( empty( $supported ) ) {
		return false;
	}

	return true;
}

/**
 * Handler for the `ucb_runtime_ready` action. Sets request-local readiness
 * true only if the payload validates; otherwise leaves readiness false and
 * does nothing further (no notice, no logging — a bad payload is treated
 * identically to no signal at all, per the contract).
 *
 * @param mixed $payload
 */
function on_ucb_runtime_ready( $payload ) {
	if ( payload_is_valid( $payload ) ) {
		runtime_ready( true );
	}
}

/**
 * True if the given product is a UCB kit, read directly from post meta —
 * no UCB code involved.
 *
 * @param int $product_id
 * @return bool
 */
function product_is_kit( $product_id ) {
	return (bool) get_post_meta( $product_id, KIT_MARKER_META_KEY, true );
}

/**
 * `woocommerce_is_purchasable` filter — late priority so any earlier
 * plugin's veto (a filter that already returned false) is preserved: this
 * guard only ever turns a true into a false, never a false back into true.
 *
 * @param bool        $is_purchasable
 * @param \WC_Product $product
 * @return bool
 */
function filter_is_purchasable( $is_purchasable, $product ) {
	if ( ! $is_purchasable ) {
		// Already vetoed by something else — never re-enable it.
		return $is_purchasable;
	}

	if ( ! $product || ! method_exists( $product, 'get_id' ) ) {
		return $is_purchasable;
	}

	if ( ! product_is_kit( $product->get_id() ) ) {
		// Not a kit — completely untouched by this guard.
		return $is_purchasable;
	}

	return runtime_ready();
}

/**
 * `woocommerce_add_to_cart_validation` filter — blocks the actual
 * add-to-cart operation (classic cart and Store API both funnel through
 * WooCommerce core's add-to-cart validation), independent of the
 * purchasability display filter above.
 *
 * @param bool $passed
 * @param int  $product_id
 * @return bool
 */
function filter_add_to_cart_validation( $passed, $product_id ) {
	if ( ! $passed ) {
		return $passed;
	}

	if ( ! product_is_kit( $product_id ) ) {
		return $passed;
	}

	if ( ! runtime_ready() ) {
		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice(
				__( 'This product is temporarily unavailable for purchase.', 'biopentra' ),
				'error'
			);
		}

		return false;
	}

	return $passed;
}

/**
 * Registers this guard's hooks. Called only once WordPress core (and
 * therefore `add_action`/`get_post_meta`/etc.) is available, and only if
 * WooCommerce itself is present — see the `plugins_loaded` gate below.
 */
function register() {
	add_action( 'ucb_runtime_ready', __NAMESPACE__ . '\\on_ucb_runtime_ready', 10, 1 );

	// Late priority (contract term 5): let every other plugin's veto stand.
	add_filter( 'woocommerce_is_purchasable', __NAMESPACE__ . '\\filter_is_purchasable', 999, 2 );
	add_filter( 'woocommerce_add_to_cart_validation', __NAMESPACE__ . '\\filter_add_to_cart_validation', 999, 2 );
}

/**
 * Registration gate. Runs on `plugins_loaded` (by which point WooCommerce,
 * if installed and active, has defined the `WooCommerce` class) so that on
 * a WordPress install without WooCommerce at all, this file loads (it must,
 * being an MU-plugin) but registers nothing and never touches a WooCommerce
 * hook, class, or function that would not exist. No UCB file, class, or
 * constant is referenced anywhere in this gate either.
 */
add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		register();
	}
);

/*
 * Note on PERSISTED_CONTRACT_OPTION: intentionally unused for anything
 * other than documentation of the split above. A future diagnostic (e.g. an
 * admin notice comparing the persisted contract's version to
 * CONTRACT_VERSION) may read it with `get_option( PERSISTED_CONTRACT_OPTION )`,
 * but no such read exists yet, and none is required to satisfy the guard
 * contract — readiness is decided solely by `runtime_ready()` above.
 */
