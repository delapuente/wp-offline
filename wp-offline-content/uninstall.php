<?php
/**
 * Performs cleaning operations triggered when uninstalling the plugin.
 *
 * @package OfflineContent
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

include_once( plugin_dir_path( __FILE__ ) . 'class-wp-offline-content-options.php' );
WP_Offline_Content_Options::get_options()->remove_all();
?>
