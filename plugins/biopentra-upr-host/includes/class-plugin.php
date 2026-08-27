<?php
/**
 * Plugin bootstrap.
 *
 * @package Biopentra_Upr_Host
 */

defined( 'ABSPATH' ) || exit;

final class Biopentra_Upr_Host_Plugin {

	/** @var self|null */
	private static $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init(): void {
		Biopentra_Upr_Host_Delivery_Adapter::register();
		Biopentra_Upr_Host_Support_Adapter::register();
		Biopentra_Upr_Host_Invitation_Send_Policy::register();
		Biopentra_Upr_Host_Review_Availability_Ux::register();
		if ( is_admin() ) {
			Biopentra_Upr_Host_Admin_Settings::register();
		}
	}
}
