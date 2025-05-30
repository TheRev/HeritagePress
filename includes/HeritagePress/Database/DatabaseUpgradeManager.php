<?php
namespace HeritagePress\Database;

/**
 * Database Upgrade Manager
 * 
 * Handles database version tracking and upgrades between versions.
 */
class DatabaseUpgradeManager {
    private const OPTION_NAME = 'heritage_press_db_version';
    private const CURRENT_VERSION = '1.0.0';

    /**
     * Check if database needs upgrade
     */
    public function needs_upgrade(): bool {
        $current_version = get_option(self::OPTION_NAME, '0.0.0');
        return version_compare($current_version, self::CURRENT_VERSION, '<');
    }

    /**
     * Run database upgrades
     */
    public function upgrade() {
        $current_version = get_option(self::OPTION_NAME, '0.0.0');

        // Upgrades must run in order
        if (version_compare($current_version, '1.0.0', '<')) {
            $this->upgrade_to_1_0_0();
        }

        // Update version in database
        update_option(self::OPTION_NAME, self::CURRENT_VERSION);
    }

    /**
     * Upgrade to version 1.0.0
     */
    private function upgrade_to_1_0_0() {
        global $wpdb;
        $prefix = $wpdb->prefix . 'genealogy_';

        // Add meta column to gedcom_trees if it doesn't exist
        $wpdb->query(
            "ALTER TABLE {$prefix}gedcom_trees 
             ADD COLUMN IF NOT EXISTS meta longtext AFTER status"
        );

        // Add indexes if they don't exist
        $wpdb->query(
            "ALTER TABLE {$prefix}individuals 
             ADD INDEX IF NOT EXISTS idx_file_status (file_id, status)"
        );

        $wpdb->query(
            "ALTER TABLE {$prefix}families 
             ADD INDEX IF NOT EXISTS idx_file_status (file_id, status)"
        );

        // Add foreign key constraints if they don't exist
        $wpdb->query(
            "ALTER TABLE {$prefix}events 
             ADD CONSTRAINT IF NOT EXISTS fk_event_individual 
             FOREIGN KEY (individual_id) REFERENCES {$prefix}individuals(id) 
             ON DELETE CASCADE"
        );

        $wpdb->query(
            "ALTER TABLE {$prefix}events 
             ADD CONSTRAINT IF NOT EXISTS fk_event_family 
             FOREIGN KEY (family_id) REFERENCES {$prefix}families(id) 
             ON DELETE CASCADE"
        );
    }
}
