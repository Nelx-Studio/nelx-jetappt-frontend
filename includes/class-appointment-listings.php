<?php
/**
 * Native appointment tables and dashboard grids.
 */
if (!defined('ABSPATH')) exit;

class NELXJAF_Appointment_Listings {
    private static $instance = null;
    private $core;
    private static $request_appointment_cache = [];
    private static $assets_registered = false;

    public static function instance($core) {
        if (is_null(self::$instance)) {
            self::$instance = new self($core);
        }
        return self::$instance;
    }

    private function __construct($core) {
        $this->core = $core;
        add_action('wp_enqueue_scripts', [$this, 'register_assets'], 25);
        add_action('rest_api_init', [$this, 'register_calendar_route']);
        add_action('nelx_appointment_rescheduled', [$this, 'invalidate_calendar_cache']);
        add_action('nelx_appointment_status_changed', [$this, 'invalidate_calendar_cache']);
        add_action('nelx_appointment_canceled', [$this, 'invalidate_calendar_cache']);
    }

    /**
     * Register listing assets without loading them on every frontend page.
     * Elementor widgets and listing shortcodes enqueue these handles only when
     * an appointment listing is actually rendered.
     */
    public function register_assets() {
        if (self::$assets_registered) return;
        self::$assets_registered = true;

        $css = NELXJAF_PLUGIN_DIR . 'assets/css/nelx-appointment-listings.css';
        $js  = NELXJAF_PLUGIN_DIR . 'assets/js/nelx-appointment-listings.js';
        if (file_exists($css)) {
            wp_register_style('nelx-appointment-listings', NELXJAF_PLUGIN_URL . 'assets/css/nelx-appointment-listings.css', ['nelx-jetappt-frontend'], filemtime($css));
        }
        if (file_exists($js)) {
            wp_register_script('nelx-appointment-listings', NELXJAF_PLUGIN_URL . 'assets/js/nelx-appointment-listings.js', ['jquery', 'nelx-jetappt-frontend'], filemtime($js), true);
        }
    }

    private function ensure_assets() {
        if (!wp_style_is('nelx-appointment-listings', 'registered')) {
            $this->register_assets();
        }
        wp_enqueue_style('nelx-appointment-listings');
        wp_enqueue_script('nelx-appointment-listings');
    }

    public function invalidate_calendar_cache($appointment_id = 0) {
        $version = (int) wp_cache_get('nelx_calendar_version', 'nelx_appointment_calendar');
        wp_cache_set('nelx_calendar_version', $version + 1, 'nelx_appointment_calendar', DAY_IN_SECONDS);
    }

    public function register_calendar_route() {
        register_rest_route('nelx-jaf/v1', '/appointments/calendar', [
            'methods' => 'GET',
            'callback' => [$this, 'calendar_endpoint'],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
            'args' => [
                'view' => ['required' => true, 'sanitize_callback' => 'sanitize_key'],
                'year' => ['required' => true, 'sanitize_callback' => 'absint'],
                'month' => ['required' => true, 'sanitize_callback' => 'absint'],
            ],
        ]);
    }

    public function calendar_endpoint($request) {
        $view = $request->get_param('view') === 'client' ? 'client' : 'staff';
        $year = absint($request->get_param('year'));
        $month = absint($request->get_param('month'));
        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
            return new WP_REST_Response(['error' => 'invalid_month'], 400);
        }

        $uid = get_current_user_id();
        if (!$uid) return new WP_REST_Response(['error' => 'forbidden'], 403);

        $cache_version = (int) wp_cache_get('nelx_calendar_version', 'nelx_appointment_calendar');
        $cache_key = 'calendar_' . $cache_version . '_' . $uid . '_' . $view . '_' . $year . '_' . $month;
        $cached = wp_cache_get($cache_key, 'nelx_appointment_calendar');
        if ($cached !== false) return new WP_REST_Response($cached, 200);

        // JetAppointments stores provider-local appointment timestamps in the slot fields.
        // Add a small boundary buffer so client-local dates that fall on an adjacent provider day
        // are still available for the selected calendar month.
        $month_start = gmmktime(0, 0, 0, $month, 1, $year);
        $month_end = gmmktime(23, 59, 59, $month + 1, 0, $year);
        $query_start = $month_start - DAY_IN_SECONDS * 2;
        $query_end = $month_end + DAY_IN_SECONDS * 2;

        $where = ['slot >= %d', 'slot <= %d'];
        $params = [$query_start, $query_end];

        if ($view === 'client') {
            $where[] = 'user_id = %d';
            $params[] = $uid;
        } else {
            $provider_post = $this->core->get_provider_post_id_for_user($uid);
            if ($provider_post) {
                $where[] = 'provider = %d';
                $params[] = $provider_post;
            } elseif (!current_user_can('manage_options')) {
                return new WP_REST_Response(['appointments' => []], 200);
            }
        }

        $sql = "SELECT * FROM {$this->core->appt_table} WHERE " . implode(' AND ', $where) . " ORDER BY slot ASC";
        $appointments = $this->core->wpdb->get_results($this->core->wpdb->prepare($sql, $params), ARRAY_A);
        if (!$appointments) {
            $payload = ['appointments' => []];
            wp_cache_set($cache_key, $payload, 'nelx_appointment_calendar', 300);
            return new WP_REST_Response($payload, 200);
        }

        $ids = array_map('absint', wp_list_pluck($appointments, 'ID'));
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $meta_rows = $this->core->wpdb->get_results(
            $this->core->wpdb->prepare("SELECT appointment_id, meta_key, meta_value FROM {$this->core->appt_meta_table} WHERE appointment_id IN ($placeholders)", $ids),
            ARRAY_A
        );
        $meta = [];
        foreach ($meta_rows as $row) $meta[$row['appointment_id']][$row['meta_key']] = $row['meta_value'];

