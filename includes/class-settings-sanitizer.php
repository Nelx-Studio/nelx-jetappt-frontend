<?php
/**
 * Settings Sanitizer Class - Handles all settings sanitization
 */

if (!defined('ABSPATH')) exit;

class NELXJAF_Settings_Sanitizer {
    
    /**
     * Sanitize main settings
     */
    public static function sanitize_settings($input) {
        $sanitized = [];
        
        // General settings
        $db_fields = ['provider_meta_table', 'provider_column'];
        foreach ($db_fields as $field) {
            $sanitized[$field] = isset($input[$field]) ? sanitize_text_field($input[$field]) : '';
        }

        $default_staff_columns = ['id', 'service', 'client', 'appointment_date', 'slot', 'slot_end', 'action'];
        $default_client_columns = ['id', 'service', 'staff', 'appointment_date', 'time', 'status', 'action'];
        $allowed_columns = [];
        if (class_exists('NELXJAF_Appointment_Listings')) {
            $allowed_columns = array_keys(NELXJAF_Appointment_Listings::instance(NELXJAF_Core::instance())->get_table_columns());
        }
        // The table supports display-only/virtual columns in addition to native DB columns.
        $allowed_columns = array_unique(array_merge($allowed_columns, [
            'id', 'service', 'client', 'staff', 'provider', 'appointment_date',
            'time', 'status', 'action', 'type',
            'appointment_type', 'date', 'slot', 'slot_end', 'appointment_status'
        ]));
        foreach (['staff_table_columns' => $default_staff_columns, 'client_table_columns' => $default_client_columns] as $field => $fallback) {
            $values = isset($input[$field]) && is_array($input[$field]) ? $input[$field] : $fallback;
            $values = array_map('sanitize_key', $values);
            // Migrate legacy virtual aliases to the native JetAppointments fields.
            $values = array_map(function($value) {
                if ($value === 'start_time') return 'slot';
                if ($value === 'end_time') return 'slot_end';
                return $value;
            }, $values);
            $values = array_values(array_unique(array_intersect($values, $allowed_columns)));
            $sanitized[$field] = $values ?: $fallback;
        }

        $default_staff_modal_fields = ['start', 'end', 'timezone', 'service', 'client', 'client_email', 'client_phone', 'google_meet', 'appointment_status', 'client_local_date', 'client_local_time', 'client_local_timezone', 'notes'];
        $default_client_modal_fields = ['date', 'time', 'timezone', 'service', 'provider', 'google_meet', 'appointment_status', 'notes'];
        $modal_definitions = [];
        if (class_exists('NELXJAF_Appointment_Listings')) {
            $listing_manager = NELXJAF_Appointment_Listings::instance(NELXJAF_Core::instance());
            $modal_definitions = [
                'staff_modal_fields' => array_keys($listing_manager->get_modal_field_definitions('staff')),
                'client_modal_fields' => array_keys($listing_manager->get_modal_field_definitions('client')),
            ];
        } else {
            $modal_definitions = [
                'staff_modal_fields' => array_keys(array_fill_keys($default_staff_modal_fields, true)),
                'client_modal_fields' => array_keys(array_fill_keys($default_client_modal_fields, true)),
            ];
        }
        foreach ([
            'staff_modal_fields' => $default_staff_modal_fields,
            'client_modal_fields' => $default_client_modal_fields,
        ] as $field => $fallback) {
            $allowed = $modal_definitions[$field] ?? $fallback;
            $values = isset($input[$field]) && is_array($input[$field]) ? $input[$field] : $fallback;
            $values = array_map('sanitize_key', $values);
            $values = array_values(array_unique(array_intersect($values, $allowed)));
            $sanitized[$field] = $values ?: $fallback;
        }

        // Optional custom labels for table columns and modal fields.
        foreach ([
            'staff_table_labels' => $allowed_columns,
            'client_table_labels' => $allowed_columns,
            'staff_modal_labels' => $modal_definitions['staff_modal_fields'] ?? $default_staff_modal_fields,
            'client_modal_labels' => $modal_definitions['client_modal_fields'] ?? $default_client_modal_fields,
        ] as $label_field => $allowed) {
            $sanitized[$label_field] = [];
            $values = isset($input[$label_field]) && is_array($input[$label_field]) ? $input[$label_field] : [];
            foreach ($values as $key => $label) {
                $key = sanitize_key($key);
                // Preserve custom labels from the removed virtual time aliases.
                if ($key === 'start_time') $key = 'slot';
                if ($key === 'end_time') $key = 'slot_end';
                if (!in_array($key, $allowed, true)) continue;
                $label = sanitize_text_field($label);
                if ($label !== '') $sanitized[$label_field][$key] = $label;
            }
        }
        $sanitized['staff_grid_limit'] = max(1, min(100, absint($input['staff_grid_limit'] ?? 5)));
        $sanitized['client_grid_limit'] = max(1, min(100, absint($input['client_grid_limit'] ?? 5)));
        foreach ([
            'staff_grid_columns_desktop' => [1, 6, 3],
            'staff_grid_columns_tablet' => [1, 4, 2],
            'client_grid_columns_desktop' => [1, 6, 3],
            'client_grid_columns_tablet' => [1, 4, 2],
            'table_header_font_size' => [8, 32, 14],
            'table_body_font_size' => [8, 32, 14],
            'table_control_font_size' => [8, 24, 14],
            'grid_label_font_size' => [8, 32, 13],
            'grid_label_font_weight' => [100, 900, 400],
            'grid_value_font_size' => [8, 32, 14],
            'grid_value_font_weight' => [100, 900, 500],
            'grid_empty_font_size' => [8, 32, 14],
            'grid_empty_font_weight' => [100, 900, 400],
            'action_disabled_opacity' => [0.1, 1, 0.5],
        ] as $field => $range) {
            $value = absint($input[$field] ?? $range[2]);
            $sanitized[$field] = max($range[0], min($range[1], $value));
        }
        
        $sanitized['grid_label_line_height'] = max(1, min(3, (float) ($input['grid_label_line_height'] ?? 1.4)));
        $sanitized['grid_value_line_height'] = max(1, min(3, (float) ($input['grid_value_line_height'] ?? 1.4)));
        $sanitized['grid_empty_line_height'] = max(1, min(3, (float) ($input['grid_empty_line_height'] ?? 1.4)));
        $sanitized['action_transition_duration'] = max(0, min(3, (float) ($input['action_transition_duration'] ?? 0.2)));
        // Google Meet settings
        $sanitized['google_client_id'] = isset($input['google_client_id']) ? sanitize_text_field($input['google_client_id']) : '';
        $sanitized['google_client_secret'] = isset($input['google_client_secret']) ? sanitize_text_field($input['google_client_secret']) : '';
        
        // Email branding settings
        $sanitized['email_logo_url'] = isset($input['email_logo_url']) ? esc_url_raw($input['email_logo_url']) : '';
        $sanitized['email_logo_alignment'] = isset($input['email_logo_alignment']) ? sanitize_text_field($input['email_logo_alignment']) : 'center';
        
        // Native appointment listing appearance
        $appointment_color_fields = [
            'table_header_bg' => '',
            'table_header_text' => '#ffffff',
            'table_hover_bg' => '#f5f5f5',
            'grid_card_bg' => '#ffffff',
            'grid_card_border' => '#e5e7eb',
            'grid_card_hover_bg' => '#ffffff',
            'grid_card_hover_border' => '#d1d5db',
            'action_disabled_bg' => '',
            'action_edit_color' => '', 'action_edit_bg' => '', 'action_edit_hover_color' => '', 'action_edit_hover_bg' => '',
            'action_confirm_color' => '', 'action_confirm_bg' => '', 'action_confirm_hover_color' => '', 'action_confirm_hover_bg' => '',
            'action_reject_color' => '', 'action_reject_bg' => '', 'action_reject_hover_color' => '', 'action_reject_hover_bg' => '',
            'action_info_color' => '', 'action_info_bg' => '', 'action_info_hover_color' => '', 'action_info_hover_bg' => '',
        ];
        foreach ($appointment_color_fields as $field => $default) {
            $value = isset($input[$field]) ? sanitize_hex_color($input[$field]) : '';
            $sanitized[$field] = $value !== null ? $value : $default;
        }
        
        // Color settings
        $color_fields = [
            'email_heading_color' => '#1E3A8A',
            'email_button_color' => '#1E3A8A',
            'email_button_hover_color' => '#1a3a47',
            'email_link_color' => '#1E3A8A',
            'email_bg_color' => '#f5f5f5',
            'email_container_bg' => '#ffffff',
            'email_header_bg' => '#f9f9f9',
            'email_footer_bg' => '#f9f9f9',
            'email_footer_text_color' => '#777777'
        ];
        
        foreach ($color_fields as $field => $default) {
            $sanitized[$field] = isset($input[$field]) ? sanitize_hex_color($input[$field]) : $default;
        }
        
        // Email footer text
        $sanitized['email_footer_text'] = isset($input['email_footer_text']) ? wp_kses_post($input['email_footer_text']) : '';
        
        // Email social icons
        $sanitized['email_social_icons'] = [];
        if (isset($input['email_social_icons']) && is_array($input['email_social_icons'])) {
            foreach ($input['email_social_icons'] as $index => $icon) {
                $sanitized['email_social_icons'][$index] = [
                    'url' => isset($icon['url']) ? esc_url_raw($icon['url']) : '',
                    'icon' => isset($icon['icon']) ? esc_url_raw($icon['icon']) : ''
                ];
            }
        }
        
        // Email templates
        $sanitized['default_email_templates'] = self::sanitize_default_templates($input['default_email_templates'] ?? []);
        $sanitized['custom_email_templates'] = self::sanitize_custom_templates($input['custom_email_templates'] ?? []);
        
        // Automation settings
        $sanitized['reminder_timing'] = isset($input['reminder_timing']) ? sanitize_text_field($input['reminder_timing']) : '24';
        $sanitized['notifications_enabled'] = isset($input['notifications_enabled']) && $input['notifications_enabled'] === '1' ? '1' : '0';
        $sanitized['api_url'] = isset($input['api_url']) ? esc_url_raw($input['api_url']) : '';
        $sanitized['provider_appointments_page'] = isset($input['provider_appointments_page']) ? esc_url_raw($input['provider_appointments_page']) : '';
        $sanitized['client_appointments_page'] = isset($input['client_appointments_page']) ? esc_url_raw($input['client_appointments_page']) : '';
        $sanitized['auto_delete_past'] = isset($input['auto_delete_past']) && $input['auto_delete_past'] === '1' ? '1' : '0';
        $sanitized['auto_delete_past_days'] = isset($input['auto_delete_past_days']) ? sanitize_text_field($input['auto_delete_past_days']) : '7';
        
        $custom_days = isset($input['auto_delete_past_custom']) ? intval($input['auto_delete_past_custom']) : 0;
        $sanitized['auto_delete_past_custom'] = ($custom_days >= 1) ? $custom_days : '';
        
        $sanitized['auto_delete_canceled'] = isset($input['auto_delete_canceled']) && $input['auto_delete_canceled'] === '1' ? '1' : '0';
        
        return $sanitized;
    }
    
