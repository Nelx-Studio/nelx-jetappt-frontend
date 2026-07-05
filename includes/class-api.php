<?php
/**
 * REST API Class - All endpoints
 * With Rate Limiting and Caching
 */

if (!defined('ABSPATH')) exit;

/**
 * Rate Limiting Helper
 */
class NELXJAF_Rate_Limiter {
    private static $instance = null;
    private $cache_group = 'nelx_rate_limit';
    private $time_window = 60; // seconds
    
    // Different limits for different user roles
    private $limits = [
        'administrator' => 30,  // Admins: 30 requests per minute
        'default' => 10,        // Regular users: 10 requests per minute
    ];
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function check_limit($user_id, $endpoint) {
        $user = get_userdata($user_id);
        $role = $this->get_user_role($user);
        
        // Get max requests for this role
        $max_requests = $this->limits[$role] ?? $this->limits['default'];
        
        $key = $this->get_key($user_id, $endpoint);
        $data = wp_cache_get($key, $this->cache_group);
        
        if (!$data) {
            $data = [
                'count' => 0,
                'first_request' => time()
            ];
        }
        
        // Reset if window expired
        if (time() - $data['first_request'] > $this->time_window) {
            $data = [
                'count' => 0,
                'first_request' => time()
            ];
        }
        
        $data['count']++;
        
        // Store with expiration
        wp_cache_set($key, $data, $this->cache_group, $this->time_window);
        
        if ($data['count'] > $max_requests) {
            return false;
        }
        
        return true;
    }
    
    private function get_user_role($user) {
        if (!$user || !isset($user->roles)) {
            return 'default';
        }
        
        // Check for admin role
        if (in_array('administrator', (array)$user->roles)) {
            return 'administrator';
        }
        
        return 'default';
    }
    
    private function get_key($user_id, $endpoint) {
        return 'rate_' . $user_id . '_' . md5($endpoint);
    }
    
    public function get_remaining($user_id, $endpoint) {
        $user = get_userdata($user_id);
        $role = $this->get_user_role($user);
        $max_requests = $this->limits[$role] ?? $this->limits['default'];
        
        $key = $this->get_key($user_id, $endpoint);
        $data = wp_cache_get($key, $this->cache_group);
        
        if (!$data) {
            return $max_requests;
        }
        
        if (time() - $data['first_request'] > $this->time_window) {
            return $max_requests;
        }
        
        return max(0, $max_requests - $data['count']);
    }
    
    public function get_reset_time($user_id, $endpoint) {
        $key = $this->get_key($user_id, $endpoint);
        $data = wp_cache_get($key, $this->cache_group);
        
        if (!$data) {
            return 0;
        }
        
        $elapsed = time() - $data['first_request'];
        return max(0, $this->time_window - $elapsed);
    }
}

class NELXJAF_API {
    
    private static $instance = null;
    private $core;
    private $namespace = 'nelx-jaf/v1';
    
    public static function instance($core) {
        if (is_null(self::$instance)) {
            self::$instance = new self($core);
        }
        return self::$instance;
    }
    
    private function __construct($core) {
        $this->core = $core;
        add_action('rest_api_init', [$this, 'register_routes']);
        
        // Clear cache when appointments change
        add_action('nelx_appointment_rescheduled', [$this, 'clear_appointment_caches']);
        add_action('nelx_appointment_status_changed', [$this, 'clear_appointment_caches']);
        add_action('nelx_appointment_canceled', [$this, 'clear_appointment_caches']);
        
        // Clear cache when provider settings change
        add_action('save_post_provider', [$this, 'clear_provider_caches']);
    }
    
