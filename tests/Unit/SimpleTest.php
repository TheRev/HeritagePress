<?php
namespace HeritagePress\Tests;

use PHPUnit\Framework\TestCase;
use HeritagePress\Plugin;

class SimpleTest extends TestCase
{
    public function testTrue()
    {
        $this->assertTrue(true);
    }

    public function testPluginClassExists()
    {
        $this->assertTrue(class_exists(Plugin::class));
    }
}
