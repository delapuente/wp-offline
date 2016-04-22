<?php
/**
 * Contains the class for configuring the plugin
 *
 * The class WP_Offline_Content_Admin adds the menu entry for configuring
 * the plugin and builds the UI for the administrative pages.
 *
 * @package OfflineContent
 */

/** Requiring access to plugin options. */
require_once( plugin_dir_path( __FILE__ ) . 'class-wp-offline-content-options.php' );

/**
 * Registers the administration menu in the dashboard and composes the UI
 * of configuration sections.
 *
 * Based on: https://codex.wordpress.org/Creating_Options_Pages#Example_.232
 */
class WP_Offline_Content_Admin {
	/**
	 * Singleton for the class.
	 *
	 * @var WP_Offline_Content_Admin
	 */
	private static $instance;

	/**
	 * Unique id to refer to the options page inside WordPress.
	 *
	 * @var string
	 */
	public static $options_page_id = 'offline-options';

	/**
	 * Unique used by the options WordPress API to group the plugin setting.
	 *
	 * @var string
	 */
	public static $options_group = 'offline-settings-group';

	/** Gets the singleton instance. */
	public static function init() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Holds the options manager.
	 *
	 * @var WP_Offline_Content_Options
	 */
	private $options;

	/** Hooks UI setup into WordPress actions */
	private function __construct() {
		$this->options = WP_Offline_Content_Options::get_options();
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}

	/** Builds the UI for the options page. */
	public function admin_init() {
		$group = self::$options_group;
		register_setting( $group, 'offline_network_timeout', array( $this, 'sanitize_network_timeout' ) );
		register_setting( $group, 'offline_debug_sw', array( $this, 'sanitize_debug_sw' ) );
		register_setting( $group, 'offline_precache', array( $this, 'sanitize_precache' ) );

		add_settings_section(
			'default',
			'',
			function () {},
			self::$options_page_id
		);

		add_settings_field(
			'debug-sw',
			__( 'Debug service worker', 'offline-content' ),
			array( $this, 'debug_sw_input' ),
			self::$options_page_id,
			'default'
		);

		add_settings_section(
			'precache',
			__( 'Precache', 'offline-content' ),
			array( $this, 'print_precache_info' ),
			self::$options_page_id
		);

		add_settings_field(
			'precache',
			__( 'Content', 'offline-content' ),
			array( $this, 'precache_input' ),
			self::$options_page_id,
			'precache'
		);

		add_settings_section(
			'serving-policy',
			__( 'Serving policy', 'offline-content' ),
			array( $this, 'print_serving_policy_info' ),
			self::$options_page_id
		);

		add_settings_field(
			'network-timeout',
			__( 'Network timeout', 'offline-content' ),
			array( $this, 'network_timeout_input' ),
			self::$options_page_id,
			'serving-policy'
		);
	}

	/** Builds the menu entry in the WordPress Dashboard. */
	public function admin_menu() {

		add_menu_page(
			__( 'Offline', 'offline-content' ),
			__( 'Offline', 'offline-content' ),
			'manage_options',
			'offline-content',
			array( $this, 'create_content_admin_page' )
		);

		add_submenu_page(
			'offline-content',
			__( 'Design', 'offline-content' ),
			__( 'Design', 'offline-content' ),
			'manage_options',
			'offline-content-shell',
			array( $this, 'create_shell_admin_page' )
		);

	}

	/** Adds the actual HTML of the options page. */
	public function create_content_admin_page() {
		include_once( plugin_dir_path( __FILE__ ) . 'lib/pages/admin.php' );
	}

	/** Adds the actual HTML of the shell options page. */
	public function create_shell_admin_page() {
		Offline_Shell_Admin::process_options();
		Offline_Shell_Admin::options();
	}

	/** Builds the input for entering network time out. */
	public function network_timeout_input() {
		$network_timeout = $this->options->get( 'offline_network_timeout' ) / 1000;
		?>
		<input id="offline-network-timeout" type="number" name="offline_network_timeout"
		 value="<?php echo esc_attr( $network_timeout ); ?>" min="1" step="1"
		 class="small-text"/> <?php esc_html_e( 'seconds before serving cached content', 'offline-content' ); ?>
		<?php
	}

	/** Builds the widget to enable or disable debug messages. */
	public function debug_sw_input() {
		$debug_sw = $this->options->get( 'offline_debug_sw' );
		?>
		<label>
		  <input id="offline-debug-sw" type="checkbox" name="offline_debug_sw"
		   value="true" <?php echo $debug_sw ? 'checked="checked"' : ''; ?>/>
			<?php esc_html_e( 'Enable debug traces from the service worker in the console.', 'offline-content' ); ?>
		</label>
		<?php
	}

	/** Builds the widget to select which precaching options are enabled. */
	public function precache_input() {
		$precache = $this->options->get( 'offline_precache' );
		?>
		<label>
		  <input id="offline-precache" type="checkbox" name="offline_precache[pages]"
		   value="pages" <?php echo $precache['pages'] ? 'checked="checked"' : ''; ?>/>
			<?php esc_html_e( 'Precache published pages.', 'offline-content' ); ?>
		</label>
		<?php
	}

	/**
	 * Converts network timeout to milliseconds and check its validity.
	 *
	 * @param string $value network timeout in seconds, sent from HTML.
	 */
	public function sanitize_network_timeout( $value ) {
		$value = $value * 1000; // Convert to milliseconds.
		if ( isset( $value ) && $value < 1000 ) {
			add_settings_error(
				'network_timeout',
				'incorrect-network-timeout',
				__( 'Network timeout must be at least 1 second.', 'offline-content' )
			);
			$value = $this->options->get( 'offline_network_timeout' );
		}
		return $value;
	}

	/**
	 * Converts the debug flag into a boolean.
	 *
	 * @param string|null $value debug flag, sent from HTML.
	 */
	public function sanitize_debug_sw( $value ) {
		return isset( $value );
	}

	/**
	 * Converts precaching options into a table of boolean flags.
	 *
	 * @param string[] $value table of set entries, sent from HTML.
	 */
	public function sanitize_precache( $value ) {
		$sanitized = array();
		$sanitized['pages'] = isset( $value['pages'] );
		return $sanitized;
	}

	/** Prints sensible information about the serving policy section. */
	public function print_serving_policy_info() {
		?>
		<p><?php esc_html_e( 'Offline plugin prefers to serve fresh living content from the Internet but it will serve cached content in case network is not available or not reliable.', 'offline-content' );?></p>
		<?php
	}

	/** Prints sensible information about the precache section. */
	public function print_precache_info() {
		?>
		<p><?php esc_html_e( 'Precache options allows you to customize which content will be available even if the user never visit it before.', 'offline-content' );?></p>
		<?php
	}
}

?>
