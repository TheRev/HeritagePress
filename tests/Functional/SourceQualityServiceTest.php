<?php
namespace HeritagePress\Tests;

use HeritagePress\Services\SourceQualityService;
use PHPUnit\Framework\TestCase;

class SourceQualityServiceTest extends TestCase
{
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SourceQualityService();
    }

    public function testSourceAssessment()
    {
        $assessment = [
            'originality' => 'primary',
            'timeframe' => 'contemporary',
            'information_type' => 'direct',
            'creator_reliability' => 'official'
        ];

        $result = $this->service->assessSource($assessment);
        
        $this->assertArrayHasKey('percentage', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertEquals(100, $result['percentage']);
        $this->assertEmpty($result['recommendations']);

        // Test lower quality source
        $lowQualityAssessment = [
            'originality' => 'derivative',
            'timeframe' => 'retrospective',
            'information_type' => 'indirect',
            'creator_reliability' => 'personal'
        ];

        $result = $this->service->assessSource($lowQualityAssessment);
        $this->assertLessThan(50, $result['percentage']);
        $this->assertNotEmpty($result['recommendations']);
    }

    public function testConflictAnalysis()
    {
        $sources = [
            [
                'source' => ['id' => 1, 'type' => 'birth_record'],
                'fact' => 'birth_date',
                'assessment' => [
                    'originality' => 'primary',
                    'timeframe' => 'contemporary',
                    'information_type' => 'direct',
                    'creator_reliability' => 'official'
                ]
            ],
            [
                'source' => ['id' => 2, 'type' => 'family_bible'],
                'fact' => 'birth_date',
                'assessment' => [
                    'originality' => 'primary',
                    'timeframe' => 'near_contemporary',
                    'information_type' => 'direct',
                    'creator_reliability' => 'personal'
                ]
            ]
        ];

        $result = $this->service->analyzeConflicts($sources);
        
        $this->assertArrayHasKey('conflicts', $result);
        $this->assertArrayHasKey('resolved', $result);
        $this->assertArrayHasKey('recommendations', $result);

        // Birth record should be considered the best source
        $this->assertEquals(1, $result['resolved']['birth_date']['best_source']['source']['id']);
    }

    public function testSourceAgreement()
    {
        $source1 = [
            'source' => ['id' => 1],
            'fact' => 'birth_date',
            'value' => '1900-01-01'
        ];

        $source2 = [
            'source' => ['id' => 2],
            'fact' => 'birth_date',
            'value' => '1900-01-01'
        ];

        // Test the private sourcesAgree method through its use in analyzeConflicts
        $result = $this->service->analyzeConflicts([$source1, $source2]);
        $this->assertArrayHasKey('conflicts', $result);
        $this->assertEmpty($result['recommendations']);
    }
}
