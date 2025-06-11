<?php
/**
 * Test GEDCOM file validation
 */

// Include WordPress
require_once('c:/MAMP/htdocs/wordpress/wp-config.php');

echo "<h1>GEDCOM File Validation Test</h1>\n";

// Test the validation function directly
echo "<h2>Testing GEDCOM File Validation</h2>\n";

// Load the ImportHandler
try {
    require_once('includes/Admin/ImportExport/ImportHandler.php');

    // Use reflection to access the private validate_gedcom_file method
    $handler = new \HeritagePress\Admin\ImportExport\ImportHandler();
    $reflection = new ReflectionClass($handler);
    $method = $reflection->getMethod('validate_gedcom_file');
    $method->setAccessible(true);

    // Test with existing GEDCOM files
    $test_files = [
        'test-small.ged',
        'test-family.ged',
        'test-cox-family.ged',
        'cox-family.ged'
    ];

    foreach ($test_files as $filename) {
        if (file_exists($filename)) {
            echo "<h3>Testing: $filename</h3>\n";

            // Read first few lines to show content
            $content = file_get_contents($filename);
            $first_lines = implode("\n", array_slice(explode("\n", $content), 0, 5));
            echo "<pre>First 5 lines:\n" . htmlspecialchars($first_lines) . "</pre>\n";

            // Test validation
            $result = $method->invoke($handler, $filename);

            if ($result['valid']) {
                echo "<p style='color: green;'>✅ Validation PASSED</p>\n";
                echo "<p>File info: " . print_r($result['info'], true) . "</p>\n";
            } else {
                echo "<p style='color: red;'>❌ Validation FAILED: " . $result['message'] . "</p>\n";
            }
            echo "<hr>\n";
        } else {
            echo "<p style='color: orange;'>⚠️ File not found: $filename</p>\n";
        }
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error testing validation: " . $e->getMessage() . "</p>\n";
}

echo "<h2>Upload Directory Check</h2>\n";

// Check if upload directory exists and has files
$upload_dir = wp_upload_dir();
$gedcom_dir = $upload_dir['basedir'] . '/heritagepress/gedcom';

echo "<p><strong>Upload directory:</strong> " . $gedcom_dir . "</p>\n";

if (is_dir($gedcom_dir)) {
    echo "<p style='color: green;'>✅ Upload directory exists</p>\n";

    $files = glob($gedcom_dir . '/*.ged');
    if (!empty($files)) {
        echo "<p style='color: green;'>✅ Found " . count($files) . " GEDCOM files in upload directory:</p>\n";
        echo "<ul>\n";
        foreach ($files as $file) {
            $basename = basename($file);
            $size = filesize($file);
            echo "<li>$basename (" . number_format($size) . " bytes)</li>\n";
        }
        echo "</ul>\n";

        // Test validation on uploaded files
        foreach (array_slice($files, 0, 2) as $file) { // Test first 2 files
            echo "<h3>Testing uploaded file: " . basename($file) . "</h3>\n";

            try {
                $result = $method->invoke($handler, $file);

                if ($result['valid']) {
                    echo "<p style='color: green;'>✅ Uploaded file validation PASSED</p>\n";
                } else {
                    echo "<p style='color: red;'>❌ Uploaded file validation FAILED: " . $result['message'] . "</p>\n";

                    // Show first few bytes in hex to check for BOM or other issues
                    $handle = fopen($file, 'rb');
                    $first_bytes = fread($handle, 20);
                    fclose($handle);
                    echo "<p>First 20 bytes (hex): " . bin2hex($first_bytes) . "</p>\n";
                    echo "<p>First 20 bytes (text): " . htmlspecialchars($first_bytes) . "</p>\n";
                }
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Error validating uploaded file: " . $e->getMessage() . "</p>\n";
            }
        }
    } else {
        echo "<p style='color: orange;'>⚠️ No GEDCOM files found in upload directory</p>\n";
    }
} else {
    echo "<p style='color: red;'>❌ Upload directory does not exist</p>\n";
}

echo "<div style='background: #e7f3ff; padding: 15px; margin: 20px 0; border-left: 4px solid #2196f3;'>\n";
echo "<h3>🔧 GEDCOM Validation Fix Applied:</h3>\n";
echo "<p>Updated the validation function to be more robust:</p>\n";
echo "<ul>\n";
echo "<li>✅ Handles whitespace and empty lines at start of file</li>\n";
echo "<li>✅ Case-insensitive header detection</li>\n";
echo "<li>✅ Uses regex pattern matching for better accuracy</li>\n";
echo "<li>✅ Searches first 10 lines for header instead of just first line</li>\n";
echo "</ul>\n";
echo "</div>\n";

?>