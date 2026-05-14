<?php
/**
 * Plugin Name: Biopentra Support Desk
 * Description: Support desk with Fluent Forms tickets, Proton Mail Bridge (IMAP/SMTP), and threaded messages.
 * Version: 2.0.0
 * Author: Biopentra
 * Text Domain: biopentra-contact-inbox
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BIOPENTRA_INBOX_VERSION', '2.0.0' );
define( 'BIOPENTRA_INBOX_PATH', plugin_dir_path( __FILE__ ) );
define( 'BIOPENTRA_INBOX_URL', plugin_dir_url( __FILE__ ) );
define( 'BIOPENTRA_INBOX_CAP', 'manage_biopentra_inbox' );

require_once BIOPENTRA_INBOX_PATH . 'includes/class-activator.php';
register_activation_hook( __FILE__, array( 'Biopentra_Contact_Inbox_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Biopentra_Contact_Inbox_Activator', 'deactivate' ) );

/**
 * Load plugin runtime (admin, WP-CLI, or WP-Cron).
 */
function biopentra_inbox_should_load_runtime() {
	if ( is_admin() ) {
		return true;
	}
	if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
		return true;
	}
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return true;
	}
	return false;
}

function biopentra_inbox_init() {
	if ( ! biopentra_inbox_should_load_runtime() ) {
		return;
	}

	require_once BIOPENTRA_INBOX_PATH . 'includes/class-ticket-ref.php';
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-subject-normalizer.php';
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-message-id.php';
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-form-resolver.php';
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-submission-repository.php';
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-reply-repository.php';
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-ticket-repository.php';
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-message-repository.php';
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-inbound-import.php';
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-bridge-smtp.php';
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-ticket-backfill.php';
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-archived-email-cleanup.php';
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-inbox-cron.php';
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-imap-sync.php';
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-support-desk-reset.php';
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-bridge-diagnostics.php';
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-fluent-migration.php';
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-fluent-ticket-bridge.php';
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-email-reply-template.php';
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-mailer.php';
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-settings.php';
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-list-table.php';
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-admin-detail.php';
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-plugin.php';

	Biopentra_Contact_Inbox_Activator::maybe_upgrade();
	Biopentra_Contact_Inbox_Ticket_Backfill::maybe_run_to_email_chunk();

	Biopentra_Contact_Inbox_Bridge_Smtp::init();
	Biopentra_Contact_Inbox_Cron::init();
	add_action( 'plugins_loaded', array( 'Biopentra_Contact_Inbox_Fluent_Ticket_Bridge', 'init' ), 100 );
	Biopentra_Contact_Inbox_Plugin::instance()->init();
}
add_action( 'plugins_loaded', 'biopentra_inbox_init', 20 );

/**
 * Load REST-only dependencies and register worker routes (runs on REST bootstrap).
 */
function biopentra_inbox_rest_api_init() {
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-ticket-ref.php';
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-subject-normalizer.php';
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-message-id.php';
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-ticket-repository.php';
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-message-repository.php';
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-inbound-import.php';
	require_once BIOPENTRA_INBOX_PATH . 'includes/class-rest-worker.php';
	Biopentra_Contact_Inbox_Rest_Worker::register_routes();
}
add_action( 'rest_api_init', 'biopentra_inbox_rest_api_init', 5 );