    public static function sanitize_google_meet_settings($input) {
        $input = is_array($input) ? $input : [];
        return [
            'google_client_id' => isset($input['google_client_id']) ? sanitize_text_field($input['google_client_id']) : '',
            'google_client_secret' => isset($input['google_client_secret']) ? sanitize_text_field($input['google_client_secret']) : '',
        ];
    }

    public static function sanitize_email_branding_settings($input) {
        $input = is_array($input) ? $input : [];
        $colors = [
            'email_heading_color' => '#1E3A8A',
            'email_button_color' => '#1E3A8A',
            'email_button_hover_color' => '#1a3a47',
            'email_link_color' => '#1E3A8A',
            'email_bg_color' => '#f5f5f5',
            'email_container_bg' => '#ffffff',
            'email_header_bg' => '#f9f9f9',
            'email_footer_bg' => '#f9f9f9',
            'email_footer_text_color' => '#777777',
        ];
        $out = [];
        $out['email_logo_url'] = isset($input['email_logo_url']) ? esc_url_raw($input['email_logo_url']) : '';
        $out['email_logo_alignment'] = isset($input['email_logo_alignment']) ? sanitize_key($input['email_logo_alignment']) : 'center';
        foreach ($colors as $field => $default) {
            $value = isset($input[$field]) ? sanitize_hex_color($input[$field]) : null;
            $out[$field] = $value !== null ? $value : $default;
        }
        $out['email_footer_text'] = isset($input['email_footer_text']) ? wp_kses_post($input['email_footer_text']) : '';
        $out['email_social_icons'] = [];
        if (isset($input['email_social_icons']) && is_array($input['email_social_icons'])) {
            foreach ($input['email_social_icons'] as $index => $icon) {
                $out['email_social_icons'][$index] = [
                    'url' => isset($icon['url']) ? esc_url_raw($icon['url']) : '',
                    'icon' => isset($icon['icon']) ? esc_url_raw($icon['icon']) : '',
                ];
            }
        }
        return $out;
    }

