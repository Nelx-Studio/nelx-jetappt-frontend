<?php
/**
 * Elementor Widgets Registration
 */

if (!defined('ABSPATH')) exit;

class NELXJAF_Elementor_Widgets {
    private static $instance = null;
    private $core;
    
    public static function instance($core) {
        if (is_null(self::$instance)) {
            self::$instance = new self($core);
        }
        return self::$instance;
    }
    
    private function __construct($core) {
        $this->core = $core;
        
        if (did_action('elementor/loaded')) {
            add_action('elementor/widgets/register', [$this, 'register_widgets']);
            add_action('elementor/elements/categories_registered', [$this, 'add_category']);
        }
    }
    
    public function register_widgets($widgets_manager) {
        // Load and register widget files
        $widget_files = [
            'schedule-editor' => NELXJAF_PLUGIN_DIR . 'elementor/widgets/schedule-editor.php',
            'provider-actions' => NELXJAF_PLUGIN_DIR . 'elementor/widgets/provider-action-buttons.php',
            'client-actions' => NELXJAF_PLUGIN_DIR . 'elementor/widgets/client-action-buttons.php',
            'google-meet' => NELXJAF_PLUGIN_DIR . 'elementor/widgets/google-meet-settings.php',
            'appointment-listings' => NELXJAF_PLUGIN_DIR . 'elementor/widgets/appointment-listings.php',
        ];
        
        foreach ($widget_files as $file) {
            if (file_exists($file)) {
                require_once $file;
            }
        }
        
        // Register widgets if classes exist
        if (class_exists('Elementor_Nelx_Schedule_Editor')) {
            $widgets_manager->register(new \Elementor_Nelx_Schedule_Editor());
        }
        if (class_exists('Elementor_Nelx_Provider_Actions')) {
            $widgets_manager->register(new \Elementor_Nelx_Provider_Actions());
        }
        if (class_exists('Elementor_Nelx_Client_Actions')) {
            $widgets_manager->register(new \Elementor_Nelx_Client_Actions());
        }
        if (class_exists('Elementor_Nelx_Google_Meet_Settings')) {
            $widgets_manager->register(new \Elementor_Nelx_Google_Meet_Settings());
        }
        foreach ([
            'Elementor_Nelx_Staff_Appointments',
            'Elementor_Nelx_Client_Appointments',
            'Elementor_Nelx_Staff_Appointments_Grid',
            'Elementor_Nelx_Client_Appointments_Grid',
        ] as $widget_class) {
            if (class_exists($widget_class)) {
                $widgets_manager->register(new $widget_class());
            }
        }
    }
    
    public function add_category($elements_manager) {
        $elements_manager->add_category(
            'nelx-jetappt',
            [
                'title' => esc_html__('Nelx JetAppointments', 'nelx-jetappt-frontend'),
                'icon' => 'fa fa-calendar',
            ]
        );
    }
}
