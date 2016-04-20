<?php

class WP_Offline_Content_Options {
	private static $instance;

	public static $defaults = array(
		'offline_network_timeout' => 4000,
		'offline_cache_name' => 'wpOfflineContent',
		'offline_debug_sw' => false,
		'offline_precache' => array( 'pages' => true ),
		'offline_shell_enabled' => false,
		'offline_shell_files' => array( 'self.css' ),
		'offline_shell_race_enabled' => false,
	);

	public static function get_options() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
	}

	public function set_defaults() {
		foreach ( self::$defaults as $name => $value ) {
			if ( ! get_option( $name ) ) {
				add_option( $name, $value );
			}
		}
	}

	public function remove_all() {
		foreach ( self::$defaults as $name => $value ) {
			delete_option( $name );
		}
	}

	public function set( $name, $value ) {
		update_option( $name, $value );
		return $this;
	}

	public function get( $name ) {
		return get_option( $name );
	}
}

?>
