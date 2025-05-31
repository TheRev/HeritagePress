<?php
namespace HeritagePress\Tests;

use PHPUnit\Framework\TestCase;
use HeritagePress\Core\GedcomEvents;

class GedcomEventsTest extends TestCase {
    protected function setUp(): void {
        GedcomEvents::reset();
    }

    public function testBasicEventHandling() {
        $called = false;
        $eventData = ['test' => 'data'];

        GedcomEvents::on('test_event', function($data) use (&$called, $eventData) {
            $called = true;
            $this->assertEquals($eventData, $data);
        });

        GedcomEvents::trigger('test_event', $eventData);

        $this->assertTrue($called);
    }

    public function testPriorityEventHandling() {
        $output = [];

        GedcomEvents::on('priority_test', function() use (&$output) {
            $output[] = 'normal';
        }, 10);

        GedcomEvents::on('priority_test', function() use (&$output) {
            $output[] = 'high';
        }, 1);

        GedcomEvents::on('priority_test', function() use (&$output) {
            $output[] = 'low';
        }, 20);

        GedcomEvents::trigger('priority_test');

        $this->assertEquals(['high', 'normal', 'low'], $output);
    }

    public function testEventRemoval() {
        $called = false;
        $callback = function() use (&$called) {
            $called = true;
        };

        GedcomEvents::on('removable_event', $callback);
        GedcomEvents::off('removable_event', $callback);
        GedcomEvents::trigger('removable_event');

        $this->assertFalse($called);
    }

    public function testFilterSystem() {
        GedcomEvents::addFilter('test_filter', function($value) {
            return $value . '_1';
        });

        GedcomEvents::addFilter('test_filter', function($value) {
            return $value . '_2';
        });

        $result = GedcomEvents::applyFilters('test_filter', 'start');
        $this->assertEquals('start_1_2', $result);
    }

    public function testFilterPriority() {
        GedcomEvents::addFilter('priority_filter', function($value) {
            return $value . '_normal';
        }, 10);

        GedcomEvents::addFilter('priority_filter', function($value) {
            return $value . '_high';
        }, 1);

        GedcomEvents::addFilter('priority_filter', function($value) {
            return $value . '_low';
        }, 20);

        $result = GedcomEvents::applyFilters('priority_filter', 'start');
        $this->assertEquals('start_high_normal_low', $result);
    }

    public function testFilterRemoval() {
        $callback = function($value) {
            return $value . '_modified';
        };

        GedcomEvents::addFilter('removable_filter', $callback);
        GedcomEvents::removeFilter('removable_filter', $callback);

        $result = GedcomEvents::applyFilters('removable_filter', 'start');
        $this->assertEquals('start', $result);
    }

    public function testNestedEventHandling() {
        $output = [];

        GedcomEvents::on('outer_event', function() use (&$output) {
            $output[] = 'outer_start';
            GedcomEvents::trigger('inner_event');
            $output[] = 'outer_end';
        });

        GedcomEvents::on('inner_event', function() use (&$output) {
            $output[] = 'inner';
        });

        GedcomEvents::trigger('outer_event');

        $this->assertEquals(['outer_start', 'inner', 'outer_end'], $output);
    }

    public function testEventPropagation() {
        $output = [];

        GedcomEvents::on('propagation_test', function() use (&$output) {
            $output[] = 'first';
            return false; // Stop propagation
        });

        GedcomEvents::on('propagation_test', function() use (&$output) {
            $output[] = 'second';
        });

        GedcomEvents::trigger('propagation_test');

        $this->assertEquals(['first'], $output);
    }

    public function testEventContext() {
        $eventData = ['test' => 'data'];
        $expectedContext = [
            'event' => 'context_test',
            'priority' => 10,
        ];

        GedcomEvents::on('context_test', function($data, $context) use ($eventData, $expectedContext) {
            $this->assertEquals($eventData, $data);
            $this->assertArrayHasKey('event', $context);
            $this->assertArrayHasKey('priority', $context);
            $this->assertArrayHasKey('timestamp', $context);
            $this->assertEquals($expectedContext['event'], $context['event']);
            $this->assertEquals($expectedContext['priority'], $context['priority']);
        });

        GedcomEvents::trigger('context_test', $eventData);
    }

    public function testFilterContext() {
        $context = ['test' => 'context'];

        GedcomEvents::addFilter('context_filter', function($value, $ctx) use ($context) {
            $this->assertEquals($context, $ctx);
            return $value . '_filtered';
        });

        $result = GedcomEvents::applyFilters('context_filter', 'start', $context);
        $this->assertEquals('start_filtered', $result);
    }

    public function testStandardEvents() {
        $events = [
            GedcomEvents::BEFORE_PARSE,
            GedcomEvents::AFTER_PARSE,
            GedcomEvents::BEFORE_CONVERT,
            GedcomEvents::AFTER_CONVERT,
            GedcomEvents::BEFORE_SAVE,
            GedcomEvents::AFTER_SAVE,
            GedcomEvents::ERROR,
            GedcomEvents::WARNING,
            GedcomEvents::CORRECTION,
            GedcomEvents::PLACE_STANDARDIZED,
            GedcomEvents::MEDIA_PROCESSED
        ];

        foreach ($events as $event) {
            $called = false;
            GedcomEvents::on($event, function() use (&$called) {
                $called = true;
            });

            GedcomEvents::trigger($event);
            $this->assertTrue($called, "Event $event was not triggered");
        }
    }

    public function testSystemState() {
        $this->assertEmpty(GedcomEvents::getRegisteredEvents());
        $this->assertEmpty(GedcomEvents::getRegisteredFilters());

        GedcomEvents::on('test_event', function() {});
        GedcomEvents::addFilter('test_filter', function($value) { return $value; });

        $this->assertContains('test_event', GedcomEvents::getRegisteredEvents());
        $this->assertContains('test_filter', GedcomEvents::getRegisteredFilters());

        GedcomEvents::reset();

        $this->assertEmpty(GedcomEvents::getRegisteredEvents());
        $this->assertEmpty(GedcomEvents::getRegisteredFilters());
    }
}
