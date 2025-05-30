<?php
/**
 * Fired during plugin deactivation
 *
 * @package HeritagePress
 */

namespace HeritagePress\Core;

class Deactivator {
    /**
     * Deactivate the plugin
     */
    public static function deactivate() {
        // Cleanup tasks if needed
        self::cleanup();
    }

    /**
     * Cleanup plugin data
     */
    private static function cleanup() {
        // Get plugin options
        $options = get_option('heritage_press_options');

        // If cleanup on deactivation is enabled
        if (!empty($options['cleanup_on_deactivate'])) {
            global $wpdb;
            $prefix = $wpdb->prefix . 'heritage_press_';

            // Drop all plugin tables
            $tables = [
                'individuals',
                'families',
                'events',
                'media',
                'places',
                'sources',
                'gedcom_trees'
            ];

            foreach ($tables as $table) {
                $wpdb->query("DROP TABLE IF EXISTS {$prefix}{$table}");
            }

            // Delete plugin options            delete_option('heritage_press_options');
            delete_option('heritage_press_db_version');
        }

        // Clear any scheduled tasks
        wp_clear_scheduled_hook('heritage_press_daily_maintenance');
    }
}
