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
        $charset_collate = $this->wpdb->get_charset_collate();        // Get the SQL files - Use complete genealogy schema for comprehensive compatibility
        $sql_files = [
            'complete-genealogy-schema.sql',      // Contains all 39 genealogy tables
            'default-event-types.sql',           // Standard GEDCOM event types
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

        // Fix missing columns required for GEDCOM import
        error_log('HeritagePress: Checking and fixing missing columns...');
        $column_results = $this->fix_missing_columns();
        foreach ($column_results as $table => $columns) {
            if (isset($columns['error'])) {
                error_log("HeritagePress: Column fix error for $table: " . $columns['error']);
            } else {
                foreach ($columns as $column => $status) {
                    error_log("HeritagePress: Column $table.$column: $status");
                }
            }
        }

        // Store the schema version
        WPHelper::updateOption('heritagepress_db_version', $this->version);
    }    /**
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
     * Fix missing columns that are required for GEDCOM import
     * 
     * @return array Results of column additions
     */
    public function fix_missing_columns()
    {
        $results = [];

        // Define the missing columns that need to be added
        $column_fixes = [
            'hp_people' => [
                'person_id' => 'VARCHAR(50) NOT NULL AFTER gedcom'
            ],
            'hp_families' => [
                'family_id' => 'VARCHAR(50) NOT NULL AFTER gedcom'
            ],
            'hp_sources' => [
                'source_id' => 'VARCHAR(50) NOT NULL AFTER gedcom'
            ],
            'hp_repositories' => [
                'name' => 'VARCHAR(255) NOT NULL AFTER repo_id'
            ],
            'hp_media' => [
                'media_id' => 'VARCHAR(50) NOT NULL AFTER gedcom'
            ]
        ];

        foreach ($column_fixes as $table_name => $columns) {
            $full_table_name = $this->wpdb->prefix . $table_name;
            $results[$table_name] = [];

            // Check if table exists
            $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
            if (!$table_exists) {
                $results[$table_name]['error'] = "Table $full_table_name does not exist";
                error_log("HeritagePress: Table $full_table_name does not exist");
                continue;
            }

            foreach ($columns as $column_name => $column_definition) {
                // Check if column already exists
                $column_exists = $this->wpdb->get_var("SHOW COLUMNS FROM $full_table_name LIKE '$column_name'");

                if ($column_exists) {
                    $results[$table_name][$column_name] = 'exists';
                    error_log("HeritagePress: Column $column_name already exists in $full_table_name");
                } else {
                    // Add the missing column
                    $sql = "ALTER TABLE $full_table_name ADD COLUMN $column_name $column_definition";
                    error_log("HeritagePress: Executing: $sql");

                    $result = $this->wpdb->query($sql);
                    if ($result !== false) {
                        $results[$table_name][$column_name] = 'added';
                        error_log("HeritagePress: Successfully added column $column_name to $full_table_name");
                    } else {
                        $results[$table_name][$column_name] = 'failed: ' . $this->wpdb->last_error;
                        error_log("HeritagePress: Failed to add column $column_name to $full_table_name: " . $this->wpdb->last_error);
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Get list of required tables
     *
     * @return array List of table names without prefix
     */
    public function get_required_tables()
    {
        return [
            // Core TNG Tables (22 tables)
            'hp_trees',
            'hp_people',
            'hp_families',
            'hp_children',
            'hp_events',
            'hp_eventtypes',
            'hp_places',
            'hp_sources',
            'hp_repositories',
            'hp_citations',
            'hp_media',
            'hp_medialinks',
            'hp_xnotes',
            'hp_notelinks',
            'hp_associations',
            'hp_countries',
            'hp_states',
            'hp_mediatypes',
            'hp_languages',
            'hp_gedcom7_enums',
            'hp_gedcom7_extensions',
            'hp_gedcom7_data',

            // Remaining TNG Tables (17 tables) - for 100% compatibility
            'hp_address',
            'hp_albums',
            'hp_albumlinks',
            'hp_album2entities',
            'hp_branches',
            'hp_branchlinks',
            'hp_cemeteries',
            'hp_dna_groups',
            'hp_dna_links',
            'hp_dna_tests',
            'hp_image_tags',
            'hp_mostwanted',
            'hp_reports',
            'hp_saveimport',
            'hp_temp_events',
            'hp_templates',
            'hp_users',
        ];
    }    /**
         * Get the WordPress database object
         *
         * @return object WordPress database object
         */
    public function get_wpdb()
    {
        global $wpdb;
        return $wpdb;
    }

    /**
     * Public method to manually trigger column fixes
     * Useful for troubleshooting import issues
     * 
     * @return array Results of column additions
     */
    public function install_missing_columns()
    {
        error_log('HeritagePress: Manual column fix triggered');
        return $this->fix_missing_columns();
    }
}