<?php
/**
 * Notification handler for Nelx JetAppointments
 */

if (!defined('ABSPATH')) exit;

class NELXJAF_Appointment_Notifications {
    private static $instance = null;
    private $options;
    private $enabled;
    private $provider_appointments_page;
    private $client_appointments_page;
    
    private $notification_settings;
    private $endpoint_url;
    private $cct_recipient_field;
    private $cct_user_roles_field;
    private $cct_type_field;
    private $cct_message_field;
    private $cct_url_field;
    private $cct_status_field;
    private $cct_sound_played_field;
    private $cct_auth_method;
    private $cct_api_username;
    private $cct_application_password;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_settings();
        $this->load_central_notification_settings();
        
        if ($this->enabled && !empty($this->endpoint_url)) {
            add_action('nelx_appointment_created', [$this, 'handle_new_appointment'], 10, 2);
            add_action('nelx_appointment_status_changed', [$this, 'handle_status_change'], 10, 3);
            add_action('nelx_appointment_canceled', [$this, 'handle_cancellation'], 10, 2);
            add_action('nelx_appointment_rescheduled', [$this, 'handle_reschedule'], 10, 1);
            add_action('nelx_appointment_reminder', [$this, 'handle_reminder'], 10, 1);
        }
    }

    private function load_settings() {
        $this->options = get_option('nelx_jetappt_settings', []);
        $this->enabled = isset($this->options['notifications_enabled']) && $this->options['notifications_enabled'] === '1';
        $this->provider_appointments_page = $this->options['provider_appointments_page'] ?? '';
        $this->client_appointments_page = $this->options['client_appointments_page'] ?? '';
    }

    private function load_central_notification_settings() {
        $option_name = 'nelx_notification_settings';
        $this->notification_settings = get_option($option_name, []);
        
        $cct_slug = $this->notification_settings['cct_slug'] ?? 'crk_all_notifications';
        $site_url = get_site_url();
        $site_url = str_replace('http://', 'https://', $site_url);
        $this->endpoint_url = $site_url . '/wp-json/jet-cct/' . $cct_slug;
        
        $this->cct_recipient_field = $this->notification_settings['cct_recipient_field'] ?? 'user_id';
        $this->cct_user_roles_field = $this->notification_settings['cct_user_roles_field'] ?? 'user_roles';
        $this->cct_type_field = $this->notification_settings['cct_type_field'] ?? 'type';
        $this->cct_message_field = $this->notification_settings['cct_message_field'] ?? 'message';
        $this->cct_url_field = $this->notification_settings['cct_url_field'] ?? 'url';
        $this->cct_status_field = $this->notification_settings['cct_status_field'] ?? 'status';
        $this->cct_sound_played_field = $this->notification_settings['cct_sound_played_field'] ?? 'sound_played';
        
        $this->cct_auth_method = $this->notification_settings['cct_auth_method'] ?? '';
        $this->cct_api_username = $this->notification_settings['cct_api_username'] ?? '';
        $this->cct_application_password = $this->notification_settings['cct_application_password'] ?? '';
    }

    private function decrypt_sensitive_data($data) {
        if (empty($data)) {
            return '';
        }
        
        if (strpos($data, 'base64:') === 0) {
            $encoded = substr($data, 7);
            $decoded = base64_decode($encoded);
            return $decoded !== false ? $decoded : '';
        }
        
        if (strpos($data, 'encrypted:') === 0) {
            $encoded = substr($data, 10);
            $decoded = base64_decode($encoded);
            
            if ($decoded === false) {
                return '';
            }
            
            $iv_length = openssl_cipher_iv_length('AES-256-CBC');
            if (strlen($decoded) <= $iv_length) {
                return '';
            }
            
            $iv = substr($decoded, 0, $iv_length);
            $encrypted = substr($decoded, $iv_length);
            $key = $this->get_encryption_key();
            $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
            
            if ($decrypted === false) {
                return '';
            }
            
            if (strpos($decrypted, 'encrypted:') === 0) {
                return $this->decrypt_sensitive_data($decrypted);
            }
            
            return $decrypted;
        }
        
        return $data;
    }

    private function get_encryption_key() {
        global $wpdb;
        
        $keys = [
            defined('AUTH_KEY') ? AUTH_KEY : '',
            defined('SECURE_AUTH_KEY') ? SECURE_AUTH_KEY : '',
            defined('LOGGED_IN_KEY') ? LOGGED_IN_KEY : '',
            defined('NONCE_KEY') ? NONCE_KEY : '',
            defined('AUTH_SALT') ? AUTH_SALT : '',
            defined('SECURE_AUTH_SALT') ? SECURE_AUTH_SALT : '',
            defined('LOGGED_IN_SALT') ? LOGGED_IN_SALT : '',
            defined('NONCE_SALT') ? NONCE_SALT : ''
        ];
        
        $keys = array_filter($keys);
        
        if (!empty($keys)) {
            $combined = implode('', $keys);
            return hash('sha256', $combined, true);
        }
        
        $fallback_key = 'nelx_' . DB_NAME . '_' . DB_USER;
        return hash('sha256', $fallback_key, true);
    }

    private function create_notification($payload) {
        if (empty($this->endpoint_url)) {
            return false;
        }

        $headers = [
            'Content-Type' => 'application/json',
        ];

        if ($this->cct_auth_method === 'application_password' && !empty($this->cct_application_password)) {
            $decrypted_password = $this->decrypt_sensitive_data($this->cct_application_password);
            
            if (empty($decrypted_password)) {
                return false;
            }
            
            $username = $this->cct_api_username;
            
            if (empty($username)) {
                return false;
            }
            
            if (strpos($decrypted_password, ':') !== false) {
                $parts = explode(':', $decrypted_password, 2);
                if (count($parts) === 2) {
                    $username = $parts[0];
                    $decrypted_password = $parts[1];
                }
            }
            
            $headers['Authorization'] = 'Basic ' . base64_encode($username . ':' . $decrypted_password);
        }

        $response = wp_remote_post($this->endpoint_url, [
            'method' => 'POST',
            'headers' => $headers,
            'body' => wp_json_encode($payload),
            'timeout' => 15,
            'sslverify' => false
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code >= 200 && $response_code < 300) {
            return true;
        } else {
            return false;
        }
    }

    private function build_payload($user_id, $message, $url, $object_id, $event_type = 'appointment') {
        $current_time = current_time('mysql');
        
        $payload = [
            $this->cct_type_field => $event_type,
            $this->cct_message_field => $message,
            $this->cct_status_field => 'unseen',
            $this->cct_sound_played_field => 'no',
            'time' => $current_time,
            $this->cct_recipient_field => strval($user_id)
        ];
        
        if (!empty($url)) {
            $payload[$this->cct_url_field] = $url;
        }
        
        if (!empty($object_id)) {
            $payload['object_id'] = strval($object_id);
        }
        
        return $payload;
    }

    /**
     * Get cached meta value for appointment
     */
    private function get_cached_meta_value($appointment_id, $meta_key) {
        $cache_key = 'nelx_notify_meta_' . $appointment_id . '_' . $meta_key;
        $cached = wp_cache_get($cache_key, 'nelx_notification_meta');
        
        if ($cached !== false) {
            return $cached;
        }
        
        global $wpdb;
        $meta_table = $wpdb->prefix . 'jet_appointments_meta';
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.SlowDBQuery.slow_db_query_meta_key
        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$meta_table} WHERE appointment_id = %d AND meta_key = %s",
            $appointment_id,
            $meta_key
        ));
        
        if ($value !== null) {
            wp_cache_set($cache_key, $value, 'nelx_notification_meta', HOUR_IN_SECONDS);
        }
        
        return $value;
    }

    /**
     * Get cached appointment data
     */
    private function get_cached_appointment($appointment_id) {
        $cache_key = 'nelx_notify_appt_' . $appointment_id;
        $cached = wp_cache_get($cache_key, 'nelx_notification_appointments');
        
        if ($cached !== false) {
            return $cached;
        }
        
        global $wpdb;
        $appt_table = $wpdb->prefix . 'jet_appointments';
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
        $appointment = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$appt_table} WHERE ID = %d", $appointment_id),
            ARRAY_A
        );
        
        if ($appointment) {
            wp_cache_set($cache_key, $appointment, 'nelx_notification_appointments', HOUR_IN_SECONDS);
        }
        
        return $appointment;
    }

    /**
     * Clear cached appointment data
     */
    private function clear_appointment_cache($appointment_id) {
        $cache_key = 'nelx_notify_appt_' . $appointment_id;
        wp_cache_delete($cache_key, 'nelx_notification_appointments');
        
        // Clear meta cache keys
        $meta_keys = ['user_local_date', 'user_local_time', 'rescheduled_by'];
        foreach ($meta_keys as $meta_key) {
            $meta_cache_key = 'nelx_notify_meta_' . $appointment_id . '_' . $meta_key;
            wp_cache_delete($meta_cache_key, 'nelx_notification_meta');
        }
    }

    private function get_client_local_datetime($appointment_id) {
        $local_date = $this->get_cached_meta_value($appointment_id, 'user_local_date');
        $local_time = $this->get_cached_meta_value($appointment_id, 'user_local_time');
        
        if ($local_date && $local_time) {
            return [
                'date' => $local_date,
                'time' => $local_time
            ];
        }
        
        return null;
    }

    private function process_notification_template($content, $appointment, $is_provider = true, $request_data = []) {
        if (empty($content)) {
            return $content;
        }

        $service_title = '';
        if (!empty($appointment['service'])) {
            $service_title = get_the_title((int) $appointment['service']);
            $service_title = html_entity_decode($service_title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $service_title = trim($service_title);
        }

        $provider_name = '';
        if (!empty($appointment['provider'])) {
            $provider_name = get_the_title($appointment['provider']);
        }

        $client_name = $appointment['user_name'] ?? 'Client';

        $appointment_date = '';
        $appointment_time = '';
        
        if ($is_provider) {
            $appointment_date = date_i18n('M j, Y', $appointment['slot']);
            $appointment_time = date_i18n('H:i', $appointment['slot']);
            
            if (!empty($appointment['slot_end']) && $appointment['slot_end'] != $appointment['slot']) {
                $end_time = date_i18n('H:i', $appointment['slot_end']);
                $appointment_time = $appointment_time . ' - ' . $end_time;
            }
        } else {
            $appointment_id = $appointment['ID'] ?? 0;
            $client_local = null;
            
            if ($appointment_id) {
                $client_local = $this->get_client_local_datetime($appointment_id);
            }
            
            if ($client_local && !empty($client_local['date']) && !empty($client_local['time'])) {
                $appointment_date = $client_local['date'];
                $appointment_time = $client_local['time'];
            } else {
                $appointment_date = date_i18n('M j, Y', $appointment['slot']);
                $appointment_time = date_i18n('H:i', $appointment['slot']);
                
                if (!empty($appointment['slot_end']) && $appointment['slot_end'] != $appointment['slot']) {
                    $end_time = date_i18n('H:i', $appointment['slot_end']);
                    $appointment_time = $appointment_time . ' - ' . $end_time;
                }
            }
        }

        $replacements = [
            'service_name' => $service_title ?: 'Consultation',
            'client_name' => $client_name,
            'provider_name' => $provider_name,
            'appointment_date' => $appointment_date,
            'appointment_time' => $appointment_time,
        ];

        foreach ($replacements as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }

        return $content;
    }

    public function handle_new_appointment($appointment_id, $request_data) {
        $appointment = $this->get_cached_appointment($appointment_id);
        if (!$appointment) return;
        
        $appointment['ID'] = $appointment_id;

        $settings = get_option('nelx_jetappt_settings', []);
        $provider_column = $settings['provider_column'] ?? 'staff_id';
        $provider_user_id = $appointment[$provider_column] ?? $appointment['provider'];
        
        $message_template = 'You have a new appointment scheduled by {client_name} for {service_name}: Date: {appointment_date}, Time: {appointment_time}';
        $message = $this->process_notification_template($message_template, $appointment, true, $request_data);

        $payload = $this->build_payload(
            $provider_user_id,
            $message,
            $this->provider_appointments_page,
            $appointment_id,
            'appointment'
        );

        $this->create_notification($payload);
    }

    public function handle_status_change($appointment_id, $old_status, $new_status) {
        $appointment = $this->get_cached_appointment($appointment_id);
        if (!$appointment || $new_status !== 'accepted') return;
        
        $appointment['ID'] = $appointment_id;
        $client_user_id = $appointment['user_id'] ?? 0;
        
        if (!$client_user_id) return;

        $message_template = 'Your appointment has been confirmed. Service: {service_name}. Date: {appointment_date} Time: {appointment_time}';
        $message = $this->process_notification_template($message_template, $appointment, false);

        $payload = $this->build_payload(
            $client_user_id,
            $message,
            $this->client_appointments_page,
            $appointment_id,
            'appointment'
        );

        $this->create_notification($payload);
    }

    public function handle_cancellation($appointment_id, $initiator) {
        $appointment = $this->get_cached_appointment($appointment_id);
        if (!$appointment) return;
        
        $appointment['ID'] = $appointment_id;

        $settings = get_option('nelx_jetappt_settings', []);
        $provider_column = $settings['provider_column'] ?? 'staff_id';
        $provider_user_id = $appointment[$provider_column] ?? $appointment['provider'];
        $client_user_id = $appointment['user_id'] ?? 0;

        if ($initiator === 'client') {
            $message_template = 'Appointment Canceled by Client. Client: {client_name}. Service: {service_name} Date: {appointment_date} Time: {appointment_time}';
            $message = $this->process_notification_template($message_template, $appointment, true);
            
            $payload = $this->build_payload(
                $provider_user_id,
                $message,
                $this->provider_appointments_page,
                $appointment_id,
                'appointment'
            );
            
            $this->create_notification($payload);
        } else {
            if ($client_user_id) {
                $message_template = 'Your Appointment with {provider_name} Has Been Canceled. Service: {service_name} Date: {appointment_date} Time: {appointment_time}';
                $message = $this->process_notification_template($message_template, $appointment, false);
                
                $payload = $this->build_payload(
                    $client_user_id,
                    $message,
                    $this->client_appointments_page,
                    $appointment_id,
                    'appointment'
                );
                
                $this->create_notification($payload);
            }
        }
    }

    public function handle_reschedule($appointment_id) {
        $appointment = $this->get_cached_appointment($appointment_id);
        if (!$appointment) return;
        
        $appointment['ID'] = $appointment_id;

        $settings = get_option('nelx_jetappt_settings', []);
        $provider_column = $settings['provider_column'] ?? 'staff_id';
        $provider_user_id = $appointment[$provider_column] ?? $appointment['provider'];
        $client_user_id = $appointment['user_id'] ?? 0;

        $current_user_id = get_current_user_id();
        $is_provider_rescheduling = ($current_user_id && $provider_user_id && $current_user_id == $provider_user_id);
        $is_client_rescheduling = ($current_user_id && $client_user_id && $current_user_id == $client_user_id);
        
        $rescheduled_by = $this->get_reschedule_initiator($appointment_id);
        
        if ($rescheduled_by === 'provider' || ($is_provider_rescheduling && !$rescheduled_by)) {
            if ($client_user_id) {
                $message_template = 'Your Appointment with {provider_name} Has Been Rescheduled. Service: {service_name} New Date: {appointment_date} New Time: {appointment_time}';
                $message = $this->process_notification_template($message_template, $appointment, false);
                
                $payload = $this->build_payload(
                    $client_user_id,
                    $message,
                    $this->client_appointments_page,
                    $appointment_id,
                    'appointment'
                );
                
                $this->create_notification($payload);
            }
        } elseif ($rescheduled_by === 'client' || ($is_client_rescheduling && !$rescheduled_by)) {
            if ($provider_user_id) {
                $message_template = 'Appointment Rescheduled by Client. Client: {client_name}. Service: {service_name} New Date: {appointment_date} New Time: {appointment_time}';
                $message = $this->process_notification_template($message_template, $appointment, true);
                
                $payload = $this->build_payload(
                    $provider_user_id,
                    $message,
                    $this->provider_appointments_page,
                    $appointment_id,
                    'appointment'
                );
                
                $this->create_notification($payload);
            }
        }
    }
    
    private function get_reschedule_initiator($appointment_id) {
        $initiator = $this->get_cached_meta_value($appointment_id, 'rescheduled_by');
        return $initiator;
    }

    public function handle_reminder($appointment_id) {
        $appointment = $this->get_cached_appointment($appointment_id);
        if (!$appointment) return;
        
        $appointment['ID'] = $appointment_id;

        $settings = get_option('nelx_jetappt_settings', []);
        $provider_column = $settings['provider_column'] ?? 'staff_id';
        $provider_user_id = $appointment[$provider_column] ?? $appointment['provider'];
        $client_user_id = $appointment['user_id'] ?? 0;

        if ($client_user_id) {
            $message_template = 'This is a reminder for your upcoming appointment. Date: {appointment_date} Time: {appointment_time}';
            $message = $this->process_notification_template($message_template, $appointment, false);
            
            $payload = $this->build_payload(
                $client_user_id,
                $message,
                $this->client_appointments_page,
                $appointment_id,
                'appointment'
            );
            
            $this->create_notification($payload);
        }

        if ($provider_user_id) {
            $message_template = 'Reminder: Upcoming appointment with {client_name} for {service_name} on {appointment_date} at {appointment_time}';
            $message = $this->process_notification_template($message_template, $appointment, true);
            
            $payload = $this->build_payload(
                $provider_user_id,
                $message,
                $this->provider_appointments_page,
                $appointment_id,
                'appointment'
            );
            
            $this->create_notification($payload);
        }
    }
    
    public function send_reminder_notification($appointment) {
        if (empty($appointment)) {
            return false;
        }
        
        if (!isset($appointment['ID']) && isset($appointment['id'])) {
            $appointment['ID'] = $appointment['id'];
        }
        
        $settings = get_option('nelx_jetappt_settings', []);
        $provider_column = $settings['provider_column'] ?? 'staff_id';
        $provider_user_id = $appointment[$provider_column] ?? $appointment['provider'] ?? 0;
        $client_user_id = $appointment['user_id'] ?? 0;
        
        $sent_count = 0;
        
        if ($client_user_id) {
            $message_template = 'Reminder: You have an upcoming appointment on {appointment_date} at {appointment_time}';
            $message = $this->process_notification_template($message_template, $appointment, false);
            
            $payload = $this->build_payload(
                $client_user_id,
                $message,
                $this->client_appointments_page,
                $appointment['ID'] ?? null,
                'appointment'
            );
            
            if ($this->create_notification($payload)) {
                $sent_count++;
            }
        }
        
        if ($provider_user_id) {
            $message_template = 'Reminder: Upcoming appointment with {client_name} for {service_name} on {appointment_date} at {appointment_time}';
            $message = $this->process_notification_template($message_template, $appointment, true);
            
            $payload = $this->build_payload(
                $provider_user_id,
                $message,
                $this->provider_appointments_page,
                $appointment['ID'] ?? null,
                'appointment'
            );
            
            if ($this->create_notification($payload)) {
                $sent_count++;
            }
        }
        
        return $sent_count > 0;
    }
}

NELXJAF_Appointment_Notifications::instance();
