<?php
namespace HeritagePress\Tests\Unit;

use HeritagePress\Database\Database_Manager;
use HeritagePress\Tests\HeritageTestCase;

class DatabaseManagerTest extends HeritageTestCase {
    protected $db_manager;
    protected $wpdb;

    public function setUp(): void {
        parent::setUp();
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->db_manager = new Database_Manager();
    }

    public function test_table_creation() {
        // Create tables
        $this->db_manager->create_tables();

        // Verify tables exist
        $table_prefix = $this->wpdb->prefix . 'heritage_press_';
        $expected_tables = [
            'individuals',
            'families',
            'events',
            'places',
            'media',
            'sources',
            'repositories',
            'citations',
            'media_relationships',
            'family_children',
            'gedcom_trees'
        ];

        foreach ($expected_tables as $table) {
            $table_name = $table_prefix . $table;
            $this->assertTrue(
                $this->table_exists($table_name),
                "Table {$table_name} should exist"
            );
        }
    }

    public function test_foreign_key_constraints() {
        $this->db_manager->create_tables();
        $table_prefix = $this->wpdb->prefix . 'heritage_press_';

        // Test constraints between individuals and places
        $constraints = $this->get_foreign_keys($table_prefix . 'individuals');
        $this->assertContains('fk_individuals_birth_place', $constraints);
        $this->assertContains('fk_individuals_death_place', $constraints);

        // Test constraints between families and individuals/places
        $constraints = $this->get_foreign_keys($table_prefix . 'families');
        $this->assertContains('fk_families_husband', $constraints);
        $this->assertContains('fk_families_wife', $constraints);
        $this->assertContains('fk_families_marriage_place', $constraints);
        $this->assertContains('fk_families_divorce_place', $constraints);

        // Test constraints for citations
        $constraints = $this->get_foreign_keys($table_prefix . 'citations');
        $this->assertContains('fk_citations_source', $constraints);
        $this->assertContains('fk_citations_individual', $constraints);
        $this->assertContains('fk_citations_family', $constraints);
        $this->assertContains('fk_citations_event', $constraints);
        $this->assertContains('fk_citations_place', $constraints);
    }

    public function test_table_prefix() {
        $prefix = Database_Manager::get_table_prefix();
        $this->assertEquals($this->wpdb->prefix . 'heritage_press_', $prefix);
    }

    private function table_exists($table_name) {
        $result = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table_name
            )
        );
        return $result === $table_name;
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
