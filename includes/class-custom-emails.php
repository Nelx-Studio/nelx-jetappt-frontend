<?php
if (!defined('ABSPATH')) exit;

class NELXJAF_Custom_Emails {
    private static $instance = null;
    private $timezone_helper = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        if (class_exists('NELXJAF_Timezone_Helper')) {
            $this->timezone_helper = NELXJAF_Timezone_Helper::instance();
        }
        
        add_action('nelx_appointment_status_changed', [$this, 'send_status_email'], 10, 3);
        add_action('nelx_appointment_canceled', [$this, 'send_cancellation_email'], 10, 2);
        add_action('nelx_appointment_rescheduled', [$this, 'send_reschedule_email'], 10, 1);
        
        add_action('wp_ajax_nelx_send_appointment_reminders', [$this, 'handle_ajax_reminders']);
        add_action('wp_ajax_nopriv_nelx_send_appointment_reminders', [$this, 'handle_ajax_reminders']);
        
        // Clear cache when appointments are updated
        add_action('nelx_appointment_status_changed', [$this, 'clear_appointment_cache'], 10, 3);
        add_action('nelx_appointment_canceled', [$this, 'clear_appointment_cache'], 10, 2);
        add_action('nelx_appointment_rescheduled', [$this, 'clear_appointment_cache'], 10, 1);
        add_action('wp_insert_post', [$this, 'clear_appointment_cache_on_post_save'], 10, 3);
    }

    /**
     * Get cached appointment or fetch from database
     */
    private function get_cached_appointment($appointment_id) {
        $cache_key = 'nelx_appointment_' . $appointment_id;
        $cached = wp_cache_get($cache_key, 'nelx_appointments');
        
        if ($cached !== false) {
            return $cached;
        }
        
        global $wpdb;
        $core = NELXJAF_Core::instance();
        $appt_table = $core->appt_table;
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $appointment = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$appt_table} WHERE ID = %d", $appointment_id),
            ARRAY_A
        );
        
        if ($appointment) {
            wp_cache_set($cache_key, $appointment, 'nelx_appointments', HOUR_IN_SECONDS);
        }
        
        return $appointment;
    }

    /**
     * Get cached meta value or fetch from database
     */
    private function get_cached_meta_value($appointment_id, $meta_key) {
        $cache_key = 'nelx_meta_' . $appointment_id . '_' . $meta_key;
        $cached = wp_cache_get($cache_key, 'nelx_appointment_meta');
        
        if ($cached !== false) {
            return $cached;
        }
        
        global $wpdb;
        $core = NELXJAF_Core::instance();
        $meta_table = $core->appt_meta_table;
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $value = $wpdb->get_var(
            $wpdb->prepare("SELECT meta_value FROM {$meta_table} WHERE appointment_id = %d AND meta_key = %s", $appointment_id, $meta_key)
        );
        
        if ($value !== null) {
            wp_cache_set($cache_key, $value, 'nelx_appointment_meta', HOUR_IN_SECONDS);
        }
        
        return $value;
    }

    /**
     * Clear cache for an appointment
     */
    public function clear_appointment_cache($appointment_id, ...$args) {
        $cache_key = 'nelx_appointment_' . $appointment_id;
        wp_cache_delete($cache_key, 'nelx_appointments');
        
        // Also clear meta cache keys (we don't know which meta keys were updated)
        wp_cache_delete('nelx_meta_' . $appointment_id . '_user_local_date', 'nelx_appointment_meta');
        wp_cache_delete('nelx_meta_' . $appointment_id . '_user_local_time', 'nelx_appointment_meta');
        wp_cache_delete('nelx_meta_' . $appointment_id . '_reminder_sent', 'nelx_appointment_meta');
        wp_cache_delete('nelx_meta_' . $appointment_id . '_rescheduled_by', 'nelx_appointment_meta');
        
        // Clear any aggregated counts
        wp_cache_delete('nelx_appointments_count', 'nelx_appointments');
        wp_cache_delete('nelx_past_appointments_count', 'nelx_appointments');
        wp_cache_delete('nelx_canceled_appointments_count', 'nelx_appointments');
    }

    /**
     * Clear cache when a post is saved (for jet-appointment post type)
     */
    public function clear_appointment_cache_on_post_save($post_id, $post, $update) {
        if ($post->post_type === 'jet-appointment') {
            $this->clear_appointment_cache($post_id);
        }
    }

    public function get_email_templates() {
        $settings = get_option('nelx_jetappt_settings', []);
        
        if (!empty($settings['default_email_templates'])) {
            return $settings['default_email_templates'];
        }
        
        return $this->get_default_templates_from_ui();
    }
    
    public function get_custom_email_templates() {
        $settings = get_option('nelx_jetappt_settings', []);
        return $settings['custom_email_templates'] ?? [];
    }
    
    public function get_default_templates() {
        return $this->get_default_templates_from_ui();
    }

    public function get_default_templates_from_ui() {
        $home_url_host = wp_parse_url(home_url(), PHP_URL_HOST);
        $noreply_from = get_bloginfo('name') . ' <noreply@' . $home_url_host . '>';
        
        if (class_exists('NELXJAF_Settings_Page')) {
            $settings_page = NELXJAF_Settings_Page::instance();
            
            return [
                [
                    'name' => 'New Appointment - Provider',
                    'email_settings' => [
                        'to' => '{provider_email}',
                        'cc' => '',
                        'bcc' => '',
                        'subject' => __('New Appointment Scheduled', 'nelx-jetappt-frontend'),
                        'message' => $settings_page->get_template_new_appointment_provider(),
                        'from' => $noreply_from
                    ]
                ],
                [
                    'name' => 'Appointment Confirmation - Online',
                    'email_settings' => [
                        'to' => '{client_email}',
                        'cc' => '',
                        'bcc' => '',
                        'subject' => __('Your Appointment Has Been Confirmed', 'nelx-jetappt-frontend'),
                        'message' => $settings_page->get_template_confirmation_online(),
                        'from' => $noreply_from
                    ]
                ],
                [
                    'name' => 'Appointment Confirmation - Physical',
                    'email_settings' => [
                        'to' => '{client_email}',
                        'cc' => '',
                        'bcc' => '',
                        'subject' => __('Your Appointment Has Been Confirmed', 'nelx-jetappt-frontend'),
                        'message' => $settings_page->get_template_confirmation_physical(),
                        'from' => $noreply_from
                    ]
                ],
                [
                    'name' => 'Appointment Canceled - Provider',
                    'email_settings' => [
                        'to' => '{provider_email}',
                        'cc' => '',
                        'bcc' => '',
                        'subject' => __('Appointment Canceled by Client', 'nelx-jetappt-frontend'),
                        'message' => $settings_page->get_template_canceled_provider(),
                        'from' => $noreply_from
                    ]
                ],
                [
                    'name' => 'Appointment Canceled - Client',
                    'email_settings' => [
                        'to' => '{client_email}',
                        'cc' => '',
                        'bcc' => '',
                        'subject' => __('Your Appointment Has Been Canceled', 'nelx-jetappt-frontend'),
                        'message' => $settings_page->get_template_canceled_client(),
                        'from' => $noreply_from
                    ]
                ],
                [
                    'name' => 'Appointment Rescheduled - Client',
                    'email_settings' => [
                        'to' => '{client_email}',
                        'cc' => '',
                        'bcc' => '',
                        'subject' => __('Your Appointment Has Been Rescheduled', 'nelx-jetappt-frontend'),
                        'message' => $settings_page->get_template_rescheduled_client(),
                        'from' => $noreply_from
                    ]
                ],
                [
                    'name' => 'Appointment Rescheduled - Provider',
                    'email_settings' => [
                        'to' => '{provider_email}',
                        'cc' => '',
                        'bcc' => '',
                        'subject' => __('Appointment Rescheduled', 'nelx-jetappt-frontend'),
                        'message' => $settings_page->get_template_rescheduled_provider(),
                        'from' => $noreply_from
                    ]
                ],
                [
                    'name' => 'Appointment Reminder',
                    'email_settings' => [
                        'to' => '{client_email}',
                        'cc' => '',
                        'bcc' => '',
                        'subject' => __('Appointment Reminder', 'nelx-jetappt-frontend'),
                        'message' => $settings_page->get_template_reminder(),
                        'from' => $noreply_from
                    ]
                ]
            ];
        }
        
        return $this->get_fallback_templates();
    }
    
    private function get_fallback_templates() {
        $home_url_host = wp_parse_url(home_url(), PHP_URL_HOST);
        $noreply_from = get_bloginfo('name') . ' <noreply@' . $home_url_host . '>';
        
        return [
            [
                'name' => 'New Appointment - Provider',
                'email_settings' => [
                    'to' => '{provider_email}',
                    'cc' => '',
                    'bcc' => '',
                    'subject' => __('New Appointment Scheduled', 'nelx-jetappt-frontend'),
                    'message' => __('A new appointment has been scheduled. Service: {service_name}, Client: {client_name}, Date: {appointment_date}, Time: {appointment_time}', 'nelx-jetappt-frontend'),
                    'from' => $noreply_from
                ]
            ],
            [
                'name' => 'Appointment Confirmation - Online',
                'email_settings' => [
                    'to' => '{client_email}',
                    'cc' => '',
                    'bcc' => '',
                    'subject' => __('Your Appointment Has Been Confirmed', 'nelx-jetappt-frontend'),
                    'message' => __('Dear {client_name}, your online appointment has been confirmed. Service: {service_name}, Date: {appointment_date}, Time: {appointment_time}, Meeting Link: {meeting_url}', 'nelx-jetappt-frontend'),
                    'from' => $noreply_from
                ]
            ],
            [
                'name' => 'Appointment Confirmation - Physical',
                'email_settings' => [
                    'to' => '{client_email}',
                    'cc' => '',
                    'bcc' => '',
                    'subject' => __('Your Appointment Has Been Confirmed', 'nelx-jetappt-frontend'),
                    'message' => __('Dear {client_name}, your appointment has been confirmed. Service: {service_name}, Date: {appointment_date}, Time: {appointment_time}', 'nelx-jetappt-frontend'),
                    'from' => $noreply_from
                ]
            ],
            [
                'name' => 'Appointment Canceled - Provider',
                'email_settings' => [
                    'to' => '{provider_email}',
                    'cc' => '',
                    'bcc' => '',
                    'subject' => __('Appointment Canceled by Client', 'nelx-jetappt-frontend'),
                    'message' => __('A client has canceled their appointment. Service: {service_name}, Client: {client_name}, Date: {appointment_date}, Time: {appointment_time}', 'nelx-jetappt-frontend'),
                    'from' => $noreply_from
                ]
            ],
            [
                'name' => 'Appointment Canceled - Client',
                'email_settings' => [
                    'to' => '{client_email}',
                    'cc' => '',
                    'bcc' => '',
                    'subject' => __('Your Appointment Has Been Canceled', 'nelx-jetappt-frontend'),
                    'message' => __('Your appointment has been canceled. Service: {service_name}, Date: {appointment_date}, Time: {appointment_time}', 'nelx-jetappt-frontend'),
                    'from' => $noreply_from
                ]
            ],
            [
                'name' => 'Appointment Rescheduled - Client',
                'email_settings' => [
                    'to' => '{client_email}',
                    'cc' => '',
                    'bcc' => '',
                    'subject' => __('Your Appointment Has Been Rescheduled', 'nelx-jetappt-frontend'),
                    'message' => __('Your appointment has been rescheduled. Service: {service_name}, Date: {appointment_date}, Time: {appointment_time}', 'nelx-jetappt-frontend'),
                    'from' => $noreply_from
                ]
            ],
            [
                'name' => 'Appointment Rescheduled - Provider',
                'email_settings' => [
                    'to' => '{provider_email}',
                    'cc' => '',
                    'bcc' => '',
                    'subject' => __('Appointment Rescheduled', 'nelx-jetappt-frontend'),
                    'message' => __('An appointment has been rescheduled. Service: {service_name}, Client: {client_name}, Date: {appointment_date}, Time: {appointment_time}', 'nelx-jetappt-frontend'),
                    'from' => $noreply_from
                ]
            ],
            [
                'name' => 'Appointment Reminder',
                'email_settings' => [
                    'to' => '{client_email}',
                    'cc' => '',
                    'bcc' => '',
                    'subject' => __('Appointment Reminder', 'nelx-jetappt-frontend'),
                    'message' => __('This is a reminder for your upcoming appointment. Service: {service_name}, Date: {appointment_date}, Time: {appointment_time}', 'nelx-jetappt-frontend'),
                    'from' => $noreply_from
                ]
            ]
        ];
    }

    public function parse_recipients($recipient_string, $appointment = [], $request_data = []) {
        if (empty($recipient_string)) {
            return [];
        }
        
        if (!empty($appointment)) {
            $recipient_string = $this->replace_placeholders($recipient_string, $appointment);
        }
        if (!empty($request_data)) {
            $recipient_string = $this->replace_custom_placeholders($recipient_string, $request_data);
        }
        
        $recipients = array_map('trim', explode(',', $recipient_string));
        $email_addresses = [];
        
        foreach ($recipients as $recipient) {
            if (empty($recipient)) continue;
            
            if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                $email_addresses[] = $recipient;
            } 
            else if (is_numeric($recipient) && $recipient > 0) {
                $user = get_user_by('ID', intval($recipient));
                if ($user && !empty($user->user_email)) {
                    $email_addresses[] = $user->user_email;
                }
            }
            else if (strpos($recipient, '@') === false && is_numeric(trim($recipient, '{}'))) {
                $user_id = intval(trim($recipient, '{}'));
                $user = get_user_by('ID', $user_id);
                if ($user && !empty($user->user_email)) {
                    $email_addresses[] = $user->user_email;
                }
            }
        }
        
        return array_unique($email_addresses);
    }

    public static function nelx_get_email_header() {
        $settings = get_option('nelx_email_branding_settings');
        $logo_url = $settings['email_logo_url'] ?? '';
        $logo_alignment = $settings['email_logo_alignment'] ?? 'center';
        $heading_color = $settings['email_heading_color'] ?? '#1E3A8A';
        $link_color = $settings['email_link_color'] ?? '#1E3A8A';
        $button_color = $settings['email_button_color'] ?? '#1E3A8A';
        $button_hover_color = $settings['email_button_hover_color'] ?? '#1a3a47';
        $bg_color = $settings['email_bg_color'] ?? '#f5f5f5';
        $container_bg = $settings['email_container_bg'] ?? '#ffffff';
        $header_bg = $settings['email_header_bg'] ?? '#f9f9f9';
        
        $logo_html = $logo_url ? 
            '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr(get_bloginfo('name')) . '" style="max-width: 180px; height: auto; display: block; margin: 0 ' . ($logo_alignment === 'center' ? 'auto' : ($logo_alignment === 'left' ? '0 auto 0 0' : '0 0 0 auto')) . ';">' : 
            '<h1 style="text-align: ' . esc_attr($logo_alignment) . '; color: ' . esc_attr($heading_color) . ';">' . esc_html(get_bloginfo('name')) . '</h1>';
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { 
                    margin: 0; 
                    padding: 10px; 
                    background-color: <?php echo esc_attr($bg_color); ?>; 
                    font-family: Arial, sans-serif; 
                    line-height: 1.6; 
                    color: #333;
                }
                .nelx-email-container { 
                    max-width: 550px; 
                    margin: 0 auto; 
                    background: <?php echo esc_attr($container_bg); ?>; 
                    border-radius: 10px; 
                    overflow: hidden; 
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
                }
                .nelx-email-header { 
                    padding: 20px; 
                    text-align: <?php echo esc_attr($logo_alignment); ?>; 
                    background: <?php echo esc_attr($header_bg); ?>; 
                    border-bottom: 1px solid #eee; 
                }
                .nelx-email-body { 
                    padding: 25px 35px; 
                }
                .nelx-email-footer { 
                    padding: 20px; 
                    font-size: 12px; 
                    text-align: center; 
                    background: <?php echo esc_attr($settings['email_footer_bg'] ?? '#f9f9f9'); ?>; 
                    color: <?php echo esc_attr($settings['email_footer_text_color'] ?? '#777777'); ?>; 
                    line-height: 1.5; 
                    border-top: 1px solid #eee;
                }
                h1, h2, h3, h4 { 
                    color: <?php echo esc_attr($heading_color); ?>; 
                    margin-top: 0;
                    margin-bottom: 20px;
                }
                h1 { font-size: 24px; }
                h2 { font-size: 22px; }
                h3 { font-size: 20px; }
                h4 { font-size: 16px; }
                p {
                    margin-bottom: 15px;
                    font-size: 15px;
                }
                a {
                    color: <?php echo esc_attr($link_color); ?>;
                    text-decoration: none;
                    font-weight: bold;
                }
                a:hover {
                    text-decoration: none;
                }
                .nelx-code-box {
                    background-color: #f0f0f0;
                    padding: 15px;
                    text-align: center;
                    border-radius: 5px;
                    font-size: 18px;
                    margin: 20px 0;
                    font-family: monospace;
                    border: 1px dashed #ccc;
                }
                .nelx-btn-container { 
                    width: 100%; 
                    margin: 20px 0; 
                    box-sizing: border-box; 
                }
                .nelx-email-button { 
                    display: block; 
                    width: 100%; 
                    padding: 12px 25px;
                    background-color: <?php echo esc_attr($button_color); ?>; 
                    color: white !important; 
                    text-decoration: none; 
                    border-radius: 5px; 
                    font-weight: bold; 
                    text-align: center;
                    box-sizing: border-box;
                    line-height: 1.5;
                    border: none;
                    cursor: pointer;
                    margin: 15px 0;
                }
                .nelx-email-button:hover { 
                    background-color: <?php echo esc_attr($button_hover_color); ?>; 
                    text-decoration: none; 
                }
                .nelx-social-links {
                    margin: 15px 0;
                    text-align: center;
                }
                .nelx-social-links a {
                    display: inline-block;
                    margin: 0 10px;
                }
                .nelx-social-links img {
                    width: 24px;
                    height: 24px;
                }
                @media only screen and (max-width: 600px) {
                    .nelx-email-container {
                        width: 100% !important;
                        max-width: 100% !important;
                        margin: 0 !important;
                        border-radius: 0 !important;
                    }
                    .nelx-email-body {
                        padding: 20px 15px !important;
                    }
                    .nelx-btn-container {
                        width: 100% !important;
                        padding: 0 !important;
                        margin: 15px 0 !important;
                    }
                    .nelx-email-button {
                        width: 100% !important;
                        margin: 0 !important;
                    }
                    body {
                        padding: 0 !important;
                        margin: 0 !important;
                    }
                }
            </style>
        </head>
        <body>
            <div class="nelx-email-container">
                <div class="nelx-email-header">
                    <?php echo $logo_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
                <div class="nelx-email-body">
        <?php
        return ob_get_clean();
    }

    public static function nelx_get_email_footer() {
        $settings = get_option('nelx_email_branding_settings');
        $footer_text = $settings['email_footer_text'] ?? '&copy; [year] ' . get_bloginfo('name') . '. All rights reserved.<br>This email was sent from ' . get_bloginfo('name') . '.';
        
        $current_year = gmdate('Y');
        $footer_text = str_replace(['[year]', '{year}'], $current_year, $footer_text);
        
        $social_icons = $settings['email_social_icons'] ?? [];
        
        ob_start();
        ?>
                    </div>
                    <div class="nelx-email-footer">
                        <?php if (!empty($social_icons)) : ?>
                            <div class="nelx-social-links">
                                <?php foreach ($social_icons as $icon) : ?>
                                    <?php if (!empty($icon['url']) && !empty($icon['icon'])) : ?>
                                        <a href="<?php echo esc_url($icon['url']); ?>" target="_blank">
                                            <img src="<?php echo esc_url($icon['icon']); ?>" alt="">
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php echo wp_kses_post($footer_text); ?>
                    </div>
                </div>
            </body>
            </html>
        <?php
        return ob_get_clean();
    }

    public function send_email($to, $subject, $content, $from = null, $cc = [], $bcc = [], $is_custom_template = false, $request_data = []) {
        $header = self::nelx_get_email_header();
        $footer = self::nelx_get_email_footer();
        
        if ($is_custom_template && !empty($request_data)) {
            $subject = $this->replace_custom_placeholders($subject, $request_data);
            $content = $this->replace_custom_placeholders($content, $request_data);
        }
        
        $full_message = $header . wpautop($content) . $footer;
        
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . ($from ?: get_bloginfo('name') . ' <' . get_option('admin_email') . '>'),
            'Reply-To: ' . get_option('admin_email')
        ];
        
        if (!empty($cc)) {
            foreach ($cc as $cc_email) {
                $headers[] = 'Cc: ' . $cc_email;
            }
        }
        
        if (!empty($bcc)) {
            foreach ($bcc as $bcc_email) {
                $headers[] = 'Bcc: ' . $bcc_email;
            }
        }
        
        $subject = html_entity_decode($subject, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        add_filter('wp_mail_content_type', function() { return 'text/html'; });
        $result = wp_mail($to, $subject, $full_message, $headers);
        remove_filter('wp_mail_content_type', 'set_html_content_type');
        
        return $result;
    }

    public function send_cancellation_email($appointment_id, $initiator) {
        $appointment = $this->get_cached_appointment($appointment_id);
        if (!$appointment) return;
        
        $appointment['ID'] = $appointment_id;
        
        $templates = $this->get_email_templates();
        
        foreach ($templates as $template) {
            $email_settings = $template['email_settings'];
            $template_name = $template['name'];
            
            if ($initiator === 'provider' && $template_name === 'Appointment Canceled - Client') {
                $to_emails = $this->parse_recipients($email_settings['to'], $appointment);
                if (empty($to_emails)) continue;
                
                $this->send_email(
                    $to_emails,
                    $this->replace_placeholders($email_settings['subject'], $appointment, false),
                    $this->replace_placeholders($email_settings['message'], $appointment, false),
                    $this->replace_placeholders($email_settings['from'], $appointment, false),
                    $this->parse_recipients($email_settings['cc'] ?? '', $appointment),
                    $this->parse_recipients($email_settings['bcc'] ?? '', $appointment)
                );
            }
            elseif ($initiator === 'client' && $template_name === 'Appointment Canceled - Provider') {
                $to_emails = $this->parse_recipients($email_settings['to'], $appointment);
                if (empty($to_emails)) continue;
                
                $this->send_email(
                    $to_emails,
                    $this->replace_placeholders($email_settings['subject'], $appointment, true),
                    $this->replace_placeholders($email_settings['message'], $appointment, true),
                    $this->replace_placeholders($email_settings['from'], $appointment, true),
                    $this->parse_recipients($email_settings['cc'] ?? '', $appointment),
                    $this->parse_recipients($email_settings['bcc'] ?? '', $appointment)
                );
            }
        }
    }
    
    public function send_reschedule_email($appointment_id) {
        $appointment = $this->get_cached_appointment($appointment_id);
        if (!$appointment) return;
        
        $appointment['ID'] = $appointment_id;
        
        $templates = $this->get_email_templates();
        
        foreach ($templates as $template) {
            $email_settings = $template['email_settings'];
            $template_name = $template['name'];
            
            if ($template_name === 'Appointment Rescheduled - Client') {
                $to_emails = $this->parse_recipients($email_settings['to'], $appointment);
                if (empty($to_emails)) continue;
                
                $this->send_email(
                    $to_emails,
                    $this->replace_placeholders($email_settings['subject'], $appointment, false),
                    $this->replace_placeholders($email_settings['message'], $appointment, false),
                    $this->replace_placeholders($email_settings['from'], $appointment, false),
                    $this->parse_recipients($email_settings['cc'] ?? '', $appointment),
                    $this->parse_recipients($email_settings['bcc'] ?? '', $appointment)
                );
            }
            elseif ($template_name === 'Appointment Rescheduled - Provider') {
                $to_emails = $this->parse_recipients($email_settings['to'], $appointment);
                if (empty($to_emails)) continue;
                
                $this->send_email(
                    $to_emails,
                    $this->replace_placeholders($email_settings['subject'], $appointment, true),
                    $this->replace_placeholders($email_settings['message'], $appointment, true),
                    $this->replace_placeholders($email_settings['from'], $appointment, true),
                    $this->parse_recipients($email_settings['cc'] ?? '', $appointment),
                    $this->parse_recipients($email_settings['bcc'] ?? '', $appointment)
                );
            }
        }
    }
    
    public function send_status_email($appointment_id, $old_status, $new_status) {
        $appointment = $this->get_cached_appointment($appointment_id);
        if (!$appointment) return;
        
        $appointment['ID'] = $appointment_id;
        $appointment_type = $appointment['appointment_type'] ?? 'physical';
        
        $templates = $this->get_email_templates();
        $initiator = $this->get_status_change_initiator();
        
        foreach ($templates as $template) {
            $email_settings = $template['email_settings'];
            $template_name = $template['name'];
            
            if ($new_status === 'accepted') {
                if ($template_name === 'Appointment Confirmation - Online' && $appointment_type === 'online') {
                    $to_emails = $this->parse_recipients($email_settings['to'], $appointment);
                    if (empty($to_emails)) continue;
                    
                    $this->send_email(
                        $to_emails,
                        $this->replace_placeholders($email_settings['subject'], $appointment, false),
                        $this->replace_placeholders($email_settings['message'], $appointment, false),
                        $this->replace_placeholders($email_settings['from'], $appointment, false),
                        $this->parse_recipients($email_settings['cc'] ?? '', $appointment),
                        $this->parse_recipients($email_settings['bcc'] ?? '', $appointment)
                    );
                } 
                elseif ($template_name === 'Appointment Confirmation - Physical' && $appointment_type === 'physical') {
                    $to_emails = $this->parse_recipients($email_settings['to'], $appointment);
                    if (empty($to_emails)) continue;
                    
                    $this->send_email(
                        $to_emails,
                        $this->replace_placeholders($email_settings['subject'], $appointment, false),
                        $this->replace_placeholders($email_settings['message'], $appointment, false),
                        $this->replace_placeholders($email_settings['from'], $appointment, false),
                        $this->parse_recipients($email_settings['cc'] ?? '', $appointment),
                        $this->parse_recipients($email_settings['bcc'] ?? '', $appointment)
                    );
                }
            }
            elseif ($new_status === 'canceled') {
                do_action('nelx_appointment_canceled', $appointment_id, $initiator);
            }
        }
    }
    
    private function get_provider_info($appointment) {
        $provider_info = [
            'id' => 0,
            'email' => '',
            'name' => ''
        ];
        
        $settings = get_option('nelx_jetappt_settings', []);
        $provider_column = $settings['provider_column'] ?? 'staff_id';
        $provider_user_id = $appointment[$provider_column] ?? 0;
        
        if ($provider_user_id) {
            $provider_info['id'] = $provider_user_id;
            
            $user = get_user_by('ID', $provider_user_id);
            if ($user) {
                $provider_info['email'] = $user->user_email;
                $provider_info['name'] = $user->display_name ?: $user->user_login;
            }
        }
        
        return $provider_info;
    }
    
    private function get_client_local_datetime($appointment_id) {
        $local_date = $this->get_cached_meta_value($appointment_id, 'user_local_date');
        $local_time = $this->get_cached_meta_value($appointment_id, 'user_local_time');
        
        if ($local_date !== null && $local_time !== null) {
            return [
                'date' => $local_date,
                'time' => $local_time
            ];
        }
        
        return null;
    }

    private function replace_placeholders($content, $appointment, $is_provider = true) {
        $service_title = '';
        if (!empty($appointment['service'])) {
            $service_title = get_the_title((int) $appointment['service']);
            $service_title = html_entity_decode($service_title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $service_title = trim($service_title);
        }
        
        $appointment_type = $appointment['appointment_type'] ?? 'physical';
        
        $provider_email = '';
        if (!empty($appointment['provider'])) {
            $settings = get_option('nelx_jetappt_settings', []);
            $provider_column = $settings['provider_column'] ?? 'staff_id';
            $provider_user_id = $appointment[$provider_column] ?? $appointment['provider'];
            
            $user = get_user_by('ID', $provider_user_id);
            if ($user) {
                $provider_email = $user->user_email;
            }
        }
        
        $appointment_date = '';
        $appointment_time = '';
        
        if ($is_provider) {
            $appointment_date = date_i18n(get_option('date_format'), $appointment['slot']);
            $appointment_time = date_i18n(get_option('time_format'), $appointment['slot']);
            
            if (!empty($appointment['slot_end']) && $appointment['slot_end'] != $appointment['slot']) {
                $end_time = date_i18n(get_option('time_format'), $appointment['slot_end']);
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
                $appointment_date = date_i18n(get_option('date_format'), $appointment['slot']);
                $appointment_time = date_i18n(get_option('time_format'), $appointment['slot']);
                
                if (!empty($appointment['slot_end']) && $appointment['slot_end'] != $appointment['slot']) {
                    $end_time = date_i18n(get_option('time_format'), $appointment['slot_end']);
                    $appointment_time = $appointment_time . ' - ' . $end_time;
                }
            }
        }
        
        $replacements = [
            'service_name' => $service_title ?: __('Consultation', 'nelx-jetappt-frontend'),
            'client_email' => $appointment['user_email'] ?? '',
            'provider_email' => $provider_email,
            'provider_name' => !empty($appointment['provider']) ? get_the_title($appointment['provider']) : '',
            'appointment_date' => $appointment_date,
            'appointment_time' => $appointment_time,
            'appointment_type' => $appointment_type,
            'meeting_url' => $appointment['google_meet_url'] ?? '',
            'client_name' => $appointment['user_name'] ?? __('Client', 'nelx-jetappt-frontend'),
            'scheduled_date' => $appointment_date
        ];
        
        foreach ($replacements as $key => $value) {
            $content = str_replace('{' . $key . '}', esc_html($value), $content);
        }
        
        $content = preg_replace_callback('/\{if ([^=]+)==="([^"]+)"\}(.*?)\{\/if\}/s', function($matches) use ($replacements) {
            $condition_key = trim($matches[1]);
            $condition_value = $matches[2];
            $block_content = $matches[3];
            return isset($replacements[$condition_key]) && $replacements[$condition_key] === $condition_value ? $block_content : '';
        }, $content);
        
        return $content;
    }
    
    private function replace_custom_placeholders($content, $request_data) {
        foreach ($request_data as $key => $value) {
            if (is_scalar($value)) {
                $content = str_replace('{' . $key . '}', esc_html($value), $content);
            }
        }
        
        $defaults = [
            'site_name'    => get_bloginfo('name'),
            'site_url'     => home_url(),
            'current_date' => date_i18n(get_option('date_format')),
            'current_time' => date_i18n(get_option('time_format')),
        ];
        
        foreach ($defaults as $key => $value) {
            $content = str_replace('{' . $key . '}', esc_html($value), $content);
        }
        
        $content = preg_replace_callback(
            '/\{if ([^=]+)==="([^"]+)"\}(.*?)\{\/if\}/s',
            function($matches) use ($request_data) {
                $condition_key   = trim($matches[1]);
                $condition_value = $matches[2];
                $block_content   = $matches[3];
                
                $value = isset($request_data[$condition_key]) && is_scalar($request_data[$condition_key]) 
                       ? $request_data[$condition_key] 
                       : '';
                
                return $value === $condition_value ? $block_content : '';
            },
            $content
        );
        
        $content = preg_replace('/\{if [^\}]*\}.*?\{\/if\}/s', '', $content);
        
        return $content;
    }

    private function get_status_change_initiator() {
        return current_user_can('manage_options') || current_user_can('edit_jet_appointments') ? 'provider' : 'client';
    }
    
    public function send_scheduled_reminders() {
        global $wpdb;
    
        $settings         = get_option('nelx_jetappt_settings', []);
        $reminder_timing  = $settings['reminder_timing'] ?? '24';
        $reminder_seconds = intval($reminder_timing) * HOUR_IN_SECONDS;
    
        if ($reminder_seconds <= 0) {
            return ['sent' => 0, 'failed' => 0, 'message' => 'Reminder timing not set or invalid'];
        }
    
        $core = NELXJAF_Core::instance();
        $appt_table      = $core->appt_table;
        $appt_meta_table = $core->appt_meta_table;
    
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $appt_table_exists = $wpdb->get_var(
            $wpdb->prepare("SHOW TABLES LIKE %s", $appt_table)
        );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $appt_meta_table_exists = $wpdb->get_var(
            $wpdb->prepare("SHOW TABLES LIKE %s", $appt_meta_table)
        );
    
        if (!$appt_table_exists || !$appt_meta_table_exists) {
            return ['sent' => 0, 'failed' => 0, 'message' => 'Required database tables not found'];
        }
    
        $current_time = current_time('timestamp');
    
        $batch_size      = apply_filters('nelx_reminder_batch_size', 50);
        $offset          = 0;
        $processed_total = 0;
        $results         = ['sent' => 0, 'failed' => 0, 'processed' => 0, 'batches' => 0];
    
        do {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            $appointments = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT a.* 
                     FROM {$appt_table} a
                     LEFT JOIN {$appt_meta_table} m 
                        ON a.ID = m.appointment_id AND m.meta_key = 'reminder_sent'
                     WHERE 
                         (a.slot - %d) <= %d
                         AND a.appointment_status = 'accepted'
                         AND m.meta_value IS NULL
                     ORDER BY a.slot ASC
                     LIMIT %d, %d",
                    $reminder_seconds,
                    $current_time,
                    $offset,
                    $batch_size
                ),
                ARRAY_A
            );
    
            $appointments_in_batch = count($appointments);
            $processed_total      += $appointments_in_batch;
            $results['batches']++;
    
            foreach ($appointments as $appointment) {
                $sent = $this->send_appointment_reminder($appointment);
    
                if ($sent) {
                    $results['sent']++;
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter
                    $insert_result = $wpdb->insert(
                        $appt_meta_table,
                        [
                            'appointment_id' => $appointment['ID'],
                            'meta_key'       => 'reminder_sent',
                            'meta_value'     => current_time('mysql')
                        ],
                        ['%d', '%s', '%s']
                    );
    
                    if ($insert_result === false) {
                        $results['failed']++;
                        $results['sent']--;
                    } else {
                        do_action('nelx_appointment_reminder', $appointment['ID']);
                        // Clear cache for this appointment
                        $this->clear_appointment_cache($appointment['ID']);
                    }
                } else {
                    $results['failed']++;
                }
            }
    
            $results['processed'] = $processed_total;
    
            if ($appointments_in_batch === $batch_size) {
                $cooling_time_seconds = apply_filters('nelx_reminder_batch_cooling_time', 2);
                sleep($cooling_time_seconds);
            }
    
            $offset += $batch_size;
    
        } while ($appointments_in_batch === $batch_size);
    
        return $results;
    }
    
    private function send_appointment_reminder($appointment) {
        $templates = $this->get_email_templates();
        
        $reminder_template = null;
        foreach ($templates as $template) {
            if ($template['name'] === 'Appointment Reminder') {
                $reminder_template = $template;
                break;
            }
        }
        
        if (!$reminder_template) {
            return false;
        }
        
        $email_settings = $reminder_template['email_settings'];
        $appointment['ID'] = $appointment['ID'];
        
        $to_emails = $this->parse_recipients($email_settings['to'], $appointment);
        if (empty($to_emails)) {
            return false;
        }
        
        return $this->send_email(
            $to_emails,
            $this->replace_placeholders($email_settings['subject'], $appointment, false),
            $this->replace_placeholders($email_settings['message'], $appointment, false),
            $this->replace_placeholders($email_settings['from'], $appointment, false),
            $this->parse_recipients($email_settings['cc'] ?? '', $appointment),
            $this->parse_recipients($email_settings['bcc'] ?? '', $appointment)
        );
    }
    
    public function handle_ajax_reminders() {
        $result = $this->send_scheduled_reminders();
        
        if (defined('DOING_AJAX') && DOING_AJAX) {
            wp_send_json_success($result);
        }
        
        echo json_encode($result);
        exit;
    }
    
    public function process_automatic_deletions() {
        global $wpdb;
        
        $options = get_option('nelx_jetappt_settings', []);
        $core = NELXJAF_Core::instance();
        $appt_table = $core->appt_table;
        
        $result = [
            'past_deleted' => 0,
            'canceled_deleted' => 0,
            'errors' => [],
            'debug' => []
        ];
        
        // Check if table exists
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $table_exists = $wpdb->get_var(
            $wpdb->prepare("SHOW TABLES LIKE %s", $appt_table)
        );
        
        if (!$table_exists) {
            $result['errors'][] = 'Table does not exist: ' . $appt_table;
            return $result;
        }
        
        // Delete past appointments
        if (($options['auto_delete_past'] ?? '0') === '1') {
            $days = $options['auto_delete_past_days'] ?? '7';
            if ($days === 'custom') {
                $days = $options['auto_delete_past_custom'] ?? '7';
            }
            
            $days = intval($days);
            if ($days < 1) {
                $days = 3;
            }
            
            $current_timestamp = current_time('timestamp');
            $cutoff_timestamp = $current_timestamp - ($days * DAY_IN_SECONDS);
            
            // Check column types
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $date_column_type = $wpdb->get_var("SHOW COLUMNS FROM {$appt_table} LIKE 'date'");
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $slot_column_type = $wpdb->get_var("SHOW COLUMNS FROM {$appt_table} LIKE 'slot'");
            
            $where_conditions = [];
            
            if ($date_column_type) {
                $where_conditions[] = $wpdb->prepare("date < %d", $cutoff_timestamp);
            }
            
            if ($slot_column_type) {
                $where_conditions[] = $wpdb->prepare("slot < %d", $cutoff_timestamp);
            }
            
            if (empty($where_conditions)) {
                $result['errors'][] = 'Neither date nor slot column found in appointments table';
            } else {
                $where_clause = implode(' OR ', $where_conditions);
                $where_clause .= " AND appointment_status != 'canceled'";
                
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $sql = "DELETE FROM {$appt_table} WHERE {$where_clause}";
                
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $result['past_deleted'] = $wpdb->query($sql);
                
                if ($result['past_deleted'] === false) {
                    $result['errors'][] = 'Failed to delete past appointments: ' . $wpdb->last_error;
                }
            }
            
            // Clear cache since we deleted records
            wp_cache_delete('nelx_past_appointments_count', 'nelx_appointments');
            wp_cache_delete('nelx_appointments_count', 'nelx_appointments');
        }
        
        // Delete canceled appointments
        if (($options['auto_delete_canceled'] ?? '0') === '1') {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $result['canceled_deleted'] = $wpdb->query(
                $wpdb->prepare("DELETE FROM {$appt_table} WHERE appointment_status = %s", 'canceled')
            );
            
            if ($result['canceled_deleted'] === false) {
                $result['errors'][] = 'Failed to delete canceled appointments: ' . $wpdb->last_error;
            }
            
            // Clear cache since we deleted records
            wp_cache_delete('nelx_canceled_appointments_count', 'nelx_appointments');
            wp_cache_delete('nelx_appointments_count', 'nelx_appointments');
        }
        
        return $result;
    }
}

NELXJAF_Custom_Emails::instance();

add_action('transition_post_status', 'nelx_track_appointment_status', 10, 3);
function nelx_track_appointment_status($new_status, $old_status, $post) {
    if ($post->post_type === 'jet-appointment' && $new_status !== $old_status) {
        do_action('nelx_appointment_status_changed', $post->ID, $old_status, $new_status);
    }
}
