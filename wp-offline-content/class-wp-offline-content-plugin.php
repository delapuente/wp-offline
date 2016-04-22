<?php
/**
 * Contains the main class of the plugin.
 *
 * @package OfflineContent
 */

/** Required to manipulate plugin options. */
require_once( plugin_dir_path( __FILE__ ) . 'class-wp-offline-content-options.php' );

/**
 * Represents the plugin and holds the main logic
 */
class WP_Offline_Content_Plugin {
	/**
	 * The singleton instance holding the plugin.
	 *
	 * @var WP_Offline_Content_Plugin
	 */
	private static $instance;

	/**
	 * Name for the JavaScript offline cache storing theme assets.
	 *
	 * @var string
	 */
	public static $cache_name = '__offline-shell';

	/**
	 * Sets the plugin instance up and return the shared instance.
	 *
	 * @returns WP_OfflineContent_Plugin the shared plugin instance.
	 */
	public static function init() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Holds the options instance to manipulate plugin's options.
	 *
	 * @var WP_Offline_Content_Options
	 */
	private $options;

	/** Hooks plugin setup into WordPress actions. */
	private function __construct() {
		$plugin_main_file = plugin_dir_path( __FILE__ ) . 'wp-offline-content.php';
		$this->options = WP_Offline_Content_Options::get_options();
		$this->set_urls();
		$this->setup_sw();
		register_activation_hook( $plugin_main_file, array( $this, 'activate' ) );
		register_deactivation_hook( $plugin_main_file, array( $this, 'deactivate' ) );
	}

	/** Sets the relevant plugin URLs. */
	private function set_urls() {
		$this->sw_scope = home_url( '/' );
	}

	/**
	 * Starts serving the dynamically generated service worker in charge of caching
	 * content and shell.
	 */
	private function setup_sw() {
		Mozilla\WP_SW_Manager::get_manager()->sw()->add_content( array( $this, 'render_sw' ) );
		// TODO: Refactor this!!
		Mozilla\WP_SW_Manager::get_manager()->sw()->add_content( array( $this, 'write_sw' ) );
	}

	/**
	 * Callback to trigger when the plugin is activated. It sets the default
	 * plugin options.
	 */
	public function activate() {
		$this->options->set_defaults();
	}

	/** Callback to trigger when the plugin is disabled. */
	public static function deactivate() {
	}

	/** Writes the portion of the service worker that caches the content. */
	public function render_sw() {
		$sw_scope = $this->sw_scope;
		$this->render(plugin_dir_path( __FILE__ ) . 'lib/js/content-sw.js', array(
			'$debug' => boolval( $this->options->get( 'offline_debug_sw' ) ),
			'$networkTimeout' => intval( $this->options->get( 'offline_network_timeout' ) ),
			'$resources' => $this->get_precache_list(),
			'$excludedPaths' => $this->get_excluded_paths(),
		));
	}

	/**
	 * Writes the portion of the service worker that caches the theme.
	 * TODO: Reconcile with render.
	 */
	public function write_sw() {
		echo self::build_sw(); // WPCS: XSS OK.
	}

	/**
	 * Actually writes the portion of the service worker that caches the theme.
	 */
	public static function build_sw() {
		// Will contain items like 'style.css' => {filemtime() of style.css} .
		$urls = array();

		// Get files and validate they are of proper type.
		$files = get_option( 'offline_shell_files' );
		if ( ! $files || ! is_array( $files ) ) {
			$files = array();
		}

		// Ensure that every file requested to be cached still exists.
		if ( get_option( 'offline_shell_enabled' ) ) {
			foreach ( $files as $index => $file ) {
				$tfile = get_template_directory().'/'.$file;
				if ( file_exists( $tfile ) ) {
					// Use file's last change time in name hash so the SW is updated if any file is updated.
					$urls[ get_template_directory_uri().'/'.$file ] = (string) filemtime( $tfile );
				}
			}
		}

		// Template content into the JS file.
		$contents = file_get_contents( dirname( __FILE__ ).'/lib/js/shell-sw.js' );
		$contents = str_replace( '$name', self::$cache_name, $contents );
		$contents = str_replace( '$urls', json_encode( $urls ), $contents );
		$contents = str_replace( '$debug', intval( get_option( 'offline_shell_debug' ) ), $contents );
		$contents = str_replace( '$raceEnabled', intval( get_option( 'offline_shell_race_enabled' ) ), $contents );
		return $contents;
	}

	/**
	 * Interpolate a template with the values from a map.
	 *
	 * The function will compute a checksum of contents and interpolated
	 * values and will try to replace a special $version placeholder with
	 * this checksum.
	 *
	 * @param string $path path to the template.
	 * @param array  $replacements map between placeholder strings to be found
	 *  in the template and the replacements for them.
	 */
	private function render( $path, $replacements ) {
		$contents = file_get_contents( $path );
		$incremental_hash = hash_init( 'md5' );
		hash_update( $incremental_hash, $contents );
		foreach ( $replacements as $key => $replacement ) {
			$value = json_encode( $replacement );
			hash_update( $incremental_hash, $value );
			$contents = str_replace( $key, $value, $contents );
		}
		$version = json_encode( hash_final( $incremental_hash ) );
		$contents = str_replace( '$version', $version, $contents );
		echo $contents; // WPCS: XSS OK.
	}

	/**
	 * Obtains the array of URLs to be precached.
	 *
	 * @returns string[] list of URL to precache.
	 */
	private function get_precache_list() {
		$precache_options = $this->options->get( 'offline_precache' );
		$precache_list = array();
		if ( $precache_options['pages'] ) {
			foreach ( $this->get_pages() as $page ) {
				$precache_list[ get_page_link( $page ) ] = $page->post_modified;
			}
		}
		return $precache_list;
	}

	/**
	 * Returns the list of published pages.
	 *
	 * @returns WP_Post[] list of published pages.
	 */
	private function get_pages() {
		$page_query = new WP_Query( array( 'post_type' => 'page', 'post_status' => 'publish' ) );
		return $page_query->get_posts();
	}

	/**
	 * Obtains the array of URL prefixes to exclude from handling in the
	 * service worker. An URL is excluded if some of this items is a prefix
	 * of the URL.
	 *
	 * @returns string[] list of URL prefixes.
	 */
	private function get_excluded_paths() {
		return array( admin_url(), content_url(), includes_url() );
	}
}

?>
