<?php
if (!defined('ABSPATH')) exit;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

abstract class NELXJAF_Appointment_Listing_Widget_Base extends Widget_Base {
    protected $view = 'staff';
    protected $display = 'table';

    public function get_categories() { return ['nelx-jetappt']; }

    public function get_style_depends() {
        if (class_exists('NELXJAF_Appointment_Listings')) {
            NELXJAF_Appointment_Listings::instance(NELXJAF_Core::instance())->register_assets();
        }
        return ['nelx-appointment-listings'];
    }

    public function get_script_depends() {
        // The editor preview is static and does not need the listing runtime.
        if (class_exists('Elementor\Plugin') && isset(\Elementor\Plugin::$instance->editor) && \Elementor\Plugin::$instance->editor->is_edit_mode()) {
            return [];
        }
        if (class_exists('NELXJAF_Appointment_Listings')) {
            NELXJAF_Appointment_Listings::instance(NELXJAF_Core::instance())->register_assets();
        }
        return ['nelx-appointment-listings'];
    }

    protected function table_style_controls() {
        $this->start_controls_section('section_table_style', [
            'label' => esc_html__('Table', 'nelx-jetappt-frontend'),
            'tab' => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('header_bg', [
            'label'=>esc_html__('Header Background','nelx-jetappt-frontend'),
            'type'=>Controls_Manager::COLOR,
            'selectors'=>['{{WRAPPER}} .nelx-appointment-table th'=>'background-color: {{VALUE}};'],
        ]);
        $this->add_control('header_text', [
            'label'=>esc_html__('Header Text Color','nelx-jetappt-frontend'),
            'type'=>Controls_Manager::COLOR,
            'selectors'=>['{{WRAPPER}} .nelx-appointment-table th'=>'color: {{VALUE}};'],
        ]);
        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'=>'header_typography',
            'label'=>esc_html__('Header Typography','nelx-jetappt-frontend'),
            'selector'=>'{{WRAPPER}} .nelx-appointment-table th',
        ]);
        $this->add_control('cell_bg', [
            'label'=>esc_html__('Cell Background','nelx-jetappt-frontend'),
            'type'=>Controls_Manager::COLOR,
            'selectors'=>['{{WRAPPER}} .nelx-appointment-table td'=>'background-color: {{VALUE}};'],
        ]);
        $this->add_control('cell_text', [
            'label'=>esc_html__('Cell Text Color','nelx-jetappt-frontend'),
            'type'=>Controls_Manager::COLOR,
            'selectors'=>['{{WRAPPER}} .nelx-appointment-table td'=>'color: {{VALUE}};'],
        ]);
        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'=>'body_typography',
            'label'=>esc_html__('Body Typography','nelx-jetappt-frontend'),
            'selector'=>'{{WRAPPER}} .nelx-appointment-table td',
        ]);
        $this->add_responsive_control('header_padding', [
            'label'=>esc_html__('Header Padding','nelx-jetappt-frontend'),
            'type'=>Controls_Manager::DIMENSIONS,
            'size_units'=>['px','em'],
            'selectors'=>['{{WRAPPER}} .nelx-appointment-table th'=>'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);
        $this->add_responsive_control('cell_padding', [
            'label'=>esc_html__('Cell Padding','nelx-jetappt-frontend'),
            'type'=>Controls_Manager::DIMENSIONS,
            'size_units'=>['px','em'],
            'selectors'=>['{{WRAPPER}} .nelx-appointment-table td'=>'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);
        $this->add_control('row_hover_bg', [
            'label'=>esc_html__('Row Hover Background','nelx-jetappt-frontend'),
            'type'=>Controls_Manager::COLOR,
            'selectors'=>['{{WRAPPER}} .nelx-appointment-table tbody tr:hover td'=>'background-color: {{VALUE}};'],
        ]);
        $this->add_control('row_stripe_bg', [
            'label'=>esc_html__('Alternate Row Background','nelx-jetappt-frontend'),
            'type'=>Controls_Manager::COLOR,
            'selectors'=>['{{WRAPPER}} .nelx-appointment-table tbody tr:nth-child(even) td'=>'background-color: {{VALUE}};'],
        ]);
        $this->end_controls_section();

        $this->start_controls_section('section_controls_style', [
            'label'=>esc_html__('Table Controls','nelx-jetappt-frontend'),
            'tab'=>Controls_Manager::TAB_STYLE,
        ]);
        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'=>'controls_typography',
            'label'=>esc_html__('Typography','nelx-jetappt-frontend'),
            'selector'=>'{{WRAPPER}} .nelx-appointment-table-toolbar, {{WRAPPER}} .nelx-appointment-table-footer, {{WRAPPER}} .nelx-appointment-table-export button, {{WRAPPER}} .nelx-appointment-table-pagination button',
        ]);
        $this->add_control('control_text', [
            'label'=>esc_html__('Controls Text Color','nelx-jetappt-frontend'),
            'type'=>Controls_Manager::COLOR,
            'selectors'=>[
                '{{WRAPPER}} .nelx-appointment-table-toolbar, {{WRAPPER}} .nelx-appointment-table-footer'=>'color: {{VALUE}};',
                '{{WRAPPER}} .nelx-appointment-table-search input, {{WRAPPER}} .nelx-appointment-table-length select'=>'color: {{VALUE}};',
                '{{WRAPPER}} .nelx-appointment-table-export button, {{WRAPPER}} .nelx-appointment-table-pagination button'=>'color: {{VALUE}};',
            ],
        ]);
        $this->add_control('control_background', [
            'label'=>esc_html__('Control Background','nelx-jetappt-frontend'),
            'type'=>Controls_Manager::COLOR,
            'selectors'=>[
                '{{WRAPPER}} .nelx-appointment-table-export button, {{WRAPPER}} .nelx-appointment-table-pagination button'=>'background-color: {{VALUE}};',
            ],
        ]);
        $this->add_control('control_hover_text', [
            'label'=>esc_html__('Control Hover Text Color','nelx-jetappt-frontend'),
            'type'=>Controls_Manager::COLOR,
            'selectors'=>['{{WRAPPER}} .nelx-appointment-table-export button:hover, {{WRAPPER}} .nelx-appointment-table-pagination button:hover:not(:disabled), {{WRAPPER}} .nelx-appointment-table-pagination button[aria-current="page"]'=>'color: {{VALUE}};'],
        ]);
        $this->add_control('control_hover_background', [
            'label'=>esc_html__('Control Hover Background','nelx-jetappt-frontend'),
            'type'=>Controls_Manager::COLOR,
            'selectors'=>['{{WRAPPER}} .nelx-appointment-table-export button:hover, {{WRAPPER}} .nelx-appointment-table-pagination button:hover:not(:disabled), {{WRAPPER}} .nelx-appointment-table-pagination button[aria-current="page"]'=>'background-color: {{VALUE}};'],
        ]);
        $this->add_control('search_border_color', [
            'label'=>esc_html__('Search / Length Border Color','nelx-jetappt-frontend'),
            'type'=>Controls_Manager::COLOR,
            'selectors'=>['{{WRAPPER}} .nelx-appointment-table-search input, {{WRAPPER}} .nelx-appointment-table-length select'=>'border-color: {{VALUE}} !important;'],
        ]);
        $this->end_controls_section();

        $this->action_buttons_style_controls($this->view === 'client' ? ['edit','reject','info'] : ['edit','confirm','reject','info']);
    }

    protected function action_buttons_style_controls($buttons) {
        $labels = [
            'edit' => esc_html__('Edit / Reschedule Button','nelx-jetappt-frontend'),
            'confirm' => esc_html__('Approve Button','nelx-jetappt-frontend'),
            'reject' => esc_html__('Cancel Button','nelx-jetappt-frontend'),
            'info' => esc_html__('Info Button','nelx-jetappt-frontend'),
        ];

        $this->start_controls_section('section_action_button_size', [
            'label' => esc_html__('Action Buttons General Style', 'nelx-jetappt-frontend'),
            'tab' => Controls_Manager::TAB_STYLE,
        ]);
        $this->add_responsive_control('action_button_width', [
            'label' => esc_html__('Button Width', 'nelx-jetappt-frontend'),
            'type' => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range' => ['px' => ['min' => 16, 'max' => 80, 'step' => 1]],
            'selectors' => ['{{WRAPPER}} .nelx-actions-inline .nelx-icon-btn, {{WRAPPER}} .nelx-client-actions-inline .nelx-icon-btn' => 'width: {{SIZE}}{{UNIT}};'],
        ]);
        $this->add_responsive_control('action_button_height', [
            'label' => esc_html__('Button Height', 'nelx-jetappt-frontend'),
            'type' => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range' => ['px' => ['min' => 16, 'max' => 80, 'step' => 1]],
            'selectors' => ['{{WRAPPER}} .nelx-actions-inline .nelx-icon-btn, {{WRAPPER}} .nelx-client-actions-inline .nelx-icon-btn' => 'height: {{SIZE}}{{UNIT}};'],
        ]);
        $this->add_responsive_control('action_icon_size', [
            'label' => esc_html__('Icon Size', 'nelx-jetappt-frontend'),
            'type' => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range' => ['px' => ['min' => 8, 'max' => 40, 'step' => 1]],
            'selectors' => ['{{WRAPPER}} .nelx-actions-inline .nelx-icon-btn svg, {{WRAPPER}} .nelx-client-actions-inline .nelx-icon-btn svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};'],
        ]);
        $this->add_responsive_control('action_button_gap', [
            'label' => esc_html__('Button Gap', 'nelx-jetappt-frontend'),
            'type' => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range' => ['px' => ['min' => 0, 'max' => 20, 'step' => 1]],
            'selectors' => ['{{WRAPPER}} .nelx-actions-inline, {{WRAPPER}} .nelx-client-actions-inline' => 'gap: {{SIZE}}{{UNIT}};'],
        ]);
        $this->add_control('action_transition_duration', [
            'label' => esc_html__('Transition Duration', 'nelx-jetappt-frontend'),
            'type' => Controls_Manager::SLIDER,
            'range' => ['px' => ['min' => 0, 'max' => 3, 'step' => 0.1]],
            'default' => ['size' => 0.2],
            'selectors' => ['{{WRAPPER}} .nelx-actions-inline .nelx-icon-btn, {{WRAPPER}} .nelx-client-actions-inline .nelx-icon-btn' => 'transition-duration: {{SIZE}}s;'],
        ]);
        $this->add_control('action_disabled_opacity', [
            'label' => esc_html__('Disabled Opacity', 'nelx-jetappt-frontend'),
            'type' => Controls_Manager::SLIDER,
            'range' => ['px' => ['min' => 0.10, 'max' => 1, 'step' => 0.01]],
            'default' => ['size' => 0.5],
            'selectors' => ['{{WRAPPER}} .nelx-actions-inline .nelx-icon-btn:disabled, {{WRAPPER}} .nelx-client-actions-inline .nelx-icon-btn:disabled' => 'opacity: {{SIZE}};'],
        ]);
        $this->add_control('action_disabled_bg_color', [
            'label' => esc_html__('Disabled Background', 'nelx-jetappt-frontend'),
            'type' => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .nelx-actions-inline .nelx-icon-btn:disabled, {{WRAPPER}} .nelx-client-actions-inline .nelx-icon-btn:disabled' => 'background-color: {{VALUE}};'],
        ]);
        $this->end_controls_section();

        foreach ($buttons as $button) {
            $class = '.nelx-' . $button;
            $key = $button . '_button';
            $this->start_controls_section('section_' . $key, [
                'label' => $labels[$button],
                'tab' => Controls_Manager::TAB_STYLE,
            ]);
            $this->start_controls_tabs($key . '_tabs');
            $this->start_controls_tab($key . '_normal', ['label'=>esc_html__('Normal','nelx-jetappt-frontend')]);
            $this->add_control($key . '_color', [
                'label'=>esc_html__('Icon Color','nelx-jetappt-frontend'), 'type'=>Controls_Manager::COLOR,
                'selectors'=>['{{WRAPPER}} '.$class=>'color: {{VALUE}};'],
            ]);
            $this->add_control($key . '_bg', [
                'label'=>esc_html__('Background Color','nelx-jetappt-frontend'), 'type'=>Controls_Manager::COLOR,
                'selectors'=>['{{WRAPPER}} '.$class=>'background-color: {{VALUE}};'],
            ]);
            $this->add_group_control(Group_Control_Border::get_type(), [
                'name'=>$key . '_border', 'selector'=>'{{WRAPPER}} '.$class,
            ]);
            $this->end_controls_tab();
            $this->start_controls_tab($key . '_hover', ['label'=>esc_html__('Hover','nelx-jetappt-frontend')]);
            $this->add_control($key . '_hover_color', [
                'label'=>esc_html__('Icon Color','nelx-jetappt-frontend'), 'type'=>Controls_Manager::COLOR,
                'selectors'=>['{{WRAPPER}} '.$class.':hover'=>'color: {{VALUE}};'],
            ]);
            $this->add_control($key . '_hover_bg', [
                'label'=>esc_html__('Background Color','nelx-jetappt-frontend'), 'type'=>Controls_Manager::COLOR,
                'selectors'=>['{{WRAPPER}} '.$class.':hover'=>'background-color: {{VALUE}};'],
            ]);
            $this->add_control($key . '_hover_border_color', [
                'label'=>esc_html__('Border Color','nelx-jetappt-frontend'), 'type'=>Controls_Manager::COLOR,
                'selectors'=>['{{WRAPPER}} '.$class.':hover'=>'border-color: {{VALUE}};'],
            ]);
            $this->add_control($key . '_hover_opacity', [
                'label'=>esc_html__('Opacity','nelx-jetappt-frontend'), 'type'=>Controls_Manager::SLIDER,
                'range'=>['px'=>['min'=>0.1,'max'=>1,'step'=>0.01]],
                'selectors'=>['{{WRAPPER}} '.$class.':hover'=>'opacity: {{SIZE}};'],
            ]);
            $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
                'name'=>$key . '_hover_shadow', 'selector'=>'{{WRAPPER}} '.$class.':hover',
            ]);
            $this->end_controls_tab();
            $this->end_controls_tabs();
            $this->end_controls_section();
        }
    }

    protected function grid_style_controls() {
        $this->start_controls_section('section_card_style', [
            'label'=>esc_html__('Appointment Card','nelx-jetappt-frontend'),
            'tab'=>Controls_Manager::TAB_STYLE,
        ]);
        $this->add_control('card_background',['label'=>esc_html__('Background','nelx-jetappt-frontend'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .nelx-appointment-card'=>'background-color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'=>'card_border', 'label'=>esc_html__('Border','nelx-jetappt-frontend'), 'selector'=>'{{WRAPPER}} .nelx-appointment-card',
        ]);
        $this->add_responsive_control('card_radius',['label'=>esc_html__('Border Radius','nelx-jetappt-frontend'),'type'=>Controls_Manager::DIMENSIONS,'size_units'=>['px','%'],'selectors'=>['{{WRAPPER}} .nelx-appointment-card'=>'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->add_responsive_control('card_padding',['label'=>esc_html__('Padding','nelx-jetappt-frontend'),'type'=>Controls_Manager::DIMENSIONS,'size_units'=>['px','em'],'selectors'=>['{{WRAPPER}} .nelx-appointment-card'=>'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
        $this->add_control('card_hover_background',['label'=>esc_html__('Hover Background','nelx-jetappt-frontend'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .nelx-appointment-card:hover'=>'background-color: {{VALUE}};']]);
        $this->add_control('card_hover_border_color',['label'=>esc_html__('Hover Border Color','nelx-jetappt-frontend'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .nelx-appointment-card:hover'=>'border-color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), ['name'=>'card_hover_shadow','label'=>esc_html__('Hover Box Shadow','nelx-jetappt-frontend'),'selector'=>'{{WRAPPER}} .nelx-appointment-card:hover']);
        $this->end_controls_section();

        $this->start_controls_section('section_card_typography', [
            'label'=>esc_html__('Card Typography','nelx-jetappt-frontend'),
            'tab'=>Controls_Manager::TAB_STYLE,
        ]);
        $this->add_control('label_color',['label'=>esc_html__('Label Color','nelx-jetappt-frontend'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .nelx-appointment-card-details label'=>'color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name'=>'label_typography','label'=>esc_html__('Label Typography','nelx-jetappt-frontend'),'selector'=>'{{WRAPPER}} .nelx-appointment-card-details label']);
        $this->add_control('value_color',['label'=>esc_html__('Value Color','nelx-jetappt-frontend'),'type'=>Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .nelx-appointment-card-details span'=>'color: {{VALUE}};']]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name'=>'value_typography','label'=>esc_html__('Value Typography','nelx-jetappt-frontend'),'selector'=>'{{WRAPPER}} .nelx-appointment-card-details span']);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name'=>'empty_typography','label'=>esc_html__('No Upcoming Appointments Typography','nelx-jetappt-frontend'),'selector'=>'{{WRAPPER}} .nelx-appointment-grid-empty']);
        $this->end_controls_section();

        $this->action_buttons_style_controls($this->view === 'client' ? ['edit','reject','info'] : ['edit','confirm','reject','info']);
    }

    protected function grid_content_controls() {
        $this->start_controls_section('section_grid_content', ['label'=>esc_html__('Grid Settings','nelx-jetappt-frontend')]);
        $this->add_control('max_appointments', [
            'label'=>esc_html__('Maximum Appointments','nelx-jetappt-frontend'),
            'type'=>Controls_Manager::NUMBER,
            'min'=>0, 'max'=>100, 'step'=>1, 'default'=>0,
            'description'=>esc_html__('Set 0 to use the global Appointment Settings value.','nelx-jetappt-frontend'),
        ]);
        $this->add_control('desktop_columns', [
            'label'=>esc_html__('Desktop Columns','nelx-jetappt-frontend'),
            'type'=>Controls_Manager::NUMBER,
            'min'=>0, 'max'=>6, 'step'=>1, 'default'=>0,
            'description'=>esc_html__('Set 0 to use the global Appointment Settings value.','nelx-jetappt-frontend'),
        ]);
        $this->add_control('tablet_columns', [
            'label'=>esc_html__('Tablet Columns','nelx-jetappt-frontend'),
            'type'=>Controls_Manager::NUMBER,
            'min'=>0, 'max'=>4, 'step'=>1, 'default'=>0,
            'description'=>esc_html__('Set 0 to use the global Appointment Settings value. Mobile automatically uses one column.','nelx-jetappt-frontend'),
        ]);
        $this->end_controls_section();
    }

    protected function content_notice($text) {
        $this->start_controls_section('section_content',['label'=>esc_html__('Content','nelx-jetappt-frontend')]);
        $this->add_control('notice',['type'=>Controls_Manager::RAW_HTML,'raw'=>'<div class="elementor-panel-alert elementor-panel-alert-info">'.esc_html($text).'</div>']);
        $this->end_controls_section();
    }

    protected function render_listing() {
        $manager = NELXJAF_Appointment_Listings::instance(NELXJAF_Core::instance());

        // Elementor repeatedly renders widgets while editing. Do not execute
        // appointment queries, metadata queries, or one action-button shortcode
        // per appointment in the editor. Use the lightweight visual preview;
        // the real data path remains unchanged on the frontend.
        if (class_exists('Elementor\Plugin') && isset(\Elementor\Plugin::$instance->editor) && \Elementor\Plugin::$instance->editor->is_edit_mode()) {
            echo $manager->render_elementor_preview($this->view, $this->display);
            return;
        }

        if ($this->display === 'grid') {
            $settings = $this->get_settings_for_display();
            $overrides = [
                'limit' => absint($settings['max_appointments'] ?? 0),
                'desktop_columns' => absint($settings['desktop_columns'] ?? 0),
                'tablet_columns' => absint($settings['tablet_columns'] ?? 0),
            ];
            echo $this->view === 'client' ? $manager->client_grid_render($overrides) : $manager->staff_grid_render($overrides);
            return;
        }
        echo $this->view === 'client' ? $manager->client_table() : $manager->staff_table();
    }
}

class Elementor_Nelx_Staff_Appointments extends NELXJAF_Appointment_Listing_Widget_Base {
    protected $view='staff'; protected $display='table';
    public function get_name(){return 'nelx_staff_appointments';}
    public function get_title(){return esc_html__('Staff Appointments','nelx-jetappt-frontend');}
    public function get_icon(){return 'eicon-table';}
    public function get_keywords(){return ['appointments','staff','provider','table','nelx'];}
    protected function register_controls(){ $this->content_notice('Displays the native staff/provider appointment table using the plugin appointment settings. No JetEngine listing or query is required.'); $this->table_style_controls(); }
    protected function render(){ $this->render_listing(); }
}

class Elementor_Nelx_Client_Appointments extends NELXJAF_Appointment_Listing_Widget_Base {
    protected $view='client'; protected $display='table';
    public function get_name(){return 'nelx_client_appointments';}
    public function get_title(){return esc_html__('Client Appointments','nelx-jetappt-frontend');}
    public function get_icon(){return 'eicon-table';}
    public function get_keywords(){return ['appointments','client','table','nelx'];}
    protected function register_controls(){ $this->content_notice('Displays the native client appointment table. Client-local time is taken from appointment meta when available.'); $this->table_style_controls(); }
    protected function render(){ $this->render_listing(); }
}

class Elementor_Nelx_Staff_Appointments_Grid extends NELXJAF_Appointment_Listing_Widget_Base {
    protected $view='staff'; protected $display='grid';
    public function get_name(){return 'nelx_staff_appointments_grid';}
    public function get_title(){return esc_html__('Staff Appointment Grid','nelx-jetappt-frontend');}
    public function get_icon(){return 'eicon-posts-grid';}
    public function get_keywords(){return ['appointments','staff','provider','grid','dashboard','nelx'];}
    protected function register_controls(){ $this->content_notice('Displays upcoming staff/provider appointments. Global Appointment Settings are used unless this widget overrides the grid settings.'); $this->grid_content_controls(); $this->grid_style_controls(); }
    protected function render(){ $this->render_listing(); }
}

class Elementor_Nelx_Client_Appointments_Grid extends NELXJAF_Appointment_Listing_Widget_Base {
    protected $view='client'; protected $display='grid';
    public function get_name(){return 'nelx_client_appointments_grid';}
    public function get_title(){return esc_html__('Client Appointment Grid','nelx-jetappt-frontend');}
    public function get_icon(){return 'eicon-posts-grid';}
    public function get_keywords(){return ['appointments','client','grid','dashboard','nelx'];}
    protected function register_controls(){ $this->content_notice('Displays upcoming client appointments. Global Appointment Settings are used unless this widget overrides the grid settings.'); $this->grid_content_controls(); $this->grid_style_controls(); }
    protected function render(){ $this->render_listing(); }
}
