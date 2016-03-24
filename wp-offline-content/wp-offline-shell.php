<?php

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
register_uninstall_hook(__FILE__, array('Offline_Shell_DB', 'on_uninstall'));

?>
