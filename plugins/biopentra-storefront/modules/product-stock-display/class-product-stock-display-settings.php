<?php
/**
 * Settings for product stock display labels and colors.
 *
 * @package Biopentra_Storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress Settings API registration for stock display.
 */
class Biopentra_Storefront_Product_Stock_Display_Settings {

	const OPTION_KEY   = 'biopentra_storefront_stock_display';
	const OPTION_GROUP = 'biopentra_storefront_stock_display';
	const PAGE_SLUG    = 'biopentra-storefront-stock-display';

	/**
	 * Default settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			'enabled'              => 'yes',
			'only_left_threshold'  => 3,
			'low_stock_threshold'  => 9,
			'text_in_stock'        => 'In stock',
			'text_low_stock'       => 'Low stock',
			'text_only_left'       => 'Only {stock} left',
			'text_out_of_stock'    => 'Out of stock',
			'color_in_stock'       => '#15803d',
			'color_low_stock'      => '#b45309',
			'color_only_left'      => '#b91c1c',
			'color_out_of_stock'   => '#b91c1c',
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get() {
		$saved = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return wp_parse_args( $saved, self::defaults() );
	}

	/**
	 * Whether custom stock display is enabled.
	 */
	public static function is_enabled() {
		$settings = self::get();
		return 'yes' === $settings['enabled'];
	}

	/**
	 * Register admin hooks.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 99 );
	}

	/**
	 * WooCommerce submenu for stock display settings.
	 */
	public static function register_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Stock display', 'biopentra-storefront' ),
			__( 'Stock display', 'biopentra-storefront' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Register option and settings fields.
	 */
	public static function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);

		add_settings_section(
			'biopentra_storefront_stock_display_main',
			__( 'Product stock labels', 'biopentra-storefront' ),
			array( __CLASS__, 'render_section_intro' ),
			self::PAGE_SLUG
		);

		$fields = array(
			'enabled'             => array( __( 'Enable custom stock display', 'biopentra-storefront' ), 'render_field_enabled' ),
			'only_left_threshold' => array( __( '“Only X left” threshold', 'biopentra-storefront' ), 'render_field_only_left_threshold' ),
			'low_stock_threshold' => array( __( 'Low stock threshold', 'biopentra-storefront' ), 'render_field_low_stock_threshold' ),
			'text_in_stock'       => array( __( 'In stock text', 'biopentra-storefront' ), 'render_field_text_in_stock' ),
			'text_low_stock'      => array( __( 'Low stock text', 'biopentra-storefront' ), 'render_field_text_low_stock' ),
			'text_only_left'      => array( __( 'Only-left text', 'biopentra-storefront' ), 'render_field_text_only_left' ),
			'text_out_of_stock'   => array( __( 'Out of stock text', 'biopentra-storefront' ), 'render_field_text_out_of_stock' ),
			'color_in_stock'      => array( __( 'In stock color', 'biopentra-storefront' ), 'render_field_color_in_stock' ),
			'color_low_stock'     => array( __( 'Low stock color', 'biopentra-storefront' ), 'render_field_color_low_stock' ),
			'color_only_left'     => array( __( 'Only-left color', 'biopentra-storefront' ), 'render_field_color_only_left' ),
			'color_out_of_stock'  => array( __( 'Out of stock color', 'biopentra-storefront' ), 'render_field_color_out_of_stock' ),
		);

