<?php
/**
 * Timezone Helper for JetAppointments
 * Handles conversions between Provider Timezone (WP Settings) and Client Timezone
 */

if (!defined('ABSPATH')) exit;

class NELXJAF_Timezone_Helper {
    
    private static $instance = null;
    public $wp_timezone;
    private $wp_timezone_string;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->wp_timezone = wp_timezone();
        $this->wp_timezone_string = $this->wp_timezone->getName();
    }
    
    public function get_timezone_dropdown($selected = '', $name = 'timezone') {
        if (!function_exists('wp_timezone_choice')) {
            require_once ABSPATH . 'wp-admin/includes/misc.php';
        }
        
        $choices = wp_timezone_choice($selected);
        
        $output = sprintf(
            '<select name="%s" id="%s" class="nelx-input nelx-timezone-select">',
            esc_attr($name),
            esc_attr($name)
        );
        $output .= $choices;
        $output .= '</select>';
        
        return $output;
    }
    
    public function get_timezone_choices_array($selected_zone = '') {
        if (!function_exists('wp_timezone_choice')) {
            require_once ABSPATH . 'wp-admin/includes/misc.php';
        }
        
        $all_zones = timezone_identifiers_list();
        $continents = array('Africa', 'America', 'Antarctica', 'Arctic', 'Asia', 'Atlantic', 'Australia', 'Europe', 'Indian', 'Pacific');
        
        $zones = array();
        $manual_zones = array();
        
        foreach ($all_zones as $zone) {
            $zone_parts = explode('/', $zone);
            if (in_array($zone_parts[0], $continents)) {
                $continent = $zone_parts[0];
                if (!isset($zones[$continent])) {
                    $zones[$continent] = array();
                }
                
                $city = str_replace('_', ' ', $zone_parts[1]);
                if (isset($zone_parts[2])) {
                    $city .= ' - ' . str_replace('_', ' ', $zone_parts[2]);
                }
                
                $offset_label = $this->get_timezone_offset_label($zone);
                
                $zones[$continent][] = array(
                    'value' => $zone,
                    'label' => sprintf('(%s) %s', $offset_label, $city),
                );
            } else {
                $offset_label = $this->get_timezone_offset_label($zone);
                
                $manual_zones[] = array(
                    'value' => $zone,
                    'label' => sprintf('(%s) %s', $offset_label, $zone),
                );
            }
        }
        
        usort($manual_zones, function($a, $b) {
            return strcmp($a['label'], $b['label']);
        });
        
        if (!empty($manual_zones)) {
            $zones['Manual Offsets'] = $manual_zones;
        }
        
        return $zones;
    }
    
    public function is_valid_timezone($timezone) {
        if (empty($timezone)) {
            return false;
        }
        return in_array($timezone, timezone_identifiers_list(), true);
    }
    
    public function get_timezone_offset_label($timezone) {
        try {
            $tz = new DateTimeZone($timezone);
            $offset = $tz->getOffset(new DateTime('now', new DateTimeZone('UTC'))) / 3600;
            return $this->format_offset($offset);
        } catch (Exception $e) {
            return 'UTC+00:00';
        }
    }
    
    public function timestamp_to_provider_local($timestamp, $format = 'H:i') {
        $datetime = new DateTime('@' . $timestamp);
        $datetime->setTimezone($this->wp_timezone);
        return $datetime->format($format);
    }
    
    public function timestamp_to_provider_date($timestamp, $format = 'Y-m-d') {
        $datetime = new DateTime('@' . $timestamp);
        $datetime->setTimezone($this->wp_timezone);
        return $datetime->format($format);
    }
    
    public function provider_local_to_client($provider_date, $provider_time, $client_timezone_string, $format = 'H:i') {
        
        if (empty($client_timezone_string) || !$this->is_valid_timezone($client_timezone_string)) {
            return $provider_time;
        }
        
        try {
            $provider_tz = $this->wp_timezone;
            $datetime = new DateTime($provider_date . ' ' . $provider_time, $provider_tz);
            
            $client_tz = new DateTimeZone($client_timezone_string);
            $datetime->setTimezone($client_tz);
            
            $result = $datetime->format($format);
            
            return $result;
        } catch (Exception $e) {
            return $provider_time;
        }
    }
    
    public function utc_to_client_timezone($utc_timestamp, $client_timezone_string, $format = 'H:i') {
        
        if (empty($client_timezone_string) || !$this->is_valid_timezone($client_timezone_string)) {
            $datetime = new DateTime('@' . $utc_timestamp);
            $result = $datetime->format($format);
            return $result;
        }
        
        try {
            $datetime = new DateTime('@' . $utc_timestamp);
            
            $client_tz = new DateTimeZone($client_timezone_string);
            $datetime->setTimezone($client_tz);
            
            $result = $datetime->format($format);
            return $result;
        } catch (Exception $e) {
            $datetime = new DateTime('@' . $utc_timestamp);
            return $datetime->format($format);
        }
    }
    
    public function client_time_to_provider_local($client_date, $client_time, $client_timezone_string) {
        
        if (!$this->is_valid_timezone($client_timezone_string)) {
            return 0;
        }
        
        try {
            $client_tz = new DateTimeZone($client_timezone_string);
            $client_datetime = new DateTime($client_date . ' ' . $client_time, $client_tz);
            
            $provider_tz = $this->wp_timezone;
            
            $provider_datetime = clone $client_datetime;
            $provider_datetime->setTimezone($provider_tz);
            
            $utc_tz = new DateTimeZone('UTC');
            $utc_datetime = new DateTime($provider_datetime->format('Y-m-d H:i:s'), $utc_tz);
            $timestamp = $utc_datetime->getTimestamp();
            return $timestamp;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get cached meta value for appointment
     */
    private function get_cached_meta_value($appointment_id, $meta_key) {
        $cache_key = 'nelx_tz_meta_' . $appointment_id . '_' . $meta_key;
        $cached = wp_cache_get($cache_key, 'nelx_timezone_meta');
        
        if ($cached !== false) {
            return $cached;
        }
        
        global $wpdb;
        $core = NELXJAF_Core::instance();
        $meta_table = $core->appt_meta_table;
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.SlowDBQuery.slow_db_query_meta_key
        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$meta_table} WHERE appointment_id = %d AND meta_key = %s",
            $appointment_id,
            $meta_key
        ));
        
        if ($value !== null) {
            wp_cache_set($cache_key, $value, 'nelx_timezone_meta', HOUR_IN_SECONDS);
        }
        
        return $value;
    }
    
    /**
     * Clear cached meta value for appointment
     */
    private function clear_cached_meta_value($appointment_id, $meta_key = null) {
        if ($meta_key) {
            $cache_key = 'nelx_tz_meta_' . $appointment_id . '_' . $meta_key;
            wp_cache_delete($cache_key, 'nelx_timezone_meta');
        } else {
            // Clear all timezone meta for this appointment
            $meta_keys = ['user_timezone', 'user_local_date', 'user_local_time'];
            foreach ($meta_keys as $key) {
                $cache_key = 'nelx_tz_meta_' . $appointment_id . '_' . $key;
                wp_cache_delete($cache_key, 'nelx_timezone_meta');
            }
        }
    }
    
    public function get_client_timezone($appointment_id) {
        $timezone = $this->get_cached_meta_value($appointment_id, 'user_timezone');
        
        if ($timezone && $this->is_valid_timezone($timezone)) {
            return $timezone;
        }
        return '';
    }
    
    public function get_client_local_time($appointment_id) {
        $local_time = $this->get_cached_meta_value($appointment_id, 'user_local_time');
        return $local_time ?: '';
    }
    
    public function get_client_local_date($appointment_id) {
        $local_date = $this->get_cached_meta_value($appointment_id, 'user_local_date');
        return $local_date ?: '';
    }
    
    public function update_client_timezone_data($appointment_id, $client_timezone, $local_date, $local_time) {
        global $wpdb;
        $core = NELXJAF_Core::instance();
        $meta_table = $core->appt_meta_table;
        
        if (!empty($client_timezone) && !$this->is_valid_timezone($client_timezone)) {
            $client_timezone = '';
        }
        
        $this->update_meta_value($appointment_id, 'user_timezone', $client_timezone);
        $this->update_meta_value($appointment_id, 'user_local_date', $local_date);
        $this->update_meta_value($appointment_id, 'user_local_time', $local_time);
        
        // Clear cache for all timezone meta
        $this->clear_cached_meta_value($appointment_id);
    }
    
    public function get_provider_timezone() {
        return $this->wp_timezone_string;
    }
    
    private function update_meta_value($appointment_id, $meta_key, $meta_value) {
        global $wpdb;
        $core = NELXJAF_Core::instance();
        $meta_table = $core->appt_meta_table;
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$meta_table} WHERE appointment_id = %d AND meta_key = %s",
            $appointment_id,
            $meta_key
        ));
        
        if ($exists) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->update(
                $meta_table,
                ['meta_value' => $meta_value],
                ['appointment_id' => $appointment_id, 'meta_key' => $meta_key],
                ['%s'],
                ['%d', '%s']
            );
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->insert(
                $meta_table,
                [
                    'appointment_id' => $appointment_id,
                    'meta_key' => $meta_key,
                    'meta_value' => $meta_value
                ],
                ['%d', '%s', '%s']
            );
        }
        
        // Clear cache for this specific meta key
        $this->clear_cached_meta_value($appointment_id, $meta_key);
    }
    
    private function format_offset($offset) {
        $total_minutes = round(abs($offset) * 60);
        $hours = floor($total_minutes / 60);
        $minutes = $total_minutes % 60;
        
        $sign = $offset >= 0 ? '+' : '-';
        
        if ($minutes > 0) {
            return sprintf('UTC%s%02d:%02d', $sign, $hours, $minutes);
        } else {
            return sprintf('UTC%s%02d:00', $sign, $hours);
        }
    }
}
