<?php
/**
 * Contains the class for configuring the plugin
 *
 * The class WP_Offline_Content_Admin adds the menu entry for configuring
 * the plugin.
 *
 * @package OfflineContent
 */

/** Option pages for offline content and offline shell capabilities.  */
require_once( plugin_dir_path( __FILE__ ) . 'admin/class-content-options.php' );
require_once( plugin_dir_path( __FILE__ ) . 'admin/class-shell-options.php' );

/**
 * Registers the administration menu in the dashboard.
 */
class WP_Offline_Content_Admin {
	/**
	 * Singleton for the class.
	 *
	 * @var WP_Offline_Content_Admin
	 */
	private static $instance;

	/** Initializes and setup administration entries for the plugin. */
	public static function init() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
	}

	/** Hooks UI setup into WordPress actions */
	private function __construct() {
        WP_Offline_Content_Content_Options::get_page()->init();
        WP_Offline_Content_Shell_Options::get_page()->init();
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	/** Builds the menu entry in the WordPress Dashboard. */
	public function admin_menu() {

		add_menu_page(
			__( 'Offline', 'offline-content' ),
			__( 'Offline', 'offline-content' ),
			'manage_options',
			'offline-content',
			array( $this, 'create_content_options_page' )
		);

		add_submenu_page(
			'offline-content',
			__( 'Design', 'offline-content' ),
			__( 'Design', 'offline-content' ),
			'manage_options',
			'offline-content-shell',
			array( $this, 'create_shell_options_page' )
		);

	}

	/** Adds the actual HTML of the offline content options page. */
	public function create_content_options_page() {
		WP_Offline_Content_Content_Options::get_page()->render();
	}

	/** Adds the actual HTML of the shell options page. */
	public function create_shell_options_page() {
		WP_Offline_Content_Shell_Options::get_page()->render();
	}
}

?>
