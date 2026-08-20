<?php
/**
 * Google Meet Integration Class
 */

if (!defined('ABSPATH')) exit;

class NELXJAF_Google_Meet_Integration {
    private static $instance = null;
    private $wpdb;
    private $appt_table;
    private $appt_meta_table;
    private $provider_column;
    private $is_shortcode_present = false;
    private $assets_enqueued = false;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        $settings = get_option('nelx_jetappt_settings', [
            'provider_column' => 'staff_id',
        ]);
        
        $this->appt_table = $wpdb->prefix . 'jet_appointments';
        $this->appt_meta_table = $wpdb->prefix . 'jet_appointments_meta';
        $this->provider_column = $settings['provider_column'] ?? 'staff_id';
        
        add_action('init', [$this, 'init']);
    }

    public function init() {
        add_shortcode('nelx_google_meet_settings', [$this, 'provider_settings_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets'], 20);
        add_action('wp_footer', [$this, 'maybe_enqueue_assets'], 1);
    }

    /**
     * Check if the shortcode is present in content
     */
    public function check_shortcode_presence() {
        global $post, $wp_query;
        
        // Check if we're on a page that might contain the shortcode
        if (is_singular() && isset($post)) {
            // Check post content
            if (has_shortcode($post->post_content, 'nelx_google_meet_settings')) {
                $this->is_shortcode_present = true;
                return;
            }
            
            // Check if page builder content contains the shortcode
            $content = $post->post_content;
            
            // Check for Elementor widget by scanning for the widget name
            if (strpos($content, 'widget-nelx_google_meet_settings') !== false || 
                strpos($content, 'nelx_google_meet_settings') !== false) {
                $this->is_shortcode_present = true;
                return;
            }
        }
        
        // Check if we're in Elementor preview or editor
        if (isset($_GET['elementor-preview']) || isset($_GET['action']) && $_GET['action'] === 'elementor') {
            $this->is_shortcode_present = true;
            return;
        }
        
        // Check if the current page is being rendered via Elementor
        if (defined('ELEMENTOR_VERSION') && \Elementor\Plugin::$instance->editor->is_edit_mode()) {
            $this->is_shortcode_present = true;
            return;
        }
    }

    public function enqueue_assets() {
        // Don't enqueue on admin pages except Elementor editor
        if (is_admin() && !$this->is_elementor_editor()) {
            return;
        }
        
        // Check if shortcode is present on the page
        $this->check_shortcode_presence();
        
        // Only enqueue if shortcode is present
        if ($this->is_shortcode_present && !$this->assets_enqueued) {
            $this->do_enqueue_assets();
        }
    }

    /**
     * Check if Elementor editor is active
     */
    private function is_elementor_editor() {
        if (!defined('ELEMENTOR_VERSION')) {
            return false;
        }
        
        if (isset($_GET['action']) && $_GET['action'] === 'elementor') {
            return true;
        }
        
        if (isset($_GET['elementor-preview'])) {
            return true;
        }
        
        if (isset($_POST['action']) && $_POST['action'] === 'elementor_ajax') {
            return true;
        }
        
        return false;
    }

    /**
     * Fallback: Check in footer if assets need to be loaded
     * (Useful for dynamically loaded content)
     */
    public function maybe_enqueue_assets() {
        global $wp_scripts, $wp_styles;
        
        // If assets were already enqueued, we're done
        if ($this->assets_enqueued) {
            return;
        }
        
        // Check if the shortcode was rendered in footer or dynamic content
        $this->check_shortcode_presence();
        
        if ($this->is_shortcode_present && !$this->assets_enqueued) {
            $this->do_enqueue_assets();
        }
    }

    /**
     * Actually enqueue the assets
     */
    private function do_enqueue_assets() {
        if ($this->assets_enqueued) {
            return;
        }
        
        $plugin_dir = NELXJAF_PLUGIN_DIR;
        $plugin_url = NELXJAF_PLUGIN_URL;
        
        // Enqueue CSS
        $css_file = $plugin_dir . 'assets/css/nelx-google-meet.min.css';
        if (file_exists($css_file)) {
            wp_enqueue_style(
                'nelx-google-meet',
                $plugin_url . 'assets/css/nelx-google-meet.min.css',
                [],
                filemtime($css_file)
            );
        }
        
        // Enqueue JS
        $js_file = $plugin_dir . 'assets/js/nelx-google-meet.min.js';
        if (file_exists($js_file)) {
            wp_enqueue_script(
                'nelx-google-meet',
                $plugin_url . 'assets/js/nelx-google-meet.min.js',
                ['jquery'],
                filemtime($js_file),
                true
            );
            wp_localize_script('nelx-google-meet', 'nelxGoogleMeet', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('nelx_google_meet_nonce')
            ]);
        }
        
        $this->assets_enqueued = true;
    }

    public function provider_settings_shortcode($atts) {
        // Mark that shortcode is present
        $this->is_shortcode_present = true;
        
        if (!is_user_logged_in()) {
            return '<div class="nelx-notice">' . esc_html__('Login required to configure Google Meet.', 'nelx-jetappt-frontend') . '</div>';
        }
        
        $user_id = get_current_user_id();
        $provider_email = get_user_meta($user_id, 'nelx_google_meet_email', true);
        $is_connected = get_user_meta($user_id, 'nelx_google_meet_access_token', true);
        
        ob_start();
        ?>
        <div class="nelx-google-meet-settings">
            <h3><?php esc_html_e('Google Meet Settings', 'nelx-jetappt-frontend'); ?></h3>
            <div class="nelx-field">
                <label for="nelx_google_meet_email"><?php esc_html_e('Google Calendar Email', 'nelx-jetappt-frontend'); ?></label>
                <input type="email" id="nelx_google_meet_email" 
                       value="<?php echo esc_attr($provider_email); ?>" 
                       placeholder="<?php esc_attr_e('your-email@gmail.com', 'nelx-jetappt-frontend'); ?>">
                <p class="description"><?php esc_html_e('This email will be used to add the meeting to your Google Calendar. Invitations will be sent to both you and the client.', 'nelx-jetappt-frontend'); ?></p>
            </div>
            <div class="nelx-actions">
                <?php if ($is_connected) : ?>
                    <button type="button" class="nelx-btn nelx-danger" id="nelx_disconnect_google">
                        <?php esc_html_e('Disconnect Google', 'nelx-jetappt-frontend'); ?>
                    </button>
                    <span class="nelx-status nelx-success"><?php esc_html_e('Connected to Google', 'nelx-jetappt-frontend'); ?></span>
                <?php else : ?>
                    <a href="<?php echo esc_url($this->get_google_auth_url()); ?>" class="nelx-btn nelx-primary" id="nelx_connect_google">
                        <?php esc_html_e('Connect Google Account', 'nelx-jetappt-frontend'); ?>
                    </a>
                    <span class="nelx-status nelx-warning"><?php esc_html_e('Not connected to Google', 'nelx-jetappt-frontend'); ?></span>
                <?php endif; ?>
            </div>
            <div class="nelx-message" style="display: none;"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function create_google_meet($provider_id, $appointment, $provider_email, $event_title = '', $request_data = null) {
        $access_token  = get_user_meta($provider_id, 'nelx_google_meet_access_token', true);
        $refresh_token = get_user_meta($provider_id, 'nelx_google_meet_refresh_token', true);
        $options       = get_option('nelx_google_meet_settings', []);
    
        $raw_slot     = isset($appointment['slot']) ? (int) $appointment['slot'] : 0;
        $raw_slot_end = isset($appointment['slot_end']) ? (int) $appointment['slot_end'] : 0;
    
        if (!$raw_slot || !$raw_slot_end) {
            return false;
        }
    
        $tz_string = wp_timezone_string() ?: 'UTC';
        $tz = new DateTimeZone($tz_string);
    
        $time_digits_start = gmdate('Y-m-d\TH:i:s', $raw_slot);
        $time_digits_end   = gmdate('Y-m-d\TH:i:s', $raw_slot_end);
    
        $offset_dt_start = new DateTime('@' . $raw_slot);
        $offset_dt_start->setTimezone($tz);
        $offset_start = $offset_dt_start->format('P');
    
        $offset_dt_end = new DateTime('@' . $raw_slot_end);
        $offset_dt_end->setTimezone($tz);
        $offset_end = $offset_dt_end->format('P');
    
        $start_time = $time_digits_start . $offset_start;
        $end_time   = $time_digits_end   . $offset_end;
        
        if (!empty($event_title)) {
            $event_summary = $event_title;
        } else {
            $service_title = '';
            if (!empty($appointment['service'])) {
                $service_title = get_the_title((int) $appointment['service']);
                $service_title = html_entity_decode($service_title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $service_title = trim($service_title);
            }
            $event_summary = 'Service: ' . ($service_title ?: __('Consultation', 'nelx-jetappt-frontend'));
        }
    
        $event_data = [
            'summary' => $event_summary,
            'description' => __('Appointment with', 'nelx-jetappt-frontend') . ' ' . ($appointment['user_name'] ?? __('Client', 'nelx-jetappt-frontend')),
            'start' => [
                'dateTime' => $start_time
            ],
            'end' => [
                'dateTime' => $end_time
            ],
            'conferenceData' => [
                'createRequest' => [
                    'requestId' => 'appointment-' . ($appointment['ID'] ?? uniqid()),
                    'conferenceSolutionKey' => ['type' => 'hangoutsMeet']
                ]
            ],
            'attendees' => [
                ['email' => $provider_email],
                ['email' => $appointment['user_email'] ?? '']
            ],
        ];
    
        $response = wp_remote_post('https://www.googleapis.com/calendar/v3/calendars/primary/events?conferenceDataVersion=1', [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json',
            ],
            'body'    => json_encode($event_data),
            'timeout' => 30
        ]);
    
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) === 401) {
            if ($refresh_token) {
                $refresh_response = wp_remote_post('https://oauth2.googleapis.com/token', [
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                    'body' => [
                        'refresh_token' => $refresh_token,
                        'client_id'     => $options['google_client_id'] ?? '',
                        'client_secret' => $options['google_client_secret'] ?? '',
                        'grant_type'    => 'refresh_token'
                    ]
                ]);
    
                if (is_wp_error($refresh_response)) {
                    return false;
                }
    
                $refresh_body = json_decode(wp_remote_retrieve_body($refresh_response), true);
                if (isset($refresh_body['access_token'])) {
                    update_user_meta($provider_id, 'nelx_google_meet_access_token', $refresh_body['access_token']);
                    $access_token = $refresh_body['access_token'];
    
                    $response = wp_remote_post('https://www.googleapis.com/calendar/v3/calendars/primary/events?conferenceDataVersion=1', [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $access_token,
                            'Content-Type'  => 'application/json',
                        ],
                        'body'    => json_encode($event_data),
                        'timeout' => 30
                    ]);
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
    
        if (is_wp_error($response)) {
            return false;
        }
    
        $body = json_decode(wp_remote_retrieve_body($response), true);
    
        if (!empty($body['hangoutLink'])) {
            if (!empty($appointment['ID'])) {
                update_post_meta($appointment['ID'], 'google_meet_url', esc_url_raw($body['hangoutLink']));
            }
            return $body['hangoutLink'];
        }
    
        return false;
    }

    private function get_google_auth_url() {
        $options = get_option('nelx_google_meet_settings', []);
        $client_id = $options['google_client_id'] ?? '';
        
        if (!$client_id) {
            return '#';
        }
        
        $redirect_uri = admin_url('admin-ajax.php?action=google_meet_auth');
        
        $state = wp_generate_password(32, false);
        set_transient('nelx_google_oauth_state_' . get_current_user_id(), $state, 600);
        
        $scopes = ['https://www.googleapis.com/auth/calendar.events'];
        $scope_string = implode(' ', $scopes);
        
        return "https://accounts.google.com/o/oauth2/auth?" . http_build_query([
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'response_type' => 'code',
            'scope' => $scope_string,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state 
        ]);
    }
}

