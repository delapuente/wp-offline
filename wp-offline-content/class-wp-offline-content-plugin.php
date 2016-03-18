<?php

include_once(plugin_dir_path(__FILE__) . 'class-wp-offline-content-options.php');

class WP_Offline_Content_Plugin {
    private static $instance;

    public static function init() {
        if(!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private $options;

    private function __construct() {
        $plugin_main_file = plugin_dir_path(__FILE__) . 'wp-offline-content.php';
        $this->options = WP_Offline_Content_Options::get_options();
        $this->set_urls();
        $this->setup_sw();
        register_activation_hook($plugin_main_file, array($this, 'activate'));
        register_deactivation_hook($plugin_main_file, array($this, 'deactivate'));
    }

    private function set_urls() {
        $this->sw_scope = home_url('/');
    }

    private function setup_sw() {
        Mozilla\WP_SW_Manager::get_manager()->sw()->add_content(array($this, 'render_sw'));
    }

    public function activate() {
        $this->options->set_defaults();
    }

    public static function deactivate() {
    }

    public function render_sw() {
        $sw_scope = $this->sw_scope;
        $this->render(plugin_dir_path(__FILE__) . 'lib/js/content-sw.js', array(
            '$debug' => boolval($this->options->get('offline_debug_sw')),
            '$networkTimeout' => intval($this->options->get('offline_network_timeout')),
            '$resources' => $this->get_precache_list(),
            '$excludedPaths' => $this->get_excluded_paths()
        ));
    }

    private function render($path, $replacements) {
        $contents = file_get_contents($path);
        $incremental_hash = hash_init('md5');
        hash_update($incremental_hash, $contents);
        foreach ($replacements as $key => $replacement) {
            $value = json_encode($replacement);
            hash_update($incremental_hash, $value);
            $contents = str_replace($key, $value, $contents);
        }
        $version = json_encode(hash_final($incremental_hash));
        $contents = str_replace('$version', $version, $contents);
        echo $contents;
    }

    private function get_precache_list() {
        $precache_options = $this->options->get('offline_precache');
        $precache_list = array();
        if ($precache_options['pages']) {
            foreach (get_pages() as $page) {
                $precache_list[get_page_link($page)] = $page->post_modified;
            }
        }
        return $precache_list;
    }

    private function get_excluded_paths() {
        return array(admin_url(), content_url(), includes_url());
    }
}

?>
