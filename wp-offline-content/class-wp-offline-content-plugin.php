<?php

require_once( plugin_dir_path( __FILE__ ) . 'class-wp-offline-content-options.php' );

class WP_Offline_Content_Plugin {
	private static $instance;

	public static $cache_name = '__offline-shell';

	public static function init() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private $options;

	private function __construct() {
		$plugin_main_file = plugin_dir_path( __FILE__ ) . 'wp-offline-content.php';
		$this->options = WP_Offline_Content_Options::get_options();
		$this->set_urls();
		$this->setup_sw();
		register_activation_hook( $plugin_main_file, array( $this, 'activate' ) );
		register_deactivation_hook( $plugin_main_file, array( $this, 'deactivate' ) );
	}

	private function set_urls() {
		$this->sw_scope = home_url( '/' );
	}

	private function setup_sw() {
		Mozilla\WP_SW_Manager::get_manager()->sw()->add_content( array( $this, 'render_sw' ) );
		// TODO: Refactor this!!
		Mozilla\WP_SW_Manager::get_manager()->sw()->add_content( array( $this, 'write_sw' ) );
	}

	public function activate() {
		$this->options->set_defaults();
	}

	public static function deactivate() {
	}

	public function render_sw() {
		$sw_scope = $this->sw_scope;
		$this->render(plugin_dir_path( __FILE__ ) . 'lib/js/content-sw.js', array(
			'$debug' => boolval( $this->options->get( 'offline_debug_sw' ) ),
			'$networkTimeout' => intval( $this->options->get( 'offline_network_timeout' ) ),
			'$resources' => $this->get_precache_list(),
			'$excludedPaths' => $this->get_excluded_paths(),
		));
	}

	// TODO: Reconcile with render.
	public function write_sw() {
		echo self::build_sw(); // WPCS: XSS OK.
	}

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

	private function get_precache_list() {
		$precache_options = $this->options->get( 'offline_precache' );
		$precache_list = array();
		if ( $precache_options['pages'] ) {
			foreach ( get_pages() as $page ) {
				$precache_list[ get_page_link( $page ) ] = $page->post_modified;
			}
		}
		return $precache_list;
	}

	private function get_excluded_paths() {
		return array( admin_url(), content_url(), includes_url() );
	}
}

?>
