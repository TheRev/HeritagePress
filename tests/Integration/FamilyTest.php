<?php
namespace HeritagePress\Tests;

use HeritagePress\Models\Family;
use HeritagePress\Models\Individual;
use HeritagePress\Models\Place;
use HeritagePress\Models\Event;

class FamilyTest extends HeritageTestCase {
    protected $wpdb;
    protected $family;

    public function setUp(): void {
        parent::setUp();
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->family = new Family();
    }

    public function test_family_creation_with_valid_data() {
        $familyData = [
            'uuid' => 'fam-123',
            'husband_id' => 1,
            'wife_id' => 2,
            'marriage_date' => '1980-06-15',
            'marriage_place_id' => 1,
            'notes' => 'Test family record',
            'privacy' => false,
            'status' => 'published'
        ];

        $this->wpdb->shouldReceive('insert')
            ->once()
            ->with('wp_families', $familyData, ['%s', '%d', '%d', '%s', '%d', '%s', '%d', '%s'])
            ->andReturn(true);

        $this->wpdb->insert_id = 1;

        $family = Family::create($familyData);
        $this->assertInstanceOf(Family::class, $family);
        $this->assertEquals(1, $family->id);
        $this->assertEquals('1980-06-15', $family->marriage_date);
    }

    public function test_family_validation() {
        $familyData = [
            'uuid' => '',
            'husband_id' => 'not-a-number',
            'wife_id' => 'not-a-number',
            'marriage_date' => 'invalid-date',
            'privacy' => 'not-a-boolean'
        ];

        $family = Family::create($familyData);
        $this->assertFalse($family);
        
        $errors = Family::getErrors();
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('uuid', $errors);
        $this->assertArrayHasKey('husband_id', $errors);
        $this->assertArrayHasKey('wife_id', $errors);
        $this->assertArrayHasKey('marriage_date', $errors);
        $this->assertArrayHasKey('privacy', $errors);
    }

    public function test_family_relationships() {
        $familyData = [
            'id' => 1,
            'husband_id' => 1,
            'wife_id' => 2,
            'marriage_place_id' => 3,
            'divorce_place_id' => 4
        ];

        $this->wpdb->shouldReceive('get_row')
            ->andReturn((object)$familyData);

        $family = Family::find(1);

        // Mock husband data
        $this->wpdb->shouldReceive('get_row')
            ->with("SELECT * FROM wp_individuals WHERE id = 1")
            ->andReturn((object)['id' => 1, 'given_names' => 'John', 'surname' => 'Doe']);

        // Mock wife data
        $this->wpdb->shouldReceive('get_row')
            ->with("SELECT * FROM wp_individuals WHERE id = 2")
            ->andReturn((object)['id' => 2, 'given_names' => 'Jane', 'surname' => 'Smith']);

        // Mock place data
        $this->wpdb->shouldReceive('get_row')
            ->with("SELECT * FROM wp_places WHERE id = 3")
            ->andReturn((object)['id' => 3, 'name' => 'Marriage Place']);

        $this->wpdb->shouldReceive('get_row')
            ->with("SELECT * FROM wp_places WHERE id = 4")
            ->andReturn((object)['id' => 4, 'name' => 'Divorce Place']);

        // Mock children data
        $this->wpdb->shouldReceive('get_results')
            ->with(
                "SELECT i.* FROM wp_individuals i " .
                "JOIN wp_family_children fc ON fc.child_id = i.id " .
                "WHERE fc.family_id = 1"
            )
            ->andReturn([
                (object)['id' => 3, 'given_names' => 'Child1', 'surname' => 'Doe'],
                (object)['id' => 4, 'given_names' => 'Child2', 'surname' => 'Doe']
            ]);

        // Test relationships
        $husband = $family->getHusband();
        $this->assertInstanceOf(Individual::class, $husband);
        $this->assertEquals(1, $husband->id);
        $this->assertEquals('John', $husband->given_names);

        $wife = $family->getWife();
        $this->assertInstanceOf(Individual::class, $wife);
        $this->assertEquals(2, $wife->id);
        $this->assertEquals('Jane', $wife->given_names);

        $marriagePlace = $family->marriagePlace();
        $this->assertInstanceOf(Place::class, $marriagePlace);
        $this->assertEquals(3, $marriagePlace->id);

        $divorcePlace = $family->divorcePlace();
        $this->assertInstanceOf(Place::class, $divorcePlace);
        $this->assertEquals(4, $divorcePlace->id);

        $children = $family->getChildren();
        $this->assertIsArray($children);
        $this->assertCount(2, $children);
        $this->assertEquals('Child1', $children[0]->given_names);
    }

    public function test_family_events() {
        $familyData = ['id' => 1];

        $this->wpdb->shouldReceive('get_row')
            ->andReturn((object)$familyData);

        $family = Family::find(1);

        // Mock events data
        $this->wpdb->shouldReceive('get_results')
            ->with("SELECT * FROM wp_events WHERE family_id = 1")
            ->andReturn([
                (object)['id' => 1, 'type' => 'MARRIAGE', 'date' => '1980-06-15'],
                (object)['id' => 2, 'type' => 'DIVORCE', 'date' => '1990-03-20']
            ]);

        $events = $family->getEvents();
        $this->assertIsArray($events);
        $this->assertCount(2, $events);
        $this->assertEquals('MARRIAGE', $events[0]->type);
        $this->assertEquals('DIVORCE', $events[1]->type);
    }

    public function test_family_update() {
        $familyData = [
            'id' => 1,
            'marriage_date' => '1980-06-15',
            'notes' => 'Original notes'
        ];

        $this->wpdb->shouldReceive('get_row')
            ->andReturn((object)$familyData);

        $family = Family::find(1);
        
        $this->wpdb->shouldReceive('update')
            ->once()
            ->with('wp_families', 
                ['marriage_date' => '1980-07-15', 'notes' => 'Updated notes'],
                ['id' => 1],
                ['%s', '%s'],
                ['%d']
            )
            ->andReturn(true);

        $success = $family->update([
            'marriage_date' => '1980-07-15',
            'notes' => 'Updated notes'
        ]);

        $this->assertTrue($success);
        $this->assertEquals('1980-07-15', $family->marriage_date);
        $this->assertEquals('Updated notes', $family->notes);
    }

    public function test_family_deletion() {
        $familyData = ['id' => 1];

        $this->wpdb->shouldReceive('get_row')
            ->andReturn((object)$familyData);

        $family = Family::find(1);

        // Should delete related records first
        $this->wpdb->shouldReceive('delete')
            ->with('wp_family_children', ['family_id' => 1], ['%d'])
            ->andReturn(true);

        $this->wpdb->shouldReceive('delete')
            ->with('wp_families', ['id' => 1], ['%d'])
            ->andReturn(true);

        $success = $family->delete();
        $this->assertTrue($success);
    }
}
