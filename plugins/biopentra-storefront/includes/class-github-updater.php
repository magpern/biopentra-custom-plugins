<?php
/**
 * GitHub Releases updater — monorepo production ZIP assets only.
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Offers updates from biopentra-custom-plugins releases tagged storefront-v*.
 */
final class Biopentra_Storefront_Github_Updater {

	private const API_RELEASES = 'https://api.github.com/repos/magpern/biopentra-custom-plugins/releases?per_page=30';

	private const TAG_PREFIX = 'storefront-v';

	private const PLUGIN_SLUG = 'biopentra-storefront';

	private const TRANSIENT_RELEASE = 'biopentra_storefront_github_release';

	private const CACHE_TTL = 12 * HOUR_IN_SECONDS;

	private static ?self $instance = null;

	private string $plugin_basename;

	private string $installed_version;

	public static function maybe_init(): void {
		if ( ! self::is_enabled() ) {
			return;
		}

		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		self::$instance->register_hooks();
	}

	public static function is_enabled(): bool {
		if ( defined( 'BIOPENTRA_STOREFRONT_DISABLE_GITHUB_UPDATER' ) && BIOPENTRA_STOREFRONT_DISABLE_GITHUB_UPDATER ) {
			return false;
		}

		/** @var bool|null $filtered */
		$filtered = apply_filters( 'biopentra_storefront_github_updater_enabled', null );
		if ( null !== $filtered ) {
			return (bool) $filtered;
		}

		$env = function_exists( 'wp_get_environment_type' )
			? wp_get_environment_type()
			: 'production';

		return 'production' === $env;
	}

	public static function is_prerelease_install_version( string $version ): bool {
		return (bool) preg_match( '/-(dev|snapshot|pilot|alpha|beta|rc)(\.|$|-)/i', $version );
	}

	public static function base_version( string $version ): string {
		if ( self::is_prerelease_install_version( $version ) ) {
			return (string) preg_replace( '/-(dev|snapshot|pilot|alpha|beta|rc).*$/i', '', $version );
		}
		return $version;
	}

	public static function should_offer_update( string $installed, string $remote ): bool {
		$installed = self::normalize_version( $installed );
		$remote    = self::normalize_version( $remote );

		if ( '' === $remote || ! preg_match( '/^\d+\.\d+\.\d+/', $remote ) ) {
			return false;
		}

		if ( self::is_prerelease_install_version( $installed ) ) {
			$offer = version_compare( $remote, self::base_version( $installed ), '>' );
		} else {
			$offer = version_compare( $remote, $installed, '>' );
		}

		return (bool) apply_filters( 'biopentra_storefront_github_updater_should_offer_update', $offer, $installed, $remote );
	}

	private function __construct() {
		$this->plugin_basename   = plugin_basename( BIOPENTRA_STOREFRONT_FILE );
		$this->installed_version = defined( 'BIOPENTRA_STOREFRONT_VERSION' )
			? (string) BIOPENTRA_STOREFRONT_VERSION
			: '0.0.0';
	}

