<?php
/**
 * Plugin Name: Nelx JetAppointments Frontend Manager
 * Description: Front-end schedule editor, provider action buttons, collision-safe reschedule, secure delete, inline modals and available slots endpoint tailored to JetAppointments schema.
 * Version: 1.1.9
 * Author: Nelx Studio
 * Author URI: https://nelxstudio.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /languages
 * Text Domain: nelx-jetappt-frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin Constants
define('NELXJAF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NELXJAF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NELXJAF_PLUGIN_VERSION', '1.1.9');

// Include required files
require_once NELXJAF_PLUGIN_DIR . 'includes/class-core.php';
require_once NELXJAF_PLUGIN_DIR . 'includes/class-timezone-helper.php';
require_once NELXJAF_PLUGIN_DIR . 'includes/class-api.php';
require_once NELXJAF_PLUGIN_DIR . 'includes/class-settings-assets.php';
require_once NELXJAF_PLUGIN_DIR . 'includes/class-shortcodes.php';
require_once NELXJAF_PLUGIN_DIR . 'includes/class-appointment-listings.php';
require_once NELXJAF_PLUGIN_DIR . 'includes/class-settings-page.php';
require_once NELXJAF_PLUGIN_DIR . 'includes/class-settings-sanitizer.php';
require_once NELXJAF_PLUGIN_DIR . 'includes/class-settings-registry.php';
require_once NELXJAF_PLUGIN_DIR . 'includes/class-ajax-handlers.php';
require_once NELXJAF_PLUGIN_DIR . 'includes/class-jfb-field-helper.php';
require_once NELXJAF_PLUGIN_DIR . 'includes/class-google-meet.php';
require_once NELXJAF_PLUGIN_DIR . 'includes/class-notifications.php';
require_once NELXJAF_PLUGIN_DIR . 'includes/class-custom-emails.php';
require_once NELXJAF_PLUGIN_DIR . 'includes/class-custom-emails-hooks.php';
require_once NELXJAF_PLUGIN_DIR . 'includes/class-elementor-widgets.php';

// Only load the format DB values class if JetEngine is active
if (class_exists('Jet_Engine')) {
    require_once NELXJAF_PLUGIN_DIR . 'includes/class-format-db-values.php';
}

// Initialize plugin
add_action('plugins_loaded', function() {
    // Core
    NELXJAF_Core::instance();
    
    // Settings components (admin only)
    if (is_admin()) {
        NELXJAF_Settings_Page::instance();
        NELXJAF_Settings_Assets::instance();
        NELXJAF_Settings_Registry::instance();
        NELXJAF_Ajax_Handlers::instance();
    }
});

// Activation/Deactivation hooks
register_activation_hook(__FILE__, ['NELXJAF_Core', 'activate']);
register_deactivation_hook(__FILE__, ['NELXJAF_Core', 'deactivate']);

// Add settings link
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=nelx-jetappt-settings')) . '">' . esc_html__('Settings', 'nelx-jetappt-frontend') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});
