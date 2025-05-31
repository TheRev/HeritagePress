<?php
namespace HeritagePress\Tests;

use HeritagePress\Models\Event;
use HeritagePress\Models\Individual;
use HeritagePress\Models\Family;
use HeritagePress\Models\Place;

class EventTest extends HeritageTestCase {
    protected $wpdb;
    protected $event;

    public function setUp(): void {
        parent::setUp();
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->event = new Event();
    }

    public function test_event_creation_with_valid_data() {
        $eventData = [
            'uuid' => 'test-123',
            'type' => 'BIRTH',
            'date' => '1990-01-01',
            'individual_id' => 1,
            'place_id' => 1,
            'description' => 'Test birth event',
            'privacy' => false,
            'status' => 'published'
        ];

        $this->wpdb->shouldReceive('insert')
            ->once()
            ->with('wp_events', $eventData, ['%s', '%s', '%s', '%d', '%d', '%s', '%d', '%s'])
            ->andReturn(true);

        $this->wpdb->insert_id = 1;

        $event = Event::create($eventData);
        $this->assertInstanceOf(Event::class, $event);
        $this->assertEquals(1, $event->id);
        $this->assertEquals('BIRTH', $event->type);
    }

    public function test_event_validation() {
        $eventData = [
            'uuid' => '',
            'type' => 'INVALID_TYPE',
            'date' => 'not-a-date',
            'individual_id' => 'not-a-number',
            'privacy' => 'not-a-boolean'
        ];

        $event = Event::create($eventData);
        $this->assertFalse($event);
        
        $errors = Event::getErrors();
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('uuid', $errors);
        $this->assertArrayHasKey('type', $errors);
        $this->assertArrayHasKey('date', $errors);
        $this->assertArrayHasKey('individual_id', $errors);
        $this->assertArrayHasKey('privacy', $errors);
    }

    public function test_event_relationships() {
        $eventData = [
            'id' => 1,
            'individual_id' => 1,
            'family_id' => 2,
            'place_id' => 3,
            'type' => 'MARRIAGE'
        ];

        $this->wpdb->shouldReceive('get_row')
            ->andReturn((object)$eventData);

        $event = Event::find(1);

        // Mock related records
        $this->wpdb->shouldReceive('get_row')
            ->with("SELECT * FROM wp_individuals WHERE id = 1")
            ->andReturn((object)['id' => 1, 'name' => 'John Doe']);

        $this->wpdb->shouldReceive('get_row')
            ->with("SELECT * FROM wp_families WHERE id = 2")
            ->andReturn((object)['id' => 2, 'husband_id' => 1, 'wife_id' => 2]);

        $this->wpdb->shouldReceive('get_row')
            ->with("SELECT * FROM wp_places WHERE id = 3")
            ->andReturn((object)['id' => 3, 'name' => 'Test Place']);

        $individual = $event->getIndividual();
        $this->assertInstanceOf(Individual::class, $individual);
        $this->assertEquals(1, $individual->id);

        $family = $event->getFamily();
        $this->assertInstanceOf(Family::class, $family);
        $this->assertEquals(2, $family->id);

        $place = $event->getPlace();
        $this->assertInstanceOf(Place::class, $place);
        $this->assertEquals(3, $place->id);
    }

    public function test_event_update() {
        $eventData = [
            'id' => 1,
            'type' => 'BIRTH',
            'date' => '1990-01-01'
        ];

        $this->wpdb->shouldReceive('get_row')
            ->andReturn((object)$eventData);

        $event = Event::find(1);
        
        $this->wpdb->shouldReceive('update')
            ->once()
            ->with('wp_events', 
                ['type' => 'DEATH', 'date' => '2020-01-01'],
                ['id' => 1],
                ['%s', '%s'],
                ['%d']
            )
            ->andReturn(true);

        $success = $event->update([
            'type' => 'DEATH',
            'date' => '2020-01-01'
        ]);

        $this->assertTrue($success);
        $this->assertEquals('DEATH', $event->type);
        $this->assertEquals('2020-01-01', $event->date);
    }

    public function test_event_deletion() {
        $eventData = [
            'id' => 1,
            'type' => 'BIRTH'
        ];

        $this->wpdb->shouldReceive('get_row')
            ->andReturn((object)$eventData);

        $event = Event::find(1);

        $this->wpdb->shouldReceive('delete')
            ->once()
            ->with('wp_events', ['id' => 1], ['%d'])
            ->andReturn(true);

        $success = $event->delete();
        $this->assertTrue($success);
    }

    public function test_event_privacy() {
        $eventData = [
            'id' => 1,
            'type' => 'BIRTH',
            'privacy' => true
        ];

        $this->wpdb->shouldReceive('get_row')
            ->andReturn((object)$eventData);

        $event = Event::find(1);
        $this->assertTrue($event->privacy);

        $this->wpdb->shouldReceive('update')
            ->once()
            ->with('wp_events', 
                ['privacy' => false],
                ['id' => 1],
                ['%d'],
                ['%d']
            )
            ->andReturn(true);

        $success = $event->update(['privacy' => false]);
        $this->assertTrue($success);
        $this->assertFalse($event->privacy);
    }
}
