<?php
/**
 * Database Manager class for HeritagePress plugin
 *
 * @package HeritagePress
 */

namespace HeritagePress\Database;

/**
 * Database Manager class
 */
class Manager {
    /**
     * Initialize database tables
     */
    public function init_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Get the SQL files
        $sql_files = [
            'core-tables.sql',
            'documentation-tables.sql',
            'gedcom7-tables.sql',
            'compliance-tables.sql',
        ];

        // Load dbDelta function
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        foreach ($sql_files as $sql_file) {
            $sql = file_get_contents(HP_PLUGIN_DIR . 'includes/Database/schema/' . $sql_file);
            if ($sql === false) {
                error_log('HeritagePress: Failed to read SQL file: ' . $sql_file);
                continue;
            }

            // Replace prefix placeholder with actual prefix
            $sql = str_replace('{$prefix}', $wpdb->prefix, $sql);

            // Add charset and collate
            $sql = str_replace('ENGINE=InnoDB', 'ENGINE=InnoDB ' . $charset_collate, $sql);

            // Run the SQL
            dbDelta($sql);
        }

        // Store the schema version
        update_option('heritagepress_db_version', HP_PLUGIN_VERSION);
    }

    /**
     * Check if all required tables exist
     *
     * @return bool True if all tables exist, false otherwise
     */
    public function check_tables() {
        global $wpdb;

        $tables = [
            'hp_individuals',
            'hp_names',
            'hp_families',
            'hp_family_links',
            'hp_events',
            'hp_event_links',
            'hp_places',
            'hp_event_types',
            'hp_trees',
            'hp_repositories',
            'hp_sources',
            'hp_citations',
            'hp_citation_links',
            'hp_notes',
            'hp_note_links',
            'hp_media_objects',
            'hp_media_links',
            'hp_aliases',
            'hp_ages',
            'hp_relationships',
            'hp_multimedia_files',
            'hp_multimedia_references',
            'hp_multimedia_identifiers',
            'hp_multimedia_cross_references',
            'hp_submitters',
            'hp_headers',
            'hp_change_tracking',
            'hp_external_identifiers',
            'hp_user_reference_numbers',
            'hp_unique_identifiers',
        ];

        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $query = $wpdb->prepare('SHOW TABLES LIKE %s', $table_name);
            if (!$wpdb->get_var($query)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Drop all plugin tables
     *
     * @return void
     */
    public function drop_tables() {
        global $wpdb;

        $tables = [
            'hp_individuals',
            'hp_names',
            'hp_families',
            'hp_family_links',
            'hp_events',
            'hp_event_links',
            'hp_places',
            'hp_event_types',
            'hp_trees',
            'hp_repositories',
            'hp_sources',
            'hp_citations',
            'hp_citation_links',
            'hp_notes',
            'hp_note_links',
            'hp_media_objects',
            'hp_media_links',
            'hp_aliases',
            'hp_ages',
            'hp_relationships',
            'hp_multimedia_files',
            'hp_multimedia_references',
            'hp_multimedia_identifiers',
            'hp_multimedia_cross_references',
            'hp_submitters',
            'hp_headers',
            'hp_change_tracking',
            'hp_external_identifiers',
            'hp_user_reference_numbers',
            'hp_unique_identifiers',
        ];

        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $wpdb->query("DROP TABLE IF EXISTS $table_name");
        }

        delete_option('heritagepress_db_version');
    }
}