		foreach ( $fields as $key => $cfg ) {
			add_settings_field(
				$key,
				$cfg[0],
				array( __CLASS__, $cfg[1] ),
				self::PAGE_SLUG,
				'biopentra_storefront_stock_display_main',
				array( 'key' => $key )
			);
		}
	}

	/**
	 * Section description.
	 */
	public static function render_section_intro() {
		echo '<p>';
		esc_html_e(
			'Replaces WooCommerce exact stock counts on the storefront with configurable labels. Does not change inventory or cart behavior.',
			'biopentra-storefront'
		);
		echo '</p>';
	}

	/**
	 * @param array<string, string> $args Field args.
	 */
	public static function render_field_enabled( $args ) {
		unset( $args );
		$settings = self::get();
		printf(
			'<label><input type="checkbox" name="%1$s[enabled]" value="yes" %2$s /> %3$s</label>',
			esc_attr( self::OPTION_KEY ),
			checked( 'yes', $settings['enabled'], false ),
			esc_html__( 'Show custom labels on product pages and loops', 'biopentra-storefront' )
		);
	}

	/**
	 * @param array<string, string> $args Field args.
	 */
	public static function render_field_only_left_threshold( $args ) {
		self::render_number_field( $args['key'], 0, 9999 );
	}

	/**
	 * @param array<string, string> $args Field args.
	 */
	public static function render_field_low_stock_threshold( $args ) {
		self::render_number_field( $args['key'], 0, 9999 );
	}

	/**
	 * @param array<string, string> $args Field args.
	 */
	public static function render_field_text_in_stock( $args ) {
		self::render_text_field( $args['key'] );
	}

	/**
	 * @param array<string, string> $args Field args.
	 */
	public static function render_field_text_low_stock( $args ) {
		self::render_text_field( $args['key'] );
	}

	/**
	 * @param array<string, string> $args Field args.
	 */
	public static function render_field_text_only_left( $args ) {
		self::render_text_field( $args['key'] );
		echo '<p class="description">';
		esc_html_e( 'Use {stock} for the quantity (only shown in this message).', 'biopentra-storefront' );
		echo '</p>';
	}

	/**
	 * @param array<string, string> $args Field args.
	 */
	public static function render_field_text_out_of_stock( $args ) {
		self::render_text_field( $args['key'] );
	}

	/**
	 * @param array<string, string> $args Field args.
	 */
	public static function render_field_color_in_stock( $args ) {
		self::render_color_field( $args['key'] );
	}

	/**
	 * @param array<string, string> $args Field args.
	 */
	public static function render_field_color_low_stock( $args ) {
		self::render_color_field( $args['key'] );
	}

	/**
	 * @param array<string, string> $args Field args.
	 */
	public static function render_field_color_only_left( $args ) {
		self::render_color_field( $args['key'] );
	}

	/**
	 * @param array<string, string> $args Field args.
	 */
	public static function render_field_color_out_of_stock( $args ) {
		self::render_color_field( $args['key'] );
	}

	/**
	 * @param string $key   Setting key.
	 * @param int    $min   Minimum.
	 * @param int    $max   Maximum.
	 */
	private static function render_number_field( $key, $min, $max ) {
		$settings = self::get();
		printf(
			'<input type="number" name="%1$s[%2$s]" value="%3$d" min="%4$d" max="%5$d" class="small-text" />',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $key ),
			(int) $settings[ $key ],
			(int) $min,
			(int) $max
		);
	}

	/**
	 * @param string $key Setting key.
	 */
	private static function render_text_field( $key ) {
		$settings = self::get();
		printf(
			'<input type="text" name="%1$s[%2$s]" value="%3$s" class="regular-text" />',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $key ),
			esc_attr( (string) $settings[ $key ] )
		);
	}

	/**
	 * @param string $key Setting key.
	 */
	private static function render_color_field( $key ) {
		$settings = self::get();
		printf(
			'<input type="text" name="%1$s[%2$s]" value="%3$s" class="regular-text" pattern="#[0-9a-fA-F]{6}" placeholder="#15803d" />',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $key ),
			esc_attr( (string) $settings[ $key ] )
		);
	}

	/**
	 * Settings page markup.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Biopentra stock display', 'biopentra-storefront' ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * @param mixed $input Raw option value.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $input ) {
		$defaults = self::defaults();
		$input    = is_array( $input ) ? $input : array();

		$out = array(
			'enabled'             => ! empty( $input['enabled'] ) && 'yes' === $input['enabled'] ? 'yes' : 'no',
			'only_left_threshold' => self::sanitize_threshold( $input['only_left_threshold'] ?? $defaults['only_left_threshold'] ),
			'low_stock_threshold' => self::sanitize_threshold( $input['low_stock_threshold'] ?? $defaults['low_stock_threshold'] ),
			'text_in_stock'       => self::sanitize_label( $input['text_in_stock'] ?? $defaults['text_in_stock'] ),
			'text_low_stock'      => self::sanitize_label( $input['text_low_stock'] ?? $defaults['text_low_stock'] ),
			'text_only_left'      => self::sanitize_only_left_label( $input['text_only_left'] ?? $defaults['text_only_left'] ),
			'text_out_of_stock'   => self::sanitize_label( $input['text_out_of_stock'] ?? $defaults['text_out_of_stock'] ),
			'color_in_stock'      => self::sanitize_hex_color( $input['color_in_stock'] ?? $defaults['color_in_stock'], $defaults['color_in_stock'] ),
			'color_low_stock'     => self::sanitize_hex_color( $input['color_low_stock'] ?? $defaults['color_low_stock'], $defaults['color_low_stock'] ),
			'color_only_left'     => self::sanitize_hex_color( $input['color_only_left'] ?? $defaults['color_only_left'], $defaults['color_only_left'] ),
			'color_out_of_stock'  => self::sanitize_hex_color( $input['color_out_of_stock'] ?? $defaults['color_out_of_stock'], $defaults['color_out_of_stock'] ),
		);

		if ( $out['low_stock_threshold'] < $out['only_left_threshold'] ) {
			$out['low_stock_threshold'] = $out['only_left_threshold'];
			add_settings_error(
				self::OPTION_KEY,
				'low_stock_threshold',
				__( 'Low stock threshold was raised to match the only-left threshold.', 'biopentra-storefront' ),
				'notice-warning'
			);
		}

		return $out;
	}

	/**
	 * @param mixed $value Raw.
	 * @return int
	 */
	private static function sanitize_threshold( $value ) {
		return max( 0, min( 9999, absint( $value ) ) );
	}

	/**
	 * @param mixed $value Raw.
	 * @return string
	 */
	private static function sanitize_label( $value ) {
		return sanitize_text_field( (string) $value );
	}

	/**
	 * @param mixed $value Raw.
	 * @return string
	 */
	private static function sanitize_only_left_label( $value ) {
		$label = sanitize_text_field( (string) $value );
		if ( '' === $label ) {
			return self::defaults()['text_only_left'];
		}
		return $label;
	}

	/**
	 * @param mixed  $value   Raw color.
	 * @param string $default Fallback hex.
	 * @return string
	 */
	private static function sanitize_hex_color( $value, $default ) {
		$color = sanitize_hex_color( (string) $value );
		return $color ? $color : $default;
	}
}