        $timezone_helper = NELXJAF_Timezone_Helper::instance();
        $items = [];

        foreach ($appointments as $appointment) {
            $id = absint($appointment['ID']);
            $appt_meta = $meta[$id] ?? [];
            $slot = (int) ($appointment['slot'] ?? 0);
            $slot_end = (int) ($appointment['slot_end'] ?? 0);
            if (!$slot) continue;

            $calendar_date = gmdate('Y-m-d', $slot);
            $display_date = gmdate('F j, Y', $slot);
            $display_time = ($slot ? gmdate('H:i', $slot) : '—') . '–' . ($slot_end ? gmdate('H:i', $slot_end) : '—');

            if ($view === 'client') {
                $local_timezone = $appt_meta['User Timezone'] ?? $appt_meta['user_timezone'] ?? '';
                $local_date = $appt_meta['User Local Date'] ?? $appt_meta['user_local_date'] ?? '';
                $local_time = $appt_meta['User Local Time'] ?? $appt_meta['user_local_time'] ?? '';

                if ($local_timezone && $local_date && $local_time) {
                    $parsed = DateTime::createFromFormat('F j, Y', $local_date);
                    if ($parsed) {
                        $calendar_date = $parsed->format('Y-m-d');
                        $display_date = $local_date;
                        $display_time = trim((string) $local_time);
                    }
                } else {
                    // Match appointment-info behavior: fall back to provider-local slot values.
                    $local_timezone = $timezone_helper->get_client_timezone($id);
                    $local_date = $timezone_helper->get_client_local_date($id);
                    $local_time = $timezone_helper->get_client_local_time($id);
                    if ($local_timezone && $local_date && $local_time) {
                        $parsed = DateTime::createFromFormat('F j, Y', $local_date);
                        if ($parsed) {
                            $calendar_date = $parsed->format('Y-m-d');
                            $display_date = $local_date;
                            $display_time = trim((string) $local_time);
                        }
                    }
                }
            }

            $month_key = substr($calendar_date, 0, 7);
            if ($month_key !== sprintf('%04d-%02d', $year, $month)) continue;

            $service_title = get_the_title((int) ($appointment['service'] ?? 0));
            $service_title = $service_title ? html_entity_decode(wp_strip_all_tags($service_title), ENT_QUOTES, get_bloginfo('charset') ?: 'UTF-8') : 'Unknown';
            if (!$service_title) $service_title = 'Unknown';
            $person = $view === 'client'
                ? (get_the_title((int) ($appointment['provider'] ?? 0)) ?: 'Unknown')
                : ($appointment['user_name'] ?? ($appointment['client_name'] ?? '—'));

            $appointment['_meta'] = $appt_meta;
            $action_html = $this->get_legacy_shortcode_actions($id, $view);

            $items[] = [
                'id' => $id,
                'date' => $calendar_date,
                'display_date' => $display_date,
                'time' => $display_time,
                'person' => $person,
                'service' => $service_title,
                'status' => $appointment['appointment_status'] ?? 'pending',
                'action_html' => $action_html,
            ];
        }

