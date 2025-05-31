<?php
namespace HeritagePress\Tests;

use PHPUnit\Framework\TestCase;
use HeritagePress\GEDCOM\GedcomPlaceHandler;

class GedcomPlaceHandlerTest extends TestCase {
    private $placeHandler;

    protected function setUp(): void {
        $this->placeHandler = new GedcomPlaceHandler();
    }

    public function testHandleSimplePlace() {
        $place = [
            'value' => 'New York, NY, USA',
            'children' => []
        ];

        $result = $this->placeHandler->handlePlace($place);

        $this->assertArrayHasKey('original', $result);
        $this->assertArrayHasKey('parts', $result);
        $this->assertArrayHasKey('standardized', $result);
        $this->assertEquals('New York, NY, USA', $result['original']);
        $this->assertCount(3, $result['parts']);
    }

    public function testHandlePlaceWithCoordinates() {
        $place = [
            'value' => 'New York, NY, USA',
            'children' => [
                ['tag' => 'LATI', 'value' => 'N40.7'],
                ['tag' => 'LONG', 'value' => 'W74.0']
            ]
        ];

        $result = $this->placeHandler->handlePlace($place);

        $this->assertArrayHasKey('coordinates', $result);
        $this->assertEquals(40.7, $result['coordinates']['latitude']);
        $this->assertEquals(-74.0, $result['coordinates']['longitude']);
    }

    public function testStandardization() {
        $places = [
            'New york, ny, usa' => 'New York, New York, United States',
            'ST. LOUIS, MO, USA' => 'Saint Louis, Missouri, United States',
            'Mt. Vernon, VA' => 'Mount Vernon, Virginia',
            'Los Angeles,CA,USA' => 'Los Angeles, California, United States'
        ];

        foreach ($places as $input => $expected) {
            $place = ['value' => $input];
            $result = $this->placeHandler->handlePlace($place);
            $standardized = implode(', ', $result['standardized']);
            $this->assertEquals($expected, $standardized, "Failed standardizing: $input");
        }
    }

    public function testAbbreviationExpansion() {
        $places = [
            'Co.' => 'County',
            'Twp.' => 'Township',
            'St.' => 'Saint',
            'Ft.' => 'Fort',
            'Mt.' => 'Mount'
        ];

        foreach ($places as $abbrev => $expanded) {
            $place = ['value' => "$abbrev Test"];
            $result = $this->placeHandler->handlePlace($place);
            $standardized = $result['standardized'][0];
            $this->assertStringContains($expanded, $standardized, "Failed expanding: $abbrev");
        }
    }

    public function testCoordinateParsing() {
        $coordinates = [
            'N40.7' => 40.7,
            'S35.5' => -35.5,
            'E145.0' => 145.0,
            'W74.0' => -74.0,
            '51.5' => 51.5
        ];

        foreach ($coordinates as $input => $expected) {
            if (strpos($input, 'N') !== false || strpos($input, 'S') !== false) {
                $place = [
                    'value' => 'Test Place',
                    'children' => [['tag' => 'LATI', 'value' => $input]]
                ];
                $result = $this->placeHandler->handlePlace($place);
                $this->assertEquals($expected, $result['coordinates']['latitude']);
            } else {
                $place = [
                    'value' => 'Test Place',
                    'children' => [['tag' => 'LONG', 'value' => $input]]
                ];
                $result = $this->placeHandler->handlePlace($place);
                $this->assertEquals($expected, $result['coordinates']['longitude']);
            }
        }
    }

    public function testPlaceHierarchy() {
        $place = [
            'value' => 'Brooklyn, Kings County, New York, United States',
            'children' => []
        ];

        $result = $this->placeHandler->handlePlace($place);

        $this->assertCount(4, $result['parts']);
        $this->assertEquals('Brooklyn', $result['parts'][0]);
        $this->assertEquals('United States', $result['parts'][3]);
    }

    public function testHandleEmptyPlace() {
        $place = [
            'value' => '',
            'children' => []
        ];

        $result = $this->placeHandler->handlePlace($place);

        $this->assertEmpty($result['parts']);
        $this->assertEmpty($result['standardized']);
    }

    public function testHandlePlaceWithNotes() {
        $place = [
            'value' => 'Test Place',
            'children' => [
                ['tag' => 'NOTE', 'value' => 'Test note 1'],
                ['tag' => 'NOTE', 'value' => 'Test note 2']
            ]
        ];

        $result = $this->placeHandler->handlePlace($place);

        $this->assertArrayHasKey('notes', $result);
        $this->assertCount(2, $result['notes']);
        $this->assertEquals('Test note 1', $result['notes'][0]);
    }

    public function testPlaceValidation() {
        $validPlace = [
            'value' => 'New York, NY, USA',
            'children' => [
                ['tag' => 'LATI', 'value' => 'N40.7'],
                ['tag' => 'LONG', 'value' => 'W74.0']
            ]
        ];

        $invalidPlace = [
            'value' => '',
            'children' => [
                ['tag' => 'LATI', 'value' => 'N95.0'], // Invalid latitude
                ['tag' => 'LONG', 'value' => 'W185.0'] // Invalid longitude
            ]
        ];

        $this->assertEmpty($this->placeHandler->validatePlace($validPlace));
        $this->assertNotEmpty($this->placeHandler->validatePlace($invalidPlace));
    }
}
