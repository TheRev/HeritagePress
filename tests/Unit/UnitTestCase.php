<?php
namespace HeritagePress\Tests;

use PHPUnit\Framework\TestCase;

class UnitTestCase extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        $this->setUpMocks();
    }

    protected function setUpMocks(): void {
        // Setup common mocks for unit tests
    }

    protected function tearDown(): void {
        parent::tearDown();
    }

    protected function assertArrayHasKeys(array $keys, array $array) {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array);
        }
    }
}
