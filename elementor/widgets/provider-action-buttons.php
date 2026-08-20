<?php
/**
 * Provider Action Buttons Elementor Widget
 */

if (!defined('ABSPATH')) exit;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

class Elementor_Nelx_Provider_Actions extends Widget_Base {
    
    public function get_name() {
        return 'nelx_provider_actions';
    }
    
    public function get_title() {
        return esc_html__('Provider Actions', 'nelx-jetappt-frontend');
    }
    
    public function get_icon() {
        return 'eicon-user-circle-o';
    }
    
    public function get_categories() {
        return ['nelx-jetappt'];
    }
    
    public function get_keywords() {
        return ['provider', 'actions', 'buttons', 'appointments', 'nelx'];
    }
    
    protected function register_controls() {
        $this->start_controls_section(
            'section_content',
            [
                'label' => esc_html__('Content', 'nelx-jetappt-frontend'),
            ]
        );
        
        $this->add_control(
            'appointment_id',
            [
                'label' => esc_html__('Appointment ID', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::TEXT,
                'description' => esc_html__('Enter the appointment ID. Leave empty to detect from listing context.', 'nelx-jetappt-frontend'),
                'placeholder' => 'e.g., 123',
            ]
        );
        
        $this->add_control(
            'notice',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => '<div class="elementor-panel-alert elementor-panel-alert-info">' .
                    esc_html__('This widget displays action buttons for providers (reschedule, confirm, cancel, info). The appointment ID can be set manually or detected automatically from listing context.', 'nelx-jetappt-frontend') .
                    '</div>',
            ]
        );
        
        $this->end_controls_section();
        
        // General Button Style
        $this->start_controls_section(
            'section_general_style',
            [
                'label' => esc_html__('General Style', 'nelx-jetappt-frontend'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_responsive_control(
            'button_size',
            [
                'label' => esc_html__('Button Size', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 20,
                        'max' => 60,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 28,
                ],
                'selectors' => [
                    '{{WRAPPER}} .nelx-icon-btn' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'icon_size',
            [
                'label' => esc_html__('Icon Size', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 12,
                        'max' => 32,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 16,
                ],
                'selectors' => [
                    '{{WRAPPER}} .nelx-icon-btn svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'button_spacing',
            [
                'label' => esc_html__('Spacing', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 30,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 6,
                ],
                'selectors' => [
                    '{{WRAPPER}} .nelx-actions-inline' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'transition_duration',
            [
                'label' => esc_html__('Transition Duration', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'max' => 3,
                        'step' => 0.1,
                    ],
                ],
                'default' => [
                    'size' => 0.2,
                ],
                'selectors' => [
                    '{{WRAPPER}} .nelx-icon-btn' => 'transition-duration: {{SIZE}}s',
                ],
            ]
        );
        
        $this->add_control(
            'disabled_opacity',
            [
                'label' => esc_html__('Disabled Opacity', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'max' => 1,
                        'min' => 0.10,
                        'step' => 0.01,
                    ],
                ],
                'default' => [
                    'size' => 0.5,
                ],
                'selectors' => [
                    '{{WRAPPER}} .nelx-icon-btn:disabled' => 'opacity: {{SIZE}};',
                ],
            ]
        );
        
        $this->add_control(
            'disabled_bg_color',
            [
                'label' => esc_html__('Disabled Background', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-icon-btn:disabled' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Edit Button Style
        $this->start_controls_section(
            'section_edit_button',
            [
                'label' => esc_html__('Edit Button', 'nelx-jetappt-frontend'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->start_controls_tabs('edit_button_tabs');
        
        // Normal State
        $this->start_controls_tab(
            'edit_button_normal',
            [
                'label' => esc_html__('Normal', 'nelx-jetappt-frontend'),
            ]
        );
        
        $this->add_control(
            'edit_button_color',
            [
                'label' => esc_html__('Color', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-edit' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'edit_button_bg',
            [
                'label' => esc_html__('Background', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-edit' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'edit_button_border',
                'selector' => '{{WRAPPER}} .nelx-edit',
            ]
        );
        
        $this->add_control(
            'edit_button_border_radius',
            [
                'label' => esc_html__('Border Radius', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .nelx-edit' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'edit_button_box_shadow',
                'selector' => '{{WRAPPER}} .nelx-edit',
            ]
        );
        
        $this->end_controls_tab();
        
        // Hover State
        $this->start_controls_tab(
            'edit_button_hover',
            [
                'label' => esc_html__('Hover', 'nelx-jetappt-frontend'),
            ]
        );
        
        $this->add_control(
            'edit_button_hover_color',
            [
                'label' => esc_html__('Color', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-edit:hover' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'edit_button_hover_bg',
            [
                'label' => esc_html__('Background', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-edit:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'edit_button_hover_border',
                'selector' => '{{WRAPPER}} .nelx-edit:hover',
            ]
        );
        
        $this->add_control(
            'edit_button_hover_border_radius',
            [
                'label' => esc_html__('Border Radius', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .nelx-edit:hover' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'edit_button_hover_box_shadow',
                'selector' => '{{WRAPPER}} .nelx-edit:hover',
            ]
        );
        
        $this->add_control(
            'edit_button_hover_opacity',
            [
                'label' => esc_html__('Opacity', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'max' => 1,
                        'min' => 0.10,
                        'step' => 0.01,
                    ],
                ],
                'default' => [
                    'size' => 0.85,
                ],
                'selectors' => [
                    '{{WRAPPER}} .nelx-edit:hover' => 'opacity: {{SIZE}};',
                ],
            ]
        );
        
        $this->end_controls_tab();
        
        $this->end_controls_tabs();
        
        $this->end_controls_section();
        
        // Confirm Button Style
        $this->start_controls_section(
            'section_confirm_button',
            [
                'label' => esc_html__('Confirm Button', 'nelx-jetappt-frontend'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->start_controls_tabs('confirm_button_tabs');
        
        // Normal State
        $this->start_controls_tab(
            'confirm_button_normal',
            [
                'label' => esc_html__('Normal', 'nelx-jetappt-frontend'),
            ]
        );
        
        $this->add_control(
            'confirm_button_color',
            [
                'label' => esc_html__('Color', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-confirm' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'confirm_button_bg',
            [
                'label' => esc_html__('Background', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-confirm' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'confirm_button_border',
                'selector' => '{{WRAPPER}} .nelx-confirm',
            ]
        );
        
        $this->add_control(
            'confirm_button_border_radius',
            [
                'label' => esc_html__('Border Radius', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .nelx-confirm' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'confirm_button_box_shadow',
                'selector' => '{{WRAPPER}} .nelx-confirm',
            ]
        );
        
        $this->end_controls_tab();
        
        // Hover State
        $this->start_controls_tab(
            'confirm_button_hover',
            [
                'label' => esc_html__('Hover', 'nelx-jetappt-frontend'),
            ]
        );
        
        $this->add_control(
            'confirm_button_hover_color',
            [
                'label' => esc_html__('Color', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-confirm:hover' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'confirm_button_hover_bg',
            [
                'label' => esc_html__('Background', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-confirm:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'confirm_button_hover_border',
                'selector' => '{{WRAPPER}} .nelx-confirm:hover',
            ]
        );
        
        $this->add_control(
            'confirm_button_hover_border_radius',
            [
                'label' => esc_html__('Border Radius', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .nelx-confirm:hover' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'confirm_button_hover_box_shadow',
                'selector' => '{{WRAPPER}} .nelx-confirm:hover',
            ]
        );
        
        $this->add_control(
            'confirm_button_hover_opacity',
            [
                'label' => esc_html__('Opacity', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'max' => 1,
                        'min' => 0.10,
                        'step' => 0.01,
                    ],
                ],
                'default' => [
                    'size' => 0.85,
                ],
                'selectors' => [
                    '{{WRAPPER}} .nelx-confirm:hover' => 'opacity: {{SIZE}};',
                ],
            ]
        );
        
        $this->end_controls_tab();
        
        $this->end_controls_tabs();
        
        $this->end_controls_section();
        
        // Cancel Button Style
        $this->start_controls_section(
            'section_cancel_button',
            [
                'label' => esc_html__('Cancel Button', 'nelx-jetappt-frontend'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->start_controls_tabs('cancel_button_tabs');
        
        // Normal State
        $this->start_controls_tab(
            'cancel_button_normal',
            [
                'label' => esc_html__('Normal', 'nelx-jetappt-frontend'),
            ]
        );
        
        $this->add_control(
            'cancel_button_color',
            [
                'label' => esc_html__('Color', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-reject' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'cancel_button_bg',
            [
                'label' => esc_html__('Background', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-reject' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'cancel_button_border',
                'selector' => '{{WRAPPER}} .nelx-reject',
            ]
        );
        
        $this->add_control(
            'cancel_button_border_radius',
            [
                'label' => esc_html__('Border Radius', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .nelx-reject' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'cancel_button_box_shadow',
                'selector' => '{{WRAPPER}} .nelx-reject',
            ]
        );
        
        $this->end_controls_tab();
        
        // Hover State
        $this->start_controls_tab(
            'cancel_button_hover',
            [
                'label' => esc_html__('Hover', 'nelx-jetappt-frontend'),
            ]
        );
        
        $this->add_control(
            'cancel_button_hover_color',
            [
                'label' => esc_html__('Color', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-reject:hover' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'cancel_button_hover_bg',
            [
                'label' => esc_html__('Background', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-reject:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'cancel_button_hover_border',
                'selector' => '{{WRAPPER}} .nelx-reject:hover',
            ]
        );
        
        $this->add_control(
            'cancel_button_hover_border_radius',
            [
                'label' => esc_html__('Border Radius', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .nelx-reject:hover' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'cancel_button_hover_box_shadow',
                'selector' => '{{WRAPPER}} .nelx-reject:hover',
            ]
        );
        
        $this->add_control(
            'cancel_button_hover_opacity',
            [
                'label' => esc_html__('Opacity', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'max' => 1,
                        'min' => 0.10,
                        'step' => 0.01,
                    ],
                ],
                'default' => [
                    'size' => 0.85,
                ],
                'selectors' => [
                    '{{WRAPPER}} .nelx-reject:hover' => 'opacity: {{SIZE}};',
                ],
            ]
        );
        
        $this->end_controls_tab();
        
        $this->end_controls_tabs();
        
        $this->end_controls_section();
        
        // Info Button Style
        $this->start_controls_section(
            'section_info_button',
            [
                'label' => esc_html__('Info Button', 'nelx-jetappt-frontend'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->start_controls_tabs('info_button_tabs');
        
        // Normal State
        $this->start_controls_tab(
            'info_button_normal',
            [
                'label' => esc_html__('Normal', 'nelx-jetappt-frontend'),
            ]
        );
        
        $this->add_control(
            'info_button_color',
            [
                'label' => esc_html__('Color', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-info' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'info_button_bg',
            [
                'label' => esc_html__('Background', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-info' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'info_button_border',
                'selector' => '{{WRAPPER}} .nelx-info',
            ]
        );
        
        $this->add_control(
            'info_button_border_radius',
            [
                'label' => esc_html__('Border Radius', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .nelx-info' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'info_button_box_shadow',
                'selector' => '{{WRAPPER}} .nelx-info',
            ]
        );
        
        $this->end_controls_tab();
        
        // Hover State
        $this->start_controls_tab(
            'info_button_hover',
            [
                'label' => esc_html__('Hover', 'nelx-jetappt-frontend'),
            ]
        );
        
        $this->add_control(
            'info_button_hover_color',
            [
                'label' => esc_html__('Color', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-info:hover' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'info_button_hover_bg',
            [
                'label' => esc_html__('Background', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-info:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'info_button_hover_border',
                'selector' => '{{WRAPPER}} .nelx-info:hover',
            ]
        );
        
        $this->add_control(
            'info_button_hover_border_radius',
            [
                'label' => esc_html__('Border Radius', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .nelx-info:hover' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'info_button_hover_box_shadow',
                'selector' => '{{WRAPPER}} .nelx-info:hover',
            ]
        );
        
        $this->add_control(
            'info_button_hover_opacity',
            [
                'label' => esc_html__('Opacity', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'max' => 1,
                        'min' => 0.10,
                        'step' => 0.01,
                    ],
                ],
                'default' => [
                    'size' => 0.85,
                ],
                'selectors' => [
                    '{{WRAPPER}} .nelx-info:hover' => 'opacity: {{SIZE}};',
                ],
            ]
        );
        
        $this->end_controls_tab();
        
        $this->end_controls_tabs();
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Use the shortcode instead of the deprecated UI class
        $appointment_id = !empty($settings['appointment_id']) ? intval($settings['appointment_id']) : '';
        
        if ($appointment_id) {
            echo do_shortcode('[nelx_provider_action_buttons appointment_id="' . esc_attr($appointment_id) . '"]');
        } else {
            echo do_shortcode('[nelx_provider_action_buttons]');
        }
    }
    
    protected function content_template() {
        ?>
        <div class="nelx-actions-inline nelx-elementor-widget">
            <button type="button" class="nelx-icon-btn nelx-edit" data-action="edit" title="<?php esc_attr_e('Reschedule', 'nelx-jetappt-frontend'); ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
            </button>
            <button type="button" class="nelx-icon-btn nelx-confirm" data-action="confirm" data-status="accepted" title="<?php esc_attr_e('Confirm', 'nelx-jetappt-frontend'); ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 16.17L4.83 12 3.41 13.41 9 19 21 7 19.59 5.59z"/></svg>
            </button>
            <button type="button" class="nelx-icon-btn nelx-reject" data-action="cancel" data-status="canceled" title="<?php esc_attr_e('Cancel', 'nelx-jetappt-frontend'); ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
            <button type="button" class="nelx-icon-btn nelx-info" data-action="info" title="<?php esc_attr_e('Info', 'nelx-jetappt-frontend'); ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 7h2v2h-2V7zm0 4h2v6h-2v-6zm1-9C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/></svg>
            </button>
        </div>
        <?php
    }
}
