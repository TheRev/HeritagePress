<?php

use HeritagePress\Models\Individual;
use HeritagePress\Models\Family;
use HeritagePress\Models\Place;
use HeritagePress\Models\Event;

class IndividualTest extends WP_UnitTestCase {
    protected $wpdb;
    protected $individual;

    public function setUp(): void {
        parent::setUp();
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->individual = new Individual();
    }

    public function test_individual_creation_with_valid_data() {
        $individualData = [
            'uuid' => 'ind-123',
            'given_names' => 'John',
            'surname' => 'Doe',
            'birth_date' => '1950-03-15',
            'birth_place_id' => 1,
            'gender' => 'M',
            'notes' => 'Test individual record',
            'privacy' => false,
            'status' => 'published'
        ];

        $this->wpdb->shouldReceive('insert')
            ->once()
            ->with('wp_individuals', $individualData, ['%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s'])
            ->andReturn(true);

        $this->wpdb->insert_id = 1;

        $individual = Individual::create($individualData);
        $this->assertInstanceOf(Individual::class, $individual);
        $this->assertEquals(1, $individual->id);
        $this->assertEquals('John', $individual->given_names);
        $this->assertEquals('Doe', $individual->surname);
    }

    public function test_individual_validation() {
        $individualData = [
            'uuid' => '',
            'given_names' => '',
            'surname' => '',
            'birth_date' => 'invalid-date',
            'gender' => 'X',
            'privacy' => 'not-a-boolean'
        ];

        $individual = Individual::create($individualData);
        $this->assertFalse($individual);
        
        $errors = Individual::getErrors();
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('uuid', $errors);
        $this->assertArrayHasKey('given_names', $errors);
        $this->assertArrayHasKey('birth_date', $errors);
        $this->assertArrayHasKey('gender', $errors);
        $this->assertArrayHasKey('privacy', $errors);
    }

    public function test_individual_places() {
        $individualData = [
            'id' => 1,
            'birth_place_id' => 1,
            'death_place_id' => 2
        ];

        $this->wpdb->shouldReceive('get_row')
            ->andReturn((object)$individualData);

        $individual = Individual::find(1);

        // Mock place data
        $this->wpdb->shouldReceive('get_row')
            ->with("SELECT * FROM wp_places WHERE id = 1")
            ->andReturn((object)['id' => 1, 'name' => 'Birth Place']);

        $this->wpdb->shouldReceive('get_row')
            ->with("SELECT * FROM wp_places WHERE id = 2")
            ->andReturn((object)['id' => 2, 'name' => 'Death Place']);

        $birthPlace = $individual->birthPlace();
        $this->assertInstanceOf(Place::class, $birthPlace);
        $this->assertEquals(1, $birthPlace->id);
        $this->assertEquals('Birth Place', $birthPlace->name);

        $deathPlace = $individual->deathPlace();
        $this->assertInstanceOf(Place::class, $deathPlace);
        $this->assertEquals(2, $deathPlace->id);
        $this->assertEquals('Death Place', $deathPlace->name);
    }

    public function test_individual_events() {
        $individualData = ['id' => 1];

        $this->wpdb->shouldReceive('get_row')
            ->andReturn((object)$individualData);

        $individual = Individual::find(1);

        // Mock events data
        $this->wpdb->shouldReceive('get_results')
            ->with("SELECT * FROM wp_events WHERE individual_id = 1")
            ->andReturn([
                (object)['id' => 1, 'type' => 'BIRTH', 'date' => '1950-03-15'],
                (object)['id' => 2, 'type' => 'GRADUATION', 'date' => '1968-06-01'],
                (object)['id' => 3, 'type' => 'DEATH', 'date' => '2020-12-01']
            ]);

        $events = $individual->events()->get();
        $this->assertIsArray($events);
        $this->assertCount(3, $events);
        $this->assertEquals('BIRTH', $events[0]->type);
        $this->assertEquals('GRADUATION', $events[1]->type);
        $this->assertEquals('DEATH', $events[2]->type);
    }

