<?php
/*
Plugin Name: Offline Content
Description: Allow your users to read your content even while offline.
Plugin URI: https://github.com/delapuente/wp-offline-content
Version: 0.6.0
Author: Mozilla
Author URI: https://www.mozilla.org/
License: GPLv2 or later
Text Domain: offline-content
*/


load_plugin_textdomain('offline-content', false, dirname(plugin_basename(__FILE__)) . '/lang');

include_once(plugin_dir_path(__FILE__) . 'vendor/autoload.php');
include_once(plugin_dir_path(__FILE__) . 'class-wp-offline-content-plugin.php');

if (is_admin()) {
    include_once(plugin_dir_path(__FILE__) . 'class-wp-offline-content-admin.php');
    WP_Offline_Content_Admin::init();
}
WP_Offline_Content_Plugin::init();


/* Offline Shell */
require_once(plugin_dir_path(__FILE__).'wp-offline-shell-main.php');
require_once(plugin_dir_path(__FILE__).'wp-offline-shell-db.php');

Offline_Shell_DB::init();
Offline_Shell_Main::init();

if (is_admin()) {
  require_once(plugin_dir_path(__FILE__).'wp-offline-shell-admin.php');
  Offline_Shell_Admin::init();
}

register_activation_hook(__FILE__, array('Offline_Shell_DB', 'on_activate'));
register_deactivation_hook(__FILE__, array('Offline_Shell_DB', 'on_deactivate'));

?>
