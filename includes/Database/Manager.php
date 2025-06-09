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
class Manager
{
    /**
     * Schema directory path
     *
     * @var string
     */
    private $schema_dir;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->schema_dir = \HP_PLUGIN_PATH . 'includes/Database/schema/';
    }

    /**
     * Initialize database tables
     */
    public function init_tables()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Get the SQL files
        $sql_files = [
            'core-tables.sql',
            'documentation-tables.sql',
            'gedcom7-tables.sql',
            'compliance-tables.sql',
        ];

        foreach ($sql_files as $sql_file) {
            $sql = file_get_contents($this->schema_dir . $sql_file);
            if ($sql === false) {
                error_log('HeritagePress: Failed to read SQL file: ' . $sql_file);
                continue;
            }

            // Replace prefix placeholder with actual prefix
            $sql = str_replace('{$prefix}', $wpdb->prefix, $sql);

            // Add charset and collate
            $sql = str_replace('ENGINE=InnoDB', 'ENGINE=InnoDB ' . $charset_collate, $sql);

            // Run the SQL
            WPHelper::dbDelta($sql);
        }

        // Initialize default calendar systems
        $calendar_system = new \HeritagePress\Models\CalendarSystem();
        $calendar_system->initDefaults();

        // Store the schema version
        WPHelper::updateOption('heritagepress_db_version', \HP_PLUGIN_VERSION);
    }

    /**
     * Check if database tables need to be updated
     *
     * @return bool True if update is needed
     */
    public function needs_update()
    {
        $current_version = WPHelper::getOption('heritagepress_db_version', '0');
        return version_compare($current_version, \HP_PLUGIN_VERSION, '<');
    }

    /**
     * Get list of required tables
     *
     * @return array List of table names without prefix
     */
    public function get_required_tables()
    {
        return [
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
            // New GEDCOM 7 tables
            'hp_gedzip_archives',
            'hp_gedzip_files',
            'hp_calendar_systems',
            'hp_calendar_dates',
            'hp_extended_characters',
            'hp_extended_character_mappings',
            'hp_event_roles',
            'hp_family_groups',
            'hp_family_group_members',
            'hp_dna_tests',
            'hp_dna_matches',
            'hp_dna_segments',
        ];
    }
}
