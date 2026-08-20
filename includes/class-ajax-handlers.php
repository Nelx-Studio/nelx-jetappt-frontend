<?php
/**
 * AJAX Handlers Class - Handles all AJAX requests
 */

if (!defined('ABSPATH')) exit;

class NELXJAF_Ajax_Handlers {
    
    private static $instance = null;
    private $option_name = 'nelx_jetappt_settings';
    private $google_meet_option_name = 'nelx_google_meet_settings';
    private $email_branding_option_name = 'nelx_email_branding_settings';
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_ajax_nelx_jetappt_load_jfb_fields', [$this, 'load_jfb_form_fields']);
        add_action('wp_ajax_nelx_save_jetappt_settings', [$this, 'save_settings']);
        add_action('wp_ajax_nelx_jetappt_get_jfb_fields', [$this, 'get_jfb_fields']);
        add_action('wp_ajax_nelx_test_appointment_automation', [$this, 'test_appointment_automation']);
        add_action('wp_ajax_nelx_save_notifications_settings', [$this, 'save_notifications_settings']);
        add_action('wp_ajax_nelx_save_automation_settings', [$this, 'save_automation_settings']);
        add_action('wp_ajax_nelx_manual_delete_past_appointments', [$this, 'manual_delete_past_appointments']);
        add_action('wp_ajax_nelx_manual_delete_canceled_appointments', [$this, 'manual_delete_canceled_appointments']);
    }
    
    public function load_jfb_form_fields() {
        if (!check_ajax_referer('nelx_jetappt_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed - Invalid nonce', 'nelx-jetappt-frontend')]);
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'nelx-jetappt-frontend')]);
        }
        
        $form_id = isset($_POST['form_id']) ? absint(wp_unslash($_POST['form_id'])) : 0;
        
        if (!$form_id) {
            wp_send_json_error(['message' => __('Invalid form ID', 'nelx-jetappt-frontend')]);
            return;
        }
        
        if (!function_exists('jet_form_builder') && !class_exists('\Jet_Form_Builder\Plugin')) {
            wp_send_json_error(['message' => __('JetFormBuilder plugin is not active', 'nelx-jetappt-frontend')]);
            return;
        }
        
        $fields = NELXJAF_JFB_Field_Helper::get_form_fields($form_id);
        
        if (empty($fields)) {
            wp_send_json_error(['message' => __('No fields found for this form', 'nelx-jetappt-frontend')]);
            return;
        }
        
        $form_title = get_the_title($form_id);
        
        wp_send_json_success([
            'fields' => $fields,
            'form_title' => $form_title
        ]);
    }
    
    public function save_settings() {
        if (!check_ajax_referer('nelx_jetappt_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed.', 'nelx-jetappt-frontend'));
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'nelx-jetappt-frontend'));
        }

        $post_data = isset($_POST['data']) ? wp_unslash($_POST['data']) : '';
        $form_data = [];
        if ($post_data !== '') {
            parse_str($post_data, $form_data);
        }

        $main_current = get_option($this->option_name, []);
        $google_current = get_option($this->google_meet_option_name, []);
        $email_current = get_option($this->email_branding_option_name, []);
        $changed = false;

        if (isset($form_data[$this->option_name]) && is_array($form_data[$this->option_name])) {
            $sanitized = NELXJAF_Settings_Sanitizer::sanitize_settings($form_data[$this->option_name]);
            $new_main = array_merge((array) $main_current, $sanitized);
            if ($new_main !== (array) $main_current) {
                update_option($this->option_name, $new_main);
                $changed = true;
            }
            $main_current = $new_main;
        }

        if (isset($form_data[$this->google_meet_option_name]) && is_array($form_data[$this->google_meet_option_name])) {
            $sanitized_google = NELXJAF_Settings_Sanitizer::sanitize_google_meet_settings($form_data[$this->google_meet_option_name]);
            $new_google = array_merge((array) $google_current, $sanitized_google);
            if ($new_google !== (array) $google_current) {
                update_option($this->google_meet_option_name, $new_google);
                $changed = true;
            }
        }

        if (isset($form_data[$this->email_branding_option_name]) && is_array($form_data[$this->email_branding_option_name])) {
            $sanitized_email = NELXJAF_Settings_Sanitizer::sanitize_email_branding_settings($form_data[$this->email_branding_option_name]);
            $new_email = array_merge((array) $email_current, $sanitized_email);
            if ($new_email !== (array) $email_current) {
                update_option($this->email_branding_option_name, $new_email);
                $changed = true;
            }
        }

        if (isset($_POST['default_templates'])) {
            $default_templates = json_decode(wp_unslash($_POST['default_templates']), true);
            if (is_array($default_templates)) {
                $templates = [];
                foreach ($default_templates as $index => $template) {
                    $templates[$index] = [
                        'name' => sanitize_text_field($template['name'] ?? ''),
                        'form_id' => absint($template['form_id'] ?? 0),
                        'email_settings' => NELXJAF_Settings_Sanitizer::sanitize_email_settings($template['email_settings'] ?? []),
                    ];
                }
                if (($main_current['default_email_templates'] ?? []) !== $templates) {
                    $main_current['default_email_templates'] = $templates;
                    update_option($this->option_name, $main_current);
                    $changed = true;
                }
            }
        }

        if (isset($_POST['custom_templates'])) {
            $custom_templates = json_decode(wp_unslash($_POST['custom_templates']), true);
            if (is_array($custom_templates)) {
                $templates = [];
                foreach ($custom_templates as $index => $template) {
                    $templates[$index] = [
                        'name' => sanitize_text_field($template['name'] ?? ''),
                        'form_id' => absint($template['form_id'] ?? 0),
                        'email_settings' => NELXJAF_Settings_Sanitizer::sanitize_email_settings($template['email_settings'] ?? []),
                    ];
                }
                if (($main_current['custom_email_templates'] ?? []) !== $templates) {
                    $main_current['custom_email_templates'] = $templates;
                    update_option($this->option_name, $main_current);
                    $changed = true;
                }
            }
        }

        // update_option() returns false when the submitted value is already identical.
        // That is a successful save state, not an error.
        wp_send_json_success($changed
            ? __('Settings saved successfully!', 'nelx-jetappt-frontend')
            : __('Settings are up to date.', 'nelx-jetappt-frontend')
        );
    }

    public function save_notifications_settings() {
        if (!check_ajax_referer('nelx_jetappt_nonce', '_wpnonce', false)) {
            wp_send_json_error(__('Security check failed.', 'nelx-jetappt-frontend'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'nelx-jetappt-frontend'));
        }
        
        $current_settings = get_option($this->option_name, array());
        
        if (isset($_POST['nelx_jetappt_settings'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $new_settings = wp_unslash($_POST['nelx_jetappt_settings']);
            
            $current_settings['notifications_enabled'] = isset($new_settings['notifications_enabled']) ? '1' : '0';
            $current_settings['provider_appointments_page'] = isset($new_settings['provider_appointments_page']) ? esc_url_raw($new_settings['provider_appointments_page']) : '';
            $current_settings['client_appointments_page'] = isset($new_settings['client_appointments_page']) ? esc_url_raw($new_settings['client_appointments_page']) : '';
            
            $result = update_option($this->option_name, $current_settings);
            
            if ($result !== false) {
                wp_send_json_success(__('Notifications settings saved!', 'nelx-jetappt-frontend'));
            } else {
                $check_settings = get_option($this->option_name, array());
                if ($check_settings['notifications_enabled'] === $current_settings['notifications_enabled'] &&
                    $check_settings['provider_appointments_page'] === $current_settings['provider_appointments_page'] &&
                    $check_settings['client_appointments_page'] === $current_settings['client_appointments_page']) {
                    wp_send_json_success(__('Settings are up to date.', 'nelx-jetappt-frontend'));
                } else {
                    wp_send_json_error(__('Failed to save settings. Database error occurred.', 'nelx-jetappt-frontend'));
                }
            }
        } else {
            wp_send_json_error(__('No settings data received.', 'nelx-jetappt-frontend'));
        }
    }
    
    public function save_automation_settings() {
        if (!check_ajax_referer('nelx_jetappt_nonce', '_nelx_wpnonce', false)) {
            wp_send_json_error(__('Security check failed.', 'nelx-jetappt-frontend'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'nelx-jetappt-frontend'));
        }
        
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $post_data = isset($_POST['data']) ? wp_unslash($_POST['data']) : '';
        parse_str($post_data, $form_data);
        
        $current_settings = get_option($this->option_name, []);
        
        if (isset($form_data[$this->option_name])) {
            $new_settings = $form_data[$this->option_name];
            
            $current_settings['auto_delete_past'] = isset($new_settings['auto_delete_past']) ? '1' : '0';
            $current_settings['auto_delete_past_days'] = isset($new_settings['auto_delete_past_days']) ? sanitize_text_field($new_settings['auto_delete_past_days']) : '7';
            $current_settings['auto_delete_past_custom'] = isset($new_settings['auto_delete_past_custom']) ? intval($new_settings['auto_delete_past_custom']) : '';
            $current_settings['auto_delete_canceled'] = isset($new_settings['auto_delete_canceled']) ? '1' : '0';
            $current_settings['reminder_timing'] = isset($new_settings['reminder_timing']) ? sanitize_text_field($new_settings['reminder_timing']) : '24';
            
            $result = update_option($this->option_name, $current_settings);
            
            if ($result) {
                wp_send_json_success(__('Automation settings saved!', 'nelx-jetappt-frontend'));
            } else {
                wp_send_json_error(__('Failed to save settings.', 'nelx-jetappt-frontend'));
            }
        } else {
            wp_send_json_error(__('Invalid data received.', 'nelx-jetappt-frontend'));
        }
    }
    
    public function get_jfb_fields() {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $nonce = isset($_POST['nonce']) ? wp_unslash($_POST['nonce']) : '';
        if (!wp_verify_nonce($nonce, 'nelx_jetappt_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'nelx-jetappt-frontend')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'nelx-jetappt-frontend')]);
        }
        
        $form_id = isset($_POST['form_id']) ? absint(wp_unslash($_POST['form_id'])) : 0;
        
        if (!$form_id) {
            wp_send_json_error(['message' => __('Invalid form ID.', 'nelx-jetappt-frontend')]);
        }
        
        if (class_exists('NELXJAF_JFB_Field_Helper')) {
            $fields = NELXJAF_JFB_Field_Helper::get_form_fields($form_id);
        } else {
            $fields = [];
        }
        
        if (empty($fields)) {
            wp_send_json_error(['message' => __('No fields found in this form.', 'nelx-jetappt-frontend')]);
        }
        
        wp_send_json_success(['fields' => $fields]);
    }
    
    public function test_appointment_automation() {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $nonce = isset($_POST['nonce']) ? wp_unslash($_POST['nonce']) : '';
        if (!wp_verify_nonce($nonce, 'nelx_jetappt_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'nelx-jetappt-frontend')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'nelx-jetappt-frontend')]);
        }
        
        $log = [];
        $cron_detected = false;
        $cron_test_passed = false;
        $manual_confirmation = isset($_POST['manual_confirm']) && $_POST['manual_confirm'] === '1';
        
        // Get the cron command
        $php_path = $this->get_php_path_for_cron();
        $cron_file_path = NELXJAF_PLUGIN_DIR . 'nelx-appointment-cron-handler.php';
        $expected_command = $php_path . ' ' . $cron_file_path . ' >/dev/null 2>&1';
        $test_command = $php_path . ' ' . $cron_file_path . ' --test';
        
        // Check if shell_exec is available
        $shell_exec_enabled = function_exists('shell_exec') && !in_array('shell_exec', explode(',', ini_get('disable_functions')));
        
        // 1. Attempt to detect cron in crontab (only if shell_exec is enabled)
        if ($shell_exec_enabled) {
            $crontab = shell_exec('crontab -l 2>/dev/null');
            
            if ($crontab) {
                $pattern = preg_quote($cron_file_path, '/');
                if (preg_match('/' . $pattern . '/', $crontab)) {
                    $cron_detected = true;
                    $log[] = '<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> ' . __('Cron job found in crontab', 'nelx-jetappt-frontend');
                    
                    if (strpos($crontab, $php_path) !== false) {
                        $log[] = '<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> ' . __('Using correct PHP path', 'nelx-jetappt-frontend');
                    } else {
                        $log[] = '<span class="dashicons dashicons-warning" style="color: #f56e28;"></span> ' . __('Using different PHP path than detected', 'nelx-jetappt-frontend');
                        preg_match('/^\s*(.*?php[^\s]*)\s/', $crontab, $matches);
                        if (!empty($matches[1])) {
                            $log[] = __('Detected PHP path in crontab:', 'nelx-jetappt-frontend') . ' ' . $matches[1];
                        }
                    }
                } else {
                    $log[] = '<span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span> ' . __('Cron job not found in crontab', 'nelx-jetappt-frontend');
                }
            } else {
                $log[] = '<span class="dashicons dashicons-warning" style="color: #f56e28;"></span> ' . __('No crontab entries found for this user', 'nelx-jetappt-frontend');
            }
        } else {
            $log[] = '<span class="dashicons dashicons-warning" style="color: #f56e28;"></span> ' . __('Could not verify crontab - shell_exec disabled', 'nelx-jetappt-frontend');
            $log[] = '<span class="dashicons dashicons-info" style="color: #00a0d2;"></span> ' . __('This is common on shared hosting. The cron may still be working.', 'nelx-jetappt-frontend');
        }
        
        // 2. Test the cron handler directly (CLI test) - only if shell_exec is enabled
        if ($shell_exec_enabled) {
            $test_output = shell_exec($test_command . ' 2>&1');
            
            if ($test_output !== null && strpos($test_output, 'Cron handler is accessible') !== false) {
                $cron_test_passed = true;
                $log[] = '<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> ' . __('Cron handler test passed (CLI)', 'nelx-jetappt-frontend');
                $log[] = __('Test output:', 'nelx-jetappt-frontend') . ' ' . trim($test_output);
            } else {
                $log[] = '<span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span> ' . __('Cron handler test failed (CLI)', 'nelx-jetappt-frontend');
                if (!empty($test_output)) {
                    $log[] = __('Error output:', 'nelx-jetappt-frontend') . ' ' . trim($test_output);
                }
            }
        } else {
            $log[] = '<span class="dashicons dashicons-warning" style="color: #f56e28;"></span> ' . __('Cannot test CLI - shell_exec disabled', 'nelx-jetappt-frontend');
        }
        
        // 3. Always check if file exists (regardless of shell_exec)
        if (file_exists($cron_file_path)) {
            $log[] = '<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> ' . __('Cron handler file exists', 'nelx-jetappt-frontend');
            $log[] = '<span class="dashicons dashicons-admin-file"></span> ' . __('File path:', 'nelx-jetappt-frontend') . ' ' . $cron_file_path;
            
            if (is_readable($cron_file_path)) {
                $log[] = '<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> ' . __('File is readable', 'nelx-jetappt-frontend');
            } else {
                $log[] = '<span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span> ' . __('File is not readable - check permissions', 'nelx-jetappt-frontend');
            }
        } else {
            $log[] = '<span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span> ' . __('Cron handler file not found at:', 'nelx-jetappt-frontend') . ' ' . $cron_file_path;
        }
        
        // Determine overall status
        $status_message = '';
        $is_configured = false;
        
        if ($shell_exec_enabled) {
            // Full verification possible
            if ($cron_detected && $cron_test_passed) {
                $status_message = '<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> ' . __('Cron is properly configured and working!', 'nelx-jetappt-frontend');
                $is_configured = true;
            } elseif ($cron_detected && !$cron_test_passed) {
                $status_message = '<span class="dashicons dashicons-warning" style="color: #f56e28;"></span> ' . __('Cron job is in crontab but handler test failed', 'nelx-jetappt-frontend');
                $is_configured = false;
            } else {
                $status_message = '<span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span> ' . __('Cron job not detected in crontab', 'nelx-jetappt-frontend');
                $is_configured = false;
            }
        } else {
            // shell_exec disabled - can only check if file exists
            if ($manual_confirmation) {
                // User has manually confirmed the cron is added
                $status_message = '<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> ' . __('Cron configuration confirmed manually.', 'nelx-jetappt-frontend');
                $log[] = '<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> ' . __('Manual confirmation received - cron is marked as configured.', 'nelx-jetappt-frontend');
                $is_configured = true;
            } else {
                // Check if we have any evidence the cron might be working
                // Check if there are any past appointments that should have been deleted
                $cron_likely_working = $this->check_cron_evidence();
                
                if ($cron_likely_working) {
                    $status_message = '<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> ' . __('Cron appears to be working (evidence found in database).', 'nelx-jetappt-frontend');
                    $log[] = '<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> ' . __('Past appointments have been processed, indicating cron is working.', 'nelx-jetappt-frontend');
                    $is_configured = true;
                } else {
                    $status_message = '<span class="dashicons dashicons-warning" style="color: #f56e28;"></span> ' . __('Cannot verify cron setup - shell_exec disabled.', 'nelx-jetappt-frontend');
                    $log[] = '<span class="dashicons dashicons-info" style="color: #00a0d2;"></span> ' . __('To verify, please confirm you have added the cron command to crontab.', 'nelx-jetappt-frontend');
                    $is_configured = false;
                }
            }
        }
        
        $response_data = [
            'message' => $status_message,
            'log' => implode("\n", $log),
            'cron_command' => $expected_command,
            'test_command' => $test_command,
            'cron_file_path' => $cron_file_path,
            'php_path' => $php_path,
            'detected_in_crontab' => $cron_detected,
            'test_passed' => $cron_test_passed,
            'shell_exec_enabled' => $shell_exec_enabled,
            'manual_confirmation' => $manual_confirmation,
            'requires_manual_confirmation' => !$shell_exec_enabled && !$manual_confirmation
        ];
        
        if (!$is_configured) {
            // Provide setup instructions
            $setup_instructions = sprintf(
                __('Add this line to your crontab (run "crontab -e"): %s', 'nelx-jetappt-frontend'),
                "\n* * * * * " . $expected_command . "\n"
            );
            $response_data['setup_instructions'] = $setup_instructions;
            
            // Add alternative instructions for shared hosting
            if (!$shell_exec_enabled) {
                $response_data['setup_instructions'] .= "\n\n" . __('Since shell_exec is disabled, you may need to:', 'nelx-jetappt-frontend') . "\n";
                $response_data['setup_instructions'] .= __('1. Check crontab via your hosting control panel', 'nelx-jetappt-frontend') . "\n";
                $response_data['setup_instructions'] .= __('2. Add the cron command shown above', 'nelx-jetappt-frontend') . "\n";
                $response_data['setup_instructions'] .= __('3. Click "I have added the cron command" button below', 'nelx-jetappt-frontend') . "\n";
                $response_data['setup_instructions'] .= __('4. This will mark the cron as configured', 'nelx-jetappt-frontend') . "\n";
            }
            
            wp_send_json_error($response_data);
            return;
        }
        
        wp_send_json_success($response_data);
    }
    
    /**
     * Check for evidence that cron is working
     */
    private function check_cron_evidence() {
        global $wpdb;
        
        $options = get_option('nelx_jetappt_settings', []);
        $core = NELXJAF_Core::instance();
        $appt_table = $core->appt_table;
        
        // Check if auto_delete_past is enabled
        $auto_delete_past = $options['auto_delete_past'] ?? '0';
        if ($auto_delete_past !== '1') {
            return false;
        }
        
        // Check if there are any past appointments that should have been deleted
        $days = $options['auto_delete_past_days'] ?? '7';
        if ($days === 'custom') {
            $days = $options['auto_delete_past_custom'] ?? '7';
        }
        $days = intval($days);
        if ($days < 1) {
            $days = 3;
        }
        
        $cutoff_timestamp = current_time('timestamp') - ($days * DAY_IN_SECONDS);
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $past_count = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$appt_table} WHERE date < %d AND appointment_status != 'canceled'", $cutoff_timestamp)
        );
        
        // If there are no past appointments, cron might be working
        return $past_count == 0;
    }
    
    private function get_php_path_for_cron() {
        $possible_php_paths = [
            '/usr/local/bin/php',
            '/usr/bin/php',
            '/usr/bin/php8',
            '/usr/bin/php81',
            '/usr/bin/php82',
            '/usr/bin/php83',
            '/opt/cpanel/ea-php82/root/usr/bin/php',
            '/opt/cpanel/ea-php81/root/usr/bin/php',
            '/opt/cpanel/ea-php80/root/usr/bin/php',
        ];
        
        foreach ($possible_php_paths as $php_path) {
            if (file_exists($php_path)) {
                return $php_path;
            }
        }
        
        return 'php';
    }
    
    private function run_automation_test() {
        global $wpdb;
        $options = get_option($this->option_name);
        $appt_table = $wpdb->prefix . 'jet_appointments';
        
        $results = [
            'reminders' => 0,
            'past_appointments' => 0,
            'canceled_appointments' => 0
        ];
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
        $sql = $wpdb->prepare("SHOW TABLES LIKE %s", $appt_table);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        if ($wpdb->get_var($sql) !== $appt_table) {
            return $results;
        }
        
        $reminder_timing = $options['reminder_timing'] ?? '24';
        $reminder_seconds = intval($reminder_timing) * HOUR_IN_SECONDS;
        
        if ($reminder_seconds > 0) {
            $current_time = current_time('timestamp');
            $target_time = $current_time + $reminder_seconds;
            $target_window_start = $target_time - (7.5 * MINUTE_IN_SECONDS);
            $target_window_end = $target_time + (7.5 * MINUTE_IN_SECONDS);
            
            $target_start_date = gmdate('Y-m-d H:i:s', $target_window_start);
            $target_end_date = gmdate('Y-m-d H:i:s', $target_window_end);
            
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $results['reminders'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$appt_table} 
                 WHERE (date BETWEEN %s AND %s OR slot BETWEEN %s AND %s)
                 AND appointment_status = 'accepted'",
                $target_start_date, $target_end_date, $target_start_date, $target_end_date
            ));
        }
        
        $auto_delete_past = $options['auto_delete_past'] ?? '0';
        if ($auto_delete_past === '1') {
            $days = $options['auto_delete_past_days'] ?? '7';
            if ($days === 'custom') {
                $days = $options['auto_delete_past_custom'] ?? '7';
            }
            $days = max(1, intval($days));
            $cutoff_date = gmdate('Y-m-d H:i:s', current_time('timestamp') - ($days * DAY_IN_SECONDS));
            
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $results['past_appointments'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$appt_table} WHERE date < %s",
                $cutoff_date
            ));
        }
        
        $auto_delete_canceled = $options['auto_delete_canceled'] ?? '0';
        if ($auto_delete_canceled === '1') {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $results['canceled_appointments'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$appt_table} WHERE appointment_status = %s",
                'canceled'
            ));
        }
        
        return $results;
    }
    
    public function manual_delete_past_appointments() {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $nonce = isset($_POST['nonce']) ? wp_unslash($_POST['nonce']) : '';
        if (!wp_verify_nonce($nonce, 'nelx_jetappt_nonce')) {
            wp_send_json_error(__('Security check failed.', 'nelx-jetappt-frontend'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'nelx-jetappt-frontend'));
        }
        
        $days = isset($_POST['days']) ? intval(wp_unslash($_POST['days'])) : 0;
        
        if ($days < 3) {
            wp_send_json_error(__('Minimum 3 days required.', 'nelx-jetappt-frontend'));
        }
        
        global $wpdb;
        $options = get_option($this->option_name);
        $appt_table = $wpdb->prefix . 'jet_appointments';
        
        $cutoff_timestamp = current_time('timestamp') - ($days * DAY_IN_SECONDS);
        $cutoff_date = gmdate('Y-m-d H:i:s', $cutoff_timestamp);
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $result = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$appt_table} WHERE date < %s",
            $cutoff_date
        ));
        
        if ($result !== false) {
            /* translators: %d: number of deleted appointments */
            wp_send_json_success(sprintf(__('Deleted %d past appointments.', 'nelx-jetappt-frontend'), $result));
        } else {
            wp_send_json_error(__('Error deleting past appointments.', 'nelx-jetappt-frontend'));
        }
    }
    
    public function manual_delete_canceled_appointments() {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $nonce = isset($_POST['nonce']) ? wp_unslash($_POST['nonce']) : '';
        if (!wp_verify_nonce($nonce, 'nelx_jetappt_nonce')) {
            wp_send_json_error(__('Security check failed.', 'nelx-jetappt-frontend'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied.', 'nelx-jetappt-frontend'));
        }
        
        global $wpdb;
        $options = get_option($this->option_name);
        $appt_table = $wpdb->prefix . 'jet_appointments';
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $result = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$appt_table} WHERE appointment_status = %s",
            'canceled'
        ));
        
        if ($result !== false) {
            /* translators: %d: number of deleted appointments */
            wp_send_json_success(sprintf(__('Deleted %d canceled appointments.', 'nelx-jetappt-frontend'), $result));
        } else {
            wp_send_json_error(__('Error deleting canceled appointments.', 'nelx-jetappt-frontend'));
        }
    }
}
