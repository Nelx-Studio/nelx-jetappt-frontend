<?php
/**
 * JetFormBuilder Field Helper - Extracted from original settings class
 */

if (!defined('ABSPATH')) exit;

class NELXJAF_JFB_Field_Helper {
    
    /**
     * Get JetFormBuilder form fields
     */
    public static function get_form_fields($form_id = 0) {
        if (!function_exists('jet_form_builder')) {
            return [];
        }
        
        $fields = [];
        $form_id = absint($form_id);
        
        // New API (JetFormBuilder 3.0+)
        if (class_exists('\Jet_Form_Builder\Forms\Tables\Forms_Table') 
            && method_exists('\Jet_Form_Builder\Forms\Tables\Forms_Table', 'get_forms')) {
            
            $forms = \Jet_Form_Builder\Forms\Tables\Forms_Table::get_forms();
            
            foreach ($forms as $form) {
                if ($form_id && intval($form['id']) !== $form_id) {
                    continue;
                }
                
                $form_fields = jet_form_builder()->form_fields->get_fields_by_form_id($form['id']);
                
                if (empty($form_fields)) {
                    $form_fields = self::parse_form_blocks($form['id']);
                }
                
                foreach ($form_fields as $field) {
                    if (!empty($field['name'])) {
                        $fields[] = [
                            'name' => $field['name'],
                            'label' => $field['label'] ?? $field['name'],
                            'form_id' => $form['id'],
                            'form_title' => $form['title'] ?? '',
                        ];
                    }
                }
            }
        }
        // Old API (JetFormBuilder 2.x)
        elseif (class_exists('\Jet_Form_Builder\Forms\Manager') 
            && class_exists('\Jet_Form_Builder\Blocks\Manager')) {
            
            $forms = \Jet_Form_Builder\Forms\Manager::get_forms();
            
            foreach ($forms as $form) {
                if ($form_id && intval($form->ID) !== $form_id) {
                    continue;
                }
                
                $form_fields = \Jet_Form_Builder\Blocks\Manager::instance()->get_form_fields($form->ID);
                
                if (empty($form_fields)) {
                    $form_fields = self::parse_form_blocks($form->ID);
                }
                
                foreach ($form_fields as $field) {
                    if (!empty($field['name'])) {
                        $fields[] = [
                            'name' => $field['name'],
                            'label' => $field['label'] ?? $field['name'],
                            'form_id' => $form->ID,
                            'form_title' => $form->post_title,
                        ];
                    }
                }
            }
        }
        // Direct block parsing
        elseif ($form_id) {
            $fields = self::parse_form_blocks($form_id);
        }
        
        return $fields;
    }
    
    /**
     * Parse form blocks to extract fields
     */
    private static function parse_form_blocks($form_id) {
        $parsed_fields = [];
        
        $post = get_post($form_id);
        if (!$post || $post->post_type !== 'jet-form-builder') {
            return [];
        }
        
        $blocks = parse_blocks($post->post_content);
        
        self::extract_block_fields($blocks, $parsed_fields, $form_id, $post->post_title);
        
        return $parsed_fields;
    }
    
    /**
     * Recursively extract fields from blocks
     */
    private static function extract_block_fields($blocks, &$parsed_fields, $form_id, $form_title) {
        foreach ($blocks as $block) {
            if (isset($block['attrs']['name'])) {
                $parsed_fields[] = [
                    'name' => $block['attrs']['name'],
                    'label' => $block['attrs']['label'] ?? $block['attrs']['name'],
                    'form_id' => $form_id,
                    'form_title' => $form_title,
                ];
            }
            if (!empty($block['innerBlocks'])) {
                self::extract_block_fields($block['innerBlocks'], $parsed_fields, $form_id, $form_title);
            }
        }
    }
}
