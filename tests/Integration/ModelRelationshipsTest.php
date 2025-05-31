<?php
namespace HeritagePress\Tests\Integration;

use HeritagePress\Tests\HeritageTestCase;
use HeritagePress\Tests\Fixtures\DatabaseFixtures;
use HeritagePress\Models\Individual;
use HeritagePress\Models\Family;
use HeritagePress\Models\Event;
use HeritagePress\Models\Place;
use HeritagePress\Models\Source;
use HeritagePress\Models\Citation;

class ModelRelationshipsTest extends HeritageTestCase {
    protected $fixtures;

    public function setUp(): void {
        parent::setUp();
        $this->fixtures = new DatabaseFixtures();
        $this->fixtures->create();
    }

    public function tearDown(): void {
        $this->fixtures->cleanup();
        parent::tearDown();
    }

    public function test_individual_relationships() {
        $individual = Individual::find(1); // John Smith
        
        // Test birth place relationship
        $birth_place = $individual->getBirthPlace();
        $this->assertInstanceOf(Place::class, $birth_place);
        $this->assertEquals('London, England', $birth_place->name);

        // Test family relationships
        $family = $individual->getFamilyAsSpouse();
        $this->assertInstanceOf(Family::class, $family);
        $this->assertEquals(1, $family->id);

        // Test events
        $events = $individual->getEvents();
        $this->assertNotEmpty($events);
        $this->assertInstanceOf(Event::class, $events[0]);
        $this->assertEquals('BIRTH', $events[0]->type);

        // Test citations
        $citations = $individual->getCitations();
        $this->assertNotEmpty($citations);
        $this->assertInstanceOf(Citation::class, $citations[0]);
        $this->assertEquals('Birth certificate details', $citations[0]->citation_text);
    }

    public function test_family_relationships() {
        $family = Family::find(1);

        // Test spouse relationships
        $husband = $family->getHusband();
        $this->assertInstanceOf(Individual::class, $husband);
        $this->assertEquals('John', $husband->given_names);

        $wife = $family->getWife();
        $this->assertInstanceOf(Individual::class, $wife);
        $this->assertEquals('Mary', $wife->given_names);

        // Test children
        $children = $family->getChildren();
        $this->assertNotEmpty($children);
        $this->assertInstanceOf(Individual::class, $children[0]);
        $this->assertEquals('William', $children[0]->given_names);

        // Test events
        $events = $family->getEvents();
        $this->assertNotEmpty($events);
        $this->assertInstanceOf(Event::class, $events[0]);
        $this->assertEquals('MARRIAGE', $events[0]->type);
    }

    public function test_event_relationships() {
        $event = Event::find(1);

        // Test place relationship
        $place = $event->getPlace();
        $this->assertInstanceOf(Place::class, $place);
        $this->assertEquals('London, England', $place->name);

        // Test individual relationship
        $individual = $event->getIndividual();
        $this->assertInstanceOf(Individual::class, $individual);
        $this->assertEquals('John', $individual->given_names);

        // Test citations
        $citations = $event->getCitations();
        $this->assertNotEmpty($citations);
        $this->assertInstanceOf(Citation::class, $citations[0]);
    }

    public function test_place_relationships() {
        $place = Place::find(1);

        // Test events at this place
        $events = $place->getEvents();
        $this->assertNotEmpty($events);
        $this->assertInstanceOf(Event::class, $events[0]);

        // Test individuals born here
        $births = $place->getBirths();
        $this->assertNotEmpty($births);
        $this->assertInstanceOf(Individual::class, $births[0]);
        $this->assertEquals('John', $births[0]->given_names);
    }

    public function test_source_relationships() {
        $source = Source::find(1);

        // Test citations
        $citations = $source->getCitations();
        $this->assertNotEmpty($citations);
        $this->assertInstanceOf(Citation::class, $citations[0]);

        // Test individuals through citations
        $individuals = $source->getIndividuals();
        $this->assertNotEmpty($individuals);
        $this->assertInstanceOf(Individual::class, $individuals[0]);
    }
}
