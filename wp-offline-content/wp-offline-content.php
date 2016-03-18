<?php
/*
Plugin Name: Offline
Description: Allow your users to read your content even while offline.
Plugin URI: https://github.com/delapuente/wp-offline-content
Version: 0.5.0
Author: Mozilla
Author URI: https://www.mozilla.org/
License: GPLv2 or later
Text Domain: offline-content
*/

load_plugin_textdomain('offline-content', false, dirname(plugin_basename(__FILE__)) . '/lang');

include_once(plugin_dir_path(__FILE__) . 'vendor/autoload.php');
include_once(plugin_dir_path(__FILE__) . 'class-wp-offline-content-plugin.php');

include_once(plugin_dir_path(__FILE__) . 'wp-offline-shell.php');

if (is_admin()) {
    include_once(plugin_dir_path(__FILE__) . 'class-wp-offline-content-admin.php');
    WP_Offline_Content_Admin::init();
}
WP_Offline_Content_Plugin::init();

?>