        $payload = ['appointments' => $items];
        wp_cache_set($cache_key, $payload, 'nelx_appointment_calendar', 300);
        return new WP_REST_Response($payload, 200);
    }

    public function register_shortcodes() {
        add_shortcode('nelx_staff_appointments', [$this, 'staff_table']);
        add_shortcode('nelx_client_appointments', [$this, 'client_table']);
        add_shortcode('nelx_staff_appointments_grid', [$this, 'staff_grid']);
        add_shortcode('nelx_client_appointments_grid', [$this, 'client_grid']);
    }

    public function get_table_columns() {
        $columns = $this->get_db_columns();
        $virtual = [
            'id' => 'ID',
            'service' => 'Service',
            'client' => 'Client',
            'staff' => 'Staff',
            'appointment_date' => 'Appointment Date',
            'time' => 'Time',
            'status' => 'Payment Status',
            'appointment_status' => 'Appointment Status',
            'action' => 'Action',
        ];
        foreach ($virtual as $key => $label) {
            if (!isset($columns[$key])) $columns[$key] = $label;
        }
        return $columns;
    }

    public function get_modal_field_definitions($view = 'staff') {
        $virtual = [
            'id' => 'ID',
            'start' => 'Start',
            'end' => 'End',
            'date' => 'Date',
            'time' => 'Time',
            'timezone' => 'Timezone',
            'service' => 'Service',
            'client' => 'Client',
            'staff' => 'Staff',
            'provider' => 'Provider',
            'client_email' => 'Client Email',
            'client_phone' => 'Client Phone',
            'google_meet' => 'Google Meet',
            'client_local_date' => 'Client Local Date',
            'client_local_time' => 'Client Local Time',
            'client_local_timezone' => 'Client Local Timezone',
            'status' => 'Payment Status',
            'appointment_status' => 'Appointment Status',
            'notes' => 'Notes',
        ];

        // All native JetAppointments columns are available in addition to the
        // friendly virtual fields above. Native status is payment status.
        foreach ($this->get_table_columns() as $key => $label) {
            if (!isset($virtual[$key])) $virtual[$key] = $label;
        }
        return $virtual;
    }

    public function get_modal_fields($view = 'staff') {
        $definitions = $this->get_modal_field_definitions($view);
        $defaults = $this->get_modal_defaults($view);

        $settings = $this->get_settings();
        $key = $view === 'client' ? 'client_modal_fields' : 'staff_modal_fields';
        $selected = isset($settings[$key]) && is_array($settings[$key]) ? $settings[$key] : $defaults;
        $selected = array_values(array_unique(array_filter(array_map('sanitize_key', $selected), function($field) use ($definitions) {
            return isset($definitions[$field]);
        })));
        return $selected ?: $defaults;
    }

    public function get_modal_labels($view = 'staff') {
        $definitions = $this->get_modal_field_definitions($view);
        $key = $view === 'client' ? 'client_modal_labels' : 'staff_modal_labels';
        $saved = $this->get_settings();
        $saved = isset($saved[$key]) && is_array($saved[$key]) ? $saved[$key] : [];
        $labels = [];
        foreach ($definitions as $field => $default) {
            $labels[$field] = !empty($saved[$field]) ? sanitize_text_field($saved[$field]) : $default;
        }
        return $labels;
    }

    public function get_table_labels($view = 'staff') {
        $definitions = $this->get_table_columns();
        $key = $view === 'client' ? 'client_table_labels' : 'staff_table_labels';
        $saved = $this->get_settings();
        $saved = isset($saved[$key]) && is_array($saved[$key]) ? $saved[$key] : [];
        $labels = [];
        foreach ($definitions as $column => $default) {
            $labels[$column] = !empty($saved[$column]) ? sanitize_text_field($saved[$column]) : $default;
        }
        return $labels;
    }

    private function get_modal_defaults($view) {
        return $view === 'client'
            ? ['date', 'time', 'timezone', 'service', 'provider', 'google_meet', 'appointment_status', 'notes']
            : ['start', 'end', 'timezone', 'service', 'client', 'client_email', 'client_phone', 'google_meet', 'appointment_status', 'client_local_date', 'client_local_time', 'client_local_timezone', 'notes'];
    }

    private function get_db_columns() {
        $cache_key = 'nelx_jetappt_table_columns_v3';
        $cached = wp_cache_get($cache_key, 'nelx_appointment_listings');
        if ($cached !== false && is_array($cached)) return $cached;

        $cols = $this->core->wpdb->get_col("DESCRIBE {$this->core->appt_table}", 0);
        $result = [];
        foreach ((array) $cols as $column) {
            $key = sanitize_key($column);
            if ($key === 'status') $label = 'Payment Status';
            elseif ($key === 'appointment_status') $label = 'Appointment Status';
            elseif ($key === 'slot') $label = 'Slot';
            elseif ($key === 'slot_end') $label = 'Slot End';
            elseif ($key === 'appointment_date') $label = 'Appointment Date';
            elseif ($key === 'id') $label = 'ID';
            else $label = ucwords(str_replace(['_', '-'], ' ', $key));
            $result[$key] = $label;
        }
        wp_cache_set($cache_key, $result, 'nelx_appointment_listings', HOUR_IN_SECONDS);
        return $result;
    }

    public function get_settings() {
        $defaults = [
            'staff_table_columns' => ['id', 'service', 'client', 'appointment_date', 'slot', 'slot_end', 'action'],
            'client_table_columns' => ['id', 'service', 'staff', 'appointment_date', 'time', 'status', 'action'],
            'staff_grid_limit' => 5,
            'client_grid_limit' => 5,
            'staff_grid_columns_desktop' => 3,
            'staff_grid_columns_tablet' => 2,
            'client_grid_columns_desktop' => 3,
            'client_grid_columns_tablet' => 2,
            'staff_modal_fields' => ['start', 'end', 'timezone', 'service', 'client', 'client_email', 'client_phone', 'google_meet', 'appointment_status', 'client_local_date', 'client_local_time', 'client_local_timezone', 'notes'],
            'client_modal_fields' => ['date', 'time', 'timezone', 'service', 'provider', 'google_meet', 'appointment_status', 'notes'],
            'staff_table_labels' => [],
            'client_table_labels' => [],
            'staff_modal_labels' => [],
            'client_modal_labels' => [],
            'table_header_bg' => '',
            'table_header_text' => '#ffffff',
            'table_header_font_size' => 14,
            'table_body_font_size' => 14,
            'table_control_font_size' => 14,
            'table_hover_bg' => '#f5f5f5',
            'grid_card_bg' => '#ffffff',
            'grid_card_border' => '#e5e7eb',
            'grid_card_hover_bg' => '#ffffff',
            'grid_card_hover_border' => '#d1d5db',
            'grid_label_font_size' => 13,
            'grid_label_font_weight' => 400,
            'grid_label_line_height' => 1.4,
            'grid_value_font_size' => 14,
            'grid_value_font_weight' => 500,
            'grid_value_line_height' => 1.4,
            'grid_empty_font_size' => 14,
            'grid_empty_font_weight' => 400,
            'grid_empty_line_height' => 1.4,
            'action_transition_duration' => 0.2,
            'action_disabled_opacity' => 0.5,
            'action_disabled_bg' => '',
            'action_edit_color' => '',
            'action_edit_bg' => '',
            'action_edit_hover_color' => '',
            'action_edit_hover_bg' => '',
            'action_confirm_color' => '',
            'action_confirm_bg' => '',
            'action_confirm_hover_color' => '',
            'action_confirm_hover_bg' => '',
            'action_reject_color' => '',
            'action_reject_bg' => '',
            'action_reject_hover_color' => '',
            'action_reject_hover_bg' => '',
            'action_info_color' => '',
            'action_info_bg' => '',
            'action_info_hover_color' => '',
            'action_info_hover_bg' => '',
        ];
        $settings = get_option('nelx_jetappt_settings', []);
        return array_merge($defaults, is_array($settings) ? $settings : []);
    }

    private function sanitize_columns($columns, $fallback) {
        $available = $this->get_table_columns();
        $out = [];
        foreach ((array) $columns as $column) {
            $column = sanitize_key($column);
            // Migrate the old virtual aliases to the real JetAppointments columns.
            if ($column === 'start_time') $column = 'slot';
            if ($column === 'end_time') $column = 'slot_end';
            if (isset($available[$column]) && !in_array($column, $out, true)) $out[] = $column;
        }
        return $out ?: $fallback;
    }

    private function get_appointments($view, $limit = 0) {
        $uid = get_current_user_id();
        if (!$uid) return [];

        $cache_key = $view . ':' . absint($limit) . ':' . $uid;
        if (isset(self::$request_appointment_cache[$cache_key])) {
            return self::$request_appointment_cache[$cache_key];
        }

        $where = [];
        $params = [];
        if ($view === 'client') {
            $where[] = 'user_id = %d';
            $params[] = $uid;
        } else {
            $provider_post = $this->core->get_provider_post_id_for_user($uid);
            if (!$provider_post && !current_user_can('manage_options')) return [];
            if ($provider_post) {
                $where[] = 'provider = %d';
                $params[] = $provider_post;
            }
        }

        $now = current_time('timestamp');
        if ($limit > 0) $where[] = 'slot >= %d';
        if ($limit > 0) $params[] = $now;

        $sql = "SELECT * FROM {$this->core->appt_table}";
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        if ($limit > 0) {
            $sql .= ' ORDER BY slot ASC LIMIT %d';
            $params[] = absint($limit);
        } else {
            $sql .= ' ORDER BY CASE WHEN slot >= %d THEN 0 ELSE 1 END ASC, CASE WHEN slot >= %d THEN slot ELSE -slot END ASC';
            $params[] = $now;
            $params[] = $now;
        }

        $prepared = $this->core->wpdb->prepare($sql, $params);
        $appointments = $this->core->wpdb->get_results($prepared, ARRAY_A);
        if (!$appointments) {
            self::$request_appointment_cache[$cache_key] = [];
            return [];
        }

        $ids = array_map('absint', wp_list_pluck($appointments, 'ID'));
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $meta_rows = $this->core->wpdb->get_results(
            $this->core->wpdb->prepare("SELECT appointment_id, meta_key, meta_value FROM {$this->core->appt_meta_table} WHERE appointment_id IN ($placeholders)", $ids),
            ARRAY_A
        );
        $meta = [];
        foreach ($meta_rows as $row) $meta[$row['appointment_id']][$row['meta_key']] = $row['meta_value'];

        foreach ($appointments as &$appointment) {
            $appointment['_meta'] = $meta[$appointment['ID']] ?? [];
            $service_title = get_the_title((int) ($appointment['service'] ?? 0));
            $appointment['_service_title'] = $service_title ? html_entity_decode(wp_strip_all_tags($service_title), ENT_QUOTES, get_bloginfo('charset') ?: 'UTF-8') : '';
        }
        unset($appointment);
        self::$request_appointment_cache[$cache_key] = $appointments;
        return $appointments;
    }

    private function display_value($appointment, $column, $view) {
        $meta = $appointment['_meta'] ?? [];
        $slot = (int) ($appointment['slot'] ?? 0);
        $slot_end = (int) ($appointment['slot_end'] ?? 0);
        switch ($column) {
            case 'id': return (string) ($appointment['ID'] ?? '');
            case 'service': return $appointment['_service_title'] ?: ($appointment['service'] ?? '—');
            case 'client': return $appointment['user_name'] ?? ($appointment['client_name'] ?? '—');
            case 'staff': return $this->get_staff_user_name($appointment['staff_id'] ?? 0);
            case 'provider': return $this->get_provider_post_title($appointment['provider'] ?? 0);
            case 'appointment_date':
                if ($view === 'client') {
                    $local_date = $meta['User Local Date'] ?? ($meta['user_local_date'] ?? '');
                    $local_time = $meta['User Local Time'] ?? ($meta['user_local_time'] ?? '');
                    $local_timezone = $meta['User Timezone'] ?? ($meta['user_timezone'] ?? '');
                    if ($local_date && $local_time && $local_timezone) return $local_date;
                }
                return $slot ? gmdate('F j, Y', $slot) : '—';
            case 'start_time': return $slot ? gmdate('H.i', $slot) : '—';
            case 'end_time': return $slot_end ? gmdate('H.i', $slot_end) : '—';
            case 'time':
                if ($view === 'client') {
                    $local_time = $meta['User Local Time'] ?? ($meta['user_local_time'] ?? '');
                    $local_date = $meta['User Local Date'] ?? ($meta['user_local_date'] ?? '');
                    $local_timezone = $meta['User Timezone'] ?? ($meta['user_timezone'] ?? '');
                    if ($local_time && $local_date && $local_timezone) return trim((string) $local_time);
                }
                return ($slot ? gmdate('H:i', $slot) : '—') . '–' . ($slot_end ? gmdate('H:i', $slot_end) : '—');
            case 'status': return $this->format_payment_status($appointment['status'] ?? '');
            case 'type':
            case 'appointment_type':
                $type = $appointment['appointment_type'] ?? ($appointment['type'] ?? '');
                return $type !== '' ? ucwords(str_replace(['_', '-'], ' ', (string) $type)) : '—';
            case 'action': return $this->get_legacy_shortcode_actions((int) ($appointment['ID'] ?? 0), $view);
            case 'date': return $slot ? gmdate('F j, Y', $slot) : '—';
            case 'slot': return $slot ? gmdate('H:i', $slot) : '—';
            case 'slot_end': return $slot_end ? gmdate('H:i', $slot_end) : '—';
            case 'appointment_status': return $this->format_status($appointment['appointment_status'] ?? 'pending');
            default:
                return $this->format_generic_db_value($appointment[$column] ?? '');
        }
    }

    private function format_payment_status($status) {
        $status = sanitize_key((string) $status);
        $labels = [
            'pending' => 'Pending payment',
            'processing' => 'Processing',
            'on-hold' => 'On hold',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'canceled' => 'Canceled',
            'refunded' => 'Refunded',
            'failed' => 'Failed',
        ];
        return $labels[$status] ?? $this->format_status($status ?: 'pending');
    }

    private function format_generic_db_value($value) {
        if ($value === null || $value === '') return '—';
        if (is_scalar($value)) {
            $value = html_entity_decode(wp_strip_all_tags((string) $value), ENT_QUOTES, get_bloginfo('charset') ?: 'UTF-8');
            if ($value === '') return '—';
            // Humanize machine values without altering normal identifiers/codes.
            if (strpos($value, '_') !== false) $value = ucwords(str_replace('_', ' ', $value));
            return $value;
        }
        return '—';
    }

    private function get_staff_user_name($user_id) {
        $user_id = absint($user_id);
        if (!$user_id) return '—';
        $first = trim((string) get_user_meta($user_id, 'first_name', true));
        $last  = trim((string) get_user_meta($user_id, 'last_name', true));
        $full  = trim($first . ' ' . $last);
        if ($full !== '') return $full;
        $user = get_userdata($user_id);
        return $user ? ($user->display_name ?: $user->user_login) : '—';
    }

    private function get_provider_post_title($provider_id) {
        $provider_id = absint($provider_id);
        if (!$provider_id) return '—';
        $title = get_the_title($provider_id);
        return $title ? html_entity_decode(wp_strip_all_tags($title), ENT_QUOTES, get_bloginfo('charset') ?: 'UTF-8') : '—';
    }

    private function format_status($status) {
        $status = (string) $status;
        $label = $status === 'canceled' ? 'Canceled' : ucwords(str_replace(['_', '-'], ' ', $status ?: 'pending'));
        return '<span class="nelx-appointment-status nelx-status-' . esc_attr(sanitize_html_class($status ?: 'pending')) . '">' . esc_html($label) . '</span>';
    }

    /**
     * Render the exact action-button markup produced by the existing legacy
     * action-button shortcodes. The listing system must not maintain a second
     * implementation of these controls.
     */
    private function get_legacy_shortcode_actions($appointment_id, $view) {
        $appointment_id = absint($appointment_id);
        if (!$appointment_id) return '';

        if ($view === 'client') {
            return do_shortcode('[nelx_client_action_buttons appointment_id="' . $appointment_id . '"]');
        }

        return do_shortcode('[nelx_provider_action_buttons appointment_id="' . $appointment_id . '"]');
    }

    private function table($view) {
        if (!is_user_logged_in()) return '<div class="nelx-notice">' . esc_html__('Login required.', 'nelx-jetappt-frontend') . '</div>';
        $this->ensure_assets();
        $settings = $this->get_settings();
        $key = $view === 'client' ? 'client_table_columns' : 'staff_table_columns';
        $fallback = $view === 'client' ? ['id','service','staff','appointment_date','time','status','action'] : ['id','service','client','appointment_date','slot','slot_end','action'];
        $columns = $this->sanitize_columns($settings[$key] ?? [], $fallback);
        $appointments = $this->get_appointments($view);
        $all = $this->get_table_columns();
        $labels = $this->get_table_labels($view);
        $table_style = $this->get_inline_table_style($settings);

        ob_start(); ?>
        <div class="nelx-appointment-table-wrap" data-view="<?php echo esc_attr($view); ?>" data-calendar-enabled="1" style="<?php echo esc_attr($table_style); ?>">
            <div class="nelx-appointment-display-switch" role="tablist" aria-label="<?php esc_attr_e('Appointment display', 'nelx-jetappt-frontend'); ?>">
                <button type="button" class="nelx-display-tab is-active" data-display="list" role="tab" aria-selected="true">List</button>
                <button type="button" class="nelx-display-tab" data-display="calendar" role="tab" aria-selected="false">Calendar</button>
            </div>

            <div class="nelx-appointment-list-view" data-display-panel="list">
                <div class="nelx-appointment-table-toolbar">
                    <div class="nelx-appointment-table-search"><input type="search" placeholder="<?php esc_attr_e('Search table...', 'nelx-jetappt-frontend'); ?>" aria-label="<?php esc_attr_e('Search table', 'nelx-jetappt-frontend'); ?>"></div>
                    <div class="nelx-appointment-table-export"><button type="button" data-export="copy">Copy</button><button type="button" data-export="csv">CSV</button><button type="button" data-export="excel">Excel</button><button type="button" data-export="print">Print</button><button type="button" data-export="pdf">PDF</button></div>
                    <div class="nelx-appointment-table-length"><label><span class="screen-reader-text">Entries per page</span><select><option>5</option><option selected>10</option><option>25</option><option>50</option><option>100</option><option>150</option><option>200</option><option>250</option><option>500</option><option>1000</option><option value="-1">All</option></select></label></div>
                    <div class="nelx-appointment-table-pagination nelx-appointment-table-pagination-top"></div>
                </div>
                <div class="nelx-appointment-table-scroll">
                    <table class="nelx-appointment-table">
                        <thead><tr><?php foreach ($columns as $column): ?><th data-column="<?php echo esc_attr($column); ?>"><?php echo esc_html($labels[$column] ?? ($all[$column] ?? ucwords(str_replace('_', ' ', $column)))); ?></th><?php endforeach; ?></tr></thead>
                        <tbody>
                        <?php foreach ($appointments as $appointment): ?><tr data-appointment-id="<?php echo esc_attr($appointment['ID']); ?>"><?php foreach ($columns as $column): ?><td data-column="<?php echo esc_attr($column); ?>"><?php $cell_value = $this->display_value($appointment, $column, $view); if ($column === 'action') { echo $cell_value; } else { echo wp_kses_post($cell_value); } ?></td><?php endforeach; ?></tr><?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (!$appointments): ?><div class="nelx-appointment-table-empty"><?php esc_html_e('No appointments found.', 'nelx-jetappt-frontend'); ?></div><?php endif; ?>
                </div>
                <div class="nelx-appointment-table-footer"><div class="nelx-appointment-table-info"></div><div class="nelx-appointment-table-pagination nelx-appointment-table-pagination-bottom"></div></div>
            </div>

            <div class="nelx-appointment-calendar-view" data-display-panel="calendar" hidden>
                <div class="nelx-appointment-calendar-toolbar">
                    <button type="button" class="nelx-calendar-nav" data-calendar-nav="prev" aria-label="Previous month">‹</button>
                    <h3 class="nelx-calendar-title" aria-live="polite"></h3>
                    <button type="button" class="nelx-calendar-nav" data-calendar-nav="next" aria-label="Next month">›</button>
                </div>
                <div class="nelx-calendar-message" aria-live="polite"></div>
                <div class="nelx-appointment-calendar-scroll">
                    <div class="nelx-appointment-calendar-grid" role="grid">
                        <div class="nelx-calendar-weekday">Sun</div><div class="nelx-calendar-weekday">Mon</div><div class="nelx-calendar-weekday">Tue</div><div class="nelx-calendar-weekday">Wed</div><div class="nelx-calendar-weekday">Thu</div><div class="nelx-calendar-weekday">Fri</div><div class="nelx-calendar-weekday">Sat</div>
                    </div>
                </div>
            </div>
        </div>
        <?php return ob_get_clean();
    }

    public function staff_table($atts = []) { return $this->table('staff'); }
    public function client_table($atts = []) { return $this->table('client'); }

    private function get_inline_table_style($settings) {
        $header_bg = !empty($settings['table_header_bg']) ? $settings['table_header_bg'] : '';
        $vars = [];
        if ($header_bg) $vars[] = '--nelx-appointment-table-header-bg:' . sanitize_hex_color($header_bg);
        if (!empty($settings['table_header_text'])) $vars[] = '--nelx-appointment-table-header-text:' . sanitize_hex_color($settings['table_header_text']);
        if (!empty($settings['table_header_font_size'])) $vars[] = '--nelx-appointment-table-header-font-size:' . absint($settings['table_header_font_size']) . 'px';
        if (!empty($settings['table_body_font_size'])) $vars[] = '--nelx-appointment-table-body-font-size:' . absint($settings['table_body_font_size']) . 'px';
        if (!empty($settings['table_control_font_size'])) $vars[] = '--nelx-appointment-table-control-font-size:' . absint($settings['table_control_font_size']) . 'px';
        if (!empty($settings['table_hover_bg'])) $vars[] = '--nelx-appointment-table-hover-bg:' . sanitize_hex_color($settings['table_hover_bg']);
        $vars = array_merge($vars, $this->get_action_style_vars($settings));
        return implode(';', $vars);
    }

    private function get_action_style_vars($settings) {
        $vars = [];
        if (isset($settings['action_transition_duration']) && $settings['action_transition_duration'] !== '') {
            $vars[] = '--nelx-action-transition-duration:' . max(0, min(3, (float) $settings['action_transition_duration'])) . 's';
        }
        if (isset($settings['action_disabled_opacity']) && $settings['action_disabled_opacity'] !== '') {
            $vars[] = '--nelx-action-disabled-opacity:' . max(0.1, min(1, (float) $settings['action_disabled_opacity']));
        }
        $colors = ['edit','confirm','reject','info'];
        foreach ($colors as $button) {
            foreach (['color','bg','hover_color','hover_bg'] as $part) {
                $key = 'action_' . $button . '_' . $part;
                $value = isset($settings[$key]) ? sanitize_hex_color($settings[$key]) : '';
                if ($value) $vars[] = '--nelx-action-' . $button . '-' . str_replace('_', '-', $part) . ':' . $value;
            }
        }
        if (!empty($settings['action_disabled_bg'])) {
            $vars[] = '--nelx-action-disabled-bg:' . sanitize_hex_color($settings['action_disabled_bg']);
        }
        return $vars;
    }

    private function grid($view, $overrides = []) {
        if (!is_user_logged_in()) return '<div class="nelx-notice">' . esc_html__('Login required.', 'nelx-jetappt-frontend') . '</div>';
        $this->ensure_assets();
        $settings = $this->get_settings();
        $limit_setting = isset($overrides['limit']) && absint($overrides['limit']) > 0 ? absint($overrides['limit']) : absint($settings[$view . '_grid_limit'] ?? 5);
        $limit = max(1, min(100, $limit_setting));
        $desktop_cols = isset($overrides['desktop_columns']) && absint($overrides['desktop_columns']) > 0 ? absint($overrides['desktop_columns']) : absint($settings[$view . '_grid_columns_desktop'] ?? 3);
        $tablet_cols = isset($overrides['tablet_columns']) && absint($overrides['tablet_columns']) > 0 ? absint($overrides['tablet_columns']) : absint($settings[$view . '_grid_columns_tablet'] ?? 2);
        $desktop_cols = max(1, min(6, $desktop_cols));
        $tablet_cols = max(1, min(4, $tablet_cols));
        $appointments = $this->get_appointments($view, $limit);
        $grid_vars = [
            '--nelx-grid-desktop-cols:' . $desktop_cols,
            '--nelx-grid-tablet-cols:' . $tablet_cols,
            '--nelx-grid-card-bg:' . sanitize_hex_color($settings['grid_card_bg'] ?? '#ffffff'),
            '--nelx-grid-card-border:' . sanitize_hex_color($settings['grid_card_border'] ?? '#e5e7eb'),
            '--nelx-grid-card-hover-bg:' . sanitize_hex_color($settings['grid_card_hover_bg'] ?? '#ffffff'),
            '--nelx-grid-card-hover-border:' . sanitize_hex_color($settings['grid_card_hover_border'] ?? '#d1d5db'),
            '--nelx-grid-label-font-size:' . absint($settings['grid_label_font_size'] ?? 13) . 'px',
            '--nelx-grid-label-font-weight:' . absint($settings['grid_label_font_weight'] ?? 400),
            '--nelx-grid-label-line-height:' . max(1, (float) ($settings['grid_label_line_height'] ?? 1.4)),
            '--nelx-grid-value-font-size:' . absint($settings['grid_value_font_size'] ?? 14) . 'px',
            '--nelx-grid-value-font-weight:' . absint($settings['grid_value_font_weight'] ?? 500),
            '--nelx-grid-value-line-height:' . max(1, (float) ($settings['grid_value_line_height'] ?? 1.4)),
            '--nelx-grid-empty-font-size:' . absint($settings['grid_empty_font_size'] ?? 14) . 'px',
            '--nelx-grid-empty-font-weight:' . absint($settings['grid_empty_font_weight'] ?? 400),
            '--nelx-grid-empty-line-height:' . max(1, (float) ($settings['grid_empty_line_height'] ?? 1.4)),
        ];
        $grid_vars = array_merge($grid_vars, $this->get_action_style_vars($settings));
        ob_start(); ?>
        <div class="nelx-appointment-grid nelx-appointment-grid-<?php echo esc_attr($view); ?>" style="<?php echo esc_attr(implode(';', $grid_vars)); ?>">
        <?php foreach ($appointments as $appointment): ?>
            <article class="nelx-appointment-card">
                <div class="nelx-appointment-card-details">
                    <div><label>Date</label><span><?php echo esc_html($this->display_value($appointment, 'appointment_date', $view)); ?></span></div>
                    <div><label>Time</label><span><?php echo esc_html($this->display_value($appointment, 'time', $view)); ?></span></div>
                    <div><label><?php echo $view === 'client' ? 'Staff' : 'Client'; ?></label><span><?php echo esc_html($this->display_value($appointment, $view === 'client' ? 'staff' : 'client', $view)); ?></span></div>
                    <div><label>Type</label><span><?php echo esc_html($this->display_value($appointment, 'type', $view)); ?></span></div>
                </div>
                <div class="nelx-appointment-card-actions"><?php echo $this->get_legacy_shortcode_actions((int) $appointment['ID'], $view); ?></div>
            </article>
        <?php endforeach; ?>
        </div>
        <?php if (!$appointments): ?><div class="nelx-appointment-grid-empty">No upcoming appointments.</div><?php endif; ?>
        <?php return ob_get_clean();
    }

    /**
     * Lightweight Elementor editor preview. The editor can render a widget many
     * times while controls are being changed, so it must not query appointment
     * rows and metadata just to paint the editor canvas.
     */
    public function render_elementor_preview($view = 'staff', $display = 'table') {
        $view = $view === 'client' ? 'client' : 'staff';
        $display = $display === 'grid' ? 'grid' : 'table';
        // Editor preview only needs the listing CSS; the runtime JS is not required.
        $this->register_assets();
        wp_enqueue_style('nelx-appointment-listings');
        $settings = $this->get_settings();

        if ($display === 'grid') {
            $desktop_cols = max(1, min(6, absint($settings[$view . '_grid_columns_desktop'] ?? 3)));
            $tablet_cols = max(1, min(4, absint($settings[$view . '_grid_columns_tablet'] ?? 2)));
            $vars = [
                '--nelx-grid-desktop-cols:' . $desktop_cols,
                '--nelx-grid-tablet-cols:' . $tablet_cols,
                '--nelx-grid-card-bg:' . sanitize_hex_color($settings['grid_card_bg'] ?? '#ffffff'),
                '--nelx-grid-card-border:' . sanitize_hex_color($settings['grid_card_border'] ?? '#e5e7eb'),
                '--nelx-grid-card-hover-bg:' . sanitize_hex_color($settings['grid_card_hover_bg'] ?? '#ffffff'),
                '--nelx-grid-card-hover-border:' . sanitize_hex_color($settings['grid_card_hover_border'] ?? '#d1d5db'),
                '--nelx-grid-label-font-size:' . absint($settings['grid_label_font_size'] ?? 13) . 'px',
                '--nelx-grid-label-font-weight:' . absint($settings['grid_label_font_weight'] ?? 400),
                '--nelx-grid-label-line-height:' . max(1, (float) ($settings['grid_label_line_height'] ?? 1.4)),
                '--nelx-grid-value-font-size:' . absint($settings['grid_value_font_size'] ?? 14) . 'px',
                '--nelx-grid-value-font-weight:' . absint($settings['grid_value_font_weight'] ?? 500),
                '--nelx-grid-value-line-height:' . max(1, (float) ($settings['grid_value_line_height'] ?? 1.4)),
                '--nelx-grid-empty-font-size:' . absint($settings['grid_empty_font_size'] ?? 14) . 'px',
                '--nelx-grid-empty-font-weight:' . absint($settings['grid_empty_font_weight'] ?? 400),
                '--nelx-grid-empty-line-height:' . max(1, (float) ($settings['grid_empty_line_height'] ?? 1.4)),
            ];
            $vars = array_merge($vars, $this->get_action_style_vars($settings));
            $actions = $view === 'client'
                ? do_shortcode('[nelx_client_action_buttons]')
                : do_shortcode('[nelx_provider_action_buttons]');
            ob_start(); ?>
            <div class="nelx-appointment-grid nelx-appointment-grid-<?php echo esc_attr($view); ?>" style="<?php echo esc_attr(implode(';', $vars)); ?>">
                <article class="nelx-appointment-card">
                    <div class="nelx-appointment-card-details">
                        <div><label>Date</label><span>August 20, 2026</span></div>
                        <div><label>Time</label><span>10:15 – 11:00</span></div>
                        <div><label><?php echo $view === 'client' ? 'Staff' : 'Client'; ?></label><span><?php echo $view === 'client' ? 'Sample Staff' : 'Sample Client'; ?></span></div>
                        <div><label>Type</label><span>Online</span></div>
                    </div>
                    <div class="nelx-appointment-card-actions"><?php echo $actions; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                </article>
            </div>
            <?php return ob_get_clean();
        }

        $key = $view === 'client' ? 'client_table_columns' : 'staff_table_columns';
        $fallback = $view === 'client'
            ? ['id','service','staff','appointment_date','time','status','action']
            : ['id','service','client','appointment_date','slot','slot_end','action'];
        $columns = $this->sanitize_columns($settings[$key] ?? [], $fallback);
        $all = $this->get_table_columns();
        $labels = $this->get_table_labels($view);
        $sample = [
            'ID' => 100,
            'service' => 'Service Request – – CM5H – CRK-C25W275',
            'user_name' => 'Sample Client',
            'staff_id' => 0,
            'provider' => 0,
            'appointment_status' => 'pending',
            'status' => 'pending',
            'slot' => strtotime('today 10:15'),
            'slot_end' => strtotime('today 11:00'),
            'appointment_type' => 'online',
        ];
        $sample['_service_title'] = $sample['service'];
        $sample['_meta'] = [];
        $actions = $view === 'client'
            ? do_shortcode('[nelx_client_action_buttons]')
            : do_shortcode('[nelx_provider_action_buttons]');
        $table_style = $this->get_inline_table_style($settings);

        ob_start(); ?>
        <div class="nelx-appointment-table-wrap" data-view="<?php echo esc_attr($view); ?>" style="<?php echo esc_attr($table_style); ?>">
            <div class="nelx-appointment-display-switch" role="tablist" aria-label="Appointment display">
                <button type="button" class="nelx-display-tab is-active">List</button>
                <button type="button" class="nelx-display-tab">Calendar</button>
            </div>
            <div class="nelx-appointment-list-view">
                <div class="nelx-appointment-table-toolbar">
                    <div class="nelx-appointment-table-search"><input type="search" placeholder="Search table..." aria-label="Search table"></div>
                    <div class="nelx-appointment-table-export"><button type="button">Copy</button><button type="button">CSV</button><button type="button">Excel</button><button type="button">Print</button><button type="button">PDF</button></div>
                    <div class="nelx-appointment-table-length"><select aria-label="Entries per page"><option selected>10</option><option>25</option><option>50</option></select></div>
                    <div class="nelx-appointment-table-pagination nelx-appointment-table-pagination-top"><button type="button">&lt;&lt;</button><button type="button" aria-current="page">1</button><button type="button">&gt;&gt;</button></div>
                </div>
                <div class="nelx-appointment-table-scroll">
                    <table class="nelx-appointment-table"><thead><tr><?php foreach ($columns as $column): ?><th data-column="<?php echo esc_attr($column); ?>"><?php echo esc_html($labels[$column] ?? ($all[$column] ?? ucwords(str_replace('_', ' ', $column)))); ?></th><?php endforeach; ?></tr></thead>
                        <tbody><tr><?php foreach ($columns as $column): ?><td data-column="<?php echo esc_attr($column); ?>"><?php if ($column === 'action') { echo $actions; } else { echo wp_kses_post($this->display_value($sample, $column, $view)); } ?></td><?php endforeach; ?></tr></tbody>
                    </table>
                </div>
                <div class="nelx-appointment-table-footer"><div class="nelx-appointment-table-info">Showing 1 to 1 of 1 entries</div><div class="nelx-appointment-table-pagination nelx-appointment-table-pagination-bottom"><button type="button">&lt;&lt;</button><button type="button" aria-current="page">1</button><button type="button">&gt;&gt;</button></div></div>
            </div>
        </div>
        <?php return ob_get_clean();
    }

    public function staff_grid($atts = []) { return $this->grid('staff'); }
    public function client_grid($atts = []) { return $this->grid('client'); }
    public function staff_grid_render($overrides = []) { return $this->grid('staff', $overrides); }
    public function client_grid_render($overrides = []) { return $this->grid('client', $overrides); }
}
