<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

include_once(plugin_dir_path(__FILE__) . 'class-wp-offline-content-options.php');
WP_Offline_Content_Options::get_options()->remove_all();

include_once(plugin_dir_path(__FILE__) . 'wp-offline-shell-db.php');
Offline_Shell_DB::init();
Offline_Shell_DB::on_uninstall();
?>
