<?php
namespace HeritagePress\Tests;

use HeritagePress\Database\Database_Manager;
use HeritagePress\Database\GedcomDatabaseHandler;
use PHPUnit\Framework\TestCase;

class DatabaseIntegrationTest extends TestCase {
    protected $db_manager;
    protected $gedcom_handler;

    protected function setUp(): void {
        global $wpdb;
        $wpdb = $this->createMock(\wpdb::class);
        $wpdb->prefix = 'wp_';

        $this->db_manager = new Database_Manager();
        $this->gedcom_handler = new GedcomDatabaseHandler();
    }

    public function testDatabaseTableCreation() {
        global $wpdb;

        // Mock the successful table creation
        $wpdb->expects($this->atLeastOnce())
             ->method('query')
             ->willReturn(true);

        $schema = $this->db_manager->get_table_schema();
        $this->assertIsArray($schema);
        $this->assertNotEmpty($schema);
    }

    public function testGedcomTreeOperations() {
        global $wpdb;

        // Mock database interactions
        $wpdb->expects($this->atLeastOnce())
             ->method('insert')
             ->willReturn(1);

        $wpdb->expects($this->atLeastOnce())
             ->method('get_results')
             ->willReturn([
                ['id' => 1, 'tree_id' => 'test-tree', 'file_name' => 'test.ged']
             ]);

        // Test storing a new tree
        $result = $this->gedcom_handler->storeGedcomTree(
            ['individuals' => []],
            'test.ged'
        );
        $this->assertNotEmpty($result);

        // Test retrieving trees
        $trees = $this->gedcom_handler->getGedcomTrees();
        $this->assertIsArray($trees);
        $this->assertNotEmpty($trees);
    }

    public function testDatabaseTransactions() {
        global $wpdb;

        $wpdb->expects($this->exactly(2))
             ->method('query')
             ->withConsecutive(
                 [$this->equalTo('START TRANSACTION')],
                 [$this->equalTo('COMMIT')]
             )
             ->willReturn(true);

        // Test transaction handling
        $result = $this->gedcom_handler->storeGedcomData(
            ['individuals' => []]
        );
        $this->assertTrue($result);
    }
}
