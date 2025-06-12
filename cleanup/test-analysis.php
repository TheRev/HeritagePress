<?php
/**
 * Test script to verify GEDCOM analysis functionality
 */

// Include WordPress bootstrap
require_once('../../../../wp-load.php');

// Include the ImportHandler
require_once('includes/Admin/ImportExport/ImportHandler.php');

use HeritagePress\Admin\ImportExport\ImportHandler;

echo "<h1>GEDCOM Analysis Test</h1>";

// Test with our sample file
$test_file = 'cox-family.ged';
if (file_exists($test_file)) {
    echo "<h2>Analyzing: $test_file</h2>";

    $import_handler = new ImportHandler();
    $analysis = $import_handler->analyze_gedcom_file($test_file);

    if (isset($analysis['error'])) {
        echo "<p style='color: red;'>Error: " . $analysis['message'] . "</p>";
    } else {
        echo "<h3>Analysis Results:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Metric</th><th>Value</th></tr>";

        foreach ($analysis as $key => $value) {
            if (is_array($value)) {
                if ($key == 'surnames' || $key == 'places') {
                    echo "<tr><td>" . ucfirst($key) . "</td><td>";
                    $count = 0;
                    foreach ($value as $name => $freq) {
                        if ($count < 5) {
                            echo "$name ($freq)<br>";
                        }
                        $count++;
                    }
                    echo "</td></tr>";
                } elseif ($key == 'date_range') {
                    echo "<tr><td>Date Range</td><td>";
                    if (!empty($value['earliest']) && !empty($value['latest'])) {
                        echo $value['earliest'] . " - " . $value['latest'];
                    } else {
                        echo "Unknown";
                    }
                    echo "</td></tr>";
                } else {
                    echo "<tr><td>" . ucfirst($key) . "</td><td>" . count($value) . " items</td></tr>";
                }
            } else {
                echo "<tr><td>" . ucfirst(str_replace('_', ' ', $key)) . "</td><td>$value</td></tr>";
            }
        }
        echo "</table>";
    }
} else {
    echo "<p style='color: red;'>Test file not found: $test_file</p>";
}

echo "<h2>Testing file upload directory</h2>";
$upload_info = wp_upload_dir();
$heritagepress_dir = $upload_info['basedir'] . '/heritagepress/';
echo "<p>Upload directory: " . $heritagepress_dir . "</p>";

if (is_dir($heritagepress_dir)) {
    $files = scandir($heritagepress_dir);
    echo "<p>Files in upload directory:</p><ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>$file</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p style='color: orange;'>Upload directory does not exist yet</p>";
}
?>