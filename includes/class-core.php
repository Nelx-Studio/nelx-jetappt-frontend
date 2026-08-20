<?php
/**
 * Core Plugin Class - Contains all main functionality
 */

if (!defined('ABSPATH')) exit;

class NELXJAF_Core {
    
    private static $instance = null;
    public $wpdb;
    public $appt_table;
    public $appt_meta_table;
    public $staff_meta_table;
    public $provider_column;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        $this->load_settings();
        
        if (!$this->validate_tables()) {
            add_action('admin_notices', [$this, 'show_table_error_notice']);
        }
        
        $this->init_components();
        $this->setup_hooks();
    }
    
    private function load_settings() {
        $settings = get_option('nelx_jetappt_settings', [
            'provider_meta_table' => 'staff_list_for_appoi_meta',
            'provider_column' => 'staff_id',
        ]);
        
        $this->appt_table = $this->wpdb->prefix . 'jet_appointments';
        $this->appt_meta_table = $this->wpdb->prefix . 'jet_appointments_meta';
        $this->staff_meta_table = $this->wpdb->prefix . ltrim($settings['provider_meta_table'] ?? 'staff_list_for_appoi_meta', $this->wpdb->prefix);
        $this->provider_column = $settings['provider_column'] ?? 'staff_id';
    }
    
    private function validate_tables() {
        $tables = [
            'appt_table' => $this->appt_table,
            'appt_meta_table' => $this->appt_meta_table,
            'staff_meta_table' => $this->staff_meta_table,
        ];
        
        foreach ($tables as $name => $table) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
            $sql = $this->wpdb->prepare("SHOW TABLES LIKE %s", $table);
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $exists = $this->wpdb->get_var($sql);
            if (!$exists) {
                return false;
            }
        }
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
        $sql = $this->wpdb->prepare("DESCRIBE %i", $this->appt_table);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $columns = $this->wpdb->get_col($sql);
        if (!in_array($this->provider_column, $columns)) {
            return false;
        }
        
        return true;
    }
    
    private function init_components() {
        if (class_exists('NELXJAF_API')) {
            NELXJAF_API::instance($this);
        }
        
        if (class_exists('NELXJAF_Shortcodes')) {
            NELXJAF_Shortcodes::instance($this);
        }

        if (class_exists('NELXJAF_Appointment_Listings')) {
            NELXJAF_Appointment_Listings::instance($this);
        }
        
        if (class_exists('NELXJAF_Modal_Manager')) {
            NELXJAF_Modal_Manager::instance();
        }
        
        if (is_admin()) {
            if (class_exists('NELXJAF_Settings_Page')) {
                NELXJAF_Settings_Page::instance();
            }
            if (class_exists('NELXJAF_Settings_Assets')) {
                NELXJAF_Settings_Assets::instance();
            }
            if (class_exists('NELXJAF_Settings_Registry')) {
                NELXJAF_Settings_Registry::instance();
            }
            if (class_exists('NELXJAF_Ajax_Handlers')) {
                NELXJAF_Ajax_Handlers::instance();
            }
        }
        
        if (class_exists('NELXJAF_Elementor_Widgets')) {
            NELXJAF_Elementor_Widgets::instance($this);
        }
        
        if (class_exists('NELXJAF_Google_Meet_Integration')) {
            NELXJAF_Google_Meet_Integration::instance($this);
        }
        
        if (class_exists('NELXJAF_Custom_Emails')) {
            NELXJAF_Custom_Emails::instance();
        }
        
        if (class_exists('NELXJAF_Custom_Emails_Hooks')) {
            NELXJAF_Custom_Emails_Hooks::instance();
        }
    }
    
    private function setup_hooks() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('admin_init', [$this, 'guard_jet_appointments_deactivation']);
    }
    
    public function register_shortcodes() {
        if (class_exists('NELXJAF_Shortcodes')) {
            $shortcodes = NELXJAF_Shortcodes::instance($this);
            add_shortcode('nelx_schedule_editor', [$shortcodes, 'schedule_editor']);
            add_shortcode('nelx_provider_action_buttons', [$shortcodes, 'provider_actions']);
            add_shortcode('nelx_client_action_buttons', [$shortcodes, 'client_actions']);
            if (class_exists('NELXJAF_Appointment_Listings')) {
                $listings = NELXJAF_Appointment_Listings::instance($this);
                $listings->register_shortcodes();
            }
        }
    }
    
    public function enqueue_assets() {
        $base = NELXJAF_PLUGIN_URL;
        $path = NELXJAF_PLUGIN_DIR;
        
        $css_file = $path . 'assets/css/nelx-jetappt.min.css';
        if (file_exists($css_file)) {
            wp_enqueue_style('nelx-jetappt-frontend', $base . 'assets/css/nelx-jetappt.min.css', [], filemtime($css_file));
        }
        
        $flatpickr_css = $path . 'assets/css/flatpickr.min.css';
        if (file_exists($flatpickr_css)) {
            wp_enqueue_style('flatpickr', $base . 'assets/css/flatpickr.min.css', [], '4.6.13');
        }
        
        $js_file = $path . 'assets/js/nelx-jetappt.min.js';
        if (file_exists($js_file)) {
            wp_enqueue_script('nelx-jetappt-frontend', $base . 'assets/js/nelx-jetappt.min.js', ['jquery', 'flatpickr'], filemtime($js_file), true);
        }
        
        $flatpickr_js = $path . 'assets/js/flatpickr.min.js';
        if (file_exists($flatpickr_js)) {
            wp_enqueue_script('flatpickr', $base . 'assets/js/flatpickr.min.js', [], '4.6.13', true);
        }
        
        $timezone_string = get_option('timezone_string');
        $gmt_offset = get_option('gmt_offset');
        
        if (empty($timezone_string)) {
            if ($gmt_offset !== '') {
                $offset = floatval($gmt_offset);
                $hours = floor($offset);
                $minutes = abs(($offset - $hours) * 60);
                $sign = $offset >= 0 ? '+' : '-';
                $timezone_string = sprintf('UTC%s%02d:%02d', $sign, abs($hours), $minutes);
            } else {
                $timezone_string = 'UTC';
            }
        }
        
        $user_id = get_current_user_id();
        $provider_post_id = $this->get_provider_post_id_for_user($user_id);
        $provider_schedule = [];
        $days_off = [];
        $global_working_hours = $this->get_global_working_hours();
        
        if ($provider_post_id) {
            $meta = $this->load_provider_meta($provider_post_id);
            $cs = $meta['custom_schedule'] ?? [];
            $use_custom_schedule = !empty($cs['use_custom_schedule']) || !empty($meta['use_custom_schedule']);
            
            if ($use_custom_schedule) {
                $provider_schedule = $cs['working_hours'] ?? $global_working_hours;
                $days_off = $cs['days_off'] ?? [];
            } else {
                $provider_schedule = $global_working_hours;
            }
        } else {
            $provider_schedule = $global_working_hours;
        }
        
        $formatted_days_off = [];
        foreach ($days_off as $day_off) {
            if (!empty($day_off['startTimeStamp']) && !empty($day_off['endTimeStamp'])) {
                $start_date = gmdate('Y-m-d', intval($day_off['startTimeStamp']) / 1000);
                $end_date = gmdate('Y-m-d', intval($day_off['endTimeStamp']) / 1000);
                $formatted_days_off[] = [
                    'start' => $start_date,
                    'end' => $end_date
                ];
            }
        }
        
        $today = current_time('Y-m-d');
        $listing_manager = class_exists('NELXJAF_Appointment_Listings') ? NELXJAF_Appointment_Listings::instance($this) : null;
        wp_localize_script('nelx-jetappt-frontend', 'NELXJAF', [
            'root' => esc_url_raw(rest_url('nelx-jaf/v1/')),
            'nonce' => wp_create_nonce('wp_rest'),
            'user_id' => get_current_user_id(),
            'timezone_string' => $timezone_string,
            'gmt_offset' => $gmt_offset,
            'provider_schedule' => $provider_schedule,
            'days_off' => $formatted_days_off,
            'global_working_hours' => $global_working_hours,
            'appointment_info_modal' => [
                'staff' => $listing_manager ? $listing_manager->get_modal_fields('staff') : ['start', 'end', 'timezone', 'service', 'client', 'client_email', 'client_phone', 'google_meet', 'appointment_status', 'client_local_date', 'client_local_time', 'client_local_timezone', 'notes'],
                'client' => $listing_manager ? $listing_manager->get_modal_fields('client') : ['date', 'time', 'timezone', 'service', 'provider', 'google_meet', 'appointment_status', 'notes'],
            ],
            'appointment_info_modal_labels' => [
                'staff' => $listing_manager ? $listing_manager->get_modal_labels('staff') : [],
                'client' => $listing_manager ? $listing_manager->get_modal_labels('client') : [],
            ],
            'i18n' => [
                'saved' => __('Changes have been saved successfully', 'nelx-jetappt-frontend'),
                'deleted' => __('Deleted', 'nelx-jetappt-frontend'),
                'areYouSure' => __('Are you sure?', 'nelx-jetappt-frontend'),
                'close' => __('Close', 'nelx-jetappt-frontend'),
                'save' => __('Save', 'nelx-jetappt-frontend'),
                'cancel' => __('Cancel', 'nelx-jetappt-frontend'),
                'saving' => __('Saving…', 'nelx-jetappt-frontend'),
                'no_id' => __('Appointment ID not found. Add ID to your listing or pass it to the shortcode.', 'nelx-jetappt-frontend'),
                'error' => __('An error occurred', 'nelx-jetappt-frontend'),
            ],
            'today' => $today,
        ]);
    
        add_action('wp_print_footer_scripts', function() {
            echo '<script>(function(){var r=document.querySelector(".nelx-schedule-editor");if(!r)return;var a=function(ctx){ctx.querySelectorAll(".nelx-time-dropdown").forEach(function(el){if(!el.querySelector("select")&&!el.querySelector(".nelx-skeleton-line")){var d=document.createElement("div");d.className="nelx-skeleton-line";d.style.height="40px";d.style.width="100%";el.appendChild(d);}})};if(document.readyState!=="loading"){requestAnimationFrame(function(){a(r);});}else{document.addEventListener("DOMContentLoaded",function(){requestAnimationFrame(function(){a(r);});});}})();</script>';
        });
    }
    
    public function guard_jet_appointments_deactivation() {
        if (!is_admin() || !current_user_can('activate_plugins')) return;
        
        add_action('admin_head', [$this, 'disable_jet_appointments_deactivation']);
        add_action('after_plugin_row_jet-appointments-booking/jet-appointments-booking.php', [$this, 'add_deactivation_notice_below_description'], 10, 2);
    }
    
    public function disable_jet_appointments_deactivation() {
        $screen = get_current_screen();
        if ($screen->id === 'plugins') {
            ?>
            <style type="text/css">
                tr[data-plugin="jet-appointments-booking/jet-appointments-booking.php"] .deactivate a {
                    pointer-events: none;
                    opacity: 0.5;
                    cursor: not-allowed;
                }
                .nelx-dependency-notice {
                    background: #fff8e5;
                    border: 1px solid #ffb900;
                    border-radius: 3px;
                    padding: 8px 12px;
                    display: inline-block;
                    font-size: 13px;
                    line-height: 1.5;
                }
                .nelx-dependency-notice strong {
                    color: #d63638;
                }
            </style>
            <?php
        }
    }
    
    public function add_deactivation_notice_below_description($plugin_file, $plugin_data) {
        echo '<script type="text/javascript">';
        echo 'jQuery(document).ready(function($) {';
        echo '  var notice = \'<div class="nelx-dependency-notice"><strong>' . esc_js(__('Cannot deactivate', 'nelx-jetappt-frontend')) . '</strong>: ' . esc_js(__('This plugin is required by Nelx JetAppointments Frontend. Please deactivate Nelx JetAppointments Frontend first.', 'nelx-jetappt-frontend')) . '</div>\';';
        echo '  $("tr[data-plugin=\'jet-appointments-booking/jet-appointments-booking.php\'] .column-description").append(notice);';
        echo '});';
        echo '</script>';
    }
    
    public function show_table_error_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e('Nelx JetAppointments: One or more configured tables or columns do not exist. Please check plugin settings.', 'nelx-jetappt-frontend'); ?></p>
        </div>
        <?php
    }
    
    public static function activate() {
        global $wpdb;
        
        $jet_appointments_slug = 'jet-appointments-booking/jet-appointments-booking.php';
        $active_plugins = get_option('active_plugins', array());
        
        if (!in_array($jet_appointments_slug, $active_plugins)) {
            deactivate_plugins(plugin_basename(NELXJAF_PLUGIN_DIR . 'nelx-jetappt-frontend.php'));
            wp_die(
                sprintf(
                    /* translators: %s: URL to plugins page */
                    esc_html__('This plugin requires %1$sJet Appointments Booking%2$s to be installed and activated. Please install/activate it first. <br/><br/><a href="%3$s">← Return to Plugins page</a>', 'nelx-jetappt-frontend'),
                    '<strong>',
                    '</strong>',
                    esc_url(admin_url('plugins.php'))
                ),
                'Plugin Dependency Check',
                ['back_link' => true, 'response' => 200]
            );
        }
        
        $appt_table = $wpdb->prefix . 'jet_appointments';
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
        $sql = $wpdb->prepare("SHOW COLUMNS FROM %i LIKE %s", $appt_table, 'google_meet_url');
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results($sql);
        if (empty($results)) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query("ALTER TABLE {$appt_table} ADD google_meet_url VARCHAR(255) DEFAULT NULL");
        }
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
        $sql = $wpdb->prepare("SHOW COLUMNS FROM %i LIKE %s", $appt_table, 'appointment_status');
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results($sql);
        if (empty($results)) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query("ALTER TABLE {$appt_table} ADD appointment_status VARCHAR(20) DEFAULT 'pending'");
        }
    }
    
    public static function deactivate() {
        flush_rewrite_rules();
    }
    
    public function get_provider_post_id_for_user($user_id) {
        // Cache key for this query
        $cache_key = 'nelx_provider_post_id_' . $user_id;
        $cached = wp_cache_get($cache_key, 'nelx_provider');
        
        if ($cached !== false) {
            return intval($cached);
        }
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $sql = $this->wpdb->prepare(
            "SELECT object_ID FROM {$this->staff_meta_table} WHERE {$this->provider_column} = %d LIMIT 1",
            $user_id
        );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $this->wpdb->get_var($sql);
        
        $result = intval($result) ?: 0;
        wp_cache_set($cache_key, $result, 'nelx_provider', HOUR_IN_SECONDS);
        
        return $result;
    }
    
    public function load_provider_meta($post_id) {
        $raw = get_post_meta($post_id, 'jet_apb_post_meta', true);
        return is_array($raw) ? $raw : [];
    }
    
    public function save_provider_meta($post_id, array $data) {
        return update_post_meta($post_id, 'jet_apb_post_meta', $data);
    }
    
    public function hms_to_seconds($hms) {
        if (is_numeric($hms)) return intval($hms);
        $parts = explode(':', (string)$hms);
        if (count($parts) === 3) {
            return (int)$parts[0]*3600 + (int)$parts[1]*60 + (int)$parts[2];
        }
        if (count($parts) === 2) {
            return (int)$parts[0]*3600 + (int)$parts[1]*60;
        }
        return intval($hms);
    }
    
    public function seconds_to_hhmm($s) {
        $s = intval($s);
        $h = floor($s / 3600);
        $m = floor(($s % 3600) / 60);
        return str_pad($h, 2, '0', STR_PAD_LEFT) . ':' . str_pad($m, 2, '0', STR_PAD_LEFT);
    }
    
    public function timestamp_to_date($timestamp) {
        return $timestamp ? gmdate('d-m-Y', intval($timestamp) / 1000) : '';
    }
    
    public function get_global_working_hours() {
        $opt = get_option('jet-apb-settings', []);
        if (isset($opt['working_hours']) && is_array($opt['working_hours'])) {
            return $opt['working_hours'];
        }
        return [
            'monday' => [['from' => '08:00', 'to' => '17:00']],
            'tuesday' => [['from' => '08:00', 'to' => '17:00']],
            'wednesday' => [['from' => '08:00', 'to' => '17:00']],
            'thursday' => [['from' => '08:00', 'to' => '17:00']],
            'friday' => [['from' => '08:00', 'to' => '17:00']],
            'saturday' => [],
            'sunday' => [],
        ];
    }
    
    public function get_working_hours_for_date($post_id, $date, $custom_schedule) {
        $use_custom_schedule = !empty($custom_schedule['use_custom_schedule']);
        
        $working_days_mode = $custom_schedule['working_days_mode'] ?? 'default';
        $working_days = $custom_schedule['working_days'] ?? [];
        
        if ($use_custom_schedule) {
            $default_working_hours = $custom_schedule['working_hours'] ?? $this->get_global_working_hours();
        } else {
            $default_working_hours = $this->get_global_working_hours();
        }
        
        if ($working_days_mode === 'default' || empty($working_days) || !$use_custom_schedule) {
            return $default_working_hours;
        }
        
        $date_ts = strtotime($date . ' 00:00:00 UTC');
        $date_str = gmdate('Y-m-d', $date_ts);
        $weekday = strtolower(gmdate('l', $date_ts));
        
        foreach ($working_days as $working_day) {
            $start_date = !empty($working_day['startTimeStamp']) ? 
                gmdate('Y-m-d', intval($working_day['startTimeStamp']) / 1000) :
                (!empty($working_day['start']) ? gmdate('Y-m-d', strtotime($working_day['start'] . ' UTC')) : '');
                
            $end_date = !empty($working_day['endTimeStamp']) ? 
                gmdate('Y-m-d', intval($working_day['endTimeStamp']) / 1000) :
                (!empty($working_day['end']) ? gmdate('Y-m-d', strtotime($working_day['end'] . ' UTC')) : $start_date);
            
            if ($start_date && $end_date && $date_str >= $start_date && $date_str <= $end_date) {
                if (!empty($working_day['schedule']) && is_array($working_day['schedule'])) {
                    return [$weekday => $working_day['schedule']];
                } elseif ($working_days_mode === 'override_full') {
                    return [$weekday => []];
                }
            }
        }
        
        if ($working_days_mode === 'override_full') {
            return [$weekday => []];
        }
        
        return $default_working_hours;
    }
}