    public function test_individual_family_relationships() {
        $individualData = ['id' => 1, 'given_names' => 'John', 'surname' => 'Doe'];

        $this->wpdb->shouldReceive('get_row')
            ->andReturn((object)$individualData);

        $individual = Individual::find(1);

        // Mock families as husband
        $this->wpdb->shouldReceive('get_results')
            ->with("SELECT * FROM wp_families WHERE husband_id = 1")
            ->andReturn([
                (object)['id' => 1, 'wife_id' => 2, 'marriage_date' => '1975-06-15']
            ]);

        // Mock families as wife
        $this->wpdb->shouldReceive('get_results')
            ->with("SELECT * FROM wp_families WHERE wife_id = 1")
            ->andReturn([]);

        // Mock parent families
        $this->wpdb->shouldReceive('get_results')
            ->with(
                "SELECT f.* FROM wp_families f " .
                "JOIN wp_family_children fc ON fc.family_id = f.id " .
                "WHERE fc.child_id = 1"
            )
            ->andReturn([
                (object)['id' => 2, 'husband_id' => 3, 'wife_id' => 4]
            ]);

        // Test relationships
        $familiesAsHusband = $individual->familiesAsHusband()->get();
        $this->assertIsArray($familiesAsHusband);
        $this->assertCount(1, $familiesAsHusband);
        $this->assertEquals(1, $familiesAsHusband[0]->id);

        $familiesAsWife = $individual->familiesAsWife()->get();
        $this->assertIsArray($familiesAsWife);
        $this->assertEmpty($familiesAsWife);

        $allFamilies = $individual->getFamilies();
        $this->assertIsArray($allFamilies);
        $this->assertCount(1, $allFamilies);

        $parentFamilies = $individual->getParentFamilies();
        $this->assertIsArray($parentFamilies);
        $this->assertCount(1, $parentFamilies);
        $this->assertEquals(2, $parentFamilies[0]->id);
    }

    public function test_get_children() {
        $individualData = ['id' => 1];

        $this->wpdb->shouldReceive('get_row')
            ->andReturn((object)$individualData);

        $individual = Individual::find(1);

        // Mock families
        $this->wpdb->shouldReceive('get_results')
            ->with("SELECT * FROM wp_families WHERE husband_id = 1")
            ->andReturn([
                (object)['id' => 1, 'wife_id' => 2]
            ]);

        $this->wpdb->shouldReceive('get_results')
            ->with("SELECT * FROM wp_families WHERE wife_id = 1")
            ->andReturn([]);

        // Mock children for the family
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

        $children = $individual->getChildren();
        $this->assertIsArray($children);
        $this->assertCount(2, $children);
        $this->assertEquals('Child1', $children[0]->given_names);
        $this->assertEquals('Child2', $children[1]->given_names);
    }

    public function test_get_siblings() {
        $individualData = ['id' => 1];

        $this->wpdb->shouldReceive('get_row')
            ->andReturn((object)$individualData);

        $individual = Individual::find(1);

        // Mock parent families
        $this->wpdb->shouldReceive('get_results')
            ->with(
                "SELECT f.* FROM wp_families f " .
                "JOIN wp_family_children fc ON fc.family_id = f.id " .
                "WHERE fc.child_id = 1"
            )
            ->andReturn([
                (object)['id' => 1, 'husband_id' => 2, 'wife_id' => 3]
            ]);

        // Mock siblings
        $this->wpdb->shouldReceive('get_results')
            ->with(
                "SELECT i.* FROM wp_individuals i " .
                "JOIN wp_family_children fc ON fc.child_id = i.id " .
                "WHERE fc.family_id = 1"
            )
            ->andReturn([
                (object)['id' => 1, 'given_names' => 'Self', 'surname' => 'Doe'],
                (object)['id' => 4, 'given_names' => 'Sibling1', 'surname' => 'Doe'],
                (object)['id' => 5, 'given_names' => 'Sibling2', 'surname' => 'Doe']
            ]);

        $siblings = $individual->getSiblings();
        $this->assertIsArray($siblings);
        $this->assertCount(2, $siblings);
        $this->assertEquals('Sibling1', $siblings[0]->given_names);
        $this->assertEquals('Sibling2', $siblings[1]->given_names);
    }

    public function test_individual_update() {
        $individualData = [
            'id' => 1,
            'given_names' => 'John',
            'surname' => 'Doe'
        ];

        $this->wpdb->shouldReceive('get_row')
            ->andReturn((object)$individualData);

        $individual = Individual::find(1);
        
        $this->wpdb->shouldReceive('update')
            ->once()
            ->with('wp_individuals', 
                ['given_names' => 'Jonathan', 'surname' => 'Doe-Smith'],
                ['id' => 1],
                ['%s', '%s'],
                ['%d']
            )
            ->andReturn(true);

        $success = $individual->update([
            'given_names' => 'Jonathan',
            'surname' => 'Doe-Smith'
        ]);

        $this->assertTrue($success);
        $this->assertEquals('Jonathan', $individual->given_names);
        $this->assertEquals('Doe-Smith', $individual->surname);
    }

    public function test_individual_deletion() {
        $individualData = ['id' => 1];

        $this->wpdb->shouldReceive('get_row')
            ->andReturn((object)$individualData);

        $individual = Individual::find(1);

        // Should delete related records first
        $this->wpdb->shouldReceive('delete')
            ->with('wp_family_children', ['child_id' => 1], ['%d'])
            ->andReturn(true);

        $this->wpdb->shouldReceive('delete')
            ->with('wp_events', ['individual_id' => 1], ['%d'])
            ->andReturn(true);

        $this->wpdb->shouldReceive('delete')
            ->with('wp_individuals', ['id' => 1], ['%d'])
            ->andReturn(true);

        $success = $individual->delete();
        $this->assertTrue($success);
    }
}
