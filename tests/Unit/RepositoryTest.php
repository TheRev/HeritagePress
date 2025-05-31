<?php
namespace HeritagePress\Tests;

use HeritagePress\Models\Repository;
use HeritagePress\Models\Source;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = new Repository([
            'name' => 'National Archives',
            'type' => 'archive',
            'address' => '123 Archive St',
            'website' => 'http://archives.gov',
            'contact_info' => 'info@archives.gov',
            'access_notes' => 'Public access with registration'
        ]);
    }

    public function testRepositoryValidation()
    {
        $this->assertTrue($this->repository->isValid());
        
        $invalidRepo = new Repository([]);
        $this->assertFalse($invalidRepo->isValid());
    }

    public function testAccessibility()
    {
        $this->assertTrue($this->repository->isAccessible());

        $onlineRepo = new Repository([
            'name' => 'Digital Archive',
            'type' => 'online',
            'website' => 'http://digital-archive.org'
        ]);
        $this->assertTrue($onlineRepo->isAccessible());

        $inaccessibleOnlineRepo = new Repository([
            'name' => 'Broken Archive',
            'type' => 'online',
            'website' => null
        ]);
        $this->assertFalse($inaccessibleOnlineRepo->isAccessible());
    }

    public function testSourceRelationship()
    {
        $source = new Source([
            'title' => 'Test Source',
            'type' => 'document',
            'repository_id' => $this->repository->id
        ]);

        $sources = $this->repository->sources();
        $this->assertNotNull($sources);
        $this->assertInstanceOf('\\HeritagePress\\Models\\Source', $sources->first());
    }

    public function testAccessUrl()
    {
        $this->assertNull($this->repository->getAccessUrl());

        $onlineRepo = new Repository([
            'name' => 'Digital Archive',
            'type' => 'online',
            'website' => 'http://digital-archive.org'
        ]);
        $this->assertEquals('http://digital-archive.org', $onlineRepo->getAccessUrl());
    }
}
