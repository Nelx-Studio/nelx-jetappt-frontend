<?php
/**
 * Custom email hooks for Nelx JetAppointments Frontend
 */

if (!defined('ABSPATH')) exit;

class NELXJAF_Custom_Emails_Hooks {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->register_new_appointment_hook();
        $this->register_custom_email_hooks();
    }

    private function register_new_appointment_hook() {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
        add_action( 'jet-apb/appointments/inserted', function( $appointment_id, $data ) {
            do_action(
                'jet-form-builder/custom-action/new_appointment_provider',
                array_merge(
                    $data,
                    [ 'appointment_id' => $appointment_id ]
                ),
                null
            );
        }, 10, 2 );
    
        add_action(
            'jet-form-builder/custom-action/new_appointment_provider',
            function( $request, $action_handler ) {
                try {
                    if ( empty( $request['appointment_id'] ) ) {
                        return false;
                    }

                    $appointment_id = absint( $request['appointment_id'] );
                    if ( ! $appointment_id ) {
                        return false;
                    }

                    global $wpdb;
                    $core = NELXJAF_Core::instance();
                    $appt_table  = $core->appt_table;
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
                    $appointment = $wpdb->get_row(
                        $wpdb->prepare(
                            "SELECT * FROM {$appt_table} WHERE ID = %d",
                            $appointment_id
                        ),
                        ARRAY_A
                    );

                    if ( ! $appointment ) {
                        return false;
                    }

                    $provider_id    = $request['provider_id'] ?? $appointment['provider'] ?? 0;
                    $provider_email = get_user_meta( $provider_id, 'nelx_google_meet_email', true );
                    
                    $settings  = get_option( 'nelx_jetappt_settings', [] );
                    $templates = $settings['default_email_templates'] ?? [];
                    $default_templates = NELXJAF_Custom_Emails::instance()->get_default_templates();
                    
                    $calendar_title = '';
                    foreach ( array_merge($default_templates, $templates) as $template ) {
                        if ( $template['name'] === 'New Appointment - Provider' ) {
                            $email_settings = $template['email_settings'];
                            $calendar_title = $this->process_template(
                                $email_settings['subject'],
                                $request,
                                $appointment
                            );
                            break;
                        }
                    }
                    
                    if ( ! $calendar_title ) {
                        $calendar_title = $this->process_template(
                            'Service: {service_name} – {appointment_start} – {appointment_end}',
                            $request,
                            $appointment
                        );
                    }
                    
                    if ( class_exists('NELXJAF_Google_Meet_Integration') ) {
                        $meet_url = NELXJAF_Google_Meet_Integration::instance()->create_google_meet(
                            $provider_id,
                            $appointment,
                            $provider_email,
                            $calendar_title,
                            $request
                        );
                        
                        if ( $meet_url ) {
                            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                            $wpdb->update(
                                $appt_table,
                                [ 'google_meet_url' => $meet_url ],
                                [ 'ID' => $appointment_id ],
                                [ '%s' ],
                                [ '%d' ]
                            );
                            $appointment['google_meet_url'] = $meet_url;
                        }
                    }

                    $templates = $settings['default_email_templates'] ?? [];

                    foreach ( array_merge($default_templates, $templates) as $template ) {
                        if ( $template['name'] === 'New Appointment - Provider' ) {
                            $email_settings = $template['email_settings'];
                            
                            $email_class = NELXJAF_Custom_Emails::instance();
                            $to_emails = $email_class->parse_recipients($email_settings['to'], $appointment, $request);
                            
                            if ( empty( $to_emails ) ) {
                                return false;
                            }

                            $subject    = $this->process_template( $email_settings['subject'], $request, $appointment );
                            $message    = $this->process_template( $email_settings['message'], $request, $appointment );
                            $from       = $this->process_template( $email_settings['from'], $request, $appointment );
                            $cc_emails  = $email_class->parse_recipients($email_settings['cc'] ?? '', $appointment, $request);
                            $bcc_emails = $email_class->parse_recipients($email_settings['bcc'] ?? '', $appointment, $request);

                            $result = $email_class->send_email(
                                $to_emails,
                                $subject,
                                $message,
                                $from,
                                $cc_emails,
                                $bcc_emails,
                                false,
                                $request
                            );
                            
                            do_action('nelx_appointment_created', $appointment_id, $request);
                        
                            return $result;
                        }
                    }

                    return false;

                } catch ( Exception $e ) {
                    return false;
                }
            },
            10,
            2
        );
    }

    public static function get_email_hook_name($template_index) {
        return 'custom_email_' . $template_index;
    }

    private function register_custom_email_hooks() {
        $settings = get_option('nelx_jetappt_settings');
        if (empty($settings) || empty($settings['custom_email_templates'])) {
            return;
        }

        foreach ($settings['custom_email_templates'] as $index => $template) {
            $hook = 'jet-form-builder/custom-action/' . self::get_email_hook_name($index);
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
            add_action($hook, function($request, $action_handler) use ($index, $template) {
                try {
                    $email_settings = $template['email_settings'] ?? $template;
                    $email_class = NELXJAF_Custom_Emails::instance();
                    
                    $to_emails = $email_class->parse_recipients($email_settings['to'], [], $request);
                    if (empty($to_emails)) {
                        return false;
                    }
                    
                    $subject = $this->process_custom_template($email_settings['subject'], $request);
                    $message = $this->process_custom_template($email_settings['message'], $request);
                    $from = $this->process_custom_template($email_settings['from'], $request);
                    $cc_emails = $email_class->parse_recipients($email_settings['cc'] ?? '', [], $request);
                    $bcc_emails = $email_class->parse_recipients($email_settings['bcc'] ?? '', [], $request);
                    
                    $result = $email_class->send_email(
                        $to_emails,
                        $subject,
                        $message,
                        $from,
                        $cc_emails,
                        $bcc_emails,
                        true,
                        $request
                    );
                    
                    return $result;
                    
                } catch (Exception $e) {
                    return false;
                }
            }, 10, 2);
        }
    }

    private function process_custom_template($content, $request_data) {
        if (empty($content)) {
            return $content;
        }
        
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

    private function process_template($content, $request_data, $appointment = null) {
        if (empty($content)) {
            return $content;
        }
    
        foreach ($request_data as $key => $value) {
            if ($key === 'date') {
                continue;
            }
            if (is_scalar($value)) {
                $content = str_replace('{' . $key . '}', esc_html($value), $content);
            }
        }
    
        if ($appointment) {
            $date_val = !empty($appointment['date'])
                ? date_i18n(get_option('date_format'), intval($appointment['date']))
                : '';
    
            if (!empty($appointment['slot']) && !empty($appointment['slot_end'])) {
                $time_val = date_i18n(get_option('time_format'), intval($appointment['slot'])) .
                            ' - ' .
                            date_i18n(get_option('time_format'), intval($appointment['slot_end']));
            } elseif (!empty($appointment['slot'])) {
                $time_val = date_i18n(get_option('time_format'), intval($appointment['slot']));
            } else {
                $time_val = '';
            }
    
            $start_val = !empty($appointment['slot'])
                ? date_i18n(get_option('time_format'), intval($appointment['slot']))
                : '';
            $end_val = !empty($appointment['slot_end'])
                ? date_i18n(get_option('time_format'), intval($appointment['slot_end']))
                : '';
    
            $provider_email = '';
            $provider_user_id = $appointment['staff_id'] ?? $appointment['provider'] ?? 0;
            if ($provider_user_id) {
                $user = get_user_by('ID', $provider_user_id);
                if ($user) {
                    $provider_email = $user->user_email;
                }
            }
    
            $replacements = [
                'service_name'      => !empty($appointment['service']) ? get_the_title($appointment['service']) : __('Consultation', 'nelx-jetappt-frontend'),
                'client_email'      => $appointment['user_email'] ?? '',
                'provider_email'    => $provider_email,
                'provider_name'     => !empty($appointment['provider']) ? get_the_title($appointment['provider']) : '',
                'scheduled_date'    => $date_val,
                'appointment_date'  => $date_val,
                'appointment_time'  => $time_val,
                'appointment_start' => $start_val,
                'appointment_end'   => $end_val,
                'appointment_type'  => $appointment['appointment_type'] ?? $appointment['type'] ?? 'online',
                'meeting_url'       => $appointment['google_meet_url'] ?? '',
                'client_name'       => $appointment['user_name'] ?? __('Client', 'nelx-jetappt-frontend'),
            ];
    
            foreach ($replacements as $key => $value) {
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
            function($matches) use ($request_data, $appointment) {
                $condition_key   = trim($matches[1]);
                $condition_value = $matches[2];
                $block_content   = $matches[3];
    
                $value = null;
                if (isset($request_data[$condition_key])) {
                    $value = is_scalar($request_data[$condition_key]) ? $request_data[$condition_key] : '';
                } elseif ($appointment && isset($appointment[$condition_key])) {
                    $value = $appointment[$condition_key];
                }
    
                return $value === $condition_value ? $block_content : '';
            },
            $content
        );
    
        $content = preg_replace('/\{if [^\}]*\}.*?\{\/if\}/s', '', $content);
    
        return $content;
    }
}

NELXJAF_Custom_Emails_Hooks::instance();
