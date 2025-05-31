<?php
namespace HeritagePress\Tests;

use HeritagePress\Models\Source;
use HeritagePress\Models\Repository;
use HeritagePress\Services\SourceQualityService;
use PHPUnit\Framework\TestCase;

class SourceTest extends TestCase
{
    protected $source;
    protected $repository;    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = new Repository([
            'id' => 1,
            'name' => 'Test Repository',
            'type' => 'archive',
            'website' => 'http://example.com'
        ]);

        $this->source = new Source([
            'title' => 'Birth Certificate',
            'type' => 'birth_record',
            'author' => 'State Registry',
            'date' => '2023-01-01',
            'repository_id' => 1
        ]);
    }

    public function testSourceValidation()
    {
        $this->assertTrue($this->source->isValid());
        
        $invalidSource = new Source([]);
        $this->assertFalse($invalidSource->isValid());
    }

    public function testQualityAssessment()
    {
        $quality = $this->source->assessQuality();
        
        $this->assertArrayHasKey('percentage', $quality);
        $this->assertArrayHasKey('recommendations', $quality);
        
        // Birth records should have high quality scores
        $this->assertGreaterThan(80, $quality['percentage']);
    }    public function testSourceRepository()
    {
        global $wpdb;
        
        // Create a repository in the mock database
        $wpdb->get_row = function($query) {
            if (strpos($query, 'repositories') !== false) {
                return (object)[
                    'id' => 1,
                    'uuid' => 'test-uuid',
                    'file_id' => 1,
                    'name' => 'Test Repository',
                    'type' => 'archive',
                    'website' => 'http://example.com',
                    'status' => 'active'
                ];
            }
            return null;
        };
        
        $repo = $this->source->repository();
        $this->assertInstanceOf(Repository::class, $repo);
        $this->assertEquals('Test Repository', $repo->name);
    }

    public function testSourceComparison()
    {
        $otherSource = new Source([
            'title' => 'Family Bible Entry',
            'type' => 'family_bible',
            'author' => 'Smith Family',
            'date' => '2023-01-01'
        ]);

        $analysis = $this->source->compareWithOtherSources('birth_date', [$otherSource]);
        
        $this->assertArrayHasKey('conflicts', $analysis);
        $this->assertArrayHasKey('resolved', $analysis);
        $this->assertArrayHasKey('recommendations', $analysis);
    }
}