NELXJAF_Google_Meet_Integration::instance();

add_action('wp_ajax_google_meet_auth', 'nelxjaf_handle_google_auth');
add_action('wp_ajax_nopriv_google_meet_auth', 'nelxjaf_handle_google_auth');
function nelxjaf_handle_google_auth() {
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_die(esc_html__('You must be logged in to connect Google account.', 'nelx-jetappt-frontend'));
    }
    
    $expected_state = get_transient('nelx_google_oauth_state_' . $user_id);
    
    if (!$expected_state) {
        wp_die(esc_html__('OAuth session expired. Please try again.', 'nelx-jetappt-frontend'));
    }
    
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if (!isset($_GET['state'])) {
        wp_die(esc_html__('Security check failed - Missing state parameter.', 'nelx-jetappt-frontend'));
    }
    
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
    $received_state = sanitize_text_field(wp_unslash($_GET['state']));
    if (!hash_equals($expected_state, $received_state)) {
        wp_die(esc_html__('Security check failed - Invalid state parameter.', 'nelx-jetappt-frontend'));
    }
    
    delete_transient('nelx_google_oauth_state_' . $user_id);
    
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if (!isset($_GET['code'])) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_GET['error'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
            $error = sanitize_text_field(wp_unslash($_GET['error']));
            /* translators: %s: error code from Google */
            wp_die(sprintf(esc_html__('Authorization failed: %s', 'nelx-jetappt-frontend'), esc_html($error)));
        }
        wp_die(esc_html__('Invalid request - Missing authorization code.', 'nelx-jetappt-frontend'));
    }
    
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
    $code = sanitize_text_field(wp_unslash($_GET['code']));
    $options = get_option('nelx_google_meet_settings', []);
    $client_id = $options['google_client_id'] ?? '';
    $client_secret = $options['google_client_secret'] ?? '';
    
    if (empty($client_id) || empty($client_secret)) {
        wp_die(esc_html__('Google API credentials not configured. Please contact site administrator.', 'nelx-jetappt-frontend'));
    }
    
    $redirect_uri = admin_url('admin-ajax.php?action=google_meet_auth');
    
    $response = wp_remote_post('https://oauth2.googleapis.com/token', [
        'headers' => [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ],
        'body' => [
            'code' => $code,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri' => $redirect_uri,
            'grant_type' => 'authorization_code'
        ],
        'timeout' => 30
    ]);
    
    if (is_wp_error($response)) {
        /* translators: %s: error message */
        wp_die(sprintf(esc_html__('Authentication request failed: %s', 'nelx-jetappt-frontend'), esc_html($response->get_error_message())));
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        /* translators: %d: HTTP response code */
        wp_die(sprintf(esc_html__('Authentication failed with HTTP code: %d', 'nelx-jetappt-frontend'), intval($response_code)));
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if (isset($body['access_token'])) {
        update_user_meta($user_id, 'nelx_google_meet_access_token', $body['access_token']);
        if (isset($body['refresh_token'])) {
            update_user_meta($user_id, 'nelx_google_meet_refresh_token', $body['refresh_token']);
        }
        
        if (isset($body['expires_in'])) {
            $expires_in = intval($body['expires_in']);
            $expiry_time = time() + $expires_in;
            update_user_meta($user_id, 'nelx_google_meet_token_expiry', $expiry_time);
        }
        
        $redirect_url = add_query_arg('google_auth', 'success', home_url('/'));
        wp_safe_redirect($redirect_url);
        exit;
    } else {
        $error_msg = isset($body['error_description']) ? $body['error_description'] : (isset($body['error']) ? $body['error'] : __('Unknown error', 'nelx-jetappt-frontend'));
        /* translators: %s: error description */
        wp_die(sprintf(esc_html__('Authentication failed: %s', 'nelx-jetappt-frontend'), esc_html($error_msg)));
    }
}