    public function register_routes() {
        register_rest_route($this->namespace, '/provider/(?P<id>\d+)/settings', [
            'methods' => 'GET',
            'callback' => [$this, 'get_provider_settings'],
            'permission_callback' => [$this, 'require_login'],
        ]);
        
        register_rest_route($this->namespace, '/provider/(?P<id>\d+)/days-off', [
            'methods' => 'GET',
            'callback' => [$this, 'get_provider_days_off'],
            'permission_callback' => [$this, 'require_login'],
        ]);
        
        register_rest_route($this->namespace, '/provider/(?P<id>\d+)/working-days', [
            'methods' => 'GET',
            'callback' => [$this, 'get_provider_working_days'],
            'permission_callback' => [$this, 'require_login'],
        ]);
        
        register_rest_route($this->namespace, '/schedule', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_schedule'],
                'permission_callback' => [$this, 'require_login'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'save_schedule'],
                'permission_callback' => [$this, 'require_login'],
            ],
        ]);
        
        register_rest_route($this->namespace, '/get-timezone-dropdown', [
            'methods' => 'POST',
            'callback' => [$this, 'get_timezone_dropdown'],
            'permission_callback' => '__return_true',
        ]);
        
        register_rest_route($this->namespace, '/appointments/(?P<id>\d+)/info', [
            'methods' => 'GET',
            'callback' => [$this, 'get_appointment_info'],
            'permission_callback' => [$this, 'require_login'],
        ]);
        
        register_rest_route($this->namespace, '/appointments/(?P<id>\d+)/with-timezone', [
            'methods' => 'POST',
            'callback' => [$this, 'get_appointment_with_timezone'],
            'permission_callback' => [$this, 'require_login'],
        ]);
        
        register_rest_route($this->namespace, '/appointments/(?P<id>\d+)/status', [
            'methods' => 'POST',
            'callback' => [$this, 'update_appointment_status'],
            'permission_callback' => [$this, 'require_login'],
        ]);
        
        register_rest_route($this->namespace, '/appointments/(?P<id>\d+)/cancel', [
            'methods' => 'POST',
            'callback' => [$this, 'cancel_appointment'],
            'permission_callback' => [$this, 'require_login'],
        ]);
        
        register_rest_route($this->namespace, '/appointments/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_appointment'],
            'permission_callback' => [$this, 'require_login'],
        ]);
        
        register_rest_route($this->namespace, '/appointments/reschedule', [
            'methods' => 'POST',
            'callback' => [$this, 'reschedule_appointment'],
            'permission_callback' => [$this, 'require_login'],
        ]);
        
        register_rest_route($this->namespace, '/appointments/reschedule-with-timezone', [
            'methods' => 'POST',
            'callback' => [$this, 'reschedule_appointment_with_timezone'],
            'permission_callback' => [$this, 'require_login'],
        ]);
        
        register_rest_route($this->namespace, '/appointments/batch', [
            'methods' => 'POST',
            'callback' => [$this, 'get_batch_appointments'],
            'permission_callback' => [$this, 'require_login'],
        ]);
        
        register_rest_route($this->namespace, '/available-slots', [
            'methods' => 'GET',
            'callback' => [$this, 'get_available_slots'],
            'permission_callback' => [$this, 'require_login'],
        ]);
        
        register_rest_route($this->namespace, '/available-slots-in-timezone', [
            'methods' => 'POST',
            'callback' => [$this, 'get_available_slots_in_timezone'],
            'permission_callback' => [$this, 'require_login'],
        ]);
        
        register_rest_route($this->namespace, '/get-provider-name', [
            'methods' => 'POST',
            'callback' => [$this, 'get_provider_name'],
            'permission_callback' => [$this, 'require_login'],
        ]);
        
        register_rest_route($this->namespace, '/get-service-title', [
            'methods' => 'POST',
            'callback' => [$this, 'get_service_title'],
            'permission_callback' => [$this, 'require_login'],
        ]);
    }
    
    /**
     * Rate limit check for endpoints
     */
    private function check_rate_limit($endpoint) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return true;
        }
        
        $limiter = NELXJAF_Rate_Limiter::instance();
        return $limiter->check_limit($user_id, $endpoint);
    }
    
    /**
     * Permission callback with rate limiting
     */
    public function require_login() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        // Get the current endpoint from the request
        $endpoint = $_SERVER['REQUEST_URI'] ?? '';
        
        // Apply rate limiting
        if (!$this->check_rate_limit($endpoint)) {
            return new WP_Error(
                'rate_limit_exceeded',
                'Too many requests. Please try again later.',
                ['status' => 429]
            );
        }
        
        return true;
    }
    
    /**
     * Clear appointment related caches
     */
    public function clear_appointment_caches($appointment_id) {
        // Clear batch appointments cache
        wp_cache_delete('nelx_batch_appointments', 'nelx_batch_appointments');
        
        // Clear individual appointment caches
        wp_cache_delete('nelx_appointment_info_' . $appointment_id, 'nelx_appointments');
        wp_cache_delete('nelx_appointment_timezone_' . $appointment_id, 'nelx_appointments');
    }
    
    /**
     * Clear provider related caches
     */
    public function clear_provider_caches($post_id) {
        // Clear provider settings cache
        wp_cache_delete('nelx_provider_settings_' . $post_id, 'nelx_provider_settings');
        wp_cache_delete('nelx_provider_working_days_' . $post_id, 'nelx_provider_working_days');
        
        // Clear all slot caches for this provider
        wp_cache_delete('nelx_slots', 'nelx_slots');
        
        // Clear all provider schedule caches
        wp_cache_delete('nelx_schedule_' . $post_id, 'nelx_schedule');
    }
    
    public function get_timezone_dropdown($request) {
        $params = $request->get_json_params();
        $selected = $params['selected'] ?? '';
        
        $timezone_helper = NELXJAF_Timezone_Helper::instance();
        
        return new WP_REST_Response([
            'html' => $timezone_helper->get_timezone_dropdown($selected),
            'zones' => $timezone_helper->get_timezone_choices_array($selected),
            'provider_timezone' => $timezone_helper->get_provider_timezone()
        ], 200);
    }
    
    public function get_provider_settings($request) {
        $post_id = intval($request['id']);
        if (!$post_id) {
            return new WP_REST_Response(['error' => 'bad_request'], 400);
        }
        
        // Check cache - 24 hours (settings rarely change)
        $cache_key = 'nelx_provider_settings_' . $post_id;
        $cached = wp_cache_get($cache_key, 'nelx_provider_settings');
        
        if ($cached !== false) {
            return new WP_REST_Response($cached, 200);
        }
        
        $meta = $this->core->load_provider_meta($post_id);
        $cs = $meta['custom_schedule'] ?? [];
        $use_custom_schedule = !empty($cs['use_custom_schedule']) || !empty($meta['use_custom_schedule']);
        
        $locked_time = 0;
        if ($use_custom_schedule && isset($cs['locked_time'])) {
            $locked_time = intval($cs['locked_time']);
        } else {
            $global_settings = get_option('jet-apb-settings');
            if ($global_settings && isset($global_settings['locked_time'])) {
                $locked_time = intval($global_settings['locked_time']);
            }
        }
        
        $default_slot = 1800;
        $buffer_before = 0;
        $buffer_after = 0;
        
        if ($use_custom_schedule) {
            $default_slot = $cs['default_slot'] ?? 1800;
            $buffer_before = $cs['buffer_before'] ?? 0;
            $buffer_after = $cs['buffer_after'] ?? 0;
        } else {
            $global_settings = get_option('jet-apb-settings');
            if ($global_settings && is_array($global_settings)) {
                $default_slot = isset($global_settings['default_slot']) ? intval($global_settings['default_slot']) : 1800;
                $buffer_before = isset($global_settings['buffer_before']) ? intval($global_settings['buffer_before']) : 0;
                $buffer_after = isset($global_settings['buffer_after']) ? intval($global_settings['buffer_after']) : 0;
            }
        }
        
        $response_data = [
            'use_custom_schedule' => $use_custom_schedule,
            'locked_time' => $locked_time,
            'default_slot' => $default_slot,
            'buffer_before' => $buffer_before,
            'buffer_after' => $buffer_after,
            'custom_schedule' => $cs
        ];
        
        // Cache for 24 hours
        wp_cache_set($cache_key, $response_data, 'nelx_provider_settings', DAY_IN_SECONDS);
        
        return new WP_REST_Response($response_data, 200);
    }
    
    public function get_schedule($request) {
        $user_id = get_current_user_id();
        $post_id = $this->core->get_provider_post_id_for_user($user_id);
        
        if (!$post_id) {
            return new WP_REST_Response(['error' => 'provider_not_found'], 404);
        }
        
        // Check cache - 1 hour
        $cache_key = 'nelx_schedule_' . $post_id;
        $cached = wp_cache_get($cache_key, 'nelx_schedule');
        
        if ($cached !== false) {
            return new WP_REST_Response($cached, 200);
        }
        
        $meta = $this->core->load_provider_meta($post_id);
        
        $response_data = [
            'use_custom_schedule' => !empty($meta['custom_schedule']['use_custom_schedule']) || !empty($meta['use_custom_schedule']),
            'custom_schedule' => $meta['custom_schedule'] ?? [],
        ];
        
        // Cache for 1 hour
        wp_cache_set($cache_key, $response_data, 'nelx_schedule', HOUR_IN_SECONDS);
        
        return new WP_REST_Response($response_data, 200);
    }
    
    public function save_schedule($request) {
        $user_id = get_current_user_id();
        $post_id = $this->core->get_provider_post_id_for_user($user_id);
        
        if (!$post_id) {
            return new WP_REST_Response(['error' => 'provider_not_found'], 404);
        }
        
        $body = $request->get_json_params();
        if (!is_array($body)) {
            return new WP_REST_Response(['error' => 'invalid_request'], 400);
        }
        
        $use = !empty($body['use_custom_schedule']);
        $cs = (array)($body['custom_schedule'] ?? []);
        
        $default_slot = $this->core->hms_to_seconds($cs['default_slot'] ?? '00:30');
        $buffer_before = $this->core->hms_to_seconds($cs['buffer_before'] ?? '00:00');
        $buffer_after = $this->core->hms_to_seconds($cs['buffer_after'] ?? '00:00');
        $locked_time = $this->core->hms_to_seconds($cs['locked_time'] ?? '00:00');
        
        $appt_range = $cs['appointments_range'] ?? [];
        $appt_range_type = in_array($appt_range['type'] ?? 'all', ['all', 'range'], true) ? $appt_range['type'] : 'all';
        $appt_range_num = strval(max(1, intval($appt_range['range_num'] ?? 60)));
        $appt_range_unit = in_array($appt_range['range_unit'] ?? 'days', ['days', 'months', 'years'], true) ? $appt_range['range_unit'] : 'days';
        
        $custom = [
            'slot_time_format' => 'H:i',
            'buffer_before' => $buffer_before,
            'buffer_after' => $buffer_after,
            'default_slot' => $default_slot,
            'locked_time' => $locked_time,
            'booking_type' => 'slot',
            'max_duration' => intval($default_slot),
            'step_duration' => intval($this->core->hms_to_seconds($cs['step_duration'] ?? '00:05')),
            'several_days' => false,
            'only_start' => false,
            'multi_booking' => false,
            'min_slot_count' => 1,
            'max_slot_count' => 1,
            'min_recurring_count' => '1',
            'max_recurring_count' => '5',
            'working_days_mode' => in_array($cs['working_days_mode'] ?? 'default', ['default', 'override_full', 'override_partial', 'override_days'], true) ? $cs['working_days_mode'] : 'default',
            'days_off_allow_rewrite' => 'allow',
            'appointments_range' => [
                'type' => $appt_range_type,
                'range_num' => $appt_range_num,
                'range_unit' => $appt_range_unit,
            ],
            'working_hours' => (array)($cs['working_hours'] ?? []),
            'days_off' => [],
            'working_days' => [],
            'use_custom_schedule' => (bool)$use,
            'daysOffAllowRewrite' => 'allow',
        ];
        
        $days_off = [];
        foreach ($cs['days_off'] ?? [] as $do) {
            $start_timestamp = !empty($do['start']) ? strtotime($do['start']) * 1000 : 0;
            $end_timestamp = !empty($do['end']) ? strtotime($do['end']) * 1000 : 0;
            
            $days_off[] = [
                'name' => $do['name'] ?? '',
                'start' => $do['start'] ?? '',
                'startTimeStamp' => $start_timestamp ? (string)$start_timestamp : '0',
                'end' => $do['end'] ?? '',
                'endTimeStamp' => $end_timestamp ? (string)$end_timestamp : '0',
                'type' => 'days_off',
                'editIndex' => ''
            ];
        }
        $custom['days_off'] = $days_off;
        
        $working_days = [];
        foreach ($cs['working_days'] ?? [] as $wd) {
            $start_timestamp = !empty($wd['start']) ? strtotime($wd['start']) * 1000 : 0;
            $end_timestamp = !empty($wd['end']) ? strtotime($wd['end']) * 1000 : 0;
            $schedule = [];
            if (!empty($wd['schedule']) && is_array($wd['schedule'])) {
                foreach ($wd['schedule'] as $slot) {
                    if (!empty($slot['from']) && !empty($slot['to'])) {
                        $schedule[] = [
                            'from' => sanitize_text_field($slot['from']),
                            'to' => sanitize_text_field($slot['to'])
                        ];
                    }
                }
            }
            $working_days[] = [
                'name' => sanitize_text_field($wd['name'] ?? ''),
                'start' => sanitize_text_field($wd['start'] ?? ''),
                'startTimeStamp' => $start_timestamp ? (string)$start_timestamp : '0',
                'end' => sanitize_text_field($wd['end'] ?? ''),
                'endTimeStamp' => $end_timestamp ? (string)$end_timestamp : '0',
                'type' => 'working_days',
                'editIndex' => '',
                'schedule' => $schedule
            ];
        }
        $custom['working_days'] = $working_days;
        
        $meta = $this->core->load_provider_meta($post_id);
        $meta['ID'] = (string)$post_id;
        $meta['custom_schedule'] = $custom;
        $meta['meta_settings'] = $meta['meta_settings'] ?? [];
        
        $this->core->save_provider_meta($post_id, $meta);
        
        // Clear provider caches after saving
        $this->clear_provider_caches($post_id);
        
        return new WP_REST_Response(['ok' => true], 200);
    }
    
    public function get_appointment_with_timezone($request) {
        $id = intval($request['id']);
        $params = $request->get_json_params();
        $is_provider_view = $params['is_provider'] ?? false;
        
        if (!$id) {
            return new WP_REST_Response(['error' => 'bad_request'], 400);
        }
        
        // Check cache - 1 hour (appointments don't change often)
        $cache_key = 'nelx_appointment_timezone_' . $id;
        $cached = wp_cache_get($cache_key, 'nelx_appointments');
        
        if ($cached !== false) {
            return new WP_REST_Response($cached, 200);
        }
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
        $appt = $this->core->wpdb->get_row(
            $this->core->wpdb->prepare(
                "SELECT * FROM {$this->core->appt_table} WHERE ID = %d LIMIT 1", 
                $id
            ),
            ARRAY_A
        );
        
        if (!$appt) {
            return new WP_REST_Response(['error' => 'not_found'], 404);
        }
        
        $uid = get_current_user_id();
        $provpid = $this->core->get_provider_post_id_for_user($uid);
        $is_provider_user = ((string)$appt['provider'] === (string)$provpid);
        $is_client = ((string)($appt['user_id'] ?? '') === (string)$uid);
        $is_admin = current_user_can('manage_options');
        
        if (!$is_provider_user && !$is_client && !$is_admin) {
            return new WP_REST_Response(['error' => 'forbidden'], 403);
        }
        
        $timezone_helper = NELXJAF_Timezone_Helper::instance();
        
        $client_timezone = $timezone_helper->get_client_timezone($id);
        $client_local_time = $timezone_helper->get_client_local_time($id);
        $client_local_date = $timezone_helper->get_client_local_date($id);
        
        $provider_timezone = $timezone_helper->get_provider_timezone();
        
        $global_settings = get_option('jet-apb-settings');
        $use_calendar_timezone = isset($global_settings['use_calendar_timezone']) && $global_settings['use_calendar_timezone'] === '1';
        
        $display_start = '';
        $display_end = '';
        $display_timezone = '';
        
        $provider_local_start = $timezone_helper->timestamp_to_provider_local($appt['slot'], 'F j, Y H:i');
        $provider_local_end = $timezone_helper->timestamp_to_provider_local($appt['slot_end'], 'H:i');
        
        if ($is_provider_view) {
            $display_start = $timezone_helper->timestamp_to_provider_local($appt['slot'], 'F j, Y H:i');
            $display_end = $timezone_helper->timestamp_to_provider_local($appt['slot_end'], 'F j, Y H:i');
            $display_timezone = $provider_timezone;
        } else {
            if ($use_calendar_timezone && !empty($client_timezone) && !empty($client_local_date) && !empty($client_local_time)) {
                $display_start = $client_local_date . ' ' . explode('-', $client_local_time)[0];
                $display_end = explode('-', $client_local_time)[1];
                $display_timezone = $client_timezone;
            } else if (!empty($client_timezone)) {
                $display_start = $timezone_helper->provider_local_to_client($provider_local_start, $provider_timezone, $client_timezone, 'F j, Y H:i');
                $display_end = $timezone_helper->provider_local_to_client($provider_local_end, $provider_timezone, $client_timezone, 'H:i');
                $display_timezone = $client_timezone;
            } else {
                $display_start = $timezone_helper->timestamp_to_provider_local($appt['slot'], 'F j, Y H:i');
                $display_end = $timezone_helper->timestamp_to_provider_local($appt['slot_end'], 'F j, Y H:i');
                $display_timezone = $provider_timezone;
            }
        }
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
        $meta = $this->core->wpdb->get_results(
            $this->core->wpdb->prepare("SELECT meta_key, meta_value FROM {$this->core->appt_meta_table} WHERE appointment_id = %d", $id),
            ARRAY_A
        );
        
        $out_meta = [];
        foreach ($meta as $m) {
            $out_meta[$m['meta_key']] = $m['meta_value'];
        }
        
        $google_meet_url = $appt['google_meet_url'] ?? '';
        if (empty($google_meet_url) && isset($out_meta['google_meet_url'])) {
            $google_meet_url = $out_meta['google_meet_url'];
        }
        
        $service_title = get_the_title($appt['service'] ?? 0);
        if (!$service_title) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
            $service_title = $this->core->wpdb->get_var(
                $this->core->wpdb->prepare("SELECT post_title FROM {$this->core->wpdb->posts} WHERE ID = %d", $appt['service'] ?? 0)
            );
            if (!$service_title) {
                $service_title = 'Unknown';
            }
        }
        
        $response_data = [
            'appt' => $appt,
            'meta' => array_merge($out_meta, ['google_meet_link' => $google_meet_url]),
            'service_title' => $service_title,
            'appointment_status' => $appt['appointment_status'] ?? 'pending',
            'is_canceled' => ($appt['appointment_status'] ?? 'pending') === 'canceled',
            'display' => [
                'start' => $display_start,
                'end' => $display_end,
                'timezone' => $display_timezone
            ],
            'client_info' => [
                'timezone' => $client_timezone,
                'local_time' => $client_local_time,
                'local_date' => $client_local_date
            ],
            'is_provider' => $is_provider_user,
            'use_calendar_timezone' => $use_calendar_timezone
        ];
        
        // Cache for 1 hour
        wp_cache_set($cache_key, $response_data, 'nelx_appointments', HOUR_IN_SECONDS);
        
        return new WP_REST_Response($response_data, 200);
    }
    
    public function get_appointment_info($request) {
        $id = intval($request['id']);
        if (!$id) {
            return new WP_REST_Response(['error' => 'bad_request'], 400);
        }
        
        // Check cache - 1 hour
        $cache_key = 'nelx_appointment_info_' . $id;
        $cached = wp_cache_get($cache_key, 'nelx_appointments');
        
        if ($cached !== false) {
            return new WP_REST_Response($cached, 200);
        }
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
        $appt = $this->core->wpdb->get_row(
            $this->core->wpdb->prepare(
                "SELECT * FROM {$this->core->appt_table} WHERE ID = %d LIMIT 1", 
                $id
            ),
            ARRAY_A
        );
        
        if (!$appt) {
            return new WP_REST_Response(['error' => 'not_found'], 404);
        }
        
        $uid = get_current_user_id();
        $provpid = $this->core->get_provider_post_id_for_user($uid);
        $is_provider = ((string)$appt['provider'] === (string)$provpid);
        $is_client = ((string)($appt['user_id'] ?? '') === (string)$uid);
        $is_admin = current_user_can('manage_options');
        
        if (!$is_provider && !$is_client && !$is_admin) {
            return new WP_REST_Response(['error' => 'forbidden'], 403);
        }
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
        $meta = $this->core->wpdb->get_results(
            $this->core->wpdb->prepare("SELECT meta_key, meta_value FROM {$this->core->appt_meta_table} WHERE appointment_id = %d", $id),
            ARRAY_A
        );
        
        $out_meta = [];
        foreach ($meta as $m) {
            $out_meta[$m['meta_key']] = $m['meta_value'];
        }
        
        $google_meet_url = $appt['google_meet_url'] ?? '';
        if (empty($google_meet_url) && isset($out_meta['google_meet_url'])) {
            $google_meet_url = $out_meta['google_meet_url'];
        }
        
        $service_title = get_the_title($appt['service'] ?? 0);
        if (!$service_title) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
            $service_title = $this->core->wpdb->get_var(
                $this->core->wpdb->prepare("SELECT post_title FROM {$this->core->wpdb->posts} WHERE ID = %d", $appt['service'] ?? 0)
            );
            if (!$service_title) {
                $service_title = 'Unknown';
            }
        }
        
        $timezone_helper = NELXJAF_Timezone_Helper::instance();
        $provider_timezone = $timezone_helper->get_provider_timezone();
        
        // Get client timezone data from meta
        $client_timezone = $timezone_helper->get_client_timezone($id);
        $client_local_time = $timezone_helper->get_client_local_time($id);
        $client_local_date = $timezone_helper->get_client_local_date($id);
        
        // Format display times based on who's viewing
        $display_start = '';
        $display_end = '';
        $display_timezone = '';
        
        if ($is_provider || $is_admin) {
            // Provider/Admin view - show provider local time
            // FIX: Use gmdate directly since timestamps are provider-local, not UTC
            $display_start = gmdate('F j, Y H:i', $appt['slot']);
            $display_end = gmdate('F j, Y H:i', $appt['slot_end']);
            $display_timezone = $provider_timezone;
        } else {
            // Client view - show client local time if available
            if (!empty($client_timezone) && !empty($client_local_date) && !empty($client_local_time)) {
                $client_times = explode('-', $client_local_time);
                $client_start_time = $client_times[0] ?? '';
                $client_end_time = $client_times[1] ?? '';
                
                $display_start = $client_local_date . ' ' . $client_start_time;
                $display_end = $client_local_date . ' ' . $client_end_time;
                $display_timezone = $client_timezone;
            } else if (!empty($client_timezone)) {
                // Convert provider-local to client timezone
                // First get provider date and time using gmdate (since timestamps are provider-local)
                $provider_date = gmdate('F j, Y', $appt['slot']);
                $provider_start = gmdate('H:i', $appt['slot']);
                $provider_end = gmdate('H:i', $appt['slot_end']);
                
                // Then convert to client timezone
                $display_start = $timezone_helper->provider_local_to_client($provider_date, $provider_start, $client_timezone, 'F j, Y H:i');
                $display_end = $timezone_helper->provider_local_to_client($provider_date, $provider_end, $client_timezone, 'F j, Y H:i');
                $display_timezone = $client_timezone;
            } else {
                // Fallback to provider timezone using gmdate
                $display_start = gmdate('F j, Y H:i', $appt['slot']);
                $display_end = gmdate('F j, Y H:i', $appt['slot_end']);
                $display_timezone = $provider_timezone;
            }
        }
        
        $response_data = [
            'appt' => $appt,
            'meta' => array_merge($out_meta, ['google_meet_link' => $google_meet_url]),
            'service_title' => $service_title,
            'appointment_status' => $appt['appointment_status'] ?? 'pending',
            'is_canceled' => ($appt['appointment_status'] ?? 'pending') === 'canceled',
            'display' => [
                'start' => $display_start,
                'end' => $display_end,
                'timezone' => $display_timezone
            ],
            'client_info' => [
                'timezone' => $client_timezone,
                'local_time' => $client_local_time,
                'local_date' => $client_local_date
            ],
            'is_provider' => $is_provider
        ];
        
        // Cache for 1 hour
        wp_cache_set($cache_key, $response_data, 'nelx_appointments', HOUR_IN_SECONDS);
        
        return new WP_REST_Response($response_data, 200);
    }
    
    public function reschedule_appointment($request) {
        $params = $request->get_json_params();
        $id = intval($params['id'] ?? 0);
        $slot = intval($params['slot'] ?? 0);
        $slot_end = intval($params['slot_end'] ?? 0);
        
        if (!$id || !$slot || !$slot_end) {
            return new WP_REST_Response(['error' => 'bad_request'], 400);
        }
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
        $appt = $this->core->wpdb->get_row(
            $this->core->wpdb->prepare(
                "SELECT * FROM {$this->core->appt_table} WHERE ID = %d LIMIT 1", 
                $id
            )
        );
        
        if (!$appt) {
            return new WP_REST_Response(['error' => 'not_found'], 404);
        }
        
        $uid = get_current_user_id();
        $provpid = $this->core->get_provider_post_id_for_user($uid);
        $is_provider = ((string)$appt->provider === (string)$provpid);
        $is_client = ((string)($appt->user_id ?? '') === (string)$uid);
        
        if (!$is_provider && !$is_client) {
            return new WP_REST_Response(['error' => 'forbidden'], 403);
        }
        
        $provider_val = (string)$appt->provider;
        $staff_val = (string)($appt->staff_id ?? '');
        $check_start = $slot;
        $check_end = $slot_end;
        
        $provCol = preg_replace('/[^a-zA-Z0-9_]/', '', $this->core->provider_column ?: 'staff_id');
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
        $sql = "SELECT COUNT(*) FROM {$this->core->appt_table} WHERE ID != %d AND appointment_status != 'canceled' AND ( (provider = %s) OR ({$provCol} = %s) ) AND NOT ( slot_end <= %d OR slot >= %d )";
        $count = intval($this->core->wpdb->get_var($this->core->wpdb->prepare($sql, $id, $provider_val, $staff_val, $check_start, $check_end)));
        
        if ($count > 0) {
            return new WP_REST_Response(['error' => 'collision'], 409);
        }
        
        $day_start = strtotime('midnight', $slot);
        $updated = $this->core->wpdb->update(
            $this->core->appt_table,
            ['date' => $day_start, 'date_end' => 0, 'slot' => $slot, 'slot_end' => $slot_end],
            ['ID' => $id],
            ['%d', '%d', '%d', '%d'],
            ['%d']
        );
        
        if ($updated) {
            do_action('nelx_appointment_rescheduled', $id);
        }
        
        return new WP_REST_Response(['ok' => (bool)$updated], 200);
    }
    
    public function reschedule_appointment_with_timezone($request) {
        $params = $request->get_json_params();
        $id = intval($params['id'] ?? 0);
        $client_timezone = sanitize_text_field($params['client_timezone'] ?? '');
        $client_local_date = sanitize_text_field($params['client_local_date'] ?? '');
        $client_local_time = sanitize_text_field($params['client_local_time'] ?? '');
        $provider_date = sanitize_text_field($params['provider_date'] ?? '');
        $provider_start_time = sanitize_text_field($params['provider_start_time'] ?? '');
        $provider_end_time = sanitize_text_field($params['provider_end_time'] ?? '');
        
        if (!$id) {
            return new WP_REST_Response(['error' => 'bad_request'], 400);
        }
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
        $appt = $this->core->wpdb->get_row(
            $this->core->wpdb->prepare(
                "SELECT * FROM {$this->core->appt_table} WHERE ID = %d LIMIT 1", 
                $id
            )
        );
        
        if (!$appt) {
            return new WP_REST_Response(['error' => 'not_found'], 404);
        }
        
        $uid = get_current_user_id();
        $provpid = $this->core->get_provider_post_id_for_user($uid);
        $is_provider = ((string)$appt->provider === (string)$provpid);
        $is_client = ((string)($appt->user_id ?? '') === (string)$uid);
        
        if (!$is_provider && !$is_client) {
            return new WP_REST_Response(['error' => 'forbidden'], 403);
        }
        
        $timezone_helper = NELXJAF_Timezone_Helper::instance();
        $final_slot = 0;
        $final_slot_end = 0;
        $provider_date_for_client = '';
        $provider_local_for_client = '';
        $provider_local_end_for_client = '';
        
        if ($is_provider && $provider_date && $provider_start_time && $provider_end_time) {
            // Provider rescheduling: parse provider-local date/time
            $start_parts = explode(':', $provider_start_time);
            $start_hour = intval($start_parts[0]);
            $start_min = intval($start_parts[1]);
            
            $end_parts = explode(':', $provider_end_time);
            $end_hour = intval($end_parts[0]);
            $end_min = intval($end_parts[1]);
            
            $date_parts = explode('-', $provider_date);
            $year = intval($date_parts[0]);
            $month = intval($date_parts[1]);
            $day = intval($date_parts[2]);
            
            $final_slot = gmmktime($start_hour, $start_min, 0, $month, $day, $year);
            $final_slot_end = gmmktime($end_hour, $end_min, 0, $month, $day, $year);
            
            $provider_local_for_client = $provider_start_time;
            $provider_local_end_for_client = $provider_end_time;
            $provider_date_for_client = $provider_date;
        } else if ($is_client && !empty($client_timezone) && !empty($client_local_date) && !empty($client_local_time)) {
            // Client rescheduling: parse client-local date/time WITHOUT UTC conversion
            // Client provided date/time is in their timezone
            $client_times = explode('-', $client_local_time);
            $client_start_time = $client_times[0] ?? '';
            $client_end_time = $client_times[1] ?? '';
            
            // Parse client date into Y-m-d format for conversion
            $client_date_obj = DateTime::createFromFormat('F j, Y', $client_local_date);
            if ($client_date_obj) {
                $client_date_parsed = $client_date_obj->format('Y-m-d');
            } else {
                // Fallback if format doesn't match
                $client_date_parsed = $client_local_date;
            }
            
            // Convert client-local to provider-local timestamp
            $final_slot = $timezone_helper->client_time_to_provider_local($client_date_parsed, $client_start_time, $client_timezone);
            $final_slot_end = $timezone_helper->client_time_to_provider_local($client_date_parsed, $client_end_time, $client_timezone);
        }
        
        if (!$final_slot || !$final_slot_end) {
            return new WP_REST_Response(['error' => 'invalid_time'], 400);
        }
        
        $provider_val = (string)$appt->provider;
        $staff_val = (string)($appt->staff_id ?? '');
        $provCol = preg_replace('/[^a-zA-Z0-9_]/', '', $this->core->provider_column ?: 'staff_id');
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
        $sql = "SELECT COUNT(*) FROM {$this->core->appt_table} WHERE ID != %d AND appointment_status != 'canceled' AND ( (provider = %s) OR ({$provCol} = %s) ) AND NOT ( slot_end <= %d OR slot >= %d )";
        $count = intval($this->core->wpdb->get_var($this->core->wpdb->prepare($sql, $id, $provider_val, $staff_val, $final_slot, $final_slot_end)));
        
        if ($count > 0) {
            return new WP_REST_Response(['error' => 'collision'], 409);
        }
        
        $day_start = strtotime('midnight', $final_slot);
        $updated = $this->core->wpdb->update(
            $this->core->appt_table,
            ['date' => $day_start, 'date_end' => 0, 'slot' => $final_slot, 'slot_end' => $final_slot_end],
            ['ID' => $id],
            ['%d', '%d', '%d', '%d'],
            ['%d']
        );
        
        if ($updated) {
            if ($is_client && !empty($client_timezone)) {
                // Save client timezone metadata exactly as client provided (no conversion)
                $timezone_helper->update_client_timezone_data($id, $client_timezone, $client_local_date, $client_local_time);
            } elseif ($is_provider) {
                // Provider rescheduled: update client metadata if it exists
                $existing_client_timezone = $timezone_helper->get_client_timezone($id);
                if (!empty($existing_client_timezone)) {
                    $client_start_display = $timezone_helper->provider_local_to_client($provider_date_for_client, $provider_local_for_client, $existing_client_timezone, 'H:i');
                    $client_end_display = $timezone_helper->provider_local_to_client($provider_date_for_client, $provider_local_end_for_client, $existing_client_timezone, 'H:i');
                    
                    $client_datetime = new DateTime($provider_date_for_client, $timezone_helper->wp_timezone);
                    $client_datetime->setTimezone(new DateTimeZone($existing_client_timezone));
                    $client_date_display = $client_datetime->format('F j, Y');
                    
                    $timezone_helper->update_client_timezone_data(
                        $id, 
                        $existing_client_timezone, 
                        $client_date_display, 
                        $client_start_display . '-' . $client_end_display
                    );
                }
            }
            
            do_action('nelx_appointment_rescheduled', $id);
        }
        
        return new WP_REST_Response(['ok' => (bool)$updated], 200);
    }
    
    public function delete_appointment($request) {
        $id = intval($request['id']);
        if (!$id) {
            return new WP_REST_Response(['error' => 'bad_request'], 400);
        }
        
        $deleted = $this->core->wpdb->delete($this->core->appt_table, ['ID' => $id], ['%d']);
        
        return new WP_REST_Response(['ok' => (bool)$deleted], 200);
    }
    
    public function update_appointment_status($request) {
        $id = intval($request['id']);
        $params = $request->get_json_params();
        $status = sanitize_text_field($params['status'] ?? '');
        
        if (!$id || !in_array($status, ['accepted', 'rejected'])) {
            return new WP_REST_Response(['error' => 'bad_request'], 400);
        }
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
        $appt = $this->core->wpdb->get_row(
            $this->core->wpdb->prepare(
                "SELECT * FROM {$this->core->appt_table} WHERE ID = %d LIMIT 1", 
                $id
            )
        );
        
        if (!$appt) {
            return new WP_REST_Response(['error' => 'not_found'], 404);
        }
        
        $old_status = $appt->appointment_status ?? 'pending';
        
        $updated = $this->core->wpdb->update(
            $this->core->appt_table,
            ['appointment_status' => $status],
            ['ID' => $id],
            ['%s'],
            ['%d']
        );
        
        if ($updated === false) {
            return new WP_REST_Response(['error' => 'db_error'], 500);
        }
        
        if ($old_status !== $status) {
            do_action('nelx_appointment_status_changed', $id, $old_status, $status);
        }
        
        return new WP_REST_Response(['ok' => true], 200);
    }
    
    public function cancel_appointment($request) {
        $id = intval($request['id']);
        if (!$id) {
            return new WP_REST_Response(['error' => 'bad_request'], 400);
        }
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
        $appt = $this->core->wpdb->get_row(
            $this->core->wpdb->prepare(
                "SELECT * FROM {$this->core->appt_table} WHERE ID = %d LIMIT 1", 
                $id
            )
        );
        
        if (!$appt) {
            return new WP_REST_Response(['error' => 'not_found'], 404);
        }
        
        $uid = get_current_user_id();
        $provpid = $this->core->get_provider_post_id_for_user($uid);
        $is_provider = ((string)$appt->provider === (string)$provpid);
        $is_client = ((string)($appt->user_id ?? '') === (string)$uid);
        
        if (!$is_provider && !$is_client) {
            return new WP_REST_Response(['error' => 'forbidden'], 403);
        }
        
        $old_status = $appt->appointment_status ?? 'pending';
        
        $updated = $this->core->wpdb->update(
            $this->core->appt_table,
            ['appointment_status' => 'canceled'],
            ['ID' => $id],
            ['%s'],
            ['%d']
        );
        
        if ($updated === false) {
            return new WP_REST_Response(['error' => 'db_error'], 500);
        }
        
        $initiator = $is_provider ? 'provider' : 'client';
        
        if ($old_status !== 'canceled') {
            do_action('nelx_appointment_canceled', $id, $initiator);
        }
        
        return new WP_REST_Response(['ok' => true], 200);
    }
    
    public function get_batch_appointments($request) {
        $ids = $request->get_param('ids');
        if (!is_array($ids) || empty($ids)) {
            return new WP_REST_Response([], 200);
        }
        
        // Sort IDs for consistent cache key
        sort($ids);
        $ids_hash = md5(implode(',', $ids));
        $cache_key = 'nelx_batch_appointments_' . $ids_hash;
        $cached = wp_cache_get($cache_key, 'nelx_batch_appointments');
        
        if ($cached !== false) {
            return new WP_REST_Response($cached, 200);
        }
        
        $ids = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
        $appointments = $this->core->wpdb->get_results(
            $this->core->wpdb->prepare(
                "SELECT ID, appointment_status, slot, slot_end, provider, user_id FROM {$this->core->appt_table} WHERE ID IN ($placeholders)",
                $ids
            ),
            ARRAY_A
        );
        
        $response = [];
        $current_time = current_time('timestamp');
        
        foreach ($appointments as $appt) {
            $appointment_start = intval($appt['slot']);
            
            if ($appointment_start <= $current_time) {
                $rescheduling_allowed = false;
            } else {
                $provider_id = $appt['provider'];
                $meta = $this->core->load_provider_meta($provider_id);
                $cs = $meta['custom_schedule'] ?? [];
                $use_custom_schedule = !empty($cs['use_custom_schedule']) || !empty($meta['use_custom_schedule']);
                
                $locked_time = 0;
                if ($use_custom_schedule && isset($cs['locked_time'])) {
                    $locked_time = intval($cs['locked_time']);
                } else {
                    $global_settings = get_option('jet-apb-settings');
                    if ($global_settings && isset($global_settings['locked_time'])) {
                        $locked_time = intval($global_settings['locked_time']);
                    }
                }
                
                $cutoff_time = $appointment_start - $locked_time;
                $rescheduling_allowed = ($current_time < $cutoff_time);
            }
            
            $response[$appt['ID']] = [
                'status' => !empty($appt['appointment_status']) ? $appt['appointment_status'] : 'pending',
                'is_past' => ($appointment_start < $current_time),
                'rescheduling_allowed' => $rescheduling_allowed,
                'slot' => $appointment_start,
                'slot_end' => intval($appt['slot_end'])
            ];
        }
        
        // Cache for 5 minutes (appointments can change frequently)
        wp_cache_set($cache_key, $response, 'nelx_batch_appointments', 300);
        
        return new WP_REST_Response($response, 200);
    }
    
    public function get_available_slots($request) {
        $post_id = intval($request->get_param('provider_post_id'));
        $date = sanitize_text_field($request->get_param('date'));
        
        if (!$post_id || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return new WP_REST_Response(['error' => 'bad_request'], 400);
        }
        
        // Check cache - 5 minutes (slots change when appointments are made)
        $cache_key = 'nelx_available_slots_' . $post_id . '_' . $date;
        $cached = wp_cache_get($cache_key, 'nelx_slots');
        
        if ($cached !== false) {
            return new WP_REST_Response(['slots' => $cached], 200);
        }
        
        $meta = $this->core->load_provider_meta($post_id);
        $cs = $meta['custom_schedule'] ?? [];
        
        $use_custom_schedule = !empty($cs['use_custom_schedule']) || !empty($meta['use_custom_schedule']);
        
        $default_slot = 1800;
        $buffer_before = 0;
        $buffer_after = 0;
        $locked_time = 0;
        $step = 300;
        
        if ($use_custom_schedule) {
            $default_slot = $cs['default_slot'] ?? 1800;
            $buffer_before = $cs['buffer_before'] ?? 0;
            $buffer_after = $cs['buffer_after'] ?? 0;
            $locked_time = $cs['locked_time'] ?? 0;
            $step = $cs['step_duration'] ?? 300;
        } else {
            $global_settings = get_option('jet-apb-settings');
            if ($global_settings && is_array($global_settings)) {
                $default_slot = isset($global_settings['default_slot']) ? intval($global_settings['default_slot']) : 1800;
                $buffer_before = isset($global_settings['buffer_before']) ? intval($global_settings['buffer_before']) : 0;
                $buffer_after = isset($global_settings['buffer_after']) ? intval($global_settings['buffer_after']) : 0;
                $locked_time = isset($global_settings['locked_time']) ? intval($global_settings['locked_time']) : 0;
                $step = isset($global_settings['step_duration']) ? intval($global_settings['step_duration']) : 300;
            }
        }
        
        $working_hours = $this->core->get_working_hours_for_date($post_id, $date, $cs);
        
        if (!$use_custom_schedule && (empty($working_hours) || !is_array($working_hours))) {
            $global_settings = get_option('jet-apb-settings');
            if ($global_settings && isset($global_settings['working_hours']) && is_array($global_settings['working_hours'])) {
                $working_hours = $global_settings['working_hours'];
            }
        }
        
        $ts_mid = strtotime($date . ' 00:00:00 UTC');
        $weekday_key = strtolower(gmdate('l', $ts_mid));
        
        $ranges = [];
        if (isset($working_hours[$weekday_key]) && is_array($working_hours[$weekday_key])) {
            foreach ($working_hours[$weekday_key] as $slot) {
                $from = $slot['from'] ?? '08:00';
                $to = $slot['to'] ?? '17:00';
                
                $start_ts = strtotime($date . ' ' . $from . ' UTC');
                $end_ts = strtotime($date . ' ' . $to . ' UTC');
                
                if ($end_ts <= $start_ts) continue;
                
                $current_start = $start_ts + $buffer_before;
                
                while ($current_start + $default_slot <= $end_ts) {
                    $e = $current_start + $default_slot;
                    
                    if ($e + $buffer_after > $end_ts) {
                        break;
                    }
                    
                    $ranges[] = [
                        'start_ts' => $current_start,
                        'end_ts' => $e,
                        'start' => gmdate('H:i', $current_start), 
                        'end' => gmdate('H:i', $e) 
                    ];
                    
                    $current_start += $default_slot + $buffer_before + $buffer_after;
                }
            }
        }
        
        $day_start = strtotime($date . ' 00:00:00 UTC');
        $day_end = strtotime($date . ' 23:59:59 UTC');
        
        $provCol = preg_replace('/[^a-zA-Z0-9_]/', '', $this->core->provider_column ?: 'staff_id');
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
        $sql = "SELECT slot, slot_end FROM {$this->core->appt_table} WHERE (provider = %s OR {$provCol} = %s) AND appointment_status != 'canceled' AND NOT ( slot_end <= %d OR slot >= %d )";
        $provider_val = (string)$post_id;
        $appt_blocks = $this->core->wpdb->get_results($this->core->wpdb->prepare($sql, $provider_val, $provider_val, $day_start, $day_end), ARRAY_A);
        
        $blocked = [];
        foreach ($appt_blocks as $b) {
            $block_start = intval($b['slot']) - $buffer_before;
            $block_end = intval($b['slot_end']) + $buffer_after;
            $blocked[] = ['start' => $block_start, 'end' => $block_end];
        }
        
        $available = [];
        $current_time = current_time('timestamp', true);
        
        foreach ($ranges as $r) {
            $ok = true;
            
            if ($r['start_ts'] - $locked_time < $current_time) {
                continue;
            }
            
            foreach ($blocked as $bl) {
                if (!($r['end_ts'] <= $bl['start'] || $r['start_ts'] >= $bl['end'])) {
                    $ok = false;
                    break;
                }
            }
            
            if ($ok) {
                $available[] = [
                    'start_ts' => $r['start_ts'],
                    'end_ts' => $r['end_ts'],
                    'start' => $r['start'],
                    'end' => $r['end'],
                ];
            }
        }
        
        // Cache for 5 minutes
        wp_cache_set($cache_key, $available, 'nelx_slots', 300);
        
        return new WP_REST_Response(['slots' => $available], 200);
    }
    
    public function get_available_slots_in_timezone($request) {
        $params = $request->get_json_params();
        $post_id = intval($params['provider_post_id'] ?? 0);
        $date = sanitize_text_field($params['date'] ?? '');
        $client_timezone = sanitize_text_field($params['timezone'] ?? '');
        
        if (!$post_id || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return new WP_REST_Response(['error' => 'bad_request'], 400);
        }
        
        // Check cache for converted slots - 5 minutes
        $cache_key = 'nelx_available_slots_tz_' . $post_id . '_' . $date . '_' . md5($client_timezone);
        $cached = wp_cache_get($cache_key, 'nelx_slots');
        
        if ($cached !== false) {
            return new WP_REST_Response($cached, 200);
        }
        
        // Create a new request object for get_available_slots
        $slots_request = new WP_REST_Request('GET');
        $slots_request->set_param('provider_post_id', $post_id);
        $slots_request->set_param('date', $date);
        
        // Get slots using the SAME provider logic that works
        $slots_response = $this->get_available_slots($slots_request);
        $slots_data = $slots_response->get_data();
        
        if (!empty($client_timezone) && !empty($slots_data['slots'])) {
            $timezone_helper = NELXJAF_Timezone_Helper::instance();
            
            $converted_slots = [];
            foreach ($slots_data['slots'] as $slot) {
                // The timestamps from get_available_slots are provider-local
                // We need to get the date and time WITHOUT any timezone conversion
                
                // Get the provider date from the timestamp (use gmdate since timestamp is provider-local, not UTC)
                $provider_date = gmdate('Y-m-d', $slot['start_ts']);
                $provider_start = gmdate('H:i', $slot['start_ts']);
                $provider_end = gmdate('H:i', $slot['end_ts']);
                
                // Convert from provider-local to client timezone
                $client_start = $timezone_helper->provider_local_to_client(
                    $provider_date, 
                    $provider_start, 
                    $client_timezone, 
                    'H:i'
                );
                
                $client_end = $timezone_helper->provider_local_to_client(
                    $provider_date, 
                    $provider_end, 
                    $client_timezone, 
                    'H:i'
                );
                
                $converted_slots[] = [
                    'start_ts' => $slot['start_ts'],
                    'end_ts' => $slot['end_ts'],
                    'start' => $client_start,
                    'end' => $client_end,
                ];
            }
            
            $response_data = [
                'slots' => $converted_slots,
                'timezone' => $client_timezone,
                'provider_timezone' => $timezone_helper->get_provider_timezone()
            ];
            
            // Cache for 5 minutes
            wp_cache_set($cache_key, $response_data, 'nelx_slots', 300);
            
            return new WP_REST_Response($response_data, 200);
        }
        
        return new WP_REST_Response($slots_data, 200);
    }
    
    public function get_provider_name($request) {
        $params = $request->get_json_params();
        $provider_id = intval($params['provider_id'] ?? 0);
        
        if (!$provider_id) {
            return new WP_REST_Response(['error' => 'bad_request'], 400);
        }
        
        $provider_name = get_the_title($provider_id);
        
        return new WP_REST_Response(['name' => $provider_name ?: 'Unknown'], 200);
    }
    
    public function get_service_title($request) {
        $params = $request->get_json_params();
        $service_id = intval($params['service_id'] ?? 0);
        
        if (!$service_id) {
            return new WP_REST_Response(['error' => 'bad_request'], 400);
        }
        
        $title = get_the_title($service_id);
        
        return new WP_REST_Response(['title' => $title ?: 'Unknown'], 200);
    }
    
    public function get_provider_days_off($request) {
        $post_id = intval($request['id']);
        if (!$post_id) {
            return new WP_REST_Response(['error' => 'bad_request'], 400);
        }
        
        $meta = $this->core->load_provider_meta($post_id);
        $days_off = $meta['custom_schedule']['days_off'] ?? [];
        
        return new WP_REST_Response(['days_off' => $days_off], 200);
    }
    
    public function get_provider_working_days($request) {
        $post_id = intval($request['id']);
        if (!$post_id) {
            return new WP_REST_Response(['error' => 'bad_request'], 400);
        }
        
        // Check cache - 1 hour (working days don't change often)
        $cache_key = 'nelx_provider_working_days_' . $post_id;
        $cached = wp_cache_get($cache_key, 'nelx_provider_working_days');
        
        if ($cached !== false) {
            return new WP_REST_Response($cached, 200);
        }
        
        $meta = $this->core->load_provider_meta($post_id);
        $custom_schedule = $meta['custom_schedule'] ?? [];
        
        $response_data = [
            'working_days_mode' => $custom_schedule['working_days_mode'] ?? 'default',
            'working_days' => $custom_schedule['working_days'] ?? [],
            'working_hours' => $custom_schedule['working_hours'] ?? $this->core->get_global_working_hours(),
            'days_off' => $custom_schedule['days_off'] ?? []
        ];
        
        // Cache for 1 hour
        wp_cache_set($cache_key, $response_data, 'nelx_provider_working_days', HOUR_IN_SECONDS);
        
        return new WP_REST_Response($response_data, 200);
    }
}