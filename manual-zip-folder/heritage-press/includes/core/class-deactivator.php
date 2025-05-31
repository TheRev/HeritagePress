<?php
/**
 * Fired during plugin deactivation
 *
 * @package HeritagePress
 */

namespace HeritagePress\Core;

/**
 * Class Deactivator
 */
class Deactivator {
    /**
     * Deactivate the plugin
     */
    public static function deactivate($delete_data = false) {
        // Get plugin options
        $options = get_option('heritage_press_options');

        // Clean up scheduled tasks
        wp_clear_scheduled_hook('heritage_press_daily_maintenance');

        if ($delete_data) {
            // Drop all plugin tables
            global $wpdb;
            $tables = [
                'individuals',
                'families',
                'events',
                'places',
                'sources',
                'repositories',
                'citations',
                'media'
            ];

            foreach ($tables as $table) {
                $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}heritage_press_{$table}");
            }

            // Delete options
            delete_option('heritage_press_options');
            delete_option('heritage_press_db_version');
        }

        // Clear caches
        wp_cache_flush();
    }
}
