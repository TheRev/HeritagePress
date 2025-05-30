<?php
/**
 * Fired during plugin activation
 *
 * @package HeritagePress
 */

namespace HeritagePress\Core;

use HeritagePress\Database\DatabaseManager;

class Activator {
    /**
     * Activate the plugin
     */
    public static function activate() {
        try {            // Run database migrations
            $database_manager = new DatabaseManager();
            $database_manager->create_tables();

            // Set default options
            self::set_default_options();

            // Log successful activation
            update_option('heritage_press_last_activation', current_time('mysql'));
            update_option('heritage_press_activation_error', '');
            update_option('heritage_press_version', HERITAGE_PRESS_VERSION);
        } catch (\Exception $e) {
            // Log activation error
            update_option('heritage_press_activation_error', $e->getMessage());
            throw new \Exception('Plugin activation failed: ' . $e->getMessage());
        }
    }

    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        add_option('heritage_press_version', HERITAGE_PRESS_VERSION);
        
        add_option('heritage_press_settings', [
            'privacy_level' => 'public',
            'date_format' => 'Y-m-d',
            'name_display_format' => 'surname_first',
            'evidence_methodology' => 'enabled'
        ]);
    }
}
