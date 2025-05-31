<?php
namespace HeritagePress\Tests;

use HeritagePress\Models\Individual;
use HeritagePress\Models\Place;
use HeritagePress\Models\Event;
use HeritagePress\Tests\Mocks\MockWPDB;
// BaseTestCase is already in the same namespace

class ModelTest extends HeritageTestCase {    protected function setUp(): void {
        parent::setUp();
        
        // Initialize database mock
        self::$wpdb = new MockWPDB();
        global $wpdb;
        $wpdb = self::$wpdb;
        
        // Register tables if not done
        $this->registerTestTables();
        
        // Clear any cached data
        $this->truncateTables(['individuals', 'places', 'events']);
    }
    
    protected function registerTestTables() {
        self::registerTable('individuals', [
            'id' => 'INT',
            'uuid' => 'VARCHAR(36)',
            'file_id' => 'INT',
            'given_names' => 'VARCHAR(100)',
            'surname' => 'VARCHAR(100)',
            'birth_date' => 'DATE',
            'birth_place_id' => 'INT',
            'death_date' => 'DATE',
            'death_place_id' => 'INT',
            'gender' => 'CHAR(1)',
            'privacy' => 'TINYINT',
            'notes' => 'TEXT',
            'status' => 'VARCHAR(20)',
            'created_at' => 'DATETIME',
            'updated_at' => 'DATETIME'
        ]);
        
        self::registerTable('places', [
            'id' => 'INT',
            'uuid' => 'VARCHAR(36)',
            'file_id' => 'INT',
            'name' => 'VARCHAR(255)',
            'latitude' => 'DECIMAL(10,8)',
            'longitude' => 'DECIMAL(11,8)',
            'notes' => 'TEXT',
            'created_at' => 'DATETIME',
            'updated_at' => 'DATETIME'
        ]);
        
        self::registerTable('events', [
            'id' => 'INT',
            'uuid' => 'VARCHAR(36)',
            'file_id' => 'INT',
            'individual_id' => 'INT',
            'type' => 'VARCHAR(50)',
            'date' => 'DATE',
            'place_id' => 'INT',
            'notes' => 'TEXT',
            'created_at' => 'DATETIME',
            'updated_at' => 'DATETIME'
        ]);
    }

    public function testBasicCRUD() {
        // Create
        $individual = new Individual([
            'given_names' => 'John',
            'surname' => 'Doe',
            'gender' => 'M',
            'birth_date' => '1900-01-01'
        ]);
        
        $this->assertTrue($individual->save());
        $this->assertNotNull($individual->id);
        
        // Read
        $found = Individual::find($individual->id);
        $this->assertNotNull($found);
        $this->assertEquals('John', $found->given_names);
        $this->assertEquals('Doe', $found->surname);
        
        // Update
        $individual->given_names = 'Jane';
        $this->assertTrue($individual->save());
        
        $updated = Individual::find($individual->id);
        $this->assertEquals('Jane', $updated->given_names);
        
        // Delete
        $this->assertTrue($individual->delete());
        $this->assertNull(Individual::find($individual->id));
    }

    public function testValidation() {
        $individual = new Individual();
        $individual->gender = 'X'; // Invalid gender
        
        $this->assertFalse($individual->save());
        $errors = $individual->getErrors();
        $this->assertNotEmpty($errors);
    }

    public function testRelationships() {
        // Create a place
        $place = new Place([
            'name' => 'Test City',
            'latitude' => 0,
            'longitude' => 0
        ]);
        $place->save();
        
        // Create an individual with birth place
        $individual = new Individual([
            'given_names' => 'John',
            'surname' => 'Doe',
            'gender' => 'M',
            'birth_date' => '1900-01-01',
            'birth_place_id' => $place->id
        ]);
        $individual->save();
        
        // Test belongs to relationship
        $birthPlace = $individual->birthPlace();
        $this->assertNotNull($birthPlace);
        $this->assertEquals('Test City', $birthPlace->name);
        
        // Test has many relationship
        $event = new Event([
            'individual_id' => $individual->id,
            'type' => 'birth',
            'date' => '1900-01-01',
            'place_id' => $place->id
        ]);
        $event->save();
        
        $events = $individual->events();
        $this->assertNotEmpty($events);
        $this->assertEquals('birth', $events[0]->type);
    }

