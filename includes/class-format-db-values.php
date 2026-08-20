<?php
/**
 * Format DB Values Callback for JetEngine
 * Converts database values (like statuses) to human-readable, properly formatted strings
 * 
 * @package NelxJetAppointments
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register callback name with JetEngine
add_filter('jet-engine/listings/allowed-callbacks', function($callbacks) {
    $callbacks['nelx_format_db_value'] = __('Format DB Value', 'nelx-jetappt-frontend');
    return $callbacks;
});

/**
 * Register callback arguments for the dynamic field
 */
add_filter('jet-engine/listings/allowed-callbacks-args', function($args) {
    $base = [
        'dynamic_field_filter' => 'yes',
        'filter_callback' => 'nelx_format_db_value',
    ];

    $args['nelx_format_db_value_capitalization'] = [
        'label' => __('Capitalization Style', 'nelx-jetappt-frontend'),
        'type' => 'select',
        'default' => 'title',
        'options' => [
            'title' => __('Title Case (Each Word Capitalized)', 'nelx-jetappt-frontend'),
            'sentence' => __('Sentence Case (First letter capital)', 'nelx-jetappt-frontend'),
            'upper' => __('UPPERCASE', 'nelx-jetappt-frontend'),
            'lower' => __('lowercase', 'nelx-jetappt-frontend'),
        ],
        'condition' => $base,
    ];

    $args['nelx_format_db_value_preserve_acronyms'] = [
        'label' => __('Preserve Acronyms (ID, URL, etc.)', 'nelx-jetappt-frontend'),
        'type' => 'select',
        'default' => 'yes',
        'options' => [
            'yes' => __('Yes', 'nelx-jetappt-frontend'),
            'no' => __('No', 'nelx-jetappt-frontend'),
        ],
        'condition' => $base,
    ];

    return $args;
});

/**
 * Inject callback args when rendering the dynamic field
 */
add_filter('jet-engine/listing/dynamic-field/callback-args', function($args, $callback, $settings = []) {
    if ('nelx_format_db_value' === $callback) {
        // The first argument is the field value (automatically passed)
        // Then we add our custom settings
        $args[] = $settings['nelx_format_db_value_capitalization'] ?? 'title';
        $args[] = $settings['nelx_format_db_value_preserve_acronyms'] ?? 'yes';
    }
    return $args;
}, 10, 3);

/**
 * Special words that should have specific formatting
 */
function nelx_get_special_formatting() {
    return [
        'google_meet' => 'Google Meet',
        'jetengine' => 'JetEngine',
        'jetappointments' => 'JetAppointments',
        'wordpress' => 'WordPress',
        'woocommerce' => 'WooCommerce',
        'elementor' => 'Elementor',
        'yoast' => 'Yoast',
        'facebook' => 'Facebook',
        'twitter' => 'Twitter',
        'instagram' => 'Instagram',
        'linkedin' => 'LinkedIn',
        'whatsapp' => 'WhatsApp',
        'youtube' => 'YouTube',
        'pending_approval' => 'Pending Approval',
        'payment_method_failed' => 'Payment Method Failed',
    ];
}

/**
 * Common acronyms that should remain uppercase
 */
function nelx_get_acronyms() {
    return ['id', 'ids', 'url', 'api', 'http', 'https', 'ftp', 'ssh', 
            'pdf', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'html', 'css',
            'js', 'json', 'xml', 'csv', 'sql', 'php', 'ajax'];
}

/**
 * Main callback function: formats database values
 * @param mixed $value The field value from the dynamic field
 * @param string $capitalization Capitalization style
 * @param string $preserve_acronyms Whether to preserve acronyms (yes/no)
 */
