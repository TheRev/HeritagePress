<?php
/**
 * Database Upgrade Manager
 */

namespace HeritagePress\Database;

/**
 * Class DatabaseUpgradeManager
 */
class DatabaseUpgradeManager {
    /**
     * Option name for storing the database version
     */
    private const OPTION_NAME = 'heritage_press_db_version';

    /**
     * Current database version
     */
    private const CURRENT_VERSION = '1.0.0';

    /**
     * Get the current database version
     */
    private function get_db_version() {
        return get_option(self::OPTION_NAME, '0');
    }

    /**
     * Update the database version
     */
    private function update_db_version($version) {
        update_option(self::OPTION_NAME, $version);
    }

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
        $table_prefix = $wpdb->prefix . 'heritage_press_';

        // Add foreign key constraints if they don't exist
        $constraints = [
            "ALTER TABLE {$table_prefix}individuals 
                ADD CONSTRAINT fk_individuals_birth_place FOREIGN KEY (birth_place_id) REFERENCES {$table_prefix}places(id),
                ADD CONSTRAINT fk_individuals_death_place FOREIGN KEY (death_place_id) REFERENCES {$table_prefix}places(id)",
            "ALTER TABLE {$table_prefix}families 
                ADD CONSTRAINT fk_families_husband FOREIGN KEY (husband_id) REFERENCES {$table_prefix}individuals(id),
                ADD CONSTRAINT fk_families_wife FOREIGN KEY (wife_id) REFERENCES {$table_prefix}individuals(id),
                ADD CONSTRAINT fk_families_marriage_place FOREIGN KEY (marriage_place_id) REFERENCES {$table_prefix}places(id),
                ADD CONSTRAINT fk_families_divorce_place FOREIGN KEY (divorce_place_id) REFERENCES {$table_prefix}places(id)",
            "ALTER TABLE {$table_prefix}events 
                ADD CONSTRAINT fk_events_individual FOREIGN KEY (individual_id) REFERENCES {$table_prefix}individuals(id) ON DELETE CASCADE,
                ADD CONSTRAINT fk_events_family FOREIGN KEY (family_id) REFERENCES {$table_prefix}families(id) ON DELETE CASCADE,
                ADD CONSTRAINT fk_events_place FOREIGN KEY (place_id) REFERENCES {$table_prefix}places(id)",
            "ALTER TABLE {$table_prefix}places 
                ADD CONSTRAINT fk_places_parent FOREIGN KEY (parent_id) REFERENCES {$table_prefix}places(id)"
        ];

        foreach ($constraints as $sql) {
            try {
                $wpdb->query($sql);
            } catch (\Exception $e) {
                // Constraint might already exist
                continue;
            }
        }

        return true;
    }
}
