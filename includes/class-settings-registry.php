<?php
/**
 * Settings Registry - Handles WordPress settings registration
 */

if (!defined('ABSPATH')) exit;

class NELXJAF_Settings_Registry {
    
    private static $instance = null;
    private $settings_group = 'nelx_jetappt_settings_group';
    private $option_name = 'nelx_jetappt_settings';
    private $google_meet_option_name = 'nelx_google_meet_settings';
    private $email_branding_option_name = 'nelx_email_branding_settings';
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
    }
    
    public function register_settings() {
        register_setting($this->settings_group, $this->option_name, ['NELXJAF_Settings_Sanitizer', 'sanitize_settings']);
        register_setting($this->settings_group, $this->google_meet_option_name, ['NELXJAF_Settings_Sanitizer', 'sanitize_settings']);
        register_setting($this->settings_group, $this->email_branding_option_name, ['NELXJAF_Settings_Sanitizer', 'sanitize_settings']);
    }
}