    public function testBelongsToRelationship() {
        // Create a place
        $place = new Place([
            'name' => 'Test City',
            'latitude' => 51.5074,
            'longitude' => -0.1278
        ]);
        $place->save();

        // Create an individual with birth place
        $individual = new Individual([
            'given_names' => 'John',
            'surname' => 'Doe',
            'gender' => 'M',
            'birth_place_id' => $place->id
        ]);
        $individual->save();

        // Test belongsTo relationship
        $birthPlace = $individual->birthPlace;
        $this->assertInstanceOf(Place::class, $birthPlace);
        $this->assertEquals('Test City', $birthPlace->name);
        $this->assertEquals(51.5074, $birthPlace->latitude);
    }

    public function testHasOneRelationship() {
        // Create an individual
        $individual = new Individual([
            'given_names' => 'John',
            'surname' => 'Doe',
            'gender' => 'M'
        ]);
        $individual->save();

        // Create a birth event for the individual
        $birthEvent = new Event([
            'individual_id' => $individual->id,
            'type' => 'birth',
            'date' => '1900-01-01'
        ]);
        $birthEvent->save();

        // Test hasOne relationship
        $birth = $individual->birthEvent;
        $this->assertInstanceOf(Event::class, $birth);
        $this->assertEquals('birth', $birth->type);
        $this->assertEquals('1900-01-01', $birth->date);
    }

    public function testHasManyRelationship() {
        // Create an individual
        $individual = new Individual([
            'given_names' => 'John',
            'surname' => 'Doe',
            'gender' => 'M'
        ]);
        $individual->save();

        // Create multiple events for the individual
        $events = [
            ['type' => 'birth', 'date' => '1900-01-01'],
            ['type' => 'marriage', 'date' => '1925-06-15'],
            ['type' => 'death', 'date' => '1980-12-31']
        ];

        foreach ($events as $eventData) {
            $event = new Event(array_merge(
                $eventData,
                ['individual_id' => $individual->id]
            ));
            $event->save();
        }

        // Test hasMany relationship
        $allEvents = $individual->events;
        $this->assertIsArray($allEvents);
        $this->assertCount(3, $allEvents);
        $this->assertContainsOnlyInstancesOf(Event::class, $allEvents);

        // Verify event types are present
        $eventTypes = array_map(function ($event) {
            return $event->type;
        }, $allEvents);
        $this->assertContains('birth', $eventTypes);
        $this->assertContains('marriage', $eventTypes);
        $this->assertContains('death', $eventTypes);
    }

    public function testCaching() {
        $individual = new Individual([
            'given_names' => 'John',
            'surname' => 'Doe',
            'gender' => 'M'
        ]);
        $individual->save();
        
        // First call should hit the database
        $found1 = Individual::find($individual->id);
        
        // Second call should use cache
        $found2 = Individual::find($individual->id);
        
        $this->assertEquals($found1->id, $found2->id);
        
        // After update, cache should be cleared
        $individual->given_names = 'Jane';
        $individual->save();
        
        $found3 = Individual::find($individual->id);
        $this->assertEquals('Jane', $found3->given_names);
    }

    public function testRelationshipCaching() {
        $place = new Place([
            'name' => 'Test City',
            'latitude' => 51.5074,
            'longitude' => -0.1278
        ]);
        $place->save();

        $individual = new Individual([
            'given_names' => 'John',
            'surname' => 'Doe',
            'gender' => 'M',
            'birth_place_id' => $place->id
        ]);
        $individual->save();

        // First call should hit database
        $firstCall = microtime(true);
        $birthPlace1 = $individual->birthPlace;
        $firstCallTime = microtime(true) - $firstCall;

        // Second call should use cache
        $secondCall = microtime(true);
        $birthPlace2 = $individual->birthPlace;
        $secondCallTime = microtime(true) - $secondCall;

        // Verify we got the same data
        $this->assertEquals($birthPlace1->id, $birthPlace2->id);
        
        // Second call should be faster (cached)
        $this->assertLessThan($firstCallTime, $secondCallTime);
    }
}
