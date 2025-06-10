<?php
/**
 * Database Manager class for HeritagePress plugin
 *
 * @package HeritagePress
 */

namespace HeritagePress\Database;

use HeritagePress\Database\WPHelper;

// Ensure WPHelper is loaded
if (!class_exists('HeritagePress\Database\WPHelper')) {
    require_once __DIR__ . '/WPHelper.php';
}

/**
 * Database Manager class
 */
class Manager
{
    /** @var string Schema directory path */
    private $schema_dir;

    /** @var string Plugin version */
    private $version;

    /** @var string Plugin directory */
    private $plugin_dir;

    /** @var \wpdb WordPress database object */
    private $wpdb;

    /**
     * Constructor
     * 
     * @param string $plugin_dir Optional plugin directory path
     * @param string $version Optional plugin version
     */
    public function __construct($plugin_dir = null, $version = '1.0.0')
    {
        global $wpdb;
        $this->wpdb = $wpdb;

        // Ensure plugin directory path ends with a slash
        $this->plugin_dir = rtrim($plugin_dir ?: dirname(dirname(dirname(__FILE__))), '/') . '/';
        $this->version = $version;
        $this->schema_dir = $this->plugin_dir . 'includes/Database/schema/';
    }

    /**
     * Install database tables (called during plugin activation)
     */
    public function install()
    {
        // Create the database tables
        $this->init_tables();

        // Store the version
        WPHelper::updateOption('heritagepress_db_version', $this->version);

        // Create a default tree
        $this->create_default_tree();
    }

    /**
     * Create default tree
     */
    private function create_default_tree()
    {
        $default_tree = [
            'title' => 'My Family Tree',
            'description' => 'Default family tree created during installation',
            'privacy_level' => 0,
            'owner_user_id' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        // Check if default tree already exists
        $existing = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->wpdb->prefix}hp_trees WHERE title = 'My Family Tree'");
        if (!$existing) {
            $this->wpdb->insert($this->wpdb->prefix . 'hp_trees', $default_tree);
        }
    }

    /**
     * Initialize database tables
     */
    public function init_tables()
    {
        $charset_collate = $this->wpdb->get_charset_collate();

        // Get the SQL files
        $sql_files = [
            'core-tables.sql',
            'documentation-tables.sql',
            'gedcom7-tables.sql',
            'compliance-tables.sql',
        ];
        foreach ($sql_files as $sql_file) {
            error_log("HeritagePress: Processing SQL file: $sql_file");
            $sql = file_get_contents($this->schema_dir . $sql_file);
            if ($sql === false) {
                error_log('HeritagePress: Failed to read SQL file: ' . $sql_file);
                continue;
            }
            error_log("HeritagePress: SQL file read successfully, " . strlen($sql) . " bytes");

            // Replace both { $prefix } and {$prefix} with the actual prefix
            $sql_before = $sql;
            $sql = str_replace(['{ $prefix }', '{$prefix}'], $this->wpdb->prefix, $sql);
            error_log("HeritagePress: Prefix replacement - before: " . substr_count($sql_before, '{$prefix}') . " instances, after replacement with: " . $this->wpdb->prefix);            // Add charset and collate
            $sql = str_replace(['ENGINE = InnoDB', 'ENGINE=InnoDB'], [
                'ENGINE = InnoDB ' . $charset_collate,
                'ENGINE=InnoDB ' . $charset_collate
            ], $sql);

            // Find all CREATE TABLE statements using regex
            $pattern = '/CREATE TABLE IF NOT EXISTS[^;]+;/i';
            if (preg_match_all($pattern, $sql, $matches)) {
                $statements = $matches[0];
                error_log("HeritagePress: Found " . count($statements) . " CREATE TABLE statements");

                $create_count = 0;
                foreach ($statements as $i => $statement) {
                    $statement = trim($statement);
                    if (empty($statement)) {
                        continue;
                    }

                    $create_count++;
                    // Extract table name for logging
                    if (preg_match('/CREATE TABLE IF NOT EXISTS\s+(\w+)/i', $statement, $table_matches)) {
                        $table_name = $table_matches[1];
                        error_log("HeritagePress: Executing CREATE TABLE for: $table_name");
                    }

                    error_log("HeritagePress: SQL Statement: " . substr($statement, 0, 100) . "...");
                    $result = WPHelper::dbDelta($statement);
                    error_log("HeritagePress: dbDelta result: " . print_r($result, true));
                }
                error_log("HeritagePress: Processed $create_count CREATE TABLE statements from $sql_file");
            } else {
                error_log("HeritagePress: No CREATE TABLE statements found in $sql_file");
            }
        }        // Initialize default calendar systems (if class exists)
        try {
            if (class_exists('HeritagePress\Models\CalendarSystem')) {
                error_log('HeritagePress: Initializing CalendarSystem...');
                $calendar_system = new \HeritagePress\Models\CalendarSystem();
                $result = $calendar_system->initDefaults();
                if ($result) {
                    error_log('HeritagePress: CalendarSystem initialized successfully');
                } else {
                    error_log('HeritagePress: CalendarSystem initialization returned false');
                }
            } else {
                error_log('HeritagePress: CalendarSystem class not found');
            }
        } catch (\Exception $e) {
            error_log('HeritagePress: Failed to initialize calendar systems: ' . $e->getMessage());
        }

        // Store the schema version
        WPHelper::updateOption('heritagepress_db_version', $this->version);
    }

    /**
     * Check if database tables need to be updated
     *
     * @return bool True if update is needed
     */
    public function needs_update()
    {
        $current_version = WPHelper::getOption('heritagepress_db_version', '0');
        return version_compare($current_version, $this->version, '<');
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

    /**
     * Get the WordPress database object
     *
     * @return object WordPress database object
     */
    public function get_wpdb()
    {
        global $wpdb;
        return $wpdb;
    }
}