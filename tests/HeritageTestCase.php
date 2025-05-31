<?php
namespace HeritagePress\Tests;

use PHPUnit\Framework\TestCase;
use HeritagePress\Tests\Mocks\MockWPDB;

/**
 * Base test case class for Heritage Press tests
 */
class HeritageTestCase extends TestCase {
    protected static $wpdb;
    protected static $tables = [];    protected function setUp(): void {
        parent::setUp();
        
        if (!self::$wpdb) {
            self::$wpdb = new MockWPDB();
            global $wpdb;
            $wpdb = self::$wpdb;
        }
    }

    protected function tearDown(): void {
        parent::tearDown();
    }

    /**
     * Truncate specified tables
     */
    protected function truncateTables(array $tables) {
        foreach ($tables as $table) {
            self::$wpdb->data[$table] = [];
        }
    }

    /**
     * Register a table for the test environment
     */
    protected static function registerTable($name, $columns = []) {
        self::$tables[] = $name;
        self::$wpdb->addTable($name, $columns);
    }    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        
        // Initialize database mock
        self::$wpdb = new MockWPDB();
        global $wpdb;
        $wpdb = self::$wpdb;
    }
}
