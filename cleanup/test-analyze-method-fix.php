<?php
/**
 * Test the fixed analyze_gedcom_file method
 */

// Add WordPress environment
require_once('wp-config.php');

echo "<h1>Testing Fixed analyze_gedcom_file Method</h1>\n";

try {
    // Load the ImportHandler class
    require_once(__DIR__ . '/includes/Admin/ImportExport/BaseManager.php');
    require_once(__DIR__ . '/includes/Admin/ImportExport/ImportHandler.php');

    $import_handler = new \HeritagePress\Admin\ImportExport\ImportHandler();

    echo "<h2>✅ ImportHandler class loaded successfully</h2>\n";

    // Check if the method exists
    if (method_exists($import_handler, 'analyze_gedcom_file')) {
        echo "<h2>✅ analyze_gedcom_file method exists</h2>\n";

        // Try to find a test GEDCOM file
        $test_files = [
            __DIR__ . '/test-cox-family.ged',
            __DIR__ . '/test-family.ged',
            __DIR__ . '/test-small.ged',
            __DIR__ . '/cox-family.ged'
        ];

        $test_file = null;
        foreach ($test_files as $file) {
            if (file_exists($file)) {
                $test_file = $file;
                break;
            }
        }

        if ($test_file) {
            echo "<h2>✅ Found test file: " . basename($test_file) . "</h2>\n";

            try {
                $analysis = $import_handler->analyze_gedcom_file($test_file);

                echo "<h2>✅ analyze_gedcom_file executed successfully</h2>\n";
                echo "<h3>Analysis Results:</h3>\n";
                echo "<table border='1' style='border-collapse: collapse;'>\n";
                echo "<tr><th>Property</th><th>Value</th></tr>\n";

                foreach ($analysis as $key => $value) {
                    if (is_array($value)) {
                        echo "<tr><td>$key</td><td>" . count($value) . " items</td></tr>\n";
                    } else {
                        echo "<tr><td>$key</td><td>$value</td></tr>\n";
                    }
                }
                echo "</table>\n";

            } catch (Exception $e) {
                echo "<h2>❌ Error running analyze_gedcom_file: " . $e->getMessage() . "</h2>\n";
            }
        } else {
            echo "<h2>⚠️ No test GEDCOM file found</h2>\n";
            echo "<p>Available files in directory:</p>\n";
            echo "<ul>\n";
            foreach (glob(__DIR__ . '/*.ged') as $file) {
                echo "<li>" . basename($file) . "</li>\n";
            }
            echo "</ul>\n";
        }

    } else {
        echo "<h2>❌ analyze_gedcom_file method does not exist</h2>\n";
        echo "<h3>Available methods:</h3>\n";
        echo "<ul>\n";
        foreach (get_class_methods($import_handler) as $method) {
            echo "<li>$method</li>\n";
        }
        echo "</ul>\n";
    }

} catch (Exception $e) {
    echo "<h2>❌ Error loading ImportHandler: " . $e->getMessage() . "</h2>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "<h2>🎯 Critical Error Should Be Fixed</h2>\n";
echo "<p>The critical error on step 2 upload was caused by the missing analyze_gedcom_file() method.</p>\n";
echo "<p>This method has now been added to the ImportHandler class.</p>\n";
echo "<p>Try accessing step 2 again to verify the fix.</p>\n";
?>