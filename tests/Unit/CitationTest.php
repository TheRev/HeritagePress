<?php
namespace HeritagePress\Tests;

use HeritagePress\Models\Citation;
use HeritagePress\Models\Source;
use HeritagePress\Models\Individual;

class CitationTest extends HeritageTestCase
{
    protected $citation;
    protected $source;
    protected $individual;

    protected function setUp(): void
    {
        parent::setUp();
        
        global $wpdb;
        
        $this->source = new Source([
            'id' => 1,
            'title' => 'Test Source',
            'type' => 'document'
        ]);

        $this->individual = new Individual([
            'id' => 1,
            'given_name' => 'John',
            'surname' => 'Doe'
        ]);

        // Mock database responses
        $wpdb->get_row = function($query) {
            if (strpos($query, 'sources') !== false) {
                return (object)[
                    'id' => 1,
                    'title' => 'Test Source',
                    'type' => 'document'
                ];
            } elseif (strpos($query, 'individuals') !== false) {
                return (object)[
                    'id' => 1,
                    'given_name' => 'John',
                    'surname' => 'Doe'
                ];
            }
            return null;
        };

        $this->citation = new Citation([
            'source_id' => 1,
            'individual_id' => 1,
            'page' => '42',
            'quality_assessment' => 'primary',
            'citation_text' => 'Birth record, page 42',
            'confidence_score' => 3
        ]);
    }

    public function testCitationValidation()
    {
        $this->assertTrue($this->citation->isValid());
        
        $invalidCitation = new Citation([]);
        $this->assertFalse($invalidCitation->isValid());
        
        $errors = $invalidCitation->getErrors();
        $this->assertArrayHasKey('source_id', $errors);
    }

    public function testSourceRelationship()
    {
        $source = $this->citation->source();
        $this->assertInstanceOf(Source::class, $source);
        $this->assertEquals('Test Source', $source->title);
    }

    public function testIndividualRelationship()
    {
        $individual = $this->citation->individual();
        $this->assertInstanceOf(Individual::class, $individual);
        $this->assertEquals('John', $individual->given_name);
    }

    public function testConfidenceValidation()
    {
        $citation = new Citation([
            'source_id' => 1,
            'individual_id' => 1,
            'confidence_score' => 5 // Invalid score
        ]);
        
        $this->assertFalse($citation->isValid());
        $errors = $citation->getErrors();
        $this->assertArrayHasKey('confidence_score', $errors);
    }

    public function testQualityAssessmentValidation()
    {
        $citation = new Citation([
            'source_id' => 1,
            'individual_id' => 1,
            'quality_assessment' => 'invalid' // Invalid assessment
        ]);
        
        $this->assertFalse($citation->isValid());
        $errors = $citation->getErrors();
        $this->assertArrayHasKey('quality_assessment', $errors);
    }
}
