<?php
echo "Testing source system fix...\n";

$test_file = 'c:/Users/Joe/Documents/Cox Family Tree_2025-05-26.ged';

if (!file_exists($test_file)) {
    echo "File not found: $test_file\n";
    exit;
}

echo "File exists: " . $test_file . "\n";

// Test ImportHandler
echo "Loading ImportHandler...\n";
require_once('c:/MAMP/htdocs/wordpress/wp-content/plugins/heritagepress/HeritagePress/includes/Admin/ImportExport/ImportHandler.php');

echo "Creating ImportHandler instance...\n";
$import_handler = new \HeritagePress\Admin\ImportExport\ImportHandler();

echo "Analyzing GEDCOM file...\n";
$analysis = $import_handler->analyze_gedcom_file($test_file);

if (isset($analysis['error'])) {
    echo "Analysis failed: " . $analysis['message'] . "\n";
} else {
    echo "Source System: " . $analysis['source_system'] . "\n";
    echo "GEDCOM Version: " . $analysis['gedcom_version'] . "\n";
    echo "Encoding: " . $analysis['encoding'] . "\n";

    if ($analysis['source_system'] === 'Family Tree Maker for Windows (Version: 25.0.0.1164)') {
        echo "SUCCESS: Source system correctly extracted!\n";
    } else {
        echo "ISSUE: Expected 'Family Tree Maker for Windows (Version: 25.0.0.1164)'\n";
    }
}
?>