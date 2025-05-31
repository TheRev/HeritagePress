<?php
namespace HeritagePress\Tests;

use PHPUnit\Framework\TestCase;
use HeritagePress\GEDCOM\GedcomRecoveryHandler;

class GedcomRecoveryHandlerTest extends TestCase {
    private $recoveryHandler;

    protected function setUp(): void {
        $this->recoveryHandler = new GedcomRecoveryHandler();
    }

    public function testHandleDateErrors() {
        $testCases = [
            // Invalid format -> Expected format
            'jan 2000' => 'JAN 2000',
            '01/01/2000' => '1 JAN 2000',
            'about 1900' => 'ABT 1900',
            'circa 1850' => 'ABT 1850',
            'before 1875' => 'BEF 1875',
            'after 1925' => 'AFT 1925',
            '1900-1950' => 'FROM 1900 TO 1950',
            '85' => '1985'  // Two-digit year
        ];

        foreach ($testCases as $invalid => $expected) {
            $result = $this->recoveryHandler->handleError('Invalid date format', [
                'type' => 'date',
                'value' => $invalid
            ]);

            $this->assertEquals($expected, $result, "Failed converting date: $invalid");
            $this->assertNotEmpty($this->recoveryHandler->getCorrections());
            $this->recoveryHandler->clearCorrections();
        }
    }

    public function testHandleNameErrors() {
        $testCases = [
            // Invalid format -> Expected format
            'John Doe' => 'John /Doe/',
            'John Smith Jr' => 'John /Smith/ Jr',
            'Mary Jane Wilson' => 'Mary Jane /Wilson/',
            'Smith, John' => 'John /Smith/',
            'DOE, John A.' => 'John A. /DOE/',
            'Jane/Smith' => 'Jane /Smith/'  // Missing closing slash
        ];

        foreach ($testCases as $invalid => $expected) {
            $result = $this->recoveryHandler->handleError('Invalid name format', [
                'type' => 'name',
                'value' => $invalid
            ]);

            $this->assertEquals($expected, $result, "Failed converting name: $invalid");
            $this->assertNotEmpty($this->recoveryHandler->getCorrections());
            $this->recoveryHandler->clearCorrections();
        }
    }

    public function testHandlePlaceErrors() {
        $testCases = [
            // Invalid format -> Expected format
            'New York;NY;USA' => 'New York, NY, USA',
            'New York>NY>USA' => 'New York, NY, USA',
            'New York|NY|USA' => 'New York, NY, USA',
            'Los Angeles-CA-USA' => 'Los Angeles, CA, USA',
            'Boston  MA  USA' => 'Boston, MA, USA',
            'Chicago, IL, USA.' => 'Chicago, IL, USA'
        ];

        foreach ($testCases as $invalid => $expected) {
            $result = $this->recoveryHandler->handleError('Invalid place format', [
                'type' => 'place',
                'value' => $invalid
            ]);

            $this->assertEquals($expected, $result, "Failed converting place: $invalid");
            $this->assertNotEmpty($this->recoveryHandler->getCorrections());
            $this->recoveryHandler->clearCorrections();
        }
    }

    public function testHandleMediaErrors() {
        $testCases = [
            // Invalid format -> Expected format
            'file://path/to/image.jpg' => 'path/to/image.jpg',
            'C:\\path\\to\\image.jpg' => 'C:/path/to/image.jpg',
            '\\\\server\\share\\image.jpg' => 'server/share/image.jpg'
        ];

        foreach ($testCases as $invalid => $expected) {
            $result = $this->recoveryHandler->handleError('Invalid media path', [
                'type' => 'media',
                'value' => $invalid
            ]);

            $this->assertEquals($expected, $result, "Failed converting media path: $invalid");
            $this->assertNotEmpty($this->recoveryHandler->getCorrections());
            $this->recoveryHandler->clearCorrections();
        }
    }

    public function testHandleWarnings() {
        $warning = 'Test warning message';
        $context = ['test' => 'context'];

        $this->recoveryHandler->handleWarning($warning, $context);

        $warnings = $this->recoveryHandler->getWarnings();
        $this->assertNotEmpty($warnings);
        $this->assertEquals($warning, $warnings[0]['message']);
        $this->assertEquals($context, $warnings[0]['context']);
    }

    public function testTrackingCorrections() {
        $this->recoveryHandler->handleError('Test error', [
            'type' => 'date',
            'value' => 'jan 2000'
        ]);

        $corrections = $this->recoveryHandler->getCorrections();
        $this->assertNotEmpty($corrections);
        $this->assertEquals('date', $corrections[0]['type']);
        $this->assertEquals('jan 2000', $corrections[0]['original']);
        $this->assertEquals('JAN 2000', $corrections[0]['corrected']);
    }

    public function testClearingData() {
        // Add some test data
        $this->recoveryHandler->handleError('Test error');
        $this->recoveryHandler->handleWarning('Test warning');
        $this->recoveryHandler->handleError('Test error', [
            'type' => 'date',
            'value' => 'jan 2000'
        ]);

        // Verify data was added
        $this->assertNotEmpty($this->recoveryHandler->getErrors());
        $this->assertNotEmpty($this->recoveryHandler->getWarnings());
        $this->assertNotEmpty($this->recoveryHandler->getCorrections());

        // Clear all data
        $this->recoveryHandler->clearErrors();
        $this->recoveryHandler->clearWarnings();
        $this->recoveryHandler->clearCorrections();

        // Verify all data was cleared
        $this->assertEmpty($this->recoveryHandler->getErrors());
        $this->assertEmpty($this->recoveryHandler->getWarnings());
        $this->assertEmpty($this->recoveryHandler->getCorrections());
    }

    public function testDataChecking() {
        $this->assertFalse($this->recoveryHandler->hasErrors());
        $this->assertFalse($this->recoveryHandler->hasWarnings());
        $this->assertFalse($this->recoveryHandler->hasCorrections());

        $this->recoveryHandler->handleError('Test error');
        $this->recoveryHandler->handleWarning('Test warning');
        $this->recoveryHandler->handleError('Test error', [
            'type' => 'date',
            'value' => 'jan 2000'
        ]);

        $this->assertTrue($this->recoveryHandler->hasErrors());
        $this->assertTrue($this->recoveryHandler->hasWarnings());
        $this->assertTrue($this->recoveryHandler->hasCorrections());
    }
}