    /**
     * Sanitize default email templates
     */
    public static function sanitize_default_templates($templates) {
        $sanitized = [];
        if (!is_array($templates)) {
            return $sanitized;
        }
        
        foreach ($templates as $index => $template) {
            $sanitized[$index] = [
                'name' => isset($template['name']) ? sanitize_text_field($template['name']) : '',
                'form_id' => isset($template['form_id']) ? absint($template['form_id']) : 0,
                'email_settings' => self::sanitize_email_settings($template['email_settings'] ?? [])
            ];
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize custom email templates
     */
    public static function sanitize_custom_templates($templates) {
        $sanitized = [];
        if (!is_array($templates)) {
            return $sanitized;
        }
        
        foreach ($templates as $index => $template) {
            $sanitized[$index] = [
                'name' => isset($template['name']) ? sanitize_text_field($template['name']) : 'Untitled Template',
                'form_id' => isset($template['form_id']) ? absint($template['form_id']) : 0,
                'email_settings' => self::sanitize_email_settings($template['email_settings'] ?? [])
            ];
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize email settings
     */
    public static function sanitize_email_settings($settings) {
        $home_url_host = wp_parse_url(home_url(), PHP_URL_HOST);
        $default_from = get_bloginfo('name') . ' <noreply@' . $home_url_host . '>';
        
        // Allow more HTML tags for email content
        $allowed_html = wp_kses_allowed_html('post');
        $allowed_html['style'] = ['type' => true];
        $allowed_html['div'] = ['class' => true, 'style' => true];
        $allowed_html['table'] = ['class' => true, 'style' => true, 'border' => true, 'cellpadding' => true, 'cellspacing' => true];
        $allowed_html['td'] = ['class' => true, 'style' => true, 'colspan' => true];
        $allowed_html['th'] = ['class' => true, 'style' => true, 'colspan' => true];
        $allowed_html['tr'] = ['class' => true, 'style' => true];
        $allowed_html['tbody'] = ['class' => true, 'style' => true];
        $allowed_html['thead'] = ['class' => true, 'style' => true];
        $allowed_html['a'] = ['href' => true, 'title' => true, 'class' => true, 'style' => true, 'target' => true];
        
        return [
            'to' => isset($settings['to']) ? sanitize_text_field($settings['to']) : '',
            'cc' => isset($settings['cc']) ? sanitize_text_field($settings['cc']) : '',
            'bcc' => isset($settings['bcc']) ? sanitize_text_field($settings['bcc']) : '',
            'subject' => isset($settings['subject']) ? sanitize_text_field($settings['subject']) : '',
            'message' => isset($settings['message']) ? wp_kses($settings['message'], $allowed_html) : '',
            'from' => isset($settings['from']) ? self::sanitize_from_field($settings['from']) : $default_from
        ];
    }
    
    /**
     * Sanitize from field (name <email> format)
     */
    public static function sanitize_from_field($value) {
        $home_url_host = wp_parse_url(home_url(), PHP_URL_HOST);
        $default_from = get_bloginfo('name') . ' <noreply@' . $home_url_host . '>';
        
        if (empty($value)) {
            return $default_from;
        }
        
        if (preg_match('/<([^>]+)>/', $value, $matches)) {
            $email = sanitize_email($matches[1]);
            $name = trim(preg_replace('/<[^>]+>/', '', $value));
            $name = sanitize_text_field($name);
            return $email ? ($name . ' <' . $email . '>') : $default_from;
        }
        
        if (is_email($value)) {
            return sanitize_email($value);
        }
        
        return sanitize_text_field($value);
    }
}
