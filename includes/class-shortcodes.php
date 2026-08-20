<?php
/**
 * Shortcodes Class
 */

if (!defined('ABSPATH')) exit;

class NELXJAF_Shortcodes {
    
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
        
        add_action('wp_footer', [$this, 'register_modals'], 5);
    }
    
    public function register_modals() {
        if (!class_exists('NELXJAF_Modal_Manager')) {
            return;
        }
        
        $modal_manager = NELXJAF_Modal_Manager::instance();
        
        $modal_manager->register_modal('days_off', [
            'title' => __('Days Off', 'nelx-jetappt-frontend'),
            'content' => $this->get_days_off_modal_content(),
            'size' => 'medium',
            'close_on_backdrop' => true,
            'close_on_esc' => true
        ]);
        
        $modal_manager->register_modal('working_days', [
            'title' => __('Working Days (custom)', 'nelx-jetappt-frontend'),
            'content' => $this->get_working_days_modal_content(),
            'size' => 'medium',
            'close_on_backdrop' => true,
            'close_on_esc' => true
        ]);
        
        $modal_manager->register_modal('edit_day', [
            'title' => __('Edit Day', 'nelx-jetappt-frontend'),
            'content' => $this->get_edit_day_modal_content(),
            'size' => 'medium',
            'close_on_backdrop' => true,
            'close_on_esc' => true
        ]);
        
        $modal_manager->register_modal('reschedule', [
            'title' => __('Reschedule Appointment', 'nelx-jetappt-frontend'),
            'content' => $this->get_reschedule_modal_content(),
            'size' => 'medium',
            'close_on_backdrop' => true,
            'close_on_esc' => true
        ]);
        
        $modal_manager->register_modal('appointment_info', [
            'title' => __('Appointment Information', 'nelx-jetappt-frontend'),
            'content' => $this->get_appointment_info_modal_content(),
            'size' => 'large',
            'close_on_backdrop' => true,
            'close_on_esc' => true
        ]);
    }
    
    private function get_days_off_modal_content() {
        ob_start();
        ?>
        <div class="nelx-modal-days-off">
            <div class="nelx-field">
                <label><?php esc_html_e('Days Name', 'nelx-jetappt-frontend'); ?></label>
                <input class="nelx-input" id="nelx_do_name" type="text">
            </div>
            <div class="nelx-field">
                <label><?php esc_html_e('Start Date *', 'nelx-jetappt-frontend'); ?></label>
                <input class="nelx-input nelx-date" id="nelx_do_start" type="text">
            </div>
            <div class="nelx-field">
                <label><?php esc_html_e('End Date', 'nelx-jetappt-frontend'); ?></label>
                <input class="nelx-input nelx-date" id="nelx_do_end" type="text">
            </div>
            <button class="nelx-btn nelx-primary" id="nelx_do_save" style="margin-top: 15px;"><?php esc_html_e('Save', 'nelx-jetappt-frontend'); ?></button>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function get_working_days_modal_content() {
        ob_start();
        ?>
        <div class="nelx-modal-working-days">
            <div class="nelx-field">
                <label><?php esc_html_e('Days Name', 'nelx-jetappt-frontend'); ?></label>
                <input class="nelx-input" id="nelx_wd_name" type="text">
            </div>
            <div class="nelx-field">
                <label><?php esc_html_e('Start Date *', 'nelx-jetappt-frontend'); ?></label>
                <input class="nelx-input nelx-date" id="nelx_wd_start" type="text">
            </div>
            <div class="nelx-field">
                <label><?php esc_html_e('End Date', 'nelx-jetappt-frontend'); ?></label>
                <input class="nelx-input nelx-date" id="nelx_wd_end" type="text">
            </div>
            <div class="nelx-subtitle"><?php esc_html_e('Custom Schedule', 'nelx-jetappt-frontend'); ?></div>
            <div id="nelx_wd_slots" class="nelx-slots"></div>
            <button type="button" class="nelx-btn nelx-outline nelx-small nelx-wd-add-slot" style="margin-top: 12px;">+ <?php esc_html_e('Add slot', 'nelx-jetappt-frontend'); ?></button>
            <button class="nelx-btn nelx-primary" id="nelx_wd_save" style="margin-top: 15px;"><?php esc_html_e('Save', 'nelx-jetappt-frontend'); ?></button>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function get_edit_day_modal_content() {
        ob_start();
        ?>
        <div class="nelx-modal-edit-day">
            <div class="nelx-field">
                <label><?php esc_html_e('Days Name', 'nelx-jetappt-frontend'); ?></label>
                <input class="nelx-input" id="edit_name" type="text">
            </div>
            <div class="nelx-field">
                <label><?php esc_html_e('Start Date *', 'nelx-jetappt-frontend'); ?></label>
                <input class="nelx-input nelx-date" id="edit_start" type="text">
            </div>
            <div class="nelx-field">
                <label><?php esc_html_e('End Date', 'nelx-jetappt-frontend'); ?></label>
                <input class="nelx-input nelx-date" id="edit_end" type="text">
            </div>
            <div class="nelx-subtitle nelx-edit-schedule-section" style="display: none;"><?php esc_html_e('Custom Schedule', 'nelx-jetappt-frontend'); ?></div>
            <div id="edit_slots" class="nelx-slots" style="display: none;"></div>
            <button type="button" class="nelx-btn nelx-outline nelx-small nelx-edit-add-slot" style="margin-top: 12px; display: none;">+ <?php esc_html_e('Add slot', 'nelx-jetappt-frontend'); ?></button>
            <button class="nelx-btn nelx-primary" id="edit_save" style="margin-top: 15px;"><?php esc_html_e('Save', 'nelx-jetappt-frontend'); ?></button>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function get_reschedule_modal_content() {
        ob_start();
        ?>
        <div class="nelx-modal-reschedule">
            <div class="nelx-reschedule-loading">
                <div class="nelx-skeleton-line" style="height: 40px; margin-bottom: 16px;"></div>
                <div class="nelx-skeleton-line" style="height: 40px; margin-bottom: 16px;"></div>
                <div class="nelx-skeleton-line" style="height: 40px;"></div>
            </div>
            <div class="nelx-reschedule-form" style="display: none;"></div>
            <button class="nelx-btn nelx-primary" id="edit_save" style="margin-top: 15px; display: none;"><?php esc_html_e('Save Changes', 'nelx-jetappt-frontend'); ?></button>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function get_appointment_info_modal_content() {
        ob_start();
        ?>
        <div class="nelx-modal-appointment-info">
            <div class="nelx-info-grid nelx-info-loading">
                <div class="nelx-skeleton-line" style="height: 20px;"></div>
                <div class="nelx-skeleton-line" style="height: 20px;"></div>
                <div class="nelx-skeleton-line" style="height: 20px;"></div>
                <div class="nelx-skeleton-line" style="height: 20px;"></div>
            </div>
            <div class="nelx-info-content" style="display: none;"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function schedule_editor($atts) {
        if (!is_user_logged_in()) {
            return '<div class="nelx-notice">' . esc_html__('Login required.', 'nelx-jetappt-frontend') . '</div>';
        }
        
        $uid = get_current_user_id();
        $post_id = $this->core->get_provider_post_id_for_user($uid);
        
        if (!$post_id) {
            return '<div class="nelx-notice">' . esc_html__('No provider profile linked to this user.', 'nelx-jetappt-frontend') . '</div>';
        }
        
        $meta = $this->core->load_provider_meta($post_id);
        $custom = $meta['custom_schedule'] ?? [];
        $use_custom = !empty($custom['use_custom_schedule']) || !empty($meta['use_custom_schedule']);
        
        ob_start();
        ?>
        <div class="nelx-schedule-editor" data-provider-post="<?php echo esc_attr($post_id); ?>">
            <div class="nelx-sched-head" style="margin-bottom:30px;">
                <label class="nelx-switch">
                    <input type="checkbox" class="nelx-switch-input" id="nelx_use_custom_schedule" <?php echo checked($use_custom, true, false); ?>>
                    <span class="nelx-switch-slider" aria-hidden="true"></span>
                </label>
                <span class="nelx-switch-label"><?php esc_html_e('Use Custom Schedule', 'nelx-jetappt-frontend'); ?></span>
                <button type="button" class="nelx-btn nelx-primary nelx-save-top" style="<?php echo $use_custom ? '' : 'display:none'; ?>">
                    <span class="nelx-btn-text"><?php esc_html_e('Save Changes', 'nelx-jetappt-frontend'); ?></span>
                    <span class="nelx-spinner" aria-hidden="true" style="display:none"></span>
                </button>
            </div>
            <div class="nelx-sched-body" style="<?php echo $use_custom ? '' : 'display:none'; ?>">
                <?php $this->render_schedule_form($custom); ?>
                <div class="nelx-actions">
                    <button type="button" class="nelx-btn nelx-primary nelx-save-bottom">
                        <span class="nelx-btn-text"><?php esc_html_e('Save Changes', 'nelx-jetappt-frontend'); ?></span>
                        <span class="nelx-spinner" aria-hidden="true" style="display:none"></span>
                    </button>
                    <div class="nelx-inline-msg" role="status" aria-live="polite"></div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function render_schedule_form($custom) {
        $default_slot = isset($custom['default_slot']) ? $this->core->seconds_to_hhmm($custom['default_slot']) : '00:30';
        $buffer_before = isset($custom['buffer_before']) ? $this->core->seconds_to_hhmm($custom['buffer_before']) : '00:00';
        $buffer_after = isset($custom['buffer_after']) ? $this->core->seconds_to_hhmm($custom['buffer_after']) : '00:00';
        $locked_time = isset($custom['locked_time']) ? $this->core->seconds_to_hhmm($custom['locked_time']) : '00:00';
        $appt_range = $custom['appointments_range'] ?? ['type' => 'all', 'range_num' => '60', 'range_unit' => 'days'];
        $working_days_mode = $custom['working_days_mode'] ?? 'default';
        ?>
        <div class="nelx-grid nelx-schedule-row">
            <div class="nelx-field">
                <label><?php esc_html_e('Duration', 'nelx-jetappt-frontend'); ?></label>
                <div class="nelx-time-dropdown" id="nelx_default_slot" data-value="<?php echo esc_attr($default_slot); ?>">
                    <div class="nelx-skeleton-line" style="height: 40px; width: 100%;"></div>
                </div>
                <small><?php esc_html_e('Default slot duration (HH:MM)', 'nelx-jetappt-frontend'); ?></small>
            </div>
            <div class="nelx-field">
                <label><?php esc_html_e('Locked Time Before', 'nelx-jetappt-frontend'); ?></label>
                <div class="nelx-time-dropdown" id="nelx_locked_time" data-value="<?php echo esc_attr($locked_time); ?>">
                    <div class="nelx-skeleton-line" style="height: 40px; width: 100%;"></div>
                </div>
                <small><?php esc_html_e('Minimum lead time before booking (HH:MM)', 'nelx-jetappt-frontend'); ?></small>
            </div>
            <div class="nelx-field">
                <label><?php esc_html_e('Buffer Time Before', 'nelx-jetappt-frontend'); ?></label>
                <div class="nelx-time-dropdown" id="nelx_buffer_before" data-value="<?php echo esc_attr($buffer_before); ?>">
                    <div class="nelx-skeleton-line" style="height: 40px; width: 100%;"></div>
                </div>
            </div>
            <div class="nelx-field">
                <label><?php esc_html_e('Buffer Time After', 'nelx-jetappt-frontend'); ?></label>
                <div class="nelx-time-dropdown" id="nelx_buffer_after" data-value="<?php echo esc_attr($buffer_after); ?>">
                    <div class="nelx-skeleton-line" style="height: 40px; width: 100%;"></div>
                </div>
            </div>
        </div>
        
        <div class="nelx-field nelx-appt-range">
            <label><?php esc_html_e('When users can book appointments', 'nelx-jetappt-frontend'); ?></label>
            <div class="nelx-radios">
                <label><input type="radio" name="nelx_appt_range_type" value="all" <?php echo checked($appt_range['type'] ?? 'all', 'all', false); ?>> <?php esc_html_e('Any date in the future', 'nelx-jetappt-frontend'); ?></label>
                <label><input type="radio" name="nelx_appt_range_type" value="range" <?php echo checked($appt_range['type'] ?? 'all', 'range', false); ?>> <?php esc_html_e('Limited range from current date', 'nelx-jetappt-frontend'); ?></label>
            </div>
            <div class="nelx-range-opts" style="<?php echo ($appt_range['type'] ?? 'all') === 'range' ? '' : 'display:none'; ?>">
                <input type="number" id="nelx_range_num" min="1" value="<?php echo esc_attr($appt_range['range_num'] ?? '60'); ?>" style="max-width:120px">
                <select id="nelx_range_unit">
                    <option value="days" <?php selected($appt_range['range_unit'] ?? 'days', 'days'); ?>><?php esc_html_e('Day(s)', 'nelx-jetappt-frontend'); ?></option>
                    <option value="months" <?php selected($appt_range['range_unit'] ?? 'days', 'months'); ?>><?php esc_html_e('Month(s)', 'nelx-jetappt-frontend'); ?></option>
                    <option value="years" <?php selected($appt_range['range_unit'] ?? 'days', 'years'); ?>><?php esc_html_e('Year(s)', 'nelx-jetappt-frontend'); ?></option>
                </select>
            </div>
            <small><?php esc_html_e('Set range of dates when you accept new appointments', 'nelx-jetappt-frontend'); ?></small>
        </div>
        
        <div class="nelx-grid nelx-col-2">
            <div class="nelx-card">
                <div class="nelx-card-head"><?php esc_html_e('Work Hours', 'nelx-jetappt-frontend'); ?></div>
                <div id="nelx_work_hours" class="nelx-work-hours">
                    <?php
                    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                    foreach ($days as $day) :
                        $has_existing = isset($custom['working_hours'][$day]) && !empty($custom['working_hours'][$day]);
                        
                        if ($has_existing) {
                            $day_slots = $custom['working_hours'][$day];
                        } else {
                            $day_slots = [];
                        }
                    ?>
                        <div class="nelx-day" data-day="<?php echo esc_attr($day); ?>">
                            <div class="nelx-day-title"><?php echo esc_html(ucfirst($day)); ?></div>
                            <div class="nelx-slots" data-has-slots="<?php echo !empty($day_slots) ? 'true' : 'false'; ?>">
                                <?php if (empty($day_slots)) : ?>
                                    <div class="nelx-empty-slots-message">
                                        <?php esc_html_e('No working hours', 'nelx-jetappt-frontend'); ?>
                                    </div>
                                <?php else : ?>
                                    <?php foreach ($day_slots as $slot) : ?>
                                        <div class="nelx-slot-row">
                                            <div class="nelx-time-dropdown" data-value="<?php echo esc_attr($slot['from']); ?>">
                                                <div class="nelx-skeleton-line" style="height: 40px; width: 100%;"></div>
                                            </div>
                                            <span class="sep">–</span>
                                            <div class="nelx-time-dropdown" data-value="<?php echo esc_attr($slot['to']); ?>">
                                                <div class="nelx-skeleton-line" style="height: 40px; width: 100%;"></div>
                                            </div>
                                            <button type="button" class="nelx-icon-btn nelx-danger nelx-remove-slot" title="<?php esc_attr_e('Remove', 'nelx-jetappt-frontend'); ?>">
                                                <svg viewBox="0 0 24 24"><path d="M6 7h12l-1 14H7L6 7zm3-3h6l1 2H8l1-2zM4 7h16v2H4z"/></svg>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="nelx-btn nelx-outline nelx-small nelx-add-slot" data-day="<?php echo esc_attr($day); ?>">+ <?php esc_html_e('Add', 'nelx-jetappt-frontend'); ?></button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="nelx-card">
                <div class="nelx-card-head"><?php esc_html_e('Days Off', 'nelx-jetappt-frontend'); ?></div>
                <div id="nelx_days_off_list" class="nelx-tags">
                    <?php
                    if (!empty($custom['days_off']) && is_array($custom['days_off'])) {
                        foreach ($custom['days_off'] as $idx => $it) {
                            $name = $it['name'] ?? '';
                            $start = $this->core->timestamp_to_date($it['startTimeStamp'] ?? ($it['start'] ? strtotime($it['start']) * 1000 : ''));
                            $end = $this->core->timestamp_to_date($it['endTimeStamp'] ?? ($it['end'] ? strtotime($it['end']) * 1000 : ''));
                            $json = wp_json_encode($it);
                            echo '<span class="nelx-tag" data-index="' . intval($idx) . '" data-item="' . esc_attr($json) . '">';
                            echo esc_html($name) . ' — ' . esc_html($start . ($end ? ' – ' . $end : ''));
                            echo ' <button type="button" class="nelx-icon-btn nelx-edit-day" title="Edit"><svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>';
                            echo ' <button type="button" class="nelx-icon-btn x" title="Remove">&times;</button></span>';
                        }
                    }
                    ?>
                </div>
                <button type="button" class="nelx-btn nelx-outline nelx-small" id="nelx_add_day_off"><?php esc_html_e('Add Days', 'nelx-jetappt-frontend'); ?></button>
                
                <div class="nelx-divider"></div>
                
                <div class="nelx-card-head"><?php esc_html_e('Working Days', 'nelx-jetappt-frontend'); ?></div>
                
                <label class="nelx-select-label"><?php esc_html_e('Mode', 'nelx-jetappt-frontend'); ?></label>
                <select id="nelx_working_days_mode">
                    <option value="default" <?php selected($working_days_mode, 'default'); ?>><?php esc_html_e('Use default weekly schedule', 'nelx-jetappt-frontend'); ?></option>
                    <option value="override_full" <?php selected($working_days_mode, 'override_full'); ?>><?php esc_html_e('Override all schedule with new days', 'nelx-jetappt-frontend'); ?></option>
                    <option value="override_days" <?php selected($working_days_mode, 'override_days'); ?>><?php esc_html_e('Override only days added below', 'nelx-jetappt-frontend'); ?></option>
                </select>
                <div id="nelx_working_days_note" style="display:<?php echo in_array($working_days_mode, ['override_partial', 'override_days']) ? 'block' : 'none'; ?>;margin-top:8px;color:#6b7280;">
                    <small><?php esc_html_e('Add dates when your availability is different from your regular weekly hours.', 'nelx-jetappt-frontend'); ?></small>
                </div>
                
                <div id="nelx_working_days_list" class="nelx-tags" style="margin-top:12px;">
                    <?php
                    if (!empty($custom['working_days']) && is_array($custom['working_days'])) {
                        foreach ($custom['working_days'] as $idx => $it) {
                            $name = $it['name'] ?? '';
                            $start = $this->core->timestamp_to_date($it['startTimeStamp'] ?? ($it['start'] ? strtotime($it['start']) * 1000 : ''));
                            $end = $this->core->timestamp_to_date($it['endTimeStamp'] ?? ($it['end'] ? strtotime($it['end']) * 1000 : ''));
                            $sub = $start . ($end ? ' – ' . $end : '');
                            if (!empty($it['schedule']) && is_array($it['schedule'])) {
                                $schedule_parts = array_map(function($s) { 
                                    return ($s['from'] ?? '') . '-' . ($s['to'] ?? ''); 
                                }, $it['schedule']);
                                $sub .= ' • ' . implode(', ', $schedule_parts);
                            }
                            $json = wp_json_encode($it);
                            echo '<span class="nelx-tag" data-index="' . intval($idx) . '" data-item="' . esc_attr($json) . '">' . esc_html($name) . ' — ' . esc_html($sub);
                            echo ' <button type="button" class="nelx-icon-btn nelx-edit-day" title="Edit"><svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg></button>';
                            echo ' <button type="button" class="nelx-icon-btn x" title="Remove">&times;</button></span>';
                        }
                    }
                    ?>
                </div>
                <button type="button" class="nelx-btn nelx-outline nelx-small" id="nelx_add_working_days"><?php esc_html_e('Add Days', 'nelx-jetappt-frontend'); ?></button>
            </div>
        </div>
        <?php
    }
    
    public function provider_actions($atts) {
        $atts = shortcode_atts(['appointment_id' => ''], $atts, 'nelx_provider_action_buttons');
        $appointment_id = $atts['appointment_id'] ? intval($atts['appointment_id']) : 0;
        
        $data_attr = $appointment_id ? ' data-appointment="' . esc_attr($appointment_id) . '"' : ' data-need-id="1" ';
        
        $status = 'pending';
        $appointment_date = 0;
        $is_past = false;
        
        if ($appointment_id) {
            $appointment = $this->core->wpdb->get_row(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $this->core->wpdb->prepare(
                    "SELECT appointment_status, date FROM {$this->core->appt_table} WHERE ID = %d LIMIT 1",
                    $appointment_id
                ),
                ARRAY_A
            );
            
            $status = !empty($appointment['appointment_status']) ? $appointment['appointment_status'] : 'pending';
            $appointment_date = $appointment['date'] ?? 0;
            $today_start = strtotime('today midnight');
            $is_past = $appointment_date && ($appointment_date < $today_start);
        }
        
        $disable_action_buttons = in_array($status, ['accepted', 'canceled']);
        $disable_edit_button = $is_past;
        
        $confirm_url = '';
        $cancel_url = '';
        if ($appointment_id) {
            $rows = $this->core->wpdb->get_results(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $this->core->wpdb->prepare("SELECT meta_key, meta_value FROM {$this->core->appt_meta_table} WHERE appointment_id = %d", $appointment_id),
                ARRAY_A
            );
            foreach ($rows as $r) {
                if ($r['meta_key'] === '_confirm_url') $confirm_url = $r['meta_value'];
                if ($r['meta_key'] === '_cancel_url') $cancel_url = $r['meta_value'];
            }
        }
        
        ob_start();
        ?>
        <div class="nelx-actions-inline"<?php echo $data_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-status="<?php echo esc_attr($status); ?>" data-is-past="<?php echo $is_past ? '1' : '0'; ?>">
            <button type="button" class="nelx-icon-btn nelx-edit" data-action="edit" title="<?php esc_attr_e('Reschedule', 'nelx-jetappt-frontend'); ?>" <?php echo $disable_edit_button ? 'disabled' : ''; ?>>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
            </button>
            
            <button type="button" class="nelx-icon-btn nelx-confirm" data-action="confirm" data-status="accepted" title="<?php esc_attr_e('Confirm', 'nelx-jetappt-frontend'); ?>" <?php echo $disable_action_buttons ? 'disabled' : ''; ?>>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 16.17L4.83 12 3.41 13.41 9 19 21 7 19.59 5.59z"/></svg>
            </button>
            
            <button type="button" class="nelx-icon-btn nelx-reject" data-action="cancel" data-status="canceled" title="<?php esc_attr_e('Cancel', 'nelx-jetappt-frontend'); ?>" <?php echo $disable_action_buttons ? 'disabled' : ''; ?>>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
            
            <button type="button" class="nelx-icon-btn nelx-info" data-action="info" title="<?php esc_attr_e('Info', 'nelx-jetappt-frontend'); ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 7h2v2h-2V7zm0 4h2v6h-2v-6zm1-9C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/></svg>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function client_actions($atts) {
        $atts = shortcode_atts(['appointment_id' => ''], $atts, 'nelx_client_action_buttons');
        $appointment_id = $atts['appointment_id'] ? intval($atts['appointment_id']) : 0;
        
        $data_attr = $appointment_id ? ' data-appointment="' . esc_attr($appointment_id) . '" data-appointment-id="' . esc_attr($appointment_id) . '"' : ' data-need-id="1" ';
        
        $status = 'pending';
        $appointment_date = 0;
        $is_past = false;
        $user_id = 0;
        
        if ($appointment_id) {
            $appointment = $this->core->wpdb->get_row(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $this->core->wpdb->prepare(
                    "SELECT appointment_status, date, user_id FROM {$this->core->appt_table} WHERE ID = %d LIMIT 1",
                    $appointment_id
                ),
                ARRAY_A
            );
            
            if (!$appointment) {
                return '<!-- invalid appointment id -->';
            }
            
            $current_user_id = get_current_user_id();
            if ($current_user_id != $appointment['user_id']) {
                return '<!-- user not authorized for this appointment -->';
            }
            
            $status = !empty($appointment['appointment_status']) ? $appointment['appointment_status'] : 'pending';
            $appointment_date = $appointment['date'] ?? 0;
            $today_start = strtotime('today midnight');
            $is_past = $appointment_date && ($appointment_date < $today_start);
        }
        
        $disable_action_buttons = in_array($status, ['accepted', 'canceled']);
        $disable_edit_button = $is_past;
        
        ob_start();
        ?>
        <div class="nelx-client-actions-inline"<?php echo $data_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-status="<?php echo esc_attr($status); ?>" data-is-past="<?php echo $is_past ? '1' : '0'; ?>">
            <button type="button" class="nelx-icon-btn nelx-edit" data-action="edit" title="<?php esc_attr_e('Reschedule', 'nelx-jetappt-frontend'); ?>" <?php echo $disable_edit_button ? 'disabled' : ''; ?>>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
            </button>
            
            <button type="button" class="nelx-icon-btn nelx-reject" data-action="cancel" data-status="canceled" title="<?php esc_attr_e('Cancel', 'nelx-jetappt-frontend'); ?>" <?php echo $disable_action_buttons ? 'disabled' : ''; ?>>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
            
            <button type="button" class="nelx-icon-btn nelx-info" data-action="info" title="<?php esc_attr_e('Info', 'nelx-jetappt-frontend'); ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 7h2v2h-2V7zm0 4h2v6h-2v-6zm1-9C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/></svg>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }
}
