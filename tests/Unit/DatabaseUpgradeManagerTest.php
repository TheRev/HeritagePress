<?php
namespace HeritagePress\Tests\Unit;

use HeritagePress\Database\DatabaseUpgradeManager;
use HeritagePress\Tests\HeritageTestCase;

class DatabaseUpgradeManagerTest extends HeritageTestCase {
    protected $upgrade_manager;
    protected $wpdb;

    public function setUp(): void {
        parent::setUp();
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->upgrade_manager = new DatabaseUpgradeManager();
        
        // Clean up any existing version
        delete_option('heritage_press_db_version');
    }

    public function test_needs_upgrade_when_no_version() {
        $this->assertTrue($this->upgrade_manager->needs_upgrade());
    }

    public function test_needs_upgrade_with_old_version() {
        update_option('heritage_press_db_version', '0.9.0');
        $this->assertTrue($this->upgrade_manager->needs_upgrade());
    }

    public function test_needs_upgrade_with_current_version() {
        // Set to current version using reflection to access private constant
        $reflection = new \ReflectionClass($this->upgrade_manager);
        $current_version = $reflection->getConstant('CURRENT_VERSION');
        update_option('heritage_press_db_version', $current_version);
        
        $this->assertFalse($this->upgrade_manager->needs_upgrade());
    }

    public function test_upgrade_applies_constraints() {
        $table_prefix = $this->wpdb->prefix . 'heritage_press_';
        
        // First create tables without constraints
        $this->create_base_tables();
        
        // Run upgrade
        $this->upgrade_manager->upgrade();
        
        // Verify constraints were added
        $constraints = $this->get_foreign_keys($table_prefix . 'individuals');
        $this->assertContains('fk_individuals_birth_place', $constraints);
        $this->assertContains('fk_individuals_death_place', $constraints);
    }

    public function test_version_is_updated_after_upgrade() {
        // Run upgrade
        $this->upgrade_manager->upgrade();
        
        // Check version was updated
        $reflection = new \ReflectionClass($this->upgrade_manager);
        $current_version = $reflection->getConstant('CURRENT_VERSION');
        $saved_version = get_option('heritage_press_db_version');
        
        $this->assertEquals($current_version, $saved_version);
    }

    private function create_base_tables() {
        $charset_collate = $this->wpdb->get_charset_collate();
        $table_prefix = $this->wpdb->prefix . 'heritage_press_';

        // Create minimal tables needed for testing constraints
        $this->wpdb->query("CREATE TABLE IF NOT EXISTS {$table_prefix}individuals (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            birth_place_id bigint(20),
            death_place_id bigint(20),
            PRIMARY KEY (id)
        ) $charset_collate");

        $this->wpdb->query("CREATE TABLE IF NOT EXISTS {$table_prefix}places (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate");
    }

    private function get_foreign_keys($table_name) {
        $constraints = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = %s 
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
                $table_name
            )
        );

        return array_map(function($row) {
            return $row->CONSTRAINT_NAME;
        }, $constraints);
    }
}