	private function register_hooks(): void {
		add_action( 'wp_update_plugins', array( __CLASS__, 'clear_release_cache' ), 1 );
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'filter_update_plugins' ) );
		add_filter( 'plugins_api', array( $this, 'filter_plugins_api' ), 20, 3 );
	}

	/**
	 * Drop cached GitHub release when WordPress refreshes plugin updates.
	 */
	public static function clear_release_cache(): void {
		delete_site_transient( self::TRANSIENT_RELEASE );
	}

	/**
	 * Normalize semver for comparison (supports 0.5.3 and v0.5.3).
	 */
	public static function normalize_version( string $version ): string {
		$version = ltrim( trim( $version ), 'vV' );
		if ( preg_match( '/^(\d+\.\d+\.\d+)/', $version, $matches ) ) {
			return $matches[1];
		}
		return $version;
	}

	/**
	 * @param object|false $transient Update plugins transient.
	 * @return object|false
	 */
	public function filter_update_plugins( $transient ) {
		if ( ! is_object( $transient ) || empty( $transient->checked ) ) {
			return $transient;
		}

		if ( ! isset( $transient->checked[ $this->plugin_basename ] ) ) {
			return $transient;
		}

		$installed = self::normalize_version( (string) $transient->checked[ $this->plugin_basename ] );

		$release = $this->get_latest_release();
		if ( '' === $release['version'] || '' === $release['package'] ) {
			return $transient;
		}

		if ( ! self::should_offer_update( $installed, $release['version'] ) ) {
			return $transient;
		}

		$response               = new stdClass();
		$response->slug         = self::PLUGIN_SLUG;
		$response->plugin       = $this->plugin_basename;
		$response->new_version  = $release['version'];
		$response->url          = $release['url'];
		$response->package      = $release['package'];
		$response->icons        = array();
		$response->banners      = array();
		$response->banners_rtl  = array();
		$response->tested       = '';
		$response->requires_php = '7.4';
		$response->requires     = '6.0';

		$transient->response[ $this->plugin_basename ] = $response;

		return $transient;
	}

	/**
	 * @param false|object|array $result Plugin API result.
	 * @param string             $action API action.
	 * @param object             $args   Query args.
	 * @return false|object|array
	 */
	public function filter_plugins_api( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( ! isset( $args->slug ) || $args->slug !== self::PLUGIN_SLUG ) {
			return $result;
		}

		$release = $this->get_latest_release();
		if ( '' === $release['version'] ) {
			return $result;
		}

		$info                = new stdClass();
		$info->name          = 'Biopentra Storefront';
		$info->slug          = self::PLUGIN_SLUG;
		$info->version       = $release['version'];
		$info->author        = '<a href="https://github.com/magpern">magpern</a>';
		$info->homepage      = $release['url'];
		$info->download_link = $release['package'];
		$info->requires      = '6.0';
		$info->requires_php  = '7.4';
		$info->sections      = array(
			'description' => __( 'Consolidated storefront modules for Biopentra WooCommerce sites.', 'biopentra-storefront' ),
			'changelog'   => '' !== $release['notes'] ? wp_kses_post( $release['notes'] ) : '',
		);

		return $info;
	}

	/**
	 * @return array{version:string,package:string,url:string,notes:string}
	 */
	private function get_latest_release(): array {
		$cached = get_site_transient( self::TRANSIENT_RELEASE );
		if ( is_array( $cached ) && isset( $cached['version'] ) ) {
			return $cached;
		}

		$parsed = $this->fetch_latest_release();
		set_site_transient( self::TRANSIENT_RELEASE, $parsed, self::CACHE_TTL );

		return $parsed;
	}

	/**
	 * @return array{version:string,package:string,url:string,notes:string}
	 */
	private function fetch_latest_release(): array {
		$empty = array(
			'version' => '',
			'package' => '',
			'url'     => '',
			'notes'   => '',
		);

		$response = wp_remote_get(
			self::API_RELEASES,
			array(
				'timeout' => 15,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'biopentra-storefront-updater/' . $this->installed_version,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $empty;
		}

		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return $empty;
		}

		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) ) {
			return $empty;
		}

		$best = $empty;

		foreach ( $data as $release ) {
			if ( ! is_array( $release ) ) {
				continue;
			}
			if ( ! empty( $release['draft'] ) || ! empty( $release['prerelease'] ) ) {
				continue;
			}

			$tag = isset( $release['tag_name'] ) ? (string) $release['tag_name'] : '';
			$version = self::version_from_tag( $tag );
			if ( '' === $version ) {
				continue;
			}

			$package = $this->find_release_zip_url( $release, $version );
			if ( '' === $package ) {
				continue;
			}

			if ( '' === $best['version'] || version_compare( $version, $best['version'], '>' ) ) {
				$best = array(
					'version' => $version,
					'package' => $package,
					'url'     => isset( $release['html_url'] ) ? (string) $release['html_url'] : '',
					'notes'   => isset( $release['body'] ) ? (string) $release['body'] : '',
				);
			}
		}

		return $best;
	}

	/**
	 * Parse storefront-v* tag to semver (0.5.3 from storefront-v0.5.3 or storefront-vv0.5.3).
	 *
	 * @param string $tag_name Git tag name.
	 */
	private static function version_from_tag( string $tag_name ): string {
		if ( 0 !== strpos( $tag_name, self::TAG_PREFIX ) ) {
			return '';
		}

		$version = substr( $tag_name, strlen( self::TAG_PREFIX ) );
		return self::normalize_version( $version );
	}

	/**
	 * @param array<string,mixed> $data    GitHub release JSON.
	 * @param string              $version Parsed version.
	 */
	private function find_release_zip_url( array $data, string $version ): string {
		if ( empty( $data['assets'] ) || ! is_array( $data['assets'] ) ) {
			return '';
		}

		$expected = self::PLUGIN_SLUG . '-' . $version . '.zip';

		foreach ( $data['assets'] as $asset ) {
			if ( ! is_array( $asset ) ) {
				continue;
			}
			$name = isset( $asset['name'] ) ? (string) $asset['name'] : '';
			if ( $name !== $expected ) {
				continue;
			}
			$url = isset( $asset['browser_download_url'] ) ? (string) $asset['browser_download_url'] : '';
			if ( '' !== $url && false !== strpos( $url, 'github.com' ) ) {
				return $url;
			}
		}

		return '';
	}
}
