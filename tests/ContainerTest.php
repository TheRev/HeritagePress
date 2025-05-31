<?php
namespace HeritagePress\Tests;

use HeritagePress\Core\Container;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase {
    private $container;

    protected function setUp(): void {
        $this->container = Container::getInstance();
    }

    public function testSingleton() {
        $container1 = Container::getInstance();
        $container2 = Container::getInstance();
        
        $this->assertSame($container1, $container2);
    }

    public function testServiceRegistration() {
        $this->container->register('test', new \stdClass());
        $this->assertTrue($this->container->has('test'));
    }

    public function testServiceResolution() {
        $service = new \stdClass();
        $this->container->register('test', $service);
        
        $resolved = $this->container->get('test');
        $this->assertSame($service, $resolved);
    }

    public function testSingletonServiceRegistration() {
        $count = 0;
        $this->container->singleton('counter', function() use (&$count) {
            $count++;
            return new \stdClass();
        });

        $service1 = $this->container->get('counter');
        $service2 = $this->container->get('counter');

        $this->assertSame($service1, $service2);
        $this->assertEquals(1, $count);
    }

    public function testServiceNotFoundException() {
        $this->expectException(\Exception::class);
        $this->container->get('nonexistent');
    }

    public function testClosureResolution() {
        $this->container->register('factory', function() {
            return new \stdClass();
        });

        $service = $this->container->get('factory');
        $this->assertInstanceOf(\stdClass::class, $service);
    }
}
