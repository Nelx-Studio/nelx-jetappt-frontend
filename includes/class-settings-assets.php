<?php
/**
 * Settings Assets Class - Handles enqueuing scripts and styles for settings page
 */

if (!defined('ABSPATH')) exit;

class NELXJAF_Settings_Assets {
    
    private static $instance = null;
    private $option_name = 'nelx_jetappt_settings';
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }
    
    /**
     * Get JetFormBuilder form options as HTML for localization
     */
    private function get_jfb_form_options_html() {
        $forms = get_posts([
            'post_type' => 'jet-form-builder',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        
        $options = '<option value="">' . __('-- Select a Form --', 'nelx-jetappt-frontend') . '</option>';
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
    
    public function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_nelx-jetappt-settings') {
            return;
        }
        
        $plugin_dir = NELXJAF_PLUGIN_DIR;
        $plugin_url = NELXJAF_PLUGIN_URL;
        
        wp_enqueue_media();
        wp_enqueue_editor();
        wp_enqueue_code_editor(['type' => 'text/html']);
        
        $css_files = [
            'nelx-jetappt-settings.min.css',
            'nelx-custom-emails-settings.min.css'
        ];
        
        foreach ($css_files as $css_file) {
            $css_path = $plugin_dir . 'assets/css/' . $css_file;
            if (file_exists($css_path)) {
                wp_enqueue_style(
                    'nelx-jetappt-' . str_replace('.css', '', $css_file),
                    $plugin_url . 'assets/css/' . $css_file,
                    [],
                    filemtime($css_path)
                );
            }
        }
        
        wp_add_inline_style('nelx-jetappt-nelx-jetappt-settings.min', '.nelx-column-repeater-items{display:flex;flex-direction:column;gap:8px;margin-bottom:10px}.nelx-column-repeater-item{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr) auto auto auto;gap:5px;align-items:center}.nelx-column-repeater-item select,.nelx-column-repeater-item input{min-width:0;width:100%;max-width:100%;box-sizing:border-box}.nelx-column-repeater-item .button{min-width:34px;padding:0 8px}.nelx-column-repeater-item .nelx-remove-column{color:#b32d2e}.nelx-column-repeater-item .nelx-move-column{color:inherit}.nelx-column-repeater .description{margin-top:10px}.nelx-appointment-config-columns{grid-template-columns:1fr!important}.nelx-remove-confirm-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:100000;display:flex;align-items:center;justify-content:center;padding:20px;box-sizing:border-box}.nelx-remove-confirm-modal{width:min(440px,100%);background:#fff;border-radius:8px;box-shadow:0 12px 40px rgba(0,0,0,.2);padding:22px}.nelx-remove-confirm-modal h3{margin:0 0 10px;font-size:18px}.nelx-remove-confirm-modal p{margin:0 0 20px;color:#50575e}.nelx-remove-confirm-actions{display:flex;justify-content:flex-end;gap:8px}@media(max-width:782px){.nelx-column-repeater-item{grid-template-columns:1fr 1fr}.nelx-column-repeater-item .nelx-move-column,.nelx-column-repeater-item .nelx-remove-column{justify-self:start}}');

        $js_files = [
            'nelx-jetappt-settings.min.js',
            'nelx-custom-emails-settings.min.js'
        ];
        
        foreach ($js_files as $js_file) {
            $js_path = $plugin_dir . 'assets/js/' . $js_file;
            if (file_exists($js_path)) {
                wp_enqueue_script(
                    'nelx-jetappt-' . str_replace('.js', '', $js_file),
                    $plugin_url . 'assets/js/' . $js_file,
                    ['jquery', 'wp-color-picker', 'editor', 'quicktags'],
                    filemtime($js_path),
                    true
                );
            }
        }
        
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        $form_options_html = $this->get_jfb_form_options_html();
        
        $home_url_host = wp_parse_url(home_url(), PHP_URL_HOST);
        $default_from = get_bloginfo('name') . ' <noreply@' . $home_url_host . '>';
        
        $localization_data = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nelx_jetappt_nonce'),
            'default_from' => $default_from,
            'plugin_url' => $plugin_url,
            'form_options_html' => $form_options_html,
            'i18n' => [
                'new_email_template' => __('New Email Template', 'nelx-jetappt-frontend'),
                'template_name' => __('Template Name', 'nelx-jetappt-frontend'),
                'template_name_desc' => __('A unique name for this email template', 'nelx-jetappt-frontend'),
                'duplicate' => __('Duplicate', 'nelx-jetappt-frontend'),
                'remove' => __('Remove', 'nelx-jetappt-frontend'),
                'copy' => __('Copy', 'nelx-jetappt-frontend'),
                'confirm_remove' => __('Are you sure you want to remove this template?', 'nelx-jetappt-frontend'),
                'cannot_remove_default' => __('Cannot remove default templates.', 'nelx-jetappt-frontend'),
                'cannot_duplicate_default' => __('Cannot duplicate default templates.', 'nelx-jetappt-frontend'),
                'expand_all' => __('Expand All', 'nelx-jetappt-frontend'),
                'collapse_all' => __('Collapse All', 'nelx-jetappt-frontend'),
                
                'jetformbuilder_form_id' => __('JetFormBuilder Form', 'nelx-jetappt-frontend'),
                'select_form' => __('-- Select a Form --', 'nelx-jetappt-frontend'),
                'select_form_first' => __('Please select a form first', 'nelx-jetappt-frontend'),
                'search_forms' => __('Search forms...', 'nelx-jetappt-frontend'),
                'search_fields' => __('Search fields...', 'nelx-jetappt-frontend'),
                'enter_form_id' => __('Enter Form ID to load fields', 'nelx-jetappt-frontend'),
                'load_fields' => __('Load Fields', 'nelx-jetappt-frontend'),
                'loading' => __('Loading...', 'nelx-jetappt-frontend'),
                'form_id_desc' => __('Select a form to load its fields for insertion.', 'nelx-jetappt-frontend'),
                'available_form_fields' => __('Available Form Fields', 'nelx-jetappt-frontend'),
                'load_fields_first' => __('Please select a form first', 'nelx-jetappt-frontend'),
                'insert_field' => __('Insert Field', 'nelx-jetappt-frontend'),
                'select_field' => __('Select a field first', 'nelx-jetappt-frontend'),
                'focus_field' => __('Please click in the field where you want to insert the tag first', 'nelx-jetappt-frontend'),
                'inserted' => __('Inserted!', 'nelx-jetappt-frontend'),
                'fields_loaded_success' => __('Loaded!', 'nelx-jetappt-frontend'),
                'load_error' => __('Error loading fields. Please check the form ID.', 'nelx-jetappt-frontend'),
                'no_fields' => __('No fields found. Please select a different form.', 'nelx-jetappt-frontend'),
                'load_fields_instructions_1' => __('1. Select a form from the dropdown above', 'nelx-jetappt-frontend'),
                'load_fields_instructions_2' => __('2. Click the + button next to any field to insert form fields', 'nelx-jetappt-frontend'),
                
                'to' => __('To', 'nelx-jetappt-frontend'),
                'to_desc' => __('Recipient email addresses (comma separated). Use the + button to insert form fields.', 'nelx-jetappt-frontend'),
                'cc' => __('CC', 'nelx-jetappt-frontend'),
                'cc_desc' => __('CC email addresses (comma separated). Use the + button to insert form fields.', 'nelx-jetappt-frontend'),
                'bcc' => __('BCC', 'nelx-jetappt-frontend'),
                'bcc_desc' => __('BCC email addresses (comma separated). Use the + button to insert form fields.', 'nelx-jetappt-frontend'),
                'subject' => __('Subject', 'nelx-jetappt-frontend'),
                'subject_desc' => __('Email subject. Use the + button to insert form fields.', 'nelx-jetappt-frontend'),
                'message' => __('Message', 'nelx-jetappt-frontend'),
                'message_desc' => __('Email message content. HTML is supported. Use the + button to insert form fields.', 'nelx-jetappt-frontend'),
                'from' => __('From', 'nelx-jetappt-frontend'),
                'from_desc' => __('The "From" header for the email (e.g., "Company Name &lt;email@example.com&gt;"). Use the + button to insert form fields.', 'nelx-jetappt-frontend'),
                'switch_to_html' => __('Switch to HTML editor', 'nelx-jetappt-frontend'),
                'switch_to_visual' => __('Switch to visual editor', 'nelx-jetappt-frontend'),
                
                'email_hook' => __('Email Sending Hook', 'nelx-jetappt-frontend'),
                'copy_hook' => __('Copy Hook', 'nelx-jetappt-frontend'),
                'copied' => __('Copied!', 'nelx-jetappt-frontend'),
                'copy_failed' => __('Failed to copy!', 'nelx-jetappt-frontend'),
                'hook_desc' => __('Copy this hook name and use it in JetFormBuilder custom action to send email', 'nelx-jetappt-frontend'),
                'hook_example' => __("add_action('jet-form-builder/custom-action/{hook_name}', function(\$request, \$action_handler) { ... });", 'nelx-jetappt-frontend'),
                
                'url' => __('URL', 'nelx-jetappt-frontend'),
                'icon' => __('Icon', 'nelx-jetappt-frontend'),
                'select_image' => __('Select Icon', 'nelx-jetappt-frontend'),
                'use_image' => __('Use This Logo', 'nelx-jetappt-frontend'),
                'change_image' => __('Change Icon', 'nelx-jetappt-frontend'),
                'remove_icon' => __('Remove Icon Set', 'nelx-jetappt-frontend'),
                'add_social_icon' => __('Add Social Icon', 'nelx-jetappt-frontend'),
                
                'select_logo' => __('Select Logo', 'nelx-jetappt-frontend'),
                'change_logo' => __('Change Logo', 'nelx-jetappt-frontend'),
                'remove_logo' => __('Remove Logo', 'nelx-jetappt-frontend'),
                'drag_drop' => __('Drag & drop logo here or', 'nelx-jetappt-frontend'),
                'click_to_select' => __('Click to select', 'nelx-jetappt-frontend'),
                'recommended_size' => __('Recommended: 180×60px transparent PNG', 'nelx-jetappt-frontend'),
                
                'saving' => __('Saving...', 'nelx-jetappt-frontend'),
                'saved' => __('Saved!', 'nelx-jetappt-frontend'),
                'settings_saved' => __('Settings saved successfully!', 'nelx-jetappt-frontend'),
                'ajax_error' => __('Error saving settings', 'nelx-jetappt-frontend'),
                'confirm_reset' => __('Are you sure you want to reset all settings? This cannot be undone.', 'nelx-jetappt-frontend'),
                'reset' => __('Reset to Defaults', 'nelx-jetappt-frontend'),
                'reset_success' => __('Settings reset successfully!', 'nelx-jetappt-frontend'),
                'confirm_remove_column' => __('Remove item?', 'nelx-jetappt-frontend'),
                'confirm_remove_column_desc' => __('Are you sure you want to remove this column/field?', 'nelx-jetappt-frontend'),
                
                'test_cron' => __('Test Cron', 'nelx-jetappt-frontend'),
                'cron_test_success' => __('Cron test completed successfully!', 'nelx-jetappt-frontend'),
                'cron_test_failed' => __('Cron test failed. Check error log for details.', 'nelx-jetappt-frontend'),
                'manual_delete_confirm' => __('Are you sure you want to delete these appointments? This action cannot be undone.', 'nelx-jetappt-frontend'),
                'delete_success' => __('Appointments deleted successfully!', 'nelx-jetappt-frontend'),
                'delete_failed' => __('Failed to delete appointments.', 'nelx-jetappt-frontend'),
            ]
        ];
        
        wp_register_script('nelx-jetappt-admin-scripts', false, [], '1.0.0', true);
        wp_enqueue_script('nelx-jetappt-admin-scripts');
        wp_localize_script('nelx-jetappt-admin-scripts', 'nelx_jetappt_data', $localization_data);

        wp_add_inline_script('nelx-jetappt-admin-scripts', <<<'JS'
jQuery(function($) {
    function refreshMoveButtons($box) {
        $box.find('.nelx-column-repeater-item').each(function(i) {
            $(this).find('.nelx-move-up').prop('disabled', i === 0);
            $(this).find('.nelx-move-down').prop('disabled', i === $box.find('.nelx-column-repeater-item').length - 1);
        });
    }
    function syncLabelName($item) {
        var $select = $item.find('select').first();
        var $label = $item.find('.nelx-column-custom-label').first();
        if (!$select.length || !$label.length) return;
        var key = $select.val() || '';
        var name = $label.attr('name') || '';
        if (!name) return;
        name = name.replace(/\[[^\]]+\]$/, '[' + key + ']');
        $label.attr('name', name);
        var optionText = $select.find('option:selected').text();
        if (!$label.val()) $label.attr('placeholder', optionText || 'Custom label (optional)');
    }
    $(document).on('click', '.nelx-add-column', function() {
        var $repeater = $(this).closest('.nelx-column-repeater');
        var template = $repeater.find('.nelx-column-template').html();
        if (template) {
            var $item = $(template);
            $repeater.find('.nelx-column-repeater-items').append($item);
            syncLabelName($item);
        }
        refreshMoveButtons($repeater);
    });
    $(document).on('change', '.nelx-column-repeater-item select', function() {
        syncLabelName($(this).closest('.nelx-column-repeater-item'));
    });
    var pendingRemoval = null;
    function openRemoveConfirm($item, $repeater) {
        pendingRemoval = { item: $item, repeater: $repeater };
        if ($('#nelx-remove-confirm-overlay').length) return;
        var html = '<div id="nelx-remove-confirm-overlay" class="nelx-remove-confirm-overlay" role="dialog" aria-modal="true" aria-labelledby="nelx-remove-confirm-title">' +
            '<div class="nelx-remove-confirm-modal">' +
            '<h3 id="nelx-remove-confirm-title">' + (nelx_jetappt_data.i18n.confirm_remove_column || 'Remove item?') + '</h3>' +
            '<p>' + (nelx_jetappt_data.i18n.confirm_remove_column_desc || 'Are you sure you want to remove this column/field?') + '</p>' +
            '<div class="nelx-remove-confirm-actions"><button type="button" class="button" id="nelx-remove-confirm-cancel">' + (nelx_jetappt_data.i18n.cancel || 'Cancel') + '</button><button type="button" class="button button-primary" id="nelx-remove-confirm-ok">' + (nelx_jetappt_data.i18n.remove || 'Remove') + '</button></div>' +
            '</div></div>';
        $('body').append(html);
        $('#nelx-remove-confirm-ok').trigger('focus');
    }
    function closeRemoveConfirm() {
        $('#nelx-remove-confirm-overlay').remove();
        pendingRemoval = null;
    }
    $(document).on('click', '.nelx-remove-column', function() {
        var $item = $(this).closest('.nelx-column-repeater-item');
        openRemoveConfirm($item, $item.closest('.nelx-column-repeater'));
    });
    $(document).on('click', '#nelx-remove-confirm-cancel', function() { closeRemoveConfirm(); });
    $(document).on('click', '#nelx-remove-confirm-ok', function() {
        if (pendingRemoval && pendingRemoval.item && pendingRemoval.item.length) {
            pendingRemoval.item.remove();
            refreshMoveButtons(pendingRemoval.repeater);
        }
        closeRemoveConfirm();
    });
    $(document).on('click', '#nelx-remove-confirm-overlay', function(e) {
        if (e.target === this) closeRemoveConfirm();
    });
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#nelx-remove-confirm-overlay').length) closeRemoveConfirm();
    });
    $(document).on('click', '.nelx-move-up', function() {
        var $item = $(this).closest('.nelx-column-repeater-item');
        $item.prev().before($item);
        refreshMoveButtons($item.closest('.nelx-column-repeater'));
    });
    $(document).on('click', '.nelx-move-down', function() {
        var $item = $(this).closest('.nelx-column-repeater-item');
        $item.next().after($item);
        refreshMoveButtons($item.closest('.nelx-column-repeater'));
    });
    $('.nelx-column-repeater').each(function(){
        var $repeater = $(this);
        $repeater.find('.nelx-column-repeater-item').each(function(){ syncLabelName($(this)); });
        refreshMoveButtons($repeater);
    });
});
JS
);

        
        wp_localize_script('nelx-jetappt-nelx-jetappt-settings', 'nelx_jetappt_data', $localization_data);
        wp_localize_script('nelx-jetappt-nelx-custom-emails-settings', 'nelx_jetappt_data', $localization_data);
        
        add_action('admin_footer', [$this, 'add_editor_initialization_script']);
    }
    
    public function add_editor_initialization_script() {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            window.nelx_init_editor = function(textarea_id, content) {
                if (typeof wp !== 'undefined' && wp.editor && typeof wp.editor.initialize === 'function') {
                    if (window.tinyMCE && window.tinyMCE.get(textarea_id)) {
                        window.tinyMCE.get(textarea_id).remove();
                    }
                    
                    var settings = {
                        tinymce: {
                            wpautop: true,
                            plugins: 'charmap,colorpicker,hr,lists,media,paste,tabfocus,textcolor,fullscreen,wordpress,wpautoresize,wpeditimage,wpgallery,wplink,wptextpattern',
                            toolbar1: 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,wp_more,spellchecker,fullscreen,wp_adv',
                            toolbar2: 'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
                            toolbar3: '',
                            toolbar4: '',
                            height: 350,
                            min_height: 350,
                            max_height: 600,
                            resize: true
                        },
                        quicktags: true,
                        mediaButtons: false
                    };
                    
                    wp.editor.initialize(textarea_id, settings);
                    
                    if (content !== undefined && content !== '') {
                        setTimeout(function() {
                            if (window.tinyMCE && window.tinyMCE.get(textarea_id)) {
                                window.tinyMCE.get(textarea_id).setContent(content);
                            } else {
                                $('#' + textarea_id).val(content);
                            }
                        }, 100);
                    }
                    
                    return true;
                }
                return false;
            };
            
            $('.nelx-jetappt-item-content textarea[id^="default_email_msg_"], .nelx-jetappt-item-content textarea[id^="custom_email_msg_"]').each(function() {
                var textarea = $(this);
                var id = textarea.attr('id');
                if (id && !textarea.closest('.wp-editor-wrap').length) {
                    setTimeout(function() {
                        nelx_init_editor(id, textarea.val());
                        textarea.closest('.nelx-jetappt-item-content').addClass('wp-editor-initialized');
                    }, 100);
                }
            });
            
            $(document).on('click', '.nelx-jetappt-item-toggle', function() {
                var itemContent = $(this).closest('.nelx-jetappt-repeater-item').find('.nelx-jetappt-item-content');
                if (itemContent.is(':visible')) {
                    setTimeout(function() {
                        itemContent.find('textarea[id^="default_email_msg_"], textarea[id^="custom_email_msg_"]').each(function() {
                            var textarea = $(this);
                            var id = textarea.attr('id');
                            if (id && !textarea.closest('.wp-editor-wrap').length) {
                                nelx_init_editor(id, textarea.val());
                                textarea.closest('.nelx-jetappt-item-content').addClass('wp-editor-initialized');
                            }
                        });
                    }, 200);
                }
            });
            
            $(document).on('nelx_repeater_item_added', function(event, newItem) {
                setTimeout(function() {
                    newItem.find('textarea[id^="custom_email_msg_"]').each(function() {
                        var textarea = $(this);
                        var id = textarea.attr('id');
                        if (id && !textarea.closest('.wp-editor-wrap').length) {
                            nelx_init_editor(id, '');
                            textarea.closest('.nelx-jetappt-item-content').addClass('wp-editor-initialized');
                        }
                    });
                }, 100);
            });
            
            $(document).trigger('nelx_repeater_item_ready');
        });
        </script>
        <?php
    }
}

NELXJAF_Settings_Assets::instance();
