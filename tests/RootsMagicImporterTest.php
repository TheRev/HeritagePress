<?php
namespace HeritagePress\Tests;

use HeritagePress\Importers\RootsMagicImporter;
use HeritagePress\Importers\RootsMagicDatabase;

class RootsMagicImporterTest extends HeritageTestCase {
    protected $importer;
    protected $test_files_dir;
    protected $rm9_file;
    protected $rm10_file;    protected function setUp(): void {
        parent::setUp();
        $this->importer = new RootsMagicImporter();
        $this->test_files_dir = dirname(__FILE__) . '/test-data/rootsmagic/';
        $this->rm9_file = 'C:/Users/Joe/Documents/jeanstuff/John church_AutoBackup.rmtree';
        $this->rm10_file = 'C:/Users/Joe/Documents/coxfamilytree.rmtree';

        // Create test directory if it doesn't exist
        if (!file_exists($this->test_files_dir)) {
            mkdir($this->test_files_dir, 0777, true);
        }

        // Ensure test files exist
        if (!file_exists($this->rm9_file)) {
            $this->markTestSkipped('RootsMagic 9 test file not found');
        }
        if (!file_exists($this->rm10_file)) {
            $this->markTestSkipped('RootsMagic 10 test file not found');
        }
    }    public function test_format_name() {
        $this->assertEquals('RootsMagic', $this->importer->get_format_name());
    }

    public function test_supported_extensions() {
        $extensions = $this->importer->get_supported_extensions();
        $this->assertContains('rmtree', $extensions);
    }

    public function test_can_import_invalid_file() {
        $invalid_file = $this->test_files_dir . 'invalid.rmtree';
        file_put_contents($invalid_file, 'Invalid content');
        
        $this->assertFalse($this->importer->can_import($invalid_file));
        unlink($invalid_file);
    }

    public function test_can_import_nonexistent_file() {
        $this->assertFalse($this->importer->can_import('nonexistent.rmtree'));
    }

    public function test_can_import_rm9_file() {
        $this->assertTrue($this->importer->can_import($this->rm9_file));
    }

    public function test_can_import_rm10_file() {
        $this->assertTrue($this->importer->can_import($this->rm10_file));
    }    public function test_import_invalid_file() {
        $invalid_file = $this->test_files_dir . 'invalid.rmtree';
        file_put_contents($invalid_file, 'Invalid content');
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot import file');
        
        $this->importer->import($invalid_file);
        unlink($invalid_file);
    }    public function test_sqlite_requirement() {
        if (!class_exists('SQLite3')) {
            $this->markTestSkipped('SQLite3 extension is not available');
        }

        // Create a minimal valid SQLite database
        $valid_file = $this->test_files_dir . 'valid.rmtree';
        $db = new \SQLite3($valid_file);
        
        // Create required tables for RootsMagic 10
        $db->exec('
            CREATE TABLE DatabaseInformation (
                Name TEXT PRIMARY KEY,
                Value TEXT
            );
            CREATE TABLE Person (
                PersonId INTEGER PRIMARY KEY,
                GivenName TEXT,
                Surname TEXT,
                Gender TEXT,
                IsPrivate INTEGER
            );
            CREATE TABLE Family (
                FamilyId INTEGER PRIMARY KEY,
                FatherId INTEGER,
                MotherId INTEGER
            );
            CREATE TABLE Event (
                EventId INTEGER PRIMARY KEY,
                EventType TEXT,
                EventDate TEXT,
                PersonId INTEGER,
                FamilyId INTEGER
            );
            INSERT INTO DatabaseInformation (Name, Value) VALUES ("Version", "10.0.0.0");
        ');

        $db->close();

        $this->assertTrue($this->importer->can_import($valid_file));
        unlink($valid_file);
    }

    public function test_version_detection_rm9() {
        $this->importer->can_import($this->rm9_file);
        $version = $this->importer->get_database()->get_version();
        $this->assertEquals(9, $version);
    }

    public function test_version_detection_rm10() {
        $this->importer->can_import($this->rm10_file);
        $version = $this->importer->get_database()->get_version();
        $this->assertEquals(10, $version);
    }

    public function test_can_read_individuals_rm9() {
        $this->importer->can_import($this->rm9_file);
        $individuals = $this->importer->get_individuals();
        $this->assertNotEmpty($individuals);
    }

    public function test_can_read_individuals_rm10() {
        $this->importer->can_import($this->rm10_file);
        $individuals = $this->importer->get_individuals();
        $this->assertNotEmpty($individuals);
    }

    public function test_can_read_families_rm9() {
        $this->importer->can_import($this->rm9_file);
        $families = $this->importer->get_families();
        $this->assertNotEmpty($families);
    }

    public function test_can_read_families_rm10() {
        $this->importer->can_import($this->rm10_file);
        $families = $this->importer->get_families();
        $this->assertNotEmpty($families);
    }

    public function test_import_rm9() {
        $stats = $this->importer->import($this->rm9_file);
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('individuals', $stats);
        $this->assertArrayHasKey('families', $stats);
        $this->assertArrayHasKey('events', $stats);
        $this->assertGreaterThan(0, $stats['individuals']);
    }

    public function test_import_rm10() {
        $stats = $this->importer->import($this->rm10_file);
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('individuals', $stats);
        $this->assertArrayHasKey('families', $stats);
        $this->assertArrayHasKey('events', $stats);
        $this->assertGreaterThan(0, $stats['individuals']);
    }

    public function test_get_individuals_rm9() {
        $this->importer->can_import($this->rm9_file);
        $individuals = $this->importer->get_individuals();
        
        $this->assertIsArray($individuals);
        $this->assertNotEmpty($individuals);
        
        $first = reset($individuals);
        $this->assertArrayHasKey('uuid', $first);
        $this->assertArrayHasKey('given_name', $first);
        $this->assertArrayHasKey('surname', $first);
    }

    public function test_get_individuals_rm10() {
        $this->importer->can_import($this->rm10_file);
        $individuals = $this->importer->get_individuals();
        
        $this->assertIsArray($individuals);
        $this->assertNotEmpty($individuals);
        
        $first = reset($individuals);
        $this->assertArrayHasKey('uuid', $first);
        $this->assertArrayHasKey('given_name', $first);
        $this->assertArrayHasKey('surname', $first);
    }

    public function test_get_events_rm9() {
        $this->importer->can_import($this->rm9_file);
        $events = $this->importer->get_events();
        
        $this->assertIsArray($events);
        $this->assertNotEmpty($events);
        
        $first = reset($events);
        $this->assertArrayHasKey('uuid', $first);
        $this->assertArrayHasKey('type', $first);
        $this->assertArrayHasKey('date', $first);
    }

    public function test_get_events_rm10() {
        $this->importer->can_import($this->rm10_file);
        $events = $this->importer->get_events();
        
        $this->assertIsArray($events);
        $this->assertNotEmpty($events);
        
        $first = reset($events);
        $this->assertArrayHasKey('uuid', $first);
        $this->assertArrayHasKey('type', $first);
        $this->assertArrayHasKey('date', $first);
    }
}