add_action('wp_ajax_save_google_meet_email', 'nelxjaf_save_google_meet_email');
function nelxjaf_save_google_meet_email() {
    if (!check_ajax_referer('nelx_google_meet_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => __('Security check failed.', 'nelx-jetappt-frontend')]);
    }
    
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    $user_id = get_current_user_id();
    
    if ($email && is_email($email)) {
        update_user_meta($user_id, 'nelx_google_meet_email', $email);
        wp_send_json_success([
            'message' => __('Email saved successfully', 'nelx-jetappt-frontend'),
            'description' => __('This email will be used to add the meeting to your Google Calendar. Invitations will be sent to both you and the client.', 'nelx-jetappt-frontend')
        ]);
    }
    
    wp_send_json_error(['message' => __('Invalid email address', 'nelx-jetappt-frontend')]);
}

add_action('wp_ajax_disconnect_google', 'nelxjaf_disconnect_google');
function nelxjaf_disconnect_google() {
    if (!check_ajax_referer('nelx_google_meet_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => __('Security check failed.', 'nelx-jetappt-frontend')]);
    }
    
    $user_id = get_current_user_id();
    delete_user_meta($user_id, 'nelx_google_meet_access_token');
    delete_user_meta($user_id, 'nelx_google_meet_refresh_token');
    delete_user_meta($user_id, 'nelx_google_meet_email');
    
    wp_send_json_success(['message' => __('Disconnected from Google', 'nelx-jetappt-frontend')]);
}
