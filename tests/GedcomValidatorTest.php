<?php
namespace HeritagePress\Tests;

use PHPUnit\Framework\TestCase;
use HeritagePress\GEDCOM\Gedcom7Validator;

class GedcomValidatorTest extends TestCase {
    private $validator;

    protected function setUp(): void {
        $this->validator = new Gedcom7Validator();
    }

    public function testValidHeader() {
        $header = [
            'data' => [
                ['tag' => 'GEDC', 'value' => ''],
                ['tag' => 'CHAR', 'value' => 'UTF-8']
            ]
        ];
        
        $this->validator->validate(['header' => $header]);
        $this->assertEmpty($this->validator->getErrors());
    }

    public function testInvalidHeader() {
        $header = [
            'data' => [
                ['tag' => 'CHAR', 'value' => 'UTF-8']
            ]
        ];
        
        $this->validator->validate(['header' => $header]);
        $this->assertNotEmpty($this->validator->getErrors());
    }

    public function testValidIndividual() {
        $individual = [
            'id' => '@I1@',
            'data' => [
                [
                    'tag' => 'NAME',
                    'value' => 'John /Doe/',
                    'children' => [
                        ['tag' => 'GIVN', 'value' => 'John'],
                        ['tag' => 'SURN', 'value' => 'Doe']
                    ]
                ],
                ['tag' => 'SEX', 'value' => 'M'],
                [
                    'tag' => 'BIRT',
                    'value' => '',
                    'children' => [
                        ['tag' => 'DATE', 'value' => '1 JAN 2000'],
                        ['tag' => 'PLAC', 'value' => 'New York, NY, USA']
                    ]
                ]
            ]
        ];
        
        $this->validator->validate(['individuals' => [$individual]]);
        $this->assertEmpty($this->validator->getErrors());
    }

    public function testInvalidIndividual() {
        $individual = [
            'id' => '@I1@',
            'data' => [
                ['tag' => 'SEX', 'value' => 'INVALID'],
                [
                    'tag' => 'BIRT',
                    'value' => '',
                    'children' => [
                        ['tag' => 'DATE', 'value' => 'INVALID DATE'],
                        ['tag' => 'PLAC', 'value' => '']
                    ]
                ]
            ]
        ];
        
        $this->validator->validate(['individuals' => [$individual]]);
        $this->assertNotEmpty($this->validator->getErrors());
        $this->assertNotEmpty($this->validator->getWarnings());
    }

    public function testValidFamily() {
        $family = [
            'id' => '@F1@',
            'data' => [
                ['tag' => 'HUSB', 'value' => '@I1@'],
                ['tag' => 'WIFE', 'value' => '@I2@'],
                [
                    'tag' => 'MARR',
                    'value' => '',
                    'children' => [
                        ['tag' => 'DATE', 'value' => '1 JAN 2000'],
                        ['tag' => 'PLAC', 'value' => 'New York, NY, USA']
                    ]
                ]
            ]
        ];
        
        $this->validator->validate(['families' => [$family]]);
        $this->assertEmpty($this->validator->getErrors());
    }

    public function testInvalidFamily() {
        $family = [
            'id' => '@F1@',
            'data' => [
                [
                    'tag' => 'MARR',
                    'value' => '',
                    'children' => [
                        ['tag' => 'DATE', 'value' => 'INVALID DATE']
                    ]
                ]
            ]
        ];
        
        $this->validator->validate(['families' => [$family]]);
        $this->assertNotEmpty($this->validator->getWarnings());
    }

    public function testValidPlaces() {
        $place = [
            'value' => 'New York, New York, USA',
            'children' => [
                ['tag' => 'LATI', 'value' => 'N40.7'],
                ['tag' => 'LONG', 'value' => 'W74.0']
            ]
        ];
        
        $this->validator->validate(['places' => [$place]]);
        $this->assertEmpty($this->validator->getErrors());
    }

    public function testInvalidPlaces() {
        $place = [
            'value' => '',
            'children' => [
                ['tag' => 'LATI', 'value' => '200.0'], // Invalid latitude
                ['tag' => 'LONG', 'value' => '400.0']  // Invalid longitude
            ]
        ];
        
        $this->validator->validate(['places' => [$place]]);
        $this->assertNotEmpty($this->validator->getWarnings());
    }

    public function testValidDates() {
        $validDates = [
            '1 JAN 2000',
            'BET 1 JAN 2000 AND 1 JAN 2001',
            'ABT 2000',
            'BEF 2000',
            'AFT 2000',
            'FROM 2000 TO 2001'
        ];

        foreach ($validDates as $date) {
            $event = [
                'tag' => 'BIRT',
                'value' => '',
                'children' => [
                    ['tag' => 'DATE', 'value' => $date]
                ]
            ];
            
            $individual = [
                'id' => '@I1@',
                'data' => [$event]
            ];
            
            $this->validator->validate(['individuals' => [$individual]]);
            $this->assertEmpty($this->validator->getErrors(), "Date '$date' should be valid");
        }
    }

    public function testInvalidDates() {
        $invalidDates = [
            'INVALID',
            '45 MONTH 2000',
            '1 JAN',
            '2000/01/01',
            '32 JAN 2000'
        ];

        foreach ($invalidDates as $date) {
            $event = [
                'tag' => 'BIRT',
                'value' => '',
                'children' => [
                    ['tag' => 'DATE', 'value' => $date]
                ]
            ];
            
            $individual = [
                'id' => '@I1@',
                'data' => [$event]
            ];
            
            $this->validator->validate(['individuals' => [$individual]]);
            $this->assertNotEmpty($this->validator->getWarnings(), "Date '$date' should be invalid");
            $this->validator->clearWarnings();
        }
    }

    public function testValidMedia() {
        $media = [
            'id' => '@M1@',
            'data' => [
                [
                    'tag' => 'FILE',
                    'value' => 'photo.jpg',
                    'children' => [
                        ['tag' => 'FORM', 'value' => 'jpg']
                    ]
                ]
            ]
        ];
        
        $this->validator->validate(['media' => [$media]]);
        $this->assertEmpty($this->validator->getErrors());
    }

    public function testInvalidMedia() {
        $media = [
            'id' => '@M1@',
            'data' => [
                ['tag' => 'FILE', 'value' => '']
            ]
        ];
        
        $this->validator->validate(['media' => [$media]]);
        $this->assertNotEmpty($this->validator->getErrors());
    }
}
