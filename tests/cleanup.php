<?php
namespace HeritagePress\Tests;

/**
 * Test cleanup script
 * 
 * This script helps organize and clean up test files by:
 * 1. Moving tests to appropriate directories based on type
 * 2. Updating test class namespaces
 * 3. Ensuring test classes extend the correct base test case
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Define test types and their directories
$testTypes = [
    'Unit' => ['Model', 'Container', 'Event', 'Source', 'Citation', 'Place', 'Repository', 'Simple'],
    'Integration' => ['DatabaseIntegration', 'GedcomEvents', 'GedcomMediaHandler', 'Family', 'Individual'],
    'Functional' => ['FtmImporter', 'RootsMagicImporter', 'SourceQualityService']
];

// Process each test file
$testFiles = glob(__DIR__ . '/*.php');
foreach ($testFiles as $file) {
    $fileName = basename($file);
    if ($fileName === 'bootstrap.php' || strpos($fileName, 'TestCase') !== false) {
        continue;
    }

    $content = file_get_contents($file);
    
    // Determine test type
    $testType = null;
    foreach ($testTypes as $type => $patterns) {
        foreach ($patterns as $pattern) {
            if (strpos($fileName, $pattern) !== false) {
                $testType = $type;
                break 2;
            }
        }
    }

    if (!$testType) {
        continue;
    }

    // Update namespace
    $content = preg_replace(
        '/namespace HeritagePress\\\\Tests;/',
        'namespace HeritagePress\\Tests\\' . $testType . ';',
        $content
    );

    // Update base test case
    $content = preg_replace(
        '/extends TestCase/',
        'extends \\HeritagePress\\Tests\\' . $testType . 'TestCase',
        $content
    );

    // Create directory if it doesn't exist
    $targetDir = __DIR__ . '/' . $testType;
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // Move file
    $targetFile = $targetDir . '/' . $fileName;
    file_put_contents($targetFile, $content);
    unlink($file);
}

echo "Test cleanup completed successfully.\n";
