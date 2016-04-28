<?php
/**
 * Plugin Name: Offline Content
 * Description: Allow your users to read your content even while offline.
 * Plugin URI: https://github.com/mozilla/wp-offline-content
 * Version: 0.6.1
 * Author: Mozilla
 * Author URI: https://www.mozilla.org/
 * License: GPLv2 or later
 * Text Domain: offline-content
 *
 * @package OfflineContent
 */

load_plugin_textdomain( 'offline-content', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );

require_once( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' );

require_once( plugin_dir_path( __FILE__ ) . 'class-wp-offline-content-plugin.php' );
if ( is_admin() ) {
	require_once( plugin_dir_path( __FILE__ ) . 'class-wp-offline-content-admin.php' );
	WP_Offline_Content_Admin::init();
}
WP_Offline_Content_Plugin::init();
?>
