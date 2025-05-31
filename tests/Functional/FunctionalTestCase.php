<?php
namespace HeritagePress\Tests;

class FunctionalTestCase extends IntegrationTestCase {
    protected $fixtures = [];

    protected function setUp(): void {
        parent::setUp();
        $this->setUpFixtures();
    }

    protected function setUpFixtures(): void {
        foreach ($this->fixtures as $fixture) {
            $this->loadFixture($fixture);
        }
    }

    protected function loadFixture(string $fixture): void {
        $fixturePath = dirname(__DIR__) . '/test-data/' . $fixture;
        if (file_exists($fixturePath)) {
            $sql = file_get_contents($fixturePath);
            $this->wpdb->query($sql);
        }
    }

    protected function tearDown(): void {
        foreach ($this->fixtures as $fixture) {
            $this->unloadFixture($fixture);
        }
        parent::tearDown();
    }

    protected function unloadFixture(string $fixture): void {
        // Implement cleanup for each fixture type
    }
}
