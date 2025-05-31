<?php
namespace HeritagePress\Tests;

use HeritagePress\Models\Individual;
use HeritagePress\Models\Family;
use HeritagePress\Models\Event;
use HeritagePress\Models\Place;
use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase {
    protected $individual;
    protected $family;
    protected $event;
    protected $place;

    protected function setUp(): void {
        global $wpdb;
        $wpdb = $this->createMock(\wpdb::class);
        $wpdb->prefix = 'wp_';

        $this->individual = new Individual([
            'uuid' => 'test-uuid',
            'given_names' => 'John',
            'surname' => 'Doe',
            'birth_date' => '1900-01-01',
            'gender' => 'M'
        ]);

        $this->family = new Family([
            'uuid' => 'test-uuid',
            'husband_id' => 1,
            'wife_id' => 2,
            'marriage_date' => '1920-01-01'
        ]);

        $this->event = new Event([
            'uuid' => 'test-uuid',
            'individual_id' => 1,
            'type' => 'BIRTH',
            'date' => '1900-01-01'
        ]);

        $this->place = new Place([
            'uuid' => 'test-uuid',
            'name' => 'London',
            'latitude' => 51.5074,
            'longitude' => -0.1278
        ]);
    }

    public function testIndividualModel() {
        $this->assertEquals('John', $this->individual->given_names);
        $this->assertEquals('Doe', $this->individual->surname);
        $this->assertEquals('1900-01-01', $this->individual->birth_date);
        $this->assertEquals('M', $this->individual->gender);
    }

    public function testFamilyModel() {
        $this->assertEquals(1, $this->family->husband_id);
        $this->assertEquals(2, $this->family->wife_id);
        $this->assertEquals('1920-01-01', $this->family->marriage_date);
    }

    public function testEventModel() {
        $this->assertEquals(1, $this->event->individual_id);
        $this->assertEquals('BIRTH', $this->event->type);
        $this->assertEquals('1900-01-01', $this->event->date);
    }

    public function testPlaceModel() {
        $this->assertEquals('London', $this->place->name);
        $this->assertEquals(51.5074, $this->place->latitude);
        $this->assertEquals(-0.1278, $this->place->longitude);
    }

    public function testModelDataAccess() {
        $data = $this->individual->toArray();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('given_names', $data);
        $this->assertArrayHasKey('surname', $data);
    }

    public function testInvalidPropertyAccess() {
        $this->assertNull($this->individual->nonexistent_property);
    }
}
