<?php

use HeritagePress\Models\Place;

class PlaceTest extends WP_UnitTestCase {
    protected $wpdb;
    protected $place;

    public function setUp(): void {
        parent::setUp();
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->place = new Place();
    }

    public function test_place_creation_with_valid_data() {
        $placeData = [
            'uuid' => 'place-123',
            'name' => 'New York',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'parent_id' => 1, // New York State
            'notes' => 'Test place record',
            'status' => 'published'
        ];

        $this->wpdb->shouldReceive('insert')
            ->once()
            ->with('wp_places', $placeData, ['%s', '%s', '%f', '%f', '%d', '%s', '%s'])
            ->andReturn(true);

        $this->wpdb->insert_id = 1;

        $place = Place::create($placeData);
        $this->assertInstanceOf(Place::class, $place);
        $this->assertEquals(1, $place->id);
        $this->assertEquals('New York', $place->name);
        $this->assertEquals(40.7128, $place->latitude);
        $this->assertEquals(-74.0060, $place->longitude);
    }

    public function test_place_validation() {
        $placeData = [
            'uuid' => '',
            'name' => '',
            'latitude' => 'not-a-number',
            'longitude' => 'not-a-number',
            'parent_id' => 'not-a-number'
        ];

        $place = Place::create($placeData);
        $this->assertFalse($place);
        
        $errors = Place::getErrors();
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('uuid', $errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('latitude', $errors);
        $this->assertArrayHasKey('longitude', $errors);
        $this->assertArrayHasKey('parent_id', $errors);
    }

    public function test_place_hierarchy() {
        // Create a hierarchical structure: USA -> New York -> New York City
        $usaData = ['id' => 1, 'name' => 'USA', 'parent_id' => null];
        $nyStateData = ['id' => 2, 'name' => 'New York', 'parent_id' => 1];
        $nyCityData = ['id' => 3, 'name' => 'New York City', 'parent_id' => 2];

        // Mock place lookups
        $this->wpdb->shouldReceive('get_row')
            ->with("SELECT * FROM wp_places WHERE id = 3")
            ->andReturn((object)$nyCityData);

        $this->wpdb->shouldReceive('get_row')
            ->with("SELECT * FROM wp_places WHERE id = 2")
            ->andReturn((object)$nyStateData);

        $this->wpdb->shouldReceive('get_row')
            ->with("SELECT * FROM wp_places WHERE id = 1")
            ->andReturn((object)$usaData);

        $nyCity = Place::find(3);
        
        // Test parent relationships
        $nyState = $nyCity->getParent();
        $this->assertInstanceOf(Place::class, $nyState);
        $this->assertEquals('New York', $nyState->name);

        $usa = $nyState->getParent();
        $this->assertInstanceOf(Place::class, $usa);
        $this->assertEquals('USA', $usa->name);

        $this->assertNull($usa->getParent());

        // Test full name generation
        $this->assertEquals('New York City, New York, USA', $nyCity->getFullName());
    }

    public function test_place_children() {
        $parentData = ['id' => 1, 'name' => 'New York'];

        $this->wpdb->shouldReceive('get_row')
            ->andReturn((object)$parentData);

        $place = Place::find(1);        // Mock child places
        $this->wpdb->shouldReceive('get_results')
            ->with("SELECT * FROM heritage_press_places WHERE parent_id = 1")
            ->andReturn([
                ['id' => 2, 'name' => 'Manhattan', 'parent_id' => 1],
                ['id' => 3, 'name' => 'Brooklyn', 'parent_id' => 1],
                ['id' => 4, 'name' => 'Queens', 'parent_id' => 1]
            ]);

        $children = $place->getChildren();
        $this->assertIsArray($children);
        $this->assertCount(3, $children);
        $this->assertEquals('Manhattan', $children[0]->name);
        $this->assertEquals('Brooklyn', $children[1]->name);
        $this->assertEquals('Queens', $children[2]->name);
    }

    public function test_place_update() {
        $placeData = [
            'id' => 1,
            'name' => 'New York City',
            'latitude' => 40.7128,
            'longitude' => -74.0060
        ];

        $this->wpdb->shouldReceive('get_row')
            ->andReturn((object)$placeData);

        $place = Place::find(1);
        
        $this->wpdb->shouldReceive('update')
            ->once()
            ->with('wp_places', 
                [
                    'name' => 'NYC',
                    'latitude' => 40.7128,
                    'longitude' => -74.0060,
                    'notes' => 'Updated place record'
                ],
                ['id' => 1],
                ['%s', '%f', '%f', '%s'],
                ['%d']
            )
            ->andReturn(true);

        $success = $place->update([
            'name' => 'NYC',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'notes' => 'Updated place record'
        ]);

        $this->assertTrue($success);
        $this->assertEquals('NYC', $place->name);
    }

    public function test_place_deletion() {
        $placeData = [
            'id' => 1,
            'name' => 'Test Place'
        ];

        $this->wpdb->shouldReceive('get_row')
            ->andReturn((object)$placeData);

        $place = Place::find(1);        // Check for child places first
        $this->wpdb->shouldReceive('get_results')
            ->with("SELECT * FROM heritage_press_places WHERE parent_id = 1")
            ->andReturn([]);

        // Then delete the place
        $this->wpdb->shouldReceive('delete')
            ->with('wp_places', ['id' => 1], ['%d'])
            ->andReturn(true);

        $success = $place->delete();
        $this->assertTrue($success);
    }

    public function test_place_with_gedcom_data() {
        // Test places from sample GEDCOM
        $places = [
            ['name' => 'New York', 'parent_id' => 2], // City
            ['name' => 'New York', 'parent_id' => 3], // State
            ['name' => 'USA', 'parent_id' => null],   // Country
            ['name' => 'Los Angeles', 'parent_id' => 4],
            ['name' => 'California', 'parent_id' => 3],
            ['name' => 'Chicago', 'parent_id' => 5],
            ['name' => 'Illinois', 'parent_id' => 3],
            ['name' => 'Boston', 'parent_id' => 6],
            ['name' => 'Massachusetts', 'parent_id' => 3]
        ];

        foreach ($places as $index => $placeData) {
            $this->wpdb->shouldReceive('insert')
                ->once()
                ->with('wp_places', 
                    array_merge($placeData, ['uuid' => "place-" . ($index + 1)]),
                    ['%s', '%s', '%d']
                )
                ->andReturn(true);

            $this->wpdb->insert_id = $index + 1;

            $place = Place::create(array_merge($placeData, [
                'uuid' => "place-" . ($index + 1)
            ]));

            $this->assertInstanceOf(Place::class, $place);
            $this->assertEquals($placeData['name'], $place->name);
        }
    }
}
