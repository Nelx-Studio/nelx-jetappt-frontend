<?php
/**
 * Settings Page Class - Handles admin menu and page rendering
 */

if (!defined('ABSPATH')) exit;

class NELXJAF_Settings_Page {
    
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
        add_action('admin_menu', [$this, 'add_menu']);
    }
    
    public function add_menu() {
        add_menu_page(
            esc_html__('Nelx Appointments', 'nelx-jetappt-frontend'),
            esc_html__('Nelx Appointments', 'nelx-jetappt-frontend'),
            'manage_options',
            'nelx-jetappt-settings',
            [$this, 'render_settings_page'],
            'dashicons-calendar-alt',
            30.5
        );
    }
    
    public function render_settings_page() {
        $shortcodes = [
            ['[nelx_schedule_editor]', esc_html__('Displays the provider schedule editor.', 'nelx-jetappt-frontend')],
            ['[nelx_provider_action_buttons]', esc_html__('Provider inline actions (confirm/reject/reschedule/info).', 'nelx-jetappt-frontend')],
            ['[nelx_client_action_buttons]', esc_html__('Client actions: reschedule, cancel, info.', 'nelx-jetappt-frontend')],
            ['[nelx_staff_appointments]', esc_html__('Native staff/provider appointments table.', 'nelx-jetappt-frontend')],
            ['[nelx_client_appointments]', esc_html__('Native client appointments table.', 'nelx-jetappt-frontend')],
            ['[nelx_staff_appointments_grid]', esc_html__('Native staff/provider dashboard appointment cards.', 'nelx-jetappt-frontend')],
            ['[nelx_client_appointments_grid]', esc_html__('Native client dashboard appointment cards.', 'nelx-jetappt-frontend')],
            ['[nelx_google_meet_settings]', esc_html__('Provider Google Meet settings page.', 'nelx-jetappt-frontend')],
        ];
        ?>
        <div class="wrap nelx-jetappt-settings-wrap">
            <h1><?php esc_html_e('Nelx JetAppointments Settings', 'nelx-jetappt-frontend'); ?></h1>
    
            <div class="nelx-jetappt-admin-tabs-wrapper">
                <div class="nelx-jetappt-tabs-header">
                    <ul class="nelx-jetappt-nav-list nelx-jetappt-horizontal-tabs">
                        <li>
                            <a href="#" class="nelx-jetappt-nav-item active" data-tab="nelx-jetappt-tab-database">
                                <span class="dashicons dashicons-database"></span>
                                <span class="nelx-jetappt-nav-text"><?php esc_html_e('General Settings', 'nelx-jetappt-frontend'); ?></span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="nelx-jetappt-nav-item" data-tab="nelx-jetappt-tab-google-meet">
                                <span class="dashicons dashicons-video-alt3"></span>
                                <span class="nelx-jetappt-nav-text"><?php esc_html_e('Google Meet', 'nelx-jetappt-frontend'); ?></span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="nelx-jetappt-nav-item" data-tab="nelx-jetappt-tab-email-branding">
                                <span class="dashicons dashicons-email-alt"></span>
                                <span class="nelx-jetappt-nav-text"><?php esc_html_e('Email Branding', 'nelx-jetappt-frontend'); ?></span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="nelx-jetappt-nav-item" data-tab="nelx-jetappt-tab-email-templates">
                                <span class="dashicons dashicons-media-document"></span>
                                <span class="nelx-jetappt-nav-text"><?php esc_html_e('Email Templates', 'nelx-jetappt-frontend'); ?></span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="nelx-jetappt-nav-item" data-tab="nelx-jetappt-tab-notifications">
                                <span class="dashicons dashicons-bell"></span>
                                <span class="nelx-jetappt-nav-text"><?php esc_html_e('Nelx Notifications', 'nelx-jetappt-frontend'); ?></span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="nelx-jetappt-nav-item" data-tab="nelx-jetappt-tab-shortcodes">
                                <span class="dashicons dashicons-shortcode"></span>
                                <span class="nelx-jetappt-nav-text"><?php esc_html_e('Shortcodes', 'nelx-jetappt-frontend'); ?></span>
                            </a>
                        </li>
                    </ul>
                    
                    <div class="nelx-jetappt-tabs-save-wrapper">
                        <?php submit_button(esc_html__('Save Changes', 'nelx-jetappt-frontend'), 'primary', 'submit', false, ['id' => 'nelx-jetappt-tabs-save-button']); ?>
                    </div>
                </div>
            </div>
    
            <form method="post" action="options.php" class="nelx-jetappt-settings-form" id="nelx-jetappt-settings-form">
                <?php 
                settings_fields('nelx_jetappt_settings_group');
                wp_nonce_field('nelx_jetappt_nonce', '_nelx_wpnonce'); 
                ?>
                <?php if (isset($_SERVER['REQUEST_URI'])) : ?>
                    <input type="hidden" name="_wp_http_referer" value="<?php echo esc_url(wp_unslash($_SERVER['REQUEST_URI'])); ?>">
                <?php endif; ?>
                
                <div class="nelx-jetappt-settings-content">
                    <div id="nelx-jetappt-tab-database" class="nelx-jetappt-tab-content active">
                        <?php $this->render_database_section(); ?>
                    </div>
    
                    <div id="nelx-jetappt-tab-google-meet" class="nelx-jetappt-tab-content">
                        <?php $this->render_google_meet_section(); ?>
                    </div>
                    
                    <div id="nelx-jetappt-tab-email-branding" class="nelx-jetappt-tab-content">
                        <?php $this->render_email_branding_section(); ?>
                    </div>
    
                    <div id="nelx-jetappt-tab-email-templates" class="nelx-jetappt-tab-content">
                        <?php $this->render_email_templates_section(); ?>
                    </div>
                    
                    <div id="nelx-jetappt-tab-notifications" class="nelx-jetappt-tab-content">
                        <?php $this->render_notifications_section(); ?>
                    </div>
    
                    <div id="nelx-jetappt-tab-shortcodes" class="nelx-jetappt-tab-content">
                        <?php $this->render_shortcodes_section($shortcodes); ?>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
    
    private function render_two_column_grid($fields, $wrapper_class = '') {
        if (empty($fields)) return '';
        
        $output = '<div class="nelx-two-column-grid ' . esc_attr($wrapper_class) . '">';
        
        foreach ($fields as $field) {
            $width_class = isset($field['width']) && $field['width'] === 'full' ? 'nelx-grid-full' : 'nelx-grid-half';
            $output .= '<div class="nelx-grid-item ' . esc_attr($width_class) . '">';
            $output .= '<div class="nelx-field-card">';
            $output .= $field['content'];
            $output .= '</div>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        return $output;
    }
    
    private function render_database_section() {
        $options = get_option($this->option_name, []);
        $defaults = [
            'provider_meta_table' => 'staff_list_for_appoi_meta',
            'provider_column' => 'staff_id',
            'staff_table_columns' => ['id', 'service', 'client', 'appointment_date', 'slot', 'slot_end', 'action'],
            'client_table_columns' => ['id', 'service', 'staff', 'appointment_date', 'time', 'status', 'action'],
            'staff_modal_fields' => ['start', 'end', 'timezone', 'service', 'client', 'client_email', 'client_phone', 'google_meet', 'appointment_status', 'client_local_date', 'client_local_time', 'client_local_timezone', 'notes'],
            'client_modal_fields' => ['date', 'time', 'timezone', 'service', 'provider', 'google_meet', 'appointment_status', 'notes'],
            'staff_table_labels' => [],
            'client_table_labels' => [],
            'staff_modal_labels' => [],
            'client_modal_labels' => [],
            'staff_grid_limit' => 5,
            'client_grid_limit' => 5,
            'staff_grid_columns_desktop' => 3,
            'staff_grid_columns_tablet' => 2,
            'client_grid_columns_desktop' => 3,
            'client_grid_columns_tablet' => 2,
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
            'action_edit_color' => '', 'action_edit_bg' => '', 'action_edit_hover_color' => '', 'action_edit_hover_bg' => '',
            'action_confirm_color' => '', 'action_confirm_bg' => '', 'action_confirm_hover_color' => '', 'action_confirm_hover_bg' => '',
            'action_reject_color' => '', 'action_reject_bg' => '', 'action_reject_hover_color' => '', 'action_reject_hover_bg' => '',
            'action_info_color' => '', 'action_info_bg' => '', 'action_info_hover_color' => '', 'action_info_hover_bg' => '',
        ];
        $options = array_merge($defaults, is_array($options) ? $options : []);
        $listing_columns = class_exists('NELXJAF_Appointment_Listings')
            ? NELXJAF_Appointment_Listings::instance(NELXJAF_Core::instance())->get_table_columns()
            : [];

        $render_column_rows = function($name, $selected, $labels = []) use ($listing_columns) {
            $selected = is_array($selected) && $selected ? $selected : [];
            $labels = is_array($labels) ? $labels : [];
            ob_start(); ?>
            <div class="nelx-column-repeater" data-name="<?php echo esc_attr($name); ?>">
                <div class="nelx-column-repeater-items">
                    <?php foreach ($selected as $column): $default_label = $listing_columns[$column] ?? ucwords(str_replace('_', ' ', $column)); ?>
                        <div class="nelx-column-repeater-item">
                            <select name="<?php echo esc_attr($this->option_name . '[' . $name . '][]'); ?>">
                                <?php foreach ($listing_columns as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($column, $key); ?>><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" class="nelx-column-custom-label regular-text" name="<?php echo esc_attr($this->option_name . '[' . str_replace('_columns','_labels',$name) . '][' . $column . ']'); ?>" value="<?php echo esc_attr($labels[$column] ?? ''); ?>" placeholder="<?php echo esc_attr($default_label); ?>" title="Custom label (optional)">
                            <button type="button" class="button nelx-move-column nelx-move-up" title="Move up">↑</button><button type="button" class="button nelx-move-column nelx-move-down" title="Move down">↓</button><button type="button" class="button nelx-remove-column">&times;</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button nelx-add-column">+ <?php esc_html_e('Add Column', 'nelx-jetappt-frontend'); ?></button>
                <p class="description"><?php esc_html_e('Add, remove and reorder the columns. Enter an optional custom label; leave it empty to use the default label.', 'nelx-jetappt-frontend'); ?></p>
                <template class="nelx-column-template"><div class="nelx-column-repeater-item"><select name="<?php echo esc_attr($this->option_name . '[' . $name . '][]'); ?>"><?php foreach ($listing_columns as $key => $label): ?><option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option><?php endforeach; ?></select><input type="text" class="nelx-column-custom-label regular-text" name="<?php echo esc_attr($this->option_name . '[' . str_replace('_columns','_labels',$name) . '][__KEY__]'); ?>" value="" placeholder="Custom label (optional)" title="Custom label (optional)"><button type="button" class="button nelx-move-column nelx-move-up" title="Move up">↑</button><button type="button" class="button nelx-move-column nelx-move-down" title="Move down">↓</button><button type="button" class="button nelx-remove-column">&times;</button></div></template>
            </div>
            <?php return ob_get_clean();
        };
        ?>
        <div class="nelx-jetappt-settings-card">
            <h3><?php esc_html_e('Provider Mapping', 'nelx-jetappt-frontend'); ?></h3>
            <p><?php esc_html_e('JetAppointments appointment tables are native and are handled automatically. Only the provider mapping table remains configurable.', 'nelx-jetappt-frontend'); ?></p>
            <?php echo $this->render_two_column_grid([
                ['content' => $this->get_field_html(esc_html__('Provider Meta Table', 'nelx-jetappt-frontend'), '<input type="text" id="provider_meta_table" name="' . esc_attr($this->option_name) . '[provider_meta_table]" value="' . esc_attr($options['provider_meta_table']) . '" class="regular-text" required>', esc_html__('Default: staff_list_for_appoi_meta. Maps WordPress users to provider profile posts.'))],
                ['content' => $this->get_field_html(esc_html__('Provider Column', 'nelx-jetappt-frontend'), '<input type="text" id="provider_column" name="' . esc_attr($this->option_name) . '[provider_column]" value="' . esc_attr($options['provider_column']) . '" class="regular-text" required autocomplete="off">', esc_html__('Default: staff_id. Column containing the provider WordPress user ID.'))],
            ]); ?>
        </div>

        <?php
        $render_modal_field_rows = function($name, $view, $selected, $labels = []) {
            $definitions = class_exists('NELXJAF_Appointment_Listings')
                ? NELXJAF_Appointment_Listings::instance(NELXJAF_Core::instance())->get_modal_field_definitions($view)
                : [];
            $selected = is_array($selected) && $selected ? $selected : array_keys($definitions);
            $labels = is_array($labels) ? $labels : [];
            ob_start(); ?>
            <div class="nelx-column-repeater nelx-modal-field-repeater" data-name="<?php echo esc_attr($name); ?>">
                <div class="nelx-column-repeater-items">
                    <?php foreach ($selected as $field): if (!isset($definitions[$field])) continue; ?>
                        <div class="nelx-column-repeater-item">
                            <select name="<?php echo esc_attr($this->option_name . '[' . $name . '][]'); ?>">
                                <?php foreach ($definitions as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($field, $key); ?>><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" class="nelx-column-custom-label regular-text" name="<?php echo esc_attr($this->option_name . '[' . str_replace('_fields','_labels',$name) . '][' . $field . ']'); ?>" value="<?php echo esc_attr($labels[$field] ?? ''); ?>" placeholder="<?php echo esc_attr($definitions[$field]); ?>" title="Custom label (optional)">
                            <button type="button" class="button nelx-move-column nelx-move-up" title="Move up">↑</button><button type="button" class="button nelx-move-column nelx-move-down" title="Move down">↓</button><button type="button" class="button nelx-remove-column">&times;</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button nelx-add-column">+ <?php esc_html_e('Add Field', 'nelx-jetappt-frontend'); ?></button>
                <p class="description"><?php esc_html_e('Add, remove and reorder fields. Enter an optional custom label; leave it empty to use the default label.', 'nelx-jetappt-frontend'); ?></p>
                <template class="nelx-column-template"><div class="nelx-column-repeater-item"><select name="<?php echo esc_attr($this->option_name . '[' . $name . '][]'); ?>"><?php foreach ($definitions as $key => $label): ?><option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option><?php endforeach; ?></select><input type="text" class="nelx-column-custom-label regular-text" name="<?php echo esc_attr($this->option_name . '[' . str_replace('_fields','_labels',$name) . '][__KEY__]'); ?>" value="" placeholder="Custom label (optional)" title="Custom label (optional)"><button type="button" class="button nelx-move-column nelx-move-up" title="Move up">↑</button><button type="button" class="button nelx-move-column nelx-move-down" title="Move down">↓</button><button type="button" class="button nelx-remove-column">&times;</button></div></template>
            </div>
            <?php return ob_get_clean();
        };
        ?>

        <div class="nelx-two-column-layout nelx-appointment-config-columns">
            <div class="nelx-jetappt-settings-card">
                <h3><?php esc_html_e('Staff / Provider Appointment Table', 'nelx-jetappt-frontend'); ?></h3>
                <p><?php esc_html_e('Choose the columns shown by the new native staff appointment table shortcode and Elementor widget. Slot and Slot End are the native JetAppointments fields used for start and end time; use custom labels when you want different labels.', 'nelx-jetappt-frontend'); ?></p>
                <?php echo $render_column_rows('staff_table_columns', $options['staff_table_columns'], $options['staff_table_labels']); ?>
            </div>

            <div class="nelx-jetappt-settings-card">
                <h3><?php esc_html_e('Staff / Provider Appointment Info Modal', 'nelx-jetappt-frontend'); ?></h3>
                <p><?php esc_html_e('Choose the fields shown in the staff/provider appointment information modal. Payment Status is the native JetAppointments payment status field and can be changed by the provider when included. Appointment Status is the separate custom appointment status field. Slot and Slot End are the native start/end timestamp fields.', 'nelx-jetappt-frontend'); ?></p>
                <?php echo $render_modal_field_rows('staff_modal_fields', 'staff', $options['staff_modal_fields'], $options['staff_modal_labels']); ?>
            </div>
        </div>

        <div class="nelx-two-column-layout nelx-appointment-config-columns">
            <div class="nelx-jetappt-settings-card">
                <h3><?php esc_html_e('Client Appointment Table', 'nelx-jetappt-frontend'); ?></h3>
                <p><?php esc_html_e('Choose the columns shown by the new native client appointment table. Time uses the saved client-local appointment data when available and falls back to the provider time.', 'nelx-jetappt-frontend'); ?></p>
                <?php echo $render_column_rows('client_table_columns', $options['client_table_columns'], $options['client_table_labels']); ?>
            </div>

            <div class="nelx-jetappt-settings-card">
                <h3><?php esc_html_e('Client Appointment Info Modal', 'nelx-jetappt-frontend'); ?></h3>
                <p><?php esc_html_e('Choose the fields shown in the client appointment information modal. Payment Status is read-only for clients when included; Appointment Status remains a separate field. Client date, time and timezone use appointment meta when available and fall back to provider time/WordPress timezone.', 'nelx-jetappt-frontend'); ?></p>
                <?php echo $render_modal_field_rows('client_modal_fields', 'client', $options['client_modal_fields'], $options['client_modal_labels']); ?>
            </div>
        </div>

        <div class="nelx-jetappt-settings-card">
            <h3><?php esc_html_e('Table Appearance', 'nelx-jetappt-frontend'); ?></h3>
            <p><?php esc_html_e('These settings apply to both native appointment table shortcodes. Elementor widgets can override their appearance from the Style tab.', 'nelx-jetappt-frontend'); ?></p>
            <?php echo $this->render_two_column_grid([
                ['content' => $this->get_field_html(esc_html__('Header Background', 'nelx-jetappt-frontend'), '<input type="text" class="nelx-jetappt-color-picker" name="' . esc_attr($this->option_name) . '[table_header_bg]" value="' . esc_attr($options['table_header_bg']) . '" data-default-color="">', esc_html__('Leave empty to inherit the site primary color.'))],
                ['content' => $this->get_field_html(esc_html__('Header Text Color', 'nelx-jetappt-frontend'), '<input type="text" class="nelx-jetappt-color-picker" name="' . esc_attr($this->option_name) . '[table_header_text]" value="' . esc_attr($options['table_header_text']) . '" data-default-color="#ffffff">')],
                ['content' => $this->get_field_html(esc_html__('Header Font Size', 'nelx-jetappt-frontend'), '<input type="number" min="8" max="32" name="' . esc_attr($this->option_name) . '[table_header_font_size]" value="' . esc_attr($options['table_header_font_size']) . '" class="small-text">')],
                ['content' => $this->get_field_html(esc_html__('Body Font Size', 'nelx-jetappt-frontend'), '<input type="number" min="8" max="32" name="' . esc_attr($this->option_name) . '[table_body_font_size]" value="' . esc_attr($options['table_body_font_size']) . '" class="small-text">')],
                ['content' => $this->get_field_html(esc_html__('Controls Font Size', 'nelx-jetappt-frontend'), '<input type="number" min="8" max="24" name="' . esc_attr($this->option_name) . '[table_control_font_size]" value="' . esc_attr($options['table_control_font_size']) . '" class="small-text">')],
                ['content' => $this->get_field_html(esc_html__('Row Hover Background', 'nelx-jetappt-frontend'), '<input type="text" class="nelx-jetappt-color-picker" name="' . esc_attr($this->option_name) . '[table_hover_bg]" value="' . esc_attr($options['table_hover_bg']) . '" data-default-color="#f5f5f5">')],
            ]); ?>
        </div>

        <div class="nelx-jetappt-settings-card">
            <h3><?php esc_html_e('Dashboard Appointment Grids', 'nelx-jetappt-frontend'); ?></h3>
            <?php echo $this->render_two_column_grid([
                ['content' => $this->get_field_html(esc_html__('Staff / Provider Maximum Appointments', 'nelx-jetappt-frontend'), '<input type="number" min="1" max="100" name="' . esc_attr($this->option_name) . '[staff_grid_limit]" value="' . esc_attr($options['staff_grid_limit']) . '" class="small-text">')],
                ['content' => $this->get_field_html(esc_html__('Client Maximum Appointments', 'nelx-jetappt-frontend'), '<input type="number" min="1" max="100" name="' . esc_attr($this->option_name) . '[client_grid_limit]" value="' . esc_attr($options['client_grid_limit']) . '" class="small-text">')],
                ['content' => $this->get_field_html(esc_html__('Staff Desktop Columns', 'nelx-jetappt-frontend'), '<input type="number" min="1" max="6" name="' . esc_attr($this->option_name) . '[staff_grid_columns_desktop]" value="' . esc_attr($options['staff_grid_columns_desktop']) . '" class="small-text">')],
                ['content' => $this->get_field_html(esc_html__('Staff Tablet Columns', 'nelx-jetappt-frontend'), '<input type="number" min="1" max="4" name="' . esc_attr($this->option_name) . '[staff_grid_columns_tablet]" value="' . esc_attr($options['staff_grid_columns_tablet']) . '" class="small-text">')],
                ['content' => $this->get_field_html(esc_html__('Client Desktop Columns', 'nelx-jetappt-frontend'), '<input type="number" min="1" max="6" name="' . esc_attr($this->option_name) . '[client_grid_columns_desktop]" value="' . esc_attr($options['client_grid_columns_desktop']) . '" class="small-text">')],
                ['content' => $this->get_field_html(esc_html__('Client Tablet Columns', 'nelx-jetappt-frontend'), '<input type="number" min="1" max="4" name="' . esc_attr($this->option_name) . '[client_grid_columns_tablet]" value="' . esc_attr($options['client_grid_columns_tablet']) . '" class="small-text">')],
            ]); ?>
            <p class="description"><?php esc_html_e('Upcoming appointments are ordered by the nearest appointment first. Mobile automatically uses one column. Elementor grid widgets can override the limit and column counts independently.', 'nelx-jetappt-frontend'); ?></p>
        </div>

        <div class="nelx-jetappt-settings-card">
            <h3><?php esc_html_e('Dashboard Card Appearance', 'nelx-jetappt-frontend'); ?></h3>
            <?php echo $this->render_two_column_grid([
                ['content' => $this->get_field_html(esc_html__('Card Background', 'nelx-jetappt-frontend'), '<input type="text" class="nelx-jetappt-color-picker" name="' . esc_attr($this->option_name) . '[grid_card_bg]" value="' . esc_attr($options['grid_card_bg']) . '" data-default-color="#ffffff">')],
                ['content' => $this->get_field_html(esc_html__('Card Border', 'nelx-jetappt-frontend'), '<input type="text" class="nelx-jetappt-color-picker" name="' . esc_attr($this->option_name) . '[grid_card_border]" value="' . esc_attr($options['grid_card_border']) . '" data-default-color="#e5e7eb">')],
                ['content' => $this->get_field_html(esc_html__('Card Hover Background', 'nelx-jetappt-frontend'), '<input type="text" class="nelx-jetappt-color-picker" name="' . esc_attr($this->option_name) . '[grid_card_hover_bg]" value="' . esc_attr($options['grid_card_hover_bg']) . '" data-default-color="#ffffff">')],
                ['content' => $this->get_field_html(esc_html__('Card Hover Border', 'nelx-jetappt-frontend'), '<input type="text" class="nelx-jetappt-color-picker" name="' . esc_attr($this->option_name) . '[grid_card_hover_border]" value="' . esc_attr($options['grid_card_hover_border']) . '" data-default-color="#d1d5db">')],
                ['content' => $this->get_field_html(esc_html__('Label Font Size', 'nelx-jetappt-frontend'), '<input type="number" min="8" max="32" name="' . esc_attr($this->option_name) . '[grid_label_font_size]" value="' . esc_attr($options['grid_label_font_size']) . '" class="small-text">')],
                ['content' => $this->get_field_html(esc_html__('Label Font Weight', 'nelx-jetappt-frontend'), '<input type="number" min="100" max="900" step="100" name="' . esc_attr($this->option_name) . '[grid_label_font_weight]" value="' . esc_attr($options['grid_label_font_weight']) . '" class="small-text">')],
                ['content' => $this->get_field_html(esc_html__('Label Line Height', 'nelx-jetappt-frontend'), '<input type="number" min="1" max="3" step="0.1" name="' . esc_attr($this->option_name) . '[grid_label_line_height]" value="' . esc_attr($options['grid_label_line_height']) . '" class="small-text">')],
                ['content' => $this->get_field_html(esc_html__('Value Font Size', 'nelx-jetappt-frontend'), '<input type="number" min="8" max="32" name="' . esc_attr($this->option_name) . '[grid_value_font_size]" value="' . esc_attr($options['grid_value_font_size']) . '" class="small-text">')],
                ['content' => $this->get_field_html(esc_html__('Value Font Weight', 'nelx-jetappt-frontend'), '<input type="number" min="100" max="900" step="100" name="' . esc_attr($this->option_name) . '[grid_value_font_weight]" value="' . esc_attr($options['grid_value_font_weight']) . '" class="small-text">')],
                ['content' => $this->get_field_html(esc_html__('Value Line Height', 'nelx-jetappt-frontend'), '<input type="number" min="1" max="3" step="0.1" name="' . esc_attr($this->option_name) . '[grid_value_line_height]" value="' . esc_attr($options['grid_value_line_height']) . '" class="small-text">')],
                ['content' => $this->get_field_html(esc_html__('No Upcoming Appointments Font Size', 'nelx-jetappt-frontend'), '<input type="number" min="8" max="32" name="' . esc_attr($this->option_name) . '[grid_empty_font_size]" value="' . esc_attr($options['grid_empty_font_size']) . '" class="small-text">')],
                ['content' => $this->get_field_html(esc_html__('No Upcoming Appointments Font Weight', 'nelx-jetappt-frontend'), '<input type="number" min="100" max="900" step="100" name="' . esc_attr($this->option_name) . '[grid_empty_font_weight]" value="' . esc_attr($options['grid_empty_font_weight']) . '" class="small-text">')],
                ['content' => $this->get_field_html(esc_html__('No Upcoming Appointments Line Height', 'nelx-jetappt-frontend'), '<input type="number" min="1" max="3" step="0.1" name="' . esc_attr($this->option_name) . '[grid_empty_line_height]" value="' . esc_attr($options['grid_empty_line_height']) . '" class="small-text">')],
            ]); ?>
        </div>

        <div class="nelx-jetappt-settings-card">
            <h3><?php esc_html_e('Action Buttons General Style', 'nelx-jetappt-frontend'); ?></h3>
            <p><?php esc_html_e('These settings apply to action buttons in the native appointment tables, calendar and dashboard grids. Leave color fields empty to keep the original action-button shortcode defaults.', 'nelx-jetappt-frontend'); ?></p>
            <?php echo $this->render_two_column_grid([
                ['content' => $this->get_field_html(esc_html__('Transition Duration (seconds)', 'nelx-jetappt-frontend'), '<input type="number" min="0" max="3" step="0.1" name="' . esc_attr($this->option_name) . '[action_transition_duration]" value="' . esc_attr($options['action_transition_duration']) . '" class="small-text">')],
                ['content' => $this->get_field_html(esc_html__('Disabled Opacity', 'nelx-jetappt-frontend'), '<input type="number" min="0.1" max="1" step="0.01" name="' . esc_attr($this->option_name) . '[action_disabled_opacity]" value="' . esc_attr($options['action_disabled_opacity']) . '" class="small-text">')],
                ['content' => $this->get_field_html(esc_html__('Disabled Background', 'nelx-jetappt-frontend'), '<input type="text" class="nelx-jetappt-color-picker" name="' . esc_attr($this->option_name) . '[action_disabled_bg]" value="' . esc_attr($options['action_disabled_bg']) . '" data-default-color="">')],
            ]); ?>
        </div>

        <div class="nelx-jetappt-settings-card">
            <h3><?php esc_html_e('Action Button Colors', 'nelx-jetappt-frontend'); ?></h3>
            <?php foreach ([
                'edit' => 'Edit / Reschedule', 'confirm' => 'Approve', 'reject' => 'Cancel', 'info' => 'Info'
            ] as $button => $label): ?>
                <h4><?php echo esc_html($label); ?></h4>
                <?php echo $this->render_two_column_grid([
                    ['content' => $this->get_field_html(esc_html__('Normal Icon Color', 'nelx-jetappt-frontend'), '<input type="text" class="nelx-jetappt-color-picker" name="' . esc_attr($this->option_name) . '[action_' . $button . '_color]" value="' . esc_attr($options['action_' . $button . '_color']) . '" data-default-color="">')],
                    ['content' => $this->get_field_html(esc_html__('Normal Background', 'nelx-jetappt-frontend'), '<input type="text" class="nelx-jetappt-color-picker" name="' . esc_attr($this->option_name) . '[action_' . $button . '_bg]" value="' . esc_attr($options['action_' . $button . '_bg']) . '" data-default-color="">')],
                    ['content' => $this->get_field_html(esc_html__('Hover Icon Color', 'nelx-jetappt-frontend'), '<input type="text" class="nelx-jetappt-color-picker" name="' . esc_attr($this->option_name) . '[action_' . $button . '_hover_color]" value="' . esc_attr($options['action_' . $button . '_hover_color']) . '" data-default-color="">')],
                    ['content' => $this->get_field_html(esc_html__('Hover Background', 'nelx-jetappt-frontend'), '<input type="text" class="nelx-jetappt-color-picker" name="' . esc_attr($this->option_name) . '[action_' . $button . '_hover_bg]" value="' . esc_attr($options['action_' . $button . '_hover_bg']) . '" data-default-color="">')],
                ]); ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private function render_google_meet_section() {
        $options = get_option($this->google_meet_option_name, []);
        $redirect_uri = admin_url('admin-ajax.php?action=google_meet_auth');
        ?>
        <div class="nelx-jetappt-settings-card">
            <h3><?php esc_html_e('Google Meet Integration', 'nelx-jetappt-frontend'); ?></h3>
            <div class="nelx-two-column-layout">
                <div class="nelx-column-left">
                    <div class="nelx-field-card">
                        <div class="nelx-field-label">
                            <label><?php esc_html_e('Setup Instructions', 'nelx-jetappt-frontend'); ?></label>
                        </div>
                        <div class="nelx-field-content">
                            <ol>
                                <?php /* translators: %1$s: opening anchor tag, %2$s: closing anchor tag */ ?>
                                <li><?php printf(esc_html__('Go to the %1$sGoogle Cloud Console%2$s', 'nelx-jetappt-frontend'), '<a href="https://console.cloud.google.com/" target="_blank">', '</a>'); ?></li>
                                <li><?php esc_html_e('Create a new project or select an existing one', 'nelx-jetappt-frontend'); ?></li>
                                <li><?php esc_html_e('Enable the Google Calendar API', 'nelx-jetappt-frontend'); ?></li>
                                <li><?php esc_html_e('Configure the OAuth consent screen', 'nelx-jetappt-frontend'); ?></li>
                                <li><?php esc_html_e('Create credentials (OAuth 2.0 Client ID)', 'nelx-jetappt-frontend'); ?></li>
                                <li><?php esc_html_e('Add authorized redirect URIs:', 'nelx-jetappt-frontend'); ?> <code><?php echo esc_url($redirect_uri); ?></code></li>
                                <?php /* translators: %1$s: opening anchor tag, %2$s: closing anchor tag */ ?>
                                <li><?php printf(esc_html__('For detailed instructions, refer to the %1$sofficial Google API documentation%2$s', 'nelx-jetappt-frontend'), '<a href="https://developers.google.com/workspace/meet/overview" target="_blank">', '</a>'); ?></li>
                            </ol>
                        </div>
                    </div>
                </div>
                
                <div class="nelx-column-right">
                    <div class="nelx-field-card">
                        <?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo $this->get_field_html(
                            esc_html__('Client ID', 'nelx-jetappt-frontend'),
                            '<input type="text" id="google_client_id" name="' . esc_attr($this->google_meet_option_name) . '[google_client_id]" 
                                value="' . esc_attr($options['google_client_id'] ?? '') . '" class="regular-text">',
                            esc_html__('Your Google API Client ID', 'nelx-jetappt-frontend')
                        ); ?>
                    </div>
                    <div class="nelx-field-card">
                        <?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo $this->get_field_html(
                            esc_html__('Client Secret', 'nelx-jetappt-frontend'),
                            '<input type="password" id="google_client_secret" name="' . esc_attr($this->google_meet_option_name) . '[google_client_secret]" 
                                value="' . esc_attr($options['google_client_secret'] ?? '') . '" class="regular-text">',
                            esc_html__('Your Google API Client Secret', 'nelx-jetappt-frontend')
                        ); ?>
                    </div>
                    <div class="nelx-field-card">
                        <?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo $this->get_field_html(
                            esc_html__('Redirect URI', 'nelx-jetappt-frontend'),
                            '<input type="text" id="google_redirect_uri" class="regular-text" value="' . esc_url($redirect_uri) . '" readonly>
                            <button type="button" style="margin-top: 10px;" class="button nelx-copy-btn" data-copy="' . esc_attr($redirect_uri) . '">
                                <span class="dashicons dashicons-admin-page"></span> ' . esc_html__('Copy', 'nelx-jetappt-frontend') . '
                            </button>',
                            esc_html__('Add this to your Google API authorized redirect URIs', 'nelx-jetappt-frontend')
                        ); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_email_branding_section() {
        $options = get_option($this->email_branding_option_name, []);
        $logo_url = $options['email_logo_url'] ?? '';
        $custom_logo_id = get_theme_mod('custom_logo');
        $site_logo_url = $custom_logo_id ? wp_get_attachment_image_url($custom_logo_id, 'full') : '';
        
        $fields = [
            [
                'content' => $this->get_field_html(
                    esc_html__('Email Logo', 'nelx-jetappt-frontend'),
                    $this->get_logo_uploader_html($logo_url, $site_logo_url),
                    ''
                )
            ],
            [
                'content' => $this->get_field_html(
                    esc_html__('Logo Alignment', 'nelx-jetappt-frontend'),
                    '<select name="' . esc_attr($this->email_branding_option_name) . '[email_logo_alignment]" id="email_logo_alignment">
                        <option value="left" ' . selected($options['email_logo_alignment'] ?? 'center', 'left', false) . '>' . esc_html__('Left', 'nelx-jetappt-frontend') . '</option>
                        <option value="center" ' . selected($options['email_logo_alignment'] ?? 'center', 'center', false) . '>' . esc_html__('Center', 'nelx-jetappt-frontend') . '</option>
                        <option value="right" ' . selected($options['email_logo_alignment'] ?? 'center', 'right', false) . '>' . esc_html__('Right', 'nelx-jetappt-frontend') . '</option>
                    </select>',
                    esc_html__('Alignment of the logo in the email header.', 'nelx-jetappt-frontend')
                )
            ],
            [
                'content' => $this->get_field_html(
                    esc_html__('Heading Color', 'nelx-jetappt-frontend'),
                    '<input type="text" class="nelx-jetappt-color-picker" 
                        name="' . esc_attr($this->email_branding_option_name) . '[email_heading_color]" 
                        id="email_heading_color"
                        value="' . esc_attr($options['email_heading_color'] ?? '#1E3A8A') . '" 
                        data-default-color="#1E3A8A">',
                    esc_html__('Color used for headings (h1, h2, h3) in emails.', 'nelx-jetappt-frontend')
                )
            ],
            [
                'content' => $this->get_field_html(
                    esc_html__('Button Color', 'nelx-jetappt-frontend'),
                    '<input type="text" class="nelx-jetappt-color-picker" 
                        name="' . esc_attr($this->email_branding_option_name) . '[email_button_color]" 
                        id="email_button_color"
                        value="' . esc_attr($options['email_button_color'] ?? '#1E3A8A') . '" 
                        data-default-color="#1E3A8A">',
                    esc_html__('Background color for buttons in emails.', 'nelx-jetappt-frontend')
                )
            ],
            [
                'content' => $this->get_field_html(
                    esc_html__('Button Hover Color', 'nelx-jetappt-frontend'),
                    '<input type="text" class="nelx-jetappt-color-picker" 
                        name="' . esc_attr($this->email_branding_option_name) . '[email_button_hover_color]" 
                        id="email_button_hover_color"
                        value="' . esc_attr($options['email_button_hover_color'] ?? '#1a3a47') . '" 
                        data-default-color="#1a3a47">',
                    esc_html__('Background color for buttons when hovered.', 'nelx-jetappt-frontend')
                )
            ],
            [
                'content' => $this->get_field_html(
                    esc_html__('Link Color', 'nelx-jetappt-frontend'),
                    '<input type="text" class="nelx-jetappt-color-picker" 
                        name="' . esc_attr($this->email_branding_option_name) . '[email_link_color]" 
                        id="email_link_color"
                        value="' . esc_attr($options['email_link_color'] ?? '#1E3A8A') . '" 
                        data-default-color="#1E3A8A">',
                    esc_html__('Color used for links in emails.', 'nelx-jetappt-frontend')
                )
            ],
            [
                'content' => $this->get_field_html(
                    esc_html__('Background Color', 'nelx-jetappt-frontend'),
                    '<input type="text" class="nelx-jetappt-color-picker" 
                        name="' . esc_attr($this->email_branding_option_name) . '[email_bg_color]" 
                        id="email_bg_color"
                        value="' . esc_attr($options['email_bg_color'] ?? '#f5f5f5') . '" 
                        data-default-color="#f5f5f5">',
                    esc_html__('Background color for the email body.', 'nelx-jetappt-frontend')
                )
            ],
            [
                'content' => $this->get_field_html(
                    esc_html__('Container Background', 'nelx-jetappt-frontend'),
                    '<input type="text" class="nelx-jetappt-color-picker" 
                        name="' . esc_attr($this->email_branding_option_name) . '[email_container_bg]" 
                        id="email_container_bg"
                        value="' . esc_attr($options['email_container_bg'] ?? '#ffffff') . '" 
                        data-default-color="#ffffff">',
                    esc_html__('Background color for the email container.', 'nelx-jetappt-frontend')
                )
            ],
            [
                'content' => $this->get_field_html(
                    esc_html__('Header Background', 'nelx-jetappt-frontend'),
                    '<input type="text" class="nelx-jetappt-color-picker" 
                        name="' . esc_attr($this->email_branding_option_name) . '[email_header_bg]" 
                        id="email_header_bg"
                        value="' . esc_attr($options['email_header_bg'] ?? '#f9f9f9') . '" 
                        data-default-color="#f9f9f9">',
                    esc_html__('Background color for the email header.', 'nelx-jetappt-frontend')
                )
            ],
            [
                'content' => $this->get_field_html(
                    esc_html__('Footer Background', 'nelx-jetappt-frontend'),
                    '<input type="text" class="nelx-jetappt-color-picker" 
                        name="' . esc_attr($this->email_branding_option_name) . '[email_footer_bg]" 
                        id="email_footer_bg"
                        value="' . esc_attr($options['email_footer_bg'] ?? '#f9f9f9') . '" 
                        data-default-color="#f9f9f9">',
                    esc_html__('Background color for the email footer.', 'nelx-jetappt-frontend')
                )
            ],
            [
                'content' => $this->get_field_html(
                    esc_html__('Footer Text Color', 'nelx-jetappt-frontend'),
                    '<input type="text" class="nelx-jetappt-color-picker" 
                        name="' . esc_attr($this->email_branding_option_name) . '[email_footer_text_color]" 
                        id="email_footer_text_color"
                        value="' . esc_attr($options['email_footer_text_color'] ?? '#777777') . '" 
                        data-default-color="#777777">',
                    esc_html__('Text color for the email footer.', 'nelx-jetappt-frontend')
                )
            ],
            [
                'content' => $this->get_field_html(
                    esc_html__('Footer Text', 'nelx-jetappt-frontend'),
                    '<textarea id="email_footer_text" rows="5"
                        name="' . esc_attr($this->email_branding_option_name) . '[email_footer_text]">' .
                        esc_textarea($options['email_footer_text'] ?? '&copy; [year] ' . get_bloginfo('name') . '. All rights reserved.<br>This email was sent from ' . get_bloginfo('name') . '.') .
                    '</textarea>',
                    esc_html__('HTML allowed. Use [year] or {year} as a placeholder for the current year. The system will automatically replace it with the actual year (e.g., 2026) when sending emails.', 'nelx-jetappt-frontend')
                )
            ]
        ];
        ?>
        
        <div class="nelx-jetappt-settings-card">
            <h3><?php esc_html_e('Email Branding', 'nelx-jetappt-frontend'); ?></h3>
            <p><?php esc_html_e('Configure the branding for emails sent for appointments.', 'nelx-jetappt-frontend'); ?></p>
            
            <?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $this->render_two_column_grid($fields);
            ?>
        </div>
        
        <div class="nelx-jetappt-settings-card">
            <h3><?php esc_html_e('Social Media Links', 'nelx-jetappt-frontend'); ?></h3>
            <div class="nelx-jetappt-form-table">
                <div class="nelx-jetappt-form-row">
                    <div class="nelx-jetappt-form-label">
                        <label><?php esc_html_e('Social Media Icons', 'nelx-jetappt-frontend'); ?></label>
                    </div>
                    <div class="nelx-jetappt-form-field">
                        <div id="nelx-jetappt-social-icons-container">
                            <?php
                            $social_icons = $options['email_social_icons'] ?? [];
                            if (!empty($social_icons)) {
                                foreach ($social_icons as $index => $icon) {
                                    ?>
                                    <div class="nelx-jetappt-social-icon" style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; background: #f9f9f9;">
                                        <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                            <div style="flex: 1;">
                                                <label><?php esc_html_e('URL', 'nelx-jetappt-frontend'); ?></label>
                                                <input type="text" 
                                                    name="<?php echo esc_attr($this->email_branding_option_name); ?>[email_social_icons][<?php echo esc_attr($index); ?>][url]" 
                                                    value="<?php echo esc_url($icon['url']); ?>" 
                                                    class="regular-text" 
                                                    placeholder="https://example.com">
                                            </div>
                                            <div style="flex: 1;">
                                                <label><?php esc_html_e('Icon', 'nelx-jetappt-frontend'); ?></label>
                                                <div class="nelx-jetappt-icon-uploader">
                                                    <input type="hidden" 
                                                        name="<?php echo esc_attr($this->email_branding_option_name); ?>[email_social_icons][<?php echo esc_attr($index); ?>][icon]" 
                                                        class="nelx-jetappt-icon-url" 
                                                        value="<?php echo esc_url($icon['icon']); ?>">
                                                    <div class="nelx-jetappt-icon-preview" style="display: flex; align-items: center; gap: 10px;">
                                                        <?php if (!empty($icon['icon'])) : ?>
                                                            <img src="<?php echo esc_url($icon['icon']); ?>" style="max-width: 24px; max-height: 24px;">
                                                            <button type="button" class="button nelx-jetappt-change-social-icon" style="display: inline;"><?php esc_html_e('Change Icon', 'nelx-jetappt-frontend'); ?></button>
                                                            <button type="button" class="button nelx-jetappt-remove-icon" style="display: inline;"><?php esc_html_e('Remove Icon', 'nelx-jetappt-frontend'); ?></button>
                                                        <?php else : ?>
                                                            <button type="button" class="button nelx-jetappt-upload-icon" style="display: inline;"><?php esc_html_e('Select Icon', 'nelx-jetappt-frontend'); ?></button>
                                                            <button type="button" class="button nelx-jetappt-change-social-icon" style="display: none;"><?php esc_html_e('Change Icon', 'nelx-jetappt-frontend'); ?></button>
                                                            <button type="button" class="button nelx-jetappt-remove-icon" style="display: none;"><?php esc_html_e('Remove', 'nelx-jetappt-frontend'); ?></button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="button nelx-jetappt-remove-social-icon"><?php esc_html_e('Remove Icon Set', 'nelx-jetappt-frontend'); ?></button>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                        <button type="button" id="nelx-jetappt-add-social-icon" class="button"><?php esc_html_e('Add Social Icon', 'nelx-jetappt-frontend'); ?></button>
                        <p class="description"><?php esc_html_e('Add social media icons that will appear in the email footer.', 'nelx-jetappt-frontend'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_email_templates_section() {
        $settings = get_option($this->option_name, []);
        $custom_templates = $settings['custom_email_templates'] ?? [];
        
        $saved_default_templates = $settings['default_email_templates'] ?? [];
        
        $home_url_host = wp_parse_url(home_url(), PHP_URL_HOST);
        $noreply_from = get_bloginfo('name') . ' <noreply@' . $home_url_host . '>';
        
        $default_templates = [
            [
                'name' => 'New Appointment - Provider',
                'form_id' => $saved_default_templates[0]['form_id'] ?? '0',
                'email_settings' => [
                    'to' => '{provider_email}',
                    'cc' => '',
                    'bcc' => '',
                    'subject' => esc_html__('New Appointment Scheduled', 'nelx-jetappt-frontend'),
                    'message' => $this->get_template_new_appointment_provider(),
                    'from' => $noreply_from
                ]
            ],
            [
                'name' => 'Appointment Confirmation - Online',
                'form_id' => $saved_default_templates[1]['form_id'] ?? '0',
                'email_settings' => [
                    'to' => '{client_email}',
                    'cc' => '',
                    'bcc' => '',
                    'subject' => esc_html__('Your Appointment Has Been Confirmed', 'nelx-jetappt-frontend'),
                    'message' => $this->get_template_confirmation_online(),
                    'from' => $noreply_from
                ]
            ],
            [
                'name' => 'Appointment Confirmation - Physical',
                'form_id' => $saved_default_templates[2]['form_id'] ?? '0',
                'email_settings' => [
                    'to' => '{client_email}',
                    'cc' => '',
                    'bcc' => '',
                    'subject' => esc_html__('Your Appointment Has Been Confirmed', 'nelx-jetappt-frontend'),
                    'message' => $this->get_template_confirmation_physical(),
                    'from' => $noreply_from
                ]
            ],
            [
                'name' => 'Appointment Canceled - Provider',
                'form_id' => $saved_default_templates[3]['form_id'] ?? '0',
                'email_settings' => [
                    'to' => '{provider_email}',
                    'cc' => '',
                    'bcc' => '',
                    'subject' => esc_html__('Appointment Canceled by Client', 'nelx-jetappt-frontend'),
                    'message' => $this->get_template_canceled_provider(),
                    'from' => $noreply_from
                ]
            ],
            [
                'name' => 'Appointment Canceled - Client',
                'form_id' => $saved_default_templates[4]['form_id'] ?? '0',
                'email_settings' => [
                    'to' => '{client_email}',
                    'cc' => '',
                    'bcc' => '',
                    'subject' => esc_html__('Your Appointment Has Been Canceled', 'nelx-jetappt-frontend'),
                    'message' => $this->get_template_canceled_client(),
                    'from' => $noreply_from
                ]
            ],
            [
                'name' => 'Appointment Rescheduled - Client',
                'form_id' => $saved_default_templates[5]['form_id'] ?? '0',
                'email_settings' => [
                    'to' => '{client_email}',
                    'cc' => '',
                    'bcc' => '',
                    'subject' => esc_html__('Your Appointment Has Been Rescheduled', 'nelx-jetappt-frontend'),
                    'message' => $this->get_template_rescheduled_client(),
                    'from' => $noreply_from
                ]
            ],
            [
                'name' => 'Appointment Rescheduled - Provider',
                'form_id' => $saved_default_templates[6]['form_id'] ?? '0',
                'email_settings' => [
                    'to' => '{provider_email}',
                    'cc' => '',
                    'bcc' => '',
                    'subject' => esc_html__('Appointment Rescheduled', 'nelx-jetappt-frontend'),
                    'message' => $this->get_template_rescheduled_provider(),
                    'from' => $noreply_from
                ]
            ],
            [
                'name' => 'Appointment Reminder',
                'form_id' => $saved_default_templates[7]['form_id'] ?? '0',
                'email_settings' => [
                    'to' => '{client_email}',
                    'cc' => '',
                    'bcc' => '',
                    'subject' => esc_html__('Appointment Reminder', 'nelx-jetappt-frontend'),
                    'message' => $this->get_template_reminder(),
                    'from' => $noreply_from
                ]
            ]
        ];
        
        foreach ($default_templates as $index => $template) {
            if (isset($saved_default_templates[$index])) {
                $default_templates[$index]['email_settings'] = array_merge(
                    $template['email_settings'],
                    $saved_default_templates[$index]['email_settings']
                );
            }
        }
        
        $system_placeholders = [
            ['value' => '{service_name}', 'label' => esc_html__('Service Name', 'nelx-jetappt-frontend')],
            ['value' => '{client_name}', 'label' => esc_html__('Client Name', 'nelx-jetappt-frontend')],
            ['value' => '{client_email}', 'label' => esc_html__('Client Email', 'nelx-jetappt-frontend')],
            ['value' => '{provider_name}', 'label' => esc_html__('Provider Name', 'nelx-jetappt-frontend')],
            ['value' => '{provider_email}', 'label' => esc_html__('Provider Email', 'nelx-jetappt-frontend')],
            ['value' => '{appointment_date}', 'label' => esc_html__('Appointment Date', 'nelx-jetappt-frontend')],
            ['value' => '{appointment_time}', 'label' => esc_html__('Appointment Time', 'nelx-jetappt-frontend')],
            ['value' => '{appointment_type}', 'label' => esc_html__('Appointment Type', 'nelx-jetappt-frontend')],
            ['value' => '{meeting_url}', 'label' => esc_html__('Meeting URL', 'nelx-jetappt-frontend')],
            ['value' => '{scheduled_date}', 'label' => esc_html__('Scheduled Date', 'nelx-jetappt-frontend')]
        ];
        ?>
        <div class="nelx-jetappt-settings-card">
            <h3><?php esc_html_e('Email Templates', 'nelx-jetappt-frontend'); ?></h3>
            <p><?php esc_html_e('Configure email templates for appointment notifications. These templates are used when emails are sent automatically.', 'nelx-jetappt-frontend'); ?></p>
            
            <div class="nelx-appt-template-tabs">
                <ul class="nelx-appt-template-tabs-nav">
                    <li><a href="#" class="active" data-tab="email-templates-tab"><?php esc_html_e('Email Templates', 'nelx-jetappt-frontend'); ?></a></li>
                    <li><a href="#" data-tab="automation-tab"><?php esc_html_e('Automation', 'nelx-jetappt-frontend'); ?></a></li>
                </ul>
        
                <div id="email-templates-tab" class="nelx-appt-template-tab-content active">
                    <h4><?php esc_html_e('Default Templates', 'nelx-jetappt-frontend'); ?></h4>
                    <p class="description"><?php esc_html_e('Pre-configured templates used by the system. You can customize the content below.', 'nelx-jetappt-frontend'); ?></p>
                    
                    <div class="nelx-jetappt-default-templates">
                        <?php foreach ($default_templates as $index => $template): ?>
                            <div class="nelx-jetappt-repeater-item" data-template-type="default" data-template-index="<?php echo esc_attr($index); ?>" data-saved-form-id="<?php echo esc_attr($template['form_id']); ?>">
                                <div class="nelx-jetappt-item-header">
                                    <button type="button" class="nelx-jetappt-item-toggle">
                                        <span class="dashicons dashicons-arrow-down"></span>
                                        <span class="item-title-text"><?php echo esc_html($template['name']); ?></span>
                                    </button>
                                    <div class="nelx-jetappt-item-actions">
                                        <span class="default-template-badge"><?php esc_html_e('System Template', 'nelx-jetappt-frontend'); ?></span>
                                    </div>
                                </div>
                                <div class="nelx-jetappt-item-content" style="display:none;">
                                    <?php if ($template['name'] === 'New Appointment - Provider'): ?>
                                        <div class="nelx-two-column-grid">
                                            <div class="nelx-grid-full">
                                                <div class="nelx-field-card">
                                                    <?php
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $this->get_field_html(
                                                        esc_html__('Template Name', 'nelx-jetappt-frontend'),
                                                        '<input type="text" 
                                                            name="' . esc_attr($this->option_name) . '[default_email_templates][' . esc_attr($index) . '][name]"
                                                            value="' . esc_attr($template['name']) . '" 
                                                            class="regular-text" readonly>',
                                                        esc_html__('Default template (read-only)', 'nelx-jetappt-frontend')
                                                    ); ?>
                                                </div>
                                            </div>
                                            
                                            <div class="nelx-grid-full">
                                                <div class="nelx-field-card">
                                                    <?php
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $this->get_field_html(
                                                        esc_html__('JetFormBuilder Form', 'nelx-jetappt-frontend'),
                                                        '<div class="njet-form-select-wrapper" style="position: relative; max-width: 450px;">
                                                            <select class="njet-form-select" style="display: none;" name="' . esc_attr($this->option_name) . '[default_email_templates][' . esc_attr($index) . '][form_id]">
                                                                <option value="">' . esc_html__('-- Select a Form --', 'nelx-jetappt-frontend') . '</option>
                                                                ' . $this->get_jfb_form_options() . '
                                                            </select>
                                                            <div class="njet-form-select-trigger">
                                                                <span class="trigger-text">' . esc_html__('-- Select a Form --', 'nelx-jetappt-frontend') . '</span>
                                                                <span class="dashicons dashicons-arrow-down"></span>
                                                            </div>
                                                            <div class="njet-form-select-dropdown" style="display: none;">
                                                                <div class="njet-form-search-header">
                                                                    <input type="text" class="njet-form-search" placeholder="' . esc_attr__('Search forms...', 'nelx-jetappt-frontend') . '" autocomplete="off">
                                                                </div>
                                                                <div class="njet-form-list"></div>
                                                            </div>
                                                        </div>',
                                                        esc_html__('Select a form to auto-load its fields for insertion.', 'nelx-jetappt-frontend')
                                                    ); ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="nelx-two-column-grid">
                                            <div class="nelx-grid-half">
                                                <div class="nelx-field-card">
                                                    <?php
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $this->render_email_field_with_insert(
                                                        [
                                                            'label' => esc_html__('To', 'nelx-jetappt-frontend'),
                                                            'name' => $this->option_name . '[default_email_templates][' . $index . '][email_settings][to]',
                                                            'value' => $template['email_settings']['to'] ?? '',
                                                            'placeholder' => esc_attr__('admin@example.com, {field_name}', 'nelx-jetappt-frontend'),
                                                            'description' => esc_html__('Recipient email addresses (comma separated). Use the + button to insert form fields.', 'nelx-jetappt-frontend'),
                                                            'field_id' => 'default_to_' . $index,
                                                            'show_insert_button' => true,
                                                            'insert_type' => 'form_fields'
                                                        ],
                                                        true
                                                    ); ?>
                                                </div>
                                            </div>
                                            <div class="nelx-grid-half">
                                                <div class="nelx-field-card">
                                                    <?php
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $this->render_email_field_with_insert(
                                                        [
                                                            'label' => esc_html__('CC', 'nelx-jetappt-frontend'),
                                                            'name' => $this->option_name . '[default_email_templates][' . $index . '][email_settings][cc]',
                                                            'value' => $template['email_settings']['cc'] ?? '',
                                                            'placeholder' => esc_attr__('cc@example.com, {field_name}', 'nelx-jetappt-frontend'),
                                                            'description' => esc_html__('CC email addresses (comma separated). Use the + button to insert form fields.', 'nelx-jetappt-frontend'),
                                                            'field_id' => 'default_cc_' . $index,
                                                            'show_insert_button' => true,
                                                            'insert_type' => 'form_fields'
                                                        ],
                                                        true
                                                    ); ?>
                                                </div>
                                            </div>
                                            <div class="nelx-grid-half">
                                                <div class="nelx-field-card">
                                                    <?php
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $this->render_email_field_with_insert(
                                                        [
                                                            'label' => esc_html__('BCC', 'nelx-jetappt-frontend'),
                                                            'name' => $this->option_name . '[default_email_templates][' . $index . '][email_settings][bcc]',
                                                            'value' => $template['email_settings']['bcc'] ?? '',
                                                            'placeholder' => esc_attr__('bcc@example.com, {field_name}', 'nelx-jetappt-frontend'),
                                                            'description' => esc_html__('BCC email addresses (comma separated). Use the + button to insert form fields.', 'nelx-jetappt-frontend'),
                                                            'field_id' => 'default_bcc_' . $index,
                                                            'show_insert_button' => true,
                                                            'insert_type' => 'form_fields'
                                                        ],
                                                        true
                                                    ); ?>
                                                </div>
                                            </div>
                                            <div class="nelx-grid-half">
                                                <div class="nelx-field-card">
                                                    <?php
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $this->render_email_field_with_insert(
                                                        [
                                                            'label' => esc_html__('Subject', 'nelx-jetappt-frontend'),
                                                            'name' => $this->option_name . '[default_email_templates][' . $index . '][email_settings][subject]',
                                                            'value' => $template['email_settings']['subject'] ?? '',
                                                            'placeholder' => esc_attr__('Your appointment on {appointment_date}', 'nelx-jetappt-frontend'),
                                                            'description' => esc_html__('Email subject. Use the + button to insert form fields.', 'nelx-jetappt-frontend'),
                                                            'field_id' => 'default_subject_' . $index,
                                                            'show_insert_button' => true,
                                                            'insert_type' => 'form_fields'
                                                        ],
                                                        true
                                                    ); ?>
                                                </div>
                                            </div>
                                            <div class="nelx-grid-half">
                                                <div class="nelx-field-card">
                                                    <?php
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $this->render_email_field_with_insert(
                                                        [
                                                            'label' => esc_html__('From', 'nelx-jetappt-frontend'),
                                                            'name' => $this->option_name . '[default_email_templates][' . $index . '][email_settings][from]',
                                                            'value' => $template['email_settings']['from'] ?? $noreply_from,
                                                            'placeholder' => esc_attr__('"Company Name" <noreply@example.com>', 'nelx-jetappt-frontend'),
                                                            'description' => esc_html__('The "From" header for the email. Use the + button to insert form fields.', 'nelx-jetappt-frontend'),
                                                            'field_id' => 'default_from_' . $index,
                                                            'show_insert_button' => true,
                                                            'insert_type' => 'form_fields'
                                                        ],
                                                        true
                                                    ); ?>
                                                </div>
                                            </div>
                                            <div class="nelx-grid-half">
                                                <div class="nelx-field-card">
                                                    <?php
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $this->get_field_html(
                                                        esc_html__('Email Sending Hook', 'nelx-jetappt-frontend'),
                                                        '<div class="nelx-jetappt-hook-name-container">
                                                            <input type="text" class="nelx-jetappt-hook-name" value="new_appointment_provider" readonly>
                                                            <button type="button" class="button nelx-jetappt-copy-hook" data-hook="new_appointment_provider">
                                                                ' . esc_html__('Copy Hook', 'nelx-jetappt-frontend') . '
                                                            </button>
                                                        </div>',
                                                        esc_html__('Copy this hook name to use in JetFormBuilder custom actions.', 'nelx-jetappt-frontend')
                                                    ); ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="nelx-grid-full">
                                            <div class="nelx-field-card">
                                                <?php 
                                                $message_id = 'default_email_msg_' . $index;
                                                $message_content = $template['email_settings']['message'] ?? '';
                                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                echo $this->render_email_editor_with_insert(
                                                    [
                                                        'label' => esc_html__('Message', 'nelx-jetappt-frontend'),
                                                        'name' => $this->option_name . '[default_email_templates][' . $index . '][email_settings][message]',
                                                        'value' => $message_content,
                                                        'description' => esc_html__('Email message content. Use the + button to insert form fields.', 'nelx-jetappt-frontend'),
                                                        'editor_id' => $message_id,
                                                        'show_insert_button' => true,
                                                        'insert_type' => 'form_fields'
                                                    ],
                                                    true
                                                ); ?>
                                            </div>
                                        </div>
                                        
                                    <?php else: ?>
                                        <div class="nelx-two-column-grid">
                                            <div class="nelx-grid-full">
                                                <div class="nelx-field-card">
                                                    <?php
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $this->get_field_html(
                                                        esc_html__('Template Name', 'nelx-jetappt-frontend'),
                                                        '<input type="text" 
                                                            name="' . esc_attr($this->option_name) . '[default_email_templates][' . esc_attr($index) . '][name]"
                                                            value="' . esc_attr($template['name']) . '" 
                                                            class="regular-text" readonly>',
                                                        esc_html__('Default template (read-only)', 'nelx-jetappt-frontend')
                                                    ); ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="nelx-two-column-grid">
                                            <div class="nelx-grid-half">
                                                <div class="nelx-field-card">
                                                    <?php
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $this->render_email_field_with_placeholder_insert(
                                                        [
                                                            'label' => esc_html__('To', 'nelx-jetappt-frontend'),
                                                            'name' => $this->option_name . '[default_email_templates][' . $index . '][email_settings][to]',
                                                            'value' => $template['email_settings']['to'] ?? '',
                                                            'placeholder' => esc_attr__('admin@example.com, {client_email}', 'nelx-jetappt-frontend'),
                                                            'description' => esc_html__('Recipient email addresses (comma separated). Use the + button to insert placeholders.', 'nelx-jetappt-frontend'),
                                                            'field_id' => 'default_to_' . $index,
                                                            'placeholders' => $system_placeholders
                                                        ],
                                                        true
                                                    ); ?>
                                                </div>
                                            </div>
                                            <div class="nelx-grid-half">
                                                <div class="nelx-field-card">
                                                    <?php
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $this->render_email_field_with_placeholder_insert(
                                                        [
                                                            'label' => esc_html__('CC', 'nelx-jetappt-frontend'),
                                                            'name' => $this->option_name . '[default_email_templates][' . $index . '][email_settings][cc]',
                                                            'value' => $template['email_settings']['cc'] ?? '',
                                                            'placeholder' => esc_attr__('cc@example.com', 'nelx-jetappt-frontend'),
                                                            'description' => esc_html__('CC email addresses (comma separated). Use the + button to insert placeholders.', 'nelx-jetappt-frontend'),
                                                            'field_id' => 'default_cc_' . $index,
                                                            'placeholders' => $system_placeholders
                                                        ],
                                                        true
                                                    ); ?>
                                                </div>
                                            </div>
                                            <div class="nelx-grid-half">
                                                <div class="nelx-field-card">
                                                    <?php
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $this->render_email_field_with_placeholder_insert(
                                                        [
                                                            'label' => esc_html__('BCC', 'nelx-jetappt-frontend'),
                                                            'name' => $this->option_name . '[default_email_templates][' . $index . '][email_settings][bcc]',
                                                            'value' => $template['email_settings']['bcc'] ?? '',
                                                            'placeholder' => esc_attr__('bcc@example.com', 'nelx-jetappt-frontend'),
                                                            'description' => esc_html__('BCC email addresses (comma separated). Use the + button to insert placeholders.', 'nelx-jetappt-frontend'),
                                                            'field_id' => 'default_bcc_' . $index,
                                                            'placeholders' => $system_placeholders
                                                        ],
                                                        true
                                                    ); ?>
                                                </div>
                                            </div>
                                            <div class="nelx-grid-half">
                                                <div class="nelx-field-card">
                                                    <?php
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $this->render_email_field_with_placeholder_insert(
                                                        [
                                                            'label' => esc_html__('Subject', 'nelx-jetappt-frontend'),
                                                            'name' => $this->option_name . '[default_email_templates][' . $index . '][email_settings][subject]',
                                                            'value' => $template['email_settings']['subject'] ?? '',
                                                            'placeholder' => esc_attr__('Your appointment on {appointment_date}', 'nelx-jetappt-frontend'),
                                                            'description' => esc_html__('Email subject. Use the + button to insert placeholders.', 'nelx-jetappt-frontend'),
                                                            'field_id' => 'default_subject_' . $index,
                                                            'placeholders' => $system_placeholders
                                                        ],
                                                        true
                                                    ); ?>
                                                </div>
                                            </div>
                                            <div class="nelx-grid-half">
                                                <div class="nelx-field-card">
                                                    <?php
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $this->render_email_field_with_placeholder_insert(
                                                        [
                                                            'label' => esc_html__('From', 'nelx-jetappt-frontend'),
                                                            'name' => $this->option_name . '[default_email_templates][' . $index . '][email_settings][from]',
                                                            'value' => $template['email_settings']['from'] ?? $noreply_from,
                                                            'placeholder' => esc_attr__('"Company Name" <noreply@example.com>', 'nelx-jetappt-frontend'),
                                                            'description' => esc_html__('The "From" header for the email. Use the + button to insert placeholders.', 'nelx-jetappt-frontend'),
                                                            'field_id' => 'default_from_' . $index,
                                                            'placeholders' => $system_placeholders
                                                        ],
                                                        true
                                                    ); ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="nelx-grid-full">
                                            <div class="nelx-field-card">
                                                <?php 
                                                $message_id = 'default_email_msg_' . $index;
                                                $message_content = $template['email_settings']['message'] ?? '';
                                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                echo $this->render_email_editor_with_placeholder_insert(
                                                    [
                                                        'label' => esc_html__('Message', 'nelx-jetappt-frontend'),
                                                        'name' => $this->option_name . '[default_email_templates][' . $index . '][email_settings][message]',
                                                        'value' => $message_content,
                                                        'description' => esc_html__('Email message content. Use the + button to insert placeholders.', 'nelx-jetappt-frontend'),
                                                        'editor_id' => $message_id,
                                                        'placeholders' => $system_placeholders
                                                    ],
                                                    true
                                                ); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <h4 style="margin-top: 30px;"><?php esc_html_e('Custom Templates', 'nelx-jetappt-frontend'); ?></h4>
                    <p class="description"><?php esc_html_e('Create your own custom email templates for specific needs. These templates ALWAYS have form field insertion enabled.', 'nelx-jetappt-frontend'); ?></p>
                    
                    <div class="nelx-jetappt-email-repeater" data-repeater-name="<?php echo esc_attr($this->option_name); ?>[custom_email_templates]">
                        <div class="nelx-jetappt-repeater-items">
                            <?php foreach ($custom_templates as $index => $template): ?>
                                <?php
                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                echo $this->render_custom_template_item($index, $template);
                                ?>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="button button-primary nelx-jetappt-add-template"><?php esc_html_e('Add Email Template', 'nelx-jetappt-frontend'); ?></button>
                    </div>
                </div>
        
                <div id="automation-tab" class="nelx-appt-template-tab-content">
                    <?php $this->render_automation_section(); ?>
                </div>
            </div>
        </div>
        
        <template id="njet-custom-template-item">
            <?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $this->render_custom_template_item('{index}', null, true);
            ?>
        </template>
        <?php
    }
    
    /**
     * Generate field HTML. All inputs are expected to be pre-escaped.
     */
    private function get_field_html($label, $field_html, $description = '') {
        $desc_html = '';
        if (!empty($description)) {
            $desc_html = '<p class="description">' . $description . '</p>';
        }
        
        return '
            <div class="nelx-field-label">
                <label>' . $label . '</label>
            </div>
            <div class="nelx-field-content">
                ' . $field_html . '
                ' . $desc_html . '
            </div>
        ';
    }
    
    private function get_logo_uploader_html($logo_url, $site_logo_url) {
        ob_start();
        ?>
        <div class="nelx-jetappt-logo-uploader">
            <input type="hidden" name="<?php echo esc_attr($this->email_branding_option_name); ?>[email_logo_url]" id="email_logo_url" value="<?php echo esc_attr($logo_url); ?>">
            
            <div id="nelx-jetappt-logo-dropzone" class="<?php echo empty($logo_url) ? 'empty' : ''; ?>">
                <?php if ($logo_url) : ?>
                    <img src="<?php echo esc_url($logo_url); ?>" class="logo-preview">
                    <div class="dropzone-overlay">
                        <span class="dashicons dashicons-update"></span>
                        <span><?php esc_html_e('Click or drag to replace', 'nelx-jetappt-frontend'); ?></span>
                    </div>
                <?php else : ?>
                    <div class="dropzone-content">
                        <span class="dashicons dashicons-format-image"></span>
                        <span><?php esc_html_e('Drag & drop logo here or', 'nelx-jetappt-frontend'); ?></span>
                        <button type="button" class="button button-primary nelx-jetappt-select-logo"><?php esc_html_e('Select Image', 'nelx-jetappt-frontend'); ?></button>
                        <small><?php esc_html_e('Recommended: 180×60px transparent PNG', 'nelx-jetappt-frontend'); ?></small>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="nelx-jetappt-logo-actions" style="margin-top: 10px; <?php echo empty($logo_url) ? 'display: none;' : ''; ?>">
                <button type="button" class="button nelx-jetappt-change-logo"><?php esc_html_e('Change Logo', 'nelx-jetappt-frontend'); ?></button>
                <button type="button" class="button nelx-jetappt-remove-logo"><?php esc_html_e('Remove Logo', 'nelx-jetappt-frontend'); ?></button>
            </div>
            
            <div class="nelx-jetappt-logo-fallback" style="margin-top: 15px; <?php echo !empty($logo_url) ? 'display: none;' : ''; ?>">
                <h4><?php esc_html_e('Current Logo Preview:', 'nelx-jetappt-frontend'); ?></h4>
                <?php if ($site_logo_url) : ?>
                    <img src="<?php echo esc_url($site_logo_url); ?>" style="max-width: 180px; max-height: 60px; border: 1px solid #ddd; padding: 5px;">
                    <p class="description"><?php esc_html_e('(Using your site logo as fallback)', 'nelx-jetappt-frontend'); ?></p>
                <?php else : ?>
                    <div style="width: 180px; height: 60px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border: 1px dashed #ccc;">
                        <span style="color: #666;"><?php esc_html_e('No logo set', 'nelx-jetappt-frontend'); ?></span>
                    </div>
                    <p class="description"><?php esc_html_e('(Will display your site name if no logo is set)', 'nelx-jetappt-frontend'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function render_email_field_with_placeholder_insert($args, $return_html = false) {
        $defaults = [
            'label' => '',
            'name' => '',
            'value' => '',
            'placeholder' => '',
            'description' => '',
            'field_id' => '',
            'input_style' => 'width: 100%;',
            'placeholders' => []
        ];
        $args = wp_parse_args($args, $defaults);
        $target_class = 'njet-insert-target-' . $args['field_id'];
        
        ob_start();
        ?>
        <div class="njet-insert-field-wrapper" style="display: flex; gap: 8px; align-items: flex-start; position: relative;">
            <input type="text" 
                id="<?php echo esc_attr($args['field_id']); ?>" 
                name="<?php echo esc_attr($args['name']); ?>"
                class="regular-text njet-insert-field-target <?php echo esc_attr($target_class); ?>" 
                value="<?php echo esc_attr($args['value']); ?>" 
                placeholder="<?php echo esc_attr($args['placeholder']); ?>"
                style="<?php echo esc_attr($args['input_style']); ?>"
                data-field-type="input">
            
            <button type="button" 
                    class="button njet-placeholder-insert-button" 
                    data-target-id="<?php echo esc_attr($args['field_id']); ?>"
                    data-target-class="<?php echo esc_attr($target_class); ?>"
                    title="<?php esc_attr_e('Insert placeholder', 'nelx-jetappt-frontend'); ?>">
                <span class="dashicons dashicons-plus-alt"></span>
                <span class="screen-reader-text"><?php esc_html_e('Insert Placeholder', 'nelx-jetappt-frontend'); ?></span>
            </button>
            
            <div class="njet-placeholder-dropdown" style="display: none;">
                <div class="njet-placeholder-header">
                    <input type="text" class="njet-placeholder-search" placeholder="<?php esc_attr_e('Search placeholders...', 'nelx-jetappt-frontend'); ?>" autocomplete="off">
                </div>
                <div class="njet-placeholder-items">
                    <div class="njet-placeholder-group">
                        <div class="njet-placeholder-group-title"><?php esc_html_e('Available Placeholders', 'nelx-jetappt-frontend'); ?></div>
                        <ul class="njet-placeholder-list">
                            <?php foreach ($args['placeholders'] as $placeholder): ?>
                                <li class="njet-placeholder-item" data-value="<?php echo esc_attr($placeholder['value']); ?>">
                                    <span class="njet-placeholder-name"><?php echo esc_html($placeholder['value']); ?></span>
                                    <span class="njet-placeholder-desc"><?php echo esc_html($placeholder['label']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php if (!empty($args['description'])): ?>
            <p class="description"><?php echo wp_kses_post($args['description']); ?></p>
        <?php endif; ?>
        <?php
        $output = $this->get_field_html($args['label'], ob_get_clean(), '');
        if ($return_html) {
            return $output;
        }
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $output;
    }
    
    private function render_email_editor_with_placeholder_insert($args, $return_html = false) {
        $defaults = [
            'label' => '',
            'name' => '',
            'value' => '',
            'description' => '',
            'editor_id' => '',
            'placeholders' => []
        ];
        $args = wp_parse_args($args, $defaults);
        $target_class = 'njet-insert-target-' . $args['editor_id'];
        
        ob_start();
        ?>
        <div class="njet-insert-field-wrapper" style="position: relative;">
            <textarea id="<?php echo esc_attr($args['editor_id']); ?>" 
                name="<?php echo esc_attr($args['name']); ?>"
                class="njet-insert-field-target njet-editor-textarea <?php echo esc_attr($target_class); ?>" 
                rows="15" 
                style="width: 100%;"
                data-field-type="textarea"><?php echo esc_textarea($args['value']); ?></textarea>
            
            <div class="njet-insert-buttons-group" style="position: absolute; top: 5px; right: 5px; z-index: 10;">
                <button type="button" 
                        class="button njet-placeholder-insert-button" 
                        data-target-id="<?php echo esc_attr($args['editor_id']); ?>"
                        data-target-class="<?php echo esc_attr($target_class); ?>"
                        title="<?php esc_attr_e('Insert placeholder', 'nelx-jetappt-frontend'); ?>">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <span class="screen-reader-text"><?php esc_html_e('Insert Placeholder', 'nelx-jetappt-frontend'); ?></span>
                </button>
            </div>
            
            <div class="njet-placeholder-dropdown" style="display: none;">
                <div class="njet-placeholder-header">
                    <input type="text" class="njet-placeholder-search" placeholder="<?php esc_attr_e('Search placeholders...', 'nelx-jetappt-frontend'); ?>" autocomplete="off">
                </div>
                <div class="njet-placeholder-items">
                    <div class="njet-placeholder-group">
                        <div class="njet-placeholder-group-title"><?php esc_html_e('Available Placeholders', 'nelx-jetappt-frontend'); ?></div>
                        <ul class="njet-placeholder-list">
                            <?php foreach ($args['placeholders'] as $placeholder): ?>
                                <li class="njet-placeholder-item" data-value="<?php echo esc_attr($placeholder['value']); ?>">
                                    <span class="njet-placeholder-name"><?php echo esc_html($placeholder['value']); ?></span>
                                    <span class="njet-placeholder-desc"><?php echo esc_html($placeholder['label']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php if (!empty($args['description'])): ?>
            <p class="description"><?php echo wp_kses_post($args['description']); ?></p>
        <?php endif; ?>
        <?php
        $output = $this->get_field_html($args['label'], ob_get_clean(), '');
        if ($return_html) {
            return $output;
        }
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $output;
    }
    
    private function render_email_field_with_insert($args, $return_html = false) {
        $defaults = [
            'label' => '',
            'name' => '',
            'value' => '',
            'placeholder' => '',
            'description' => '',
            'field_id' => '',
            'input_style' => 'width: 100%;',
            'show_insert_button' => true
        ];
        $args = wp_parse_args($args, $defaults);
        $target_class = 'njet-insert-target-' . $args['field_id'];
        
        ob_start();
        ?>
        <div class="njet-insert-field-wrapper" style="display: flex; gap: 8px; align-items: flex-start; position: relative;">
            <input type="text" 
                id="<?php echo esc_attr($args['field_id']); ?>" 
                name="<?php echo esc_attr($args['name']); ?>"
                class="regular-text njet-insert-field-target <?php echo esc_attr($target_class); ?>" 
                value="<?php echo esc_attr($args['value']); ?>" 
                placeholder="<?php echo esc_attr($args['placeholder']); ?>"
                style="<?php echo esc_attr($args['input_style']); ?>"
                data-field-type="input">
            
            <?php if ($args['show_insert_button']): ?>
                <button type="button" 
                        class="button njet-insert-field-button" 
                        data-target-id="<?php echo esc_attr($args['field_id']); ?>"
                        data-target-class="<?php echo esc_attr($target_class); ?>"
                        title="<?php esc_attr_e('Insert form field', 'nelx-jetappt-frontend'); ?>">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <span class="screen-reader-text"><?php esc_html_e('Insert Field', 'nelx-jetappt-frontend'); ?></span>
                </button>
                
                <div class="njet-quick-insert-dropdown" style="display: none;">
                    <div class="njet-quick-insert-header">
                        <input type="text" class="njet-quick-insert-search" placeholder="<?php esc_attr_e('Search fields...', 'nelx-jetappt-frontend'); ?>" autocomplete="off">
                    </div>
                    <div class="njet-quick-insert-fields">
                        <div class="njet-quick-insert-loading"><?php esc_html_e('Select a form first...', 'nelx-jetappt-frontend'); ?></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php if (!empty($args['description'])): ?>
            <p class="description"><?php echo wp_kses_post($args['description']); ?></p>
        <?php endif; ?>
        <?php
        $output = $this->get_field_html($args['label'], ob_get_clean(), '');
        if ($return_html) {
            return $output;
        }
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $output;
    }
    
    private function render_email_editor_with_insert($args, $return_html = false) {
        $defaults = [
            'label' => '',
            'name' => '',
            'value' => '',
            'description' => '',
            'editor_id' => '',
            'show_insert_button' => true
        ];
        $args = wp_parse_args($args, $defaults);
        $target_class = 'njet-insert-target-' . $args['editor_id'];
        
        ob_start();
        ?>
        <div class="njet-insert-field-wrapper" style="position: relative;">
            <textarea id="<?php echo esc_attr($args['editor_id']); ?>" 
                name="<?php echo esc_attr($args['name']); ?>"
                class="njet-insert-field-target njet-editor-textarea <?php echo esc_attr($target_class); ?>" 
                rows="15" 
                style="width: 100%;"
                data-field-type="textarea"><?php echo esc_textarea($args['value']); ?></textarea>
            
            <?php if ($args['show_insert_button']): ?>
                <div class="njet-insert-buttons-group" style="position: absolute; top: 5px; right: 5px; z-index: 10;">
                    <button type="button" 
                            class="button njet-insert-field-button" 
                            data-target-id="<?php echo esc_attr($args['editor_id']); ?>"
                            data-target-class="<?php echo esc_attr($target_class); ?>"
                            title="<?php esc_attr_e('Insert form field', 'nelx-jetappt-frontend'); ?>">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <span class="screen-reader-text"><?php esc_html_e('Insert Field', 'nelx-jetappt-frontend'); ?></span>
                    </button>
                </div>
                
                <div class="njet-quick-insert-dropdown" style="display: none;">
                    <div class="njet-quick-insert-header">
                        <input type="text" class="njet-quick-insert-search" placeholder="<?php esc_attr_e('Search fields...', 'nelx-jetappt-frontend'); ?>" autocomplete="off">
                    </div>
                    <div class="njet-quick-insert-fields">
                        <div class="njet-quick-insert-loading"><?php esc_html_e('Select a form first...', 'nelx-jetappt-frontend'); ?></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php if (!empty($args['description'])): ?>
            <p class="description"><?php echo wp_kses_post($args['description']); ?></p>
        <?php endif; ?>
        <?php
        $output = $this->get_field_html($args['label'], ob_get_clean(), '');
        if ($return_html) {
            return $output;
        }
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $output;
    }
    
    private function render_custom_template_item($index, $template = null, $is_template = false) {
        $repeater_name = $this->option_name . '[custom_email_templates]';
        $template_name = $is_template ? '' : ($template['name'] ?? '');
        $saved_form_id = $is_template ? '0' : ($template['form_id'] ?? '0');
        $to_value = $is_template ? '' : ($template['email_settings']['to'] ?? '');
        $cc_value = $is_template ? '' : ($template['email_settings']['cc'] ?? '');
        $bcc_value = $is_template ? '' : ($template['email_settings']['bcc'] ?? '');
        $subject_value = $is_template ? '' : ($template['email_settings']['subject'] ?? '');
        $message_value = $is_template ? '' : ($template['email_settings']['message'] ?? '');
        
        $home_url_host = wp_parse_url(home_url(), PHP_URL_HOST);
        $noreply_from = get_bloginfo('name') . ' <noreply@' . $home_url_host . '>';
        $from_value = $is_template ? $noreply_from : ($template['email_settings']['from'] ?? $noreply_from);
        
        $editor_id = $is_template ? 'custom_email_msg_{index}' : 'custom_email_msg_' . $index;
        $hook_name = $is_template ? 'custom_email_{index}' : 'custom_email_' . $index;
        
        ob_start();
        ?>
        <div class="nelx-jetappt-repeater-item" data-template-type="custom" data-template-index="<?php echo $is_template ? '{index}' : esc_attr($index); ?>" data-saved-form-id="<?php echo esc_attr($saved_form_id); ?>">
            <div class="nelx-jetappt-item-header">
                <button type="button" class="nelx-jetappt-item-toggle">
                    <span class="dashicons dashicons-arrow-down"></span>
                    <span class="item-title-text"><?php echo $is_template ? esc_html__('New Template', 'nelx-jetappt-frontend') : esc_html($template_name); ?></span>
                </button>
                <div class="nelx-jetappt-item-actions">
                    <button type="button" class="button nelx-jetappt-duplicate-item" title="<?php esc_attr_e('Duplicate', 'nelx-jetappt-frontend'); ?>">
                        <span class="dashicons dashicons-admin-page"></span>
                    </button>
                    <button type="button" class="button nelx-jetappt-remove-item" title="<?php esc_attr_e('Remove', 'nelx-jetappt-frontend'); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>
            <div class="nelx-jetappt-item-content" style="display:none;">
                <div class="nelx-two-column-grid">
                    <div class="nelx-grid-full">
                        <div class="nelx-field-card">
                            <?php
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo $this->get_field_html(
                                esc_html__('Template Name', 'nelx-jetappt-frontend'),
                                '<input type="text" name="' . esc_attr($repeater_name) . '[' . ($is_template ? '{index}' : esc_attr($index)) . '][name]" 
                                    value="' . esc_attr($template_name) . '" 
                                    class="regular-text template-name-input" required>',
                                esc_html__('A unique name for this email template', 'nelx-jetappt-frontend')
                            ); ?>
                        </div>
                    </div>
                    
                    <div class="nelx-grid-full">
                        <div class="nelx-field-card">
                            <?php
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo $this->get_field_html(
                                esc_html__('JetFormBuilder Form', 'nelx-jetappt-frontend'),
                                '<div class="njet-form-select-wrapper" style="position: relative; max-width: 450px;">
                                    <select class="njet-form-select" style="display: none;">
                                        <option value="">' . esc_html__('-- Select a Form --', 'nelx-jetappt-frontend') . '</option>
                                        ' . $this->get_jfb_form_options() . '
                                    </select>
                                    <div class="njet-form-select-trigger">
                                        <span class="trigger-text">' . esc_html__('-- Select a Form --', 'nelx-jetappt-frontend') . '</span>
                                        <span class="dashicons dashicons-arrow-down"></span>
                                    </div>
                                    <div class="njet-form-select-dropdown" style="display: none;">
                                        <div class="njet-form-search-header">
                                            <input type="text" class="njet-form-search" placeholder="' . esc_attr__('Search forms...', 'nelx-jetappt-frontend') . '" autocomplete="off">
                                        </div>
                                        <div class="njet-form-list"></div>
                                    </div>
                                </div>',
                                esc_html__('Select a form to load its fields for insertion.', 'nelx-jetappt-frontend')
                            ); ?>
                        </div>
                    </div>
                </div>
                
                <div class="nelx-two-column-grid">
                    <div class="nelx-grid-half">
                        <div class="nelx-field-card">
                            <?php
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo $this->render_email_field_with_insert([
                                'label' => esc_html__('To', 'nelx-jetappt-frontend'),
                                'name' => $repeater_name . '[' . ($is_template ? '{index}' : esc_attr($index)) . '][email_settings][to]',
                                'value' => $to_value,
                                'placeholder' => esc_attr__('admin@example.com, {field_name}', 'nelx-jetappt-frontend'),
                                'description' => esc_html__('Recipient email addresses (comma separated). Use the + button to insert form fields.', 'nelx-jetappt-frontend'),
                                'field_id' => 'custom_to_' . ($is_template ? '{index}' : esc_attr($index)),
                                'show_insert_button' => true
                            ], true); ?>
                        </div>
                    </div>
                    <div class="nelx-grid-half">
                        <div class="nelx-field-card">
                            <?php
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo $this->render_email_field_with_insert([
                                'label' => esc_html__('CC', 'nelx-jetappt-frontend'),
                                'name' => $repeater_name . '[' . ($is_template ? '{index}' : esc_attr($index)) . '][email_settings][cc]',
                                'value' => $cc_value,
                                'placeholder' => esc_attr__('cc@example.com, {field_name}', 'nelx-jetappt-frontend'),
                                'description' => esc_html__('CC email addresses (comma separated). Use the + button to insert form fields.', 'nelx-jetappt-frontend'),
                                'field_id' => 'custom_cc_' . ($is_template ? '{index}' : esc_attr($index)),
                                'show_insert_button' => true
                            ], true); ?>
                        </div>
                    </div>
                    <div class="nelx-grid-half">
                        <div class="nelx-field-card">
                            <?php
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo $this->render_email_field_with_insert([
                                'label' => esc_html__('BCC', 'nelx-jetappt-frontend'),
                                'name' => $repeater_name . '[' . ($is_template ? '{index}' : esc_attr($index)) . '][email_settings][bcc]',
                                'value' => $bcc_value,
                                'placeholder' => esc_attr__('bcc@example.com, {field_name}', 'nelx-jetappt-frontend'),
                                'description' => esc_html__('BCC email addresses (comma separated). Use the + button to insert form fields.', 'nelx-jetappt-frontend'),
                                'field_id' => 'custom_bcc_' . ($is_template ? '{index}' : esc_attr($index)),
                                'show_insert_button' => true
                            ], true); ?>
                        </div>
                    </div>
                    <div class="nelx-grid-half">
                        <div class="nelx-field-card">
                            <?php
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo $this->render_email_field_with_insert([
                                'label' => esc_html__('Subject', 'nelx-jetappt-frontend'),
                                'name' => $repeater_name . '[' . ($is_template ? '{index}' : esc_attr($index)) . '][email_settings][subject]',
                                'value' => $subject_value,
                                'placeholder' => esc_attr__('Your appointment on {appointment_date}', 'nelx-jetappt-frontend'),
                                'description' => esc_html__('Email subject. Use the + button to insert form fields.', 'nelx-jetappt-frontend'),
                                'field_id' => 'custom_subject_' . ($is_template ? '{index}' : esc_attr($index)),
                                'show_insert_button' => true
                            ], true); ?>
                        </div>
                    </div>
                    <div class="nelx-grid-half">
                        <div class="nelx-field-card">
                            <?php
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo $this->render_email_field_with_insert([
                                'label' => esc_html__('From', 'nelx-jetappt-frontend'),
                                'name' => $repeater_name . '[' . ($is_template ? '{index}' : esc_attr($index)) . '][email_settings][from]',
                                'value' => $from_value,
                                'placeholder' => esc_attr__('"Company Name" <noreply@example.com>', 'nelx-jetappt-frontend'),
                                'description' => esc_html__('The "From" header for the email. Use the + button to insert form fields.', 'nelx-jetappt-frontend'),
                                'field_id' => 'custom_from_' . ($is_template ? '{index}' : esc_attr($index)),
                                'show_insert_button' => true
                            ], true); ?>
                        </div>
                    </div>
                    <div class="nelx-grid-half">
                        <div class="nelx-field-card">
                            <?php
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo $this->get_field_html(
                                esc_html__('Email Sending Hook', 'nelx-jetappt-frontend'),
                                '<div class="nelx-jetappt-hook-name-container">
                                    <input type="text" class="nelx-jetappt-hook-name" value="' . esc_attr($hook_name) . '" readonly>
                                    <button type="button" class="button nelx-jetappt-copy-hook" data-hook="' . esc_attr($hook_name) . '">
                                        ' . esc_html__('Copy Hook', 'nelx-jetappt-frontend') . '
                                    </button>
                                </div>',
                                esc_html__('Copy this hook name to use in JetFormBuilder custom actions.', 'nelx-jetappt-frontend')
                            ); ?>
                        </div>
                    </div>
                </div>
                
                <div class="nelx-two-column-grid">
                    <div class="nelx-grid-full">
                        <div class="nelx-field-card">
                            <?php
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo $this->render_email_editor_with_insert([
                                'label' => esc_html__('Message', 'nelx-jetappt-frontend'),
                                'name' => $repeater_name . '[' . ($is_template ? '{index}' : esc_attr($index)) . '][email_settings][message]',
                                'value' => $message_value,
                                'description' => esc_html__('Email message content. Use the + button to insert form fields.', 'nelx-jetappt-frontend'),
                                'editor_id' => $editor_id,
                                'show_insert_button' => true
                            ], true); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function get_jfb_form_options() {
        $forms = get_posts([
            'post_type' => 'jet-form-builder',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        
        $options = '';
        foreach ($forms as $form) {
            $options .= sprintf(
                '<option value="%d" data-title="%s">%s (ID: %d)</option>',
                esc_attr($form->ID),
                esc_attr($form->post_title),
                esc_html($form->post_title),
                esc_attr($form->ID)
            );
        }
        
        return $options;
    }
    
    private function render_automation_section() {
        $options = get_option($this->option_name);
        $reminder_timing = $options['reminder_timing'] ?? '24';
        $auto_delete_past = $options['auto_delete_past'] ?? '0';
        $auto_delete_past_days = $options['auto_delete_past_days'] ?? '7';
        $auto_delete_past_custom = $options['auto_delete_past_custom'] ?? '';
        $auto_delete_canceled = $options['auto_delete_canceled'] ?? '0';
        
        $php_path = $this->get_php_path_for_cron();
        $cron_file_path = NELXJAF_PLUGIN_DIR . 'nelx-appointment-cron-handler.php';
        $cron_command = $php_path . ' ' . $cron_file_path . ' >/dev/null 2>&1';
        ?>
        <h4><?php esc_html_e('Appointment Reminder Automation', 'nelx-jetappt-frontend'); ?></h4>
        <p class="description"><?php esc_html_e('Configure automated appointment reminder emails.', 'nelx-jetappt-frontend'); ?></p>
        
        <div class="nelx-masonry-grid">
            <div class="nelx-masonry-item">
                <div class="nelx-field-card">
                    <?php
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo $this->get_field_html(
                        esc_html__('Send Reminder', 'nelx-jetappt-frontend'),
                        '<select id="nelx-reminder-timing" name="' . esc_attr($this->option_name) . '[reminder_timing]" class="regular-text">
                            <option value="1" ' . selected($reminder_timing, '1', false) . '>' . esc_html__('1 hour before', 'nelx-jetappt-frontend') . '</option>
                            <option value="2" ' . selected($reminder_timing, '2', false) . '>' . esc_html__('2 hours before', 'nelx-jetappt-frontend') . '</option>
                            <option value="4" ' . selected($reminder_timing, '4', false) . '>' . esc_html__('4 hours before', 'nelx-jetappt-frontend') . '</option>
                            <option value="6" ' . selected($reminder_timing, '6', false) . '>' . esc_html__('6 hours before', 'nelx-jetappt-frontend') . '</option>
                            <option value="12" ' . selected($reminder_timing, '12', false) . '>' . esc_html__('12 hours before', 'nelx-jetappt-frontend') . '</option>
                            <option value="24" ' . selected($reminder_timing, '24', false) . '>' . esc_html__('24 hours before', 'nelx-jetappt-frontend') . '</option>
                            <option value="48" ' . selected($reminder_timing, '48', false) . '>' . esc_html__('48 hours before', 'nelx-jetappt-frontend') . '</option>
                            <option value="72" ' . selected($reminder_timing, '72', false) . '>' . esc_html__('72 hours before', 'nelx-jetappt-frontend') . '</option>
                        </select>',
                        esc_html__('When to send appointment reminder emails before the appointment time', 'nelx-jetappt-frontend')
                    ); ?>
                </div>
            </div>
            
            <div class="nelx-masonry-item">
                <div class="nelx-field-card">
                    <?php
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo $this->get_field_html(
                        esc_html__('Automatic Delete Past Appointments', 'nelx-jetappt-frontend'),
                        '<label class="nelx-appt-switch">
                            <input type="checkbox" id="auto-delete-past" 
                                   name="' . esc_attr($this->option_name) . '[auto_delete_past]" 
                                   value="1" ' . checked('1', $auto_delete_past, false) . '>
                            <span class="nelx-appt-slider nelx-appt-round"></span>
                        </label>
                        <div id="auto-delete-past-settings" style="margin-top: 15px; ' . ($auto_delete_past ? '' : 'display: none;') . '">
                            <select id="auto-delete-past-days" name="' . esc_attr($this->option_name) . '[auto_delete_past_days]" class="regular-text">
                                <option value="3" ' . selected($auto_delete_past_days, '3', false) . '>' . esc_html__('3 days', 'nelx-jetappt-frontend') . '</option>
                                <option value="7" ' . selected($auto_delete_past_days, '7', false) . '>' . esc_html__('7 days', 'nelx-jetappt-frontend') . '</option>
                                <option value="21" ' . selected($auto_delete_past_days, '21', false) . '>' . esc_html__('21 days', 'nelx-jetappt-frontend') . '</option>
                                <option value="30" ' . selected($auto_delete_past_days, '30', false) . '>' . esc_html__('30 days', 'nelx-jetappt-frontend') . '</option>
                                <option value="custom" ' . selected($auto_delete_past_days, 'custom', false) . '>' . esc_html__('Custom', 'nelx-jetappt-frontend') . '</option>
                            </select>
                            <div id="auto-delete-past-custom-container" style="margin-top: 10px; ' . (($auto_delete_past_days === 'custom') ? '' : 'display: none;') . '">
                                <input type="number" id="auto-delete-past-custom" 
                                       name="' . esc_attr($this->option_name) . '[auto_delete_past_custom]" 
                                       value="' . esc_attr($auto_delete_past_custom) . '" 
                                       min="1" class="small-text" placeholder="' . esc_attr__('Enter days', 'nelx-jetappt-frontend') . '">
                                <span class="description">' . esc_html__('days', 'nelx-jetappt-frontend') . '</span>
                                <p class="description">' . esc_html__('Minimum: 1 day', 'nelx-jetappt-frontend') . '</p>
                            </div>
                            <p class="description">' . esc_html__('Automatically delete appointments that are older than the selected number of days. This runs during cron execution.', 'nelx-jetappt-frontend') . '</p>
                        </div>',
                        ''
                    ); ?>
                </div>
            </div>
            
            <div class="nelx-masonry-item">
                <div class="nelx-field-card">
                    <?php
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo $this->get_field_html(
                        esc_html__('Automatic Delete Canceled Appointments', 'nelx-jetappt-frontend'),
                        '<label class="nelx-appt-switch">
                            <input type="checkbox" id="auto-delete-canceled" 
                                   name="' . esc_attr($this->option_name) . '[auto_delete_canceled]" 
                                   value="1" ' . checked('1', $auto_delete_canceled, false) . '>
                            <span class="nelx-appt-slider nelx-appt-round"></span>
                        </label>
                        <div id="auto-delete-canceled-settings" style="margin-top: 15px; ' . ($auto_delete_canceled ? '' : 'display: none;') . '">
                            <p class="description">' . esc_html__('Automatically delete appointments marked as canceled. This happens on every cron run when enabled.', 'nelx-jetappt-frontend') . '</p>
                        </div>',
                        ''
                    ); ?>
                </div>
            </div>
            
            <div class="nelx-masonry-item">
                <div class="nelx-field-card">
                    <?php
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo $this->get_field_html(
                        esc_html__('Cron Command', 'nelx-jetappt-frontend'),
                        '<code id="nelx-appt-cron-command" style="display: block; word-break: break-all; background: #f5f5f5; padding: 10px; border-radius: 4px;">' . esc_html($cron_command) . '</code>
                        <div class="cron-command-container" style="margin-top: 10px;">
                            <button type="button" id="nelx-appt-copy-cron" class="button">
                                <span class="dashicons dashicons-admin-page"></span>
                                ' . esc_html__('Copy Command', 'nelx-jetappt-frontend') . '
                            </button>
                        </div>
                        <p class="description">
                            ' . esc_html__('Add this to your crontab with your preferred schedule (e.g. "0 * * * *" for hourly execution). This will process both reminders AND automatic deletions.', 'nelx-jetappt-frontend') . '
                        </p>
                        <p class="description">
                            <strong>' . esc_html__('Note:', 'nelx-jetappt-frontend') . '</strong> ' . esc_html__('If this command doesn\'t work, run "which php" in your terminal to find the correct PHP path for your server.', 'nelx-jetappt-frontend') . '
                        </p>',
                        ''
                    ); ?>
                </div>
            </div>
            
            <div class="nelx-masonry-item">
                <div class="nelx-field-card">
                    <?php
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo $this->get_field_html(
                        esc_html__('Cron Verification', 'nelx-jetappt-frontend'),
                        '<button type="button" id="nelx-appt-test-cron" class="button">
                            ' . esc_html__('Verify Cron Setup', 'nelx-jetappt-frontend') . '
                        </button>
                        <div id="nelx-appt-cron-test-result" style="margin-top:10px;"></div>
                        <div id="nelx-appt-cron-manual-confirm" style="margin-top:15px; display:none; padding:15px; background:#f8f9fa; border:1px solid #ddd; border-radius:4px;">
                            <p style="margin-top:0;"><strong>' . esc_html__('Manual Confirmation Required', 'nelx-jetappt-frontend') . '</strong></p>
                            <p>' . esc_html__('Since shell_exec is disabled on this server, we cannot automatically verify the cron setup.', 'nelx-jetappt-frontend') . '</p>
                            <p>' . esc_html__('Please confirm that you have added the cron command to your crontab:', 'nelx-jetappt-frontend') . '</p>
                            <pre style="background:#f1f1f1;padding:10px;border-radius:4px;overflow-x:auto;border:1px solid #ddd;">* * * * * ' . esc_html($php_path) . ' ' . esc_html($cron_file_path) . ' >/dev/null 2>&1</pre>
                            <button type="button" id="nelx-appt-confirm-cron" class="button button-primary">
                                <span class="dashicons dashicons-yes"></span>
                                ' . esc_html__('I have added the cron command', 'nelx-jetappt-frontend') . '
                            </button>
                            <p class="description" style="margin-top:10px; margin-bottom:0;">' . esc_html__('Click this button after you have added the cron command to your crontab.', 'nelx-jetappt-frontend') . '</p>
                        </div>
                        <p class="description">
                            ' . esc_html__('This test checks if your cron job is properly configured.', 'nelx-jetappt-frontend') . '
                        </p>',
                        ''
                    ); ?>
                </div>
            </div>
        </div>
        
        <h4 style="margin-top: 40px;"><?php esc_html_e('Manual Deletion', 'nelx-jetappt-frontend'); ?></h4>
        <p class="description"><?php esc_html_e('Manually delete appointments from the database. Use with caution!', 'nelx-jetappt-frontend'); ?></p>
        
        <div class="nelx-masonry-grid">
            <div class="nelx-masonry-item">
                <div class="nelx-field-card">
                    <?php
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo $this->get_field_html(
                        esc_html__('Manually Delete Past Appointments', 'nelx-jetappt-frontend'),
                        '<div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                            <input type="number" id="manual-delete-past-days" min="3" value="30" class="small-text" style="width: 80px;">
                            <span class="description">' . esc_html__('Days', 'nelx-jetappt-frontend') . '</span>
                            <button type="button" id="nelx-manual-delete-past" class="button">
                                ' . esc_html__('Delete Past Appointments', 'nelx-jetappt-frontend') . '
                            </button>
                        </div>
                        <p class="description">' . esc_html__('Delete appointments older than the specified number of days. Minimum: 3 days.', 'nelx-jetappt-frontend') . '</p>',
                        ''
                    ); ?>
                </div>
            </div>
            
            <div class="nelx-masonry-item">
                <div class="nelx-field-card">
                    <?php
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo $this->get_field_html(
                        esc_html__('Manually Delete Canceled Appointments', 'nelx-jetappt-frontend'),
                        '<button type="button" id="nelx-manual-delete-canceled" class="button">
                            ' . esc_html__('Delete Canceled Appointments', 'nelx-jetappt-frontend') . '
                        </button>
                        <p class="description">' . esc_html__('Delete all appointments with canceled status.', 'nelx-jetappt-frontend') . '</p>',
                        ''
                    ); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_notifications_section() {
        $options = get_option($this->option_name);
        $notifications_enabled = $options['notifications_enabled'] ?? '0';
        $provider_appointments_page = $options['provider_appointments_page'] ?? '';
        $client_appointments_page = $options['client_appointments_page'] ?? '';
        
        $nelx_notifications_active = $this->is_nelx_notifications_active();
        $is_disabled = !$nelx_notifications_active ? 'disabled' : '';
        ?>
        <div class="nelx-jetappt-settings-card">
            <h3><?php esc_html_e('Nelx Notifications', 'nelx-jetappt-frontend'); ?></h3>
            <p><?php esc_html_e('Configure notifications. This feature allows users to receive real-time notifications about appointments (new, confirmations, canceled, and reminders).', 'nelx-jetappt-frontend'); ?></p>
            
            <div class="nelx-two-column-grid">
                <div class="nelx-grid-full">
                    <div class="nelx-field-card">
                        <?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo $this->get_field_html(
                            esc_html__('Enable Nelx Notifications', 'nelx-jetappt-frontend'),
                            '<label class="nelx-appt-switch">
                                <input type="checkbox" id="notifications-enabled" 
                                       name="' . esc_attr($this->option_name) . '[notifications_enabled]" 
                                       value="1" ' . checked('1', $notifications_enabled, false) . ' ' . esc_attr($is_disabled) . '>
                                <span class="nelx-appt-slider nelx-appt-round"></span>
                            </label>' . 
                            (!$nelx_notifications_active ? '<p class="description" style="color: #ff0000;">' . esc_html__('Nelx Notifications plugin is not active. Please install and activate it to use this feature.', 'nelx-jetappt-frontend') . '</p>' : ''),
                            ''
                        ); ?>
                    </div>
                </div>
            </div>
            
            <div id="notifications-fields-wrapper" style="<?php echo $notifications_enabled ? '' : 'display: none;'; ?>">
                <div class="nelx-two-column-grid">
                    <div class="nelx-grid-half">
                        <div class="nelx-field-card">
                            <?php
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo $this->get_field_html(
                                esc_html__('Provider Appointments Listing Page URL', 'nelx-jetappt-frontend'),
                                '<input type="url" id="provider-appointments-page" name="' . esc_attr($this->option_name) . '[provider_appointments_page]" 
                                       value="' . esc_url($provider_appointments_page) . '" class="regular-text" ' . esc_attr($is_disabled) . '
                                       placeholder="https://example.com/dashboard/appointments">
                                <p class="description">' . esc_html__('Enter the full URL where providers can view their appointments.', 'nelx-jetappt-frontend') . '</p>',
                                ''
                            ); ?>
                        </div>
                    </div>
                    <div class="nelx-grid-half">
                        <div class="nelx-field-card">
                            <?php
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo $this->get_field_html(
                                esc_html__('Client/User Appointments Listing Page URL', 'nelx-jetappt-frontend'),
                                '<input type="url" id="client-appointments-page" name="' . esc_attr($this->option_name) . '[client_appointments_page]" 
                                       value="' . esc_url($client_appointments_page) . '" class="regular-text" ' . esc_attr($is_disabled) . '
                                       placeholder="https://example.com/dashboard/client-appointments/">
                                <p class="description">' . esc_html__('Enter the full URL where clients can view their appointments.', 'nelx-jetappt-frontend') . '</p>',
                                ''
                            ); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_shortcodes_section($shortcodes) {
        ?>
        <div class="nelx-jetappt-settings-card">
            <h3><?php esc_html_e('Available Shortcodes', 'nelx-jetappt-frontend'); ?></h3>
            <p><?php esc_html_e('Click the copy button to copy a shortcode to your clipboard.', 'nelx-jetappt-frontend'); ?></p>
            
            <div class="nelx-shortcode-grid">
                <?php foreach ($shortcodes as $sc): ?>
                <div class="nelx-shortcode-item">
                    <div class="nelx-shortcode-header">
                        <span class="nelx-shortcode-label"><?php echo esc_html($sc[0]); ?></span>
                        <button class="button button-primary nelx-copy-btn" type="button">
                            <span class="dashicons dashicons-admin-page"></span>
                            <?php esc_html_e('Copy', 'nelx-jetappt-frontend'); ?>
                        </button>
                    </div>
                    <div class="nelx-shortcode-description">
                        <?php echo esc_html($sc[1]); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
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
    
    public function get_template_new_appointment_provider() {
        return '
            <div class="nelx-appointment-details">
                <p><strong>' . esc_html__('Service:', 'nelx-jetappt-frontend') . '</strong> {service_name}</p>
                <p><strong>' . esc_html__('Client:', 'nelx-jetappt-frontend') . '</strong> {client_name} ({client_email})</p>
                <p><strong>' . esc_html__('Date:', 'nelx-jetappt-frontend') . '</strong> {scheduled_date}</p>
                <p><strong>' . esc_html__('Time:', 'nelx-jetappt-frontend') . '</strong> {appointment_time}</p>
                <p><strong>' . esc_html__('Type:', 'nelx-jetappt-frontend') . '</strong> {appointment_type}</p>
            </div>
            <p>' . esc_html__('A Google Meet link has been created for this appointment. Click the button below to join the meeting:', 'nelx-jetappt-frontend') . '</p>
            <div class="nelx-btn-container">
                <a href="{meeting_url}" class="nelx-email-button" target="_blank">' . esc_html__('Join Google Meet', 'nelx-jetappt-frontend') . '</a>
            </div>
            {if appointment_type === "physical"}
                <p><strong>' . esc_html__('Note:', 'nelx-jetappt-frontend') . '</strong> ' . esc_html__('This is scheduled as a physical meeting, but the client has the option to join online if needed.', 'nelx-jetappt-frontend') . '</p>
            {/if}';
    }
    
    public function get_template_confirmation_online() {
        return '
            <h3>' . esc_html__('Dear {client_name},', 'nelx-jetappt-frontend') . '</h3>
            <p>' . esc_html__('Your online appointment has been scheduled with {provider_name}.', 'nelx-jetappt-frontend') . '</p>
            <div class="nelx-appointment-details">
                <p><strong>' . esc_html__('Service:', 'nelx-jetappt-frontend') . '</strong> {service_name}</p>
                <p><strong>' . esc_html__('Provider:', 'nelx-jetappt-frontend') . '</strong> {provider_name}</p>
                <p><strong>' . esc_html__('Date:', 'nelx-jetappt-frontend') . '</strong> {appointment_date}</p>
                <p><strong>' . esc_html__('Time:', 'nelx-jetappt-frontend') . '</strong> {appointment_time}</p>
                <p><strong>' . esc_html__('Meeting Link:', 'nelx-jetappt-frontend') . '</strong> <a href="{meeting_url}">' . esc_html__('Join Google Meet', 'nelx-jetappt-frontend') . '</a></p>
            </div>
            <p>' . esc_html__('Please join the meeting 5 minutes before your scheduled time.', 'nelx-jetappt-frontend') . '</p>
            <div class="nelx-btn-container">
                <a href="{meeting_url}" class="nelx-email-button" target="_blank">' . esc_html__('Join Google Meet', 'nelx-jetappt-frontend') . '</a>
            </div>
            <p><small>' . esc_html__('Note: This link will become active at the scheduled time of your appointment.', 'nelx-jetappt-frontend') . '</small></p>
            <p>' . esc_html__('We look forward to seeing you online!', 'nelx-jetappt-frontend') . '</p>';
    }
    
    public function get_template_confirmation_physical() {
        return '
            <h3>' . esc_html__('Dear {client_name},', 'nelx-jetappt-frontend') . '</h3>
            <p>' . esc_html__('Your appointment has been confirmed with {provider_name}.', 'nelx-jetappt-frontend') . '</p>
            <div class="nelx-appointment-details">
                <p><strong>' . esc_html__('Service:', 'nelx-jetappt-frontend') . '</strong> {service_name}</p>
                <p><strong>' . esc_html__('Provider:', 'nelx-jetappt-frontend') . '</strong> {provider_name}</p>
                <p><strong>' . esc_html__('Date:', 'nelx-jetappt-frontend') . '</strong> {appointment_date}</p>
                <p><strong>' . esc_html__('Time:', 'nelx-jetappt-frontend') . '</strong> {appointment_time}</p>
            </div>
            <p>' . esc_html__('Please arrive 10 minutes before your scheduled time.', 'nelx-jetappt-frontend') . '</p>
            <p><strong>' . esc_html__('Note:', 'nelx-jetappt-frontend') . '</strong> ' . esc_html__('If your circumstances change and you cannot make it to the physical meeting, you can use the online meeting link below to join remotely:', 'nelx-jetappt-frontend') . '</p>
            <div class="nelx-btn-container">
                <a href="{meeting_url}" class="nelx-email-button" target="_blank">' . esc_html__('Join Online Meeting', 'nelx-jetappt-frontend') . '</a>
            </div>
            <p><small>' . esc_html__('Please inform the provider if you plan to use the online option.', 'nelx-jetappt-frontend') . '</small></p>
            <p>' . esc_html__('We look forward to seeing you!', 'nelx-jetappt-frontend') . '</p>';
    }
    
    public function get_template_canceled_provider() {
        return '
            <h3>' . esc_html__('Appointment Canceled by Client', 'nelx-jetappt-frontend') . '</h3>
            <p>' . esc_html__('A client has canceled their appointment.', 'nelx-jetappt-frontend') . '</p>
            <div class="nelx-appointment-details">
                <p><strong>' . esc_html__('Service:', 'nelx-jetappt-frontend') . '</strong> {service_name}</p>
                <p><strong>' . esc_html__('Client:', 'nelx-jetappt-frontend') . '</strong> {client_name} ({client_email})</p>
                <p><strong>' . esc_html__('Date:', 'nelx-jetappt-frontend') . '</strong> {appointment_date}</p>
                <p><strong>' . esc_html__('Time:', 'nelx-jetappt-frontend') . '</strong> {appointment_time}</p>
            </div>';
    }
    
    public function get_template_canceled_client() {
        return '
            <h3>' . esc_html__('Your Appointment Has Been Canceled', 'nelx-jetappt-frontend') . '</h3>
            <p>' . esc_html__('We regret to inform you that your appointment has been canceled by the provider.', 'nelx-jetappt-frontend') . '</p>
            <div class="nelx-appointment-details">
                <p><strong>' . esc_html__('Service:', 'nelx-jetappt-frontend') . '</strong> {service_name}</p>
                <p><strong>' . esc_html__('Provider:', 'nelx-jetappt-frontend') . '</strong> {provider_name}</p>
                <p><strong>' . esc_html__('Date:', 'nelx-jetappt-frontend') . '</strong> {appointment_date}</p>
                <p><strong>' . esc_html__('Time:', 'nelx-jetappt-frontend') . '</strong> {appointment_time}</p>
            </div>
            <p>' . esc_html__('If you have any questions or would like to reschedule, please contact us.', 'nelx-jetappt-frontend') . '</p>';
    }
    
    public function get_template_rescheduled_client() {
        return '
            <h3>' . esc_html__('Your Appointment Has Been Rescheduled', 'nelx-jetappt-frontend') . '</h3>
            <p>' . esc_html__('Your appointment has been rescheduled.', 'nelx-jetappt-frontend') . '</p>
            <div class="nelx-appointment-details">
                <p><strong>' . esc_html__('Service:', 'nelx-jetappt-frontend') . '</strong> {service_name}</p>
                <p><strong>' . esc_html__('Provider:', 'nelx-jetappt-frontend') . '</strong> {provider_name}</p>
                <p><strong>' . esc_html__('Date:', 'nelx-jetappt-frontend') . '</strong> {appointment_date}</p>
                <p><strong>' . esc_html__('Time:', 'nelx-jetappt-frontend') . '</strong> {appointment_time}</p>
                {if appointment_type==="online"}
                    <p><strong>' . esc_html__('Meeting Link:', 'nelx-jetappt-frontend') . '</strong> <a href="{meeting_url}">' . esc_html__('Join Google Meet', 'nelx-jetappt-frontend') . '</a></p>
                {/if}
            </div>
            <p>' . esc_html__('If you have any questions, please contact us.', 'nelx-jetappt-frontend') . '</p>';
    }
    
    public function get_template_rescheduled_provider() {
        return '
            <h3>' . esc_html__('Appointment Rescheduled', 'nelx-jetappt-frontend') . '</h3>
            <p>' . esc_html__('An appointment has been rescheduled.', 'nelx-jetappt-frontend') . '</p>
            <div class="nelx-appointment-details">
                <p><strong>' . esc_html__('Service:', 'nelx-jetappt-frontend') . '</strong> {service_name}</p>
                <p><strong>' . esc_html__('Client:', 'nelx-jetappt-frontend') . '</strong> {client_name} ({client_email})</p>
                <p><strong>' . esc_html__('Date:', 'nelx-jetappt-frontend') . '</strong> {appointment_date}</p>
                <p><strong>' . esc_html__('Time:', 'nelx-jetappt-frontend') . '</strong> {appointment_time}</p>
                {if appointment_type==="online"}
                    <p><strong>' . esc_html__('Meeting Link:', 'nelx-jetappt-frontend') . '</strong> <a href="{meeting_url}">' . esc_html__('Join Google Meet', 'nelx-jetappt-frontend') . '</a></p>
                {/if}
            </div>
            <p>' . esc_html__('Please update your calendar accordingly.', 'nelx-jetappt-frontend') . '</p>';
    }
    
    public function get_template_reminder() {
        return '
            <h3>' . esc_html__('Appointment Reminder', 'nelx-jetappt-frontend') . '</h3>
            <p>' . esc_html__('This is a reminder for your upcoming appointment.', 'nelx-jetappt-frontend') . '</p>
            <div class="nelx-appointment-details">
                <p><strong>' . esc_html__('Service:', 'nelx-jetappt-frontend') . '</strong> {service_name}</p>
                <p><strong>' . esc_html__('Provider:', 'nelx-jetappt-frontend') . '</strong> {provider_name}</p>
                <p><strong>' . esc_html__('Date:', 'nelx-jetappt-frontend') . '</strong> {appointment_date}</p>
                <p><strong>' . esc_html__('Time:', 'nelx-jetappt-frontend') . '</strong> {appointment_time}</p>
                {if appointment_type==="online"}
                    <p><strong>' . esc_html__('Meeting Link:', 'nelx-jetappt-frontend') . '</strong> <a href="{meeting_url}">' . esc_html__('Join Google Meet', 'nelx-jetappt-frontend') . '</a></p>
                {/if}
            </div>
            <p>' . esc_html__('We look forward to seeing you!', 'nelx-jetappt-frontend') . '</p>';
    }
    
    private function is_nelx_notifications_active() {
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        
        return is_plugin_active('nelx-notification-system/nelx-notification-system.php') || 
               class_exists('Nelx_Notification_System');
    }
}
