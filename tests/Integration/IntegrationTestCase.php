<?php
namespace HeritagePress\Tests;

class IntegrationTestCase extends UnitTestCase {
    protected $wpdb;

    protected function setUp(): void {
        parent::setUp();
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->setUpDatabase();
    }

    protected function setUpDatabase(): void {
        // Setup test database
        $this->wpdb->query('START TRANSACTION');
    }

    protected function tearDown(): void {
        // Rollback test database changes
        $this->wpdb->query('ROLLBACK');
        parent::tearDown();
    }
}
