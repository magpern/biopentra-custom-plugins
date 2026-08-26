<?php
/**
 * Admin settings (manage_woocommerce).
 *
 * @package Biopentra_Upr_Host
 */

defined( 'ABSPATH' ) || exit;

final class Biopentra_Upr_Host_Admin_Settings {

	public static function register(): void {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	public static function add_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'UPR Host', 'biopentra-upr-host' ),
			__( 'UPR Host', 'biopentra-upr-host' ),
			'manage_woocommerce',
			'biopentra-upr-host',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function register_settings(): void {
		register_setting(
			'biopentra_upr_host',
			Biopentra_Upr_Host_Options::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => Biopentra_Upr_Host_Options::defaults(),
			)
		);
	}

	/**
	 * @param mixed $input Raw POST.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $input ): array {
		$defaults = Biopentra_Upr_Host_Options::defaults();
		if ( ! is_array( $input ) ) {
			return $defaults;
		}

		$out                          = $defaults;
		$out['confirm_on_shipped']    = ! empty( $input['confirm_on_shipped'] );
		$out['support_delay_days']    = max( 1, (int) ( $input['support_delay_days'] ?? 14 ) );
		$out['enable_pdp_summary']    = ! empty( $input['enable_pdp_summary'] );
		$out['enable_card_ratings']   = ! empty( $input['enable_card_ratings'] );
		$out['card_ratings_min_count'] = max( 1, (int) ( $input['card_ratings_min_count'] ?? 3 ) );

		foreach ( array( 'support_delay_tags', 'support_suppress_tags', 'support_open_statuses' ) as $list_key ) {
			$raw = $input[ $list_key ] ?? $defaults[ $list_key ];
			if ( is_string( $raw ) ) {
				$raw = preg_split( '/[\s,]+/', $raw ) ?: array();
			}
			$clean = array();
			if ( is_array( $raw ) ) {
				foreach ( $raw as $item ) {
					$item = sanitize_key( (string) $item );
					if ( '' !== $item ) {
						$clean[] = $item;
					}
				}
			}
			$out[ $list_key ] = array_values( array_unique( $clean ) );
		}

		return $out;
	}

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$settings = Biopentra_Upr_Host_Options::all();
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Biopentra UPR Host', 'biopentra-upr-host' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'biopentra_upr_host' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html__( 'Confirm on shipped', 'biopentra-upr-host' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( Biopentra_Upr_Host_Options::OPTION_KEY ); ?>[confirm_on_shipped]" value="1" <?php checked( ! empty( $settings['confirm_on_shipped'] ) ); ?> />
								<?php echo esc_html__( 'Treat MPCF shipped as delivery confirmation (shipped_fallback)', 'biopentra-upr-host' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Support delay days', 'biopentra-upr-host' ); ?></th>
						<td>
							<input type="number" min="1" name="<?php echo esc_attr( Biopentra_Upr_Host_Options::OPTION_KEY ); ?>[support_delay_days]" value="<?php echo esc_attr( (string) $settings['support_delay_days'] ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Delay tags', 'biopentra-upr-host' ); ?></th>
						<td>
							<input type="text" class="large-text" name="<?php echo esc_attr( Biopentra_Upr_Host_Options::OPTION_KEY ); ?>[support_delay_tags]" value="<?php echo esc_attr( implode( ', ', (array) $settings['support_delay_tags'] ) ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Suppress tags', 'biopentra-upr-host' ); ?></th>
						<td>
							<input type="text" class="large-text" name="<?php echo esc_attr( Biopentra_Upr_Host_Options::OPTION_KEY ); ?>[support_suppress_tags]" value="<?php echo esc_attr( implode( ', ', (array) $settings['support_suppress_tags'] ) ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'PDP rating summary', 'biopentra-upr-host' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( Biopentra_Upr_Host_Options::OPTION_KEY ); ?>[enable_pdp_summary]" value="1" <?php checked( ! empty( $settings['enable_pdp_summary'] ) ); ?> />
								<?php echo esc_html__( 'Enable storefront PDP summary module flag', 'biopentra-upr-host' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Card ratings', 'biopentra-upr-host' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( Biopentra_Upr_Host_Options::OPTION_KEY ); ?>[enable_card_ratings]" value="1" <?php checked( ! empty( $settings['enable_card_ratings'] ) ); ?> />
								<?php echo esc_html__( 'Enable loop-card ratings (still requires ≥ min approved reviews)', 'biopentra-upr-host' ); ?>
							</label>
							<p>
								<label>
									<?php echo esc_html__( 'Minimum approved reviews', 'biopentra-upr-host' ); ?>
									<input type="number" min="1" name="<?php echo esc_attr( Biopentra_Upr_Host_Options::OPTION_KEY ); ?>[card_ratings_min_count]" value="<?php echo esc_attr( (string) $settings['card_ratings_min_count'] ); ?>" />
								</label>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