function nelx_format_db_value($value, $capitalization = 'title', $preserve_acronyms = 'yes') {
    if (empty($value) && !is_numeric($value)) {
        return '';
    }

    $preserve_acronyms = $preserve_acronyms === 'yes';
    $string_value = (string) $value;
    
    // Check for special formatting first
    $lower_value = strtolower($string_value);
    $special_formatting = nelx_get_special_formatting();
    
    if (isset($special_formatting[$lower_value])) {
        $formatted = $special_formatting[$lower_value];
        return nelx_apply_capitalization($formatted, $capitalization, $preserve_acronyms);
    }
    
    // Replace underscores and hyphens with spaces
    $formatted = str_replace(['_', '-'], ' ', $string_value);
    
    // Split into words for individual processing
    $words = explode(' ', $formatted);
    
    // Process each word
    $processed_words = array_map(function($word) use ($preserve_acronyms) {
        return nelx_process_word($word, $preserve_acronyms);
    }, $words);
    
    // Join back with spaces
    $formatted = implode(' ', $processed_words);
    
    // Apply final capitalization
    return nelx_apply_capitalization($formatted, $capitalization, $preserve_acronyms);
}

/**
 * Process a single word for proper formatting
 */
function nelx_process_word($word, $preserve_acronyms = true) {
    if (empty($word)) {
        return '';
    }
    
    $lower_word = strtolower($word);
    $special_formatting = nelx_get_special_formatting();
    
    // Check for special formatting on individual word
    if (isset($special_formatting[$lower_word])) {
        return $special_formatting[$lower_word];
    }
    
    // Handle hyphenated words (e.g., "high-quality" should become "High-Quality")
    if (strpos($word, '-') !== false) {
        return nelx_format_hyphenated_word($word, $preserve_acronyms);
    }
    
    // Handle acronyms
    if ($preserve_acronyms && nelx_is_acronym($word)) {
        return strtoupper($word);
    }
    
    // Handle words that are fully uppercase (might be acronyms)
    if ($preserve_acronyms && ctype_upper($word) && strlen($word) > 1) {
        return $word;
    }
    
    // Normal word - capitalize first letter, rest lowercase
    return ucfirst(strtolower($word));
}

/**
 * Format hyphenated words (e.g., mother-child -> Mother-Child)
 */
function nelx_format_hyphenated_word($word, $preserve_acronyms = true) {
    $parts = explode('-', $word);
    $formatted_parts = array_map(function($part) use ($preserve_acronyms) {
        return nelx_process_word($part, $preserve_acronyms);
    }, $parts);
    return implode('-', $formatted_parts);
}

/**
 * Check if a word is an acronym
 */
function nelx_is_acronym($word) {
    $len = strlen($word);
    $acronyms = nelx_get_acronyms();
    
    // Acronyms are typically 2-5 characters
    if ($len < 2 || $len > 5) {
        return false;
    }
    
    // Must contain only letters
    if (!ctype_alpha($word)) {
        return false;
    }
    
    // Check against common acronyms list
    if (in_array(strtolower($word), $acronyms)) {
        return true;
    }
    
    // If it's all uppercase and length 2-4, treat as acronym
    if (ctype_upper($word) && $len <= 4) {
        return true;
    }
    
    return false;
}

/**
 * Apply final capitalization to the entire string
 */
function nelx_apply_capitalization($text, $style, $preserve_acronyms = true) {
    switch ($style) {
        case 'sentence':
            return ucfirst($text);
        case 'upper':
            return strtoupper($text);
        case 'lower':
            return strtolower($text);
        case 'title':
        default:
            if ($preserve_acronyms) {
                return nelx_title_case_preserve_acronyms($text);
            }
            return ucwords(strtolower($text));
    }
}

/**
 * Apply title case while preserving acronyms and special words
 */
function nelx_title_case_preserve_acronyms($text) {
    $words = explode(' ', $text);
    
    $processed_words = array_map(function($word) {
        // Check if it's an acronym (all uppercase)
        if (ctype_upper($word) && strlen($word) >= 2 && strlen($word) <= 5) {
            return $word;
        }
        
        // Check if it's a hyphenated word that might contain acronyms
        if (strpos($word, '-') !== false) {
            $parts = explode('-', $word);
            $formatted_parts = array_map(function($part) {
                if (ctype_upper($part) && strlen($part) >= 2 && strlen($part) <= 5) {
                    return $part;
                }
                return ucfirst(strtolower($part));
            }, $parts);
            return implode('-', $formatted_parts);
        }
        
        // Normal word - capitalize first letter
        return ucfirst(strtolower($word));
    }, $words);
    
    return implode(' ', $processed_words);
}
