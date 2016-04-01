<?php

include_once(plugin_dir_path(__FILE__) . 'class-wp-offline-content-options.php');

// Based on: https://codex.wordpress.org/Creating_Options_Pages#Example_.232
class WP_Offline_Content_Admin {
    private static $instance;

    public static $options_page_id = 'offline-options';

    public static $options_group = 'offline-settings-group';

    public static function init() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private $options;

    private function __construct() {
        $this->options = WP_Offline_Content_Options::get_options();
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
    }

    public function admin_init() {
        $group = self::$options_group;
        register_setting($group, 'offline_network_timeout', array($this, 'sanitize_network_timeout'));
        register_setting($group, 'offline_debug_sw', array($this, 'sanitize_debug_sw'));
        register_setting($group, 'offline_precache', array($this, 'sanitize_precache'));

        add_settings_section(
            'default',
            '',
            function () {},
            self::$options_page_id
        );

        add_settings_field(
            'debug-sw',
            __('Debug service worker', 'offline-content'),
            array($this, 'debug_sw_input'),
            self::$options_page_id,
            'default'
        );

        add_settings_section(
            'precache',
            __('Precache', 'offline-content'),
            array($this, 'print_precache_info'),
            self::$options_page_id
        );

        add_settings_field(
            'precache',
            __('Content', 'offline-content'),
            array($this, 'precache_input'),
            self::$options_page_id,
            'precache'
        );

        add_settings_section(
            'serving-policy',
            __('Serving policy', 'offline-content'),
            array($this, 'print_serving_policy_info'),
            self::$options_page_id
        );

        add_settings_field(
            'network-timeout',
            __('Network timeout', 'offline-content'),
            array($this, 'network_timeout_input'),
            self::$options_page_id,
            'serving-policy'
        );
    }

    public function admin_menu() {

      add_menu_page(
        __('Offline', 'offline-content'),
        __('Offline', 'offline-content'),
        'manage_options',
        'offline-content',
        array($this, 'create_content_admin_page')
      );

      add_submenu_page(
        'offline-content',
        __('Design', 'offline-content'),
        __('Design', 'offline-content'),
        'manage_options',
        'offline-content-shell',
        array($this, 'create_shell_admin_page')
      );

    }

    public function create_content_admin_page() {
        include_once(plugin_dir_path(__FILE__) . 'lib/pages/admin.php');
    }

    public function create_shell_admin_page() {
        Offline_Shell_Admin::process_options();
        Offline_Shell_Admin::options();
    }

    public function network_timeout_input() {
        $network_timeout = $this->options->get('offline_network_timeout') / 1000;
        ?>
        <input id="offline-network-timeout" type="number" name="offline_network_timeout"
         value="<?php echo $network_timeout; ?>" min="1" step="1"
         class="small-text"/> <?php _e('seconds before serving cached content', 'offline-content'); ?>
        <?php
    }

    public function debug_sw_input() {
        $debug_sw = $this->options->get('offline_debug_sw');
        ?>
        <label>
          <input id="offline-debug-sw" type="checkbox" name="offline_debug_sw"
           value="true" <?php echo $debug_sw ? 'checked="checked"' : ''; ?>/>
          <?php _e('Enable debug traces from the service worker in the console.', 'offline-content'); ?>
        </label>
        <?php
    }

   public function precache_input() {
        $precache = $this->options->get('offline_precache');
        ?>
        <label>
          <input id="offline-precache" type="checkbox" name="offline_precache[pages]"
           value="pages" <?php echo $precache['pages'] ? 'checked="checked"' : ''; ?>/>
          <?php _e('Precache published pages.', 'offline-content'); ?>
        </label>
        <?php
    }

    public function sanitize_network_timeout($value) {
        $value = $value * 1000; // convert to milliseconds
        if (isset($value) && $value < 1000) {
            add_settings_error(
                'network_timeout',
                'incorrect-network-timeout',
                __('Network timeout must be at least 1 second.', 'offline-content')
            );
            $value = $this->options->get('offline_network_timeout');
        }
        return $value;
    }

    public function sanitize_debug_sw($value) {
        return isset($value);
    }

    public function sanitize_precache($value) {
        $sanitized = array();
        $sanitized['pages'] = isset($value['pages']);
        return $sanitized;
    }

    public function print_serving_policy_info() {
        ?>
        <p><?php _e('Offline plugin prefers to serve fresh living content from the Internet but it will serve cached content in case network is not available or not reliable.', 'offline-content');?></p>
        <?php
    }

    public function print_precache_info() {
        ?>
        <p><?php _e('Precache options allows you to customize which content will be available even if the user never visit it before.', 'offline-content');?></p>
        <?php
    }

}

?>
