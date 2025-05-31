<?php
namespace HeritagePress\Tests;

use PHPUnit\Framework\TestCase;

class HeritagePressTestCase extends TestCase {
    protected $wpdb;

    protected function setUp(): void {
        parent::setUp();
        global $wpdb;
        $this->wpdb = $wpdb;
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
