<?php
/**
 * Schedule Editor Elementor Widget
 */

if (!defined('ABSPATH')) exit;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

class Elementor_Nelx_Schedule_Editor extends Widget_Base {
    
    public function get_name() {
        return 'nelx_schedule_editor';
    }
    
    public function get_title() {
        return esc_html__('Schedule Editor', 'nelx-jetappt-frontend');
    }
    
    public function get_icon() {
        return 'eicon-calendar';
    }
    
    public function get_categories() {
        return ['nelx-jetappt'];
    }
    
    public function get_keywords() {
        return ['schedule', 'editor', 'appointments', 'provider', 'nelx'];
    }
    
    protected function register_controls() {
        $this->start_controls_section(
            'section_content',
            [
                'label' => esc_html__('Content', 'nelx-jetappt-frontend'),
            ]
        );
        
        $this->add_control(
            'notice',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => '<div class="elementor-panel-alert elementor-panel-alert-info">' .
                    esc_html__('This widget displays the schedule editor for providers. It automatically detects the current user\'s provider profile.', 'nelx-jetappt-frontend') .
                    '</div>',
            ]
        );
        
        $this->end_controls_section();
        
        // Container Style
        $this->start_controls_section(
            'section_container_style',
            [
                'label' => esc_html__('Container', 'nelx-jetappt-frontend'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'container_background',
            [
                'label' => esc_html__('Background Color', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-schedule-editor' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'container_border',
                'selector' => '{{WRAPPER}} .nelx-schedule-editor',
                'separator' => 'before',
            ]
        );
        
        $this->add_control(
            'container_border_radius',
            [
                'label' => esc_html__('Border Radius', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .nelx-schedule-editor' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'container_box_shadow',
                'selector' => '{{WRAPPER}} .nelx-schedule-editor',
            ]
        );
        
        $this->add_responsive_control(
            'container_padding',
            [
                'label' => esc_html__('Padding', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .nelx-schedule-editor' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'container_margin',
            [
                'label' => esc_html__('Margin', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .nelx-schedule-editor' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Switch Style
        $this->start_controls_section(
            'section_switch_style',
            [
                'label' => esc_html__('Switch', 'nelx-jetappt-frontend'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'switch_width',
            [
                'label' => esc_html__('Width', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 40,
                        'max' => 100,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 60,
                ],
                'selectors' => [
                    '{{WRAPPER}} .nelx-switch' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'switch_height',
            [
                'label' => esc_html__('Height', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 20,
                        'max' => 40,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 24,
                ],
                'selectors' => [
                    '{{WRAPPER}} .nelx-switch' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'switch_off_background',
            [
                'label' => esc_html__('Off Background', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'default' => '#d1d5db',
                'selectors' => [
                    '{{WRAPPER}} .nelx-switch-slider' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'switch_on_background',
            [
                'label' => esc_html__('On Background', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'default' => '#2563eb',
                'selectors' => [
                    '{{WRAPPER}} .nelx-switch-input:checked + .nelx-switch-slider' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'switch_knob_background',
            [
                'label' => esc_html__('Knob Background', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .nelx-switch-slider:before' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'switch_knob_size',
            [
                'label' => esc_html__('Knob Size', 'nelx-jetappt-frontend'),
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
                    '{{WRAPPER}} .nelx-switch-slider:before' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'switch_label_color',
            [
                'label' => esc_html__('Label Color', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-switch-label' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'switch_label_typography',
                'selector' => '{{WRAPPER}} .nelx-switch-label',
            ]
        );
        
        $this->end_controls_section();
        
        // Card Style
        $this->start_controls_section(
            'section_card_style',
            [
                'label' => esc_html__('Cards', 'nelx-jetappt-frontend'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'card_background',
            [
                'label' => esc_html__('Background Color', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .nelx-card' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'card_border',
                'selector' => '{{WRAPPER}} .nelx-card',
            ]
        );
        
        $this->add_control(
            'card_border_radius',
            [
                'label' => esc_html__('Border Radius', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'top' => '12',
                    'right' => '12',
                    'bottom' => '12',
                    'left' => '12',
                    'unit' => 'px',
                    'isLinked' => true,
                ],
                'selectors' => [
                    '{{WRAPPER}} .nelx-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'card_box_shadow',
                'selector' => '{{WRAPPER}} .nelx-card',
            ]
        );
        
        $this->add_responsive_control(
            'card_padding',
            [
                'label' => esc_html__('Padding', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'top' => '16',
                    'right' => '16',
                    'bottom' => '16',
                    'left' => '16',
                    'unit' => 'px',
                    'isLinked' => true,
                ],
                'selectors' => [
                    '{{WRAPPER}} .nelx-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'card_head_color',
            [
                'label' => esc_html__('Card Head Color', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-card-head' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'card_head_typography',
                'selector' => '{{WRAPPER}} .nelx-card-head',
            ]
        );
        
        $this->end_controls_section();
        
        // Button Style
        $this->start_controls_section(
            'section_button_style',
            [
                'label' => esc_html__('Buttons', 'nelx-jetappt-frontend'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'selector' => '{{WRAPPER}} .nelx-btn',
            ]
        );
        
        $this->start_controls_tabs('button_styles');
        
        $this->start_controls_tab(
            'button_normal',
            [
                'label' => esc_html__('Normal', 'nelx-jetappt-frontend'),
            ]
        );
        
        $this->add_control(
            'button_text_color',
            [
                'label' => esc_html__('Text Color', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-btn' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'button_background',
            [
                'label' => esc_html__('Background Color', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->end_controls_tab();
        
        $this->start_controls_tab(
            'button_hover',
            [
                'label' => esc_html__('Hover', 'nelx-jetappt-frontend'),
            ]
        );
        
        $this->add_control(
            'button_hover_text_color',
            [
                'label' => esc_html__('Text Color', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-btn:hover' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'button_hover_background',
            [
                'label' => esc_html__('Background Color', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-btn:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->end_controls_tab();
        
        $this->end_controls_tabs();
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'button_border',
                'selector' => '{{WRAPPER}} .nelx-btn',
                'separator' => 'before',
            ]
        );
        
        $this->add_control(
            'button_border_radius',
            [
                'label' => esc_html__('Border Radius', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .nelx-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'button_box_shadow',
                'selector' => '{{WRAPPER}} .nelx-btn',
            ]
        );
        
        $this->add_responsive_control(
            'button_padding',
            [
                'label' => esc_html__('Padding', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .nelx-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        // Primary button specific
        $this->add_control(
            'primary_button_heading',
            [
                'label' => esc_html__('Primary Button', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
        
        $this->add_control(
            'primary_button_color',
            [
                'label' => esc_html__('Text Color', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-btn.nelx-primary' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'primary_button_bg',
            [
                'label' => esc_html__('Background Color', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-btn.nelx-primary' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'primary_button_hover_color',
            [
                'label' => esc_html__('Hover Text Color', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-btn.nelx-primary:hover' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'primary_button_hover_bg',
            [
                'label' => esc_html__('Hover Background', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-btn.nelx-primary:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        // Outline button specific
        $this->add_control(
            'outline_button_heading',
            [
                'label' => esc_html__('Outline Button', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
        
        $this->add_control(
            'outline_button_color',
            [
                'label' => esc_html__('Text Color', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-btn.nelx-outline' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'outline_button_border_color',
            [
                'label' => esc_html__('Border Color', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-btn.nelx-outline' => 'border-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Field Style
        $this->start_controls_section(
            'section_field_style',
            [
                'label' => esc_html__('Form Fields', 'nelx-jetappt-frontend'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'label_color',
            [
                'label' => esc_html__('Label Color', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-field label' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'label_typography',
                'selector' => '{{WRAPPER}} .nelx-field label',
            ]
        );
        
        $this->add_control(
            'field_background',
            [
                'label' => esc_html__('Field Background', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-input, {{WRAPPER}} select' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'field_text_color',
            [
                'label' => esc_html__('Field Text Color', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-input, {{WRAPPER}} select' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'field_border',
                'selector' => '{{WRAPPER}} .nelx-input, {{WRAPPER}} select',
            ]
        );
        
        $this->add_control(
            'field_border_radius',
            [
                'label' => esc_html__('Border Radius', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .nelx-input, {{WRAPPER}} select' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'field_box_shadow',
                'selector' => '{{WRAPPER}} .nelx-input, {{WRAPPER}} select',
            ]
        );
        
        $this->add_responsive_control(
            'field_padding',
            [
                'label' => esc_html__('Padding', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .nelx-input, {{WRAPPER}} select' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Day Style
        $this->start_controls_section(
            'section_day_style',
            [
                'label' => esc_html__('Days & Slots', 'nelx-jetappt-frontend'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'day_background',
            [
                'label' => esc_html__('Day Background', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-day' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'day_border',
                'selector' => '{{WRAPPER}} .nelx-day',
            ]
        );
        
        $this->add_control(
            'day_border_radius',
            [
                'label' => esc_html__('Day Border Radius', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .nelx-day' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'day_title_color',
            [
                'label' => esc_html__('Day Title Color', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-day-title' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'day_title_typography',
                'selector' => '{{WRAPPER}} .nelx-day-title',
            ]
        );
        
        $this->add_control(
            'separator_color',
            [
                'label' => esc_html__('Separator Color', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-slot-row .sep' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        // Tag Style
        $this->add_control(
            'tag_heading',
            [
                'label' => esc_html__('Tags', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
        
        $this->add_control(
            'tag_background',
            [
                'label' => esc_html__('Tag Background', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-tag' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'tag_text_color',
            [
                'label' => esc_html__('Tag Text Color', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-tag' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'tag_border',
                'selector' => '{{WRAPPER}} .nelx-tag',
            ]
        );
        
        $this->add_control(
            'tag_border_radius',
            [
                'label' => esc_html__('Tag Border Radius', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .nelx-tag' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Divider Style
        $this->start_controls_section(
            'section_divider_style',
            [
                'label' => esc_html__('Divider', 'nelx-jetappt-frontend'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'divider_color',
            [
                'label' => esc_html__('Color', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .nelx-divider' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'divider_height',
            [
                'label' => esc_html__('Height', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 1,
                        'max' => 10,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 1,
                ],
                'selectors' => [
                    '{{WRAPPER}} .nelx-divider' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'divider_margin',
            [
                'label' => esc_html__('Margin', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .nelx-divider' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Grid Style
        $this->start_controls_section(
            'section_grid_style',
            [
                'label' => esc_html__('Grid Layout', 'nelx-jetappt-frontend'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_responsive_control(
            'grid_gap',
            [
                'label' => esc_html__('Gap', 'nelx-jetappt-frontend'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 16,
                ],
                'selectors' => [
                    '{{WRAPPER}} .nelx-grid' => 'grid-gap: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .nelx-schedule-row' => 'grid-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Use the shortcode instead of the deprecated UI class
        echo do_shortcode('[nelx_schedule_editor]');
    }
    
    protected function content_template() {
        ?>
        <div class="nelx-schedule-editor">
            <div class="nelx-sched-head" style="margin-bottom:30px;">
                <label class="nelx-switch">
                    <input type="checkbox" class="nelx-switch-input" id="nelx_use_custom_schedule">
                    <span class="nelx-switch-slider" aria-hidden="true"></span>
                </label>
                <span class="nelx-switch-label"><?php esc_html_e('Use Custom Schedule', 'nelx-jetappt-frontend'); ?></span>
                <button type="button" class="nelx-btn nelx-primary nelx-save-top" style="display:none;">
                    <span class="nelx-btn-text"><?php esc_html_e('Save Changes', 'nelx-jetappt-frontend'); ?></span>
                    <span class="nelx-spinner" aria-hidden="true" style="display:none"></span>
                </button>
            </div>
            <div class="nelx-sched-body" style="display:none;">
                <!-- Schedule editor content will be loaded dynamically -->
                <div style="text-align:center; padding:40px;">
                    <?php esc_html_e('Schedule Editor will load for logged-in providers.', 'nelx-jetappt-frontend'); ?>
                </div>
            </div>
        </div>
        <?php
    }
}
