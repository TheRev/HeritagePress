<?php
/**
 * Fired during plugin activation
 *
 * @since      1.0.0
 * @package HeritagePress
 */

namespace HeritagePress\Core;

/**
 * Class Activator
 */
class Activator {
    /**
     * Activate the plugin
     */
    public static function activate() {
        $database_manager = new \HeritagePress\Database\Database_Manager();
        $database_manager->create_tables();

        // Schedule daily maintenance tasks
        if (!wp_next_scheduled('heritage_press_daily_maintenance')) {
            wp_schedule_event(time(), 'daily', 'heritage_press_daily_maintenance');
        }        // Set default options if not already set
        $default_options = [
            'use_wp_media' => true,
            'media_privacy' => 'public',
            'optimize_images' => true,
            'batch_size' => 50,
            'save_interval' => 30,
            'detailed_error_logging' => true,
        ];

        if (!get_option('heritage_press_options')) {
            update_option('heritage_press_options', $default_options);
        }

        // Set version options
        update_option('heritage_press_version', HERITAGE_PRESS_VERSION);
        update_option('heritage_press_db_version', HERITAGE_PRESS_VERSION);
    }
}
