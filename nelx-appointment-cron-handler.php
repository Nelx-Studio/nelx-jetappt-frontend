<?php
/**
 * Appointment Reminder Cron Handler
 * This file should only be called by server cron jobs
 */

// Simple security check
if (isset($_SERVER['REQUEST_METHOD']) || isset($_SERVER['HTTP_HOST'])) {
    exit('Direct access not allowed');
}

// Check if this is a test request
if (isset($argv) && in_array('--test', $argv)) {
    // CLI test mode
    echo "Cron handler is accessible and working.\n";
    echo "PHP Version: " . PHP_VERSION . "\n";
    echo "WordPress Path: " . __DIR__ . "\n";
    exit(0);
}

// Dynamically find wp-load.php by traversing up the directory tree
function nelxjaf_find_wp_load() {
    $dir = dirname(__FILE__);
    $max_attempts = 10;
    $attempts = 0;
    
    while ($attempts < $max_attempts) {
        $wp_load_path = $dir . '/wp-load.php';
        if (file_exists($wp_load_path)) {
            return $wp_load_path;
        }
        $parent_dir = dirname($dir);
        if ($parent_dir === $dir) {
            break;
        }
        $dir = $parent_dir;
        $attempts++;
    }
    return false;
}

$nelxjaf_wp_load_path = nelxjaf_find_wp_load();

if (!$nelxjaf_wp_load_path || !file_exists($nelxjaf_wp_load_path)) {
    exit('WordPress not found');
}

require_once $nelxjaf_wp_load_path;

// Verify WordPress loaded
if (!defined('ABSPATH')) {
    exit('Failed to load WordPress.');
}

// Check if our plugin constant is defined
if (!defined('NELXJAF_PLUGIN_DIR')) {
    exit('NELXJAF Plugin not active');
}

// Check if required classes exist
if (!class_exists('NELXJAF_Custom_Emails')) {
    exit('NELXJAF Plugin not properly loaded');
}

try {
    $nelxjaf_reminder_sent = 0;
    $nelxjaf_reminder_failed = 0;
    $nelxjaf_notifications_sent = 0;
    $nelxjaf_notifications_failed = 0;
    
    $nelxjaf_reminder_results = NELXJAF_Custom_Emails::instance()->send_scheduled_reminders();
    
    $nelxjaf_reminder_sent = $nelxjaf_reminder_results['sent'] ?? 0;
    $nelxjaf_reminder_failed = $nelxjaf_reminder_results['failed'] ?? 0;
    
    if (class_exists('NELXJAF_Appointment_Notifications')) {
        try {
            global $wpdb;
            $nelxjaf_core = NELXJAF_Core::instance();
            $nelxjaf_appt_table = $nelxjaf_core->appt_table;
            $nelxjaf_appt_meta_table = $nelxjaf_core->appt_meta_table;
            
            $nelxjaf_settings = get_option('nelx_jetappt_settings', []);
            $nelxjaf_reminder_timing = $nelxjaf_settings['reminder_timing'] ?? '24';
            $nelxjaf_reminder_seconds = intval($nelxjaf_reminder_timing) * HOUR_IN_SECONDS;
            $nelxjaf_current_time = current_time('timestamp');
            
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            $nelxjaf_appointments = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT a.* 
                     FROM {$nelxjaf_appt_table} a
                     LEFT JOIN {$nelxjaf_appt_meta_table} m1 ON a.ID = m1.appointment_id AND m1.meta_key = 'reminder_sent'
                     LEFT JOIN {$nelxjaf_appt_meta_table} m2 ON a.ID = m2.appointment_id AND m2.meta_key = 'reminder_notification_sent'
                     WHERE 
                         (a.slot - %d) <= %d
                         AND a.appointment_status = 'accepted'
                         AND m1.meta_value IS NOT NULL
                         AND m2.meta_value IS NULL
                     ORDER BY a.slot ASC",
                    $nelxjaf_reminder_seconds,
                    $nelxjaf_current_time
                ),
                ARRAY_A
            );
            
            foreach ($nelxjaf_appointments as $nelxjaf_appointment) {
                $nelxjaf_notification_result = NELXJAF_Appointment_Notifications::instance()->send_reminder_notification($nelxjaf_appointment);
                
                if ($nelxjaf_notification_result) {
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                    $wpdb->insert(
                        $nelxjaf_appt_meta_table,
                        [
                            'appointment_id' => $nelxjaf_appointment['ID'],
                            'meta_key' => 'reminder_notification_sent',
                            'meta_value' => current_time('mysql')
                        ],
                        ['%d', '%s', '%s']
                    );
                    $nelxjaf_notifications_sent++;
                } else {
                    $nelxjaf_notifications_failed++;
                }
            }
        } catch (Exception $e) {
            $nelxjaf_notifications_failed++;
        }
    }
    
    $nelxjaf_deletion_results = NELXJAF_Custom_Emails::instance()->process_automatic_deletions();
    
} catch (Exception $e) {
    // Silently fail
}
