<?php
/**
 * Contains a class to interface with the plugin options
 *
 * @package OfflineContent
 */

/**
 * Provides a common access API hiding persistence implementation.
 */
class WP_Offline_Content_Options {
	/**
	 * The singleton instance.
	 *
	 * @var WP_Offline_Content_Options
	 */
	private static $instance;

	/**
	 * Default values for plugin options.
	 *
	 * This is a map between strings with the names of the options and values.
	 *
	 * @var mixed
	 */
	public static $defaults = array(
		'offline_network_timeout' => 4000,
		'offline_cache_name' => 'wpOfflineContent',
		'offline_debug_sw' => false,
		'offline_precache' => array( 'pages' => true ),
		'offline_shell_enabled' => false,
		'offline_shell_files' => array( 'self.css' ),
		'offline_shell_race_enabled' => false,
	);

	/** Gets the singleton instance. */
	public static function get_options() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/** Writes defaults values for all the plugin options without overwriting them. */
	public function set_defaults() {
		foreach ( self::$defaults as $name => $value ) {
			if ( ! get_option( $name ) ) {
				add_option( $name, $value );
			}
		}
	}

	/** Remove all plugin options. */
	public function remove_all() {
		foreach ( self::$defaults as $name => $value ) {
			delete_option( $name );
		}
	}

	/**
	 * Sets an option value.
	 *
	 * @chainable
	 * @param string $name the property to set.
	 * @param mixed  $value the value for the property.
	 * @returns WP_Offline_Content_Options $this, to allow method chaining.
	 */
	public function set( $name, $value ) {
		update_option( $name, $value );
		return $this;
	}

	/**
	 * Gets the value of an option.
	 *
	 * @param string $name the property to retrieve.
	 * @returns mixed the value of an option or false if it does not exist.
	 */
	public function get( $name ) {
		return get_option( $name );
	}
}

?>
